<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Wi-Fi - Autenticação</title>

    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="/src/css/all.min.css">
</head>

<body class="pagina-login">
    <div class="portal-container px-3 py-4">

        <div class="text-center text-white mb-4">
            <h2 class="fw-bold mb-1">Conecte-se ao Wi-Fi</h2>
            <p class="small text-white-50 mb-2">Selecione uma das opções abaixo para libertar o seu acesso.</p>
        </div>

        <?php if (!empty($sessaoAtiva)): ?>
            <div class="card p-4 text-center mb-4 border-success shadow-lg">
                <div class="card-body">
                    <div class="text-success mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                        </svg>
                    </div>
                    <h4 class="fw-bold text-success mb-1">Acesso Já Libertado!</h4>
                    <p class="text-muted small mb-3">Plano Atual: <strong><?php echo htmlspecialchars($sessaoAtiva['plano_nome'] ?? 'Ativo'); ?></strong></p>

                    <div class="alert alert-light border py-2 mb-3">
                        <span class="text-secondary small d-block">Tempo Restante:</span>
                        <span class="fw-bold text-dark fs-5" id="cronometro-regressivo">--:--:--</span>
                    </div>

                    <?php
                    // ENGENHARIA: Formulário POST substitui a tag <a> para evitar bloqueios do CNA da Apple
                    $mikrotikGateway = defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : '192.168.254.1';
                    $macStr = strtoupper($mac ?? ''); // Forçando maiúsculo para casar com o banco do MikroTik
                    ?>

                    <form action="http://<?php echo $mikrotikGateway; ?>/login" method="post" class="mt-2">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($macStr); ?>">
                        <input type="hidden" name="password" value="<?php echo htmlspecialchars($macStr); ?>">

                        <input type="hidden" name="dst" value="http://captive.apple.com/hotspot-detect.html">

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                            🌐 Navegar na Internet
                        </button>
                    </form>
                </div>
            </div>

            <script>
                (function() {
                    var segundosRestantes = <?php echo intval($tempoRestante); ?>;
                    var display = document.getElementById('cronometro-regressivo');

                    function atualizarTimer() {
                        if (segundosRestantes <= 0) {
                            window.location.reload();
                            return;
                        }
                        var hrs = Math.floor(segundosRestantes / 3600);
                        var mins = Math.floor((segundosRestantes % 3600) / 60);
                        var segs = Math.floor(segundosRestantes % 60);

                        display.innerText =
                            (hrs < 10 ? "0" + hrs : hrs) + ":" +
                            (mins < 10 ? "0" + mins : mins) + ":" +
                            (segs < 10 ? "0" + segs : segs);

                        segundosRestantes--;
                    }
                    atualizarTimer();
                    setInterval(atualizarTimer, 1000);
                })();
            </script>
        <?php endif; ?>

        <div class="card p-4">
            <div class="card-body p-1">

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger py-2 text-center small mb-3">
                        Erro ao processar o plano. Por favor, tente novamente.
                    </div>
                <?php endif; ?>

                <form method="POST" action="/gerar-pix">
                    <input type="hidden" name="mac" value="<?php echo htmlspecialchars($mac ?? ''); ?>">
                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip ?? ''); ?>">

                    <h5 class="fw-bold text-dark mb-3">Escolha como deseja acessar:</h5>

                    <div class="mb-4">
                        <?php if (empty($plans)): ?>
                            <div class="alert alert-warning text-center small py-2 m-0">
                                Nenhum plano disponível de momento.
                            </div>
                        <?php else: ?>
                            <?php foreach ($plans as $index => $plan): ?>
                                <label class="d-block mb-3 position-relative" style="cursor: pointer;">
                                    <input type="radio" name="plan_id" value="<?php echo $plan['id']; ?>" class="plan-radio" <?php echo $index === 0 ? 'checked' : ''; ?>>

                                    <div class="plan-card p-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="fw-bold text-dark d-block fs-5"><?php echo htmlspecialchars($plan['name']); ?></span>
                                            <span class="text-muted small">Duração: <?php echo $plan['duration_minutes'] >= 60 ? ($plan['duration_minutes'] / 60) . ' Hora(s)' : $plan['duration_minutes'] . ' Minutos'; ?></span>

                                            <?php if (intval($plan['price_cents']) === 0): ?>
                                                <div class="text-warning small fw-bold mt-1 d-flex align-items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-speedometer2 me-1" viewBox="0 0 16 16">
                                                        <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z" />
                                                        <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3" />
                                                    </svg>
                                                    Velocidade Reduzida
                                                </div>
                                            <?php else: ?>
                                                <div class="text-success small fw-bold mt-1 d-flex align-items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-rocket-takeoff-fill me-1" viewBox="0 0 16 16">
                                                        <path d="M12.17 9.47a1.5 1.5 0 0 0-.32-.38l-1.11-.89-.53.53a.25.25 0 0 1-.36 0L8.08 7.15a.25.25 0 0 1 0-.36l.53-.53-.89-1.11a1.5 1.5 0 0 0-.38-.32L4.24 3.46a.25.25 0 0 0-.33.33L5.45 7l-.5.5a.75.75 0 0 0-.17.25L3.43 11a.5.5 0 0 0 .58.58l3.25-1.35a.75.75 0 0 0 .25-.17l.5-.5 3.21 1.54a.25.25 0 0 0 .33-.33z" />
                                                        <path d="M10.08 2.5a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 0 0 1.06 1.06l3-3a.75.75 0 0 0 0-1.06m3.5 3.5a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 0 0 1.06 1.06l3-3a.75.75 0 0 0 0-1.06" />
                                                        <path d="M14.5 1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.354.146L8.146 3.146a.5.5 0 0 0 .708.708l2.5-2.5H14v2.646a.5.5 0 0 0 .707.708l2.5-2.5A.5.5 0 0 0 14.5 1" />
                                                    </svg>
                                                    🚀 Velocidade Máxima
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold text-success fs-5">
                                                <?php echo intval($plan['price_cents']) === 0 ? 'GRÁTIS' : 'R$ ' . number_format($plan['price_cents'] / 100, 2, ',', '.'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($plans)): ?>
                        <button type="submit" id="btn-prosseguir" class="btn btn-primary btn-lg w-100 fw-bold py-2 shadow-sm">
                            Prosseguir para o Acesso ➔
                        </button>
                    <?php endif; ?>
                </form>

            </div>
        </div>

        <div class="text-center mt-4 pb-4">
            <small class="text-white-50">Pagamento seguro via PIX - Mercado Pago</small><br>
            <small><a href="/politicas" target="_blank" class="text-white-50 text-decoration-none text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Termos de Uso e LGPD</a></small>
        </div>
    </div>

    <script src="/src/bootstrap.bundle.min.js"></script>
    <script>
        var form = document.querySelector('form[action="/gerar-pix"]');
        var btn = document.getElementById('btn-prosseguir');
        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.setAttribute('disabled', 'true');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> A carregar...';
            });
        }
    </script>

    <?php require_once __DIR__ . '/chatbot.php'; ?>

</body>

</html>