<?php
// registrar_clique.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/banco.php';

$url_destino = $_GET['url'] ?? '';
$mac_cliente = $_GET['mac'] ?? 'desconhecido';

// Se não tiver URL, não faz nada
if (empty($url_destino) || $url_destino === '#') {
    die("Destino inválido.");
}

try {
    $db = new Banco();
    // Salva o clique no banco de dados (certifique-se de ter essa tabela criada)
    $db->query("INSERT INTO cliques_anuncio (mac_address, url_destino, data_clique) VALUES (?, ?, NOW())", [$mac_cliente, $url_destino]);
} catch (Exception $e) {
    // Ignora erros de banco para não travar a experiência do cliente
}

// Redireciona o cliente instantaneamente para o link do parceiro
header("Location: " . filter_var($url_destino, FILTER_SANITIZE_URL));
exit;
