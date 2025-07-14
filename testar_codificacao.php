<?php
/**
 * TESTE DE CODIFICAÇÃO UTF-8 - PRODUTOS
 * Script para verificar problemas de codificação nos produtos
 */

// Incluir conexão com banco de dados
require_once 'includes/db_connect.php';

// Configurações de exibição
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Teste de Codificação UTF-8</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .problem { background: #ffcccc; padding: 10px; margin: 10px 0; border-left: 4px solid #ff0000; }
        .ok { background: #ccffcc; padding: 10px; margin: 10px 0; border-left: 4px solid #00ff00; }
        .info { background: #ccccff; padding: 10px; margin: 10px 0; border-left: 4px solid #0000ff; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>🔍 Teste de Codificação UTF-8 - Sistema Catalogopy</h1>";

// Testar configuração do banco
echo "<h2>1. Configuração do Banco de Dados</h2>";
$charset_queries = [
    "SELECT @@character_set_client as client",
    "SELECT @@character_set_connection as connection", 
    "SELECT @@character_set_results as results",
    "SELECT @@collation_connection as collation"
];

foreach ($charset_queries as $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $key = array_keys($row)[0];
        $value = $row[$key];
        
        if (strpos($value, 'utf8') !== false) {
            echo "<div class='ok'>✅ {$key}: {$value}</div>";
        } else {
            echo "<div class='problem'>❌ {$key}: {$value}</div>";
        }
    }
}

// Testar produtos com possíveis problemas de codificação
echo "<h2>2. Produtos com Possíveis Problemas de Codificação</h2>";

$products_query = "SELECT id, name, description FROM products WHERE 
                   name LIKE '%Ã%' OR 
                   description LIKE '%Ã%' OR
                   name LIKE '%â%' OR
                   description LIKE '%â%' OR
                   name LIKE '%ã%' OR
                   description LIKE '%ã%'
                   ORDER BY name";

$result = $conn->query($products_query);

if ($result && $result->num_rows > 0) {
    echo "<div class='problem'>❌ Encontrados produtos com possíveis problemas de codificação:</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['description'], 0, 100)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='ok'>✅ Nenhum produto encontrado com problemas óbvios de codificação</div>";
}

// Testar produtos em geral
echo "<h2>3. Amostra de Produtos (Primeiros 10)</h2>";
$sample_query = "SELECT id, name, description FROM products ORDER BY name LIMIT 10";
$sample_result = $conn->query($sample_query);

if ($sample_result && $sample_result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th></tr>";
    
    while ($row = $sample_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['description'] ?? '', 0, 100)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='info'>ℹ️ Nenhum produto encontrado no banco</div>";
}

// Testar configurações da loja
echo "<h2>4. Configurações da Loja</h2>";
$store_query = "SELECT store_name, store_description FROM store_settings WHERE id = 1";
$store_result = $conn->query($store_query);

if ($store_result && $store_result->num_rows > 0) {
    $store = $store_result->fetch_assoc();
    echo "<div class='info'>📊 Nome da Loja: " . htmlspecialchars($store['store_name']) . "</div>";
    echo "<div class='info'>📝 Descrição: " . htmlspecialchars($store['store_description'] ?? 'Não definida') . "</div>";
} else {
    echo "<div class='problem'>❌ Configurações da loja não encontradas</div>";
}

// Teste de caracteres especiais
echo "<h2>5. Teste de Caracteres Especiais</h2>";
$special_chars = [
    'á' => 'a com acento agudo',
    'é' => 'e com acento agudo', 
    'í' => 'i com acento agudo',
    'ó' => 'o com acento agudo',
    'ú' => 'u com acento agudo',
    'ã' => 'a com til',
    'õ' => 'o com til',
    'ç' => 'c com cedilha',
    'ñ' => 'n com til (espanhol)'
];

foreach ($special_chars as $char => $desc) {
    echo "<div class='info'>📝 {$char} - {$desc}</div>";
}

echo "<h2>6. Recomendações</h2>";
echo "<div class='info'>
    <h3>Para corrigir problemas de codificação:</h3>
    <ol>
        <li>Certificar que todos os arquivos estão salvos em UTF-8</li>
        <li>Verificar se o banco de dados está configurado como utf8mb4</li>
        <li>Confirmar que as consultas SQL usam charset correto</li>
        <li>Testar inserção de caracteres especiais</li>
    </ol>
</div>";

echo "</body></html>";

$conn->close();
?> 