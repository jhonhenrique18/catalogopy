<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Incluir funções utilitárias
require_once 'includes/functions.php';

// Incluir funções de câmbio
require_once 'includes/exchange_functions.php';

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Verificar se o carrinho existe
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    $cart_empty = true;
    $cart_items = [];
} else {
    $cart_empty = false;
    $cart_items = $_SESSION['cart'];
    
    // Corrigir preços inválidos no carrinho
    foreach ($_SESSION['cart'] as $key => $item) {
        // Verificar se o preço minorista está muito baixo (erro)
        if (isset($item['retail_price']) && $item['retail_price'] <= 1) {
            // Buscar o preço correto no banco de dados
            $product_id = $item['id'];
            $query = "SELECT retail_price FROM products WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Atualizar o preço no carrinho
                $_SESSION['cart'][$key]['retail_price'] = floatval($row['retail_price']);
                $cart_items[$key]['retail_price'] = floatval($row['retail_price']);
            }
        }
        
        // Verificar se o preço mayorista está muito baixo (erro)
        if (isset($item['wholesale_price']) && $item['wholesale_price'] <= 1) {
            // Buscar o preço correto no banco de dados
            $product_id = $item['id'];
            $query = "SELECT wholesale_price FROM products WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Atualizar o preço no carrinho
                $_SESSION['cart'][$key]['wholesale_price'] = floatval($row['wholesale_price']);
                $cart_items[$key]['wholesale_price'] = floatval($row['wholesale_price']);
            }
        }
    }
}

// Calcular totais
$subtotal = 0;
$total_weight = 0;
$shipping_rate = isset($store['shipping_rate']) ? $store['shipping_rate'] : 1500; // Padrão: 1500 Gs por kg

