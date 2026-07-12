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

        $mac_url = strtoupper(urldecode($_GET['mac'] ?? ''));
        $ip_url = $_GET['ip'] ?? '';

        // CAPTURA MULTI-NAS ROUTER ID
        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();
        
        $router_url = strtolower(trim($_GET['router'] ?? ''));
        if (!empty($router_url) && $modeloRoteador->obterPorIdentificador($router_url)) {
            setcookie('router_id', $router_url, time() + (86400 * 30), "/");
            $router_id = $router_url;
        } else {
            $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');
        }

        // CORREÇÃO TÉCNICA: Persistência do IP Local por Cookie (Evita quebra do Anti-Spoofing)
        $ip = '';
        if (!empty($ip_url)) {
            setcookie('ip_cliente', $ip_url, time() + (86400 * 30), "/");
            $ip = $ip_url;
        } elseif (!empty($_COOKIE['ip_cliente'])) {
            $ip = $_COOKIE['ip_cliente'];
        } else {
            $ip = Rede::obterIpCliente();
        }

        $mac = '';
        // Prioridade 1: Veio direto do roteador agora (URL)
        if (!empty($mac_url)) {
            setcookie('mac_cliente', $mac_url, time() + (86400 * 30), "/");
            $mac = $mac_url;
        }
        // Prioridade 2: Cookie ou Banco de Dados
        elseif (!empty($_COOKIE['mac_cliente'])) {
            $mac = $_COOKIE['mac_cliente'];
        } else {
            $ultimoAcesso = $db->getRow("SELECT mac_address FROM acessos_pix WHERE ip_address = ? ORDER BY id DESC LIMIT 1", [$ip]);
            if ($ultimoAcesso) {
                $mac = $ultimoAcesso['mac_address'];
                setcookie('mac_cliente', $mac, time() + (86400 * 30), "/");
            }
        }

        // TRAVA DE SEGURANÇA: IMPEDIR COMPRA PELO 4G (Aceitando rede local)
        if (empty($mac_url) && !preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ip)) {
            require_once __DIR__ . '/../views/institucional.php';
            exit;
        }

        if (empty($mac)) {
            $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
            $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
            header("Location: http://{$mikrotikGateway}/login");
            exit;
        }

        if (!empty($mac)) {
            // Busca a sessão ativa do usuário (Gratuita ou Paga)
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

                if ($acesso['ip_address'] !== $ip) {
                    $log = date('Y-m-d H:i:s') . " - 🚨 TENTATIVA DE SPOOFING BLOQUEADA | MAC: $mac | IP Esperado: {$acesso['ip_address']} | IP Invasor: $ip\n";
                    file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
                }
                // UX: Se o usuário caiu do Wi-Fi e o tempo (ex: 10 min) ainda não acabou, reconecta ele sem mostrar planos
                elseif ($expiracao > $agora) {
                    $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                    $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
                    $urlSucesso = "http://" . $_SERVER['HTTP_HOST'] . "/sucesso?mac=" . urlencode($mac);
                    
                    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Reconectando...</title></head>
                    <body style='background:#f8f9fa;display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;margin:0;'>
                    <div style='text-align:center; padding: 20px;'>
                        <div style='width: 3rem; height: 3rem; border: 4px solid #0d6efd; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem auto;'></div>
                        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
                        <h2 style='color:#333; margin-bottom: 10px;'>Reconectando...</h2>
                        <p style='color:#666;'>Você possui um plano ativo. Liberando o acesso...</p>
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
        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        $ip = $_REQUEST['ip'] ?? '';

        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();
        $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');

        if (empty($ip)) {
            $ip = $_COOKIE['ip_cliente'] ?? Rede::obterIpCliente();
        }

        if (empty($mac)) {
            $mac = $_COOKIE['mac_cliente'] ?? '';
        }

        if (empty($mac)) {
            $ultimoAcesso = $db->getRow("SELECT mac_address FROM acessos_pix WHERE ip_address = ? ORDER BY id DESC LIMIT 1", [$ip]);
            if ($ultimoAcesso) {
                $mac = $ultimoAcesso['mac_address'];
            }
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

        // Lógica de Plano Grátis
        if (intval($plano['price_cents']) === 0) {
            $ultimoGratis = $db->getRow("
                SELECT expira_em FROM acessos_pix 
                WHERE mac_address = ? AND plano_id = ? 
                ORDER BY id DESC LIMIT 1
            ", [$mac, $plano_id]);

            if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
                date_default_timezone_set('America/Fortaleza');
                $agora = time();
                
                // O momento exato em que os 10 minutos (duration_minutes) terminam
                $expiracao = strtotime($ultimoGratis['expira_em']);

                // CENÁRIO 1: O PLANO AINDA ESTÁ ATIVO (O cliente voltou antes do tempo acabar)
                if ($agora < $expiracao) {
                    $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                    $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';
                    $urlSucesso = "http://" . $_SERVER['HTTP_HOST'] . "/sucesso?mac=" . urlencode($mac);
                    
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

                // CENÁRIO 2: O TEMPO DE USO ACABOU. INICIA A CARÊNCIA.
                $carencia_bd = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'tempo_carencia'");
                $minutos_carencia = $carencia_bd ? intval($carencia_bd['valor']) : 15;
                
                // A carência (ex: 15min) é somada em cima do momento que o plano expirou
                $hora_liberacao_nova = $expiracao + ($minutos_carencia * 60);

                // Se ainda está na janela de bloqueio
                if ($agora < $hora_liberacao_nova) {
                    $min_restantes = ceil(($hora_liberacao_nova - $agora) / 60);
                    require_once __DIR__ . '/../views/limite.php';
                    exit;
                }
            }

            // A carência acabou ou o usuário nunca usou plano grátis
            require_once __DIR__ . '/../views/publicidade.php';
            exit;
        }

        // Lógica de Plano Pago (PIX) - Não tem bloqueio de carência, é imediato.
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
        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        $ip = $_REQUEST['ip'] ?? '';

        $modeloRoteador = new Roteador();
        $padraoRoteador = $modeloRoteador->obterPadrao();
        $router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');

        $whatsapp_raw = $_REQUEST['whatsapp'] ?? '';
        $whatsapp_numero = preg_replace('/[^0-9]/', '', $whatsapp_raw);
        if (empty($whatsapp_numero)) {
            $whatsapp_numero = null;
        }

        if (!$plano_id || empty($mac)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos ou incompletos.']);
            exit;
        }

        if (empty($ip)) {
            $ip = $_COOKIE['ip_cliente'] ?? Rede::obterIpCliente();
        }

        $db = new Banco();
        $plano = $db->getRow("SELECT * FROM planos WHERE id = ?", [$plano_id]);

        if (!$plano || intval($plano['price_cents']) !== 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Plano inválido.']);
            exit;
        }

        // SEGURANÇA DA API: Validação rigorosa na linha de chegada
        $ultimoGratis = $db->getRow("
            SELECT expira_em, status FROM acessos_pix 
            WHERE mac_address = ? AND plano_id = ? 
            ORDER BY id DESC LIMIT 1
        ", [$mac, $plano_id]);

        if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
            date_default_timezone_set('America/Fortaleza');
            $agora = time();
            $expiracao = strtotime($ultimoGratis['expira_em']);

            // Tentou enviar o comando de liberação mesmo com o plano em uso?
            if ($agora < $expiracao && $ultimoGratis['status'] === 'ativo') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Você já possui um plano em uso no momento.']);
                exit;
            }

            // O tempo de uso expirou, valida a carência dinamicamente
            $carencia_bd = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'tempo_carencia'");
            $minutos_carencia = $carencia_bd ? intval($carencia_bd['valor']) : 15;
            $hora_liberacao_nova = $expiracao + ($minutos_carencia * 60);

            // Tentou burlar a tela e disparar a API durante a carência?
            if ($agora < $hora_liberacao_nova) {
                $min_restantes = ceil(($hora_liberacao_nova - $agora) / 60);
                echo json_encode(['sucesso' => false, 'mensagem' => "Acesso negado. Aguarde {$min_restantes} minutos de carência."]);
                exit;
            }
        }

        $txid_gratis = "PUB-" . time() . "-" . rand(1000, 9999);
        date_default_timezone_set('America/Fortaleza');
        
        // Define dinamicamente o tempo que o cliente vai usar, baseado no plano criado.
        $minutos_do_plano = intval($plano['duration_minutes']);
        $expiracao_calculada = date('Y-m-d H:i:s', strtotime("+{$minutos_do_plano} minutes"));

        $db->query("
            INSERT INTO acessos_pix (txid, status, ip_address, mac_address, whatsapp, plano_id, expira_em, router_id) 
            VALUES (?, 'processando', ?, ?, ?, ?, ?, ?)
        ", [$txid_gratis, $ip, $mac, $whatsapp_numero, $plano_id, $expiracao_calculada, $router_id]);

        try {
            $mk = new Mikrotik($router_id);
            $liberouNoRouter = $mk->liberarAcessoTempo($mac, $minutos_do_plano, 'plano_gratis');

            if ($liberouNoRouter) {
                $db->query("UPDATE acessos_pix SET status = 'ativo' WHERE txid = ?", [$txid_gratis]);

                $rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
                $mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';

                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Acesso libertado!',
                    'mac' => $mac,
                    'hotspot_ip' => $mikrotikGateway
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