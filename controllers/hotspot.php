<?php
// controllers/hotspot.php

require_once __DIR__ . '/../models/banco.php';
require_once __DIR__ . '/../models/MercadoPago.php';
require_once __DIR__ . '/../models/mikrotik.php';
require_once __DIR__ . '/../models/Roteador.php';
require_once __DIR__ . '/../utils/rede.php';

class Hotspot
{

    public function index()
    {
        $db = new Banco();
        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();

        $limpar_url = false;
        
        // 1. INTERCEPTAÇÃO INTELIGENTE (Sem redirecionamento PHP para evitar bloqueio do Chrome)
        if (isset($_GET['mac']) || isset($_GET['ip']) || isset($_GET['router'])) {
            
            $mac = strtoupper(urldecode($_GET['mac'] ?? ''));
            $ip = $_GET['ip'] ?? '';
            $router_id = strtolower(trim($_GET['router'] ?? ''));

            if (!empty($mac)) setcookie('mac_cliente', $mac, time() + (86400 * 30), "/");
            if (!empty($ip)) setcookie('ip_cliente', $ip, time() + (86400 * 30), "/");
            
            if (!empty($router_id) && $modeloRoteador->obterPorIdentificador($router_id)) {
                setcookie('router_id', $router_id, time() + (86400 * 30), "/");
            } else {
                $router_id = $padraoRoteador['nome_identificador'] ?? '';
            }

            // Avisa a View (HTML) para limpar a barra de endereços via JavaScript
            $limpar_url = true;

        } else {
            // 2. RECUPERAÇÃO SEGURA DOS COOKIES (Se a URL já veio limpa)
            $mac = $_COOKIE['mac_cliente'] ?? '';
            $ip = $_COOKIE['ip_cliente'] ?? '';
            $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');
        }

        // 3. TRAVA DE SEGURANÇA BLINDADA: IMPEDIR COMPRA PELO 4G EXTERNO
        $ip_cliente_real = Rede::obterIpCliente();
        
        // Checa se é IP de rede local (RFC 1918)
        $is_local_ip = preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ip_cliente_real);
        
        // Checa se o IP do cliente é o mesmo IP Público do MikroTik (caso de portal hospedado na nuvem/NAT)
        $is_router_ip = false;
        // CORRIGIDO: Retornando para a tabela correta do seu banco de dados
        $roteadores = $db->getAll("SELECT host FROM crm_roteadores"); 
        foreach ($roteadores as $rot) {
            $rot_ip = gethostbyname($rot['host']);
            if ($ip_cliente_real === $rot_ip) {
                $is_router_ip = true;
                break;
            }
        }

        // Se o IP não for local e não for do router, bloqueia.
        if (!$is_local_ip && !$is_router_ip) {
            require_once __DIR__ . '/../views/institucional.php';
            exit;
        }

        // SE O PHP NÃO SABE O MAC, ELE DEVOLVE PARA O MIKROTIK FORÇAR O ENVIO DOS DADOS
        if (empty($mac)) {
            $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
            $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
            header("Location: http://{$mikrotikGateway}/login");
            exit;
        }

        // 4. LÓGICA DE SESSÃO ATIVA (Cronômetro na tela)
        $sessaoAtiva = null;
        if (!empty($mac)) {
            $acesso = $db->getRow("
                SELECT a.id, a.expira_em, a.ip_address, p.name as plano_nome 
                FROM acessos_pix a
                LEFT JOIN planos p ON a.plano_id = p.id
                WHERE a.mac_address = ? AND a.status = 'ativo' 
                ORDER BY a.id DESC LIMIT 1
            ", [$mac]);

            if ($acesso) {
                date_default_timezone_set('America/Fortaleza');
                $agora = time();
                $expiracao = strtotime($acesso['expira_em']);

                if ($expiracao > $agora) {
                    $tempoRestante = $expiracao - $agora;
                    $sessaoAtiva = $acesso;

                    $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                    $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';

                    $configAd = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'exibir_ad_pos_pago'");
                    $exibir_ad_pos_pago = $configAd ? $configAd['valor'] : 'passivo';

                    $anuncioPosPago = null;
                    if ($exibir_ad_pos_pago === 'passivo') {
                        $anuncios = $db->getAll("SELECT id, tipo, caminho_arquivo, link_destino FROM crm_anuncios WHERE exibir = 'sim' AND (FIND_IN_SET(?, localizacao) > 0 OR localizacao = 'todos')", [$router_id]);
                        if (empty($anuncios)) {
                            $anuncios = $db->getAll("SELECT id, tipo, caminho_arquivo, link_destino FROM crm_anuncios WHERE exibir = 'sim'");
                        }
                        if (!empty($anuncios)) {
                            $anuncioPosPago = $anuncios[array_rand($anuncios)];
                        }
                    }
                }
                else {
                    $db->query("UPDATE acessos_pix SET status = 'expirado' WHERE id = ?", [$acesso['id']]);
                }
            }
        }

        $plans = $db->getAll("SELECT * FROM planos WHERE ativo = 1 ORDER BY price_cents ASC");

        require_once __DIR__ . '/../views/login.php';
    }

