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

// Os roteadores agora são gerenciados pelo banco de dados (tabela crm_roteadores)

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