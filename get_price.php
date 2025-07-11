<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Verificar se temos um ID de produto e tipo de preço
if (isset($_GET['id']) && isset($_GET['type'])) {
    $product_id = (int) $_GET['id'];
    $price_type = $_GET['type'];
    
    // Determinar qual coluna buscar
    $column = ($price_type === 'retail') ? 'retail_price' : 'wholesale_price';
    
    // Consultar o preço no banco de dados
    $query = "SELECT $column FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Retornar o preço
        echo json_encode([
            'success' => true,
            'price' => floatval($row[$column])
        ]);
        exit;
    }
}

// Se chegou aqui, houve um erro
echo json_encode([
    'success' => false,
    'message' => 'Não foi possível obter o preço'
]);
exit;
?>