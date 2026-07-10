<?php
// utils/rede.php

class Rede
{
    public static function obterIpCliente()
    {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }
}
