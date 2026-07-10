<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempo Esgotado - Portal Hotspot</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body class="pagina-expirado">
    <div class="expired-container px-3">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="expired-icon mb-4">⏱</div>
                <h2 class="mb-4 text-dark">O tempo acabou!</h2>
                <div class="alert alert-warning">
                    <strong>Sua sessão de internet chegou ao fim.</strong>
                </div>
                <p class="lead mb-4 text-secondary">Não se preocupe! Para continuar conectado, basta escolher um novo plano.</p>
                <a href="/inicio" class="btn btn-primary btn-lg w-100 fw-bold">Ver Planos de Internet</a>
            </div>
        </div>
    </div>
</body>

</html>