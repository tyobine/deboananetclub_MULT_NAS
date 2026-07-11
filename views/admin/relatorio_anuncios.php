<?php
// views/admin/relatorio_anuncios.php

require_once __DIR__ . '/../../models/banco.php';
require_once __DIR__ . '/../../controllers/admin/anuncios.php';

// Proteção da sessão
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_logado'])) {
    header("Location: /admin/login");
    exit;
}

$db = new Banco();

$filtroStatus = $_GET['filtro'] ?? 'todos';

// Otimização e Mágica Financeira: Traz tudo numa consulta só com uso do COALESCE
$query = "
    SELECT 
        c.id,
        c.nome_empresa as nome,
        c.telefone,
        (SELECT COUNT(a.id) FROM crm_anuncios a WHERE a.anunciante_id = c.id) as anuncios,
        (SELECT COALESCE(SUM(a.valor_pacote), 0) FROM crm_anuncios a WHERE a.anunciante_id = c.id) as total_investido,
        (SELECT COUNT(v.id) FROM crm_views v INNER JOIN crm_anuncios a ON v.anuncio_id = a.id WHERE a.anunciante_id = c.id) as views,
        (SELECT COUNT(cl.id) FROM crm_cliques cl INNER JOIN crm_anuncios a ON cl.anuncio_id = a.id WHERE a.anunciante_id = c.id) as cliques
    FROM crm_anunciantes c
    ORDER BY c.nome_empresa ASC
";

$relatorio_bruto = $db->getAll($query);

