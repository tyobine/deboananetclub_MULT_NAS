<?php
// index.php

// ==========================================================================
// BLOQUEIO GLOBAL DE CACHE PARA PORTAL CATIVO (IOS/ANDROID)
// ==========================================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT"); // Força uma data no passado

require_once 'controllers/hotspot.php';
require_once 'controllers/webhook.php';
// Carrega os novos controladores do painel de administração fatiado
require_once 'controllers/admin/login.php';
require_once 'controllers/admin/dashboard.php';
require_once 'controllers/admin/planos.php';
require_once 'controllers/admin/anuncios.php';
require_once 'controllers/admin/transacoes.php';

$url = trim($_GET['url'] ?? 'inicio', '/');
if (empty($url)) $url = 'inicio';

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
        case 'sucesso':
                require_once 'views/sucesso.php';
                break;

        case 'expirado':
                require_once 'views/expirado.php';
                break;

        case 'status':
                header("Location: /inicio");
                exit;

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

        // Gestão de Planos
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

        // Gestão de Anúncios
        case 'admin/anuncio':
                (new Anuncios())->index();
                break;

        case 'admin/anuncio/salvar':
                (new Anuncios())->salvar();
                break;

        case 'admin/anuncio/upload':
                (new Anuncios())->upload();
                break;

        case 'admin/anuncio/delete':
                (new Anuncios())->deletar();
                break;

        // Gestão de Transações
        case 'admin/transactions':
                (new Transacoes())->index();
                break;

        // PÁGINA NÃO ENCONTRADA
        default:
                http_response_code(404);
                echo "<h1>404 - Página não encontrada</h1>";
                break;
}
