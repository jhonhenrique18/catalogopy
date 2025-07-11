<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Incluir funções utilitárias
require_once 'includes/functions.php';

// Incluir funções de câmbio
require_once 'includes/exchange_functions.php';

// Verificar se foi fornecido um ID de produto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirecionar para a página inicial se não houver ID
    header('Location: index.php');
    exit;
}

$product_id = (int) $_GET['id'];

// Consultar dados do produto
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ? AND p.status = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se o produto existe
if ($result->num_rows === 0) {
    // Produto não encontrado, redirecionar para a página inicial
    header('Location: index.php');
    exit;
}

// Obter dados do produto
$product = $result->fetch_assoc();

// Detectar se é produto pai ou variação e carregar variações relevantes
$parent_product = null;
$variations = [];
$current_variation = null;

if (!empty($product['parent_product_id'])) {
    // Este é uma variação - carregar produto pai e todas as variações irmãs
    $parent_id = $product['parent_product_id'];
    $current_variation = $product;
    
    // Carregar produto pai
    $parent_query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = ? AND p.status = 1";
    $parent_stmt = $conn->prepare($parent_query);
    $parent_stmt->bind_param("i", $parent_id);
    $parent_stmt->execute();
    $parent_result = $parent_stmt->get_result();
    
    if ($parent_result->num_rows > 0) {
        $parent_product = $parent_result->fetch_assoc();
    }
    
    // Carregar todas as variações (incluindo esta)
    $variations_query = "SELECT * FROM products 
                        WHERE parent_product_id = ? AND status = 1 
                        ORDER BY variation_display";
    $variations_stmt = $conn->prepare($variations_query);
    $variations_stmt->bind_param("i", $parent_id);
    $variations_stmt->execute();
    $variations_result = $variations_stmt->get_result();
    
    while ($variation = $variations_result->fetch_assoc()) {
        $variations[] = $variation;
    }
    
} else {
    // Este é um produto pai - carregar suas variações (se houver)
    $parent_product = $product;
    
    $variations_query = "SELECT * FROM products 
                        WHERE parent_product_id = ? AND status = 1 
                        ORDER BY variation_display";
    $variations_stmt = $conn->prepare($variations_query);
    $variations_stmt->bind_param("i", $product_id);
    $variations_stmt->execute();
    $variations_result = $variations_stmt->get_result();
    
    while ($variation = $variations_result->fetch_assoc()) {
        $variations[] = $variation;
    }
}

// Obter configurações da loja
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$store = $result->fetch_assoc();

// Consultar produtos relacionados (mesma categoria)
$query_related = "SELECT * FROM products 
                 WHERE category_id = ? AND id != ? AND status = 1 
                 ORDER BY name LIMIT 6";
