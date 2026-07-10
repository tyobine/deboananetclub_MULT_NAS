<?php
// controllers/admin/transacoes.php
require_once __DIR__ . '/../../models/banco.php';

class Transacoes
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['admin_logado'])) {
            header("Location: /admin/login");
            exit;
        }
    }

    public function index()
    {
        $db = new Banco();

        // Captura os filtros da URL
        $search = $_GET['search'] ?? '';
        $router = $_GET['router'] ?? '';
        $status = $_GET['status'] ?? '';

        // Base da Query SQL
        $sql = "
            SELECT a.id, a.txid, a.ip_address, a.mac_address, a.whatsapp, a.status, a.expira_em, a.router_id,
                   p.name AS plan_name, p.price_cents AS amount_cents 
            FROM acessos_pix a 
            LEFT JOIN planos p ON a.plano_id = p.id 
            WHERE 1=1
        ";
        $params = [];

        // Filtro de Busca (Procura no MAC, WhatsApp ou ID do Comprovante/TXID)
        if (!empty($search)) {
            $sql .= " AND (a.mac_address LIKE ? OR a.whatsapp LIKE ? OR a.txid LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filtro por Cidade/Router
        if (!empty($router)) {
            $sql .= " AND a.router_id = ?";
            $params[] = $router;
        }

        // Filtro por Status
        if (!empty($status)) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.id DESC LIMIT 100";

        // Executa a busca com ou sem parâmetros
        if (!empty($params)) {
            $transactions = $db->getAll($sql, $params);
        } else {
            $transactions = $db->getAll($sql);
        }

        require_once __DIR__ . '/../../views/admin/transacoes.php';
    }
}
