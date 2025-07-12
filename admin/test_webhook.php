<?php
/**
 * SCRIPT DE TESTE PARA SISTEMA DE WEBHOOK
 * Use este script para testar se o sistema está funcionando corretamente
 */

require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/webhook_functions.php';

echo "<h2>🧪 Teste do Sistema de Webhook</h2>";

// Teste 1: Verificar se as tabelas existem
echo "<h3>1. Verificando tabelas do banco...</h3>";
$tables_check = [
    'webhook_settings' => "SELECT COUNT(*) as count FROM webhook_settings",
    'webhook_logs' => "SELECT COUNT(*) as count FROM webhook_logs"
];

foreach ($tables_check as $table => $query) {
    $result = $conn->query($query);
    if ($result) {
        echo "✅ Tabela '{$table}' existe<br>";
    } else {
        echo "❌ Erro na tabela '{$table}': " . $conn->error . "<br>";
    }
}

// Teste 2: Verificar configurações
echo "<h3>2. Verificando configurações...</h3>";
$settings = getWebhookSettings($conn);
echo "📊 Status: " . ($settings['enabled'] ? '✅ Ativo' : '⚠️ Inativo') . "<br>";
echo "🔗 URL: " . ($settings['webhook_url'] ? htmlspecialchars($settings['webhook_url']) : '❌ Não configurada') . "<br>";
echo "⏱️ Timeout: " . $settings['timeout'] . "s<br>";
echo "🔄 Tentativas: " . $settings['retry_attempts'] . "<br>";

// Teste 3: Testar payload
echo "<h3>3. Testando geração de payload...</h3>";
$test_order_data = [
    'id' => 999,
    'order_number' => 'TEST-' . date('YmdHis'),
    'status' => 'pendente',
    'created_at' => date('Y-m-d H:i:s'),
    'customer_name' => 'João Teste',
    'customer_email' => 'joao@teste.com',
    'customer_phone' => '595991234567',
    'customer_address' => 'Rua Teste, 123',
    'customer_city' => 'Assunção',
    'customer_reference' => 'Próximo ao mercado',
    'customer_notes' => 'Teste de webhook',
    'subtotal' => 25000,
    'shipping' => 7500,
    'total' => 32500,
    'total_weight' => 5.5,
    'items' => [
        [
            'product_id' => 1,
            'product_name' => 'Produto Teste',
            'quantity' => 2,
            'unit_price' => 12500,
            'total_price' => 25000
        ]
    ]
];

$payload = prepareWebhookPayload($test_order_data, $settings);

echo "✅ Payload gerado com sucesso!<br>";
echo "📦 Tamanho: " . strlen(json_encode($payload)) . " bytes<br>";
echo "🎯 Evento: " . $payload['event'] . "<br>";
echo "📱 Mensagem WhatsApp: " . (isset($payload['order']['whatsapp_message']) ? '✅ Incluída' : '❌ Faltando') . "<br>";

// Teste 4: Exibir estrutura do payload
echo "<h3>4. Estrutura do Payload:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "</pre>";

// Teste 5: Testar função de mensagem WhatsApp
echo "<h3>5. Testando mensagem do WhatsApp...</h3>";
$order_for_whatsapp = [
    'customer_name' => 'João Teste',
    'customer_phone' => '595991234567',
    'customer_email' => 'joao@teste.com',
    'customer_address' => 'Rua Teste, 123',
    'items' => [
        [
            'name' => 'Produto Teste',
            'quantity' => 2,
            'price' => 12500,
            'subtotal' => 25000,
            'has_price' => true
        ]
    ],
    'subtotal' => 25000,
    'total_weight' => 5.5,
    'shipping' => 7500,
    'total' => 32500,
    'notes' => 'Teste de webhook'
];

$whatsapp_message = generateWhatsAppMessage($order_for_whatsapp);
echo "✅ Mensagem gerada com sucesso!<br>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>📱 Prévia da mensagem:</strong><br>";
echo nl2br(htmlspecialchars($whatsapp_message));
echo "</div>";

echo "<h3>✅ Teste Concluído!</h3>";
echo "<p>Se todos os itens acima estão com ✅, o sistema de webhook está funcionando corretamente!</p>";
echo "<p><a href='webhooks.php'>← Voltar para Configurações de Webhook</a></p>";
?> 