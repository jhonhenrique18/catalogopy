<?php
/**
 * Processamento de atualização de itens no carrinho
 * 
 * Recebe requisições AJAX para atualizar quantidades de produtos no carrinho
 */

// Iniciar sessão
session_start();

// Incluir conexão com banco de dados
require_once 'db_connect.php';

// Incluir funções utilitárias
require_once 'functions.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se os dados necessários foram enviados
    if (isset($_POST['index']) && isset($_POST['quantity'])) {
        $index = (int) $_POST['index'];
        $quantity = (int) $_POST['quantity'];
        
        // Validar quantidade
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
        // Verificar se o índice é válido no carrinho
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && isset($_SESSION['cart'][$index])) {
            // Atualizar quantidade
            $_SESSION['cart'][$index]['quantity'] = $quantity;
            
            // Determinar o preço (mayorista ou minorista) com base na quantidade
            $item = $_SESSION['cart'][$index];
            $is_mayorista = $quantity >= $item['min_wholesale_quantity'];
            $price = $is_mayorista ? $item['wholesale_price'] : $item['retail_price'];
            $subtotal = $price * $quantity;
            
            // Recalcular totais
            $cart_subtotal = 0;
            $cart_weight = 0;
            foreach ($_SESSION['cart'] as $cart_item) {
                $cart_price = $cart_item['quantity'] >= $cart_item['min_wholesale_quantity'] ? 
                    $cart_item['wholesale_price'] : $cart_item['retail_price'];
                $cart_subtotal += $cart_price * $cart_item['quantity'];
                $cart_weight += $cart_item['weight'] * $cart_item['quantity'];
            }
            
            // Obter taxa de frete
            $query = "SELECT shipping_rate FROM store_settings WHERE id = 1";
            $result = $conn->query($query);
            $store = $result->fetch_assoc();
            $shipping_rate = isset($store['shipping_rate']) ? $store['shipping_rate'] : 1500;
            
            // Calcular frete e total
            $shipping = calculateShipping($cart_weight, $shipping_rate);
            $total = $cart_subtotal + $shipping;
            
            // Calcular total de itens no carrinho
            $cart_count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
            
            // Retornar resposta de sucesso
            echo json_encode([
                'success' => true,
                'message' => 'Carrito actualizado',
                'cart_count' => $cart_count,
                'subtotal' => $cart_subtotal,
                'subtotal_formatted' => 'G$ ' . number_format($cart_subtotal, 0, ',', '.'),
                'weight' => $cart_weight,
                'weight_formatted' => number_format($cart_weight, 2, ',', '.') . ' kg',
                'shipping' => $shipping,
                'shipping_formatted' => 'G$ ' . number_format($shipping, 0, ',', '.'),
                'total' => $total,
                'total_formatted' => 'G$ ' . number_format($total, 0, ',', '.'),
                'item' => [
                    'price' => $price,
                    'price_formatted' => 'G$ ' . number_format($price, 0, ',', '.'),
                    'subtotal' => $subtotal,
                    'subtotal_formatted' => 'G$ ' . number_format($subtotal, 0, ',', '.'),
                    'is_mayorista' => $is_mayorista,
                    'min_wholesale_quantity' => $item['min_wholesale_quantity']
                ]
            ]);
            exit;
        } else {
            // Índice inválido
            echo json_encode([
                'success' => false,
                'message' => 'Ítem no encontrado en el carrito'
            ]);
            exit;
        }
    }
}

// Se chegou aqui, houve um erro
echo json_encode([
    'success' => false,
    'message' => 'Error al actualizar el carrito'
]);
exit;
?>