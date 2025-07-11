<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Verificar se há ações
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_variation':
                try {
                    $parent_id = (int)$_POST['parent_id'];
                    $variation_name = trim($_POST['variation_name']);
                    $variation_price = (float)$_POST['variation_price'];
                    $variation_weight = (float)$_POST['variation_weight'];
                    $variation_stock = (int)$_POST['variation_stock'];
                    $variation_min_qty = (int)$_POST['variation_min_qty'];
                    
                    // Obter dados do produto pai
                    $parent_query = "SELECT * FROM products WHERE id = ?";
                    $parent_stmt = $conn->prepare($parent_query);
                    $parent_stmt->bind_param("i", $parent_id);
                    $parent_stmt->execute();
                    $parent_result = $parent_stmt->get_result();
                    $parent_product = $parent_result->fetch_assoc();
                    
                    if (!$parent_product) {
                        throw new Exception("Produto pai não encontrado");
                    }
                    
                    // Criar variação
                    $variation_full_name = $parent_product['name'] . ' ' . $variation_name;
                    $has_min_quantity = $variation_min_qty > 1 ? 1 : 0;
                    
                    $query = "INSERT INTO products (
                        name, description, wholesale_price, retail_price, min_wholesale_quantity,
                        unit_weight, unit_type, unit_display_name, stock, category_id,
                        featured, promotion, status, show_price, has_min_quantity,
                        parent_product_id, variation_display, variation_type, image_url,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 'size', ?, NOW(), NOW())";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param(
                        "ssdddssiiiiiisss",
                        $variation_full_name,
                        $parent_product['description'],
                        $variation_price,
                        $variation_min_qty,
                        $variation_weight,
                        $parent_product['unit_type'],
                        $parent_product['unit_display_name'],
                        $variation_stock,
                        $parent_product['category_id'],
                        $parent_product['featured'],
                        $parent_product['promotion'],
                        $parent_product['status'],
                        $has_min_quantity,
                        $parent_id,
                        $variation_name,
                        $parent_product['image_url']
                    );
                    
                    if ($stmt->execute()) {
                        $success_message = 'Variação criada com sucesso!';
                    } else {
                        throw new Exception($conn->error);
                    }
                    
                } catch (Exception $e) {
                    $error_message = 'Erro ao criar variação: ' . $e->getMessage();
                }
                break;
                
            case 'update_variation':
                try {
                    $variation_id = (int)$_POST['variation_id'];
                    $variation_name = trim($_POST['variation_name']);
                    $variation_price = (float)$_POST['variation_price'];
                    $variation_weight = (float)$_POST['variation_weight'];
                    $variation_stock = (int)$_POST['variation_stock'];
                    $variation_min_qty = (int)$_POST['variation_min_qty'];
                    $has_min_quantity = $variation_min_qty > 1 ? 1 : 0;
                    
                    // Obter dados da variação atual
                    $current_query = "SELECT * FROM products WHERE id = ?";
                    $current_stmt = $conn->prepare($current_query);
                    $current_stmt->bind_param("i", $variation_id);
                    $current_stmt->execute();
                    $current_result = $current_stmt->get_result();
                    $current_variation = $current_result->fetch_assoc();
                    
                    if (!$current_variation) {
                        throw new Exception("Variação não encontrada");
                    }
                    
                    // Obter nome do produto pai
                    $parent_query = "SELECT name FROM products WHERE id = ?";
                    $parent_stmt = $conn->prepare($parent_query);
                    $parent_stmt->bind_param("i", $current_variation['parent_product_id']);
                    $parent_stmt->execute();
                    $parent_result = $parent_stmt->get_result();
                    $parent_product = $parent_result->fetch_assoc();
                    
                    $variation_full_name = $parent_product['name'] . ' ' . $variation_name;
                    
                    $query = "UPDATE products SET 
                        name = ?, variation_display = ?, wholesale_price = ?, 
                        unit_weight = ?, stock = ?, min_wholesale_quantity = ?, 
                        has_min_quantity = ?, updated_at = NOW() 
                        WHERE id = ?";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param(
                        "ssddiiis",
                        $variation_full_name,
                        $variation_name,
                        $variation_price,
                        $variation_weight,
                        $variation_stock,
                        $variation_min_qty,
                        $has_min_quantity,
                        $variation_id
                    );
                    
                    if ($stmt->execute()) {
                        $success_message = 'Variação atualizada com sucesso!';
                    } else {
                        throw new Exception($conn->error);
                    }
                    
                } catch (Exception $e) {
                    $error_message = 'Erro ao atualizar variação: ' . $e->getMessage();
                }
                break;
                
            case 'delete_variation':
                try {
                    $variation_id = (int)$_POST['variation_id'];
                    
                    $query = "DELETE FROM products WHERE id = ? AND parent_product_id IS NOT NULL";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $variation_id);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Variação removida com sucesso!';
                    } else {
                        throw new Exception($conn->error);
                    }
                    
                } catch (Exception $e) {
                    $error_message = 'Erro ao remover variação: ' . $e->getMessage();
                }
                break;
                
            case 'convert_to_variable':
                try {
                    $product_id = (int)$_POST['product_id'];
                    $variations_data = $_POST['variations'];
                    
                    // Converter produto simples em produto com variações
                    foreach ($variations_data as $variation) {
                        if (empty($variation['name'])) continue;
                        
                        // Obter dados do produto original
                        $original_query = "SELECT * FROM products WHERE id = ?";
                        $original_stmt = $conn->prepare($original_query);
                        $original_stmt->bind_param("i", $product_id);
                        $original_stmt->execute();
                        $original_result = $original_stmt->get_result();
                        $original_product = $original_result->fetch_assoc();
                        
                        if (!$original_product) continue;
                        
                        $variation_name = trim($variation['name']);
                        $variation_price = (float)$variation['price'];
                        $variation_weight = (float)$variation['weight'];
                        $variation_stock = (int)$variation['stock'];
                        $variation_min_qty = (int)$variation['min_qty'];
                        $has_min_quantity = $variation_min_qty > 1 ? 1 : 0;
                        
                        $variation_full_name = $original_product['name'] . ' ' . $variation_name;
                        
                        $query = "INSERT INTO products (
                            name, description, wholesale_price, retail_price, min_wholesale_quantity,
                            unit_weight, unit_type, unit_display_name, stock, category_id,
                            featured, promotion, status, show_price, has_min_quantity,
                            parent_product_id, variation_display, variation_type, image_url,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 'size', ?, NOW(), NOW())";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param(
                            "ssdddssiiiiiisss",
                            $variation_full_name,
                            $original_product['description'],
                            $variation_price,
                            $variation_min_qty,
                            $variation_weight,
                            $original_product['unit_type'],
                            $original_product['unit_display_name'],
                            $variation_stock,
                            $original_product['category_id'],
                            $original_product['featured'],
                            $original_product['promotion'],
                            $original_product['status'],
                            $has_min_quantity,
                            $product_id,
                            $variation_name,
                            $original_product['image_url']
                        );
                        
                        $stmt->execute();
                    }
                    
                    $success_message = 'Produto convertido para variável com sucesso!';
                    
                } catch (Exception $e) {
                    $error_message = 'Erro ao converter produto: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Obter produtos pai (com variações)
$products_with_variations_query = "SELECT p.*, c.name as category_name,
    (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id) as variations_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.parent_product_id IS NULL
    AND EXISTS (SELECT 1 FROM products v WHERE v.parent_product_id = p.id)
    ORDER BY p.name";
$products_with_variations = $conn->query($products_with_variations_query);

// Obter produtos simples (sem variações)
$simple_products_query = "SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1 AND p.parent_product_id IS NULL
    AND NOT EXISTS (SELECT 1 FROM products v WHERE v.parent_product_id = p.id)
    ORDER BY p.name";
$simple_products = $conn->query($simple_products_query);

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<style>
.variation-management-container {
    padding: 20px;
}

.section-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.variations-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.table th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    color: #6c757d;
    padding: 15px;
}

