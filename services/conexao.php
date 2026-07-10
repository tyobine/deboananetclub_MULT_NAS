<?php
// services/conexao.php

require_once __DIR__ . '/../models/banco.php';
require_once __DIR__ . '/../models/mikrotik.php';

class Conexao
{
    private $db;

    public function __construct()
    {
        $this->db = new Banco();
    }

    /**
     * Calcula o tempo, acumula se necessário e libera o MAC no MikroTik da cidade correspondente
     */
    public function processarLiberacao($txid, $mac, $plano_id, $router_id = ROUTER_DEFAULT)
    {
        $plano = $this->db->getRow("SELECT duration_minutes FROM planos WHERE id = ?", [$plano_id]);
        if (!$plano) return false;

        $minutosComprados = $plano['duration_minutes'];
        $agora = time();
        $minutosTotais = $minutosComprados;
        $tempoBaseParaCalculo = $agora;

        $sessaoAtiva = $this->db->getRow("
            SELECT expira_em 
            FROM acessos_pix 
            WHERE mac_address = ? AND status = 'ativo' AND expira_em > NOW() 
            ORDER BY id DESC LIMIT 1
        ", [$mac]);

        if ($sessaoAtiva) {
            $expiracaoAtual = strtotime($sessaoAtiva['expira_em']);

            if ($expiracaoAtual > $agora) {
                $minutosSobra = floor(($expiracaoAtual - $agora) / 60);
                $minutosTotais = $minutosComprados + $minutosSobra;
                $tempoBaseParaCalculo = $expiracaoAtual;
            }
        }

        $expira_em = date('Y-m-d H:i:s', $tempoBaseParaCalculo + ($minutosComprados * 60));

        // Inicia a conexão com o MikroTik EXATO de onde veio o pagamento
        $mk = new Mikrotik($router_id);

        if ($mk->liberarAcessoTempo($mac, $minutosTotais)) {
            $this->db->query("UPDATE acessos_pix SET status = 'ativo', expira_em = ? WHERE txid = ?", [$expira_em, $txid]);
            return true;
        }

        return false;
    }
}
