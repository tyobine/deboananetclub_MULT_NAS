<?php
// models/banco.php

require_once __DIR__ . '/../config/config.php';

class Banco {
    private $pdo;

    public function __construct() {
        try {
            // A mágica da consistência acontece aqui:
            // Forçamos o charset e o fuso horário (-03:00 Fortaleza) direto na inicialização do MySQL
            $opcoes = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-03:00'"
            ];

            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $opcoes
            );
        } catch (PDOException $e) {
            // Em produção, nunca exiba o erro real do banco para o usuário final
            die("Erro crítico de infraestrutura: Falha na comunicação com o banco de dados.");
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function getAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function getRow($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($tabela, $dados) {
        $chaves = array_keys($dados);
        $campos = implode(', ', $chaves);
        $valores = ':' . implode(', :', $chaves);

        $sql = "INSERT INTO {$tabela} ({$campos}) VALUES ({$valores})";
        $this->query($sql, $dados);
        return $this->pdo->lastInsertId();
    }

    public function update($tabela, $dados, $condicao, $paramsCondicao = []) {
        $set = [];
        foreach ($dados as $chave => $valor) {
            $set[] = "{$chave} = :{$chave}";
        }
        $setStr = implode(', ', $set);

        $sql = "UPDATE {$tabela} SET {$setStr} WHERE {$condicao}";
        $this->query($sql, array_merge($dados, $paramsCondicao));
    }
    
    // Suporte a transações seguras (essencial para pagamentos)
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
?>