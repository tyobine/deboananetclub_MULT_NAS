<?php
// controllers/admin/anuncios.php
require_once __DIR__ . '/../../models/banco.php';

class Anuncios
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['admin_logado'])) {
            header("Location: /admin/login");
            exit;
        }
        $this->db = new Banco();
    }

    public function index()
    {
        $anunciantes = $this->db->getAll("SELECT * FROM crm_anunciantes ORDER BY nome_empresa ASC");
        $anuncios = $this->db->getAll("SELECT * FROM crm_anuncios ORDER BY id DESC");
        
        $locais = $this->db->getAll("SELECT * FROM crm_locais ORDER BY nome ASC");
        
        $midias_por_anunciante = [];
        foreach ($anuncios as $ad) {
            $midias_por_anunciante[$ad['anunciante_id']][] = $ad;
        }

        require_once __DIR__ . '/../../views/admin/anuncio.php';
    }

    public function salvar_local()
    {
        $nome = strtolower(trim($_POST['nome_local'] ?? ''));
        if (!empty($nome)) {
            $existe = $this->db->getRow("SELECT id FROM crm_locais WHERE nome = ?", [$nome]);
            if (!$existe) {
                $this->db->query("INSERT INTO crm_locais (nome) VALUES (?)", [$nome]);
            }
            header("Location: /admin/anuncio?sucesso=local_salvo");
        } else {
            header("Location: /admin/anuncio?erro=nome_vazio");
        }
        exit;
    }

    public function salvar_anunciante()
    {
        $nome = $_POST['nome_empresa'] ?? '';
        $telefone = $_POST['telefone'] ?? '';

        if (!empty($nome)) {
            $this->db->query("INSERT INTO crm_anunciantes (nome_empresa, telefone) VALUES (?, ?)", [$nome, $telefone]);
            header("Location: /admin/anuncio?sucesso=cliente_salvo");
        } else {
            header("Location: /admin/anuncio?erro=nome_vazio");
        }
        exit;
    }

    public function upload_midia()
    {
        $anunciante_id = (int)($_POST['anunciante_id'] ?? 0);
        $link_destino = trim($_POST['link_destino'] ?? '');
        $localizacao = trim($_POST['localizacao'] ?? 'todos');

        if ($anunciante_id > 0 && isset($_FILES['arquivo_upload']) && $_FILES['arquivo_upload']['error'] === UPLOAD_ERR_OK) {
            
            $extensao = strtolower(pathinfo($_FILES['arquivo_upload']['name'], PATHINFO_EXTENSION));
            $tipo = ($extensao === 'mp4') ? 'video' : 'imagem';
            $nome_arquivo = 'crm_' . $anunciante_id . '_' . time() . '.' . $extensao;
            $caminho_destino = __DIR__ . '/../../uploads/' . $nome_arquivo;
            $caminho_bd = '/uploads/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['arquivo_upload']['tmp_name'], $caminho_destino)) {
                $this->db->query("
                    INSERT INTO crm_anuncios (anunciante_id, tipo, caminho_arquivo, link_destino, exibir, localizacao) 
                    VALUES (?, ?, ?, ?, 'sim', ?)
                ", [$anunciante_id, $tipo, $caminho_bd, $link_destino, $localizacao]);
                
                header("Location: /admin/anuncio?sucesso=midia_salva");
                exit;
            }
        }
        header("Location: /admin/anuncio?erro=falha_upload");
        exit;
    }

    public function toggle_exibicao()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $exibir = $_POST['exibir'] === 'sim' ? 'sim' : 'nao';
        if ($id > 0) $this->db->query("UPDATE crm_anuncios SET exibir = ? WHERE id = ?", [$exibir, $id]);
        header("Location: /admin/anuncio?sucesso=status_atualizado");
        exit;
    }

    public function deletar_midia()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        if ($id > 0) {
            $anuncio = $this->db->getRow("SELECT caminho_arquivo FROM crm_anuncios WHERE id = ?", [$id]);
            if ($anuncio) {
                $caminho_fisico = __DIR__ . '/../..' . $anuncio['caminho_arquivo'];
                if (file_exists($caminho_fisico)) unlink($caminho_fisico); 
                $this->db->query("DELETE FROM crm_anuncios WHERE id = ?", [$id]);
            }
        }
        header("Location: /admin/anuncio?sucesso=midia_deletada");
        exit;
    }

    // 🚀 AGORA ESTA FUNÇÃO EDITA O LINK E A LOCALIZAÇÃO AO MESMO TEMPO
    public function editar_link()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $novo_link = trim($_POST['link_destino'] ?? '');
        $nova_localizacao = trim($_POST['localizacao'] ?? 'todos');
        
        if ($id > 0) {
            $this->db->query("UPDATE crm_anuncios SET link_destino = ?, localizacao = ? WHERE id = ?", [$novo_link, $nova_localizacao, $id]);
        }
        header("Location: /admin/anuncio?sucesso=dados_atualizados");
        exit;
    }
}