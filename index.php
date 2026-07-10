<?php
// index.php

// ==========================================================================
// 1. BLOQUEIO GLOBAL DE CACHE PARA PORTAL CATIVO (IOS/ANDROID)
// ==========================================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

// ==========================================================================
// 2. CARREGAMENTO DOS CONTROLADORES
// ==========================================================================
require_once 'controllers/hotspot.php';
require_once 'controllers/webhook.php';
require_once 'controllers/tracking.php'; 
require_once 'controllers/admin/login.php';
require_once 'controllers/admin/dashboard.php';
require_once 'controllers/admin/planos.php';
require_once 'controllers/admin/anuncios.php'; 
require_once 'controllers/admin/transacoes.php';

// ==========================================================================
// 3. MOTOR DE ROTAS BLINDADO
// ==========================================================================
$urlOriginal = $_GET['url'] ?? 'inicio';
$urlLimpa = strtok($urlOriginal, '?');
$url = trim($urlLimpa, '/');

if (empty($url)) $url = 'inicio';

// ==========================================================================
// 4. SWITCH DE ROTAS
// ==========================================================================
switch ($url) {
    // === ÁREA DO CLIENTE ===
    case 'inicio':
        (new Hotspot())->index();
        break;

    case 'politicas':
        require_once 'views/politicas.php';
        break;

    case 'gerar-pix':
        (new Hotspot())->gerarCobranca();
        break;

    case 'webhook':
        (new Webhook())->receberNotificacao();
        break;

    case 'checar-status':
        (new Hotspot())->checarStatus();
        break;

    case 'liberar-gratis-confirmado':
        (new Hotspot())->liberarGratisConfirmado();
        break;

    // === ROTAS DE MÉTRICAS DA PUBLICIDADE ===
    case 'visualizacao':
        (new Tracking())->registrarVisualizacao();
        break;

    case 'clique':
        (new Tracking())->registrarClique();
        break;

    // === ÁREA DO ADMINISTRADOR ===
    case 'admin':
        header("Location: /admin/dashboard");
        exit;

    case 'admin/login':
        (new Login())->tela();
        break;

    case 'admin/login/auth':
        (new Login())->autenticar();
        break;

    case 'admin/logout':
        (new Login())->sair();
        break;

    case 'admin/dashboard':
        (new Dashboard())->index();
        break;

    // === GESTÃO DE PLANOS ===
    case 'admin/plans':
        (new Planos())->index();
        break;

    case 'admin/plans/create':
        (new Planos())->criar();
        break;

    case 'admin/plans/update':
        (new Planos())->atualizar();
        break;

    case 'admin/plans/toggle':
        (new Planos())->alternar();
        break;

    case 'admin/plans/delete':
        (new Planos())->deletar();
        break;

    // === GESTÃO DE ANÚNCIOS (NOVO CRM) ===
    case 'admin/anuncio':
        (new Anuncios())->index();
        break;

    case 'admin/anuncio/salvar-cliente':
        (new Anuncios())->salvar_anunciante();
        break;

    case 'admin/anuncio/upload-midia':
        (new Anuncios())->upload_midia();
        break;

    case 'admin/anuncio/toggle-midia':
        (new Anuncios())->toggle_exibicao();
        break;

    case 'admin/anuncio/delete-midia':
        (new Anuncios())->deletar_midia();
        break;

    case 'admin/anuncio/editar-link':
        (new Anuncios())->editar_link();
        break;

    // 🚀 AQUI ESTÁ A ROTA QUE FALTAVA PARA SALVAR O LOCAL!
    case 'admin/anuncio/salvar-local':
        (new Anuncios())->salvar_local();
        break;

    // === RELATÓRIOS DO CRM ===
    case 'admin/relatorio-anuncios':
        require_once 'views/admin/relatorio_anuncios.php';
        break;

    // === GESTÃO DE TRANSAÇÕES ===
    case 'admin/transacoes':
    case 'admin/transactions':
        (new Transacoes())->index();
        break;

    default:
        // Se a rota não for encontrada, ele manda para o início!
        header("Location: /inicio");
        exit;
}