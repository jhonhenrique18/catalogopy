<?php
/**
 * SISTEMA DE WEBHOOK PARA AUTOMAÇÃO DE PEDIDOS
 * Integração completa com Make.com e outras plataformas
 * Desenvolvido para o Catalogopy
 */

/**
 * Enviar webhook quando um pedido é criado
 * @param int $order_id ID do pedido
 * @param mysqli $conn Conexão com o banco
 * @return bool Sucesso do envio
 */
function sendOrderWebhook($order_id, $conn) {
    try {
        // Buscar configurações de webhook
        $webhook_settings = getWebhookSettings($conn);
        
        if (!$webhook_settings['enabled'] || empty($webhook_settings['webhook_url'])) {
            return true; // Webhook desabilitado, não é erro
        }
        
        // Buscar dados completos do pedido
        $order_data = getOrderDataForWebhook($order_id, $conn);
        
        if (!$order_data) {
            throw new Exception("Pedido não encontrado: {$order_id}");
        }
        
        // Preparar payload do webhook
        $webhook_payload = prepareWebhookPayload($order_data, $webhook_settings);
        
        // Enviar webhook
        $response = sendWebhookRequest($webhook_settings['webhook_url'], $webhook_payload, $webhook_settings);
        
        // Registrar log
        logWebhookAttempt($order_id, $webhook_payload, $response, $conn);
        
        return $response['success'];
        
    } catch (Exception $e) {
        error_log("Erro no webhook para pedido {$order_id}: " . $e->getMessage());
        logWebhookError($order_id, $e->getMessage(), $conn);
        return false;
    }
}

/**
 * Obter configurações de webhook
 */
function getWebhookSettings($conn) {
    $query = "SELECT * FROM webhook_settings WHERE id = 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Configurações padrão
    return [
        'enabled' => false,
        'webhook_url' => '',
        'secret_key' => '',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 5
    ];
}

/**
 * Buscar dados completos do pedido para webhook
 */
function getOrderDataForWebhook($order_id, $conn) {
    // Buscar dados do pedido
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $order = $result->fetch_assoc();
    
    // Buscar itens do pedido
    $items_query = "SELECT oi.*, p.name as product_name, p.image_url 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $order['items'] = [];
    while ($item = $items_result->fetch_assoc()) {
        $order['items'][] = $item;
    }
    
    return $order;
}

/**
 * Preparar payload do webhook com mensagem do WhatsApp
 */
function prepareWebhookPayload($order_data, $webhook_settings) {
    // Preparar dados do pedido para a mensagem do WhatsApp
    $order_for_whatsapp = [
        'customer_name' => $order_data['customer_name'],
        'customer_phone' => $order_data['customer_phone'],
        'customer_email' => $order_data['customer_email'],
        'customer_address' => $order_data['customer_address'],
        'items' => [],
        'subtotal' => $order_data['subtotal'],
        'total_weight' => $order_data['total_weight'],
        'shipping' => $order_data['shipping'],
        'total' => $order_data['total'],
        'notes' => $order_data['customer_notes']
    ];
    
    // Preparar itens para a mensagem
    foreach ($order_data['items'] as $item) {
        $order_for_whatsapp['items'][] = [
            'name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'price' => $item['unit_price'],
            'subtotal' => $item['total_price'],
            'has_price' => $item['unit_price'] > 0
        ];
    }
    
    // Gerar mensagem do WhatsApp usando a função existente
    require_once 'functions.php';
    $whatsapp_message = generateWhatsAppMessage($order_for_whatsapp);
    
    // Preparar payload completo
    $payload = [
        'event' => 'new_order',
        'timestamp' => date('c'),
        'order' => [
            'id' => $order_data['id'],
            'number' => $order_data['order_number'],
            'status' => $order_data['status'],
            'created_at' => $order_data['created_at'],
            'updated_at' => $order_data['updated_at'],
            'customer' => [
                'name' => $order_data['customer_name'],
                'email' => $order_data['customer_email'],
                'phone' => $order_data['customer_phone'],
                'address' => $order_data['customer_address'],
                'city' => $order_data['customer_city'],
                'reference' => $order_data['customer_reference'],
                'notes' => $order_data['customer_notes']
            ],
            'items' => $order_data['items'],
            'totals' => [
                'subtotal' => $order_data['subtotal'],
                'shipping' => $order_data['shipping'],
                'total' => $order_data['total'],
                'total_weight' => $order_data['total_weight']
            ],
            'whatsapp_message' => $whatsapp_message, // ⚡ MENSAGEM PRONTA!
            'whatsapp_phone' => $order_data['customer_phone']
        ]
    ];
    
    return $payload;
}

/**
 * Gerar mensagem do WhatsApp baseada na função existente
 */