$todas_midias = $db->getAll("
    SELECT a.*, 
           (SELECT COUNT(v.id) FROM crm_views v WHERE v.anuncio_id = a.id) as views_ad,
           (SELECT COUNT(cl.id) FROM crm_cliques cl WHERE cl.anuncio_id = a.id) as cliques_ad
    FROM crm_anuncios a
    ORDER BY a.id DESC
");

$midias_por_cliente = [];
foreach ($todas_midias as $midia) {
    $infoStatus = Anuncios::obterStatus($midia['data_inicio'], $midia['data_fim'], $midia['exibir']);
    $diasRestantes = Anuncios::obterDiasRestantes($midia['data_fim']);
    
    // Cálculo do Custo Por Interação (CPI) individual da mídia
    $views = (int)$midia['views_ad'];
    $cliques = (int)$midia['cliques_ad'];
    $interacoes_equivalentes = $views + ($cliques * 2);
    $valor_pago = $midia['valor_pacote'] / 100; // Convertendo centavos para reais
    
    $cpi = $interacoes_equivalentes > 0 ? ($valor_pago / $interacoes_equivalentes) : 0;
    $midia['cpi_formatado'] = 'R$ ' . number_format($cpi, 2, ',', '.');
    
    $incluir = false;
    if ($filtroStatus === 'todos') $incluir = true;
    elseif ($filtroStatus === 'ativos' && $infoStatus['status'] === 'ativo') $incluir = true;
    elseif ($filtroStatus === 'inativos' && $infoStatus['status'] === 'inativo') $incluir = true;
    elseif ($filtroStatus === 'programados' && $infoStatus['status'] === 'programado') $incluir = true;
    elseif ($filtroStatus === 'expirados' && $infoStatus['status'] === 'expirado') $incluir = true;
    elseif ($filtroStatus === 'expirando' && $infoStatus['status'] === 'ativo' && $diasRestantes <= 3) $incluir = true;

    if ($incluir) {
        $midias_por_cliente[$midia['anunciante_id']][] = [
            'dados' => $midia,
            'status_info' => $infoStatus,
            'dias' => $diasRestantes
        ];
    }
}

$relatorio = [];
$total_geral_views = 0;
$total_geral_cliques = 0;
$total_financeiro_geral = 0;

foreach ($relatorio_bruto as $linha) {
    if ($filtroStatus !== 'todos' && empty($midias_por_cliente[$linha['id']])) {
        continue;
    }

    $linha['telefone'] = !empty($linha['telefone']) ? $linha['telefone'] : 'Não informado';
    
    $investimento_centavos = $linha['total_investido'] ?? 0;
    $investimento_reais = $investimento_centavos / 100;
    $linha['total_investido_formatado'] = 'R$ ' . number_format($investimento_reais, 2, ',', '.');
    
    // Cálculo do CPI Médio Global do Cliente (Soma de tudo)
    $views_total = (int)$linha['views'];
    $cliques_total = (int)$linha['cliques'];
    $interacoes_eq_total = $views_total + ($cliques_total * 2);
    
    $cpi_medio = $interacoes_eq_total > 0 ? ($investimento_reais / $interacoes_eq_total) : 0;
    $linha['cpi_medio_formatado'] = 'R$ ' . number_format($cpi_medio, 2, ',', '.');

    $total_geral_views += $linha['views'];
    $total_geral_cliques += $linha['cliques'];
    $total_financeiro_geral += $investimento_centavos;

    $relatorio[] = $linha;
}

// =========================================================================
// MODO EXCEL
// =========================================================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    $cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
    
    $dados_exportar = [];
    $nome_arquivo = "Relatorio_Anunciantes_" . date('Y-m-d') . ".xls";
    $titulo_tabela = "RELATÓRIO DE PUBLICIDADE - " . mb_strtoupper($filtroStatus);
    
    if ($cliente_id > 0) {
        foreach ($relatorio as $linha) {
            if ((int)$linha['id'] === $cliente_id) {
                $dados_exportar[] = $linha;
                $nome_seguro = preg_replace('/[^a-zA-Z0-9]/', '_', $linha['nome']);
                $nome_arquivo = "Relatorio_" . $nome_seguro . "_" . date('Y-m-d') . ".xls";
                $titulo_tabela = "RELATÓRIO DE DESEMPENHO - " . mb_strtoupper($linha['nome'], 'UTF-8');
                break;
            }
        }
    } else {
        $dados_exportar = $relatorio;
    }

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . $nome_arquivo);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
    echo "<table border='1' style='font-family: Arial, sans-serif; border-collapse: collapse; width: 100%;'>";
    echo "<tr><th colspan='7' style='background-color:#0d6efd; color:#ffffff; font-size: 18px; padding: 10px;'>" . $titulo_tabela . "</th></tr>";
    echo "<tr style='background-color:#343a40; color:#ffffff;'>
            <th style='padding: 8px;'>Anunciante</th>
            <th style='padding: 8px;'>Telefone</th>
            <th style='padding: 8px;'>Mídias Vinculadas</th>
            <th style='padding: 8px;'>Visualizações</th>
            <th style='padding: 8px;'>Cliques</th>
            <th style='padding: 8px;'>Custo/Interação (Média)</th>
            <th style='padding: 8px;'>Total Investido</th>
          </tr>";
          
    $soma_views = 0;
    $soma_cliques = 0;
    $soma_investido = 0;
    
    foreach ($dados_exportar as $linha) {
        echo "<tr>
                <td style='padding: 8px; font-weight: bold;'>" . htmlspecialchars($linha['nome']) . "</td>
                <td style='padding: 8px;'>" . htmlspecialchars($linha['telefone']) . "</td>
                <td style='padding: 8px; text-align:center; background-color:#e9ecef;'>" . $linha['anuncios'] . "</td>
                <td style='padding: 8px; text-align:center; color:#0dcaf0; font-weight: bold;'>" . $linha['views'] . "</td>
                <td style='padding: 8px; text-align:center; color:#d63384; font-weight: bold;'>" . $linha['cliques'] . "</td>
                <td style='padding: 8px; text-align:center; color:#fd7e14; font-weight: bold;'>" . $linha['cpi_medio_formatado'] . "</td>
                <td style='padding: 8px; text-align:center; color:#198754; font-weight: bold;'>" . $linha['total_investido_formatado'] . "</td>
              </tr>";
        $soma_views += $linha['views'];
        $soma_cliques += $linha['cliques'];
        $soma_investido += ($linha['total_investido'] ?? 0);
    }
    
    echo "<tr style='background-color:#ffc107; font-weight:bold; font-size: 16px;'>
            <td colspan='3' style='text-align:right; padding: 10px;'>RESULTADO TOTAL:</td>
            <td style='text-align:center; padding: 10px;'>" . $soma_views . "</td>
            <td style='text-align:center; padding: 10px;'>" . $soma_cliques . "</td>
            <td style='text-align:center; padding: 10px;'>-</td>
            <td style='text-align:center; padding: 10px;'>R$ " . number_format($soma_investido / 100, 2, ',', '.') . "</td>
          </tr>";
    echo "</table>";
    exit; 
}

