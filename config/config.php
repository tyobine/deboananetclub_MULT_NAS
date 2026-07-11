<?php
// config/config.php

// Força o fuso horário correto para os logs e para a API do MP
date_default_timezone_set('America/Fortaleza'); 

// =========================================================================
// 1. CONFIGURAÇÕES DO BANCO DE DADOS
// =========================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'moveisjb_database');
define('DB_USER', 'moveisjb_hotspot_db');
define('DB_PASS', 'vtgd65aoty');

// =========================================================================
// 2. CONFIGURAÇÕES DOS ROTEADORES MIKROTIK (Multi-NAS Misto)
// =========================================================================
define('ROUTER_DEFAULT', 'sobral');

define('ROUTERS', [
    // 1. SOBRAL (Borda com IP Público Próprio)
    'sobral' => [
        'host'       => 'api.deboananet.club', // IP Público de Sobral
        'user'       => 'admin',
        'pass'       => 'xtz900af',
        'port'       => '8080',                // Porta local da API em Sobral
        'hotspot_ip' => '10.50.0.1'            // COLOQUE O IP DO HOTSPOT DE SOBRAL
    ],

    /*/ 2. MATOS (Sem IP Público - Usa VPN passando por Sobral)
    'matos' => [
        'host'       => '',                    // O site bate na Borda (Sobral)
        'user'       => 'mulato',              // Usuário da RB dos Matos
        'pass'       => 'vtgd65aoty',          // Senha da RB dos Matos
        'port'       => '8083',                // A Borda recebe na 8081 e joga pro túnel VPN dos Matos
        'hotspot_ip' => '10.50.0.1'            // COLOQUE O IP DO HOTSPOT DOS MATOS
    ],

    // 2. mulato (Sem IP Público - Usa VPN passando por Sobral)
    'mulato' => [
        'host'       => '',                    // O site bate na Borda (Sobral)
        'user'       => 'mulato',              // Usuário da RB dos Matos
        'pass'       => 'vtgd65aoty',          // Senha da RB dos Matos
        'port'       => '8083',                // A Borda recebe na 8081 e joga pro túnel VPN dos Matos
        'hotspot_ip' => '10.50.0.1'            // COLOQUE O IP DO HOTSPOT DOS MATOS
    ],   */

	// 3. FORTALEZA (Com IP Público Próprio - Sem VPN)
    'fortaleza' => [
        'host'       => '189.45.78.164',       // COLOQUE AQUI O IP PÚBLICO DE FORTALEZA
        'user'       => 'mulato',              // Usuário da RB de Fortaleza
        'pass'       => 'vtgd65aoty',          // Senha da RB de Fortaleza
        'port'       => '8080',                // Acesso direto na porta 8080, sem passar por Sobral
        'hotspot_ip' => '10.50.0.1'            // COLOQUE O IP DO HOTSPOT DE FORTALEZA
    ]
]);

// =========================================================================
// 3. CREDENCIAIS DE ACESSO AO PAINEL ADMINISTRATIVO
// =========================================================================
define('ADMIN_USER', 'thiago');
define('ADMIN_PASS', 'vtgd65aoty');

// =========================================================================
// 4. CONFIGURAÇÕES DO MERCADO PAGO
// =========================================================================
define('MP_TOKEN', 'APP_USR-1238557524864247-090421-5066188abd1e8361a8a839231b517f29-101398970');

// =========================================================================
// 5. CONFIGURAÇÕES DO TELEGRAM (SUPORTE)
// =========================================================================
define('TELEGRAM_TOKEN', '8987836182:AAFvVYZ7Y16z0_pwnjDn7lwloHbbsrlODlY');
define('TELEGRAM_CHAT_ID', '1625672208');

// =========================================================================
// 6. DETECÇÃO DINÂMICA DE PROTOCOLO E BASE URL
// =========================================================================
$protocolo = 'http';

// Detecta se o HTTPS está ativo (nativamente ou via proxy/Cloudflare)
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocolo = 'https';
}

// Cria a constante BASE_URL baseada no protocolo atual para evitar quebra de CSS/JS
define('BASE_URL', $protocolo . '://deboananet.club');

// Inicia a sessão de forma segura se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>