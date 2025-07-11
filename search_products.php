<?php
// Iniciar sessão e incluir dependências
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/exchange_functions.php';

// Definir header JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar se há termo de busca
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Termo de busca não fornecido',
        'products' => []
    ]);
    exit;
}

$searchTerm = trim($_GET['q']);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Máximo 20 resultados

// Prevenir SQL injection e preparar termo para busca
$searchPattern = '%' . $searchTerm . '%';

try {
    // Query otimizada para busca rápida
    // Buscar APENAS em produtos PAI (parent_product_id IS NULL)
    $query = "SELECT 
                p.id,
                p.name,
                p.wholesale_price,
                p.image_url,
                p.show_price,
                p.featured,
                p.promotion,
                (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
              FROM products p 
              WHERE p.status = 1 
                AND p.parent_product_id IS NULL
                AND (
                    p.name LIKE ? 
                    OR p.description LIKE ?
                )
              ORDER BY 
                p.promotion DESC,
                p.featured DESC,
                CASE 
                    WHEN p.name LIKE ? THEN 1
                    ELSE 2
                END,
                p.name ASC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters - nome tem prioridade sobre descrição
    $namePattern = $searchTerm . '%'; // Para priorizar começados com termo
    $stmt->bind_param("sssi", $searchPattern, $searchPattern, $namePattern, $limit);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    
    while ($product = $result->fetch_assoc()) {
        // Converter preço para Guaranis
        $wholesale_price_pyg = convertBrlToPyg($product['wholesale_price']);
        
        // Determinar se deve mostrar preço
        $show_price = $product['show_price'] ?? 1;
        
        $products[] = [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['name']),
            'wholesale_price' => $wholesale_price_pyg,
            'wholesale_price_formatted' => $show_price && !empty($product['wholesale_price']) 
                ? formatPriceInGuaranis($product['wholesale_price']) 
                : null,
            'image_url' => !empty($product['image_url']) 
                ? htmlspecialchars($product['image_url']) 
                : 'assets/images/default-logo.png',
            'show_price' => $show_price,
            'featured' => $product['featured'],
            'promotion' => $product['promotion'],
            'variations_count' => $product['variations_count'] ?? 0
        ];
    }
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total_found' => count($products),
        'search_term' => htmlspecialchars($searchTerm),
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    // Log do erro (em produção, usar sistema de log adequado)
    error_log("Erro na busca de produtos: " . $e->getMessage());
    
    // Resposta de erro
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'products' => [],
        'error_code' => 'SEARCH_ERROR'
    ]);
}

// Fechar conexão
$conn->close();
?> 