<?php
/**
 * Funções para gerenciamento de pedidos
 */

/**
 * Gera um número único de pedido
 * @return string Número do pedido (formato: ORD-YYYYMMDD-XXXX)
 */
function generateOrderNumber() {
    $date = date('Ymd');
    $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return "ORD-{$date}-{$random}";
}

/**
 * Salva um pedido no banco de dados
 * @param array $orderData Dados do pedido
 * @param mysqli $conn Conexão com o banco
 * @return int|false ID do pedido criado ou false em caso de erro
 */
function saveOrder($orderData, $conn) {
    try {
        // Iniciar transação
        $conn->begin_transaction();
        
        // Gerar número do pedido
        $orderNumber = generateOrderNumber();
        
        // Inserir pedido principal
        $stmt = $conn->prepare("
            INSERT INTO orders (
                order_number, customer_name, customer_email, customer_phone, 
                customer_address, customer_city, customer_reference, customer_notes,
                subtotal, total_weight, shipping, total, whatsapp_sent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $whatsapp_sent = 1; // Assume que vai para o WhatsApp por padrão
        
        $stmt->bind_param("ssssssssddddi",
            $orderNumber,
            $orderData['customer_name'],
            $orderData['customer_email'],
            $orderData['customer_phone'],
            $orderData['customer_address'],
            $orderData['customer_city'],
            $orderData['customer_reference'],
            $orderData['customer_notes'],
            $orderData['subtotal'],
            $orderData['total_weight'],
            $orderData['shipping'],
            $orderData['total'],
            $whatsapp_sent
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao inserir pedido: " . $stmt->error);
        }
        
        $orderId = $conn->insert_id;
        
        // Inserir itens do pedido
        $stmt_items = $conn->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, quantity, 
                unit_price, total_price, is_wholesale
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($orderData['items'] as $item) {
            $isWholesale = $item['quantity'] >= ($item['min_wholesale_quantity'] ?? 10) ? 1 : 0;
            
            $stmt_items->bind_param("iisiddi",
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $item['subtotal'],
                $isWholesale
            );
            
            if (!$stmt_items->execute()) {
                throw new Exception("Erro ao inserir item do pedido: " . $stmt_items->error);
            }
        }
        
        // Confirmar transação
        $conn->commit();
        
        // ⚡ INTEGRAÇÃO WEBHOOK - Enviar automação após pedido confirmado
        try {
            require_once 'webhook_functions.php';
            sendOrderWebhook($orderId, $conn);
        } catch (Exception $webhook_error) {
            // Log do erro mas não falha o pedido
            error_log("Erro ao enviar webhook para pedido {$orderId}: " . $webhook_error->getMessage());
        }
        
        return $orderId;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        error_log("Erro ao salvar pedido: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca pedidos com filtros opcionais
 * @param mysqli $conn Conexão com o banco
 * @param array $filters Filtros para a busca
 * @param int $limit Limite de resultados
 * @param int $offset Offset para paginação
 * @return array Lista de pedidos
 */
function getOrders($conn, $filters = [], $limit = 50, $offset = 0) {
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Filtro por status
    if (!empty($filters['status'])) {
        $where_conditions[] = "o.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    // Filtro por data inicial
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    // Filtro por data final
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    // Filtro por busca (nome ou telefone)
    if (!empty($filters['search'])) {
        $where_conditions[] = "(o.customer_name LIKE ? OR o.customer_phone LIKE ? OR o.order_number LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    // Construir query
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $query = "
        SELECT 
            o.*,
            COUNT(oi.id) as item_count,
            SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        {$where_clause}
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Busca detalhes de um pedido específico
 * @param int $orderId ID do pedido
 * @param mysqli $conn Conexão com o banco
 * @return array|null Dados do pedido ou null se não encontrado
 */
function getOrderDetails($orderId, $conn) {
    // Buscar dados do pedido
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $order = $result->fetch_assoc();
    
    // Buscar itens do pedido
    $stmt_items = $conn->prepare("
        SELECT oi.*, p.image_url 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt_items->bind_param("i", $orderId);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    
    $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
    
    return $order;
}

/**
 * Atualiza o status de um pedido
 * @param int $orderId ID do pedido
 * @param string $status Novo status
 * @param string $adminUser Usuário que fez a alteração
 * @param mysqli $conn Conexão com o banco
 * @return bool Sucesso da operação
 */
function updateOrderStatus($orderId, $status, $adminUser, $conn) {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = ?, contacted_by = ?, contacted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $status, $adminUser, $orderId);
    
    return $stmt->execute();
}

/**
 * Adiciona observações administrativas a um pedido
 * @param int $orderId ID do pedido
 * @param string $notes Observações
 * @param mysqli $conn Conexão com o banco
 * @return bool Sucesso da operação
 */
function addOrderNotes($orderId, $notes, $conn) {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $notes, $orderId);
    
    return $stmt->execute();
}

/**
 * Obtém estatísticas dos pedidos
 * @param mysqli $conn Conexão com o banco
 * @param string $period Período (today, week, month, year)
 * @return array Estatísticas
 */
function getOrderStats($conn, $period = 'month') {
    $date_condition = "";
    
    switch ($period) {
        case 'today':
            $date_condition = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            break;
        default:
            $date_condition = "1=1";
    }
    
    $query = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(total) as total_revenue,
            AVG(total) as average_order_value,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'contatado' THEN 1 ELSE 0 END) as contacted_orders,
            SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as confirmed_orders,
            SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelled_orders
        FROM orders 
        WHERE {$date_condition}
    ";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

/**
 * Obtém status traduzidos para português
 * @param string $status Status em inglês
 * @return string Status em português
 */
function getStatusLabel($status) {
    $labels = [
        'pendente' => 'Pendente',
        'contatado' => 'Contatado',
        'confirmado' => 'Confirmado',
        'enviado' => 'Enviado',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado'
    ];
    
    return $labels[$status] ?? $status;
}

/**
 * Obtém classe CSS para badge de status
 * @param string $status Status do pedido
 * @return string Classe CSS
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pendente' => 'bg-warning text-dark',
        'contatado' => 'bg-info',
        'confirmado' => 'bg-primary',
        'enviado' => 'bg-secondary',
        'entregue' => 'bg-success',
        'cancelado' => 'bg-danger'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}
?> 