<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir conexão com banco de dados
require_once 'includes/db_connect.php';

// Incluir funções utilitárias  
require_once 'includes/functions.php';

// Incluir funções de câmbio e formatação
require_once 'includes/exchange_functions.php';

// Incluir funções de pop-up
require_once 'includes/popup_functions.php';

// Incluir funções da barra rotativa
require_once 'includes/rotating_banner_functions.php';

// INCLUIR SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS
require_once 'includes/minimum_quantity_functions.php';

// Incluir configurações da loja (frete, mínimos, etc.)
require_once 'includes/store_config.php';

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Configurações globais de sistema - IGUAL AO INDEX.PHP
$global_minimums_enabled = isset($store['enable_global_minimums']) ? (bool)$store['enable_global_minimums'] : true;

// Verificar se foi passado um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Obter todas as categorias principais com contagem de produtos e ordenação por quantidade
    $query = "SELECT c.*, 
              COUNT(p.id) as product_count
              FROM categories c
              LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
              WHERE c.parent_id IS NULL AND c.status = 1 
              GROUP BY c.id
              ORDER BY product_count DESC, c.name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $categories_result = $stmt->get_result();
    
    $showing_all_categories = true;
} else {
    $category_id = (int) $_GET['id'];

    // Obter informações da categoria
    $query = "SELECT * FROM categories WHERE id = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se a categoria não existir ou estiver inativa, mostrar todas as categorias
    if ($result->num_rows === 0) {
        header('Location: categorias.php');
        exit;
    }

    $category = $result->fetch_assoc();

    // Obter categoria pai, se existir
    $parent_category = null;
    if (!empty($category['parent_id'])) {
        $query = "SELECT * FROM categories WHERE id = ? AND status = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $category['parent_id']);
        $stmt->execute();
        $parent_result = $stmt->get_result();
        if ($parent_result->num_rows > 0) {
            $parent_category = $parent_result->fetch_assoc();
        }
    }

    // Obter produtos da categoria - MESMA LÓGICA DO INDEX.PHP
    $query_products = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, unit_type, unit_display_name, image_url, featured, promotion, show_price, has_min_quantity, category_id 
                      FROM products 
                      WHERE category_id = ? AND status = 1 
                      ORDER BY featured DESC, promotion DESC, name ASC";
    $stmt = $conn->prepare($query_products);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $products_result = $stmt->get_result();

    // Obter possíveis subcategorias com contagem de produtos
    $query = "SELECT c.*, 
              COUNT(p.id) as product_count
              FROM categories c
              LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
              WHERE c.parent_id = ? AND c.status = 1 
              GROUP BY c.id
              ORDER BY product_count DESC, c.name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $subcategories_result = $stmt->get_result();

    $showing_all_categories = false;
}

