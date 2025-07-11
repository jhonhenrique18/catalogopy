<?php
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/exchange_functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

$order_id = intval($_GET['id']);

// Buscar dados completos do pedido
$order_query = "SELECT o.*, 
                GROUP_CONCAT(
                    CONCAT(oi.quantity, 'kg ', p.name, ' - ', oi.unit_price, ' = ', oi.total_price)
                    SEPARATOR '|'
                ) as items_list
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE o.id = ? 
                GROUP BY o.id";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido não encontrado']);
    exit;
}

$order = $result->fetch_assoc();

// Buscar itens detalhados
$items_query = "SELECT oi.*, p.name as product_name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";

$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

$order['items'] = $items;

header('Content-Type: application/json');
echo json_encode($order);
?> 