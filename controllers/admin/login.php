<?php
// controllers/admin/login.php
require_once __DIR__ . '/../../config/config.php';

class Login
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function tela()
    {
        if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
            header("Location: /admin/dashboard");
            exit;
        }
        require_once __DIR__ . '/../../views/admin/login.php';
    }

    public function autenticar()
    {
        if (($_POST['user'] ?? '') === ADMIN_USER && ($_POST['pass'] ?? '') === ADMIN_PASS) {
            $_SESSION['admin_logado'] = true;
            header("Location: /admin/dashboard");
            exit;
        }
        header("Location: /admin/login?erro=1");
        exit;
    }

    public function sair()
    {
        session_destroy();
        header("Location: /admin/login");
        exit;
    }
}
