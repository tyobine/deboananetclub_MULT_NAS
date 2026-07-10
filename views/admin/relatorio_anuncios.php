<?php
// views/admin/relatorio_anuncios.php

require_once __DIR__ . '/../../models/banco.php';

// Proteção da sessão
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_logado'])) {
    header("Location: /admin/login");
    exit;
}

$db = new Banco();

// Busca todos os anunciantes e cruza com os dados de views e cliques das tabelas novas
$anunciantes = $db->getAll("SELECT * FROM crm_anunciantes ORDER BY nome_empresa ASC");

$relatorio = [];
$total_geral_views = 0;
$total_geral_cliques = 0;

foreach ($anunciantes as $cliente) {
    $id = $cliente['id'];
    
    // Conta as métricas SOMENTE deste anunciante
    $views = $db->getRow("SELECT COUNT(v.id) as total FROM crm_views v JOIN crm_anuncios a ON v.anuncio_id = a.id WHERE a.anunciante_id = ?", [$id])['total'] ?? 0;
    $cliques = $db->getRow("SELECT COUNT(cl.id) as total FROM crm_cliques cl JOIN crm_anuncios a ON cl.anuncio_id = a.id WHERE a.anunciante_id = ?", [$id])['total'] ?? 0;
    $qtd_anuncios = $db->getRow("SELECT COUNT(id) as total FROM crm_anuncios WHERE anunciante_id = ?", [$id])['total'] ?? 0;
    
    $total_geral_views += $views;
    $total_geral_cliques += $cliques;

    $relatorio[] = [
        'id' => $id, // 🚀 ID adicionado para podermos filtrar individualmente
        'nome' => $cliente['nome_empresa'],
        'telefone' => !empty($cliente['telefone']) ? $cliente['telefone'] : 'Não informado',
        'anuncios' => $qtd_anuncios,
        'views' => $views,
        'cliques' => $cliques
    ];
}

// =========================================================================
// MODO EXCEL: Gera o Excel GERAL ou INDIVIDUAL e para a execução
// =========================================================================
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    
    $cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
    
    $dados_exportar = [];
    $nome_arquivo = "Relatorio_Geral_Anunciantes_" . date('Y-m-d') . ".xls";
    $titulo_tabela = "RELATÓRIO DE PUBLICIDADE - GERAL (" . date('d/m/Y') . ")";
    
    if ($cliente_id > 0) {
        // 🚀 EXCEL INDIVIDUAL: Filtra apenas os dados do cliente clicado
        foreach ($relatorio as $linha) {
            if ($linha['id'] == $cliente_id) {
                $dados_exportar[] = $linha;
                $nome_seguro = preg_replace('/[^a-zA-Z0-9]/', '_', $linha['nome']);
                $nome_arquivo = "Relatorio_" . $nome_seguro . "_" . date('Y-m-d') . ".xls";
                $titulo_tabela = "RELATÓRIO DE DESEMPENHO - " . mb_strtoupper($linha['nome'], 'UTF-8');
                break;
            }
        }
    } else {
        // EXCEL GERAL
        $dados_exportar = $relatorio;
    }

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $nome_arquivo);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
    echo "<table border='1'>";
    echo "<tr><th colspan='5' style='background-color:#0d6efd; color:#fff; font-size: 16px; height: 40px;'>" . $titulo_tabela . "</th></tr>";
    echo "<tr style='background-color:#f1f1f1;'>
            <th>Anunciante</th>
            <th>Telefone</th>
            <th>Qtd de Anúncios</th>
            <th>Visualizações</th>
            <th>Cliques</th>
          </tr>";
          
    $soma_views = 0;
    $soma_cliques = 0;
    
    foreach ($dados_exportar as $linha) {
        echo "<tr>
                <td>" . htmlspecialchars($linha['nome']) . "</td>
                <td>" . htmlspecialchars($linha['telefone']) . "</td>
                <td style='text-align:center;'>" . $linha['anuncios'] . "</td>
                <td style='text-align:center;'>" . $linha['views'] . "</td>
                <td style='text-align:center;'>" . $linha['cliques'] . "</td>
              </tr>";
        $soma_views += $linha['views'];
        $soma_cliques += $linha['cliques'];
    }
    
    // Linha de totais
    echo "<tr style='background-color:#fff3cd; font-weight:bold;'>
            <th colspan='3' style='text-align:right;'>TOTAL:</th>
            <th style='text-align:center;'>" . $soma_views . "</th>
            <th style='text-align:center;'>" . $soma_cliques . "</th>
          </tr>";
    echo "</table>";
    exit; 
}

// =========================================================================
// MODO TELA: Imprime o HTML normal do painel de administração
// =========================================================================
require_once __DIR__ . '/header.php';
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-chart-pie"></i> Relatório de Desempenho</h3>
        <a href="/admin/relatorio-anuncios?exportar=excel" class="btn btn-success fw-bold shadow-sm">
            <i class="fa-solid fa-file-excel"></i> Baixar Relatório Geral
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm border-0 h-100">
                <div class="card-body text-center py-4">
                    <h1 class="display-5 fw-bold mb-0"><?= count($anunciantes) ?></h1>
                    <p class="mb-0">Anunciantes Cadastrados</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm border-0 h-100">
                <div class="card-body text-center py-4">
                    <h1 class="display-5 fw-bold mb-0"><?= $total_geral_views ?></h1>
                    <p class="mb-0"><i class="fa-solid fa-eye"></i> Visualizações Totais</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-pink text-white shadow-sm border-0 h-100" style="background-color: #d63384;">
                <div class="card-body text-center py-4">
                    <h1 class="display-5 fw-bold mb-0"><?= $total_geral_cliques ?></h1>
                    <p class="mb-0"><i class="fa-solid fa-hand-pointer"></i> Cliques Totais</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold">
            <i class="fa-solid fa-table-list"></i> Detalhamento por Cliente
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Anunciante</th>
                            <th>Telefone</th>
                            <th class="text-center">Mídias Ativas/Inativas</th>
                            <th class="text-center">Visualizações</th>
                            <th class="text-center">Cliques</th>
                            <th class="text-center">Exportar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorio)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhum dado para exibir. Cadastre anunciantes no CRM.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($relatorio as $linha): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><i class="fa-regular fa-building text-primary me-2"></i> <?= htmlspecialchars($linha['nome']) ?></td>
                                    <td><?= htmlspecialchars($linha['telefone']) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= $linha['anuncios'] ?></span></td>
                                    <td class="text-center fw-bold text-info"><?= $linha['views'] ?></td>
                                    <td class="text-center fw-bold" style="color: #d63384;"><?= $linha['cliques'] ?></td>
                                    
                                    <td class="text-center">
                                        <a href="/admin/relatorio-anuncios?exportar=excel&cliente_id=<?= $linha['id'] ?>" class="btn btn-sm btn-outline-success" title="Baixar Excel deste cliente para enviar no WhatsApp">
                                            <i class="fa-solid fa-file-excel"></i> Cliente
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/src/bootstrap.bundle.min.js"></script>
</body>
</html>