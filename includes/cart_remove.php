<?php
/**
 * Processamento de remoção de itens do carrinho
 * 
 * Recebe requisições AJAX para remover produtos do carrinho
 */

// Iniciar sessão
session_start();

// Incluir conexão com banco de dados
require_once 'db_connect.php';

// Incluir funções utilitárias
require_once 'functions.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se o índice foi enviado
    if (isset($_POST['index'])) {
        $index = (int) $_POST['index'];
        
        // Verificar se o índice é válido no carrinho
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && isset($_SESSION['cart'][$index])) {
            // Remover o item do carrinho
            array_splice($_SESSION['cart'], $index, 1);
            
            // Verificar se o carrinho está vazio
            $cart_empty = count($_SESSION['cart']) === 0;
            
            if (!$cart_empty) {
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
                    'message' => 'Producto eliminado del carrito',
                    'cart_empty' => false,
                    'cart_count' => $cart_count,
                    'subtotal' => $cart_subtotal,
                    'subtotal_formatted' => 'G$ ' . number_format($cart_subtotal, 0, ',', '.'),
                    'weight' => $cart_weight,
                    'weight_formatted' => number_format($cart_weight, 2, ',', '.') . ' kg',
                    'shipping' => $shipping,
                    'shipping_formatted' => 'G$ ' . number_format($shipping, 0, ',', '.'),
                    'total' => $total,
                    'total_formatted' => 'G$ ' . number_format($total, 0, ',', '.')
                ]);
            } else {
                // Carrinho vazio após remoção
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto eliminado del carrito',
                    'cart_empty' => true,
                    'cart_count' => 0
                ]);
            }
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
    'message' => 'Error al eliminar el producto del carrito'
]);
exit;
?>