    public function gerarCobranca()
    {
        $db = new Banco();

        $plano_id = $_REQUEST['plan_id'] ?? null;
        
        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();
        $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');

        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        if (empty($mac)) {
            $mac = $_COOKIE['mac_cliente'] ?? '';
        }

        $ip = $_REQUEST['ip'] ?? '';
        if (empty($ip)) {
            $ip = $_COOKIE['ip_cliente'] ?? '';
        }

        if (empty($mac)) {
            $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
            $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
            header("Location: http://{$mikrotikGateway}/login");
            exit;
        }

        if (!$plano_id) {
            header("Location: /inicio?error=1");
            exit;
        }

        $plano = $db->getRow("SELECT * FROM planos WHERE id = ?", [$plano_id]);

        if (!$plano) {
            header("Location: /inicio?error=1");
            exit;
        }

        if (intval($plano['price_cents']) === 0) {
            $ultimoGratis = $db->getRow("
                SELECT expira_em FROM acessos_pix 
                WHERE mac_address = ? AND plano_id = ? 
                ORDER BY id DESC LIMIT 1
            ", [$mac, $plano_id]);

            if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
                date_default_timezone_set('America/Fortaleza');
                $agora = time();
                $expiracao = strtotime($ultimoGratis['expira_em']);

                if ($agora < $expiracao) {
                    $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                    $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
                    $urlSucesso = "http://" . $_SERVER['HTTP_HOST'] . "/sucesso";
                    
                    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Reconectando...</title></head>
                    <body style='background:#f8f9fa;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;margin:0;'>
                    <div style='text-align:center; padding: 20px;'>
                        <div style='width: 3rem; height: 3rem; border: 4px solid #0d6efd; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem auto;'></div>
                        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
                        <h2 style='color:#333; margin-bottom: 10px;'>Reconectando...</h2>
                        <p style='color:#666;'>Você ainda tem tempo grátis. Liberando o acesso...</p>
                    </div>
                    <form id='autoLoginForm' method='POST' action='http://{$mikrotikGateway}/login' style='display:none;'>
                        <input type='hidden' name='username' value='{$mac}'>
                        <input type='hidden' name='password' value='{$mac}'>
                        <input type='hidden' name='dst' value='{$urlSucesso}'>
                    </form>
                    <script>setTimeout(function(){ document.getElementById('autoLoginForm').submit(); }, 1500);</script>
                    </body></html>";
                    exit;
                }

                $carencia_bd = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'tempo_carencia'");
                $minutos_carencia = $carencia_bd ? intval($carencia_bd['valor']) : 15;
                $hora_liberacao_nova = $expiracao + ($minutos_carencia * 60);

                if ($agora < $hora_liberacao_nova) {
                    $min_restantes = ceil(($hora_liberacao_nova - $agora) / 60);
                    require_once __DIR__ . '/../views/limite.php';
                    exit;
                }
            }

            require_once __DIR__ . '/../views/publicidade.php';
            exit;
        }

        $mp = new MercadoPago();
        $dadosPix = $mp->criarPix($plano['price_cents'], $mac, $ip, $plano_id, $plano['name'], $router_id);

        if ($dadosPix && isset($dadosPix['id'])) {
            $db->query("
                INSERT INTO acessos_pix (txid, status, ip_address, mac_address, plano_id, expira_em, router_id) 
                VALUES (?, 'pendente', ?, ?, ?, NULL, ?)
            ", [$dadosPix['id'], $ip, $mac, $plano_id, $router_id]);

            $qr_code = $dadosPix['point_of_interaction']['transaction_data']['qr_code'];
            $qr_code_img = $dadosPix['point_of_interaction']['transaction_data']['qr_code_base64'];
            $txid = $dadosPix['id'];

            $plano_horas = $plano['duration_minutes'] / 60;
            $valor = $plano['price_cents'] / 100;

            require_once __DIR__ . '/../views/pagamento.php';
        } else {
            echo "<h1>Erro ao gerar o PIX junto ao Mercado Pago. Tente novamente.</h1>";
        }
    }

    public function liberarGratisConfirmado()
    {
        header('Content-Type: application/json');

        $plano_id = $_REQUEST['plan_id'] ?? null;
        $anuncio_id_clicado = isset($_REQUEST['anuncio_id_clicado']) ? (int)$_REQUEST['anuncio_id_clicado'] : 0;
        $url_redirecionamento_final = $_REQUEST['url_redirecionamento_final'] ?? '';

        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();
        $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');

        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        if (empty($mac)) {
            $mac = $_COOKIE['mac_cliente'] ?? '';
        }

        $ip = $_REQUEST['ip'] ?? '';
        if (empty($ip)) {
            $ip = $_COOKIE['ip_cliente'] ?? '';
        }

        $whatsapp_raw = $_REQUEST['whatsapp'] ?? '';
        $whatsapp_numero = preg_replace('/[^0-9]/', '', $whatsapp_raw);
        if (empty($whatsapp_numero)) {
            $whatsapp_numero = null;
        }

        if (!$plano_id || empty($mac)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos. Acesso negado.']);
            exit;
        }

        $db = new Banco();
        $plano = $db->getRow("SELECT * FROM planos WHERE id = ?", [$plano_id]);

        if (!$plano || intval($plano['price_cents']) !== 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Plano inválido.']);
            exit;
        }

        $ultimoGratis = $db->getRow("
            SELECT expira_em, status FROM acessos_pix 
            WHERE mac_address = ? AND plano_id = ? 
            ORDER BY id DESC LIMIT 1
        ", [$mac, $plano_id]);

        if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
            date_default_timezone_set('America/Fortaleza');
            $agora = time();
            $expiracao = strtotime($ultimoGratis['expira_em']);

            if ($agora < $expiracao && $ultimoGratis['status'] === 'ativo') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Você já possui um plano em uso no momento.']);
                exit;
            }

            $carencia_bd = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'tempo_carencia'");
            $minutos_carencia = $carencia_bd ? intval($carencia_bd['valor']) : 15;
            $hora_liberacao_nova = $expiracao + ($minutos_carencia * 60);

            if ($agora < $hora_liberacao_nova) {
                $min_restantes = ceil(($hora_liberacao_nova - $agora) / 60);
                echo json_encode(['sucesso' => false, 'mensagem' => "Acesso negado. Aguarde {$min_restantes} minutos de carência."]);
                exit;
            }
        }

        $txid_gratis = "PUB-" . time() . "-" . rand(1000, 9999);
        date_default_timezone_set('America/Fortaleza');
        
        $minutos_do_plano = intval($plano['duration_minutes']);
        $expiracao_calculada = date('Y-m-d H:i:s', strtotime("+{$minutos_do_plano} minutes"));

        $db->query("
            INSERT INTO acessos_pix (txid, status, ip_address, mac_address, whatsapp, plano_id, expira_em, router_id) 
            VALUES (?, 'processando', ?, ?, ?, ?, ?, ?)
        ", [$txid_gratis, $ip, $mac, $whatsapp_numero, $plano_id, $expiracao_calculada, $router_id]);

        if ($anuncio_id_clicado > 0 && !empty($url_redirecionamento_final)) {
            try {
                $db->query("INSERT INTO crm_cliques (anuncio_id, data_registro) VALUES (?, NOW())", [$anuncio_id_clicado]);
            } catch (\Throwable $th) {}
        }

        try {
            $mk = new Mikrotik($router_id);
            $liberouNoRouter = $mk->liberarAcessoTempo($mac, $minutos_do_plano, 'plano_gratis');

            if ($liberouNoRouter) {
                $db->query("UPDATE acessos_pix SET status = 'ativo' WHERE txid = ?", [$txid_gratis]);

                $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';

                if (empty($url_redirecionamento_final)) {
                    $url_redirecionamento_final = "http://" . $_SERVER['HTTP_HOST'] . "/sucesso";
                }

                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Acesso libertado!',
                    'mac' => $mac,
                    'hotspot_ip' => $mikrotikGateway,
                    'redirecionar_para' => $url_redirecionamento_final
                ]);
                exit;
            } else {
                $db->query("UPDATE acessos_pix SET status = 'erro_mikrotik' WHERE txid = ?", [$txid_gratis]);
                echo json_encode(['sucesso' => false, 'mensagem' => 'A torre recusou o comando de liberação técnico.']);
                exit;
            }
        } catch (\Throwable $th) {
            $db->query("UPDATE acessos_pix SET status = 'erro_mikrotik' WHERE txid = ?", [$txid_gratis]);
            echo json_encode(['sucesso' => false, 'mensagem' => 'A torre de transmissão desta localidade está fora de linha ou sem energia neste momento.']);
            exit;
        }
    }

    public function checarStatus()
    {
        $txid = $_GET['txid'] ?? '';

        $db = new Banco();
        $transacao = $db->getRow("SELECT status, mac_address FROM acessos_pix WHERE txid = ?", [$txid]);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => $transacao['status'] ?? 'nao_encontrado',
            'mac' => $transacao['mac_address'] ?? ''
        ]);
        exit;
    }
}
