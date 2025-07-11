<?php
/**
 * Sincroniza o carrinho entre cliente e servidor
 */

// Iniciar sessão
session_start();

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Incluir conexão com banco de dados
require_once 'db_connect.php';

// Verificar se os dados foram recebidos via POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados do carrinho não recebidos'
    ]);
    exit;
}

// Validar e processar dados do carrinho
$cart_items = [];
$total_count = 0;

foreach ($data['cart'] as $item) {
    if (!isset($item['id']) && !isset($item['product_id'])) {
        continue; // Pular itens sem ID
    }
    
    $product_id = isset($item['product_id']) ? $item['product_id'] : $item['id'];
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    
    if ($quantity <= 0) {
        continue; // Pular itens com quantidade inválida
    }
    
    // Verificar se o produto existe no banco de dados
    $query = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, image_url FROM products WHERE id = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $cart_items[] = [
            'id' => (int)$row['id'],
            'product_id' => (int)$row['id'],
            'name' => $row['name'],
            'wholesale_price' => (float)$row['wholesale_price'],
            'retail_price' => (float)$row['retail_price'],
            'min_wholesale_quantity' => (int)$row['min_wholesale_quantity'],
            'quantity' => $quantity,
            'weight' => (float)$row['unit_weight'],
            'image_url' => $row['image_url'] ?: 'assets/images/no-image.png'
        ];
        
        $total_count += $quantity;
    }
}

// Atualizar a sessão
$_SESSION['cart'] = $cart_items;

// Retornar resposta de sucesso
echo json_encode([
    'success' => true,
    'message' => 'Carrinho sincronizado com sucesso',
    'count' => $total_count,
    'items' => count($cart_items)
]);
?> 