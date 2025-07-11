<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Incluir funções de câmbio
require_once 'includes/exchange_functions.php';

// INCLUIR SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS
require_once 'includes/minimum_quantity_functions.php';

// Consultar configurações globais da loja
$store_query = "SELECT enable_global_minimums, minimum_explanation_text FROM store_settings WHERE id = 1";
$store_result = $conn->query($store_query);
$store_config = $store_result->fetch_assoc();
$global_minimums_enabled = $store_config['enable_global_minimums'] ?? 1;

// Processar parâmetros de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Montar a consulta SQL para produtos com paginação e nova ordenação (promoção > destacado > alfabética)
// APENAS PRODUTOS PAI - Variações ficam ocultas do infinite scroll
if ($category > 0) {
    $query = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity,
              (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
              FROM products p
              WHERE p.status = 1 AND p.parent_product_id IS NULL AND p.category_id = ? 
              ORDER BY p.promotion DESC, p.featured DESC, p.name ASC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $category, $items_per_page, $offset);
} else {
    $query = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity,
              (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
              FROM products p
              WHERE p.status = 1 AND p.parent_product_id IS NULL
              ORDER BY p.promotion DESC, p.featured DESC, p.name ASC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $items_per_page, $offset);
}

// Executar consulta
$stmt->execute();
$result = $stmt->get_result();

// Consulta para contar o total de produtos PAI
if ($category > 0) {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1 AND parent_product_id IS NULL AND category_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $category);
} else {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1 AND parent_product_id IS NULL";
    $count_stmt = $conn->prepare($count_query);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $items_per_page);

// Preparar resposta
$products = [];
$has_more = ($page < $total_pages);

// Formatar produtos para resposta JSON usando SISTEMA UNIFICADO
while ($product = $result->fetch_assoc()) {
    // Preparar produto com sistema unificado
    $product = prepareProductForDisplay($product);
    
    $wholesale_price_pyg = convertBrlToPyg($product['wholesale_price']);
    $retail_price_pyg = null; // Preço de varejo removido conforme solicitado
    
    $unit_type = $product['unit_type'] ?? 'kg';
    $unit_display = $product['unit_display_name'] ?? 'kg';
    $unit_weight = $product['unit_weight'] ?? '1.00';
    $show_price = $product['show_price'] ?? 1;
    
    $products[] = [
        'id' => $product['id'],
        'name' => htmlspecialchars($product['name']),
        'wholesale_price' => $wholesale_price_pyg,
        'wholesale_price_formatted' => $show_price && !empty($product['wholesale_price']) ? formatPriceInGuaranis($product['wholesale_price']) : 'Consultar precio',
        'retail_price' => $retail_price_pyg,
        'retail_price_formatted' => null, // Removido preço de varejo
        'min_wholesale_quantity' => $product['effective_min_quantity'], // SISTEMA UNIFICADO
        'unit_weight' => $unit_weight,
        'unit_type' => $unit_type,
        'unit_display_name' => htmlspecialchars($unit_display),
        'image_url' => !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'assets/images/no-image.png',
        'featured' => $product['featured'],
        'promotion' => $product['promotion'],
        'show_price' => $show_price,
        'has_min_quantity' => $product['should_apply_minimum'], // SISTEMA UNIFICADO
        'variations_count' => $product['variations_count'] ?? 0
    ];
}

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'products' => $products,
    'has_more' => $has_more,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_products' => $total_products,
    'enable_global_minimums' => $global_minimums_enabled
]);
?>
