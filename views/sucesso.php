<?php
// views/sucesso.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/banco.php';

$mac_cliente = $_GET['mac'] ?? $_COOKIE['mac_cliente'] ?? '';
require_once __DIR__ . '/../models/Roteador.php';
$modeloRoteador = new Roteador();
$padraoRoteador = $modeloRoteador->obterPadrao();
$router_id = $_COOKIE['router_id'] ?? ($padraoRoteador['nome_identificador'] ?? '');
$rotInfo = $modeloRoteador->obterPorIdentificador($router_id) ?: $padraoRoteador;
$mikrotikGateway = $rotInfo['hotspot_ip'] ?? '10.50.0.1';

$planName = 'Plano Ativo';
$duration = '--';
$expiresHuman = '--';

if (!empty($mac_cliente)) {
    $db = new Banco();
    $acesso = $db->getRow("
        SELECT p.name, p.duration_minutes, a.expira_em 
        FROM acessos_pix a 
        JOIN planos p ON a.plano_id = p.id 
        WHERE a.mac_address = ? AND a.status = 'ativo' 
        ORDER BY a.id DESC LIMIT 1
    ", [$mac_cliente]);

    if ($acesso) {
        $planName = $acesso['name'];
        $duration = $acesso['duration_minutes'];
        $expiresHuman = date('d/m/Y H:i', strtotime($acesso['expira_em']));
    }
}

// Pegamos a base segura da URL atual para enviar de volta ao cronômetro
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$urlInicioContador = $protocolo . $host . "/inicio?mac=" . urlencode($mac_cliente);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Liberado - Portal Hotspot</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=2" rel="stylesheet">
</head>

<body class="pagina-sucesso">
    <div class="success-container px-3">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="success-icon mb-4">✓</div>
                <h2 class="mb-4 text-dark">Acesso Liberado!</h2>
                <div class="alert alert-success"><strong>Sua internet já está ativa.</strong></div>
                <div class="mb-4">
                    <p class="text-muted">Plano Liberado:</p>
                    <h4 class="text-dark"><?php echo htmlspecialchars($planName); ?></h4>
                    <p class="text-muted">Duração: <?php echo $duration; ?> minutos</p>
                </div>
                <p class="lead mb-4 text-secondary" id="texto-status">Autenticando seu dispositivo na rede, aguarde...</p>
                <?php if ($expiresHuman != '--'): ?>
                    <p class="small text-muted">Seu acesso expira em <?php echo $expiresHuman; ?></p>
                <?php endif; ?>

                <form id="frmLoginMikrotik" action="http://<?php echo $mikrotikGateway; ?>/login" method="post" style="display: none;">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($mac_cliente); ?>">
                    <input type="hidden" name="password" value="<?php echo htmlspecialchars($mac_cliente); ?>">
                    <input type="hidden" name="dst" value="<?php echo htmlspecialchars($urlInicioContador); ?>">
                </form>

                <button onclick="document.getElementById('frmLoginMikrotik').submit();" class="btn btn-primary d-none w-100 mt-2 p-2" id="btnManual">Conectar Internet</button>
            </div>
        </div>
    </div>

    <script>
        // Engenharia de Rede: Submissão do formulário via POST. 
        // Em 99% das vezes, o iOS aceita essa comunicação POST sem levantar o popup de quebra de segurança HTTPS->HTTP
        setTimeout(() => {
            document.getElementById('frmLoginMikrotik').submit();

            // Mecanismo de Fallback para as restrições da Apple: 
            // Se o CNA do iOS congelar a execução do submit automático para prevenir "ações não solicitadas",
            // mostramos um botão manual após 3 segundos para não prender o usuário.
            setTimeout(() => {
                document.getElementById('texto-status').innerHTML = "Quase pronto! Conclua o seu acesso:";
                document.getElementById('btnManual').classList.remove('d-none');
            }, 3000);

        }, 1500);
    </script>
</body>

</html>