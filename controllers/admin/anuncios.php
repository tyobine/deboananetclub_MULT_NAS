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
        $pacote_tipo = $_POST['pacote_tipo'] ?? '1dia';
        $valor_pacote = (int)($_POST['valor_pacote'] ?? 0);

        if ($anunciante_id > 0 && isset($_FILES['arquivo_upload']) && $_FILES['arquivo_upload']['error'] === UPLOAD_ERR_OK) {
            
            $extensao = strtolower(pathinfo($_FILES['arquivo_upload']['name'], PATHINFO_EXTENSION));
            $tipo = ($extensao === 'mp4') ? 'video' : 'imagem';
            $nome_arquivo = 'crm_' . $anunciante_id . '_' . time() . '.' . $extensao;
            $caminho_destino = __DIR__ . '/../../uploads/' . $nome_arquivo;
            $caminho_bd = '/uploads/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['arquivo_upload']['tmp_name'], $caminho_destino)) {
                // Calcular data de fim baseado no pacote
                $data_inicio = date('Y-m-d H:i:s');
                $data_fim = $this->calcularDataFim($pacote_tipo);

                $this->db->query("
                    INSERT INTO crm_anuncios (anunciante_id, tipo, caminho_arquivo, link_destino, exibir, localizacao, pacote_tipo, valor_pacote, data_inicio, data_fim) 
                    VALUES (?, ?, ?, ?, 'sim', ?, ?, ?, ?, ?)
                ", [$anunciante_id, $tipo, $caminho_bd, $link_destino, $localizacao, $pacote_tipo, $valor_pacote, $data_inicio, $data_fim]);
                
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

    /**
     * Renovar anúncio (estender data de fim)
     */
    public function renovar_anuncio()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $pacote_tipo = $_POST['pacote_tipo'] ?? '1dia';

        if ($id > 0) {
            $anuncio = $this->db->getRow("SELECT data_fim FROM crm_anuncios WHERE id = ?", [$id]);
            
            if ($anuncio) {
                $nova_data_fim = $this->calcularDataFim($pacote_tipo, $anuncio['data_fim']);
                $valor_pacote = $this->obterValorPacote($pacote_tipo);

                $this->db->query(
                    "UPDATE crm_anuncios SET data_fim = ?, pacote_tipo = ?, valor_pacote = valor_pacote + ? WHERE id = ?",
                    [$nova_data_fim, $pacote_tipo, $valor_pacote, $id]
                );
            }
        }
        header("Location: /admin/anuncio?sucesso=anuncio_renovado");
        exit;
    }

    /**
     * Reativar anúncio com nova data de fim
     */
    public function reativar_anuncio()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $pacote_tipo = $_POST['pacote_tipo'] ?? '1dia';

        if ($id > 0) {
            $data_inicio = date('Y-m-d H:i:s');
            $data_fim = $this->calcularDataFim($pacote_tipo);
            $valor_pacote = $this->obterValorPacote($pacote_tipo);

            $this->db->query(
                "UPDATE crm_anuncios SET data_inicio = ?, data_fim = ?, pacote_tipo = ?, valor_pacote = ?, exibir = 'sim' WHERE id = ?",
                [$data_inicio, $data_fim, $pacote_tipo, $valor_pacote, $id]
            );
        }
        header("Location: /admin/anuncio?sucesso=anuncio_reativado");
        exit;
    }

    /**
     * Editar data de fim manualmente
     */
    public function editar_data_fim()
    {
        $id = (int)($_POST['anuncio_id'] ?? 0);
        $nova_data_fim = trim($_POST['data_fim'] ?? '');

        if ($id > 0 && !empty($nova_data_fim)) {
            $this->db->query("UPDATE crm_anuncios SET data_fim = ? WHERE id = ?", [$nova_data_fim, $id]);
        }
        header("Location: /admin/anuncio?sucesso=data_atualizada");
        exit;
    }

    /**
     * Calcular data de fim baseado no pacote
     */
    private function calcularDataFim($pacote_tipo, $dataBase = null)
    {
        $data = $dataBase ? new DateTime($dataBase) : new DateTime();

        switch ($pacote_tipo) {
            case '1dia':
                $data->add(new DateInterval('P1D'));
                break;
            case '1semana':
                $data->add(new DateInterval('P7D'));
                break;
            case '15dias':
                $data->add(new DateInterval('P15D'));
                break;
            default:
                $data->add(new DateInterval('P1D'));
        }

        return $data->format('Y-m-d H:i:s');
    }

    /**
     * Obter valor do pacote (em centavos)
     */
    private function obterValorPacote($pacote_tipo)
    {
        $valores = [
            '1dia' => 5000,      // R$ 50,00
            '1semana' => 30000,  // R$ 300,00
            '15dias' => 60000    // R$ 600,00
        ];

        return $valores[$pacote_tipo] ?? 0;
    }

    /**
     * Obter status do anúncio
     */
    public static function obterStatus($dataInicio, $dataFim)
    {
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

    /**
     * Calcular dias restantes
     */
    public static function obterDiasRestantes($dataFim)
    {
        $agora = new DateTime();
        $fim = new DateTime($dataFim);
        $intervalo = $agora->diff($fim);

        return $intervalo->days;
    }
}
?>
