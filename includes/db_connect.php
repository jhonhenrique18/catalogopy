<?php
/**
 * Arquivo de conexão - VERSÃO COMPATÍVEL COM CPANEL
 */

// Buffer de saída para evitar problemas de header
if (!ob_get_level()) {
    ob_start();
}

// Configurações de encoding ANTES de qualquer output
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Detectar ambiente - VERSÃO MELHORADA
function isProduction() {
    $production_indicators = [
        isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'graosfoz.com.br') !== false,
        isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.com.br') !== false,
        isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost',
        isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost'
    ];
    
    foreach ($production_indicators as $indicator) {
        if ($indicator) return true;
    }
    return false;
}

$is_production = isProduction();

// Configurações de banco baseadas no ambiente
if ($is_production) {
    // PRODUÇÃO - cPanel
    $host = 'localhost';
    $dbname = 'lollad10_catalogo2';
    $username = 'lollad10_jhonatan';
    $password = 'jhonatan2727A@';
    
    // Configurações para produção
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
} else {
    // DESENVOLVIMENTO - localhost
    $host = 'localhost';
    $dbname = 'catalogo_graos';
    $username = 'root';
    $password = '';
    
    // Configurações para desenvolvimento
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Conectar com configurações específicas para cPanel
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        if ($is_production) {
            error_log("MySQL Connection Error: " . $conn->connect_error);
            die("Erro interno do servidor. Contate o administrador.");
        } else {
            die("Erro de conexão: " . $conn->connect_error);
        }
    }
    
    // Configurações UTF-8 ESPECÍFICAS PARA CPANEL
    $conn->set_charset("utf8mb4");
    
    // Sequência de comandos para garantir UTF-8 no cPanel
    $utf8_commands = [
        "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        "SET CHARACTER SET utf8mb4",
        "SET character_set_client = utf8mb4",
        "SET character_set_results = utf8mb4", 
        "SET character_set_connection = utf8mb4",
        "SET character_set_database = utf8mb4",
        "SET character_set_server = utf8mb4",
        "SET collation_connection = utf8mb4_unicode_ci",
        "SET collation_database = utf8mb4_unicode_ci",
        "SET collation_server = utf8mb4_unicode_ci"
    ];
    
    foreach ($utf8_commands as $command) {
        $conn->query($command);
    }
    
} catch (Exception $e) {
    if ($is_production) {
        error_log("Database Error: " . $e->getMessage());
        die("Erro interno do servidor.");
    } else {
        die("Erro: " . $e->getMessage());
    }
}

// Timezone
date_default_timezone_set('America/Asuncion');
?>