$stmt = $conn->prepare($query_related);
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$result_related = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($store['store_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS moderno personalizado -->
    <style>
        :root {
            --color-primary: #27AE60;
            --color-primary-dark: #219653;
            --color-primary-light: #6FCF97;
            --color-secondary: #3498DB;
            --color-danger: #E74C3C;
            --color-warning: #F39C12;
            --color-dark: #2C3E50;
            --color-light: #ECF0F1;
            --color-gray-100: #F8F9FA;
            --color-gray-200: #E9ECEF;
            --color-gray-300: #DEE2E6;
            --color-gray-400: #CED4DA;
            --color-gray-500: #ADB5BD;
            --color-gray-600: #6C757D;
            --color-gray-700: #495057;
            --color-gray-800: #343A40;
            --color-gray-900: #212529;
            --gradient-primary: linear-gradient(135deg, #27AE60 0%, #2ECC71 100%);
            --gradient-secondary: linear-gradient(135deg, #3498DB 0%, #5DADE2 100%);
            --gradient-card: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.2);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --font-primary: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-primary);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-bottom: 80px;
            touch-action: manipulation;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* ===== NAVBAR MODERNA ===== */
        .navbar {
            background: var(--gradient-card) !important;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid var(--color-gray-200);
            padding: 8px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand img {
            max-height: 45px;
            transition: var(--transition);
        }
        
        .navbar-brand img:hover {
            transform: scale(1.05);
        }
        
        .cart-btn {
            background: var(--gradient-primary);
            border: none;
            border-radius: 25px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        
        .cart-btn:hover {
            background: var(--gradient-secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        .cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }
        
        .cart-btn:hover::before {
            left: 100%;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--color-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            animation: cartPulse 0.6s ease-out;
        }
        
        /* ============ BARRA DE PESQUISA MODERNA ============ */
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
            border: 2px solid var(--color-gray-300);
            border-radius: 25px;
            font-size: 15px;
            background: var(--color-gray-100);
            transition: var(--transition);
            outline: none;
        }
        
        .search-input:focus {
            border-color: var(--color-primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .search-input::placeholder {
            color: var(--color-gray-600);
            font-style: italic;
        }
        
        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--color-primary);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-btn:hover {
            background: var(--color-primary-dark);
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
            color: var(--color-primary);
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
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)) !important;
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
            color: var(--color-primary);
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
            color: var(--color-primary);
        }
        
        /* ANIMAÇÃO DO LOADING */
        .search-loading .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--color-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ===== CONTAINER PRINCIPAL ===== */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 15px;
            background: var(--color-light);
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px 10px;
            }
        }
        
        /* ===== CARD DE PRODUTO ===== */
        .product-card {
            background: var(--gradient-card);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        /* ===== GALERIA DE IMAGENS ===== */
        .image-gallery {
            position: relative;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 350px;
        }
        
        .main-image-container {
            position: relative;
            width: 100%;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: zoom-in;
            overflow: hidden;
        }
        
        .main-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: var(--transition);
            border-radius: 8px;
        }
        
        .main-image-container:hover .main-image {
            transform: scale(1.05);
        }
        
        .zoom-indicator {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            opacity: 0;
            transition: var(--transition);
        }
        
        .main-image-container:hover .zoom-indicator {
            opacity: 1;
        }
        
        /* ===== INFORMAÇÕES DO PRODUTO ===== */
        .product-info {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .product-info {
                padding: 20px 15px;
                gap: 15px;
            }
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-dark);
            margin: 0;
            line-height: 1.3;
        }
        
        @media (max-width: 768px) {
            .product-title {
                font-size: 24px;
            }
        }
        
        .product-category {
            display: inline-flex;
            align-items: center;
            background: var(--gradient-primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            align-self: flex-start;
        }
        
        .product-category:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        /* ===== VARIAÇÕES ===== */
        .variations-card {
            background: var(--color-gray-100);
            border-radius: var(--border-radius);
            padding: 20px;
            border-left: 4px solid var(--color-primary);
        }
        
        .variations-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .variations-title i {
            margin-right: 8px;
            color: var(--color-primary);
        }
        
        .variations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        
        @media (max-width: 576px) {
            .variations-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .variation-option {
            background: white;
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius);
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 13px;
        }
        
        .variation-option:hover {
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .variation-option.active {
            background: var(--gradient-primary);
            color: white;
            border-color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* ===== SEÇÃO DE PREÇOS ===== */
        .price-section {
            text-align: center;
            background: var(--gradient-light);
            padding: 25px;
            border-radius: var(--border-radius);
            border: 2px solid var(--color-primary-light);
        }
        
        .price-main {
            font-size: 32px;
            font-weight: 800;
            color: var(--color-primary);
            margin-bottom: 10px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .price-main {
                font-size: 28px;
            }
        }
        
        .price-contact {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* ===== QUANTIDADE MÍNIMA ===== */
        .min-quantity {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-warning-light);
            color: var(--color-warning-dark);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 600;
            gap: 8px;
        }
        
        /* ===== CONTROLE DE QUANTIDADE ===== */
        .quantity-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-dark);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            background: white;
            border-radius: var(--border-radius);
            border: 2px solid var(--color-gray-300);
            overflow: hidden;
        }
        
        .qty-btn {
            width: 50px;
            height: 50px;
            border: none;
            background: var(--color-gray-200);
            color: var(--color-dark);
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: var(--color-primary);
            color: white;
        }
        
        .qty-btn:active {
            transform: scale(0.95);
        }
        
        .qty-input {
            width: 80px;
            height: 50px;
            border: none;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: var(--color-dark);
            background: white;
        }
        
        .qty-input:focus {
            outline: none;
            background: var(--color-gray-100);
        }
        
        /* ===== BOTÕES DE AÇÃO ===== */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        @media (min-width: 576px) {
            .action-buttons {
                flex-direction: row;
                justify-content: center;
            }
        }
        
        .btn-modern {
            padding: 16px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-add-cart {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .btn-add-cart:hover {
            background: var(--gradient-secondary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: white;
        }
        
        .btn-back {
            background: var(--color-gray-200);
            color: var(--color-gray-700);
        }
        
        .btn-back:hover {
            background: var(--color-gray-300);
            transform: translateY(-2px);
            color: var(--color-gray-800);
        }
        
        .btn-modern i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* ===== DETALHES DO PRODUTO ===== */
        .details-card {
            background: var(--gradient-card);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .details-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .details-title i {
            margin-right: 10px;
            color: var(--color-primary);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--color-gray-200);
            transition: var(--transition);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item:hover {
            background: var(--color-gray-100);
            margin: 0 -15px;
            padding: 12px 15px;
            border-radius: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--color-gray-700);
            display: flex;
            align-items: center;
        }
        
        .detail-label i {
            margin-right: 8px;
            color: var(--color-primary);
            width: 16px;
        }
        
        .detail-value {
            font-weight: 500;
            color: var(--color-dark);
        }
        
        /* ===== DESCRIÇÃO ===== */
        .description-card {
            background: var(--gradient-card);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .description-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .description-title i {
            margin-right: 10px;
            color: var(--color-primary);
        }
        
        .description-text {
            line-height: 1.6;
            color: var(--color-gray-700);
            font-size: 16px;
        }
        
        /* ===== PRODUTOS RELACIONADOS ===== */
        .related-section {
            margin-top: 40px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-dark);
            margin-bottom: 25px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--color-primary);
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            justify-items: center;
        }
        
        @media (max-width: 768px) {
            .related-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .related-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
        }
        
        .related-card {
            background: var(--gradient-card);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            width: 100%;
            max-width: 280px;
            display: flex;
            flex-direction: column;
        }
        
        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            color: inherit;
        }
        
        .related-image {
            height: 160px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: var(--color-gray-100);
            border-bottom: 1px solid var(--color-gray-200);
        }
        
        @media (max-width: 576px) {
            .related-image {
                height: 120px;
            }
        }
        
        .related-info {
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
            text-align: center;
        }
        
        .related-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--color-dark);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: auto;
        }
        
        .related-price {
            font-weight: 700;
            font-size: 16px;
            color: var(--color-primary);
            margin-top: auto;
        }
        
        .related-contact {
            font-weight: 600;
            font-size: 12px;
            color: var(--color-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin-top: auto;
        }
        
        /* ===== FOOTER MODERNO ===== */
        .footer-modern {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--gradient-card);
            backdrop-filter: blur(10px);
            box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
            border-top: 1px solid var(--color-gray-200);
            padding: 12px 0;
            display: flex;
            justify-content: space-around;
            z-index: 1000;
        }
        
        .footer-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--color-gray-600);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            position: relative;
        }
        
        .footer-icon:hover {
            color: var(--color-primary);
            background: var(--color-gray-100);
            transform: translateY(-2px);
        }
        
        .footer-icon i {
            font-size: 22px;
            margin-bottom: 4px;
        }
        
        .footer-cart-count {
            position: absolute;
            top: 3px;
            right: 8px;
            background: var(--color-danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            animation: cartPulse 0.6s ease-out;
        }
        
        /* ===== TOAST MODERNO ===== */
        .toast-container {
            position: fixed;
            bottom: 90px;
            right: 20px;
            z-index: 1050;
        }
        
        .toast-modern {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            min-width: 300px;
        }
        
        .toast-body {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            font-weight: 500;
        }
        
        .toast-body i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* ===== ANIMAÇÕES ===== */
        @keyframes cartPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        
        /* ===== RESPONSIVIDADE MOBILE ===== */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px 10px;
            }
            
            .product-card {
                margin-bottom: 20px;
            }
            
            .image-gallery {
                padding: 20px;
                min-height: 300px;
            }
            
            .main-image {
                max-height: 250px;
            }
            
            .product-info {
                padding: 20px;
            }
            
            .product-title {
                font-size: 24px;
            }
            
            .price-main {
                font-size: 28px;
            }
            
            .variations-grid {
                grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
                gap: 8px;
            }
            
            .variation-option {
                padding: 10px 12px;
                font-size: 13px;
                min-height: 40px;
            }
            
            .related-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
            }
            
            .related-image {
                height: 150px;
            }
            
            .section-title {
                font-size: 22px;
            }
            
            .search-container {
                min-width: 180px;
                max-width: 220px;
            }
            
            .search-input {
                padding: 8px 40px 8px 12px;
                font-size: 13px;
            }
            
            .search-btn {
                width: 28px;
                height: 28px;
                right: 2px;
                font-size: 12px;
            }
            
            .search-clear {
                width: 18px;
                height: 18px;
                right: 32px;
                font-size: 8px;
            }
            
            .search-results {
                max-height: 250px;
            }
            
            .search-result-item {
                padding: 8px 12px;
            }
            
            .search-result-image {
                width: 30px;
                height: 30px;
                margin-right: 8px;
            }
            
            .search-result-name {
                font-size: 12px;
            }
            
            .search-result-price {
                font-size: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .product-title {
                font-size: 20px;
            }
            
            .price-main {
                font-size: 24px;
            }
            
            .variations-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .related-grid {
                grid-template-columns: 1fr;
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
        /* ===== ANIMAÇÕES DO CARRINHO ===== */
        .cart-count, .footer-cart-count {
            opacity: 1;
            transform: scale(1);
            transition: all 0.3s ease;
        }
        
        .cart-count[style*="display: none"], 
        .footer-cart-count[style*="display: none"] {
            opacity: 0;
            transform: scale(0.8);
        }
        
        @keyframes cartPulse {
            0% { 
                transform: scale(1); 
                background-color: var(--color-danger);
            }
            50% { 
                transform: scale(1.2); 
                background-color: #ff4757;
            }
            100% { 
                transform: scale(1); 
                background-color: var(--color-danger);
            }
        }
        
        .footer-cart-count {
            animation-duration: 0.6s;
            animation-timing-function: ease-out;
        }

        /* ===== CONTADOR DO FOOTER SEMPRE VISÍVEL ===== */
        .footer-cart-count {
            background-color: var(--color-danger);
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 700;
            display: flex !important; /* Sempre visível */
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -5px;
            right: -5px;
            z-index: 10;
            transition: all 0.3s ease;
            opacity: 1;
        }
        
        /* Styling quando tem itens */
        .footer-cart-count.has-items {
            background-color: #ff4757;
            box-shadow: 0 0 10px rgba(255, 71, 87, 0.5);
            transform: scale(1.1);
        }
        
        /* Styling quando está zerado - mais sutil mas visível */
        .footer-cart-count:not(.has-items) {
            background-color: #95a5a6;
            opacity: 0.7;
            transform: scale(0.9);
        }
    </style>
</head>
<body>
    <!-- Navbar Moderna -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-2">
            <a class="navbar-brand me-2 me-md-3" href="index.php">
                <?php if (!empty($store['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($store['logo_url']); ?>" alt="<?php echo htmlspecialchars($store['store_name']); ?>">
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($store['store_name']); ?></strong>
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
            
            <!-- Área direita com carrinho -->
            <div class="d-flex align-items-center">
                <a href="carrinho.php" class="cart-btn position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Carrito</span>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Container Principal -->
    <div class="main-container">
        <!-- Card Principal do Produto -->
        <div class="product-card animate-slide-up">
            <div class="row g-0">
                <!-- Galeria de Imagens -->
                <div class="col-lg-6">
                    <div class="image-gallery">
                        <div class="main-image-container">
                            <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="main-image"
                                 loading="lazy">
                            <div class="zoom-indicator">
                                <i class="fas fa-search-plus"></i> Toque para ampliar
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Produto -->
                <div class="col-lg-6">
                    <div class="product-info">
                        <!-- Título e Categoria -->
                        <h1 class="product-title"><?php echo htmlspecialchars($parent_product['name']); ?></h1>
                        
                        <a href="categorias.php?id=<?php echo $product['category_id']; ?>" class="product-category">
                            <i class="fas fa-tag me-1"></i> 
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </a>
                        
                        <!-- Seletor de Variações -->
                        <?php if (!empty($variations)): ?>
                        <div class="variations-card animate-fade-in">
                            <h6 class="variations-title">
                                <i class="fas fa-layer-group"></i>Seleccionar tamaño:
                            </h6>
                            <div class="variations-grid">
                                <?php foreach ($variations as $variation): ?>
                                <button type="button" 
                                        class="variation-option <?php echo ($variation['id'] == $product['id']) ? 'active' : ''; ?>"
                                        data-variation-id="<?php echo $variation['id']; ?>"
                                        data-variation-name="<?php echo htmlspecialchars($variation['name']); ?>"
                                        data-variation-price="<?php echo $variation['wholesale_price']; ?>"
                                        data-variation-min-qty="<?php echo $variation['min_wholesale_quantity']; ?>"
                                        data-variation-weight="<?php echo $variation['unit_weight']; ?>"
                                        data-variation-display="<?php echo htmlspecialchars($variation['variation_display']); ?>"
                                        data-variation-show-price="<?php echo $variation['show_price'] ?? 1; ?>"
                                        data-variation-has-min="<?php echo $variation['has_min_quantity'] ?? 1; ?>">
                                    <span><?php echo htmlspecialchars($variation['variation_display']); ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $unit_type = $product['unit_type'] ?? 'kg';
                        $unit_display = $product['unit_display_name'] ?? 'kg';
                        $unit_weight = $product['unit_weight'] ?? '1.00';
                        $show_price = $product['show_price'] ?? 1;
                        $has_min_quantity = $product['has_min_quantity'] ?? 1;
                        $contact_seller_text = $store['contact_seller_text'] ?? 'Consultar con el vendedor';
                        ?>
                        
                        <!-- Seção de Preços -->
                        <div class="price-section">
                            <?php if ($show_price && !empty($product['wholesale_price'])): ?>
                            <div class="price-main"><?php echo formatPriceInGuaranis($product['wholesale_price']); ?></div>
                            <?php else: ?>
                                <div class="price-contact">
                                    <i class="fas fa-phone"></i><?php echo htmlspecialchars($contact_seller_text); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quantidade Mínima -->
                        <?php if ($has_min_quantity && !empty($product['min_wholesale_quantity'])): ?>
                            <div class="min-quantity">
                                <i class="fas fa-info-circle"></i>
                                <span>Mínimo: <?php echo intval($product['min_wholesale_quantity']); ?> <?php echo $unit_type == 'kg' ? htmlspecialchars($unit_display) : 'unidades'; ?></span>
                            </div>
                            
                            <?php if ($unit_type == 'unit' && $unit_display == 'ml'): ?>
                                <?php 
                                // Extrair conteúdo em ml do nome do produto
                                if (preg_match('/(\d+)\s?ml/i', $product['name'], $matches)) {
                                    $ml_content = $matches[1];
                                    echo '<div class="min-quantity"><i class="fas fa-flask"></i><span>Conteúdo: ' . $ml_content . 'ml por unidade</span></div>';
                                }
                                ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Controle de Quantidade -->
                        <div class="quantity-section">
                            <div class="quantity-label">Cantidad</div>
                            <div class="quantity-control">
                                <button type="button" class="qty-btn" id="btn-qty-minus">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       min="<?php echo $has_min_quantity ? intval($product['min_wholesale_quantity'] ?: 1) : 1; ?>" 
                                       value="<?php echo $has_min_quantity ? intval($product['min_wholesale_quantity'] ?: 1) : 1; ?>" 
                                       id="product-quantity" 
                                       class="qty-input"
                                       inputmode="numeric">
                                <button type="button" class="qty-btn" id="btn-qty-plus">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação -->
                        <div class="action-buttons">
                            <?php if ($show_price): ?>
                            <!-- Botão normal para produtos com preço -->
                            <button type="button" class="btn-modern btn-add-cart" id="btn-agregar">
                                <i class="fas fa-shopping-cart"></i> Agregar al Carrito
                            </button>
                            <?php else: ?>
                            <!-- Botão para produto sem preço -->
                            <button type="button" class="btn-modern btn-add-cart" data-product-id="<?php echo $product['id']; ?>" data-no-price="true" id="btn-agregar">
                                <i class="fas fa-shopping-cart"></i> Agregar para Cotizar
                            </button>
                            <?php endif; ?>
                            
                            <a href="index.php" class="btn-modern btn-back">
                                <i class="fas fa-chevron-left"></i> Volver al Catálogo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detalhes do Produto -->
        <div class="details-card animate-slide-up">
            <h3 class="details-title">
                <i class="fas fa-info-circle"></i>Detalles del Producto
            </h3>
            
            <?php if ($unit_type == 'kg'): ?>
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-weight"></i>Peso por unidad:
                    </span>
                    <span class="detail-value"><?php echo number_format(floatval($unit_weight), 2, ',', '.'); ?> kg</span>
                </div>
            <?php else: ?>
                <!-- Para produtos por unidade, mostrar peso para frete separado do conteúdo -->
                <div class="detail-item">
                    <span class="detail-label">
                        <i class="fas fa-shipping-fast"></i>Peso para envío:
                    </span>
                    <span class="detail-value"><?php echo number_format(floatval($unit_weight), 2, ',', '.'); ?> kg por unidad</span>
                </div>
                <?php if ($unit_display == 'ml' && preg_match('/(\d+)\s?ml/i', $product['name'], $matches)): ?>
            <div class="detail-item">
                        <span class="detail-label">
                            <i class="fas fa-flask"></i>Contenido:
                        </span>
                        <span class="detail-value"><?php echo $matches[1]; ?>ml por unidad</span>
            </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="detail-item">
                <span class="detail-label">
                    <i class="fas fa-check-circle"></i>Disponibilidad:
                </span>
                <span class="detail-value" style="color: <?php echo $product['stock'] > 0 ? 'var(--color-primary)' : 'var(--color-danger)'; ?>">
                    <?php echo $product['stock'] > 0 ? 'En stock' : 'Agotado'; ?>
                </span>
            </div>
            
            <?php if (!empty($product['category_name'])): ?>
            <div class="detail-item">
                <span class="detail-label">
                    <i class="fas fa-folder"></i>Categoría:
                </span>
                <span class="detail-value"><?php echo htmlspecialchars($product['category_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Descrição do Produto -->
        <?php if (!empty($product['description'])): ?>
        <div class="description-card animate-slide-up">
            <h3 class="description-title">
                <i class="fas fa-align-left"></i>Descripción
            </h3>
            <div class="description-text">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Produtos Relacionados -->
        <?php if ($result_related->num_rows > 0): ?>
        <div class="related-section animate-slide-up">
            <h2 class="section-title">
                <i class="fas fa-heart"></i>Productos Relacionados
            </h2>
            
            <div class="related-grid">
                <?php while ($related = $result_related->fetch_assoc()): ?>
                <a href="produto.php?id=<?php echo $related['id']; ?>" class="related-card">
                    <div class="related-image" style="background-image: url('<?php echo !empty($related['image_url']) ? htmlspecialchars($related['image_url']) : 'assets/images/no-image.png'; ?>')"></div>
                    <div class="related-info">
                        <div class="related-name"><?php echo htmlspecialchars($related['name']); ?></div>
                        <?php 
                        $related_show_price = $related['show_price'] ?? 1;
                        if ($related_show_price && !empty($related['wholesale_price'])): ?>
                        <div class="related-price"><?php echo formatPriceInGuaranis($related['wholesale_price']); ?></div>
                        <?php else: ?>
                            <div class="related-contact">
                                <i class="fas fa-phone"></i><?php echo htmlspecialchars($contact_seller_text); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Toast para notificações -->
    <div class="toast-container">
        <div class="toast toast-modern align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" id="toast-notification">
            <div class="toast-body">
                <i class="fas fa-check-circle"></i>
                <span id="toast-message">Producto agregado al carrito</span>
            </div>
        </div>
    </div>
    
    <!-- Footer Moderno -->
    <div class="footer-modern">
        <a href="index.php" class="footer-icon">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="categorias.php" class="footer-icon">
            <i class="fas fa-th-large"></i>
            <span>Categorías</span>
        </a>
        <a href="carrinho.php" class="footer-icon position-relative">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrito</span>
            <span class="footer-cart-count" id="footer-cart-count">0</span>
        </a>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de carrinho unificado -->
    <script src="assets/js/cart.js"></script>
    
    <!-- Script específico para esta página -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === CARREGAR CONTADOR DO CARRINHO IMEDIATAMENTE ===
            console.log('🔄 Inicializando página de produto...');
            
            // Função para garantir carregamento do carrinho
            function garantirCarregamentoCarrinho() {
                console.log('🛒 Verificando contador do carrinho...');
                
                // Buscar contador atual
                updateCartCount();
                
                // Verificar se o footer foi atualizado após 500ms
                setTimeout(() => {
                    const footerCount = document.getElementById('footer-cart-count');
                    if (footerCount) {
                        console.log('🛒 Footer count atual:', footerCount.textContent);
                        
                        // Se ainda está em 0, forçar nova atualização
                        if (footerCount.textContent === '0' && cartCountCache > 0) {
                            console.log('⚠️ Footer não atualizado, forçando atualização...');
                            footerCount.textContent = cartCountCache;
                            footerCount.style.display = 'flex';
                        }
                    }
                }, 500);
            }
            
            // Carregamento inicial com múltiplas tentativas
            setTimeout(() => {
                garantirCarregamentoCarrinho();
            }, 100);
            
            setTimeout(() => {
                garantirCarregamentoCarrinho();
            }, 300);
            
            setTimeout(() => {
                garantirCarregamentoCarrinho();
            }, 800);
            
            // Configurar controles de quantidade
            const btnMinus = document.getElementById('btn-qty-minus');
            const btnPlus = document.getElementById('btn-qty-plus');
            const qtyInput = document.getElementById('product-quantity');
            const btnAgregar = document.getElementById('btn-agregar');
            const hasMinQty = <?php echo $has_min_quantity ? 'true' : 'false'; ?>;
            const minQty = <?php echo $has_min_quantity ? intval($product['min_wholesale_quantity'] ?: 1) : 1; ?>;
            
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
                            <i class="fas fa-search mb-2" style="font-size: 20px; color: #ddd;"></i><br>
                            No encontramos productos con "<strong>${searchTerm}</strong>"<br>
                            <small>Probá con otras palabras</small>
                        </div>
                    `;
                    searchResults.classList.add('show');
                    return;
                }
                
                let resultsHTML = '';
                const maxResults = 6; // Máximo 6 resultados para tela menor
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
                        <div class="search-result-item" style="justify-content: center; background: #f8f9fa; font-weight: 500; color: var(--color-primary);">
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
                return text.replace(regex, '<strong style="color: var(--color-primary);">$1</strong>');
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
            
            // Adicionar animações aos elementos
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '0';
                        entry.target.style.transform = 'translateY(30px)';
                        entry.target.style.transition = 'all 0.6s ease-out';
                        
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, 100);
                        
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observar elementos para animação
            document.querySelectorAll('.animate-slide-up').forEach(el => {
                observer.observe(el);
            });
            
            // Zoom na imagem principal
            const mainImage = document.querySelector('.main-image');
            if (mainImage) {
                mainImage.addEventListener('click', function() {
                    // Criar overlay para zoom
                    const overlay = document.createElement('div');
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.9);
                        z-index: 2000;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: zoom-out;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    `;
                    
                    const zoomedImage = document.createElement('img');
                    zoomedImage.src = this.src;
                    zoomedImage.style.cssText = `
                        max-width: 90%;
                        max-height: 90%;
                        object-fit: contain;
                        border-radius: 12px;
                        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                        transform: scale(0.8);
                        transition: transform 0.3s ease;
                    `;
                    
                    overlay.appendChild(zoomedImage);
                    document.body.appendChild(overlay);
                    
                    // Animar entrada
                    setTimeout(() => {
                        overlay.style.opacity = '1';
                        zoomedImage.style.transform = 'scale(1)';
                    }, 10);
                    
                    // Fechar ao clicar
                    overlay.addEventListener('click', function() {
                        overlay.style.opacity = '0';
                        zoomedImage.style.transform = 'scale(0.8)';
                        setTimeout(() => overlay.remove(), 300);
                    });
                });
            }
            
            // Diminuir quantidade
            btnMinus.addEventListener('click', function() {
                let qty = parseInt(qtyInput.value);
                const minValue = hasMinQty ? minQty : 1;
                if (qty > minValue) {
                    qtyInput.value = qty - 1;
                    // Animação visual
                    this.style.transform = 'scale(0.9)';
                    setTimeout(() => this.style.transform = 'scale(1)', 150);
                }
            });
            
            // Aumentar quantidade
            btnPlus.addEventListener('click', function() {
                let qty = parseInt(qtyInput.value);
                qtyInput.value = qty + 1;
                // Animação visual
                this.style.transform = 'scale(0.9)';
                setTimeout(() => this.style.transform = 'scale(1)', 150);
            });
            
            // Adicionar ao carrinho usando o sistema unificado
            if (btnAgregar) {
            btnAgregar.addEventListener('click', function() {
                const quantity = parseInt(qtyInput.value);
                const productId = <?php echo $product['id']; ?>;
                
                if (quantity <= 0) {
                    return;
                }
                
                // Animação de loading no botão
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
                this.disabled = true;
                
                // Usar a função global do cart.js
                addToCart(productId, quantity);
                
                // Restaurar botão após sucesso
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    // Manter quantidade mínima ao invés de resetar para 1
                    const minValue = hasMinQty ? minQty : 1;
                    qtyInput.value = minValue;
                }, 1000);
                });
            }
            
            // Sistema de Variações com animações melhoradas
            const variationButtons = document.querySelectorAll('.variation-option');
            const currentProductId = <?php echo $product['id']; ?>;
            
            variationButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Animação de seleção
                    variationButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.style.transform = 'scale(1)';
                    });
                    
                    this.classList.add('active');
                    this.style.transform = 'scale(1.05)';
                    setTimeout(() => this.style.transform = 'scale(1)', 200);
                    
                    // Obter dados da variação selecionada
                    const variationId = this.getAttribute('data-variation-id');
                    const variationPrice = this.getAttribute('data-variation-price');
                    const variationMinQty = this.getAttribute('data-variation-min-qty');
                    const variationWeight = this.getAttribute('data-variation-weight');
                    const variationDisplay = this.getAttribute('data-variation-display');
                    const variationShowPrice = this.getAttribute('data-variation-show-price');
                    const variationHasMin = this.getAttribute('data-variation-has-min');
                    
                    // Atualizar preço com animação
                    const priceElement = document.querySelector('.price-main, .price-contact');
                    if (priceElement) {
                        priceElement.style.opacity = '0.5';
                        priceElement.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            if (variationShowPrice === '1' && variationPrice && parseFloat(variationPrice) > 0) {
                                // Mostrar preço formatado
                                fetch(`get_price.php?id=${variationId}&type=wholesale`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            const formatter = new Intl.NumberFormat('es-PY', {
                                                style: 'currency',
                                                currency: 'PYG',
                                                minimumFractionDigits: 0
                                            });
                                            priceElement.innerHTML = formatter.format(data.price).replace('PYG', 'G$');
                                            priceElement.className = 'price-main';
                                        }
                                    })
                                    .catch(() => {
                                        const price = parseFloat(variationPrice) || 0;
                                        const formattedPrice = new Intl.NumberFormat('es-PY').format(price);
                                        priceElement.innerHTML = `G$ ${formattedPrice}`;
                                        priceElement.className = 'price-main';
                                    });
                            } else {
                                priceElement.innerHTML = '<i class="fas fa-phone"></i>Consultar con el vendedor';
                                priceElement.className = 'price-contact';
                            }
                            
                            // Animar volta
                            priceElement.style.opacity = '1';
                            priceElement.style.transform = 'scale(1)';
                        }, 200);
                    }
                    
                    // Atualizar quantidade mínima
                    const minQtyElement = document.querySelector('.min-quantity');
                    
                    if (variationHasMin === '1' && variationMinQty && parseInt(variationMinQty) > 0) {
                        const minQty = parseInt(variationMinQty);
                        const unitType = '<?php echo $unit_type; ?>';
                        const unitDisplay = unitType === 'kg' ? '<?php echo htmlspecialchars($unit_display); ?>' : 'unidades';
                        
                        if (minQtyElement) {
                            minQtyElement.innerHTML = `<i class="fas fa-info-circle"></i><span>Mínimo: ${minQty} ${unitDisplay}</span>`;
                            minQtyElement.style.display = 'flex';
                        }
                        
                        // Atualizar input de quantidade
                        qtyInput.min = minQty;
                        qtyInput.value = minQty;
                    } else {
                        if (minQtyElement) {
                            minQtyElement.style.display = 'none';
                        }
                        qtyInput.min = 1;
                        qtyInput.value = 1;
                    }
                    
                    // Atualizar ID do produto no botão
                    if (btnAgregar) {
                        btnAgregar.setAttribute('data-product-id', variationId);
                        
                        // Atualizar evento click
                        const newBtn = btnAgregar.cloneNode(true);
                        btnAgregar.parentNode.replaceChild(newBtn, btnAgregar);
                        
                        newBtn.addEventListener('click', function() {
                            const quantity = parseInt(qtyInput.value);
                            
                            if (quantity <= 0) return;
                            
                            // Animação de loading
                            const originalText = this.innerHTML;
                            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
                            this.disabled = true;
                            
                            addToCart(parseInt(variationId), quantity);
                            
                            setTimeout(() => {
                                this.innerHTML = originalText;
                                this.disabled = false;
                                const minValue = variationHasMin === '1' ? parseInt(variationMinQty || 1) : 1;
                                qtyInput.value = minValue;
                            }, 1000);
                        });
                    }
                    
                    // Opcionalmente, atualizar URL sem recarregar página
                    if (history.pushState) {
                        const newUrl = `produto.php?id=${variationId}`;
                        history.pushState({variationId: variationId}, '', newUrl);
                    }
                });
            });
        });
    </script>
</body>
</html>