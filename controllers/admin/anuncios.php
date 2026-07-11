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
            
            $tmp_name = $_FILES['arquivo_upload']['tmp_name'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            $mime_permitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'video/mp4' => 'mp4'
            ];

            if (!array_key_exists($mime_type, $mime_permitidos)) {
                header("Location: /admin/anuncio?erro=formato_invalido");
                exit;
            }

            $extensao = $mime_permitidos[$mime_type];
            $tipo = ($extensao === 'mp4') ? 'video' : 'imagem';
            $nome_arquivo = 'crm_' . $anunciante_id . '_' . time() . '.' . $extensao;
            $caminho_destino = __DIR__ . '/../../uploads/' . $nome_arquivo;
            $caminho_bd = '/uploads/' . $nome_arquivo;

            if (move_uploaded_file($tmp_name, $caminho_destino)) {
                $data_inicio = date('Y-m-d H:i:s');
                $data_fim = date('Y-m-d H:i:s', strtotime('+30 days'));

                // O valor fica R$ 0,00 na hora do upload. Sem atrito.
                $this->db->query("
                    INSERT INTO crm_anuncios (anunciante_id, tipo, caminho_arquivo, link_destino, exibir, localizacao, pacote_tipo, valor_pacote, data_inicio, data_fim) 
                    VALUES (?, ?, ?, ?, 'sim', ?, 'avulso', 0, ?, ?)
                ", [$anunciante_id, $tipo, $caminho_bd, $link_destino, $localizacao, $data_inicio, $data_fim]);
                
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

    public function editar_dados_anuncio()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $novo_link = trim($_POST['link_destino'] ?? '');
        $nova_localizacao = trim($_POST['localizacao'] ?? 'todos');
        
        // Pega o valor recebido no input, aceitando decimais, e converte pra centavos para salvar no banco.
        $valor_float = (float)($_POST['valor_pacote'] ?? 0);
        $valor_pacote = (int)round($valor_float * 100);
        
        if ($id > 0) {
            $this->db->query("UPDATE crm_anuncios SET link_destino = ?, localizacao = ?, valor_pacote = ? WHERE id = ?", [$novo_link, $nova_localizacao, $valor_pacote, $id]);
        }
        header("Location: /admin/anuncio?sucesso=dados_atualizados");
        exit;
    }

    public function editar_datas()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $data_inicio = trim($_POST['data_inicio'] ?? '');
        $data_fim = trim($_POST['data_fim'] ?? '');

        if ($id > 0 && !empty($data_inicio) && !empty($data_fim)) {
            $this->db->query("UPDATE crm_anuncios SET data_inicio = ?, data_fim = ?, exibir = 'sim' WHERE id = ?", [$data_inicio, $data_fim, $id]);
        }
        header("Location: /admin/anuncio?sucesso=data_atualizada");
        exit;
    }

    public static function obterStatus($dataInicio, $dataFim, $exibir = 'sim')
    {
        if ($exibir === 'nao') {
            return ['status' => 'inativo', 'badge' => 'secondary', 'texto' => 'Pausado/Inativo'];
        }

        $agora = new DateTime();
        $inicio = new DateTime($dataInicio);
        $fim = new DateTime($dataFim);

        if ($agora < $inicio) {
            return ['status' => 'programado', 'badge' => 'warning', 'texto' => 'Programado'];
        } elseif ($agora >= $inicio && $agora <= $fim) {
            return ['status' => 'ativo', 'badge' => 'success', 'texto' => 'Ativo'];
        } else {
            return ['status' => 'expirado', 'badge' => 'danger', 'texto' => 'Expirado'];
        }
    }

    public static function obterDiasRestantes($dataFim)
    {
        $agora = new DateTime();
        $fim = new DateTime($dataFim);
        $intervalo = $agora->diff($fim);
        
        if ($fim < $agora) return 0;
        return $intervalo->days;
    }
}
?>