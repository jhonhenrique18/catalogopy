<?php
/**
 * PROCESSADOR DE PRODUTOS - BACKEND PROFISSIONAL
 * Processa dados do novo assistente de produtos
 * Suporte completo a variações e diferentes tipos de medida
 */

// Incluir verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Configurar resposta JSON
header('Content-Type: application/json');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Começar transação
    $conn->begin_transaction();
    
    // Validar dados recebidos
    $productData = validateProductData($_POST);
    
    // Processar baseado no tipo de produto
    if ($productData['product_type'] === 'simple') {
        $productId = createSimpleProduct($productData);
    } else {
        $productId = createVariableProduct($productData);
    }
    
    // Processar upload de imagem se enviada
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $imageUrl = processImageUpload($_FILES['product_image'], $productId);
        updateProductImage($productId, $imageUrl);
    }
    
    // Commit da transação
    $conn->commit();
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Produto criado com sucesso!',
        'product_id' => $productId,
        'redirect' => 'produtos.php'
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
}

/**
 * Validar dados do produto
 */
function validateProductData($data) {
    $errors = [];
    $validated = [];
    
    // Validações básicas
    if (empty($data['product_name']) || strlen($data['product_name']) < 3) {
        $errors[] = 'Nome do produto deve ter pelo menos 3 caracteres';
    }
    $validated['name'] = trim($data['product_name']);
    
    if (empty($data['category_id'])) {
        $errors[] = 'Categoria é obrigatória';
    }
    $validated['category_id'] = (int)$data['category_id'];
    
    $validated['description'] = trim($data['product_description'] ?? '');
    $validated['stock'] = max(0, (int)($data['product_stock'] ?? 0));
    
    // Validar tipo de produto
    if (!in_array($data['product_type'], ['simple', 'variable'])) {
        $errors[] = 'Tipo de produto inválido';
    }
    $validated['product_type'] = $data['product_type'];
    
    // Validar tipo de medida
    if (!in_array($data['measure_type'], ['weight', 'volume', 'unit'])) {
        $errors[] = 'Tipo de medida inválido';
    }
    $validated['measure_type'] = $data['measure_type'];
    
    // Validações específicas por tipo de medida
    switch ($data['measure_type']) {
        case 'weight':
            $validated['unit_type'] = 'kg';
            $validated['unit_display_name'] = $data['weight_unit'] ?? 'kg';
            $validated['unit_weight'] = max(0.001, (float)($data['unit_weight'] ?? 1));
            break;
            
        case 'volume':
            $validated['unit_type'] = 'unit';
            $validated['unit_display_name'] = $data['volume_unit'] ?? 'ml';
            $validated['unit_volume'] = max(1, (int)($data['unit_volume'] ?? 200));
            $validated['unit_weight'] = max(0.001, (float)($data['real_weight'] ?? 0.2));
            break;
            
        case 'unit':
            $validated['unit_type'] = 'unit';
            $validated['unit_display_name'] = $data['unit_name'] ?? 'unidades';
            $validated['unit_weight'] = max(0.001, (float)($data['unit_weight'] ?? 0.5));
            break;
    }
    
    // Validar preços para produto simples
    if ($data['product_type'] === 'simple') {
        if (empty($data['product_price']) || (float)$data['product_price'] <= 0) {
            $errors[] = 'Preço do produto deve ser maior que zero';
        }
        $validated['price'] = (float)$data['product_price'];
        $validated['min_quantity'] = max(1, (int)($data['min_quantity'] ?? 1));
        $validated['show_price'] = isset($data['show_price']) ? 1 : 0;
    }
    
    // Validar variações para produto variável
    if ($data['product_type'] === 'variable') {
        $validated['variations'] = validateVariations($data);
        if (empty($validated['variations'])) {
            $errors[] = 'Produto com variações deve ter pelo menos uma variação';
        }
    }
    
    // Status e opções
    $validated['featured'] = isset($data['product_featured']) ? 1 : 0;
    $validated['promotion'] = isset($data['product_promotion']) ? 1 : 0;
    $validated['status'] = isset($data['product_status']) ? 1 : 0;
    
    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }
    
    return $validated;
}

