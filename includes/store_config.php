<?php
/**
 * Arquivo para disponibilizar configurações da loja para JavaScript
 * Usado para tornar configurações acessíveis no frontend
 */

// Incluir conexão com banco se não existir
if (!isset($conn)) {
    require_once 'db_connect.php';
}

// Obter configurações da loja se não foram carregadas
if (!isset($store)) {
    $query = "SELECT * FROM store_settings WHERE id = 1";
    $result = $conn->query($query);
    $store = $result->fetch_assoc();
}

// Criar configurações JavaScript-safe
$store_config = array(
    'enable_shipping' => isset($store['enable_shipping']) ? (bool)$store['enable_shipping'] : true,
    'enable_global_minimums' => isset($store['enable_global_minimums']) ? (bool)$store['enable_global_minimums'] : true,
    'shipping_control_text' => isset($store['shipping_control_text']) ? $store['shipping_control_text'] : 'Frete calculado automaticamente',
    'minimum_explanation_text' => isset($store['minimum_explanation_text']) ? $store['minimum_explanation_text'] : 'Vendemos somente no mínimo especificado',
    'shipping_rate' => isset($store['shipping_rate']) ? floatval($store['shipping_rate']) : 1500,
    'whatsapp_number' => isset($store['whatsapp_number']) ? preg_replace('/[^0-9]/', '', $store['whatsapp_number']) : '595991234567',
    'store_name' => isset($store['store_name']) ? $store['store_name'] : 'Productos Naturales Paraguay'
);

// Função para gerar JavaScript com configurações
function generateStoreConfigScript($store_config) {
    $json_config = json_encode($store_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    
    return "
    <script>
    // Configurações da loja disponíveis globalmente
    window.STORE_CONFIG = {$json_config};
    
    // Função helper para verificar se frete está ativo
    window.isShippingEnabled = function() {
        return window.STORE_CONFIG.enable_shipping === true;
    };
    
    // Função helper para verificar se mínimos estão ativos
    window.areMinimumQuantitiesEnabled = function() {
        return window.STORE_CONFIG.enable_global_minimums === true;
    };
    
    // Função helper para obter taxa de frete
    window.getShippingRate = function() {
        return window.STORE_CONFIG.shipping_rate || 1500;
    };
    
    // Função helper para obter número do WhatsApp
    window.getWhatsappNumber = function() {
        return window.STORE_CONFIG.whatsapp_number || '595991234567';
    };
    
    console.log('🏪 Configurações da loja carregadas:', window.STORE_CONFIG);
    </script>
    ";
}

// Se chamado diretamente, enviar como JSON para AJAX
if (basename($_SERVER['PHP_SELF']) === 'store_config.php') {
    header('Content-Type: application/json');
    echo json_encode($store_config);
    exit;
}
?> 