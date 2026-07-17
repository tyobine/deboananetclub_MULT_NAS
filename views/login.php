<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Wi-Fi - Autenticação</title>

    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="/src/css/all.min.css">
    
    <?php if (isset($limpar_url) && $limpar_url): ?>
    <script>
        // Truque Mágico: Apaga o MAC e IP da URL sem recarregar a página.
        // Isso evita MAC Spoofing e burla o "Bounce Tracking" do Google Chrome.
        window.history.replaceState({}, document.title, "/inicio");
    </script>
    <?php endif; ?>
</head>

<body class="pagina-login">
    <div class="portal-container px-3 py-4">

        <?php if (empty($sessaoAtiva)): ?>
        <div class="text-center text-white mb-4">
            <h2 class="fw-bold mb-1">Libere sua Internet</h2>
            <p class="small text-white-50 mb-2">Selecione uma das opções abaixo para conectar o seu acesso.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($sessaoAtiva)): ?>
            <div class="card p-4 text-center mb-4 border-success shadow-lg">
                <div class="card-body p-2">
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

                    <?php if (!empty($anuncioPosPago)): ?>
                        <div class="mb-3">
                            <a href="<?= htmlspecialchars($anuncioPosPago['link_destino'] ?: '#') ?>" target="_blank" rel="noopener noreferrer" onclick="
                                fetch('/visualizacao', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ anuncio_id: <?= $anuncioPosPago['id'] ?> }) });
                                if('<?= htmlspecialchars($anuncioPosPago['link_destino']) ?>' !== '') fetch('/clique?id=<?= $anuncioPosPago['id'] ?>');
                            ">
                                <?php if ($anuncioPosPago['tipo'] === 'video'): ?>
                                    <video autoplay muted loop playsinline class="img-fluid rounded shadow-sm" style="aspect-ratio: 3/4; width: 100%; height: auto; object-fit: cover; display: block;">
                                        <source src="<?= htmlspecialchars($anuncioPosPago['caminho_arquivo']) ?>" type="video/mp4">
                                    </video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($anuncioPosPago['caminho_arquivo']) ?>" class="img-fluid rounded shadow-sm" style="aspect-ratio: 3/4; width: 100%; height: auto; object-fit: cover; display: block;" alt="Patrocinador">
                                <?php endif; ?>
                            </a>
                        </div>
                        <script>
                            fetch('/visualizacao', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ anuncio_id: <?= $anuncioPosPago['id'] ?> })
                            });
                        </script>
                    <?php endif; ?>

                    <div class="mt-3">
                        <button class="btn btn-primary w-100 py-2 fw-bold shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlanos" aria-expanded="false" aria-controls="collapsePlanos">
                            <i class="bi bi-cart-plus"></i> Comprar Mais Tempo
                        </button>
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

        <?php if (!empty($sessaoAtiva)): ?>
            <div class="collapse mt-4 text-start" id="collapsePlanos">
                <hr class="mb-4">
        <?php else: ?>
        <div class="card p-4 mb-4 shadow-sm border-0">
            <div class="card-body p-1">
        <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger py-2 text-center small mb-3">
                        Erro ao processar o plano. Por favor, tente novamente.
                    </div>
                <?php endif; ?>

                <form method="POST" action="/gerar-pix">
                    <input type="hidden" name="mac" value="<?php echo htmlspecialchars($mac ?? ''); ?>">
                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip ?? ''); ?>">

                    <h5 class="fw-bold text-dark mb-3">
                        <?php echo !empty($sessaoAtiva) ? 'Adicionar mais tempo ao seu plano:' : 'Escolha seu Plano de Acesso:'; ?>
                    </h5>

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
                                                <div class="text-warning small fw-bold mt-1">
                                                    🐢 Velocidade Reduzida
                                                </div>
                                            <?php else: ?>
                                                <div class="text-success small fw-bold mt-1">
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

        <?php if (!empty($sessaoAtiva)): ?>
            </div> <!-- fecha collapse -->
            </div> <!-- fecha card-body -->
            </div> <!-- fecha card -->
        <?php else: ?>
            </div> <!-- fecha card-body -->
            </div> <!-- fecha card -->
        <?php endif; ?>

        <div class="text-center mt-4 pb-4">
            <small class="text-white-50">Pagamento seguro via PIX - Mercado Pago</small><br>
            <small><a href="/politicas" target="_blank" class="text-white-50 text-decoration-none text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Termos de Uso e LGPD</a></small>
        </div>
    </div>

    <script src="/src/bootstrap.bundle.min.js"></script>
    <script>
        // Trava do botão de prosseguir (Evita duplo clique)
        var form = document.querySelector('form[action="/gerar-pix"]');
        var btn = document.getElementById('btn-prosseguir');
        if (form && btn) {
            form.addEventListener('submit', function() {
                btn.setAttribute('disabled', 'true');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> A carregar...';
            });
        }

        // Auto-scroll suave ao expandir os planos de recompra
        var collapsePlanos = document.getElementById('collapsePlanos');
        if (collapsePlanos) {
            collapsePlanos.addEventListener('shown.bs.collapse', function () {
                collapsePlanos.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    </script>

    <?php require_once __DIR__ . '/chatbot.php'; ?>

</body>

</html>