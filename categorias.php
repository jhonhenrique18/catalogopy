<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir conexão com banco de dados
require_once 'includes/db_connect.php';

// Incluir funções utilitárias  
require_once 'includes/functions.php';

// Incluir funções de câmbio e formatação
require_once 'includes/exchange_functions.php';

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
    // Obter todas as categorias principais
    $query = "SELECT * FROM categories WHERE parent_id IS NULL AND status = 1 ORDER BY name";
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

    // Obter possíveis subcategorias
    $query = "SELECT * FROM categories WHERE parent_id = ? AND status = 1 ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $subcategories_result = $stmt->get_result();

    $showing_all_categories = false;
}

// Função para gerar cor baseada no nome da categoria
function generateColorFromName($name) {
    $colors = [
        '#27AE60', // Verde
        '#3498DB', // Azul
        '#E74C3C', // Vermelho
        '#F39C12', // Laranja
        '#9B59B6', // Roxo
        '#1ABC9C', // Turquesa
        '#D35400', // Laranja escuro
        '#2980B9', // Azul escuro
        '#8E44AD', // Roxo escuro
        '#16A085', // Verde escuro
    ];
    
    // Usar o hash do nome para selecionar uma cor
    $hash = crc32($name);
    $index = abs($hash % count($colors));
    
    return $colors[$index];
}

