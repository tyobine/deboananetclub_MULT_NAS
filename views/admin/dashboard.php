<?php include __DIR__ . '/header.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-gauge"></i> Dashboard Multi-NAS (Geral)</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-primary p-2"><i class="fa-solid fa-user-shield"></i> Admin Logado</span>
            <!-- Tag de Roteadores removida conforme solicitado -->
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
                        <h3 class="fw-bold mb-0" id="clientes-ativos-valor"><span class="spinner-border spinner-border-sm text-warning" role="status"></span></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD: Status dos Roteadores -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <h6 class="text-muted mb-2"><i class="fa-solid fa-server text-info"></i> Status Roteadores</h6>
                <div class="d-flex flex-column gap-2" id="roteadores-status-lista">
                    <div class="text-center text-muted small py-2">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Consultando torres...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de Alerta de Erro de Roteadores via API -->
    <div id="alertas-dashboard" style="display: none;">
        <div class="alert alert-danger shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i> <span id="alertas-texto"></span>
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
                <h5 class="card-title mb-4"><i class="fa-solid fa-tv text-info"></i> Resumo de Publicidade</h5>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Anúncios Ativos</small>
                        <span class="badge bg-success"><?= $anunciosAtivos ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Programados para o Futuro</small>
                        <span class="badge bg-warning"><?= $anunciosProgramados ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold">Anúncios Expirados</small>
                        <span class="badge bg-danger"><?= $anunciosExpirados ?></span>
                    </div>
                </div>

                <hr>

                <div class="text-center mt-3">
                    <a href="/admin/relatorio-anuncios" class="btn btn-outline-primary btn-sm w-100 fw-bold">Ver Relatório Detalhado</a>
                </div>
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

    // Função para carregar os status dos roteadores e clientes ativos assincronamente
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/admin/api/dashboard-status')
            .then(response => response.json())
            .then(data => {
                // Atualiza Clientes Ativos
                document.getElementById('clientes-ativos-valor').innerHTML = data.clientesAtivos + ' <small class="text-muted fs-6">toda rede</small>';
                
                // Atualiza Status dos Roteadores
                let htmlStatus = '';
                data.roteadoresStatus.forEach(function(rot) {
                    let badge = rot.online 
                        ? '<span class="badge bg-success"><i class="fa-solid fa-circle"></i> Online</span>' 
                        : '<span class="badge bg-danger"><i class="fa-solid fa-circle"></i> Offline</span>';
                    
                    htmlStatus += `
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="fw-bold">${rot.nome}</small>
                            ${badge}
                        </div>
                    `;
                });
                
                if(htmlStatus === '') htmlStatus = '<div class="text-muted small">Nenhum roteador cadastrado.</div>';
                document.getElementById('roteadores-status-lista').innerHTML = htmlStatus;

                // Mostra alertas se houver falhas de comunicação
                if(data.erros && data.erros.length > 0) {
                    document.getElementById('alertas-texto').innerText = "Falha ao consultar usuários ativos nos seguintes roteadores: " + data.erros.join(', ');
                    document.getElementById('alertas-dashboard').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('clientes-ativos-valor').innerHTML = '<span class="text-danger fs-5"><i class="fa-solid fa-circle-exclamation"></i> Erro</span>';
                document.getElementById('roteadores-status-lista').innerHTML = '<div class="text-danger small">Falha ao carregar status.</div>';
            });
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>