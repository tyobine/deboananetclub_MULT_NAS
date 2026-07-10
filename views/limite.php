<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limite Atingido - Hotspot</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body class="pagina-limite">
    <div class="px-3 w-100 d-flex justify-content-center">
        <div class="limit-card">
            <h2 class="text-danger fw-bold mb-3">⏳ Limite Atingido</h2>
            <p class="text-dark">Você já utilizou o plano grátis recentemente.</p>
            <div class="alert alert-danger py-2">
                Aguarde mais <b><?= htmlspecialchars($min_restantes ?? 'alguns') ?> minutos</b> para usar o grátis novamente.
            </div>
            <p class="text-muted small mb-4">Ou compre um plano pago para navegar agora mesmo com velocidade máxima!</p>
            <a href="/inicio" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm">Ver Planos Pagos</a>
        </div>
    </div>
</body>

</html>