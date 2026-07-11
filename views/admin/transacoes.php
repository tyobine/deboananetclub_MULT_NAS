<?php include __DIR__ . '/header.php'; ?>

<div class="container mt-5 mb-5">

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light rounded">
            <form method="GET" action="/admin/transacoes" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Buscar por MAC, WhatsApp ou ID do Comprovante</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cole o MAC, número ou ID aqui..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Filtrar por Cidade</label>
                    <select name="router" class="form-select">
                        <option value="">Todas as Cidades</option>
                        <?php foreach ($roteadores as $rot): ?>
                            <option value="<?= htmlspecialchars($rot['nome_identificador']) ?>" <?= ($_GET['router'] ?? '') === $rot['nome_identificador'] ? 'selected' : '' ?>><?= mb_strtoupper($rot['nome_identificador']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos os Status</option>
                        <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="pendente" <?= ($_GET['status'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="expirado" <?= ($_GET['status'] ?? '') === 'expirado' ? 'selected' : '' ?>>Expirado</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list-ul"></i> Histórico de Transações</h5>
            <a href="/exportar_contatos.php" class="btn btn-light btn-sm fw-bold text-primary shadow-sm">
                <i class="fa-solid fa-file-csv text-success"></i> Exportar Leads
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Cliente / Dispositivo</th>
                            <th>ID Comprovante (TXID)</th>
                            <th>Cidade (Router)</th>
                            <th>WhatsApp (Lead)</th>
                            <th>Plano</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td class="ps-3"><?= $txn['id']; ?></td>
                                <td>
                                    <small class="font-monospace fw-bold"><?= htmlspecialchars($txn['mac_address']); ?></small><br>
                                    <small class="text-muted" style="font-size: 0.75rem;">IP: <?= htmlspecialchars($txn['ip_address']); ?></small>
                                </td>
                                <td>
                                    <small class="text-muted font-monospace bg-light p-1 rounded" style="font-size: 0.8rem;" title="<?= htmlspecialchars($txn['txid'] ?? ''); ?>">
                                        <?= !empty($txn['txid']) ? substr($txn['txid'], 0, 15) . '...' : '-'; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $cidade = !empty($txn['router_id']) ? strtoupper($txn['router_id']) : 'SOBRAL';
                                    $badgeColor = ($cidade === 'SOBRAL') ? 'bg-primary' : (($cidade === 'FORTALEZA') ? 'bg-success' : 'bg-dark');
                                    ?>
                                    <span class="badge <?= $badgeColor ?>"><?= $cidade; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($txn['whatsapp'])): ?>
                                        <a href="https://wa.me/55<?= $txn['whatsapp'] ?>" target="_blank" class="badge bg-success text-decoration-none p-2 shadow-sm">
                                            <i class="fa-brands fa-whatsapp"></i> <?= $txn['whatsapp']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($txn['plan_name'] ?? 'Plano Deletado'); ?></td>
                                <td class="fw-bold text-dark">R$ <?= isset($txn['amount_cents']) ? number_format($txn['amount_cents'] / 100, 2, ',', '.') : '0,00'; ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pendente' => 'warning text-dark',
                                        'processando' => 'info text-white',
                                        'ativo' => 'success text-white',
                                        'expirado' => 'secondary text-white'
                                    ];
                                    $color = $statusColors[$txn['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color; ?> px-2 py-1 fw-bold"><?= ucfirst($txn['status']); ?></span>
                                </td>
                                <td>
                                    <small class="text-muted fw-bold">
                                        <?= !empty($txn['expira_em']) ? date('d/m/Y H:i', strtotime($txn['expira_em'])) : '-'; ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="alert alert-info text-center m-4">
                    <i class="fa-solid fa-circle-info"></i> Nenhuma transação encontrada com os filtros aplicados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>