<?php
// services/conexao.php

require_once __DIR__ . '/../models/banco.php';
require_once __DIR__ . '/../models/mikrotik.php';

class Conexao
{
    private $db;

    // Número máximo de tentativas antes de desistir e acionar o estorno
    const MAX_TENTATIVAS = 3;

    // Segundos de espera entre cada tentativa (evita sobrecarregar um roteador instável)
    const ESPERA_ENTRE_TENTATIVAS = 2;

    public function __construct()
    {
        $this->db = new Banco();
    }

    /**
     * Calcula o tempo, acumula se necessário e libera o MAC no MikroTik da cidade correspondente.
     *
     * Implementa retry automático (MAX_TENTATIVAS) para absorver instabilidades temporárias
     * de rede, evitando estornos desnecessários por falha pontual de comunicação.
     * O estorno só é acionado se TODAS as tentativas falharem.
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

        // ============================================================
        // RETRY: Tenta comunicar com o MikroTik até MAX_TENTATIVAS vezes.
        // Isso absorve instabilidades momentâneas de rede (ex: spike de
        // latência, reinicialização parcial do roteador) sem acionar o
        // estorno automático prematuramente.
        // ============================================================
        $tentativa = 0;
        $ultimoErro = '';

        while ($tentativa < self::MAX_TENTATIVAS) {
            $tentativa++;

            try {
                $mk = new Mikrotik($router_id);
                $liberou = $mk->liberarAcessoTempo($mac, $minutosTotais);

                if ($liberou) {
                    // Sucesso! Atualiza o banco e retorna true.
                    if ($tentativa > 1) {
                        $log = date('Y-m-d H:i:s') . " - ✅ RETRY SUCESSO na tentativa {$tentativa}/{" . self::MAX_TENTATIVAS . "} para MAC: $mac (router: $router_id)\n";
                        @file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
                    }
                    $this->db->query("UPDATE acessos_pix SET status = 'ativo', expira_em = ? WHERE txid = ?", [$expira_em, $txid]);
                    return true;
                }

                // Roteador respondeu mas recusou (ex: usuário já existe, erro de API)
                // Não adianta ficar tentando: retorna false imediatamente.
                $log = date('Y-m-d H:i:s') . " - ⚠️ ROTEADOR RECUSOU (não é timeout) na tentativa {$tentativa} para MAC: $mac (router: $router_id). Abortando retries.\n";
                @file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
                return false;

            } catch (\Throwable $e) {
                // Falha de rede/timeout — vale tentar de novo
                $ultimoErro = $e->getMessage();
                $log = date('Y-m-d H:i:s') . " - 🔄 RETRY {$tentativa}/" . self::MAX_TENTATIVAS . " | MAC: $mac | Router: $router_id | Erro: {$ultimoErro}\n";
                @file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);

                if ($tentativa < self::MAX_TENTATIVAS) {
                    sleep(self::ESPERA_ENTRE_TENTATIVAS);
                }
            }
        }

        // Todas as tentativas esgotadas — relança como Exception para que
        // o webhook.php capture e acione o estorno automático com log completo.
        throw new \RuntimeException(
            "Roteador {$router_id} inacessível após " . self::MAX_TENTATIVAS . " tentativas. Último erro: {$ultimoErro}"
        );
    }
}
