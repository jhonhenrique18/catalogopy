<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivos essenciais
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/exchange_functions.php';
require_once 'includes/order_functions.php';

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Verificar se o carrinho está vazio e redirecionar
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header('Location: carrinho.php');
    exit;
}

// Inicializar variáveis para o formulário
$customer_name = '';
$customer_email = '';
$customer_phone = '';
$customer_address = '';
$customer_city = '';
$customer_reference = '';
$customer_notes = '';
$errors = [];

// Processar o formulário apenas em requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar e atribuir os dados do formulário
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $customer_email = sanitize($_POST['customer_email'] ?? '');
    $customer_phone = sanitize($_POST['customer_phone'] ?? '');
    $customer_address = sanitize($_POST['customer_address'] ?? '');
    $customer_city = sanitize($_POST['customer_city'] ?? '');
    $customer_reference = sanitize($_POST['customer_reference'] ?? '');
    $customer_notes = sanitize($_POST['customer_notes'] ?? '');
    
    // Validação dos campos obrigatórios
    if (empty($customer_name)) $errors['customer_name'] = 'Por favor, ingrese su nombre.';
    if (empty($customer_phone)) $errors['customer_phone'] = 'Por favor, ingrese su número de teléfono.';
    if (empty($customer_address)) $errors['customer_address'] = 'Por favor, ingrese su dirección.';
    if (empty($customer_city)) $errors['customer_city'] = 'Por favor, ingrese su ciudad.';
    
    // Se não houver erros, prosseguir
    if (empty($errors)) {
        // Calcular totais com base nos itens da sessão
        $subtotal_brl = 0;
        $total_weight = 0;
        $shipping_rate = $store['shipping_rate'] ?? 1500;
        $global_shipping_enabled = isset($store['enable_shipping']) ? (bool)$store['enable_shipping'] : true;
        $products_with_price = [];
        $products_to_quote = [];

        foreach ($_SESSION['cart'] as $item) {
            $min_wholesale_quantity = $item['min_wholesale_quantity'] ?? 10;
            $wholesale_price = floatval($item['wholesale_price'] ?? 0);
            $retail_price = floatval($item['retail_price'] ?? 0);
            $quantity = intval($item['quantity'] ?? 1);
            $weight = floatval($item['weight'] ?? 1);
            $has_price = isset($item['has_price']) ? $item['has_price'] : ($wholesale_price > 0);
            
            if ($has_price) {
                $price_brl = $quantity >= $min_wholesale_quantity ? $wholesale_price : $retail_price;
                $subtotal_brl += $price_brl * $quantity;
                $products_with_price[] = $item;
            } else {
                $products_to_quote[] = $item;
            }
            
            $total_weight += $weight * $quantity;
        }

        $shipping_pyg = $global_shipping_enabled ? calculateShipping($total_weight, $shipping_rate) : 0;
        $subtotal_pyg = convertBrlToPyg($subtotal_brl);
        $total_pyg = $subtotal_pyg + $shipping_pyg;

        // Montar dados para salvar no banco
        $order_data_for_db = [
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'customer_city' => $customer_city,
            'customer_reference' => $customer_reference,
            'customer_notes' => $customer_notes,
            'subtotal' => $subtotal_pyg,
            'total_weight' => $total_weight,
            'shipping' => $shipping_pyg,
            'total' => $total_pyg,
            'items' => []
        ];

        foreach ($_SESSION['cart'] as $item) {
            $min_wholesale_quantity = $item['min_wholesale_quantity'] ?? 10;
            $wholesale_price_brl = floatval($item['wholesale_price'] ?? 0);
            $retail_price_brl = floatval($item['retail_price'] ?? 0);
            $quantity = intval($item['quantity'] ?? 1);
            $has_price = isset($item['has_price']) ? $item['has_price'] : ($wholesale_price_brl > 0);
            
            if ($has_price) {
                $price_brl = $quantity >= $min_wholesale_quantity ? $wholesale_price_brl : $retail_price_brl;
                $price_pyg = convertBrlToPyg($price_brl);
                $subtotal_pyg = $price_pyg * $quantity;
            } else {
                $price_pyg = 0;
                $subtotal_pyg = 0;
            }

            $order_data_for_db['items'][] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'quantity' => $quantity,
                'price' => $price_pyg,
                'subtotal' => $subtotal_pyg,
                'min_wholesale_quantity' => $min_wholesale_quantity,
                'has_price' => $has_price,
                'price_status' => $has_price ? 'with_price' : 'quote_required'
            ];
        }
        
        // Salvar pedido no banco
        saveOrder($order_data_for_db, $conn);
        
        // Preparar dados para o link do WhatsApp
        $full_address = $customer_address . ', ' . $customer_city;
        if (!empty($customer_reference)) {
            $full_address .= ' (Referencia: ' . $customer_reference . ')';
        }
        
        $order_for_whatsapp = [
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'customer_address' => $full_address,
            'items' => $order_data_for_db['items'],
            'subtotal' => $subtotal_pyg,
            'total_weight' => $total_weight,
            'shipping' => $shipping_pyg,
            'total' => $total_pyg,
            'notes' => $customer_notes
        ];
        
        $whatsapp_phone = $store['whatsapp_number'] ?? '';
        $include_shipping = isset($store['enable_shipping']) ? (bool)$store['enable_shipping'] : true;
        $whatsapp_link = generateWhatsAppLink($order_for_whatsapp, $whatsapp_phone, $include_shipping);
        
        // Limpar o carrinho e redirecionar
        $_SESSION['cart'] = [];
        header('Location: ' . $whatsapp_link);
        exit;
    }
}

