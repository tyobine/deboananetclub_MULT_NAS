<?php
// models/mikrotik.php

require_once __DIR__ . '/../config/config.php';

class Mikrotik
{

    private $ip;
    private $user;
    private $pass;
    private $port;

    public function __construct($router_id = null)
    {
        // Multi-NAS: Pega do cookie ou usa o padrão
        if (!$router_id) {
            $router_id = $_COOKIE['router_id'] ?? ROUTER_DEFAULT;
        }

        if (!array_key_exists($router_id, ROUTERS)) {
            $router_id = ROUTER_DEFAULT;
        }

        $config = ROUTERS[$router_id];
        $this->ip   = $config['host'];
        $this->user = $config['user'];
        $this->pass = $config['pass'];
        // Garante que a porta seja usada, ou força a 80 (ou 443) como no seu original
        $this->port = $config['port'] ?? '80';
    }

    private function requestREST($endpoint, $method = 'GET', $data = null)
    {
        $url = "https://{$this->ip}:{$this->port}/rest{$endpoint}";

        $ch = curl_init($url);
        $headers = ["Content-Type: application/json"];

        curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Fundamental para não travar o PIX
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'PUT' || $method === 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode == 0) {
            $log = date('Y-m-d H:i:s') . " - FALHA CRÍTICA DE REDE: {$curlError} | Tentando acessar: {$url}\n";
            file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
        }

        return ['code' => $httpCode, 'body' => json_decode($response, true)];
    }

    public function liberarAcessoTempo($mac, $minutos, $profile = 'default')
    {
        // Derruba sessões presas (Ativas)
        $ativas = $this->requestREST('/ip/hotspot/active?mac-address=' . $mac);
        if ($ativas['code'] == 200 && !empty($ativas['body'])) {
            foreach ($ativas['body'] as $sessao) {
                if (isset($sessao['.id'])) {
                    $this->requestREST('/ip/hotspot/active/' . $sessao['.id'], 'DELETE');
                }
            }
        }

        // Deleta usuário antigo se existir
        $usuarios = $this->requestREST('/ip/hotspot/user?name=' . $mac);
        if ($usuarios['code'] == 200 && !empty($usuarios['body'])) {
            foreach ($usuarios['body'] as $user) {
                if (isset($user['.id'])) {
                    $this->requestREST('/ip/hotspot/user/' . $user['.id'], 'DELETE');
                }
            }
        }

        // LÓGICA DE OURO RECUPERADA DO SEU CÓDIGO (Trata minutos que ultrapassam 24 horas)
        $dias = floor($minutos / 1440);
        $horas = floor(($minutos % 1440) / 60);
        $restoMinutos = $minutos % 60;

        if ($dias > 0) {
            $limit_uptime = sprintf("%dd%02d:%02d:00", $dias, $horas, $restoMinutos);
        } else {
            $limit_uptime = sprintf("%02d:%02d:00", $horas, $restoMinutos);
        }

        $dadosNovoUser = [
            'name'         => $mac,
            'password'     => $mac,
            'profile'      => $profile,
            'limit-uptime' => $limit_uptime,
            'comment'      => 'Liberado via Portal Multi-NAS - ' . date('d/m/Y H:i')
        ];

        $resposta = $this->requestREST('/ip/hotspot/user', 'PUT', $dadosNovoUser);

        // Em caso de falha da API do MikroTik, guarda o log exato
        if ($resposta['code'] != 201 && $resposta['code'] != 200) {
            $log = date('Y-m-d H:i:s') . " - ERRO MK API: Não foi possível criar user $mac. Code: {$resposta['code']}. Motivo: " . json_encode($resposta['body']) . "\n";
            file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
        }

        return ($resposta['code'] == 201 || $resposta['code'] == 200);
    }

    public function contarUtilizadoresAtivos()
    {
        $resposta = $this->requestREST('/ip/hotspot/active', 'GET');
        if ($resposta['code'] == 200 && is_array($resposta['body'])) {
            return count($resposta['body']);
        }
        return 0;
    }
}
