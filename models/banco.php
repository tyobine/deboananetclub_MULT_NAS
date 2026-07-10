<?php
// models/banco.php

require_once __DIR__ . '/../config/config.php';

class Banco
{
    private $pdo;

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            // Em caso de falha no banco, regista no log do sistema e aborta para não expor erros no ecrã
            file_put_contents(__DIR__ . '/../webhook_log.txt', date('Y-m-d H:i:s') . " - FALHA CRÍTICA DB: " . $e->getMessage() . "\n", FILE_APPEND);
            die("Erro crítico: Falha de comunicação com o banco de dados.");
        }
    }

    // Método genérico para INSERTS, UPDATES e DELETES
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Pega uma única linha (ex: buscar a sessão de um MAC)
    public function getRow($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Pega várias linhas (ex: buscar as métricas e todos os planos)
    public function getAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
}