/**
 * Validar variações
 */
function validateVariations($data) {
    $variations = [];
    
    if (!isset($data['variations']) || !is_array($data['variations'])) {
        return $variations;
    }
    
    foreach ($data['variations'] as $variationId => $variation) {
        if (empty($variation['name'])) {
            continue; // Pular variações sem nome
        }
        
        $validated = [
            'name' => trim($variation['name']),
            'price' => 0,
            'stock' => 0,
            'min_quantity' => 1
        ];
        
        // Dados específicos por tipo
        if (isset($variation['weight'])) {
            $validated['weight'] = max(0.001, (float)$variation['weight']);
        }
        if (isset($variation['volume'])) {
            $validated['volume'] = max(1, (int)$variation['volume']);
        }
        if (isset($variation['quantity'])) {
            $validated['quantity'] = max(1, (int)$variation['quantity']);
        }
        
        // Preços e estoque das variações
        if (isset($data['variation_prices'][$variationId])) {
            $validated['price'] = max(0, (float)$data['variation_prices'][$variationId]);
        }
        if (isset($data['variation_stocks'][$variationId])) {
            $validated['stock'] = max(0, (int)$data['variation_stocks'][$variationId]);
        }
        if (isset($data['variation_minimums'][$variationId])) {
            $validated['min_quantity'] = max(1, (int)$data['variation_minimums'][$variationId]);
        }
        
        $variations[] = $validated;
    }
    
    return $variations;
}

/**
 * Criar produto simples
 */
function createSimpleProduct($data) {
    global $conn;
    
    // Converter preço para reais (assumindo que vem em reais)
    $wholesalePrice = $data['price'];
    
    // Preparar dados para inserção
    $query = "INSERT INTO products (
        name, description, wholesale_price, retail_price, min_wholesale_quantity,
        unit_weight, unit_type, unit_display_name, stock, category_id,
        featured, promotion, status, show_price, has_min_quantity,
        created_at, updated_at
    ) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Erro ao preparar query: ' . $conn->error);
    }
    
    $hasMinQuantity = $data['min_quantity'] > 1 ? 1 : 0;
    $minQuantity = $hasMinQuantity ? $data['min_quantity'] : null;
    
    $stmt->bind_param(
        "ssddsssiiiiiii",
        $data['name'],              // s - string
        $data['description'],       // s - string  
        $wholesalePrice,           // d - double
        $minQuantity,              // d - double (nullable)
        $data['unit_weight'],      // d - double
        $data['unit_type'],        // s - string
        $data['unit_display_name'], // s - string
        $data['stock'],            // i - int
        $data['category_id'],      // i - int
        $data['featured'],         // i - int
        $data['promotion'],        // i - int
        $data['status'],           // i - int
        $data['show_price'],       // i - int
        $hasMinQuantity            // i - int
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir produto: ' . $stmt->error);
    }
    
    return $conn->insert_id;
}

/**
 * Criar produto com variações
 */
function createVariableProduct($data) {
    global $conn;
    
    // 1. Criar produto pai
    $parentId = createParentProduct($data);
    
    // 2. Criar variações
    foreach ($data['variations'] as $variation) {
        createVariation($parentId, $variation, $data);
    }
    
    return $parentId;
}

/**
 * Criar produto pai
 */
function createParentProduct($data) {
    global $conn;
    
    $query = "INSERT INTO products (
        name, description, wholesale_price, retail_price, min_wholesale_quantity,
        unit_weight, unit_type, unit_display_name, stock, category_id,
        featured, promotion, status, show_price, has_min_quantity,
        parent_product_id, variation_display, variation_type,
        created_at, updated_at
    ) VALUES (?, ?, NULL, NULL, NULL, ?, ?, ?, 0, ?, ?, ?, ?, 0, 0, NULL, 'parent', ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Erro ao preparar query do produto pai: ' . $conn->error);
    }
    
    // Determinar tipo de variação baseado na medida
    $variationType = $data['measure_type']; // 'weight', 'volume', 'unit'
    
    $stmt->bind_param(
        "ssdssiiiis",
        $data['name'],              // s - string
        $data['description'],       // s - string
        $data['unit_weight'],      // d - double
        $data['unit_type'],        // s - string
        $data['unit_display_name'], // s - string
        $data['category_id'],      // i - int
        $data['featured'],         // i - int
        $data['promotion'],        // i - int
        $data['status'],           // i - int
        $variationType             // s - string
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir produto pai: ' . $stmt->error);
    }
    
    return $conn->insert_id;
}

