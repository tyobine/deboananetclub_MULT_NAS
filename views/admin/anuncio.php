<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/banco.php';
require_once __DIR__ . '/../../controllers/admin/anuncios.php';

$db_metricas = new Banco();
?>

<style>
    .media-preview { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; background: #111; }
    .media-card { transition: transform 0.2s; border: 1px solid #ddd; }
    .media-card:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .client-section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #0d6efd; }
    .badge-local { position: absolute; top: 10px; left: 10px; z-index: 10; font-size: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
</style>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-rectangle-ad"></i> CRM de Publicidade</h3>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <?php if ($_GET['sucesso'] == 'cliente_salvo'): ?><strong>Sucesso!</strong> Anunciante cadastrado.
            <?php elseif ($_GET['sucesso'] == 'midia_salva'): ?><strong>Sucesso!</strong> Mídia vinculada.
            <?php elseif ($_GET['sucesso'] == 'status_atualizado'): ?><strong>Sucesso!</strong> Status atualizado.
            <?php elseif ($_GET['sucesso'] == 'midia_deletada'): ?><strong>Sucesso!</strong> Mídia excluída.
            <?php elseif ($_GET['sucesso'] == 'dados_atualizados'): ?><strong>Sucesso!</strong> Dados do anúncio atualizados!
            <?php elseif ($_GET['sucesso'] == 'local_salvo'): ?><strong>Sucesso!</strong> Novo roteador cadastrado.
            <?php elseif ($_GET['sucesso'] == 'data_atualizada'): ?><strong>Sucesso!</strong> Período de exibição atualizado!
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold"><i class="fa-solid fa-user-plus"></i> Novo Anunciante</div>
                <div class="card-body">
                    <form action="/admin/anuncio/salvar-cliente" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nome da Empresa</label>
                            <input type="text" name="nome_empresa" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Telefone</label>
                            <input type="text" name="telefone" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-bold">Cadastrar Cliente</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold"><i class="fa-solid fa-cloud-arrow-up"></i> Envio Rápido de Mídia</div>
                <div class="card-body">
                    <form action="/admin/anuncio/upload-midia" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Selecione o Anunciante</label>
                            <select name="anunciante_id" class="form-select" required>
                                <option value="">-- Escolha --</option>
                                <?php foreach ($anunciantes as $cliente): ?>
                                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome_empresa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Roteador / Localização</label>
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" onclick="abrirModalLocal()"><i class="fa-solid fa-plus"></i> Novo</button>
                            </div>
                            <select name="localizacao" class="form-select" required>
                                <?php foreach ($locais as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc['nome']) ?>"><?= mb_strtoupper($loc['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Link de Destino (Opcional)</label>
                            <input type="url" name="link_destino" class="form-control">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Arquivo (Imagem ou Vídeo)</label>
                            <input type="file" name="arquivo_upload" class="form-control" accept="image/*,video/mp4" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" <?= empty($anunciantes) ? 'disabled' : '' ?>>Fazer Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-secondary"><i class="fa-solid fa-users-viewfinder"></i> Gestão de Campanhas</h5>
                
                <?php if (!empty($anunciantes)): ?>
                <div style="width: 250px;">
                    <select id="filtroAnunciante" class="form-select shadow-sm" onchange="filtrarClientes()">
                        <option value="todos">Exibir Todos os Clientes</option>
                        <?php foreach ($anunciantes as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome_empresa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($anunciantes)): ?>
                <div class="text-center w-100 py-5 bg-white rounded shadow-sm text-muted border">
                    <h5>Nenhum anunciante cadastrado.</h5>
                </div>
            <?php else: ?>
                <?php foreach ($anunciantes as $cliente): ?>
                    <div class="client-section" data-cliente-id="<?= $cliente['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fa-regular fa-building text-primary"></i> <?= htmlspecialchars($cliente['nome_empresa']) ?></h5>
                        </div>
                        <?php $anuncios_deste_cliente = $midias_por_anunciante[$cliente['id']] ?? []; ?>
                        <?php if (empty($anuncios_deste_cliente)): ?>
                            <p class="text-muted small mb-0">Nenhuma mídia enviada.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($anuncios_deste_cliente as $ad): ?>
                                    <?php
                                    $is_video = $ad['tipo'] === 'video';
                                    $is_ativo = $ad['exibir'] === 'sim';
                                    $caminho = htmlspecialchars($ad['caminho_arquivo']);
                                    $local = htmlspecialchars($ad['localizacao'] ?? 'todos');
                                    // Prepara o valor no formato que o input type number entende (ex: 50.00)
                                    $valor_formatado = number_format($ad['valor_pacote'] / 100, 2, '.', '');
                                    ?>
                                    <div class="col-6 col-md-6 col-lg-4">
                                        <div class="card media-card h-100 p-2 position-relative bg-light">
                                            
                                            <span class="badge bg-dark text-white badge-local"><i class="fa-solid fa-location-dot"></i> <?= $local === 'todos' ? 'Global' : mb_strtoupper($local) ?></span>

                                            <?php if ($is_video): ?>
                                                <video class="media-preview" autoplay loop muted playsinline><source src="<?= $caminho ?>" type="video/mp4"></video>
                                            <?php else: ?>
                                                <img src="<?= $caminho ?>" class="media-preview">
                                            <?php endif; ?>

                                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                                <form action="/admin/anuncio/toggle-midia" method="POST" class="m-0" title="Ativar/Pausar Mídia">
                                                    <input type="hidden" name="anuncio_id" value="<?= $ad['id'] ?>">
                                                    <input type="hidden" name="exibir" value="<?= $is_ativo ? 'nao' : 'sim' ?>">
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" role="switch" <?= $is_ativo ? 'checked' : '' ?> onchange="this.form.submit()">
                                                    </div>
                                                </form>

                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" title="Editar Anúncio (Dados e Valor)" onclick="abrirModalEditar(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['link_destino']) ?>', '<?= htmlspecialchars($ad['localizacao']) ?>', '<?= $valor_formatado ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                                                    
                                                    <button type="button" class="btn btn-outline-info btn-sm" title="Programar Datas" onclick="abrirModalEditarDatas(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['data_inicio']) ?>', '<?= htmlspecialchars($ad['data_fim']) ?>')"><i class="fa-solid fa-calendar-check"></i></button>
                                                    
                                                    <form action="/admin/anuncio/delete-midia" method="POST" class="m-0" onsubmit="return confirm('Tem certeza que deseja excluir esta mídia definitivamente?');">
                                                        <input type="hidden" name="anuncio_id" value="<?= $ad['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Deletar"><i class="fa-solid fa-trash-can"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL: Novo Local -->
<div class="modal fade" id="modalNovoLocal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/admin/anuncio/salvar-local" method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-location-dot text-primary"></i> Cadastrar Novo Roteador</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold">Palavra Exata no MikroTik (ex: fortaleza)</label>
            <input type="text" name="nome_local" class="form-control form-control-lg" placeholder="Digite em letras minúsculas" required>
          </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary fw-bold px-4">Salvar Roteador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: Editar Anúncio (Link, Local e VALOR PAGO) -->
<div class="modal fade" id="modalEditarAnuncio" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/admin/anuncio/editar-dados-anuncio" method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary"></i> Editar Dados do Anúncio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="anuncio_id" id="edit_anuncio_id">
          
          <div class="mb-3">
            <label class="form-label small fw-bold">Localização / Roteador</label>
            <select name="localizacao" id="edit_localizacao" class="form-select">
                <?php foreach ($locais as $loc): ?>
                    <option value="<?= htmlspecialchars($loc['nome']) ?>"><?= mb_strtoupper($loc['nome']) ?></option>
                <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Link de Destino</label>
            <input type="url" name="link_destino" id="edit_link_destino" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Valor Recebido (R$)</label>
            <input type="number" name="valor_pacote" id="edit_valor_pacote" class="form-control" placeholder="0.00" step="0.01">
            <div class="form-text text-muted">Usado para calcular Ticket Médio e faturamento na tela de Relatórios.</div>
          </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary fw-bold px-4">Salvar Alterações</button></div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: Editar Datas (Início e Fim) -->
<div class="modal fade" id="modalEditarDatas" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/admin/anuncio/editar-datas" method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-check text-info"></i> Programar Exibição</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="anuncio_id" id="editar_data_anuncio_id">
          
          <div class="mb-3">
            <label class="form-label small fw-bold">Início da Exibição</label>
            <input type="datetime-local" name="data_inicio" id="editar_data_inicio" class="form-control" required>
            <div class="form-text">Para programar para o futuro, coloque uma data à frente.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold">Fim da Exibição (Vencimento)</label>
            <input type="datetime-local" name="data_fim" id="editar_data_fim" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-info fw-bold px-4 text-white">Salvar Programação</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalLocal() {
    new bootstrap.Modal(document.getElementById('modalNovoLocal')).show();
}

function abrirModalEditar(id, linkAtual, localAtual, valorAtual) {
    document.getElementById('edit_anuncio_id').value = id;
    document.getElementById('edit_link_destino').value = linkAtual;
    document.getElementById('edit_localizacao').value = localAtual;
    document.getElementById('edit_valor_pacote').value = valorAtual;
    new bootstrap.Modal(document.getElementById('modalEditarAnuncio')).show();
}

function abrirModalEditarDatas(id, dataInicio, dataFim) {
    document.getElementById('editar_data_anuncio_id').value = id;
    document.getElementById('editar_data_inicio').value = dataInicio;
    document.getElementById('editar_data_fim').value = dataFim;
    new bootstrap.Modal(document.getElementById('modalEditarDatas')).show();
}

function filtrarClientes() {
    const selecao = document.getElementById('filtroAnunciante').value;
    const sections = document.querySelectorAll('.client-section');
    
    sections.forEach(sec => {
        if (selecao === 'todos' || sec.getAttribute('data-cliente-id') === selecao) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}
</script>
<script src="/src/bootstrap.bundle.min.js"></script>
</body>
</html>