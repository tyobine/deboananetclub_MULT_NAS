<?php
// controllers/admin/roteadores.php
require_once __DIR__ . '/../../models/banco.php';
require_once __DIR__ . '/../../models/Roteador.php';

class RoteadoresController
{
    private $db;
    private $roteadorModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['admin_logado'])) {
            header("Location: /admin/login");
            exit;
        }
        $this->db = new Banco();
        $this->roteadorModel = new Roteador();
    }

    public function index()
    {
        $roteadores = $this->roteadorModel->obterTodos();
        require_once __DIR__ . '/../../views/admin/roteadores.php';
    }

    public function salvar()
    {
        $id = (int)($_POST['roteador_id'] ?? 0);
        $nome_identificador = strtolower(trim($_POST['nome_identificador'] ?? ''));
        $host = trim($_POST['host'] ?? '');
        $user = trim($_POST['user'] ?? '');
        $pass = trim($_POST['pass'] ?? '');
        $port = (int)($_POST['port'] ?? 8080);
        $hotspot_ip = trim($_POST['hotspot_ip'] ?? '');
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        if (empty($nome_identificador) || empty($host)) {
            header("Location: /admin/roteadores?erro=dados_invalidos");
            exit;
        }

        // Se marcou como padrão, remove o padrão dos outros
        if ($is_default) {
            $this->db->query("UPDATE crm_roteadores SET is_default = 0");
        }

        if ($id > 0) {
            $this->db->query("UPDATE crm_roteadores SET nome_identificador = ?, host = ?, user = ?, pass = ?, port = ?, hotspot_ip = ?, is_default = ? WHERE id = ?", 
                [$nome_identificador, $host, $user, $pass, $port, $hotspot_ip, $is_default, $id]);
            header("Location: /admin/roteadores?sucesso=atualizado");
        } else {
            $existe = $this->roteadorModel->obterPorIdentificador($nome_identificador);
            if ($existe) {
                header("Location: /admin/roteadores?erro=identificador_existe");
                exit;
            }
            $this->db->query("INSERT INTO crm_roteadores (nome_identificador, host, user, pass, port, hotspot_ip, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                [$nome_identificador, $host, $user, $pass, $port, $hotspot_ip, $is_default]);
            header("Location: /admin/roteadores?sucesso=cadastrado");
        }
        exit;
    }

    public function deletar()
    {
        $id = (int)($_POST['roteador_id'] ?? 0);
        if ($id > 0) {
            $this->db->query("DELETE FROM crm_roteadores WHERE id = ?", [$id]);
        }
        header("Location: /admin/roteadores?sucesso=deletado");
        exit;
    }
}
?>
