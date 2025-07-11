<?php
/**
 * Retorna os dados do carrinho em formato JSON
 */

// Iniciar sessão
session_start();

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Incluir conexão com banco de dados
require_once 'db_connect.php';

// Incluir funções de câmbio
require_once 'exchange_functions.php';

// Verificar se o carrinho existe
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    echo json_encode([
        'success' => true,
        'cart' => [],
        'count' => 0,
        'message' => 'Carrinho vazio'
    ]);
    exit;
}

// Processar e corrigir dados do carrinho
$cart_items = [];
$total_count = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    // Verificar se é array associativo ou indexado
    $product_id = isset($item['product_id']) ? $item['product_id'] : (isset($item['id']) ? $item['id'] : null);
    
    if (!$product_id) {
        continue; // Pular itens inválidos
    }
    
    // Buscar dados atualizados do produto no banco de dados
    $query = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, 
              p.unit_weight, p.unit_type, p.unit_display_name, p.image_url,
              p.parent_product_id, p.variation_display,
              (SELECT name FROM products WHERE id = p.parent_product_id) as parent_name
              FROM products p
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Garantir que a quantidade seja válida
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        if ($quantity <= 0) $quantity = 1;
        
        // Detectar se é variação e preparar nome para exibição
        $is_variation = !empty($row['parent_product_id']);
        $display_name = $row['name'];
        $parent_name = $row['parent_name'] ?? null;
        $variation_display = $row['variation_display'] ?? null;
        
        // Se for variação, criar nome mais claro para o carrinho
        if ($is_variation && $parent_name && $variation_display) {
            $display_name = $parent_name . ' ' . $variation_display;
        }
        
        $cart_items[] = [
            'id' => (int)$row['id'],
            'product_id' => (int)$row['id'],
            'name' => $row['name'], // Nome original
            'display_name' => $display_name, // Nome para exibição
            'parent_product_id' => $row['parent_product_id'],
            'parent_name' => $parent_name,
            'variation_display' => $variation_display,
            'is_variation' => $is_variation,
            'wholesale_price' => (float)$row['wholesale_price'],
            'retail_price' => (float)$row['retail_price'],
            'wholesale_price_pyg' => convertBrlToPyg($row['wholesale_price']),
            'retail_price_pyg' => convertBrlToPyg($row['retail_price']),
            'min_wholesale_quantity' => (int)$row['min_wholesale_quantity'],
            'quantity' => $quantity,
            'weight' => (float)$row['unit_weight'],
            'unit_type' => $row['unit_type'] ?? 'kg',
            'unit_display_name' => $row['unit_display_name'] ?? 'kg',
            'image_url' => $row['image_url'] ?: 'assets/images/no-image.png'
        ];
        
        $total_count += 1; // Conta apenas produtos únicos
    }
}

// Atualizar a sessão com os dados corrigidos
$_SESSION['cart'] = $cart_items;

// Retornar resposta
echo json_encode([
    'success' => true,
    'cart' => $cart_items,
    'count' => $total_count,
    'message' => 'Carrinho carregado com sucesso'
]);
?> 