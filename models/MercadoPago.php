<?php
// models/MercadoPago.php

require_once __DIR__ . '/../config/config.php';

class MercadoPago
{
    private function request($endpoint, $method = 'GET', $data = null, $customIdempotencyKey = null)
    {
        $url = "https://api.mercadopago.com/v1/" . $endpoint;
        $ch = curl_init($url);

        $headers = [
            "Authorization: Bearer " . MP_TOKEN,
            "Content-Type: application/json"
        ];

        if (($method === 'POST' || $method === 'PUT') && $customIdempotencyKey !== null) {
            $headers[] = "X-Idempotency-Key: " . $customIdempotencyKey;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function criarPix($valor_cents, $mac, $ip, $plano_id, $descricao, $router_id = ROUTER_DEFAULT)
    {
        $valor_decimal = $valor_cents / 100;
        
        // Chave de idempotência segura (1 minuto de janela para evitar duplicação por clique duplo)
        $idempotencyKey = md5($mac . '_' . $plano_id . '_' . date('Y-m-d_H:i'));

        // =========================================================================
        // CONSISTÊNCIA DE TEMPO (ISO 8601 exato exigido pelo Mercado Pago)
        // =========================================================================
        // P = Diferença para o Horário de Greenwich (GMT) com dois pontos entre horas e minutos (ex: -03:00)
        // v = Milissegundos
        $date_of_expiration = date('Y-m-d\TH:i:s.vP', strtotime('+30 minutes'));

        // =========================================================================
        // CONSISTÊNCIA DE URL DO WEBHOOK (Usando a BASE_URL do config.php)
        // =========================================================================
        $notificationUrl = BASE_URL . "/webhook";

        $externalReference = "hotspot_" . uniqid();

        $dados = [
            "transaction_amount" => (float)$valor_decimal,
            "description" => "Acesso Hotspot: " . $descricao,
            "payment_method_id" => "pix",
            "date_of_expiration" => $date_of_expiration,
            "external_reference" => $externalReference,
            "notification_url" => $notificationUrl,
            "payer" => [
                "email" => "cliente_hotspot@seuprovedor.com.br",
                "first_name" => "Cliente",
                "last_name" => "Hotspot"
            ],
            "additional_info" => [
                "items" => [
                    [
                        "id" => (string)$plano_id,
                        "title" => "Plano de Internet Wi-Fi",
                        "description" => $descricao,
                        "category_id" => "virtual_goods",
                        "quantity" => 1,
                        "unit_price" => (float)$valor_decimal
                    ]
                ]
            ],
            "metadata" => [
                "mac_address" => $mac,
                "ip_address"  => $ip,
                "plano_id"    => $plano_id,
                "router_id"   => $router_id  // Injetando a informação do NAS correto
            ]
        ];

        return $this->request("payments", "POST", $dados, $idempotencyKey);
    }

    public function consultarPagamento($paymentId)
    {
        return $this->request("payments/{$paymentId}", "GET");
    }

    public function estornarPix($paymentId)
    {
        return $this->request("payments/{$paymentId}/refunds", "POST", [], uniqid('refund_'));
    }
}