function generateWhatsAppMessage($order) {
    $message = "*NUEVO PEDIDO*\n\n";
    
    // Informações do cliente
    $message .= "*Cliente:* " . $order['customer_name'] . "\n";
    $message .= "*Teléfono:* " . $order['customer_phone'] . "\n";
    $message .= "*Email:* " . $order['customer_email'] . "\n";
    $message .= "*Dirección:* " . $order['customer_address'] . "\n\n";
    
    // Separar produtos com e sem preço
    $products_with_price = [];
    $products_to_quote = [];
    
    foreach ($order['items'] as $item) {
        $has_price = isset($item['has_price']) ? $item['has_price'] : ($item['price'] > 0);
        if ($has_price) {
            $products_with_price[] = $item;
        } else {
            $products_to_quote[] = $item;
        }
    }
    
    // Produtos com preço definido
    if (!empty($products_with_price)) {
        $message .= "*PRODUCTOS CON PRECIO:*\n";
        foreach ($products_with_price as $index => $item) {
            $message .= ($index + 1) . ". " . $item['quantity'] . "kg " . $item['name'] . " - " . 
                        formatGuarani($item['price']) . "/kg = " . formatGuarani($item['subtotal']) . "\n";
        }
        $message .= "\n";
    }
    
    // Produtos que precisam de cotação
    if (!empty($products_to_quote)) {
        $message .= "*PRODUCTOS A COTIZAR:*\n";
        foreach ($products_to_quote as $index => $item) {
            $message .= ($index + 1) . ". " . $item['quantity'] . "kg " . $item['name'] . " - *PRECIO A CONSULTAR*\n";
        }
        $message .= "\n";
    }
    
    // Totais
    if (!empty($products_with_price)) {
        $message .= "*Subtotal productos con precio:* " . formatGuarani($order['subtotal']) . "\n";
        $message .= "*Peso total:* " . number_format($order['total_weight'], 2, ',', '.') . " kg\n";
        $message .= "*Flete:* " . formatGuarani($order['shipping']) . "\n";
        
        if (!empty($products_to_quote)) {
            $message .= "*TOTAL (parcial):* " . formatGuarani($order['total']) . " *+ cotización*\n\n";
            $message .= "⚠️ *SOLICITUD:* Por favor cotizar los productos marcados y enviar precio total final.\n\n";
        } else {
            $message .= "*TOTAL:* " . formatGuarani($order['total']) . "\n\n";
        }
    } else {
        // Solo productos para cotizar
        $message .= "*Peso total:* " . number_format($order['total_weight'], 2, ',', '.') . " kg\n";
        $message .= "*Flete estimado:* " . formatGuarani($order['shipping']) . "\n\n";
        $message .= "⚠️ *SOLICITUD:* Todos los productos requieren cotización. Por favor enviar precio total.\n\n";
    }
    
    if (!empty($order['notes'])) {
        $message .= "*Observaciones:*\n" . $order['notes'] . "\n\n";
    }
    
    return $message;
}

/**
 * Enviar requisição HTTP para webhook
 */
function sendWebhookRequest($url, $payload, $settings) {
    $ch = curl_init();
    
    // Configurar headers
    $headers = [
        'Content-Type: application/json',
        'User-Agent: Catalogopy-Webhook/1.0'
    ];
    
    // Adicionar autenticação se configurada
    if (!empty($settings['secret_key'])) {
        $headers[] = 'X-Webhook-Secret: ' . $settings['secret_key'];
    }
    
    // Configurar cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $settings['timeout'] ?? 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // Para desenvolvimento
        CURLOPT_SSL_VERIFYHOST => false  // Para desenvolvimento
    ]);
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'success' => ($http_code >= 200 && $http_code < 300),
        'http_code' => $http_code,
        'response' => $response_body,
        'error' => $error
    ];
}

/**
 * Registrar tentativa de webhook
 */
function logWebhookAttempt($order_id, $payload, $response, $conn) {
    $query = "INSERT INTO webhook_logs (order_id, payload, response_code, response_body, success, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $payload_json = json_encode($payload);
    $success = $response['success'] ? 1 : 0;
    
    $stmt->bind_param("isisi", 
        $order_id, 
        $payload_json, 
        $response['http_code'], 
        $response['response'], 
        $success
    );
    
    $stmt->execute();
}

/**
 * Registrar erro de webhook
 */
function logWebhookError($order_id, $error_message, $conn) {
    $query = "INSERT INTO webhook_logs (order_id, error_message, success, created_at) 
              VALUES (?, ?, 0, NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $order_id, $error_message);
    $stmt->execute();
}

/**
 * Tentar reenviar webhook falho
 */
function retryFailedWebhook($order_id, $conn) {
    return sendOrderWebhook($order_id, $conn);
}

/**
 * Obter logs de webhook para um pedido
 */
function getWebhookLogs($order_id, $conn) {
    $query = "SELECT * FROM webhook_logs WHERE order_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($log = $result->fetch_assoc()) {
        $logs[] = $log;
    }
    
    return $logs;
}

/**
 * Testar configuração de webhook
 */
function testWebhookConnection($webhook_url, $secret_key = '') {
    $test_payload = [
        'event' => 'test_connection',
        'timestamp' => date('c'),
        'message' => 'Teste de conexão do webhook do Catalogopy'
    ];
    
    $settings = [
        'secret_key' => $secret_key,
        'timeout' => 10
    ];
    
    return sendWebhookRequest($webhook_url, $test_payload, $settings);
}

/**
 * Criar tabelas necessárias para webhook
 */
function createWebhookTables($conn) {
    $sql_settings = "
        CREATE TABLE IF NOT EXISTS webhook_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            enabled BOOLEAN DEFAULT FALSE,
            webhook_url VARCHAR(500) DEFAULT '',
            secret_key VARCHAR(255) DEFAULT '',
            timeout INT DEFAULT 30,
            retry_attempts INT DEFAULT 3,
            retry_delay INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $sql_logs = "
        CREATE TABLE IF NOT EXISTS webhook_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            payload TEXT,
            response_code INT,
            response_body TEXT,
            error_message TEXT,
            success BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_created_at (created_at)
        )
    ";
    
    $conn->query($sql_settings);
    $conn->query($sql_logs);
    
    // Inserir configuração padrão se não existir
    $conn->query("INSERT IGNORE INTO webhook_settings (id, enabled) VALUES (1, FALSE)");
}

?> 