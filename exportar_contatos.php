<?php
// exportar_contatos.php

// Ajuste o caminho dependendo de onde guardar este ficheiro
require_once __DIR__ . '/models/banco.php';

$db = new Banco();

// Busca apenas os clientes que preencheram o WhatsApp (sem a coluna de status)
$leads = $db->query("
    SELECT DISTINCT whatsapp, mac_address 
    FROM acessos_pix 
    WHERE whatsapp IS NOT NULL AND whatsapp != ''
    ORDER BY id DESC
");

// Força o navegador a descarregar o ficheiro
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=leads_provedor_' . date('Y-m-d_H-i') . '.csv');

// Abre a saída de dados diretamente para o download
$saida = fopen('php://output', 'w');

// Adiciona o "BOM" do UTF-8 para o Excel reconhecer a codificação corretamente
fputs($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cria o cabeçalho das colunas no Excel (apenas as duas)
fputcsv($saida, ['WhatsApp', 'Endereço MAC'], ';');

// Preenche as linhas com os dados reais
if ($leads) {
    foreach ($leads as $linha) {
        fputcsv($saida, [
            $linha['whatsapp'],
            $linha['mac_address']
        ], ';');
    }
}

fclose($saida);
exit;
