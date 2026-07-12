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
        
        require_once __DIR__ . '/../../models/Roteador.php';
        $modeloRoteador = new Roteador();
        $locais = $modeloRoteador->obterTodos();
        
        $midias_por_anunciante = [];
        foreach ($anuncios as $ad) {
            $midias_por_anunciante[$ad['anunciante_id']][] = $ad;
        }

        require_once __DIR__ . '/../../views/admin/anuncio.php';
    }

    public function salvar_local()
    {
        // Esta função não é mais usada (os roteadores agora são gerenciados em /admin/roteadores)
        header("Location: /admin/roteadores");
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
        $locais_array = $_POST['localizacao'] ?? ['todos'];
        $localizacao = is_array($locais_array) ? implode(',', $locais_array) : trim($locais_array);
        if (empty($localizacao)) $localizacao = 'todos';

        if ($anunciante_id > 0 && isset($_FILES['arquivo_upload']) && $_FILES['arquivo_upload']['error'] === UPLOAD_ERR_OK) {
            
            $tmp_name = $_FILES['arquivo_upload']['tmp_name'];
            $file_size = $_FILES['arquivo_upload']['size'];
            
            // Trava de segurança: Máximo 10MB (evita quebra do servidor e lentidão excessiva)
            if ($file_size > 10 * 1024 * 1024) {
                header("Location: /admin/anuncio?erro=arquivo_muito_grande");
                exit;
            }
            
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
            $nome_arquivo_base = 'crm_' . $anunciante_id . '_' . time();
            $sucesso_upload = false;

            // Tratamento de Compressão Extrema WebP para JPG e PNG
            if ($mime_type === 'image/jpeg' || $mime_type === 'image/png') {
                $extensao = 'webp';
                $nome_arquivo = $nome_arquivo_base . '.' . $extensao;
                $caminho_destino = __DIR__ . '/../../uploads/' . $nome_arquivo;
                
                if ($mime_type === 'image/jpeg') {
                    $imagem = imagecreatefromjpeg($tmp_name);
                } else {
                    $imagem = imagecreatefrompng($tmp_name);
                    imagepalettetotruecolor($imagem); // Garante conversão correta de paletas indexadas
                    imagealphablending($imagem, true);
                    imagesavealpha($imagem, true);
                }
                
                // Converte e salva como WebP com qualidade 80 (redução dramática de tamanho sem perda visual perceptível)
                if ($imagem !== false) {
                    $sucesso_upload = imagewebp($imagem, $caminho_destino, 80);
                    imagedestroy($imagem);
                }
            } else {
                // GIF and MP4 (mantém extensões originais e move o arquivo bruto)
                $nome_arquivo = $nome_arquivo_base . '.' . $extensao;
                $caminho_destino = __DIR__ . '/../../uploads/' . $nome_arquivo;
                $sucesso_upload = move_uploaded_file($tmp_name, $caminho_destino);
            }

            $caminho_bd = '/uploads/' . $nome_arquivo;

            if ($sucesso_upload) {
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
                $caminho_fisico = __DIR__ . '/../.. ' . $anuncio['caminho_arquivo'];
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
        $locais_array = $_POST['localizacao'] ?? ['todos'];
        $nova_localizacao = is_array($locais_array) ? implode(',', $locais_array) : trim($locais_array);
        if (empty($nova_localizacao)) $nova_localizacao = 'todos';
        
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

    public function salvar_configuracoes()
    {
        $tempo_anuncio = (int)($_POST['tempo_anuncio'] ?? 15);
        $tempo_limite = (int)($_POST['tempo_limite'] ?? 30);
        $exibir_ad_pos_pago = trim($_POST['exibir_ad_pos_pago'] ?? 'passivo');

        // Sanitização e Defesa contra inputs maliciosos
        if ($tempo_anuncio < 5) $tempo_anuncio = 5;
        if ($tempo_limite < 0) $tempo_limite = 0;
        if ($exibir_ad_pos_pago !== 'nao' && $exibir_ad_pos_pago !== 'passivo') {
            $exibir_ad_pos_pago = 'passivo';
        }

        // Utiliza a estrutura padrão de UPSERT do MySQL para tabelas Chave-Valor
        $this->db->query("INSERT INTO configuracoes (chave, valor) VALUES ('tempo_anuncio', ?) ON DUPLICATE KEY UPDATE valor = ?", [$tempo_anuncio, $tempo_anuncio]);
        $this->db->query("INSERT INTO configuracoes (chave, valor) VALUES ('tempo_limite', ?) ON DUPLICATE KEY UPDATE valor = ?", [$tempo_limite, $tempo_limite]);
        $this->db->query("INSERT INTO configuracoes (chave, valor) VALUES ('exibir_ad_pos_pago', ?) ON DUPLICATE KEY UPDATE valor = ?", [$exibir_ad_pos_pago, $exibir_ad_pos_pago]);

        header("Location: /admin/anuncio?sucesso=config_salva");
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