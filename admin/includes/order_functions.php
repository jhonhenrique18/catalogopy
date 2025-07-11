<?php
/**
 * Funções relacionadas aos pedidos
 */

/**
 * Busca detalhes completos de um pedido
 */
function getOrderDetails($order_id, $conn) {
    // Buscar dados básicos do pedido
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        return false;
    }
    
    // Buscar itens do pedido
    $query = "SELECT oi.*, p.name as product_name, p.image_url as product_image
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?
              ORDER BY oi.id";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $order['items'] = [];
    while ($item = $result->fetch_assoc()) {
        $order['items'][] = $item;
    }
    
    return $order;
}

/**
 * Retorna o label do status do pedido
 */
function getStatusLabel($status) {
    $statuses = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',  
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}

/**
 * Retorna a classe CSS do badge do status
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'confirmed' => 'bg-info',
        'shipped' => 'bg-primary', 
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    
    return isset($classes[$status]) ? $classes[$status] : 'bg-secondary';
}

/**
 * Calcula totais do pedido
 */
function calculateOrderTotals($items) {
    $subtotal = 0;
    $total_weight = 0;
    
    foreach ($items as $item) {
        $subtotal += $item['total_price'];
        $total_weight += ($item['weight'] * $item['quantity']);
    }
    
    // Calcular frete
    $shipping = calculateShipping($total_weight);
    $total = $subtotal + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
        'total_weight' => $total_weight
    ];
}

/**
 * Busca todos os pedidos com filtros
 */
function getOrders($conn, $filters = []) {
    $where_conditions = [];
    $params = [];
    $types = "";
    
    // Aplicar filtros
    if (!empty($filters['search'])) {
        $where_conditions[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
        $search_term = "%" . $filters['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['date_start'])) {
        $where_conditions[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_start'];
        $types .= "s";
    }
    
    if (!empty($filters['date_end'])) {
        $where_conditions[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_end'];
        $types .= "s";
    }
    
    // Construir query
    $query = "SELECT * FROM orders";
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($order = $result->fetch_assoc()) {
        $orders[] = $order;
    }
    
    return $orders;
}

/**
 * Busca estatísticas dos pedidos
 */
function getOrderStats($conn) {
    $stats = [];
    
    // Total de pedidos
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $result->fetch_assoc()['total'];
    
    // Pedidos pendentes
    $result = $conn->query("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $result->fetch_assoc()['pending'];
    
    // Receita total
    $result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'");
    $stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;
    
    return $stats;
}

/**
 * Exclui um pedido e seus itens
 */
function deleteOrder($order_id, $conn) {
    $conn->begin_transaction();
    
    try {
        // Excluir itens do pedido
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Excluir logs do pedido
        $stmt = $conn->prepare("DELETE FROM order_logs WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Excluir pedido
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?> 