.table td {
    border: none;
    padding: 15px;
    vertical-align: middle;
}

.table tbody tr:not(:last-child) {
    border-bottom: 1px solid #f8f9fa;
}

.variation-row {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
}

.variation-input {
    margin-bottom: 10px;
}

.btn-variation {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-variation:hover {
    background: #218838;
    transform: translateY(-1px);
}

.btn-variation-danger {
    background: #dc3545;
}

.btn-variation-danger:hover {
    background: #c82333;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}

.stat-card.success {
    border-left-color: #28a745;
}

.stat-card.warning {
    border-left-color: #ffc107;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
}

.variation-badge {
    background: #e9ecef;
    color: #495057;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.variation-badge.active {
    background: #28a745;
    color: white;
}

.modal-variation-form {
    max-width: 600px;
}

.variations-builder {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
    background: #f8f9fa;
}

.variations-builder.active {
    border-color: #28a745;
    background: #d4edda;
}

.variation-form-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 60px;
    gap: 10px;
    align-items: end;
    margin-bottom: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.btn-add-variation {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-add-variation:hover {
    background: #0056b3;
}

.collapse-toggle {
    cursor: pointer;
    user-select: none;
    transition: all 0.3s ease;
}

.collapse-toggle:hover {
    background: #f8f9fa;
}

.collapse-toggle .fa-chevron-down {
    transition: transform 0.3s ease;
}

.collapse-toggle.collapsed .fa-chevron-down {
    transform: rotate(-90deg);
}
</style>

<div class="variation-management-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Gestão de Variações</h2>
            <p class="text-muted">Gerencie variações de produtos como grandes e-commerces</p>
        </div>
        <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#convertProductModal">
            <i class="fas fa-plus me-2"></i>Converter Produto
        </button>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-number"><?php echo $products_with_variations->num_rows; ?></div>
            <div class="stat-label">Produtos com Variações</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?php echo $simple_products->num_rows; ?></div>
            <div class="stat-label">Produtos Simples</div>
        </div>
        <div class="stat-card">
            <?php
            $total_variations_query = "SELECT COUNT(*) as total FROM products WHERE parent_product_id IS NOT NULL";
            $total_variations_result = $conn->query($total_variations_query);
            $total_variations = $total_variations_result->fetch_assoc()['total'];
            ?>
            <div class="stat-number"><?php echo $total_variations; ?></div>
            <div class="stat-label">Total de Variações</div>
        </div>
    </div>

    <!-- Produtos com Variações -->
    <div class="section-card">
        <h4 class="mb-3">
            <i class="fas fa-layer-group me-2"></i>Produtos com Variações
            <span class="badge bg-success ms-2"><?php echo $products_with_variations->num_rows; ?></span>
        </h4>
        
        <?php if ($products_with_variations->num_rows > 0): ?>
            <div class="variations-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Variações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $products_with_variations->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($product['image_url']) ? '../' . $product['image_url'] : '../assets/images/no-image.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image me-3">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'Sem categoria'); ?></span>
                                    </td>
                                    <td>
                                        <span class="variation-badge active">
                                            <?php echo $product['variations_count']; ?> variações
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="loadVariations(<?php echo $product['id']; ?>)"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#variationsModal">
                                            <i class="fas fa-edit me-1"></i>Gerenciar
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="addVariation(<?php echo $product['id']; ?>)"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#addVariationModal">
                                            <i class="fas fa-plus me-1"></i>Adicionar
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Nenhum produto com variações encontrado.
            </div>
        <?php endif; ?>
    </div>

    <!-- Produtos Simples -->
    <div class="section-card">
        <h4 class="mb-3">
            <i class="fas fa-cube me-2"></i>Produtos Simples
            <span class="badge bg-warning ms-2"><?php echo $simple_products->num_rows; ?></span>
        </h4>
        
        <p class="text-muted mb-3">
            Produtos que podem ser convertidos para ter variações (tamanhos, volumes, etc.)
        </p>
        
        <?php if ($simple_products->num_rows > 0): ?>
            <div class="variations-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Unidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $simple_products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($product['image_url']) ? '../' . $product['image_url'] : '../assets/images/no-image.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="product-image me-3">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'Sem categoria'); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPriceInGuaranis($product['wholesale_price']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="variation-badge">
                                            <?php echo $product['unit_type'] == 'kg' ? 'Por kg' : 'Por unidade'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" 
                                                onclick="convertToVariable(<?php echo $product['id']; ?>)"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#convertProductModal">
                                            <i class="fas fa-layer-group me-1"></i>Converter
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Todos os produtos já possuem variações.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Gerenciar Variações -->
<div class="modal fade" id="variationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-layer-group me-2"></i>Gerenciar Variações
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="variationsContent">
                    <!-- Conteúdo será carregado dinamicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Variação -->
<div class="modal fade" id="addVariationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Adicionar Variação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_variation">
                    <input type="hidden" name="parent_id" id="addVariationParentId">
                    
                    <div class="mb-3">
                        <label for="variation_name" class="form-label">Nome da Variação</label>
                        <input type="text" class="form-control" id="variation_name" name="variation_name" 
                               placeholder="ex: 200ml, 500ml, 1L" required>
                        <div class="form-text">Digite apenas a variação (ex: 200ml). O nome completo será criado automaticamente.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="variation_price" class="form-label">Preço (R$)</label>
                            <input type="number" class="form-control" id="variation_price" name="variation_price" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="variation_weight" class="form-label">Peso (kg)</label>
                            <input type="number" class="form-control" id="variation_weight" name="variation_weight" 
                                   step="0.001" min="0.001" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="variation_stock" class="form-label">Estoque</label>
                            <input type="number" class="form-control" id="variation_stock" name="variation_stock" 
                                   min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="variation_min_qty" class="form-label">Quantidade Mínima</label>
                            <input type="number" class="form-control" id="variation_min_qty" name="variation_min_qty" 
                                   min="1" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Criar Variação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Converter Produto -->
<div class="modal fade" id="convertProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-layer-group me-2"></i>Converter para Produto Variável
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="convert_to_variable">
                    <input type="hidden" name="product_id" id="convertProductId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Adicione as variações desejadas para este produto. O produto original se tornará o produto "pai" e as variações serão criadas.
                    </div>
                    
                    <div id="variationsBuilder" class="variations-builder">
                        <h6 class="mb-3">Variações do Produto</h6>
                        <div id="variationsContainer">
                            <!-- Variações serão adicionadas dinamicamente -->
                        </div>
                        <button type="button" class="btn btn-add-variation" onclick="addVariationRow()">
                            <i class="fas fa-plus me-2"></i>Adicionar Variação
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-magic me-2"></i>Converter Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let variationCounter = 0;

function loadVariations(productId) {
    fetch(`get_variations.php?product_id=${productId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('variationsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Erro ao carregar variações:', error);
        });
}

function addVariation(productId) {
    document.getElementById('addVariationParentId').value = productId;
}

function convertToVariable(productId) {
    document.getElementById('convertProductId').value = productId;
    document.getElementById('variationsContainer').innerHTML = '';
    variationCounter = 0;
    addVariationRow();
}

function addVariationRow() {
    variationCounter++;
    const container = document.getElementById('variationsContainer');
    
    const row = document.createElement('div');
    row.className = 'variation-form-row';
    row.innerHTML = `
        <div>
            <label class="form-label">Nome da Variação</label>
            <input type="text" class="form-control" name="variations[${variationCounter}][name]" 
                   placeholder="ex: 200ml, 500ml, 1L" required>
        </div>
        <div>
            <label class="form-label">Preço (R$)</label>
            <input type="number" class="form-control" name="variations[${variationCounter}][price]" 
                   step="0.01" min="0" required>
        </div>
        <div>
            <label class="form-label">Peso (kg)</label>
            <input type="number" class="form-control" name="variations[${variationCounter}][weight]" 
                   step="0.001" min="0.001" required>
        </div>
        <div>
            <label class="form-label">Estoque</label>
            <input type="number" class="form-control" name="variations[${variationCounter}][stock]" 
                   min="0" value="0">
        </div>
        <div>
            <label class="form-label">Mín.</label>
            <input type="number" class="form-control" name="variations[${variationCounter}][min_qty]" 
                   min="1" value="1">
        </div>
        <div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeVariationRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
    
    // Animar entrada
    row.style.opacity = '0';
    row.style.transform = 'translateY(20px)';
    setTimeout(() => {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
    }, 10);
}

function removeVariationRow(button) {
    const row = button.closest('.variation-form-row');
    row.style.transition = 'all 0.3s ease';
    row.style.opacity = '0';
    row.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        row.remove();
    }, 300);
}

function editProduct(productId) {
    window.location.href = `produto_editar.php?id=${productId}`;
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script> 