/**
 * Criar variação
 */
function createVariation($parentId, $variation, $parentData) {
    global $conn;
    
    // Montar nome completo da variação
    $variationName = $parentData['name'] . ' ' . $variation['name'];
    
    // Calcular peso da variação baseado no tipo
    $variationWeight = calculateVariationWeight($variation, $parentData);
    
    // Determinar display da variação
    $variationDisplay = $variation['name'];
    
    $query = "INSERT INTO products (
        name, description, wholesale_price, retail_price, min_wholesale_quantity,
        unit_weight, unit_type, unit_display_name, stock, category_id,
        featured, promotion, status, show_price, has_min_quantity,
        parent_product_id, variation_display, variation_type,
        created_at, updated_at
    ) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Erro ao preparar query da variação: ' . $conn->error);
    }
    
    $hasMinQuantity = $variation['min_quantity'] > 1 ? 1 : 0;
    $minQuantity = $hasMinQuantity ? $variation['min_quantity'] : null;
    
    $stmt->bind_param(
        "ssdddssiiiiiiiiss",
        $variationName,                    // s - string
        $parentData['description'],        // s - string
        $variation['price'],              // d - double
        $minQuantity,                     // d - double (nullable)
        $variationWeight,                 // d - double
        $parentData['unit_type'],         // s - string
        $parentData['unit_display_name'], // s - string
        $variation['stock'],              // i - int
        $parentData['category_id'],       // i - int
        $parentData['featured'],          // i - int
        $parentData['promotion'],         // i - int
        $parentData['status'],            // i - int
        $hasMinQuantity,                  // i - int
        $parentId,                        // i - int
        $variationDisplay,                // s - string
        $parentData['measure_type']       // s - string
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir variação: ' . $stmt->error);
    }
    
    return $conn->insert_id;
}

/**
 * Calcular peso da variação
 */
function calculateVariationWeight($variation, $parentData) {
    switch ($parentData['measure_type']) {
        case 'weight':
            return $variation['weight'] ?? $parentData['unit_weight'];
            
        case 'volume':
            // Para volumes, usar o peso real especificado
            return $variation['weight'] ?? (($variation['volume'] ?? 200) / 1000 * 0.95);
            
        case 'unit':
            return $variation['weight'] ?? $parentData['unit_weight'];
            
        default:
            return $parentData['unit_weight'];
    }
}

/**
 * Processar upload de imagem
 */
function processImageUpload($file, $productId) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validações
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de arquivo inválido. Use JPG, PNG ou GIF.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Arquivo muito grande. Máximo 2MB.');
    }
    
    // Criar diretório se não existir
    $uploadDir = '../uploads/produtos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'product_' . uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $fileName;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Erro ao fazer upload da imagem.');
    }
    
    return 'uploads/produtos/' . $fileName;
}

/**
 * Atualizar imagem do produto
 */
function updateProductImage($productId, $imageUrl) {
    global $conn;
    
    $query = "UPDATE products SET image_url = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar update de imagem: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $imageUrl, $productId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar imagem: ' . $stmt->error);
    }
}

/**
 * Log de atividade (opcional)
 */
function logActivity($action, $productId, $details = '') {
    global $conn;
    
    $userId = $_SESSION['admin_id'] ?? 0;
    $query = "INSERT INTO activity_log (user_id, action, product_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isis", $userId, $action, $productId, $details);
        $stmt->execute();
    }
}

?> 