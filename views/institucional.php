<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeboaNaNet - Internet de Qualidade</title>

    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="/src/css/all.min.css">

</head>

<body class="pagina-institucional">

    <div class="hero-section">
        <h1>Conexão Rápida, Segura e Sem Complicação!</h1>
        <p class="lead mb-4">Descubra o projeto de internet inteligente que está conectando nossa região com alta velocidade e planos que cabem no seu bolso.</p>
        <a href="#como-funciona" class="btn cta-button">Saiba Mais</a>
    </div>

    <div class="container py-5" id="como-funciona">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Como funciona nosso projeto?</h2>
            <p class="text-muted">Nosso sistema de Hotspot foi desenhado para ser simples e transparente.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card card-institucional h-100 p-4">
                    <div class="card-body">
                        <i class="fa-solid fa-wifi feature-icon"></i>
                        <h4 class="fw-bold text-dark">1. Conecte-se</h4>
                        <p class="text-muted">Procure pela nossa rede Wi-Fi aberta em um dos nossos pontos de acesso e conecte seu celular ou notebook.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-institucional h-100 p-4">
                    <div class="card-body">
                        <i class="fa-brands fa-pix feature-icon text-success"></i>
                        <h4 class="fw-bold text-dark">2. Escolha o Plano</h4>
                        <p class="text-muted">A tela de planos abrirá automaticamente. Escolha o tempo de acesso que desejar e pague na hora via PIX.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-institucional h-100 p-4">
                    <div class="card-body">
                        <i class="fa-solid fa-rocket feature-icon text-warning"></i>
                        <h4 class="fw-bold text-dark">3. Navegue!</h4>
                        <p class="text-muted">A liberação é instantânea! O sistema reconhece o pagamento em segundos e libera sua internet em alta velocidade.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5 border-top">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Nossos Locais de Atuação</h2>
            <p class="text-muted">Estamos expandindo nossa rede. Veja onde você já pode encontrar nosso Wi-Fi:</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <ul class="list-group list-group-flush text-start shadow-sm rounded-3">
                    <li class="list-group-item p-3 d-flex align-items-center">
                        <i class="fa-solid fa-location-dot text-danger me-3 fs-4"></i>
                        <div>
                            <strong class="d-block fs-5 text-dark">Praça Principal do Centro</strong>
                            <span class="text-muted small">Cobertura completa em toda a área da praça.</span>
                        </div>
                    </li>
                    <li class="list-group-item p-3 d-flex align-items-center">
                        <i class="fa-solid fa-location-dot text-danger me-3 fs-4"></i>
                        <div>
                            <strong class="d-block fs-5 text-dark">Terminal Rodoviário</strong>
                            <span class="text-muted small">Internet rápida enquanto você aguarda sua viagem.</span>
                        </div>
                    </li>
                    <li class="list-group-item p-3 d-flex align-items-center">
                        <i class="fa-solid fa-location-dot text-danger me-3 fs-4"></i>
                        <div>
                            <strong class="d-block fs-5 text-dark">Condomínio Residencial Parque</strong>
                            <span class="text-muted small">Acesso exclusivo para visitantes e áreas comuns.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-dark text-white py-5 text-center mt-4">
        <div class="container">
            <h3 class="fw-bold mb-3 text-white">Vá agora para um de nossos pontos!</h3>
            <p class="mb-4 text-white-50">Conecte-se à nossa rede e desfrute da melhor conexão da cidade.</p>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="#" class="text-white fs-3"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="text-white fs-3"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" class="text-white fs-3"><i class="fa-brands fa-whatsapp"></i></a>
            </div>

            <hr class="border-secondary">

            <div class="small text-white-50 d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span>&copy; <?php echo date('Y'); ?> DeboaNaNet. Todos os direitos reservados.</span>
                <a href="/politicas" class="text-white-50 text-decoration-none mt-2 mt-md-0">Termos de Uso e LGPD</a>
            </div>
        </div>
    </div>

    <script src="/src/bootstrap.bundle.min.js"></script>
    <?php require_once __DIR__ . '/chatbot.php'; ?>
</body>

</html>