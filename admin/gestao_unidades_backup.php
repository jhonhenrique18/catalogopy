<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se há ações
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_single':
                $product_id = (int)$_POST['product_id'];
                $unit_type = $_POST['unit_type'];
                $unit_display = trim($_POST['unit_display']);
                $unit_weight = (float)$_POST['unit_weight'];
                
                $query = "UPDATE products SET unit_type = ?, unit_display_name = ?, unit_weight = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssdi", $unit_type, $unit_display, $unit_weight, $product_id);
                
                if ($stmt->execute()) {
                    $success_message = "Produto atualizado com sucesso!";
                } else {
                    $error_message = "Erro ao atualizar produto: " . $conn->error;
                }
                break;
                
            case 'convert_liquids':
                // Converter produtos líquidos para unidades
                $affected = 0;
                
                // Produtos com ml no nome devem ser vendidos por unidade (frasco)
                $query = "UPDATE products SET 
                    unit_type = 'unit', 
                    unit_display_name = 'unidades',
                    updated_at = NOW()
                    WHERE (name LIKE '%ml%' OR name LIKE '%ML%') 
                    AND unit_display_name = 'ml'";
                
                if ($conn->query($query)) {
                    $affected = $conn->affected_rows;
                    $success_message = "{$affected} produtos líquidos convertidos para vendas por unidade!";
                } else {
                    $error_message = "Erro na conversão: " . $conn->error;
                }
                break;
                
            case 'bulk_update':
                if (isset($_POST['products']) && is_array($_POST['products'])) {
                    $updated = 0;
                    foreach ($_POST['products'] as $product_id => $data) {
                        $query = "UPDATE products SET 
                            unit_type = ?, 
                            unit_display_name = ?, 
                            unit_weight = ?,
                            updated_at = NOW()
                            WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssdi", 
                            $data['unit_type'], 
                            $data['unit_display'], 
                            $data['unit_weight'], 
                            $product_id
                        );
                        
                        if ($stmt->execute()) {
                            $updated++;
                        }
                    }
                    $success_message = "{$updated} produtos atualizados em lote!";
                }
                break;
        }
    }
}

// Buscar produtos para análise
$query = "SELECT p.id, p.name, p.unit_type, p.unit_display_name, p.unit_weight, 
          c.name as category_name,
          p.wholesale_price, p.min_wholesale_quantity
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 1 
          ORDER BY 
            CASE 
                WHEN p.name LIKE '%ml%' OR p.name LIKE '%ML%' THEN 1
                WHEN p.unit_type = 'unit' THEN 2
                ELSE 3
            END,
            p.name";
$result = $conn->query($query);

// Análise de produtos
$liquid_products = [];
$bulk_products = [];
$unit_products = [];

