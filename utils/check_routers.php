<?php
// utils/check_routers.php - Verificar status dos roteadores

require_once __DIR__ . '/../config/config.php';

class VerificadorRoteadores
{
    /**
     * Verifica o status de um roteador fazendo ping na porta
     * @param string $host IP ou domínio do roteador
     * @param string $port Porta a verificar
     * @param int $timeout Tempo limite em segundos
     * @return bool true se está online, false se offline
     */
    public static function checarOnline($host, $port, $timeout = 2)
    {
        if (empty($host)) {
            return false; // Host vazio = offline
        }

        // Tenta conectar na porta especificada
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        
        return false;
    }

    /**
     * Retorna status de todos os roteadores
     * @return array ['nome_router' => ['host' => '', 'port' => '', 'online' => bool]]
     */
    public static function statusTodos()
    {
        require_once __DIR__ . '/../models/Roteador.php';
        $modeloRoteador = new Roteador();
        $routers = $modeloRoteador->obterTodos();

        $resultado = [];

        foreach ($routers as $config) {
            $nome = $config['nome_identificador'];
            $resultado[$nome] = [
                'host' => $config['host'],
                'port' => $config['port'],
                'online' => self::checarOnline($config['host'], $config['port'])
            ];
        }

        return $resultado;
    }

    /**
     * Retorna nome legível do roteador
     * @param string $nome Chave do roteador
     * @return string Nome com primeira letra maiúscula
     */
    public static function getNomeLegivel($nome)
    {
        $nomes = [
            'sobral' => 'Sobral',
            'matos' => 'Matos',
            'fortaleza' => 'Fortaleza'
        ];

        return $nomes[$nome] ?? ucfirst($nome);
    }
}
?>