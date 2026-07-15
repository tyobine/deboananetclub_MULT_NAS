<?php
// controllers/webhook.php

require_once __DIR__ . '/../services/pix.php';
require_once __DIR__ . '/../services/conexao.php';
require_once __DIR__ . '/../models/banco.php';

class Webhook
{

    private function log($mensagem)
    {
        file_put_contents(__DIR__ . '/../webhook_log.txt', date('Y-m-d H:i:s') . " - " . $mensagem . "\n", FILE_APPEND);
    }

    public function receberNotificacao()
    {
        try {
            date_default_timezone_set('America/Fortaleza');

            $json = file_get_contents('php://input');
            $headers = getallheaders();
            $this->log("RECEBEU WEBHOOK: " . str_replace(["\r", "\n"], "", $json));

            $signatureHeader = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
            $requestId = $headers['x-request-id'] ?? $headers['X-Request-Id'] ?? '';

            if (defined('MP_WEBHOOK_SECRET') && !empty($signatureHeader)) {
                preg_match('/ts=(\d+),v1=([a-f0-9]+)/', $signatureHeader, $matches);
                if (count($matches) === 3) {
                    $manifest = "id:$requestId;request-parts:;ts:{$matches[1]};";
                    $expectedSignature = hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET);
                    if (!hash_equals($expectedSignature, $matches[2])) {
                        $this->log("🚨 ALERTA: Assinatura inválida bloqueada.");
                        http_response_code(403);
                        die('Assinatura Inválida');
                    }
                }
            }

            $dados = json_decode($json, true);
            $paymentId = $dados['data']['id'] ?? ($_GET['id'] ?? null);

            if (function_exists('fastcgi_finish_request')) {
                echo "OK";
                session_write_close();
                fastcgi_finish_request();
            } else {
                ob_start();
                echo "OK";
                header("Connection: close");
                header("Content-Length: " . ob_get_length());
                http_response_code(200);
                ob_end_flush();
                flush();
            }

            if ($paymentId) {
                $pixService = new Pix();
                $conexaoService = new Conexao();
                $db = new Banco();

                $pagamento = $pixService->consultarStatus($paymentId);

                if (isset($pagamento['status']) && $pagamento['status'] == 'approved') {
                    $txid = $paymentId;

                    $transacao = $db->getRow("SELECT router_id, mac_address, plano_id FROM acessos_pix WHERE txid = ?", [$txid]);

                    if ($transacao) {
                        $mac = strtoupper($transacao['mac_address']);
                        $plano_id = $transacao['plano_id'];
                        $router_id = !empty($transacao['router_id']) ? $transacao['router_id'] : ROUTER_DEFAULT;

                        $stmt = $db->query("UPDATE acessos_pix SET status = 'processando' WHERE txid = ? AND status = 'pendente'", [$txid]);

                        if ($stmt->rowCount() > 0) {
                            $this->log("Iniciando liberação para MAC: $mac no roteador: $router_id");

                            // ============================================================
                            // BLOCO CRÍTICO: Qualquer falha (retorno false OU exception)
                            // deve acionar o estorno automático para proteger o cliente.
                            // ============================================================
                            try {
                                $sucesso = $conexaoService->processarLiberacao($txid, $mac, $plano_id, $router_id);

                                if ($sucesso) {
                                    // SÓ ATIVA SE A REQUISIÇÃO AO MIKROTIK TIVER SUCESSO
                                    $db->query("UPDATE acessos_pix SET status = 'ativo' WHERE txid = ?", [$txid]);
                                    $this->log("SUCESSO: Tempo acumulado/liberado no Roteador ($router_id).");
                                } else {
                                    // FALHA NA TORRE (retornou false): MARCA ERRO E ESTORNA
                                    $db->query("UPDATE acessos_pix SET status = 'erro_mikrotik' WHERE txid = ?", [$txid]);
                                    $this->log("FALHA: Roteador ($router_id) retornou erro. Iniciando Estorno automático...");
                                    $pixService->devolverValor($txid);
                                }
                            } catch (\Throwable $errMk) {
                                // FALHA NA TORRE (exception de rede/timeout): MARCA ERRO E ESTORNA
                                $db->query("UPDATE acessos_pix SET status = 'erro_mikrotik' WHERE txid = ?", [$txid]);
                                $this->log("FALHA (Exception): Roteador ($router_id) inacessível — " . $errMk->getMessage() . ". Iniciando Estorno automático...");
                                $pixService->devolverValor($txid);
                            }
                        } else {
                            $this->log("AVISO: Transação {$txid} ignorada. Já processada anteriormente.");
                        }
                    } else {
                        $this->log("ERRO FATAL: Webhook aprovou o pagamento {$txid}, mas ele não existe no nosso banco de dados!");
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->log("❌ ERRO FATAL (externo): " . $e->getMessage());
        }
    }
}
