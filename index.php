<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Incluir funções utilitárias
require_once 'includes/functions.php';

// Incluir funções de câmbio
require_once 'includes/exchange_functions.php';

// Incluir funções de pop-up
require_once 'includes/popup_functions.php';

// Incluir funções da barra rotativa
require_once 'includes/rotating_banner_functions.php';

// INCLUIR SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS
require_once 'includes/minimum_quantity_functions.php';

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Definir variáveis de controle global
$global_minimums_enabled = isset($store['enable_global_minimums']) ? (bool)$store['enable_global_minimums'] : true;
$global_shipping_enabled = isset($store['enable_shipping']) ? (bool)$store['enable_shipping'] : true;

// Verificar se existem categorias
$query_categories = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
$result_categories = $conn->query($query_categories);

// Verificar se foi solicitada uma categoria específica
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Parâmetros de paginação - carregamento inicial
$items_per_page = 20; // Número de produtos por página
$current_page = 1; // Página inicial

// Montar a consulta SQL para produtos com paginação e nova ordenação (promoção > destacado > alfabética)
// APENAS PRODUTOS PAI - Variações ficam ocultas da listagem principal
if ($category_id > 0) {
    $query_products = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity,
                      (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
                      FROM products p
                      WHERE p.status = 1 AND p.parent_product_id IS NULL AND p.category_id = ? 
                      ORDER BY p.promotion DESC, p.featured DESC, p.name ASC 
                      LIMIT ?";
    
    $stmt = $conn->prepare($query_products);
    $stmt->bind_param("ii", $category_id, $items_per_page);
} else {
    $query_products = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity,
                      (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
                      FROM products p
                      WHERE p.status = 1 AND p.parent_product_id IS NULL
                      ORDER BY p.promotion DESC, p.featured DESC, p.name ASC 
                      LIMIT ?";
    
    $stmt = $conn->prepare($query_products);
    $stmt->bind_param("i", $items_per_page);
}

// Executar consulta
$stmt->execute();
$result_products = $stmt->get_result();

// Consulta para contar o total de produtos PAI (para navegação)
if ($category_id > 0) {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1 AND parent_product_id IS NULL AND category_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $category_id);
} else {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1 AND parent_product_id IS NULL";
    $count_stmt = $conn->prepare($count_query);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $items_per_page);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($store['store_name']); ?> - Catálogo de Productos</title>
    
    <?php 
    // Incluir CSS do pop-up se houver pop-up ativo
    if (shouldShowPopup($conn)) {
        echo generatePopupCSS();
    }
    ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Configurações da Loja para JavaScript -->
    <?php 
    require_once 'includes/store_config.php';
    echo generateStoreConfigScript($store_config);
    ?>
    
    <!-- SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS -->
    <?php 
    echo generateMinimumQuantityCSS();
    echo generateMinimumQuantityJavaScript();
    ?>
    
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
            --font-size-small: 13px;
            --font-size-medium: 15px;
            --font-size-large: 17px;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 70px; /* Espaço para o footer fixo */
            touch-action: manipulation; /* Evita delay de clique em mobile */
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .category-filter {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            padding: 8px 0;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
        }
        
        .category-filter::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .category-filter .btn {
            margin-right: 8px;
            flex-shrink: 0;
            border-radius: 20px;
        }
        
        .category-filter .btn.active {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 16px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
            position: relative;
        }
        
        .product-card:active {
            transform: scale(0.98);
        }
        
        /* Estilos para produtos em promoção */
        .product-card.promotion {
            border: 3px solid #FF6B35;
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
            background: linear-gradient(135deg, #fff 0%, #fff9f7 100%);
        }
        
        .product-card.promotion::before {
            content: "🔥 PROMOÇÃO";
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(45deg, #FF6B35, #FF4500);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.4);
            animation: pulsePromo 2s infinite;
        }
        
        @keyframes pulsePromo {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Estilos para produtos destacados */
        .product-card.featured {
            border: 2px solid #FFD700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }
        
        .product-card.featured::after {
            content: "⭐ DESTACADO";
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #333;
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 2px 6px rgba(255, 215, 0, 0.3);
        }
        
        /* Ajuste para produtos que são promoção E destacados */
        .product-card.promotion.featured::after {
            top: 32px; /* Mover para baixo do badge de promoção */
        }
        
        .product-image {
            height: 140px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #fff;
            border-bottom: 1px solid var(--color-gray-light);
        }
        
        .product-info {
            padding: 12px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        
        .product-title {
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--color-gray-dark);
            height: 38px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.2;
        }
        
        .price-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 8px;
            background-color: var(--color-gray-light);
        }
        
        .product-mayorista {
            font-size: var(--font-size-large);
            font-weight: bold;
            color: var(--color-primary);
            margin-bottom: 2px;
        }
        
        .product-min-qty {
            font-size: var(--font-size-small);
            color: var(--color-primary-dark);
            margin-bottom: 6px;
            font-weight: 600;
            padding: 2px 8px;
            background-color: var(--color-primary-light);
            color: white;
            border-radius: 4px;
            display: inline-block;
        }
        
        .product-minorista {
            font-size: var(--font-size-small);
            color: var(--color-gray-dark);
            margin-bottom: 0;
            padding-top: 4px;
            border-top: 1px dashed var(--color-gray-medium);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            justify-content: center;
        }
        
        .quantity-control button {
            background-color: var(--color-gray-light);
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .quantity-control button:active {
            background-color: var(--color-gray-medium);
        }
        
        .quantity-control input {
            width: 50px;
            height: 40px;
            text-align: center;
            border: 1px solid var(--color-gray-medium);
            margin: 0 6px;
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .quantity-control input::-webkit-inner-spin-button,
        .quantity-control input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .btn-agregar {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            height: 42px;
            width: 100%;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(39, 174, 96, 0.3);
        }
        
        .btn-agregar:hover {
            background-color: var(--color-primary-dark);
        }
        
        .btn-agregar:active {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(39, 174, 96, 0.3);
        }
        
        .btn-agregar i {
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
        
        .navbar {
            background-color: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            z-index: 200;
            position: relative;
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
        
        /* Reposicionamento do toast para o topo da tela */
        .toast-container {
            position: fixed;
            top: 60px; /* Abaixo do navbar */
            left: 0;
            right: 0;
            z-index: 1050;
            display: flex;
            justify-content: center;
        }
        
        .toast {
            max-width: 90%;
            width: auto;
            background-color: rgba(39, 174, 96, 0.95);
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Loader para carregamento infinito */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px 0;
        }
        
        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--color-primary);
        }
        
        /* Lazy loading para imagens */
        .lazy-bg {
            opacity: 0;
            transition: opacity 0.3s;
            background-color: #f0f0f0;
        }
        
        .lazy-bg.loaded {
            opacity: 1;
        }
        
        @media (max-width: 767px) {
            .product-image {
                height: 130px;
            }
            
            .row.row-cols-2 {
                --bs-gutter-x: 10px;
            }
        }
        
        /* Previne zoom em campos de formulário no iOS */
        @media screen and (-webkit-min-device-pixel-ratio:0) { 
            select,
            textarea,
            input {
                font-size: 16px;
            }
        }

        /* ============ BARRA DE PESQUISA MODERNA ============ */
        /* BARRA DE PESQUISA MODERNA */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            min-width: 250px;
        }
        
        @media (max-width: 768px) {
            .search-container {
                max-width: 100%;
                min-width: 200px;
            }
        }
        
        @media (max-width: 576px) {
            .search-container {
                min-width: 180px;
                max-width: 280px;
            }
        }
        
        .search-wrapper {
            position: relative;
            width: 100%;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .search-input:focus {
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .search-input::placeholder {
            color: #6c757d;
            font-style: italic;
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #007bff;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn:hover {
            background: #0056b3;
            transform: translateY(-50%) scale(1.05);
        }
        
        .search-clear {
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        .search-clear:hover {
            background: #c82333;
            transform: translateY(-50%) scale(1.1);
        }
        
        /* RESULTADOS DE BUSCA */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 5px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .search-results.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .search-result-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        
        .search-result-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #007bff;
            text-decoration: none;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
            border-radius: 0 0 15px 15px;
        }
        
        .search-result-item:first-child {
            border-radius: 15px 15px 0 0;
        }
        
        .search-result-item.single {
            border-radius: 15px;
        }
        
        .search-result-item.active {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
            color: white !important;
        }
        
        .search-result-item.active .search-result-name {
            color: white !important;
        }
        
        .search-result-item.active .search-result-price {
            color: #e3f2fd !important;
        }
        
        .search-result-image {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 12px;
            object-fit: cover;
            background: #f8f9fa;
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-name {
            font-weight: 500;
            margin: 0;
            font-size: 14px;
            color: #333;
            line-height: 1.3;
        }
        
        .search-result-price {
            font-size: 12px;
            color: #007bff;
            margin: 2px 0 0 0;
            font-weight: 600;
        }
        
        .search-no-results {
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        .search-loading {
            padding: 20px;
            text-align: center;
            color: #007bff;
        }
        
        /* ANIMAÇÃO DO LOADING */
        .search-loading .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* RESPONSIVIDADE MOBILE */
        @media (max-width: 768px) {
            .search-container {
                min-width: 200px;
                max-width: 250px;
            }
            
            .search-input {
                padding: 10px 45px 10px 15px;
                font-size: 14px;
            }
            
            .search-btn {
                width: 30px;
                height: 30px;
                right: 3px;
            }
            
            .search-clear {
                width: 20px;
                height: 20px;
                right: 35px;
                font-size: 8px;
            }
            
            .search-results {
                max-height: 300px;
            }
            
            .search-result-item {
                padding: 10px 12px;
            }
            
            .search-result-image {
                width: 35px;
                height: 35px;
                margin-right: 10px;
            }
            
            .search-result-name {
                font-size: 13px;
            }
            
            .search-result-price {
                font-size: 11px;
            }
        }
        
        @media (max-width: 576px) {
            .search-container {
                min-width: 180px;
                max-width: 200px;
            }
            
            .search-input::placeholder {
                font-size: 13px;
            }
        }

        /* Ajuste para iOS Safari */
        .search-input {
            -webkit-appearance: none;
            -webkit-border-radius: 25px;
        }
        
        /* Melhorar touch targets no mobile */
        @media (max-width: 768px) {
            .search-result-item {
                min-height: 50px;
                touch-action: manipulation;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Incluir barra rotativa se houver mensagens ativas
    echo generateRotatingBannerHTML($conn);
    ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container-fluid px-2">
            <a class="navbar-brand me-2 me-md-3" href="index.php">
                <?php if (!empty($store['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($store['logo_url']); ?>" alt="<?php echo htmlspecialchars($store['store_name']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($store['store_name']); ?>
                <?php endif; ?>
            </a>
            
            <!-- Área central com barra de pesquisa -->
            <div class="flex-grow-1 d-flex justify-content-center mx-2">
                <!-- BARRA DE PESQUISA MODERNA -->
                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" 
                               class="search-input" 
                               id="product-search" 
                               placeholder="¿Qué producto buscás?"
                               autocomplete="off">
                        <button class="search-btn" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="search-clear" type="button" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- RESULTADOS DE BUSCA EM TEMPO REAL -->
                    <div class="search-results" id="search-results"></div>
                </div>
            </div>
            
            <!-- Área direita com carrinho e menu -->
            <div class="d-flex align-items-center">
                <a href="carrinho.php" class="btn btn-outline-primary position-relative me-2">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
                <button class="navbar-toggler ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="carrinho.php">Carrito</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Conteúdo principal -->
    <div class="container py-3">
        <!-- Filtro de categorias -->
        <?php if ($result_categories->num_rows > 0): ?>
        <div class="category-filter mb-3">
            <a href="index.php" class="btn btn-sm <?php echo $category_id == 0 ? 'btn-primary active' : 'btn-outline-primary'; ?>">
                Todos
            </a>
            <?php while ($category = $result_categories->fetch_assoc()): ?>
            <a href="index.php?category=<?php echo $category['id']; ?>" 
               class="btn btn-sm <?php echo $category_id == $category['id'] ? 'btn-primary active' : 'btn-outline-primary'; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        
        <!-- Informação sobre total de produtos -->
        <div class="mb-2 text-muted small">
            <span id="products-count"><?php echo $total_products; ?></span> productos encontrados
        </div>
        
        <!-- Grid de produtos -->
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2" id="products-grid">
            <?php if ($result_products->num_rows > 0): ?>
                <?php while ($product = $result_products->fetch_assoc()): ?>
                <div class="col product-item">
                    <div class="product-card <?php echo $product['promotion'] ? 'promotion' : ''; ?> <?php echo $product['featured'] ? 'featured' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                        <a href="produto.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                            <div class="product-image lazy-bg" data-bg="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png'; ?>"></div>
                        </a>
                        <div class="product-info">
                            <a href="produto.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            </a>
                            
                            <div class="price-container">
                                <?php 
                                $unit_type = $product['unit_type'] ?? 'kg';
                                $unit_display = $product['unit_display_name'] ?? 'kg';
                                $unit_weight = $product['unit_weight'] ?? '1.00';
                                $show_price = $product['show_price'] ?? 1;
                                $has_min_quantity = $product['has_min_quantity'] ?? 1;
                                $contact_seller_text = $store['contact_seller_text'] ?? 'Consultar con el vendedor';
                                ?>
                                
                                <!-- Exibição de preço ou texto de consulta -->
                                <?php if ($show_price && !empty($product['wholesale_price'])): ?>
                                    <p class="product-mayorista"><?php echo formatPriceInGuaranis($product['wholesale_price']); ?></p>
                                <?php else: ?>
                                    <p class="product-mayorista text-info">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($contact_seller_text); ?>
                                    </p>
                                <?php endif; ?>
                                
                                                                <!-- Indicador de variações disponíveis -->
                                <?php if (isset($product['variations_count']) && $product['variations_count'] > 0): ?>
                                    <p class="product-variations-indicator" style="font-size: 11px; color: #28a745; margin: 2px 0;">
                                        <i class="fas fa-layer-group me-1"></i><?php echo $product['variations_count']; ?> variaciones disponibles
                                    </p>
                                <?php endif; ?>

                                <!-- Quantidade mínima - só exibe se configurado -->
                                <?php 
                                // USAR SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS
                                echo generateMinimumQuantityText($product);
                                if (shouldShowMinimumText($product) && !empty($store['minimum_explanation_text'])): ?>
                                    <small class="text-muted d-block" style="font-size: 10px; line-height: 1.2; margin-top: -2px;">
                                        <?php echo htmlspecialchars($store['minimum_explanation_text']); ?>
                                    </small>
                                <?php endif; ?>
                                
                                <!-- Não mostrar cálculo "por ml" - vendemos por unidade, não por ml -->
                                <?php // Removido cálculo de preço por ml pois vendemos por unidade, não por ml ?>
                            </div>
                            
                            <!-- Controles de compra - SISTEMA UNIFICADO -->
                            <?php echo generateQuantityControls($product); ?>
                            
                            <?php if ($show_price && !empty($product['wholesale_price'])): ?>
                            <!-- Botão normal para produtos com preço -->
                            <button type="button" class="btn-agregar" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Agregar
                            </button>
                            <?php else: ?>
                            <!-- Texto explicativo para produtos sem preço -->
                            <div class="text-center mb-2">
                                <small class="text-info d-block" style="font-size: 11px; line-height: 1.3; font-weight: 500;">
                                    <i class="fas fa-info-circle me-1"></i>Precio a consultar<br>
                                    <span class="text-muted">Agregar al carrito y cotizar</span>
                                </small>
                            </div>
                            <!-- Botão para adicionar ao carrinho (mesmo para produtos sem preço) -->
                            <button type="button" class="btn-agregar" data-product-id="<?php echo $product['id']; ?>" data-no-price="true">
                                <i class="fas fa-shopping-cart"></i> Agregar al carrito
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No hay productos disponibles en esta categoría.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- CONTADOR DE PRODUTOS EM ESPANHOL (SEM BOTÃO) -->
        <div class="text-center my-4" id="product-counter">
            <small class="text-muted">
                <span id="products-loaded">20</span> de <?php echo $total_products; ?> productos cargados
            </small>
        </div>
        
        <!-- Indicador de carregamento -->
        <div class="loading-spinner" id="loading-spinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando más productos...</p>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <a href="index.php" class="footer-icon active">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="categorias.php" class="footer-icon">
            <i class="fas fa-list"></i>
            <span>Categorías</span>
        </a>
        <a href="carrinho.php" class="footer-icon position-relative">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrito</span>
            <span class="cart-count footer-cart-count" id="footer-cart-count">0</span>
        </a>
    </div>
    
    <!-- Toast para notificações -->
    <div class="toast-container">
        <div class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" id="toast-notification">
            <div class="d-flex">
                <div class="toast-body">
                    Producto agregado al carrito
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script do carrinho -->
    <script src="assets/js/cart.js"></script>
    
    <?php 
    // Incluir HTML e JavaScript do pop-up se houver pop-up ativo
    if (shouldShowPopup($conn)) {
        echo generatePopupHTML($conn);
        echo generatePopupJS();
    }
    ?>
    
    <!-- Script personalizado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Script iniciado - DOMContentLoaded');
            
            // Verificar se elementos necessários existem
            const productGrid = document.getElementById('products-grid');
            const loadingSpinner = document.getElementById('loading-spinner');
            
            console.log('🔍 Elementos encontrados:', {
                productGrid: !!productGrid,
                loadingSpinner: !!loadingSpinner
            });
            
            // Variáveis para controle de paginação
            let currentPage = 1;
            let loading = false;
            let hasMore = <?php echo ($total_products > $items_per_page) ? 'true' : 'false'; ?>;
            const currentCategory = <?php echo $category_id; ?>;
            
            console.log('⚙️ Variáveis iniciais:', {
                currentPage,
                loading,
                hasMore,
                currentCategory,
                totalProducts: <?php echo $total_products; ?>,
                itemsPerPage: <?php echo $items_per_page; ?>
            });
            
            // ============ CORREÇÃO DEFINITIVA - SCROLL INFINITO E IMAGENS ============
            
            // Inicializar lazy loading para imagens de fundo (ORIGINAL)
            function initLazyLoading() {
                const lazyImages = document.querySelectorAll('.lazy-bg');
                
                const lazyImageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target;
                            const bgImage = lazyImage.getAttribute('data-bg');
                            if (bgImage) {
                                lazyImage.style.backgroundImage = `url('${bgImage}')`;
                                lazyImage.classList.remove('lazy-bg');
                                observer.unobserve(lazyImage);
                            }
                        }
                    });
                });
                
                lazyImages.forEach(lazyImage => {
                    lazyImageObserver.observe(lazyImage);
                });
            }
            
            // Configurar botões de contato (ORIGINAL)
            function setupContactSellerButtons() {
                // Função placeholder - pode ser expandida conforme necessário
                console.log('📞 Botões de contato configurados');
            }
            
            // SCROLL INFINITO CORRIGIDO - versão ultra-simples
            function setupInfiniteScroll() {
                console.log('🚀 Iniciando scroll infinito CORRIGIDO');
                
                // Verificação automática a cada 800ms
                const scrollCheck = setInterval(() => {
                    if (!hasMore || loading) {
                        if (!hasMore) {
                            clearInterval(scrollCheck);
                            console.log('✅ Todos os produtos carregados');
                        }
                        return;
                    }
                    
                    const scrollY = window.scrollY;
                    const windowHeight = window.innerHeight;
                    const documentHeight = document.documentElement.scrollHeight;
                    
                    // Trigger aos 75% da página
                    const scrollPercent = (scrollY + windowHeight) / documentHeight;
                    
                    if (scrollPercent > 0.75) {
                        console.log('🔥 75% atingido - carregando mais produtos');
                        loadMoreProducts();
                    }
                }, 800);
                
                // Carregamento inicial forçado após 2 segundos
                setTimeout(() => {
                    if (hasMore && !loading) {
                        console.log('⚡ Carregamento inicial forçado');
                        loadMoreProducts();
                    }
                }, 2000);
            }
            
            // Função para carregar mais produtos (CORRIGIDA)
            function loadMoreProducts() {
                if (loading || !hasMore) return;
                
                loading = true;
                const nextPage = currentPage + 1;
                
                console.log(`📦 Carregando página ${nextPage}`);
                
                // Mostrar spinner se existir
                if (loadingSpinner) loadingSpinner.style.display = 'block';
                
                fetch(`load_more_products.php?page=${nextPage}&category=${currentCategory}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('✅ Dados recebidos:', data);
                        
                        if (loadingSpinner) loadingSpinner.style.display = 'none';
                        
                        if (data.products && data.products.length > 0) {
                            currentPage = nextPage;
                            appendProducts(data.products, data.enable_global_minimums);
                            hasMore = data.has_more;
                            
                            // Atualizar contador (CORRIGIDO)
                            const totalCarregados = currentPage * <?php echo $items_per_page; ?>;
                            const contador = document.querySelector('#product-counter small');
                            if (contador) {
                                const produtosCarregados = Math.min(totalCarregados, <?php echo $total_products; ?>);
                                contador.innerHTML = `<span id="products-loaded">${produtosCarregados}</span> de <?php echo $total_products; ?> productos cargados`;
                            }
                            
                            console.log(`✅ Página ${nextPage} carregada. Mais produtos: ${hasMore}`);
                        } else {
                            hasMore = false;
                            console.log('❌ No hay más productos');
                        }
                        
                        loading = false;
                    })
                    .catch(error => {
                        console.error('❌ Erro ao carregar:', error);
                        if (loadingSpinner) loadingSpinner.style.display = 'none';
                        loading = false;
                    });
            }
            
            // Função para adicionar produtos (SISTEMA ORIGINAL CORRIGIDO)
            function appendProducts(products, globalMinimumsEnabled = true) {
                const productGrid = document.getElementById('products-grid');
                if (!productGrid) {
                    console.error('❌ Grid de produtos não encontrado');
                    return;
                }
                
                products.forEach(product => {
                    // Determinar classes CSS baseadas nos flags
                    let cardClasses = 'product-card';
                    if (product.promotion) cardClasses += ' promotion';
                    if (product.featured) cardClasses += ' featured';
                    
                    // Construir HTML do preço dinamicamente
                    let priceHtml = '';
                    if (product.show_price && product.wholesale_price) {
                        priceHtml = `<p class="product-mayorista">${product.wholesale_price_formatted}</p>`;
                    } else {
                        priceHtml = `<p class="product-mayorista text-info"><i class="fas fa-phone me-1"></i>Consultar con el vendedor</p>`;
                    }
                    
                    // Indicador de variações disponíveis
                    let variationsHtml = '';
                    if (product.variations_count && product.variations_count > 0) {
                        variationsHtml = `<p class="product-variations-indicator" style="font-size: 11px; color: #28a745; margin: 2px 0;">
                            <i class="fas fa-layer-group me-1"></i>${product.variations_count} variaciones disponibles
                        </p>`;
                    }
                    
                    // Sistema de quantidades mínimas
                    const hasMinQuantity = product.has_min_quantity;
                    const minQuantity = product.min_wholesale_quantity || 1;
                    let minQtyHtml = '';
                    
                    if (hasMinQuantity && globalMinimumsEnabled) {
                        const unitDisplay = product.unit_type === 'kg' ? product.unit_display_name : 'unidades';
                        minQtyHtml = `<p class="product-min-qty">Mínimo: ${minQuantity} ${unitDisplay}</p>`;
                    }
                    
                    // Controles de compra
                    const effectiveMinimum = (hasMinQuantity && globalMinimumsEnabled) ? minQuantity : 1;
                    let buyControlsHtml = `
                        <div class="quantity-control">
                            <button type="button" class="btn-qty-minus" data-product-id="${product.id}">-</button>
                            <input type="number" min="${effectiveMinimum}" value="${effectiveMinimum}" class="product-quantity" data-product-id="${product.id}" inputmode="numeric">
                            <button type="button" class="btn-qty-plus" data-product-id="${product.id}">+</button>
                        </div>
                    `;
                    
                    if (product.show_price && product.wholesale_price) {
                        buyControlsHtml += `
                            <button type="button" class="btn-agregar" data-product-id="${product.id}">
                                <i class="fas fa-shopping-cart"></i> Agregar
                            </button>
                        `;
                    } else {
                        buyControlsHtml += `
                            <div class="text-center mb-2">
                                <small class="text-info d-block" style="font-size: 11px; line-height: 1.3; font-weight: 500;">
                                    <i class="fas fa-info-circle me-1"></i>Precio a consultar<br>
                                    <span class="text-muted">Agregar al carrito y cotizar</span>
                                </small>
                            </div>
                            <button type="button" class="btn-agregar" data-product-id="${product.id}" data-no-price="true">
                                <i class="fas fa-shopping-cart"></i> Agregar al carrito
                            </button>
                        `;
                    }
                    
                    // HTML do produto (SISTEMA ORIGINAL COM LAZY LOADING)
                    const productHtml = `
                        <div class="col product-item">
                            <div class="${cardClasses}" data-product-id="${product.id}">
                                <a href="produto.php?id=${product.id}" class="text-decoration-none">
                                    <div class="product-image lazy-bg" data-bg="${product.image_url}"></div>
                                </a>
                                <div class="product-info">
                                    <a href="produto.php?id=${product.id}" class="text-decoration-none">
                                        <h5 class="product-title">${product.name}</h5>
                                    </a>
                                    
                                    <div class="price-container">
                                        ${priceHtml}
                                        ${variationsHtml}
                                        ${minQtyHtml}
                                    </div>
                                    
                                    ${buyControlsHtml}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Usar DocumentFragment para melhor performance
                    const temp = document.createElement('template');
                    temp.innerHTML = productHtml.trim();
                    productGrid.appendChild(temp.content.firstChild);
                });
                
                // Reinicializar lazy loading para as novas imagens
                initLazyLoading();
                
                // ⚡ CORREÇÃO CRÍTICA: Aplicar event listeners aos novos botões
                if (typeof setupAddToCartButtons === 'function') {
                    setupAddToCartButtons();
                    console.log('🛒 Event listeners aplicados aos novos botões');
                } else {
                    console.warn('⚠️ setupAddToCartButtons não encontrada');
                }
                
                console.log(`✅ ${products.length} produtos adicionados ao grid`);
            }
            
            // Inicializar lazy loading para imagens de fundo
            initLazyLoading();
            
            // Configurar botões de contato para produtos sem preço
            setupContactSellerButtons();
            
            // Inicializar quando houver mais produtos
            if (hasMore) {
                setupInfiniteScroll();
            }

            // ============ SISTEMA DE BUSCA EM TEMPO REAL ============
            
            function initSearchSystem() {
                const searchInput = document.getElementById('product-search');
                const searchResults = document.getElementById('search-results');
                const searchClear = document.querySelector('.search-clear');
                const searchBtn = document.querySelector('.search-btn');
                
                if (!searchInput || !searchResults) {
                    console.log('⚠️ Elementos de busca não encontrados');
                    return;
                }
                
                let searchTimeout;
                let currentSearchTerm = '';
                
                // Busca em tempo real conforme digita
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.trim();
                    currentSearchTerm = searchTerm;
                    
                    // Mostrar/esconder botão limpar
                    if (searchTerm.length > 0) {
                        searchClear.style.display = 'flex';
                    } else {
                        searchClear.style.display = 'none';
                        hideSearchResults();
                        return;
                    }
                    
                    // Debounce - aguardar 300ms antes de buscar
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (searchTerm.length >= 1) {
                            performSearch(searchTerm);
                        }
                    }, 300);
                });
                
                // Limpar busca
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    currentSearchTerm = '';
                    this.style.display = 'none';
                    hideSearchResults();
                    searchInput.focus();
                });
                
                // Buscar ao clicar no botão
                searchBtn.addEventListener('click', function() {
                    const searchTerm = searchInput.value.trim();
                    if (searchTerm.length >= 1) {
                        performSearch(searchTerm);
                    }
                });
                
                // Buscar ao pressionar Enter
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const searchTerm = this.value.trim();
                        if (searchTerm.length >= 1) {
                            performSearch(searchTerm);
                        }
                    }
                    
                    // Navegar pelos resultados com setas
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        navigateSearchResults(e.key === 'ArrowDown' ? 'down' : 'up');
                    }
                    
                    // Escape para fechar
                    if (e.key === 'Escape') {
                        hideSearchResults();
                    }
                });
                
                // Fechar resultados ao clicar fora
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.search-container')) {
                        hideSearchResults();
                    }
                });
                
                console.log('🔍 Sistema de busca inicializado');
            }
            
            // Realizar busca AJAX
            function performSearch(searchTerm) {
                const searchResults = document.getElementById('search-results');
                
                // Mostrar loading
                showSearchLoading();
                
                // Fazer requisição AJAX
                fetch(`search_products.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('🔍 Resultados da busca:', data);
                        displaySearchResults(data.products, searchTerm);
                    })
                    .catch(error => {
                        console.error('❌ Erro na busca:', error);
                        showSearchError();
                    });
            }
            
            // Mostrar loading
            function showSearchLoading() {
                const searchResults = document.getElementById('search-results');
                searchResults.innerHTML = `
                    <div class="search-loading">
                        <div class="spinner"></div>
                        <div style="margin-top: 8px;">Buscando productos...</div>
                    </div>
                `;
                searchResults.classList.add('show');
            }
            
            // Mostrar erro
            function showSearchError() {
                const searchResults = document.getElementById('search-results');
                searchResults.innerHTML = `
                    <div class="search-no-results">
                        <i class="fas fa-exclamation-triangle mb-2"></i><br>
                        Error al buscar. Inténtalo de nuevo.
                    </div>
                `;
                searchResults.classList.add('show');
            }
            
            // Exibir resultados da busca
            function displaySearchResults(products, searchTerm) {
                const searchResults = document.getElementById('search-results');
                
                if (!products || products.length === 0) {
                    searchResults.innerHTML = `
                        <div class="search-no-results">
                            <i class="fas fa-search mb-2" style="font-size: 24px; color: #ddd;"></i><br>
                            No encontramos productos con "<strong>${searchTerm}</strong>"<br>
                            <small>Probá con otras palabras</small>
                        </div>
                    `;
                    searchResults.classList.add('show');
                    return;
                }
                
                let resultsHTML = '';
                const maxResults = 8; // Máximo 8 resultados
                const displayProducts = products.slice(0, maxResults);
                
                displayProducts.forEach((product, index) => {
                    const imageUrl = product.image_url || 'assets/images/default-logo.png';
                    const priceText = product.show_price && product.wholesale_price_formatted 
                        ? product.wholesale_price_formatted 
                        : 'Consultar precio';
                    
                    const singleClass = displayProducts.length === 1 ? 'single' : '';
                    
                    resultsHTML += `
                        <a href="produto.php?id=${product.id}" class="search-result-item ${singleClass}">
                            <img src="${imageUrl}" alt="${product.name}" class="search-result-image" loading="lazy">
                            <div class="search-result-info">
                                <p class="search-result-name">${highlightSearchTerm(product.name, searchTerm)}</p>
                                <p class="search-result-price">${priceText}</p>
                            </div>
                        </a>
                    `;
                });
                
                // Mostrar link "Ver todos" se houver mais resultados
                if (products.length > maxResults) {
                    resultsHTML += `
                        <div class="search-result-item" style="justify-content: center; background: #f8f9fa; font-weight: 500; color: #007bff;">
                            <i class="fas fa-plus-circle me-2"></i>
                            Ver todos los ${products.length} resultados
                        </div>
                    `;
                }
                
                searchResults.innerHTML = resultsHTML;
                searchResults.classList.add('show');
            }
            
            // Destacar termo da busca
            function highlightSearchTerm(text, searchTerm) {
                if (!searchTerm) return text;
                
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                return text.replace(regex, '<strong style="color: #007bff;">$1</strong>');
            }
            
            // Navegar pelos resultados com teclado
            function navigateSearchResults(direction) {
                const results = document.querySelectorAll('.search-result-item');
                if (results.length === 0) return;
                
                const current = document.querySelector('.search-result-item.active');
                let newIndex = 0;
                
                if (current) {
                    const currentIndex = Array.from(results).indexOf(current);
                    current.classList.remove('active');
                    
                    if (direction === 'down') {
                        newIndex = currentIndex + 1 >= results.length ? 0 : currentIndex + 1;
                    } else {
                        newIndex = currentIndex - 1 < 0 ? results.length - 1 : currentIndex - 1;
                    }
                } else {
                    newIndex = direction === 'down' ? 0 : results.length - 1;
                }
                
                results[newIndex].classList.add('active');
                results[newIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            // Esconder resultados
            function hideSearchResults() {
                const searchResults = document.getElementById('search-results');
                searchResults.classList.remove('show');
                
                // Remover classe active
                document.querySelectorAll('.search-result-item.active').forEach(item => {
                    item.classList.remove('active');
                });
            }
            
            // Inicializar sistema de busca
            initSearchSystem();
        });
    </script>