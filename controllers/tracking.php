<?php
// controllers/tracking.php
require_once __DIR__ . '/../models/banco.php';

class Tracking
{
    private $db;

    public function __construct()
    {
        $this->db = new Banco();
    }

    // 1. Grava a Visualização silenciosamente via JavaScript (Fetch)
    public function registrarVisualizacao()
    {
        header('Content-Type: application/json');
        
        // Tenta ler o ID que vem do JavaScript
        $dados = json_decode(file_get_contents('php://input'), true);
        $anuncio_id = (int) ($dados['anuncio_id'] ?? $_GET['anuncio_id'] ?? 0);

        if ($anuncio_id > 0) {
            $this->db->query("INSERT INTO crm_views (anuncio_id) VALUES (?)", [$anuncio_id]);
            echo json_encode(['sucesso' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'ID de anuncio invalido']);
        }
        exit;
    }

    // 2. Grava o Clique e redireciona o cliente para o WhatsApp/Site do patrocinador
    public function registrarClique()
    {
        $anuncio_id = (int)($_GET['id'] ?? 0);

        if ($anuncio_id > 0) {
            // Regista o clique no banco
            $this->db->query("INSERT INTO crm_cliques (anuncio_id) VALUES (?)", [$anuncio_id]);

            // Busca o link de destino que você configurou no painel
            $anuncio = $this->db->getRow("SELECT link_destino FROM crm_anuncios WHERE id = ?", [$anuncio_id]);

            if ($anuncio && !empty(trim($anuncio['link_destino']))) {
                // Redireciona o cliente para o patrocinador
                header("Location: " . trim($anuncio['link_destino']));
                exit;
            }
        }

        // Se o anúncio não tiver link de destino, volta para a tela inicial
        header("Location: /inicio");
        exit;
    }
}