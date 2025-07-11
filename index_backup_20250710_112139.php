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
if ($category_id > 0) {
    $query_products = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, unit_type, unit_display_name, image_url, featured, promotion 
                      FROM products 
                      WHERE status = 1 AND category_id = ? 
                      ORDER BY promotion DESC, featured DESC, name ASC 
                      LIMIT ?";
    
    $stmt = $conn->prepare($query_products);
    $stmt->bind_param("ii", $category_id, $items_per_page);
} else {
    $query_products = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, unit_type, unit_display_name, image_url, featured, promotion 
                      FROM products 
                      WHERE status = 1 
                      ORDER BY promotion DESC, featured DESC, name ASC 
                      LIMIT ?";
    
    $stmt = $conn->prepare($query_products);
    $stmt->bind_param("i", $items_per_page);
}

// Executar consulta
$stmt->execute();
$result_products = $stmt->get_result();

// Consulta para contar o total de produtos (para navegação)
if ($category_id > 0) {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1 AND category_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $category_id);
} else {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
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
    </style>
</head>
<body>
    <?php 
    // Incluir barra rotativa se houver mensagens ativas
    echo generateRotatingBannerHTML($conn);
    ?>
    
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
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No hay productos disponibles en esta categoría.
                    </div>
                </div>
            <?php endif; ?>
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
            // Variáveis para controle de paginação
            let currentPage = 1;
            let loading = false;
            let hasMore = <?php echo ($total_products > $items_per_page) ? 'true' : 'false'; ?>;
            const currentCategory = <?php echo $category_id; ?>;
            
            // Inicializar lazy loading para imagens de fundo
            initLazyLoading();
            
            // Configurar botões de contato para produtos sem preço
            setupContactSellerButtons();
            
            // Infinite scroll
            window.addEventListener('scroll', function() {
                if (loading || !hasMore) return;
                
                // Verificar se chegou próximo ao final da página
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
                    loadMoreProducts();
                }
            });
            
            // Função para carregar mais produtos
            function loadMoreProducts() {
                loading = true;
                
                // Incrementar página apenas após validação
                const nextPage = currentPage + 1;
                
                // Mostrar indicador de carregamento
                document.getElementById('loading-spinner').style.display = 'block';
                
                // Fazer requisição AJAX para carregar mais produtos
                fetch(`load_more_products.php?page=${nextPage}&category=${currentCategory}`)
                    .then(response => response.json())
                    .then(data => {
                        // Esconder indicador de carregamento
                        document.getElementById('loading-spinner').style.display = 'none';
                        
                        if (data.products.length > 0) {
                            // APENAS atualizar currentPage se produtos foram carregados com sucesso
                            currentPage = nextPage;
                            
                            // Adicionar produtos ao grid
                            appendProducts(data.products);
                            
                            // Atualizar status de paginação
                            hasMore = data.has_more;
                        } else {
                            hasMore = false;
                        }
                        
                        loading = false;
                    })
                    .catch(error => {
                        console.error('Erro ao carregar mais produtos:', error);
                        document.getElementById('loading-spinner').style.display = 'none';
                        loading = false;
                    });
            }
            
            // Função para adicionar produtos ao DOM
            function appendProducts(products) {
                const productGrid = document.getElementById('products-grid');
                
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
                    
                    // Exibir quantidade mínima só se configurado
                    let minQtyHtml = '';
                    if (product.has_min_quantity && product.min_wholesale_quantity) {
                        const unitDisplay = product.unit_type === 'kg' ? product.unit_display_name : 'unidades';
                        minQtyHtml = `<p class="product-min-qty">Mínimo: ${product.min_wholesale_quantity} ${unitDisplay}</p>`;
                    }
                    
                    // Não mostrar cálculo "por ml" - vendemos por unidade, não por ml
                    let unitPriceHtml = ''; // Removido cálculo incorreto
                    
                    // Controles de compra - sempre exibe contador
                    const minValue = product.has_min_quantity ? (product.min_wholesale_quantity || 1) : 1;
                    let buyControlsHtml = `
                        <div class="quantity-control">
                            <button type="button" class="btn-qty-minus" data-product-id="${product.id}">-</button>
                            <input type="number" min="${minValue}" value="${minValue}" class="product-quantity" data-product-id="${product.id}" inputmode="numeric">
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
                        // Produto SEM preço - botão para adicionar ao carrinho
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
                                        ${minQtyHtml}
                                        ${unitPriceHtml}
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
                
                // Reinicializar eventos para os novos produtos
                setupQuantityButtons();
                setupAddToCartButtons();
                setupContactSellerButtons();
                
                // Reinicializar lazy loading para as novas imagens
                initLazyLoading();
            }
            
            // Inicializar lazy loading para imagens de fundo
            function initLazyLoading() {
                const lazyBackgrounds = document.querySelectorAll('.lazy-bg:not(.loaded)');
                
                if ('IntersectionObserver' in window) {
                    let lazyBackgroundObserver = new IntersectionObserver(function(entries, observer) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                let lazyBackground = entry.target;
                                lazyBackground.style.backgroundImage = `url('${lazyBackground.dataset.bg}')`;
                                lazyBackground.classList.add('loaded');
                                lazyBackgroundObserver.unobserve(lazyBackground);
                            }
                        });
                    });
                    
                    lazyBackgrounds.forEach(function(lazyBackground) {
                        lazyBackgroundObserver.observe(lazyBackground);
                    });
                } else {
                    // Fallback para navegadores que não suportam IntersectionObserver
                    lazyBackgrounds.forEach(function(lazyBackground) {
                        lazyBackground.style.backgroundImage = `url('${lazyBackground.dataset.bg}')`;
                        lazyBackground.classList.add('loaded');
                    });
                }
            }
            
            // Configurar botões de contato com vendedor
            function setupContactSellerButtons() {
                // Atualizar todos os links de WhatsApp quando quantidades mudam
                document.querySelectorAll('.contact-seller-btn').forEach(button => {
                    const productId = button.closest('.product-card').dataset.productId;
                    const quantityInput = document.querySelector(`.product-quantity[data-product-id="${productId}"]`);
                    
                    if (quantityInput) {
                        // Função para atualizar o link
                        const updateLink = () => {
                            const quantity = parseInt(quantityInput.value) || 1;
                            const productName = button.getAttribute('data-product-name');
                            const unit = button.getAttribute('data-unit');
                            
                            const message = `Produto ${productName}, quantidade ${quantity} ${unit}, preço a ser consultado pelo vendedor`;
                            const whatsappNumber = button.href.match(/wa\.me\/(\d+)/)[1];
                            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
                            
                            button.href = whatsappUrl;
                        };
                        
                        // Atualizar quando quantidade muda
                        quantityInput.addEventListener('input', updateLink);
                        quantityInput.addEventListener('change', updateLink);
                        
                        // Atualizar agora
                        updateLink();
                    }
                });
                
                // Eventos para botões de quantidade
                document.querySelectorAll('.btn-qty-minus, .btn-qty-plus').forEach(button => {
                    button.addEventListener('click', function() {
                        // Aguardar um frame para o input ser atualizado
                        setTimeout(() => {
                            const productId = this.getAttribute('data-product-id');
                            const contactBtn = document.querySelector(`.contact-seller-btn[data-product-name]`);
                            const quantityInput = document.querySelector(`.product-quantity[data-product-id="${productId}"]`);
                            
                            if (contactBtn && quantityInput && contactBtn.closest('.product-card').dataset.productId === productId) {
                                const quantity = parseInt(quantityInput.value) || 1;
                                const productName = contactBtn.getAttribute('data-product-name');
                                const unit = contactBtn.getAttribute('data-unit');
                                
                                const message = `Produto ${productName}, quantidade ${quantity} ${unit}, preço a ser consultado pelo vendedor`;
                                const whatsappNumber = contactBtn.href.match(/wa\.me\/(\d+)/)[1];
                                const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
                                
                                contactBtn.href = whatsappUrl;
                            }
                        }, 10);
                    });
                });
            }
            
            // Formatar número
            function formatNumber(number) {
                return new Intl.NumberFormat('es-PY').format(number);
            }
        });
    </script>
</body>
</html>
