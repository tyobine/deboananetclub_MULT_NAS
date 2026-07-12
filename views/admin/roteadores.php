<?php
require_once __DIR__ . '/header.php';
?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <!-- Correção do ícone no título -->
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-network-wired"></i> Gerenciar Roteadores (Multi-NAS)</h3>
        <button class="btn btn-primary fw-bold" onclick="abrirModalNovo()"><i class="fa-solid fa-plus"></i> Novo Roteador</button>
    </div>

    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <?php if ($_GET['sucesso'] == 'cadastrado'): ?><strong>Sucesso!</strong> Roteador cadastrado.
            <?php elseif ($_GET['sucesso'] == 'atualizado'): ?><strong>Sucesso!</strong> Roteador atualizado.
            <?php elseif ($_GET['sucesso'] == 'deletado'): ?><strong>Sucesso!</strong> Roteador excluído.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <?php if ($_GET['erro'] == 'dados_invalidos'): ?><strong>Erro!</strong> Preencha todos os campos obrigatórios.
            <?php elseif ($_GET['erro'] == 'identificador_existe'): ?><strong>Erro!</strong> Já existe um roteador com este identificador.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Identificador (URL)</th>
                            <th>Host (IP Público/DNS)</th>
                            <th>Porta</th>
                            <th>IP Hotspot Local</th>
                            <th>Padrão</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($roteadores)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum roteador cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach($roteadores as $rot): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($rot['nome_identificador']) ?></td>
                                <td><?= htmlspecialchars($rot['host']) ?></td>
                                <td><?= htmlspecialchars($rot['port']) ?></td>
                                <td><?= htmlspecialchars($rot['hotspot_ip']) ?></td>
                                <td>
                                    <?php if($rot['is_default']): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check"></i> Sim</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($rot)) ?>)">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="/admin/roteadores/deletar" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza? Isso pode afetar os anúncios e acessos deste local.');">
                                        <input type="hidden" name="roteador_id" value="<?= $rot['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
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

<!-- Modal Roteador -->
<div class="modal fade" id="modalRoteador" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <form action="/admin/roteadores/salvar" method="POST">
        <div class="modal-header bg-light">
          <!-- Correção do ícone no modal -->
          <h5 class="modal-title fw-bold" id="modalTitle"><i class="fa-solid fa-network-wired text-primary"></i> Cadastrar Roteador</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="roteador_id" id="roteador_id" value="0">
          
          <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Identificador (ex: fortaleza)</label>
                <input type="text" name="nome_identificador" id="nome_identificador" class="form-control" placeholder="Letras minúsculas, sem espaço" required>
                <div class="form-text">É a palavra usada na URL (ex: ?router=fortaleza)</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Host (API Mikrotik)</label>
                <input type="text" name="host" id="host" class="form-control" placeholder="IP Público ou DNS" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Usuário API</label>
                <input type="text" name="user" id="user" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Senha API</label>
                <input type="password" name="pass" id="pass" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Porta API</label>
                <input type="number" name="port" id="port" class="form-control" value="8080" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">IP Local do Hotspot</label>
                <input type="text" name="hotspot_ip" id="hotspot_ip" class="form-control" placeholder="ex: 10.50.0.1" required>
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1">
                  <label class="form-check-label small fw-bold" for="is_default">Tornar Roteador Padrão</label>
                </div>
              </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary fw-bold px-4">Salvar Roteador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="/src/bootstrap.bundle.min.js"></script>

<script>
const modalObj = new bootstrap.Modal(document.getElementById('modalRoteador'));

function abrirModalNovo() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus text-primary"></i> Cadastrar Roteador';
    document.getElementById('roteador_id').value = '0';
    document.getElementById('nome_identificador').value = '';
    document.getElementById('host').value = '';
    document.getElementById('user').value = '';
    document.getElementById('pass').value = '';
    document.getElementById('port').value = '8080';
    document.getElementById('hotspot_ip').value = '';
    document.getElementById('is_default').checked = false;
    modalObj.show();
}

function abrirModalEditar(rot) {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen text-primary"></i> Editar Roteador';
    document.getElementById('roteador_id').value = rot.id;
    document.getElementById('nome_identificador').value = rot.nome_identificador;
    document.getElementById('host').value = rot.host;
    document.getElementById('user').value = rot.user;
    document.getElementById('pass').value = rot.pass;
    document.getElementById('port').value = rot.port;
    document.getElementById('hotspot_ip').value = rot.hotspot_ip;
    document.getElementById('is_default').checked = rot.is_default == 1;
    modalObj.show();
}
</script>

</body>
</html>