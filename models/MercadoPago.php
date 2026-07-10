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

    // NOVA ASSINATURA: Agora aceita o $router_id
    public function criarPix($valor_cents, $mac, $ip, $plano_id, $descricao, $router_id = ROUTER_DEFAULT)
    {
        $valor_decimal = $valor_cents / 100;
        $idempotencyKey = md5($mac . '_' . $plano_id . '_' . floor(time() / 10));

        date_default_timezone_set('America/Fortaleza');
        $date_of_expiration = date('Y-m-d\TH:i:s.000-03:00', strtotime('+30 minutes'));

        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $dominio = $_SERVER['HTTP_HOST'] ?? 'seudominio.com.br';
        $notificationUrl = "{$protocolo}://{$dominio}/webhook";

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
                "router_id"   => $router_id  // INJETANDO A INFORMAÇÃO DA CIDADE AQUI!
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