// Função para gerar cor baseada no nome da categoria (otimizada para mercado paraguaio)
function generateColorFromName($name) {
    // Primeiro, tentar mapeamento específico para categorias paraguaias
    $name_lower = strtolower(trim($name));
    
    // Correção automática para nomes corrompidos UTF-8
    $name_corrections = [
        'tôδε-rs' => 'tés',
        'tôδε' => 'té',
        'açúcar' => 'azúcar',
        'grão' => 'grano',
        'feijão' => 'frijol',
        'óleos' => 'aceites',
        'temperos' => 'condimentos',
        'doces' => 'dulces',
        'farinhas' => 'harinas',
        'cereais' => 'cereales',
        'biscoitos' => 'galletas',
        'carnes' => 'carnes',
        'laticínios' => 'lácteos',
        'bebidas' => 'bebidas',
        'conservas' => 'conservas',
        'congelados' => 'congelados',
        'higiene' => 'higiene',
        'limpeza' => 'limpieza'
    ];
    
    // Aplicar correções se necessário
    foreach ($name_corrections as $corrupted => $corrected) {
        if (strpos($name_lower, $corrupted) !== false) {
            $name_lower = str_replace($corrupted, $corrected, $name_lower);
        }
    }
    
    // Paleta de cores específica para categorias paraguaias
    $specific_colors = [
        // Alimentos básicos
        'arroz' => '#8BC34A',
        'frijol' => '#795548',
        'maíz' => '#FFC107',
        'trigo' => '#FF9800',
        'harina' => '#FFEB3B',
        'azúcar' => '#E1F5FE',
        'sal' => '#F5F5F5',
        
        // Proteínas
        'carne' => '#D32F2F',
        'pollo' => '#FF9800',
        'pescado' => '#00BCD4',
        'huevo' => '#FFEB3B',
        'leche' => '#FFFFFF',
        'queso' => '#FFF9C4',
        'yogurt' => '#E8F5E8',
        
        // Frutas e vegetais
        'fruta' => '#4CAF50',
        'verdura' => '#2E7D32',
        'legumbre' => '#8BC34A',
        'tomate' => '#F44336',
        'cebolla' => '#9C27B0',
        'zanahoria' => '#FF9800',
        'papa' => '#8D6E63',
        'banana' => '#FFEB3B',
        'manzana' => '#F44336',
        'naranja' => '#FF9800',
        
        // Bebidas
        'agua' => '#2196F3',
        'refresco' => '#4CAF50',
        'jugo' => '#FF9800',
        'té' => '#4CAF50',
        'café' => '#5D4037',
        'yerba' => '#4CAF50',
        'cerveza' => '#FFC107',
        'vino' => '#9C27B0',
        
        // Condimentos e especias
        'aceite' => '#FFC107',
        'vinagre' => '#795548',
        'condimento' => '#FF5722',
        'especia' => '#FF9800',
        'sal' => '#F5F5F5',
        'pimienta' => '#424242',
        'ajo' => '#FFFFFF',
        'perejil' => '#4CAF50',
        
        // Dulces y postres
        'dulce' => '#E91E63',
        'chocolate' => '#5D4037',
        'caramelo' => '#FF9800',
        'galleta' => '#8D6E63',
        'torta' => '#E91E63',
        'helado' => '#81D4FA',
        
        // Productos de limpieza e higiene
        'limpieza' => '#2196F3',
        'jabón' => '#81C784',
        'detergente' => '#4FC3F7',
        'shampoo' => '#BA68C8',
        'pasta' => '#81C784',
        'papel' => '#FFF9C4',
        
        // Otros productos
        'conserva' => '#FF9800',
        'enlatado' => '#616161',
        'congelado' => '#81D4FA',
        'cereales' => '#FF9800',
        'snack' => '#FF5722',
        'golosina' => '#E91E63'
    ];
    
    // Verificar se há uma cor específica para a categoria
    foreach ($specific_colors as $keyword => $color) {
        if (strpos($name_lower, $keyword) !== false) {
            return $color;
        }
    }
    
    // Paleta de cores diversificada como fallback
    $fallback_colors = [
        '#27AE60', '#3498DB', '#E74C3C', '#F39C12', '#9B59B6', '#1ABC9C', 
        '#D35400', '#2980B9', '#8E44AD', '#16A085', '#E67E22', '#34495E',
        '#F1C40F', '#E74C3C', '#9B59B6', '#3498DB', '#2ECC71', '#E67E22',
        '#95A5A6', '#F39C12', '#D35400', '#C0392B', '#8E44AD', '#2980B9',
        '#27AE60', '#16A085', '#F1C40F', '#E67E22', '#9B59B6', '#3498DB'
    ];
    
    // Usar o hash do nome para selecionar uma cor
    $hash = crc32($name);
    $index = abs($hash % count($fallback_colors));
    
    return $fallback_colors[$index];
}