require_once __DIR__ . '/header.php';
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-chart-pie"></i> Relatórios & Métricas</h3>
        <a href="/admin/relatorio-anuncios?exportar=excel&filtro=<?= $filtroStatus ?>" class="btn btn-success fw-bold shadow-sm">
            <i class="fa-solid fa-file-excel"></i> Baixar Relatório (Atual)
        </a>
    </div>

    <!-- Filtros de Relatório -->
    <div class="btn-group w-100 mb-4 shadow-sm" role="group">
        <a href="?filtro=todos" class="btn btn-outline-dark <?= $filtroStatus === 'todos' ? 'active' : '' ?>">Todos</a>
        <a href="?filtro=ativos" class="btn btn-outline-success <?= $filtroStatus === 'ativos' ? 'active' : '' ?>">Ativos</a>
        <a href="?filtro=inativos" class="btn btn-outline-secondary <?= $filtroStatus === 'inativos' ? 'active' : '' ?>">Inativos</a>
        <a href="?filtro=programados" class="btn btn-outline-warning <?= $filtroStatus === 'programados' ? 'active' : '' ?>">Programados</a>
        <a href="?filtro=expirados" class="btn btn-outline-danger <?= $filtroStatus === 'expirados' ? 'active' : '' ?>">Expirados</a>
        <a href="?filtro=expirando" class="btn btn-outline-info <?= $filtroStatus === 'expirando' ? 'active' : '' ?>">Vencendo (< 3 dias)</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <h2 class="fw-bold mb-0"><?= count($relatorio) ?></h2>
                    <p class="mb-0 small">Anunciantes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <h2 class="fw-bold mb-0"><?= $total_geral_views ?></h2>
                    <p class="mb-0 small"><i class="fa-solid fa-eye"></i> Visualizações</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white shadow-sm border-0 h-100" style="background-color: #d63384;">
                <div class="card-body text-center py-3">
                    <h2 class="fw-bold mb-0"><?= $total_geral_cliques ?></h2>
                    <p class="mb-0 small"><i class="fa-solid fa-hand-pointer"></i> Cliques</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <h2 class="fw-bold mb-0">R$ <?= number_format($total_financeiro_geral / 100, 2, ',', '.') ?></h2>
                    <p class="mb-0 small"><i class="fa-solid fa-sack-dollar"></i> Faturamento</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold">
            <i class="fa-solid fa-table-list"></i> Detalhamento por Cliente (Clique na linha para ver as mídias)
        </div>
        <div class="card-body p-0">
            <!-- Table responsive protege a quebra no mobile -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="border-collapse: collapse;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 200px;">Anunciante</th>
                            <th>Telefone</th>
                            <th class="text-center">Mídias</th>
                            <th class="text-center">Visualizações</th>
                            <th class="text-center">Cliques</th>
                            <th class="text-center" style="color: #fd7e14;">Custo/Interação</th>
                            <th class="text-center text-success">Investido</th>
                            <th class="text-center">Excel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatorio)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Nenhum dado encontrado para o filtro selecionado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($relatorio as $linha): ?>
                                <!-- Linha Clicável (Accordion) -->
                                <tr data-bs-toggle="collapse" data-bs-target="#collapseClient<?= $linha['id'] ?>" style="cursor: pointer;">
                                    <td class="fw-bold text-dark">
                                        <div class="text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($linha['nome']) ?>">
                                            <i class="fa-regular fa-building text-primary me-2"></i> <?= htmlspecialchars($linha['nome']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 120px;">
                                            <?= htmlspecialchars($linha['telefone']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= $linha['anuncios'] ?></span></td>
                                    <td class="text-center fw-bold text-info"><?= $linha['views'] ?></td>
                                    <td class="text-center fw-bold" style="color: #d63384;"><?= $linha['cliques'] ?></td>
                                    <td class="text-center fw-bold" style="color: #fd7e14;" title="Média de todas as mídias deste cliente"><?= $linha['cpi_medio_formatado'] ?></td>
                                    <td class="text-center fw-bold text-success"><?= $linha['total_investido_formatado'] ?></td>
                                    <td class="text-center">
                                        <a href="/admin/relatorio-anuncios?exportar=excel&cliente_id=<?= $linha['id'] ?>" class="btn btn-sm btn-outline-success" title="Baixar Excel deste cliente">
                                            <i class="fa-solid fa-file-excel"></i>
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Sanfona (Conteúdo Expandido) com a lista de Mídias -->
                                <tr>
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="collapse" id="collapseClient<?= $linha['id'] ?>">
                                            <div class="p-3" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                                <h6 class="fw-bold text-muted mb-3">Desempenho Individual por Mídia (Filtradas):</h6>
                                                <?php if (empty($midias_por_cliente[$linha['id']])): ?>
                                                    <p class="small text-muted mb-0">Nenhuma mídia correspondente ao filtro.</p>
                                                <?php else: ?>
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php foreach ($midias_por_cliente[$linha['id']] as $item): ?>
                                                            <!-- Aplicando o alinhamento de colunas forçadas para alinhar perfeitamente -->
                                                            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start align-items-xl-center bg-white p-3 p-xl-2 rounded border gap-2">
                                                                
                                                                <!-- Lado Esquerdo: Info da mídia -->
                                                                <div class="flex-grow-1 text-truncate pe-2">
                                                                    <!-- Largura fixa na tag de status para alinhar o ID logo após -->
                                                                    <span class="badge bg-<?= $item['status_info']['badge'] ?> me-2 text-center" style="width: 90px; display: inline-block;">
                                                                        <?= $item['status_info']['texto'] ?>
                                                                    </span>
                                                                    <span class="fw-bold text-dark">
                                                                        ID #<?= $item['dados']['id'] ?> - <?= htmlspecialchars(ucfirst($item['dados']['tipo'])) ?>
                                                                    </span>
                                                                    <span class="text-muted ms-2">
                                                                        | Período: <?= date('d/m/Y', strtotime($item['dados']['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($item['dados']['data_fim'])) ?>
                                                                    </span>
                                                                </div>

                                                                <!-- Lado Direito: Badges tabuladas (Colunas perfeitamente alinhadas) -->
                                                                <div class="d-flex flex-nowrap gap-2 justify-content-start justify-content-xl-end overflow-auto pb-1 pb-xl-0">
                                                                    <span class="badge bg-success text-start" style="width: 105px; display: inline-block;" title="Valor investido nesta mídia">
                                                                        <i class="fa-solid fa-dollar-sign"></i> R$ <?= number_format($item['dados']['valor_pacote'] / 100, 2, ',', '.') ?>
                                                                    </span>
                                                                    
                                                                    <span class="badge text-start" style="background-color: #fd7e14; width: 120px; display: inline-block;" title="Custo por Interação (CPI) desta mídia">
                                                                        <i class="fa-solid fa-chart-line"></i> CPI: <?= $item['dados']['cpi_formatado'] ?>
                                                                    </span>
                                                                    
                                                                    <span class="badge bg-danger text-start" style="width: 135px; display: inline-block;">
                                                                        <i class="fa-solid fa-clock"></i> <?= $item['dias'] ?> dias restantes
                                                                    </span>
                                                                    
                                                                    <span class="badge bg-info text-dark text-center" style="width: 60px; display: inline-block;">
                                                                        <i class="fa-solid fa-eye"></i> <?= $item['dados']['views_ad'] ?>
                                                                    </span>
                                                                    
                                                                    <span class="badge text-center" style="background-color: #d63384; width: 60px; display: inline-block;">
                                                                        <i class="fa-solid fa-hand-pointer"></i> <?= $item['dados']['cliques_ad'] ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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