// Função para selecionar ícone baseado no nome da categoria
function selectIconForCategory($name) {
    $name = strtolower($name);
    
    $iconMap = [
        'fruta' => 'fa-apple-whole',
        'frutas' => 'fa-apple-whole',
        'legume' => 'fa-carrot',
        'legumes' => 'fa-carrot',
        'verdura' => 'fa-leaf',
        'verduras' => 'fa-leaf',
        'grão' => 'fa-seedling',
        'grãos' => 'fa-seedling',
        'cereal' => 'fa-wheat-awn',
        'cereais' => 'fa-wheat-awn',
        'tempero' => 'fa-mortar-pestle',
        'temperos' => 'fa-mortar-pestle',
        'especiaria' => 'fa-pepper-hot',
        'especiarias' => 'fa-pepper-hot',
        'semente' => 'fa-seedling',
        'sementes' => 'fa-seedling',
        'chá' => 'fa-mug-hot',
        'chás' => 'fa-mug-hot',
        'bebida' => 'fa-glass-water',
        'bebidas' => 'fa-glass-water',
        'orgânico' => 'fa-leaf',
        'orgânicos' => 'fa-leaf',
        'natural' => 'fa-seedling',
        'naturais' => 'fa-seedling',
        'integral' => 'fa-wheat-awn',
        'integrais' => 'fa-wheat-awn',
        'sem glúten' => 'fa-wheat-awn-slash',
        'vegano' => 'fa-leaf',
        'veganos' => 'fa-leaf',
        'vegetariano' => 'fa-leaf',
        'vegetarianos' => 'fa-leaf',
    ];
    
    // Verificar se alguma palavra-chave do nome da categoria corresponde a um ícone
    foreach ($iconMap as $keyword => $icon) {
        if (strpos($name, $keyword) !== false) {
            return $icon;
        }
    }
    
    // Ícone padrão se nenhuma correspondência for encontrada
    return 'fa-folder';
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
        
        .category-card {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        
        .category-card:active {
            transform: scale(0.98);
        }
        
        .category-image {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }
        
        .category-info {
            padding: 12px;
            text-align: center;
            background-color: white;
        }
        
        .category-title {
            font-size: var(--font-size-medium);
            margin: 0;
            font-weight: 600;
            color: var(--color-gray-dark);
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        
        .product-card:active {
            transform: scale(0.98);
        }
        
        .product-image {
            height: 150px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #fff;
            border-bottom: 1px solid var(--color-gray-light);
        }
        
        .product-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        
        .product-title {
            font-size: var(--font-size-medium);
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--color-gray-dark);
            height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .price-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 15px;
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
            margin-bottom: 8px;
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
            padding-top: 5px;
            border-top: 1px dashed var(--color-gray-medium);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            justify-content: center;
        }
        
        .quantity-control button {
            background-color: var(--color-gray-light);
            border: none;
            width: 44px;
            height: 44px;
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
            width: 60px;
            height: 44px;
            text-align: center;
            border: 1px solid var(--color-gray-medium);
            margin: 0 8px;
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
        
        /* Breadcrumb personalizado */
        .custom-breadcrumb {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px 0;
            margin-bottom: 15px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .custom-breadcrumb::-webkit-scrollbar {
            display: none;
        }
        
        .custom-breadcrumb .breadcrumb-item {
            display: inline-block;
            font-size: 14px;
        }
        
        .custom-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            padding: 0 8px;
            color: var(--color-gray-dark);
        }
        
        .custom-breadcrumb a {
            color: var(--color-primary);
            text-decoration: none;
        }
        
        .custom-breadcrumb .active {
            color: var(--color-gray-dark);
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
        
        .category-section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--color-gray-dark);
            padding-bottom: 8px;
            border-bottom: 2px solid var(--color-primary-light);
        }
        
        @media (max-width: 767px) {
            .product-image {
                height: 150px;
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
            <div class="custom-breadcrumb">
                <div class="breadcrumb-item"><a href="categorias.php">Categorías</a></div>
                <?php if ($parent_category): ?>
                    <div class="breadcrumb-item"><a href="categorias.php?id=<?php echo $parent_category['id']; ?>"><?php echo htmlspecialchars($parent_category['name']); ?></a></div>
                <?php endif; ?>
                <div class="breadcrumb-item active"><?php echo htmlspecialchars($category['name']); ?></div>
            </div>
            
            <h1 class="h3 mb-4"><?php echo htmlspecialchars($category['name']); ?></h1>
            
            <!-- Subcategorias, se existirem -->
            <?php if ($subcategories_result->num_rows > 0): ?>
                <h2 class="category-section-title">Subcategorías</h2>
                <div class="row row-cols-2 row-cols-md-3 g-3 mb-4">
                    <?php while ($subcategory = $subcategories_result->fetch_assoc()): 
                        $subcategoryColor = generateColorFromName($subcategory['name']);
                        $subcategoryIcon = selectIconForCategory($subcategory['name']);
                    ?>
                        <div class="col">
                            <a href="categorias.php?id=<?php echo $subcategory['id']; ?>" class="text-decoration-none">
                                <div class="category-card">
                                    <div class="category-image" style="background-color: <?php echo $subcategoryColor; ?>">
                                        <i class="fas <?php echo $subcategoryIcon; ?>"></i>
                                    </div>
                                    <div class="category-info">
                                        <h3 class="category-title"><?php echo htmlspecialchars($subcategory['name']); ?></h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
            <!-- Produtos da categoria - MESMA LÓGICA DO INDEX.PHP -->
            <h2 class="category-section-title">Productos</h2>
            <?php if ($products_result->num_rows > 0): ?>
                <div class="row row-cols-2 row-cols-md-3 g-3" id="products-grid">
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
                                    <div class="product-image lazy-bg" data-bg="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png'; ?>" style="background-image: url('<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png'; ?>')"></div>
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
                                        
                                        <!-- Quantidade mínima - só exibe se configurado -->
                                        <?php if ($global_minimums_enabled && $has_min_quantity && !empty($product['min_wholesale_quantity'])): ?>
                                            <p class="product-min-qty">Mínimo: <?php echo $product['min_wholesale_quantity']; ?> <?php echo $unit_type == 'kg' ? htmlspecialchars($unit_display) : 'unidades'; ?></p>
                                            <?php if (!empty($store['minimum_explanation_text'])): ?>
                                            <small class="text-muted d-block" style="font-size: 10px; line-height: 1.2; margin-top: -2px;">
                                                <?php echo htmlspecialchars($store['minimum_explanation_text']); ?>
                                            </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Não mostrar cálculo "por ml" - vendemos por unidade, não por ml -->
                                        <?php // Removido cálculo de preço por ml pois vendemos por unidade, não por ml ?>
                                    </div>
                                    
                                    <!-- Controles de compra - sempre exibe contador, mas comportamento diferente -->
                                    <div class="quantity-control">
                                        <?php 
                                        // Determinar quantidade mínima baseada na configuração global
                                        $effective_min_quantity = ($global_minimums_enabled && $has_min_quantity) ? 
                                            intval($product['min_wholesale_quantity'] ?: 1) : 1;
                                        ?>
                                        <button type="button" class="btn-qty-minus" data-product-id="<?php echo $product['id']; ?>">-</button>
                                        <input type="number" min="<?php echo $effective_min_quantity; ?>" value="<?php echo $effective_min_quantity; ?>" class="product-quantity" data-product-id="<?php echo $product['id']; ?>" inputmode="numeric">
                                        <button type="button" class="btn-qty-plus" data-product-id="<?php echo $product['id']; ?>">+</button>
                                    </div>
                                    
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
            <!-- Mostrar todas las categorías principales -->
            <h1 class="h3 mb-4">Categorías</h1>
            
            <div class="row row-cols-2 row-cols-md-3 g-3">
                <?php while ($category = $categories_result->fetch_assoc()): 
                    $categoryColor = generateColorFromName($category['name']);
                    $categoryIcon = selectIconForCategory($category['name']);
                ?>
                    <div class="col">
                        <a href="categorias.php?id=<?php echo $category['id']; ?>" class="text-decoration-none">
                            <div class="category-card">
                                <div class="category-image" style="background-color: <?php echo $categoryColor; ?>">
                                    <i class="fas <?php echo $categoryIcon; ?>"></i>
                                </div>
                                <div class="category-info">
                                    <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                                </div>
                            </div>
                        </a>
                    </div>
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
    
    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de carrinho centralizado -->
    <script src="assets/js/cart.js"></script>
</body>
</html>