// Função para selecionar ícone baseado no nome da categoria (otimizada para mercado paraguaio)
function selectIconForCategory($name) {
    $name_lower = strtolower(trim($name));
    
    // Mapeamento específico de ícones para categorias paraguaias
    $iconMap = [
        // Alimentos básicos
        'arroz' => 'fa-seedling',
        'frijol' => 'fa-seedling',
        'maíz' => 'fa-corn',
        'trigo' => 'fa-wheat-awn',
        'harina' => 'fa-wheat-awn',
        'azúcar' => 'fa-cube',
        'sal' => 'fa-cube',
        
        // Proteínas
        'carne' => 'fa-drumstick-bite',
        'pollo' => 'fa-drumstick-bite',
        'pescado' => 'fa-fish',
        'huevo' => 'fa-egg',
        'leche' => 'fa-milk-alt',
        'queso' => 'fa-cheese',
        'yogurt' => 'fa-ice-cream',
        
        // Frutas y vegetales
        'fruta' => 'fa-apple-alt',
        'verdura' => 'fa-carrot',
        'legumbre' => 'fa-seedling',
        'tomate' => 'fa-apple-alt',
        'cebolla' => 'fa-onion',
        'zanahoria' => 'fa-carrot',
        'papa' => 'fa-potato',
        'banana' => 'fa-banana',
        'manzana' => 'fa-apple-alt',
        'naranja' => 'fa-lemon',
        
        // Bebidas
        'agua' => 'fa-tint',
        'refresco' => 'fa-glass-water',
        'jugo' => 'fa-glass-water',
        'té' => 'fa-mug-hot',
        'tés' => 'fa-mug-hot',
        'café' => 'fa-coffee',
        'yerba' => 'fa-leaf',
        'cerveza' => 'fa-beer',
        'vino' => 'fa-wine-glass',
        
        // Condimentos y especias
        'aceite' => 'fa-oil-can',
        'vinagre' => 'fa-bottle-droplet',
        'condimento' => 'fa-pepper-hot',
        'especia' => 'fa-mortar-pestle',
        'pimienta' => 'fa-pepper-hot',
        'ajo' => 'fa-onion',
        'perejil' => 'fa-leaf',
        
        // Dulces y postres
        'dulce' => 'fa-candy-cane',
        'chocolate' => 'fa-cookie-bite',
        'caramelo' => 'fa-candy-cane',
        'galleta' => 'fa-cookie',
        'torta' => 'fa-birthday-cake',
        'helado' => 'fa-ice-cream',
        
        // Productos de limpieza e higiene
        'limpieza' => 'fa-spray-can',
        'jabón' => 'fa-soap',
        'detergente' => 'fa-bottle-droplet',
        'shampoo' => 'fa-pump-soap',
        'pasta' => 'fa-toothbrush',
        'papel' => 'fa-toilet-paper',
        
        // Otros productos
        'conserva' => 'fa-can-food',
        'enlatado' => 'fa-can-food',
        'congelado' => 'fa-snowflake',
        'cereales' => 'fa-bowl-food',
        'snack' => 'fa-cookie-bite',
        'golosina' => 'fa-candy-cane'
    ];
    
    // Verificar se alguma palavra-chave do nome da categoria corresponde a um ícone
    foreach ($iconMap as $keyword => $icon) {
        if (strpos($name_lower, $keyword) !== false) {
            return $icon;
        }
    }
    
    // Ícone padrão se nenhuma correspondência for encontrada
    return 'fa-tags';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($category) ? htmlspecialchars($category['name']) : 'Categorías'; ?> - <?php echo htmlspecialchars($store['store_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS personalizado das categorias -->
    <link rel="stylesheet" href="assets/css/categories.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Configurações da Loja para JavaScript -->
    <?php 
    echo generateStoreConfigScript($store_config);
    ?>
    
    <!-- SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS -->
    <?php 
    echo generateMinimumQuantityCSS();
    echo generateMinimumQuantityJavaScript();
    ?>
    
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

        .navbar-brand img {
            max-height: 40px;
        }

        /* ===== PRODUTOS - MESMO ESTILO DO INDEX.PHP ===== */
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

        .product-card.promotion.featured::after {
            top: 32px;
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
        }

        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .btn-qty-minus, .btn-qty-plus {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-qty-minus:hover, .btn-qty-plus:hover {
            background-color: var(--color-primary-dark);
        }

        .product-quantity {
            width: 60px;
            text-align: center;
            border: 1px solid var(--color-gray-medium);
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            margin: 0 8px;
            padding: 6px 4px;
        }

        .btn-agregar {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            height: 44px;
            width: 100%;
            font-size: 16px;
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

        @keyframes cartPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .navbar {
            background-color: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        /* ===== BARRA DE PESQUISA - MESMO ESTILO DO INDEX.PHP ===== */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 2px;
            transition: all 0.3s ease;
        }

        .search-wrapper:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        .search-input {
            border: none;
            outline: none;
            background: transparent;
            padding: 8px 15px;
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .search-input::placeholder {
            color: #6c757d;
        }

        .search-btn, .search-clear {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover, .search-clear:hover {
            background-color: var(--color-primary-dark);
            transform: scale(1.05);
        }

        .search-clear {
            background-color: #dc3545;
            margin-left: 5px;
        }

        .search-clear:hover {
            background-color: #c82333;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-results.show {
            display: block;
        }

        .search-result-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .search-result-price {
            color: var(--color-primary);
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .search-container {
                max-width: 250px;
            }
            
            .search-input {
                font-size: 13px;
                padding: 6px 10px;
            }
            
            .search-btn, .search-clear {
                width: 32px;
                height: 32px;
            }
        }

        .lazy-bg {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #fff;
        }

        .toast-container {
            position: fixed;
            top: 60px;
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
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="categorias.php">Categorías</a>
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
        <?php if (!$showing_all_categories): ?>
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="categorias.php">Categorías</a></li>
                    <?php if ($parent_category): ?>
                        <li class="breadcrumb-item"><a href="categorias.php?id=<?php echo $parent_category['id']; ?>"><?php echo htmlspecialchars($parent_category['name']); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
                </ol>
            </nav>
            
            <h1 class="h3 mb-4"><?php echo htmlspecialchars($category['name']); ?></h1>
            
            <!-- Subcategorias, se existirem -->
            <?php if ($subcategories_result->num_rows > 0): ?>
                <h2 class="h4 mb-3">Subcategorías</h2>
                <div class="categories-grid mb-4">
                    <?php while ($subcategory = $subcategories_result->fetch_assoc()): 
                        $subcategoryColor = generateColorFromName($subcategory['name']);
                        $subcategoryIcon = selectIconForCategory($subcategory['name']);
                        $subcategoryProductCount = $subcategory['product_count'] ?? 0;
                        
                        $subDisplayType = $subcategory['display_type'] ?? 'icon';
                        $subIconName = $subcategory['icon_name'] ?? selectIconForCategory($subcategory['name']);
                        $subTitleDisplay = !empty($subcategory['title_display']) ? $subcategory['title_display'] : $subcategory['name'];
                        $subImageUrl = $subcategory['image_url'] ?? '';
                    ?>
                        <a href="categorias.php?id=<?php echo $subcategory['id']; ?>" class="text-decoration-none">
                            <div class="category-card modern-category">
                                <?php if ($subDisplayType === 'image' && !empty($subImageUrl) && file_exists($subImageUrl)): ?>
                                    <div class="category-image category-image-bg" style="background-image: url('<?php echo htmlspecialchars($subImageUrl); ?>');">
                                        <div class="category-overlay">
                                            <div class="category-text">
                                                <h3 class="category-title-overlay"><?php echo htmlspecialchars($subTitleDisplay); ?></h3>
                                                <p class="category-subtitle-overlay">
                                                    <?php echo $subcategoryProductCount; ?> producto<?php echo $subcategoryProductCount != 1 ? 's' : ''; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if ($subcategoryProductCount > 0): ?>
                                            <span class="product-count-badge"><?php echo $subcategoryProductCount; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="category-image" style="background-color: <?php echo $subcategoryColor; ?>">
                                        <i class="fas <?php echo $subIconName; ?>"></i>
                                        <?php if ($subcategoryProductCount > 0): ?>
                                            <span class="product-count-badge"><?php echo $subcategoryProductCount; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="category-info">
                                        <h3 class="category-title"><?php echo htmlspecialchars($subTitleDisplay); ?></h3>
                                        <p class="category-subtitle">
                                            <?php echo $subcategoryProductCount; ?> producto<?php echo $subcategoryProductCount != 1 ? 's' : ''; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
            <!-- Produtos da categoria -->
            <h2 class="h4 mb-3">Productos</h2>
            <?php if ($products_result->num_rows > 0): ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2" id="products-grid">
                    <?php while ($product = $products_result->fetch_assoc()): 
                        // Obter configurações de unidade - IGUAL AO INDEX.PHP
                        $unit_type = $product['unit_type'] ?? 'kg';
                        $unit_display = $product['unit_display_name'] ?? 'kg';
                        $unit_weight = $product['unit_weight'] ?? '1.00';
                        $show_price = $product['show_price'] ?? 1;
                        $has_min_quantity = $product['has_min_quantity'] ?? 1;
                        $contact_seller_text = $store['contact_seller_text'] ?? 'Consultar con el vendedor';
                        
                        // Determinar classes CSS baseadas nos flags
                        $card_classes = 'product-card';
                        if (!empty($product['promotion'])) $card_classes .= ' promotion';
                        if (!empty($product['featured'])) $card_classes .= ' featured';
                    ?>
                        <div class="col product-item">
                            <div class="<?php echo $card_classes; ?>" data-product-id="<?php echo $product['id']; ?>">
                                <a href="produto.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <div class="product-image lazy-bg" data-bg="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png'; ?>"></div>
                                </a>
                                <div class="product-info">
                                    <a href="produto.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    </a>
                                    
                                    <div class="price-container">
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
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No hay productos disponibles en esta categoría.
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Mostrar todas las categorías principais -->
            <div class="page-header mb-4">
                <h1 class="page-title">Nuestras Categorías</h1>
                <p class="page-subtitle">Encuentra productos de calidad organizados por categorías</p>
            </div>
            
            <div class="categories-grid">
                <?php while ($category = $categories_result->fetch_assoc()): 
                    $categoryColor = generateColorFromName($category['name']);
                    $categoryIcon = selectIconForCategory($category['name']);
                    $productCount = $category['product_count'] ?? 0;
                    
                    $displayType = $category['display_type'] ?? 'icon';
                    $iconName = $category['icon_name'] ?? selectIconForCategory($category['name']);
                    $titleDisplay = !empty($category['title_display']) ? $category['title_display'] : $category['name'];
                    $imageUrl = $category['image_url'] ?? '';
                ?>
                    <a href="categorias.php?id=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="category-card modern-category">
                            <?php if ($displayType === 'image' && !empty($imageUrl) && file_exists($imageUrl)): ?>
                                <div class="category-image category-image-bg" style="background-image: url('<?php echo htmlspecialchars($imageUrl); ?>');">
                                    <div class="category-overlay">
                                        <div class="category-text">
                                            <h3 class="category-title-overlay"><?php echo htmlspecialchars($titleDisplay); ?></h3>
                                            <p class="category-subtitle-overlay">
                                                <?php echo $productCount; ?> producto<?php echo $productCount != 1 ? 's' : ''; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($productCount > 0): ?>
                                        <span class="product-count-badge"><?php echo $productCount; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="category-image" style="background-color: <?php echo $categoryColor; ?>">
                                    <i class="fas <?php echo $iconName; ?>"></i>
                                    <?php if ($productCount > 0): ?>
                                        <span class="product-count-badge"><?php echo $productCount; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="category-info">
                                    <h3 class="category-title"><?php echo htmlspecialchars($titleDisplay); ?></h3>
                                    <p class="category-subtitle">
                                        <?php echo $productCount; ?> produto<?php echo $productCount != 1 ? 's' : ''; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <a href="index.php" class="footer-icon">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="categorias.php" class="footer-icon active">
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de carrinho centralizado -->
    <script src="assets/js/cart.js"></script>
    
    <!-- Sistema de categorias otimizado -->
    <script src="assets/js/categories.js"></script>
    
    <!-- JavaScript do Index.php - EXATO -->
    <script>
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
        
        // Lazy loading para imagens
        function initLazyLoading() {
            const lazyImages = document.querySelectorAll('.lazy-bg');
            
            if ('IntersectionObserver' in window) {
                const lazyImageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target;
                            const bgImage = lazyImage.getAttribute('data-bg');
                            if (bgImage) {
                                lazyImage.style.backgroundImage = `url('${bgImage}')`;
                                lazyImage.classList.add('loaded');
                                observer.unobserve(lazyImage);
                            }
                        }
                    });
                });
                
                lazyImages.forEach(lazyImage => {
                    lazyImageObserver.observe(lazyImage);
                });
            } else {
                // Fallback para browsers sem IntersectionObserver
                lazyImages.forEach(lazyImage => {
                    const bgImage = lazyImage.getAttribute('data-bg');
                    if (bgImage) {
                        lazyImage.style.backgroundImage = `url('${bgImage}')`;
                        lazyImage.classList.add('loaded');
                    }
                });
            }
        }
        
        // Inicializar quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar sistema de busca
            initSearchSystem();
            
            // Inicializar lazy loading
            initLazyLoading();
            
            // Sistema de categorias se existir
            if (typeof CategoryManager !== 'undefined') {
                const categoryManager = new CategoryManager();
                console.log('📂 Sistema de categorias inicializado');
            }
        });
    </script>
</body>
</html> 