if (!$cart_empty) {
    foreach ($cart_items as $item) {
        // Verificar se as chaves necessárias existem para evitar warnings
        $min_wholesale_quantity = isset($item['min_wholesale_quantity']) ? $item['min_wholesale_quantity'] : 10;
        $wholesale_price = isset($item['wholesale_price']) ? floatval($item['wholesale_price']) : 0;
        $retail_price = isset($item['retail_price']) ? floatval($item['retail_price']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
        $weight = isset($item['weight']) ? floatval($item['weight']) : 1; // Peso padrão de 1kg se não especificado
        
        // Verifique se a quantidade atende ao mínimo para preço mayorista
        $price = $quantity >= $min_wholesale_quantity ? $wholesale_price : $retail_price;
        
        $subtotal += $price * $quantity;
        $total_weight += $weight * $quantity;
    }
}

// Calcular frete
$shipping = calculateShipping($total_weight, $shipping_rate);

// Calcular total
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carrito de Compras - <?php echo htmlspecialchars($store['store_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Configurações da Loja para JavaScript -->
    <?php 
    require_once 'includes/store_config.php';
    echo generateStoreConfigScript($store_config);
    ?>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS personalizado -->
    <style>
        :root {
            --color-primary: #27AE60;
            --color-primary-dark: #219653;
            --color-primary-light: #6FCF97;
            --color-secondary: #3498DB;
            --color-danger: #E74C3C;
            --color-gray-light: #F5F5F5;
            --color-gray-medium: #E0E0E0;
            --color-gray-dark: #333333;
            --font-size-small: 14px;
            --font-size-medium: 16px;
            --font-size-large: 18px;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 70px; /* Espaço para o footer fixo */
            touch-action: manipulation; /* Evita delay de clique em mobile */
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--color-gray-dark);
        }
        
        .cart-empty {
            text-align: center;
            padding: 30px 15px;
            background-color: white;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .cart-empty-icon {
            font-size: 50px;
            color: var(--color-gray-medium);
            margin-bottom: 15px;
        }
        
        .cart-empty-message {
            font-size: var(--font-size-medium);
            color: var(--color-gray-dark);
            margin-bottom: 20px;
        }
        
        .cart-item {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: white;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .cart-item-info {
            flex-grow: 1;
        }
        
        .cart-item-name {
            font-size: var(--font-size-medium);
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--color-gray-dark);
        }
        
        /* Indicador de variação no carrinho */
        .variation-badge {
            display: inline-block;
            background-color: var(--color-primary);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        .cart-item-price {
            font-size: var(--font-size-medium);
            color: var(--color-primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .cart-item-type {
            font-size: var(--font-size-small);
            color: var(--color-primary-dark);
            margin-bottom: 5px;
        }
        
        /* Estilo específico para produtos sem preço */
        .cart-item.quote-required {
            border-left: 3px solid #17A2B8;
            background-color: #F8F9FA;
        }
        
        .cart-item.quote-required .cart-item-price {
            color: #17A2B8 !important;
            font-weight: 600;
        }
        
        .cart-item-subtotal {
            font-size: var(--font-size-medium);
            color: var(--color-gray-dark);
            font-weight: 600;
        }
        
        .cart-item-actions {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .quantity-control button {
            background-color: var(--color-gray-light);
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .quantity-control input {
            width: 50px;
            height: 40px;
            text-align: center;
            border: 1px solid var(--color-gray-medium);
            margin: 0 5px;
            font-size: 16px;
            border-radius: 4px;
        }
        
        .quantity-control input::-webkit-inner-spin-button,
        .quantity-control input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .btn-remove {
            color: var(--color-danger);
            background: none;
            border: none;
            font-size: 20px;
            padding: 8px;
            cursor: pointer;
        }
        
        .cart-summary {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: var(--font-size-medium);
        }
        
        .summary-item.total {
            font-size: var(--font-size-large);
            font-weight: 600;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--color-gray-light);
        }
        
        .btn-continuar {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 4px;
            height: 50px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .btn-continuar:hover {
            background-color: var(--color-primary-dark);
        }
        
        .btn-seguir {
            background-color: var(--color-gray-light);
            color: var(--color-gray-dark);
            border: none;
            border-radius: 4px;
            height: 50px;
            font-size: 16px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-seguir i {
            margin-right: 8px;
        }
        
        .cart-count {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--color-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transform: translate(50%, -50%);
        }
        
        /* Contador do carrinho no footer */
        .footer-cart-count {
            position: absolute;
            top: -5px;
            right: 15px;
            background-color: var(--color-danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            transform: none;
            animation: cartPulse 0.6s ease-out;
        }
        
        .footer-cart-count.hidden {
            display: none;
        }
        
        @keyframes cartPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            box-shadow: 0 -1px 4px rgba(0,0,0,0.1);
            padding: 10px 0;
            display: flex;
            justify-content: space-around;
            z-index: 1000;
        }
        
        .footer-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--color-gray-dark);
            text-decoration: none;
            font-size: 12px;
        }
        
        .footer-icon i {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .footer-icon.active {
            color: var(--color-primary);
        }
        
        .btn-clear-cart {
            background-color: var(--color-danger);
            color: white;
            border: none;
            border-radius: 4px;
            height: 40px;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-clear-cart i {
            margin-right: 8px;
        }
        

        
        /* Previne zoom em campos de formulário no iOS */
        @media screen and (-webkit-min-device-pixel-ratio:0) { 
            select,
            textarea,
            input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($store['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($store['logo_url']); ?>" alt="<?php echo htmlspecialchars($store['store_name']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($store['store_name']); ?>
                <?php endif; ?>
            </a>
            
            <div class="d-flex align-items-center">
                <a href="carrinho.php" class="btn btn-outline-primary position-relative me-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="carrinho.php">Carrito</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    

    
    <!-- Conteúdo principal -->
    <div class="container py-4">
        <h1 class="page-title">Carrito de Compras</h1>
        
        <div id="cart-container">
            <!-- O conteúdo do carrinho será carregado dinamicamente via JavaScript -->
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando carrito...</p>
            </div>
        </div>
        
        <!-- Botão para limpar carrinho -->
        <button type="button" id="btn-clear-cart" class="btn-clear-cart btn w-100 mb-3">
            <i class="fas fa-trash-alt"></i> Vaciar Carrito
        </button>
        
        <!-- Botões de ação -->
        <a href="index.php" class="btn-seguir text-decoration-none">
            <i class="fas fa-chevron-left"></i> Seguir Comprando
        </a>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <a href="index.php" class="footer-icon">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="categorias.php" class="footer-icon">
            <i class="fas fa-list"></i>
            <span>Categorías</span>
        </a>
        <a href="carrinho.php" class="footer-icon active position-relative">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrito</span>
            <span class="cart-count footer-cart-count" id="footer-cart-count">0</span>
        </a>
    </div>
    
    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de carrinho centralizado -->
    <script src="assets/js/cart.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar botão de limpar carrinho
        document.getElementById('btn-clear-cart').addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas vaciar el carrito?')) {
                clearCart();
            }
        });
    });
    </script>
</body>
</html>