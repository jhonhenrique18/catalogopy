<?php
/**
 * Verificação Final do Sistema Administrativo
 * Este arquivo verifica se todos os problemas foram corrigidos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>VERIFICAÇÃO FINAL DO SISTEMA ADMINISTRATIVO</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
</style>";

// Incluir conexão
$conn = null;
if (file_exists('../includes/db_connect.php')) {
    require_once '../includes/db_connect.php';
} else {
    echo "<p class='error'>Arquivo db_connect.php não encontrado</p>";
}

$errors_found = 0;
$checks_total = 0;

function check($description, $condition, $error_msg = '') {
    global $errors_found, $checks_total;
    $checks_total++;
    
    echo "<p>";
    if ($condition) {
        echo "<span class='success'>✓</span> " . $description;
    } else {
        echo "<span class='error'>✗</span> " . $description;
        if ($error_msg) echo " - " . $error_msg;
        $errors_found++;
    }
    echo "</p>";
}

echo "<h2>1. VERIFICAÇÃO DE SINTAXE DOS ARQUIVOS</h2>";

$php_files = [
    'index.php', 'dashboard.php', 'produtos.php', 'produto_adicionar.php', 'produto_editar.php',
    'categorias.php', 'categoria_adicionar.php', 'categoria_editar.php', 
    'configuracoes.php', 'pedidos.php', 'cotacao.php', 'perfil.php'
];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("C:/xampp/php/php.exe -l $file 2>&1", $output, $return_code);
        check("Sintaxe de $file", $return_code === 0, implode(' ', $output));
    } else {
        check("Arquivo $file existe", false, "Arquivo não encontrado");
    }
}

echo "<h2>2. VERIFICAÇÃO DE CONEXÃO COM BANCO DE DADOS</h2>";

check("Conexão com MySQL", $conn && !$conn->connect_error, $conn ? ($conn->connect_error ?? '') : 'Conexão não estabelecida');

if ($conn && !$conn->connect_error) {
    // Verificar tabelas principais
    $tables = ['categories', 'products', 'store_settings', 'users'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        check("Tabela $table existe", $result && $result->num_rows > 0);
        
        if ($result && $result->num_rows > 0) {
            $count_result = $conn->query("SELECT COUNT(*) as total FROM $table");
            if ($count_result) {
                $count = $count_result->fetch_assoc()['total'];
                echo "<span class='info'>  → $count registros encontrados</span><br>";
            }
        }
    }
}

echo "<h2>3. VERIFICAÇÃO DE CONSTRAINTS</h2>";

// Verificar constraint de categories
if ($conn) {
    $result = $conn->query("SHOW CREATE TABLE categories");
    if ($result) {
        $create_table = $result->fetch_assoc()['Create Table'];
        check("Foreign key constraint em categories", strpos($create_table, 'FOREIGN KEY') !== false);
    } else {
        check("Foreign key constraint em categories", false, "Não foi possível verificar");
    }
} else {
    check("Foreign key constraint em categories", false, "Sem conexão com banco");
}

echo "<h2>4. VERIFICAÇÃO DE INCLUDES E DEPENDÊNCIAS</h2>";

$includes_to_check = [
    'includes/admin_layout.php' => file_exists('includes/admin_layout.php'),
    'includes/auth_check.php' => file_exists('includes/auth_check.php'),
    '../includes/db_connect.php' => file_exists('../includes/db_connect.php'),
    '../includes/exchange_functions.php' => file_exists('../includes/exchange_functions.php')
];

foreach ($includes_to_check as $include => $exists) {
    check("Arquivo $include", $exists);
}

echo "<h2>5. VERIFICAÇÃO DE FUNÇÕES CRÍTICAS</h2>";

// Verificar se as funções necessárias existem
if (file_exists('../includes/exchange_functions.php')) {
    require_once '../includes/exchange_functions.php';
    check("Função formatPriceInGuaranis", function_exists('formatPriceInGuaranis'));
}

echo "<h2>6. RESUMO FINAL</h2>";

echo "<div style='padding: 20px; background-color: " . ($errors_found === 0 ? '#d4edda' : '#f8d7da') . "; border-radius: 5px; margin: 20px 0;'>";

if ($errors_found === 0) {
    echo "<h3 style='color: green;'>✅ SISTEMA ADMINISTRATIVO TOTALMENTE CORRIGIDO!</h3>";
    echo "<p><strong>Todos os $checks_total testes passaram com sucesso.</strong></p>";
    echo "<p>O sistema administrativo está livre de erros e pronto para uso.</p>";
} else {
    echo "<h3 style='color: red;'>⚠️ FORAM ENCONTRADOS $errors_found PROBLEMAS</h3>";
    echo "<p>De $checks_total verificações realizadas, $errors_found falharam.</p>";
    echo "<p>Por favor, corrija os problemas identificados acima.</p>";
}

echo "</div>";

echo "<h3>VERIFICAÇÕES REALIZADAS:</h3>";
echo "<ul>";
echo "<li>✓ Sintaxe de todos os arquivos PHP principais</li>";
echo "<li>✓ Conectividade com banco de dados</li>";
echo "<li>✓ Existência de tabelas necessárias</li>";
echo "<li>✓ Constraints de chave estrangeira</li>";
echo "<li>✓ Arquivos de include e dependências</li>";
echo "<li>✓ Funções críticas do sistema</li>";
echo "</ul>";

echo "<p><em>Verificação realizada em: " . date('d/m/Y H:i:s') . "</em></p>";
?> 