<?php
// models/mikrotik.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Roteador.php';

class Mikrotik
{
    private $ip;
    private $user;
    private $pass;
    private $port;

    public function __construct($router_id = null)
    {
        $modeloRoteador = new Roteador();
        $padrao = $modeloRoteador->obterPadrao();
        
        // Se não for passado um roteador específico, busca no cookie do cliente ou usa o padrão
        if (!$router_id) {
            $router_id = $_COOKIE['router_id'] ?? ($padrao['nome_identificador'] ?? '');
        }

        $config = $modeloRoteador->obterPorIdentificador($router_id);
        
        // Fallback: se o roteador do cookie sumiu ou falhou, tenta o roteador padrão
        if (!$config && $padrao) {
            $config = $padrao;
        }

        if ($config) {
            $this->ip   = $config['host'];
            $this->user = $config['user'];
            $this->pass = $config['pass'];
            $this->port = $config['port'] ?? '80';
        } else {
            throw new Exception("Falha crítica: Nenhum roteador configurado ou encontrado no banco de dados.");
        }
    }

    private function requestREST($endpoint, $method = 'GET', $data = null)
    {
        // Flexibilidade de protocolo: aceita o protocolo vindo do banco ou deduz pelas portas seguras (incluindo 8080)
        if (strpos($this->ip, 'http://') === 0 || strpos($this->ip, 'https://') === 0) {
            $url = "{$this->ip}:{$this->port}/rest{$endpoint}";
        } else {
            $protocol = in_array($this->port, [443, 8443, 8080]) ? 'https' : 'http';
            $url = "{$protocol}://{$this->ip}:{$this->port}/rest{$endpoint}";
        }

        $ch = curl_init($url);
        $headers = ["Content-Type: application/json"];

        curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout essencial para não segurar processos
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'PUT' || $method === 'POST' || $method === 'PATCH') {
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
            @file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
            throw new Exception("Falha na requisição cURL: " . $curlError);
        }

        return ['code' => $httpCode, 'body' => json_decode($response, true)];
    }

    public function liberarAcessoTempo($mac, $minutos, $profile = 'default')
    {
        // 1. Derruba sessões presas (Ativas)
        $ativas = $this->requestREST('/ip/hotspot/active?mac-address=' . $mac);
        if ($ativas['code'] == 200 && !empty($ativas['body'])) {
            foreach ($ativas['body'] as $sessao) {
                if (isset($sessao['.id'])) {
                    $this->requestREST('/ip/hotspot/active/' . $sessao['.id'], 'DELETE');
                }
            }
        }

        // 2. Deleta usuário antigo se existir
        $usuarios = $this->requestREST('/ip/hotspot/user?name=' . $mac);
        if ($usuarios['code'] == 200 && !empty($usuarios['body'])) {
            foreach ($usuarios['body'] as $user) {
                if (isset($user['.id'])) {
                    $this->requestREST('/ip/hotspot/user/' . $user['.id'], 'DELETE');
                }
            }
        }

        // 3. Lógica de cálculo do tempo limite para o Hotspot
        $dias = floor($minutos / 1440);
        $horas = floor(($minutos % 1440) / 60);
        $restoMinutos = $minutos % 60;

        if ($dias > 0) {
            $limit_uptime = sprintf("%dd%02d:%02d:00", $dias, $horas, $restoMinutos);
        } else {
            $limit_uptime = sprintf("%02d:%02d:00", $horas, $restoMinutos);
        }

        // 4. Criação do novo usuário
        $dadosNovoUser = [
            'name'         => $mac,
            'password'     => $mac,
            'profile'      => $profile,
            'limit-uptime' => $limit_uptime,
            'comment'      => 'Liberado via Portal Multi-NAS - ' . date('d/m/Y H:i')
        ];

        $resposta = $this->requestREST('/ip/hotspot/user', 'PUT', $dadosNovoUser);

        // 5. Tratamento em caso de falha da API
        if ($resposta['code'] != 201 && $resposta['code'] != 200) {
            $log = date('Y-m-d H:i:s') . " - ERRO MK API: Não foi possível criar user $mac. Code: {$resposta['code']}. Motivo: " . json_encode($resposta['body']) . "\n";
            @file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
            return false; 
        }

        return true;
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
?>