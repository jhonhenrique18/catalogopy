<?php
session_start();

// Contar produtos únicos no carrinho
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Retornar JSON
header('Content-Type: application/json');
echo json_encode([
    'count' => $cart_count,
    'success' => true
]);
?> 