// Calcular totais para exibição na página (GET request)
$subtotal_brl = 0;
$total_weight = 0;
$shipping_rate = $store['shipping_rate'] ?? 1500;
$global_shipping_enabled = isset($store['enable_shipping']) ? (bool)$store['enable_shipping'] : true;
$cart_items_for_summary = [];
foreach ($_SESSION['cart'] as $item) {
    $min_wholesale_quantity = $item['min_wholesale_quantity'] ?? 10;
    $wholesale_price = floatval($item['wholesale_price'] ?? 0);
    $retail_price = floatval($item['retail_price'] ?? 0);
    $quantity = intval($item['quantity'] ?? 1);
    $weight = floatval($item['weight'] ?? 1);
    $price_brl = $quantity >= $min_wholesale_quantity ? $wholesale_price : $retail_price;
    $price_pyg = convertBrlToPyg($price_brl);
    
    $subtotal_brl += $price_brl * $quantity;
    $total_weight += $weight * $quantity;
    $cart_items_for_summary[] = [
        'name' => $item['name'],
        'quantity' => $quantity,
        'price' => $price_pyg,
        'subtotal' => $price_pyg * $quantity
    ];
}
$shipping_pyg = $global_shipping_enabled ? calculateShipping($total_weight, $shipping_rate) : 0;
$subtotal_pyg = convertBrlToPyg($subtotal_brl);
$total_pyg = $subtotal_pyg + $shipping_pyg;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Checkout - <?php echo htmlspecialchars($store['store_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #27AE60;
            --color-primary-dark: #219653;
            --color-danger: #E74C3C;
            --color-gray-light: #f8f9fa;
            --color-gray-medium: #e9ecef;
            --color-gray-dark: #343a40;
            --font-family-main: 'Poppins', sans-serif;
        }
        body {
            font-family: var(--font-family-main);
            background-color: var(--color-gray-light);
            padding-bottom: 150px; /* Space for fixed footer */
        }
        .form-label.required-field::after {
            content: '*';
            color: var(--color-danger);
            margin-left: 4px;
        }
        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }
        .checkout-section {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .checkout-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header a {
            font-size: 1.5rem;
            color: var(--color-gray-dark);
            margin-right: 16px;
        }
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        /* Sticky Footer for Summary and CTA */
        .summary-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border-top: 1px solid var(--color-gray-medium);
            box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
            padding: 16px;
            z-index: 1000;
        }
        .accordion-button {
            font-weight: 500;
            background-color: transparent !important;
            box-shadow: none !important;
            color: var(--color-gray-dark) !important;
            padding: 8px 0;
        }
        .accordion-button::after {
            filter: grayscale(1);
        }
        .summary-total-value {
            font-weight: 700;
            color: var(--color-primary);
        }
        .summary-details .summary-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 8px 0;
            border-bottom: 1px solid var(--color-gray-light);
        }
        .summary-details .summary-item:last-child {
            border-bottom: none;
        }
        .summary-details .total {
            font-weight: 700;
            font-size: 1rem;
            margin-top: 8px;
        }
        .btn-finalizar {
            background: linear-gradient(90deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 8px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container py-3">
        <div class="page-header">
            <a href="carrinho.php"><i class="fas fa-arrow-left"></i></a>
            <h1>Finalizar Compra</h1>
        </div>

        <form method="post" action="checkout.php" class="needs-validation" novalidate>
            <div class="checkout-section">
                <h2><i class="fas fa-user-circle text-primary me-2"></i>Datos Personales</h2>
                <div class="mb-3">
                    <label for="customer_name" class="form-label required-field">Nombre y Apellido</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                    <div class="invalid-feedback">Por favor, ingrese su nombre.</div>
                </div>
                <div class="mb-3">
                    <label for="customer_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>">
                </div>
                <div class="mb-3">
                    <label for="customer_phone" class="form-label required-field">Teléfono</label>
                    <input type="tel" class="form-control" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                    <div class="invalid-feedback">Por favor, ingrese su número de teléfono.</div>
                </div>
            </div>
            
            <div class="checkout-section">
                <h2><i class="fas fa-map-marker-alt text-primary me-2"></i>Dirección de Entrega</h2>
                <div class="mb-3">
                    <label for="customer_address" class="form-label required-field">Dirección</label>
                    <input type="text" class="form-control" id="customer_address" name="customer_address" value="<?php echo htmlspecialchars($customer_address); ?>" required>
                    <div class="invalid-feedback">Por favor, ingrese su dirección.</div>
                </div>
                <div class="mb-3">
                    <label for="customer_city" class="form-label required-field">Ciudad</label>
                    <input type="text" class="form-control" id="customer_city" name="customer_city" value="<?php echo htmlspecialchars($customer_city); ?>" required>
                    <div class="invalid-feedback">Por favor, ingrese su ciudad.</div>
                </div>
                <div class="mb-3">
                    <label for="customer_reference" class="form-label">Referencia de ubicación</label>
                    <input type="text" class="form-control" id="customer_reference" name="customer_reference" value="<?php echo htmlspecialchars($customer_reference); ?>">
                </div>
            </div>

            <div class="checkout-section">
                                    <h2><i class="fas fa-pencil-alt text-primary me-2"></i>Observaciones</h2>
                                    <textarea class="form-control" id="customer_notes" name="customer_notes" rows="3" placeholder="Información adicional para el vendedor (opcional)"><?php echo htmlspecialchars($customer_notes); ?></textarea>
            </div>

            <div class="summary-footer">
                <div class="accordion" id="summaryAccordion">
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            Ver Resumen del Pedido <span class="ms-auto summary-total-value">Total: G$ <?php echo number_format($total_pyg, 0, ',', '.'); ?></span>
                        </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#summaryAccordion">
                            <div class="accordion-body summary-details p-0">
                                <?php foreach($cart_items_for_summary as $item): ?>
                                <div class="summary-item">
                                    <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                                    <span>G$ <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                                </div>
                                <?php endforeach; ?>
                                <div class="summary-item">
                                    <span>Subtotal:</span>
                                    <span>G$ <?php echo number_format($subtotal_pyg, 0, ',', '.'); ?></span>
                                </div>
                                <?php if ($global_shipping_enabled): ?>
                                <div class="summary-item">
                                    <span>Flete:</span>
                                    <span>G$ <?php echo number_format($shipping_pyg, 0, ',', '.'); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-item total">
                                    <span>Total:</span>
                                    <span>G$ <?php echo number_format($total_pyg, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-finalizar">
                    <i class="fab fa-whatsapp"></i> Finalizar Pedido
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>