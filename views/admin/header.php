<?php
// views/admin/header.php
$uri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Portal Hotspot</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/all.min.css" rel="stylesheet">
    <!-- Chama apenas o CSS do Admin -->
    <link href="/src/css/admin.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4 shadow-sm sticky-top">
        <div class="container">
            <span class="navbar-brand text-white"><i class="fa-solid fa-wifi text-success"></i> Admin Hotspot</span>
            <div>
                <a href="/admin/dashboard" class="btn <?= (strpos($uri, 'dashboard') !== false || $uri == '/admin') ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>

                <a href="/admin/plans" class="btn <?= strpos($uri, 'plans') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-box"></i> Planos
                </a>

                <a href="/admin/transactions" class="btn <?= strpos($uri, 'transactions') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-list-check"></i> Transações
                </a>

                <!-- Correção do ícone aqui (de fa-router para fa-network-wired) -->
                <a href="/admin/roteadores" class="btn <?= strpos($uri, 'roteadores') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-network-wired"></i> Roteadores
                </a>

                <a href="/admin/anuncio" class="btn <?= (strpos($uri, 'anuncio') !== false && strpos($uri, 'relatorio') === false) ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-tv"></i> Anúncios
                </a>

                <a href="/admin/relatorio-anuncios" class="btn <?= strpos($uri, 'relatorio-anuncios') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-chart-pie"></i> Relatórios
                </a>

                <a href="/logs.php" class="btn <?= strpos($uri, 'logs') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-terminal"></i> Logs
                </a>

                <a href="/admin/logout" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-power-off"></i> Sair
                </a>
            </div>
        </div>
    </nav>