<?php
// controllers/admin/dashboard.php
require_once __DIR__ . '/../../models/banco.php';
require_once __DIR__ . '/../../models/mikrotik.php';
require_once __DIR__ . '/../../utils/check_routers.php';

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

        $hoje = $db->getRow("SELECT SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') AND DATE(a.expira_em) = CURDATE() AND a.status != 'pendente'");
        $faturamentoHoje = ($hoje['total'] ?? 0) / 100;

        $mes = $db->getRow("SELECT SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') AND MONTH(a.expira_em) = MONTH(CURDATE()) AND YEAR(a.expira_em) = YEAR(CURDATE()) AND a.status != 'pendente'");
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
                // Se algum roteador estiver offline, ignora
            }
        }

        $vendas7Dias = $db->getAll("SELECT DATE_FORMAT(a.expira_em, '%d/%m') as dia, SUM(p.price_cents) as total FROM acessos_pix a INNER JOIN planos p ON a.plano_id = p.id WHERE a.status IN ('ativo', 'expirado') AND a.status != 'pendente' AND a.expira_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(a.expira_em) ORDER BY a.expira_em ASC");

        $graficoDados = ['dias' => [], 'valores' => []];
        foreach ($vendas7Dias as $venda) {
            $graficoDados['dias'][] = $venda['dia'];
            $graficoDados['valores'][] = $venda['total'] / 100;
        }
        if (empty($graficoDados['dias'])) {
            $graficoDados['dias'] = [date('d/m')];
            $graficoDados['valores'] = [0];
        }

        // ========== STATUS DOS ROTEADORES ==========
        $rotoresStatus = VerificadorRoteadores::statusTodos();

        // ========== MÉTRICAS DE ANÚNCIOS ==========
        $anunciosAtivos = $db->getRow(
            "SELECT COUNT(*) as total FROM crm_anuncios 
             WHERE exibir = 'sim' AND data_inicio <= NOW() AND data_fim > NOW()"
        )['total'] ?? 0;

        $anunciosExpirados = $db->getRow(
            "SELECT COUNT(*) as total FROM crm_anuncios 
             WHERE data_fim <= NOW()"
        )['total'] ?? 0;

        $anunciosProgramados = $db->getRow(
            "SELECT COUNT(*) as total FROM crm_anuncios 
             WHERE data_inicio > NOW()"
        )['total'] ?? 0;

        // ========== RECEITA POR PACOTE ==========
        $receitaPorPacote = $db->getAll(
            "SELECT pacote_tipo, COUNT(*) as quantidade, SUM(valor_pacote) as total 
             FROM crm_anuncios 
             GROUP BY pacote_tipo 
             ORDER BY total DESC"
        );

        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }
}
?>
