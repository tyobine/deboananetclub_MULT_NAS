<?php
// script temporário: migrate_db.php
// Acesse este arquivo pelo navegador para criar a tabela de roteadores. Ex: http://seusite.com/migrate_db.php

require_once 'config/config.php';
require_once 'models/banco.php';

try {
    $db = new Banco();
    
    // 1. Criar a tabela crm_roteadores
    $sql = "CREATE TABLE IF NOT EXISTS crm_roteadores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_identificador VARCHAR(100) NOT NULL UNIQUE,
        host VARCHAR(255) NOT NULL,
        user VARCHAR(100) NOT NULL,
        pass VARCHAR(255) NOT NULL,
        port INT DEFAULT 8080,
        hotspot_ip VARCHAR(50) NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->query($sql);
    
    // 2. Inserir dados padrão baseados no config.php antigo
    $roteadores = [
        'sobral' => [
            'host'       => 'api.deboananet.club',
            'user'       => 'admin',
            'pass'       => 'xtz900af',
            'port'       => 8080,
            'hotspot_ip' => '10.50.0.1',
            'is_default' => 1
        ],
        'fortaleza' => [
            'host'       => '189.45.78.164',
            'user'       => 'mulato',
            'pass'       => 'vtgd65aoty',
            'port'       => 8080,
            'hotspot_ip' => '10.50.0.1',
            'is_default' => 0
        ]
    ];
    
    foreach ($roteadores as $id => $r) {
        $existe = $db->getRow("SELECT id FROM crm_roteadores WHERE nome_identificador = ?", [$id]);
        if (!$existe) {
            $db->query("INSERT INTO crm_roteadores (nome_identificador, host, user, pass, port, hotspot_ip, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                $id, $r['host'], $r['user'], $r['pass'], $r['port'], $r['hotspot_ip'], $r['is_default']
            ]);
        }
    }
    
    // 3. Deletar a tabela crm_locais, pois ela não será mais usada.
    $db->query("DROP TABLE IF EXISTS crm_locais");
    
    echo "<h1>Banco de dados atualizado com sucesso!</h1>";
    echo "<p>A tabela <b>crm_roteadores</b> foi criada e os dados iniciais foram inseridos.</p>";
    echo "<p>Você já pode apagar este arquivo (migrate_db.php) por segurança.</p>";
    
} catch (Exception $e) {
    echo "Erro ao atualizar banco: " . $e->getMessage();
}
