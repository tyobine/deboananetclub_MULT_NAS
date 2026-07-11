<?php
// models/Roteador.php

require_once __DIR__ . '/banco.php';

class Roteador
{
    private $db;

    public function __construct()
    {
        $this->db = new Banco();
    }

    public function obterTodos()
    {
        return $this->db->getAll("SELECT * FROM crm_roteadores ORDER BY nome_identificador ASC");
    }

    public function obterPorId($id)
    {
        return $this->db->getRow("SELECT * FROM crm_roteadores WHERE id = ?", [$id]);
    }

    public function obterPorIdentificador($identificador)
    {
        return $this->db->getRow("SELECT * FROM crm_roteadores WHERE nome_identificador = ?", [$identificador]);
    }

    public function obterPadrao()
    {
        $padrao = $this->db->getRow("SELECT * FROM crm_roteadores WHERE is_default = 1 LIMIT 1");
        if (!$padrao) {
            // Se nenhum for padrão, pega o primeiro que achar
            $padrao = $this->db->getRow("SELECT * FROM crm_roteadores LIMIT 1");
        }
        return $padrao;
    }
}
?>
