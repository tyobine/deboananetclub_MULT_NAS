<?php
// services/pix.php

require_once __DIR__ . '/../models/banco.php';
require_once __DIR__ . '/../models/MercadoPago.php';

class Pix
{
    private $db;
    private $mp;

    public function __construct()
    {
        $this->db = new Banco();
        $this->mp = new MercadoPago();
    }

    /**
     * Cria a cobrança PIX no Mercado Pago e regista no banco como 'pendente'
     */
    public function criarCobranca($price_cents, $mac, $ip, $plano_id, $plano_name)
    {
        $dadosPix = $this->mp->criarPix($price_cents, $mac, $ip, $plano_id, $plano_name);

        // Verifica se a API do Mercado Pago retornou um ID válido
        if ($dadosPix && isset($dadosPix['id'])) {
            $this->db->query("
                INSERT INTO acessos_pix (txid, status, ip_address, mac_address, plano_id, expira_em) 
                VALUES (?, 'pendente', ?, ?, ?, NULL)
            ", [$dadosPix['id'], $ip, $mac, $plano_id]);

            return [
                'sucesso' => true,
                'txid' => $dadosPix['id'],
                'qr_code' => $dadosPix['point_of_interaction']['transaction_data']['qr_code'],
                'qr_code_img' => $dadosPix['point_of_interaction']['transaction_data']['qr_code_base64']
            ];
        }

        return ['sucesso' => false];
    }

    /**
     * Consulta o status de um pagamento no Mercado Pago
     */
    public function consultarStatus($paymentId)
    {
        return $this->mp->consultarPagamento($paymentId);
    }

    /**
     * Faz o estorno (devolve o dinheiro) e marca no banco como 'estornado'
     */
    public function devolverValor($paymentId)
    {
        $this->mp->estornarPix($paymentId);
        $this->db->query("UPDATE acessos_pix SET status = 'estornado' WHERE txid = ?", [$paymentId]);
    }
}
