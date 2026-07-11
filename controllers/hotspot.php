<?php
// controllers/hotspot.php

require_once __DIR__ . '/../models/banco.php';
require_once __DIR__ . '/../models/MercadoPago.php';
require_once __DIR__ . '/../models/mikrotik.php';
require_once __DIR__ . '/../models/Roteador.php';
require_once __DIR__ . '/../utils/rede.php'; // Utilitário de Rede

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

        // 🛡️ CORREÇÃO TÉCNICA: Persistência do IP Local por Cookie (Evita quebra do Anti-Spoofing)
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

        // 🚨 TRAVA DE SEGURANÇA: IMPEDIR COMPRA PELO 4G (Aceitando rede 10., 100., 172.16 e 192.168)
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

        $sessaoAtiva = null;
        $tempoRestante = 0;

        if (!empty($mac)) {
            // Busca a sessão pelo MAC e puxa também o IP de origem da compra
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

                // 🛡️ SEGURANÇA: Trava Anti-Spoofing Corrigida (Agora compara os IPs Locais de forma consistente)
                if ($acesso['ip_address'] !== $ip) {
                    $log = date('Y-m-d H:i:s') . " - 🚨 TENTATIVA DE SPOOFING BLOQUEADA | MAC: $mac | IP Esperado: {$acesso['ip_address']} | IP Invasor: $ip\n";
                    file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);

                    $sessaoAtiva = null;
                    $tempoRestante = 0;
                }
                // Se estiver tudo correto e o tempo for válido
                elseif ($expiracao > $agora) {
                    $sessaoAtiva = $acesso;
                    $tempoRestante = $expiracao - $agora;
                }
                // Se o tempo expirou
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
                $hora_liberacao = strtotime($ultimoGratis['expira_em']) + 3600;

                if (time() < $hora_liberacao) {
                    $min_restantes = ceil(($hora_liberacao - time()) / 60);
                    require_once __DIR__ . '/../views/limite.php';
                    exit;
                }
            }

            require_once __DIR__ . '/../views/publicidade.php';
            exit;
        }

        // Lógica de Plano Pago (PIX)
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

        $jaExiste = $db->getRow("
            SELECT id FROM acessos_pix 
            WHERE mac_address = ? AND plano_id = ? AND status = 'ativo' AND expira_em > NOW()
            ORDER BY id DESC LIMIT 1
        ", [$mac, $plano_id]);

        if ($jaExiste) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Já possui um plano ativo.', 'mac' => $mac]);
            exit;
        }

        $txid_gratis = "PUB-" . time() . "-" . rand(1000, 9999);
        date_default_timezone_set('America/Fortaleza');
        $expiracao = date('Y-m-d H:i:s', strtotime("+" . $plano['duration_minutes'] . " minutes"));

        $db->query("
            INSERT INTO acessos_pix (txid, status, ip_address, mac_address, whatsapp, plano_id, expira_em, router_id) 
            VALUES (?, 'processando', ?, ?, ?, ?, ?, ?)
        ", [$txid_gratis, $ip, $mac, $whatsapp_numero, $plano_id, $expiracao, $router_id]);

        try {
            $mk = new Mikrotik($router_id);
            $liberouNoRouter = $mk->liberarAcessoTempo($mac, intval($plano['duration_minutes']), 'plano_gratis');

            if ($liberouNoRouter) {
                $db->query("UPDATE acessos_pix SET status = 'ativo' WHERE txid = ?", [$txid_gratis]);

                // 🚀 ADICIONADO: Pega o IP do roteador local para mandar para o JavaScript fazer o login
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