$result_analysis = $conn->query($query);
while ($product = $result_analysis->fetch_assoc()) {
    if (preg_match('/\d+\s*(ml|ML)/i', $product['name'])) {
        $liquid_products[] = $product;
    } elseif ($product['unit_type'] === 'kg') {
        $bulk_products[] = $product;
    } else {
        $unit_products[] = $product;
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<style>
.unit-badge {
    font-size: 0.8em;
    padding: 4px 8px;
}
.product-row {
    transition: all 0.3s ease;
}
.product-row:hover {
    background-color: rgba(0,123,255,0.1);
}
.analysis-card {
    border-left: 4px solid;
    background: #f8f9fa;
}
.liquid-card { border-left-color: #17a2b8; }
.bulk-card { border-left-color: #28a745; }
.unit-card { border-left-color: #ffc107; }
.quick-actions {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 20px;
}
</style>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>
                        Gestão de Unidades dos Produtos
                    </h4>
                    <small>Organize produtos líquidos (por unidade) e a granel (por kg)</small>
                </div>
                
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Análise Rápida -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="analysis-card liquid-card p-3">
                                <h6><i class="fas fa-flask me-2"></i>Produtos Líquidos</h6>
                                <div class="fs-3 fw-bold text-info"><?php echo count($liquid_products); ?></div>
                                <small>Produtos com ml/ML no nome</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="analysis-card bulk-card p-3">
                                <h6><i class="fas fa-weight me-2"></i>Produtos a Granel</h6>
                                <div class="fs-3 fw-bold text-success"><?php echo count($bulk_products); ?></div>
                                <small>Vendidos por kg</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="analysis-card unit-card p-3">
                                <h6><i class="fas fa-boxes me-2"></i>Produtos Unitários</h6>
                                <div class="fs-3 fw-bold text-warning"><?php echo count($unit_products); ?></div>
                                <small>Vendidos por unidade</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ações Rápidas -->
                    <div class="quick-actions mb-4">
                        <h5><i class="fas fa-magic me-2"></i>Ações Rápidas</h5>
                        <p class="mb-3">Otimize seu catálogo com conversões automáticas</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="convert_liquids">
                                    <button type="submit" class="btn btn-light btn-sm" 
                                            onclick="return confirm('Converter todos os produtos líquidos (com ml) para vendas por unidade? Isso mudará a forma como são exibidos no site.')">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        Converter Líquidos para Unidades
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="opacity-75">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Líquidos serão vendidos por frasco/unidade ao invés de ml
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navegação por Abas -->
                    <ul class="nav nav-tabs" id="unitsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="liquids-tab" data-bs-toggle="tab" data-bs-target="#liquids" type="button">
                                <i class="fas fa-flask me-2"></i>Produtos Líquidos (<?php echo count($liquid_products); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" type="button">
                                <i class="fas fa-weight me-2"></i>Produtos a Granel (<?php echo count($bulk_products); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" type="button">
                                <i class="fas fa-boxes me-2"></i>Produtos Unitários (<?php echo count($unit_products); ?>)
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-4" id="unitsTabContent">
                        <!-- Produtos Líquidos -->
                        <div class="tab-pane fade show active" id="liquids">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-flask me-2 text-info"></i>Produtos Líquidos</h5>
                                <small class="text-muted">Produtos com ml/ML no nome</small>
                            </div>
                            
                            <?php if (empty($liquid_products)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Nenhum produto líquido encontrado.
                                </div>
                            <?php else: ?>
                                <form method="POST" id="liquidForm">
                                    <input type="hidden" name="action" value="bulk_update">
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Produto</th>
                                                    <th>Categoria</th>
                                                    <th>Unidade Atual</th>
                                                    <th>Nova Configuração</th>
                                                    <th>Sugestão</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($liquid_products as $product): ?>
                                                    <?php
                                                    // Extrair volume do nome
                                                    $volume = '';
                                                    if (preg_match('/(\d+)\s*(ml|ML)/i', $product['name'], $matches)) {
                                                        $volume = $matches[1] . 'ml';
                                                    }
                                                    ?>
                                                    <tr class="product-row">
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                            <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?: 'Sem categoria'); ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="unit-badge badge bg-<?php echo $product['unit_type'] == 'kg' ? 'success' : 'info'; ?>">
                                                                <?php echo $product['unit_type']; ?>
                                                            </span>
                                                            <br><small><?php echo htmlspecialchars($product['unit_display_name']); ?></small>
                                                        </td>
                                                        <td>
                                                            <select name="products[<?php echo $product['id']; ?>][unit_type]" class="form-select form-select-sm mb-1">
                                                                <option value="unit" <?php echo $product['unit_type'] == 'unit' ? 'selected' : ''; ?>>Unidade</option>
                                                                <option value="kg" <?php echo $product['unit_type'] == 'kg' ? 'selected' : ''; ?>>Quilograma</option>
                                                            </select>
                                                            <input type="text" name="products[<?php echo $product['id']; ?>][unit_display]" 
                                                                   class="form-control form-control-sm mb-1" 
                                                                   value="<?php echo htmlspecialchars($product['unit_display_name']); ?>"
                                                                   placeholder="unidades">
                                                            <input type="number" name="products[<?php echo $product['id']; ?>][unit_weight]" 
                                                                   class="form-control form-control-sm" 
                                                                   value="<?php echo $product['unit_weight']; ?>"
                                                                   step="0.001" min="0.001">
                                                        </td>
                                                        <td>
                                                            <div class="alert alert-light alert-sm p-2 mb-0">
                                                                <small>
                                                                    <strong>Sugestão:</strong><br>
                                                                    • Tipo: <code>unit</code><br>
                                                                    • Display: <code>unidades</code><br>
                                                                    • Peso: <code>0.5kg</code><br>
                                                                    <em class="text-muted">Frasco de <?php echo $volume; ?></em>
                                                                </small>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Salvar Alterações em Lote
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Produtos a Granel -->
                        <div class="tab-pane fade" id="bulk">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-weight me-2 text-success"></i>Produtos a Granel</h5>
                                <small class="text-muted">Vendidos por quilograma</small>
                            </div>
                            
                            <?php if (empty($bulk_products)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Nenhum produto a granel encontrado.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Produto</th>
                                                <th>Categoria</th>
                                                <th>Configuração</th>
                                                <th>Quantidade Mínima</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bulk_products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?: 'Sem categoria'); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="unit-badge badge bg-success">kg</span>
                                                        <br><small><?php echo htmlspecialchars($product['unit_display_name']); ?></small>
                                                        <br><small class="text-muted">Peso: <?php echo $product['unit_weight']; ?>kg</small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo $product['min_wholesale_quantity']; ?> kg</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Configurado
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Produtos Unitários -->
                        <div class="tab-pane fade" id="units">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="fas fa-boxes me-2 text-warning"></i>Produtos Unitários</h5>
                                <small class="text-muted">Vendidos por unidade</small>
                            </div>
                            
                            <?php if (empty($unit_products)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Nenhum produto unitário encontrado.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Produto</th>
                                                <th>Categoria</th>
                                                <th>Configuração</th>
                                                <th>Quantidade Mínima</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($unit_products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <br><small class="text-muted">ID: <?php echo $product['id']; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?: 'Sem categoria'); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="unit-badge badge bg-warning">unit</span>
                                                        <br><small><?php echo htmlspecialchars($product['unit_display_name']); ?></small>
                                                        <br><small class="text-muted">Peso: <?php echo $product['unit_weight']; ?>kg</small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo $product['min_wholesale_quantity']; ?> unidades</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Configurado
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tooltips do Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-sugestões para produtos líquidos
    document.querySelectorAll('select[name*="unit_type"]').forEach(function(select) {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const displayInput = row.querySelector('input[name*="unit_display"]');
            const weightInput = row.querySelector('input[name*="unit_weight"]');
            
            if (this.value === 'unit') {
                displayInput.value = 'unidades';
                if (weightInput.value == '1.00') {
                    weightInput.value = '0.5';
                }
            } else {
                displayInput.value = 'kg';
                if (weightInput.value == '0.5') {
                    weightInput.value = '1.00';
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 