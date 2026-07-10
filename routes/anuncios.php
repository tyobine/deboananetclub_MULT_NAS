<?php
// index.php - Rotas de anúncios

// As rotas abaixo devem ser adicionadas ao seu roteador principal

// Rota GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (preg_match('#^/admin/anuncio/?$#', $uri)) {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->index();
    }
}

// Rotas POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($uri === '/admin/anuncio/salvar-cliente') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->salvar_anunciante();
    }
    
    elseif ($uri === '/admin/anuncio/salvar-local') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->salvar_local();
    }
    
    elseif ($uri === '/admin/anuncio/upload-midia') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->upload_midia();
    }
    
    elseif ($uri === '/admin/anuncio/toggle-midia') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->toggle_exibicao();
    }
    
    elseif ($uri === '/admin/anuncio/editar-link') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->editar_link();
    }
    
    elseif ($uri === '/admin/anuncio/delete-midia') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->deletar_midia();
    }
    
    // ===== NOVAS ROTAS =====
    elseif ($uri === '/admin/anuncio/renovar-anuncio') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->renovar_anuncio();
    }
    
    elseif ($uri === '/admin/anuncio/reativar-anuncio') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->reativar_anuncio();
    }
    
    elseif ($uri === '/admin/anuncio/editar-data-fim') {
        require_once __DIR__ . '/controllers/admin/anuncios.php';
        $controller = new Anuncios();
        $controller->editar_data_fim();
    }
}

// ===== FIM DAS ROTAS DE ANÚNCIOS =====
// Adicione estas rotas ao seu arquivo de roteamento principal (index.php)
// e certifique-se de que a classe Dashboard está sendo carregada também

// Rota do Dashboard com novas funcionalidades
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (preg_match('#^/admin/dashboard/?$#', $uri) || preg_match('#^/admin/?$#', $uri)) {
        require_once __DIR__ . '/controllers/admin/dashboard.php';
        $controller = new Dashboard();
        $controller->index();
    }
}
?>
