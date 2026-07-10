// view/admin/anuncio.php_check_syntax
<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../../models/banco.php';

$db_metricas = new Banco();
?>

<style>
    .media-preview { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; background: #111; }
    .media-card { transition: transform 0.2s; border: 1px solid #ddd; }
    .media-card:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .box-ativo { border: 2px solid #198754; background-color: #f8fff9; }
    .badge-metricas { position: absolute; top: -10px; right: -10px; background-color: #212529; color: #ffffff; font-weight: 700; font-size: 0.85rem; border-radius: 20px; padding: 4px 10px; display: flex; gap: 10px; align-items: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); z-index: 10; border: 2px solid #ffffff; }
    .badge-metricas span { display: flex; align-items: center; gap: 4px; }
    .text-pink { color: #ff00ff; } 
    .text-blue { color: #00d4ff; }
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
                <div class="card-header bg-primary text-white fw-bold"><i class="fa-solid fa-cloud-arrow-up"></i> Enviar Mídia</div>
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
                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none fw-bold" onclick="abrirModalLocal()"><i class="fa-solid fa-plus"></i> Novo Local</button>
                            </div>
                            <select name="localizacao" class="form-select" required>
                                <?php foreach ($locais as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc['nome']) ?>"><?= mb_strtoupper($loc['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Link de Destino</label>
                            <input type="url" name="link_destino" class="form-control">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Arquivo</label>
                            <input type="file" name="arquivo_upload" class="form-control" accept="image/*,video/mp4" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" <?= empty($anunciantes) ? 'disabled' : '' ?>>Fazer Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h5 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-users-viewfinder"></i> Gestão de Campanhas</h5>
            
            <?php if (empty($anunciantes)): ?>
                <div class="text-center w-100 py-5 bg-white rounded shadow-sm text-muted border">
                    <h5>Nenhum anunciante cadastrado.</h5>
                </div>
            <?php else: ?>
                <?php foreach ($anunciantes as $cliente): ?>
                    <div class="client-section">
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
                                    $total_views = $db_metricas->getRow("SELECT COUNT(id) as total FROM crm_views WHERE anuncio_id = ?", [$ad['id']])['total'] ?? 0;
                                    $total_cliques = $db_metricas->getRow("SELECT COUNT(id) as total FROM crm_cliques WHERE anuncio_id = ?", [$ad['id']])['total'] ?? 0;
                                    ?>
                                    <div class="col-6 col-md-6 col-lg-4">
                                        <div class="card media-card h-100 p-2 position-relative <?= $is_ativo ? 'box-ativo' : '' ?>">
                                            
                                            <span class="badge bg-info text-dark badge-local"><i class="fa-solid fa-location-dot"></i> <?= $local === 'todos' ? 'Global' : mb_strtoupper($local) ?></span>

                                            <?php if($total_views > 0 || $total_cliques > 0): ?>
                                            <div class="badge-metricas">
                                                <span class="text-blue"><i class="fa-solid fa-eye"></i> <?= $total_views > 999 ? '999+' : $total_views ?></span>
                                                <span class="text-pink"><i class="fa-solid fa-hand-pointer"></i> <?= $total_cliques > 999 ? '999+' : $total_cliques ?></span>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($is_video): ?>
                                                <video class="media-preview" autoplay loop muted playsinline><source src="<?= $caminho ?>" type="video/mp4"></video>
                                            <?php else: ?>
                                                <img src="<?= $caminho ?>" class="media-preview">
                                            <?php endif; ?>

                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                                <form action="/admin/anuncio/toggle-midia" method="POST" class="m-0">
                                                    <input type="hidden" name="anuncio_id" value="<?= $ad['id'] ?>">
                                                    <input type="hidden" name="exibir" value="<?= $is_ativo ? 'nao' : 'sim' ?>">
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" role="switch" <?= $is_ativo ? 'checked' : '' ?> onchange="this.form.submit()">
                                                    </div>
                                                </form>

                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="abrirModalEditar(<?= $ad['id'] ?>, '<?= htmlspecialchars($ad['link_destino'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($ad['localizacao'] ?? 'todos', ENT_QUOTES) ?>')" title="Editar Anúncio"><i class="fa-solid fa-pen-to-square"></i></button>
                                                    <form action="/admin/anuncio/delete-midia" method="POST" class="m-0" onsubmit="return confirm('Tem certeza?');">
                                                        <input type="hidden" name="anuncio_id" value="<?= $ad['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash-can"></i></button>
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

<div class="modal fade" id="modalEditarAnuncio" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="/admin/anuncio/editar-link" method="POST">
        <div class="modal-header bg-light">
          <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-primary"></i> Editar Anúncio</h5>
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

        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary fw-bold px-4">Salvar Alterações</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalLocal() {
    new bootstrap.Modal(document.getElementById('modalNovoLocal')).show();
}

// 🚀 FUNÇÃO ATUALIZADA PARA PREENCHER LOCAL E LINK
function abrirModalEditar(id, linkAtual, localAtual) {
    document.getElementById('edit_anuncio_id').value = id;
    document.getElementById('edit_link_destino').value = linkAtual;
    document.getElementById('edit_localizacao').value = localAtual;
    new bootstrap.Modal(document.getElementById('modalEditarAnuncio')).show();
}
</script>
<script src="/src/bootstrap.bundle.min.js"></script>
</body>
</html>