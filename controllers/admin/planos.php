<?php
// controllers/admin/planos.php
require_once __DIR__ . '/../../models/banco.php';

class Planos
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
        $plans = $this->db->getAll("SELECT * FROM planos ORDER BY id DESC");
        require_once __DIR__ . '/../../views/admin/planos.php';
    }

    public function criar()
    {
        $this->db->query("INSERT INTO planos (name, price_cents, duration_minutes) VALUES (?, ?, ?)", [$_POST['name'] ?? '', $_POST['price_cents'] ?? 0, $_POST['duration_minutes'] ?? 0]);
        header("Location: /admin/plans");
        exit;
    }

    public function atualizar()
    {
        $this->db->query("UPDATE planos SET name = ?, price_cents = ?, duration_minutes = ? WHERE id = ?", [$_POST['name'] ?? '', $_POST['price_cents'] ?? 0, $_POST['duration_minutes'] ?? 0, $_POST['id'] ?? 0]);
        header("Location: /admin/plans");
        exit;
    }

    public function alternar()
    {
        $this->db->query("UPDATE planos SET ativo = NOT ativo WHERE id = ?", [$_POST['id'] ?? 0]);
        header("Location: /admin/plans");
        exit;
    }

    public function deletar()
    {
        $this->db->query("DELETE FROM planos WHERE id = ?", [$_POST['id'] ?? 0]);
        header("Location: /admin/plans");
        exit;
    }
}
