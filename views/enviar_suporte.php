<?php
// ==========================================================================
// ARQUIVO: views/enviar_suporte.php
// ==========================================================================

// Desligamos os erros em HTML para garantir que apenas JSON seja devolvido
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// O COMANDO MÁGICO DE NAVEGAÇÃO: 
// __DIR__ (estou em views) -> '/../' (volto para a raiz) -> 'config/config.php' (entro no config)
// ATENÇÃO: Se o ficheiro dentro da pasta config tiver outro nome, mude abaixo!
require_once __DIR__ . '/../config/config.php';

// Puxa as credenciais
$token = TELEGRAM_TOKEN;
$chat_id = TELEGRAM_CHAT_ID;

// ... (O resto do seu código que recebe o POST e faz o curl continua igual)
$mensagem = $_POST['mensagem'] ?? '';
$mac = $_POST['mac'] ?? 'Desconhecido';

// Verifica se há um anexo (comprovativo) enviado e se não deu erro no upload
$tem_arquivo = isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK;

// Validação de segurança: se não houver texto nem arquivo, aborta
if (empty(trim($mensagem)) && !$tem_arquivo) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados vazios']);
    exit;
}

// Monta a mensagem formatada para o Telegram
$texto_telegram = "🚨 *SUPORTE SOLICITADO* 🚨\n\n";
$texto_telegram .= "📱 *MAC:* `" . $mac . "`\n";
$texto_telegram .= "🌐 *IP:* `" . $ip . "`\n";
if (!empty(trim($mensagem))) {
    $texto_telegram .= "💬 *Mensagem:*\n_" . htmlspecialchars($mensagem) . "_";
}

$ch = curl_init();

// Se o cliente anexou uma imagem ou PDF
if ($tem_arquivo) {
    $caminho_tmp = $_FILES['comprovante']['tmp_name'];
    $tipo_mime = $_FILES['comprovante']['type'];
    $nome_arquivo = $_FILES['comprovante']['name'];

    // Separa se é foto ou documento (PDF) para usar o endpoint correto do Telegram
    $is_image = strpos($tipo_mime, 'image/') === 0;
    $endpoint = $is_image ? 'sendPhoto' : 'sendDocument';
    $campo_arquivo = $is_image ? 'photo' : 'document';

    $url = "https://api.telegram.org/bot{$token}/{$endpoint}";

    $post_fields = [
        'chat_id' => $chat_id,
        'caption' => $texto_telegram, // O texto vira a legenda da foto
        'parse_mode' => 'Markdown',
        $campo_arquivo => new CURLFile($caminho_tmp, $tipo_mime, $nome_arquivo)
    ];
} else {
    // Se for apenas mensagem de texto simples
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $texto_telegram,
        'parse_mode' => 'Markdown'
    ];
}

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita bloqueios de SSL em testes locais/Windows

$resultado = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Retorna a resposta exata para o JavaScript manipular a tela do cliente
if ($resultado && $http_status == 200) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro na API. HTTP Status: ' . $http_status]);
}
