<?php
// controllers/admin/dashboard.php
require_once __DIR__ . '/../../models/banco.php';
require_once __DIR__ . '/../../models/mikrotik.php';

class Dashboard
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
        date_default_timezone_set('America/Fortaleza');

        // Auto-limpeza: derruba quem passou da hora
        $db->query("UPDATE acessos_pix SET status = 'expirado' WHERE status = 'ativo' AND expira_em < NOW()");

        $hoje = $db->getRow("SELECT SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') AND DATE(a.expira_em) = CURDATE()");
        $faturamentoHoje = ($hoje['total'] ?? 0) / 100;

        $mes = $db->getRow("SELECT SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') AND MONTH(a.expira_em) = MONTH(CURDATE()) AND YEAR(a.expira_em) = YEAR(CURDATE())");
        $faturamentoMes = ($mes['total'] ?? 0) / 100;

        // ------------------------------------------------------------------
        // A MÁGICA DO MULTI-NAS: Somar utilizadores de TODOS os roteadores
        // ------------------------------------------------------------------
        $clientesAtivos = 0;
        foreach (ROUTERS as $router_id => $config) {
            try {
                $mk = new Mikrotik($router_id);
                $clientesAtivos += $mk->contarUtilizadoresAtivos();
            } catch (\Throwable $th) {
                // Se algum roteador estiver offline (ex: sem energia),
                // o try-catch impede que a página quebre e apenas ignora ele.
            }
        }

        $vendas7Dias = $db->getAll("SELECT DATE_FORMAT(a.expira_em, '%d/%m') as dia, SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') GROUP BY DATE(a.expira_em) ORDER BY DATE(a.expira_em) ASC LIMIT 7");

        $graficoDados = ['dias' => [], 'valores' => []];
        foreach ($vendas7Dias as $venda) {
            $graficoDados['dias'][] = $venda['dia'];
            $graficoDados['valores'][] = $venda['total'] / 100;
        }
        if (empty($graficoDados['dias'])) {
            $graficoDados['dias'] = [date('d/m')];
            $graficoDados['valores'] = [0];
        }

        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }
}
