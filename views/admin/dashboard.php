<?php include __DIR__ . '/header.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-gauge"></i> Dashboard Multi-NAS (Geral)</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-primary p-2"><i class="fa-solid fa-user-shield"></i> Admin Logado</span>
            <span class="badge bg-info p-2"><i class="fa-solid fa-server"></i> Roteadores</span>
        </div>
    </div>

    <!-- === PRIMEIRA LINHA: Faturamento + Clientes + Roteadores === -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                        <i class="fa-solid fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Faturamento Hoje</h6>
                        <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoHoje, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                        <i class="fa-solid fa-chart-line fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Faturamento Mês</h6>
                        <h3 class="fw-bold mb-0">R$ <?= number_format($faturamentoMes, 2, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                        <i class="fa-solid fa-users fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Clientes Conectados</h6>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($clientesAtivos) ?> <small class="text-muted fs-6">toda rede</small></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD: Status dos Roteadores -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <h6 class="text-muted mb-2"><i class="fa-solid fa-server text-info"></i> Status Roteadores</h6>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($rotoresStatus as $nome => $status): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="fw-bold"><?= VerificadorRoteadores::getNomeLegivel($nome) ?></small>
                            <?php if ($status['online']): ?>
                                <span class="badge bg-success"><i class="fa-solid fa-circle"></i> Online</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fa-solid fa-circle"></i> Offline</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- === SEGUNDA LINHA: Gráfico + Métricas de Anúncios === -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="card-title mb-4"><i class="fa-solid fa-chart-bar"></i> Desempenho de Vendas (Últimos 7 dias)</h5>
                <div style="height: 350px; position: relative;">
                    <canvas id="graficoVendas"></canvas>
                </div>
            </div>
        </div>

        <!-- CARD: Métricas de Anúncios -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h5 class="card-title mb-4"><i class="fa-solid fa-tv text-info"></i> Anúncios</h5>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Ativos</small>
                        <span class="badge bg-success"><?= $anunciosAtivos ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Programados</small>
                        <span class="badge bg-warning"><?= $anunciosProgramados ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Expirados</small>
                        <span class="badge bg-danger"><?= $anunciosExpirados ?></span>
                    </div>
                </div>

                <hr>

                <h6 class="text-muted mt-3 mb-2"><i class="fa-solid fa-chart-pie"></i> Receita por Pacote</h6>
                <?php if (!empty($receitaPorPacote)): ?>
                    <?php foreach ($receitaPorPacote as $pacote): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-secondary">
                                <?php 
                                $labels = ['1dia' => '1 Dia', '1semana' => '1 Semana', '15dias' => '15 Dias'];
                                echo $labels[$pacote['pacote_tipo']] ?? $pacote['pacote_tipo'];
                                ?>
                            </small>
                            <small class="fw-bold text-success">R$ <?= number_format($pacote['total'] / 100, 2, ',', '.') ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">Nenhum anúncio ainda</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/src/chart.js"></script>
<script>
    const labelsDias = <?= json_encode($graficoDados['dias'] ?? ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']) ?>;
    const valoresVendas = <?= json_encode($graficoDados['valores'] ?? [0, 0, 0, 0, 0, 0, 0]) ?>;

    const ctx = document.getElementById('graficoVendas').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsDias,
            datasets: [{
                label: 'Faturamento Diário (R$)',
                data: valoresVendas,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(2).replace('.', ',');
                        }
                    }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>
