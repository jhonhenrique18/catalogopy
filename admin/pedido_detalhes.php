<?php
// Verificar autenticação
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once 'includes/order_functions.php';
require_once '../includes/functions.php';

// Verificar se foi passado um ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$order_id = intval($_GET['id']);

// Buscar detalhes do pedido
$order = getOrderDetails($order_id, $conn);

if (!$order) {
    header('Location: pedidos.php');
    exit;
}

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Gerar link do WhatsApp para o vendedor com detalhes do pedido
function generateVendorWhatsApp($order, $store) {
    $whatsapp = preg_replace('/[^0-9]/', '', $store['whatsapp_number']);
    
    $message = "🛒 *DETALHES DO PEDIDO #" . $order['order_number'] . "*\n\n";
    $message .= "📅 *Data:* " . date('d/m/Y às H:i', strtotime($order['created_at'])) . "\n";
    $message .= "💰 *Total:* " . formatGuarani($order['total']) . "\n";
    $message .= "📋 *Status:* " . getStatusLabel($order['status']) . "\n\n";
    
    $message .= "👤 *CLIENTE:*\n";
    $message .= "• Nome: " . $order['customer_name'] . "\n";
    $message .= "• Telefone: " . $order['customer_phone'] . "\n";
    $message .= "• Endereço: " . $order['customer_address'] . "\n";
    $message .= "• Cidade: " . $order['customer_city'] . "\n\n";
    
    $message .= "📦 *PRODUTOS:*\n";
    foreach ($order['items'] as $item) {
        $message .= "• " . $item['product_name'] . "\n";
        $message .= "  Qtd: " . $item['quantity'] . "x - " . formatGuarani($item['total_price']) . "\n";
    }
    
    $message .= "\n💵 *RESUMO:*\n";
    $message .= "• Subtotal: " . formatGuarani($order['subtotal']) . "\n";
    $message .= "• Frete: " . formatGuarani($order['shipping']) . "\n";
    $message .= "• *TOTAL: " . formatGuarani($order['total']) . "*\n\n";
    
    if (!empty($order['customer_notes'])) {
        $message .= "📝 *Observações do Cliente:*\n" . $order['customer_notes'] . "\n\n";
    }
    
    $message .= "---\n";
    $message .= "Enviado via Sistema Administrativo";
    
    return "https://wa.me/" . $whatsapp . "?text=" . urlencode($message);
}

$page_title = "Detalhes do Pedido #" . $order['order_number'];
include 'includes/admin_layout.php';
?>

<style>
.order-header {
    background: linear-gradient(135deg, var(--admin-success) 0%, #2ECC71 100%);
    color: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.info-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.product-item {
    border-bottom: 1px solid #e9ecef;
    padding: 15px 0;
}

.product-item:last-child {
    border-bottom: none;
}

.product-image {
    width: 60px;
    height: 60px;
    background-size: cover;
    background-position: center;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.btn-whatsapp-vendor {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-whatsapp-vendor:hover {
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(37, 211, 102, 0.3);
}

@media print {
    .no-print {
        display: none !important;
    }
    .order-header {
        background: var(--admin-success) !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<div class="content-wrapper">
    <div class="content-header">
        <h1>Detalhes do Pedido</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pedidos.php">Pedidos</a></li>
                <li class="breadcrumb-item active">Pedido #<?php echo htmlspecialchars($order['order_number']); ?></li>
            </ol>
        </nav>
        <div class="header-actions no-print">
            <a href="pedidos.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Voltar aos Pedidos
            </a>
            <a href="<?php echo generateVendorWhatsApp($order, $store); ?>" 
               target="_blank" class="btn btn-whatsapp-vendor me-2">
                <i class="fab fa-whatsapp"></i> WhatsApp Vendedor
            </a>
<?php
            // Preparar dados do pedido para WhatsApp do cliente
            $order_for_client = [
                'customer_name' => $order['customer_name'],
                'customer_phone' => $order['customer_phone'],
                'customer_email' => $order['customer_email'] ?? '',
                'customer_address' => $order['customer_address'],
                'items' => [],
                'subtotal' => $order['subtotal'],
                'shipping' => $order['shipping'],
                'total' => $order['total'],
                'total_weight' => $order['total_weight'] ?? 0,
                'notes' => $order['customer_notes'] ?? ''
            ];
            
            foreach ($order['items'] as $item) {
                $order_for_client['items'][] = [
                    'name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['unit_price'],
                    'subtotal' => $item['total_price']
                ];
            }
            ?>
            <a href="<?php echo generateWhatsAppLink($order_for_client, $order['customer_phone']); ?>" 
               target="_blank" class="btn btn-success me-2">
                <i class="fab fa-whatsapp"></i> Contatar Cliente
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="content-body">
        <!-- Cabeçalho do Pedido -->
        <div class="order-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">Pedido #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                    <p class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo date('d/m/Y às H:i', strtotime($order['created_at'])); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-primary fs-6 px-3 py-2">
                        <?php echo getStatusLabel($order['status']); ?>
                    </span>
                    <h3 class="mt-2 mb-0"><?php echo formatGuarani($order['total']); ?></h3>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informações do Cliente -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-user text-primary me-2"></i>
                        Informações do Cliente
                    </h5>
                    <div class="customer-info">
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p><strong>Endereço:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                        <p><strong>Cidade:</strong> <?php echo htmlspecialchars($order['customer_city']); ?></p>
                        <?php if (!empty($order['customer_notes'])): ?>
                        <p><strong>Observações:</strong></p>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="col-md-6">
                <div class="info-card">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-calculator text-success me-2"></i>
                        Resumo Financeiro
                    </h5>
                    <div class="financial-summary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong><?php echo formatGuarani($order['subtotal']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frete:</span>
                            <strong><?php echo formatGuarani($order['shipping']); ?></strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span><strong>Total:</strong></span>
                            <strong class="text-success"><?php echo formatGuarani($order['total']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produtos do Pedido -->
        <div class="info-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-shopping-bag text-warning me-2"></i>
                Produtos do Pedido
            </h5>
            <div class="products-list">
                <?php foreach ($order['items'] as $item): ?>
                <div class="product-item">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="product-image" 
                                 style="background-image: url('<?php echo !empty($item['product_image']) ? "../" . htmlspecialchars($item['product_image']) : "../assets/images/default-product.png"; ?>');">
                            </div>
                        </div>
                        <div class="col">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <small class="text-muted">
                                Quantidade: <?php echo $item['quantity']; ?>x - 
                                Preço unitário: <?php echo formatGuarani($item['unit_price']); ?>
                            </small>
                        </div>
                        <div class="col-auto">
                            <strong class="text-success"><?php echo formatGuarani($item['total_price']); ?></strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Script para melhorar a impressão
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script> 