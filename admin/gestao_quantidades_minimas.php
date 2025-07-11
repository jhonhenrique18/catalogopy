<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_global_settings':
                updateGlobalSettings();
                break;
            case 'bulk_update_minimums':
                bulkUpdateMinimums();
                break;
            case 'update_product_minimum':
                updateProductMinimum();
                break;
        }
    }
}

function updateGlobalSettings() {
    global $conn;
    
    $enableGlobalMinimums = isset($_POST['enable_global_minimums']) ? 1 : 0;
    $explanationText = trim($_POST['minimum_explanation_text']);
    
    $query = "UPDATE store_settings SET enable_global_minimums = ?, minimum_explanation_text = ? WHERE id = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $enableGlobalMinimums, $explanationText);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Configurações globais atualizadas com sucesso!';
    } else {
        $_SESSION['error_message'] = 'Erro ao atualizar configurações: ' . $conn->error;
    }
}

function bulkUpdateMinimums() {
    global $conn;
    
    $action = $_POST['bulk_action'];
    $selectedProducts = $_POST['selected_products'] ?? [];
    
    if (empty($selectedProducts)) {
        $_SESSION['error_message'] = 'Selecione pelo menos um produto';
        return;
    }
    
    $placeholders = str_repeat('?,', count($selectedProducts) - 1) . '?';
    
    switch ($action) {
        case 'enable_minimums':
            $query = "UPDATE products SET has_min_quantity = 1 WHERE id IN ($placeholders)";
            break;
        case 'disable_minimums':
            $query = "UPDATE products SET has_min_quantity = 0 WHERE id IN ($placeholders)";
            break;
        default:
            $_SESSION['error_message'] = 'Ação inválida';
            return;
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('i', count($selectedProducts)), ...$selectedProducts);
    
    if ($stmt->execute()) {
        $count = $stmt->affected_rows;
        $_SESSION['success_message'] = "$count produtos atualizados com sucesso!";
    } else {
        $_SESSION['error_message'] = 'Erro ao atualizar produtos: ' . $conn->error;
    }
}

function updateProductMinimum() {
    global $conn;
    
    $productId = (int)$_POST['product_id'];
    $hasMinQuantity = isset($_POST['has_min_quantity']) ? 1 : 0;
    $minQuantity = $hasMinQuantity ? (int)$_POST['min_wholesale_quantity'] : null;
    
    $query = "UPDATE products SET has_min_quantity = ?, min_wholesale_quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $hasMinQuantity, $minQuantity, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Produto atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar produto: ' . $conn->error]);
    }
    exit;
}

// Obter configurações atuais
$query = "SELECT enable_global_minimums, minimum_explanation_text FROM store_settings WHERE id = 1";
$result = $conn->query($query);
$settings = $result->fetch_assoc();

// Obter estatísticas
$statsQuery = "SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN has_min_quantity = 1 THEN 1 ELSE 0 END) as products_with_minimum,
    SUM(CASE WHEN has_min_quantity = 0 THEN 1 ELSE 0 END) as products_without_minimum
    FROM products WHERE status = 1";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Obter produtos para listagem
$productsQuery = "SELECT p.id, p.name, p.min_wholesale_quantity, p.has_min_quantity, 
                  p.unit_type, p.unit_display_name, c.name as category_name
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 1 
                  ORDER BY p.has_min_quantity DESC, p.name ASC";
$productsResult = $conn->query($productsQuery);

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<style>
.minimum-management-container {
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.success {
    border-left-color: #28a745;
}

.stat-card.warning {
    border-left-color: #ffc107;
}

.stat-card.info {
    border-left-color: #17a2b8;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    align-items: center;
}

.section-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
}

.section-header i {
    margin-right: 12px;
    font-size: 24px;
}

.section-content {
    padding: 25px;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.products-table th,
.products-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.products-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.products-table tr:hover {
    background: #f8f9fa;
}

.minimum-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.minimum-badge.enabled {
    background: #d4edda;
    color: #155724;
}

.minimum-badge.disabled {
    background: #f8d7da;
    color: #721c24;
}

.bulk-actions {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.form-switch-modern {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.form-switch-modern input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider-modern {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.slider-modern:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider-modern {
    background-color: #28a745;
}

input:checked + .slider-modern:before {
    transform: translateX(26px);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .bulk-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .products-table {
        font-size: 14px;
    }
    
    .products-table th,
    .products-table td {
        padding: 8px;
    }
}
</style>

<!-- Conteúdo principal -->
<div class="minimum-management-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title">
                <i class="fas fa-sort-numeric-up text-primary me-2"></i>
                Gestão de Quantidades Mínimas
            </h1>
            <p class="text-muted">Controle centralizado de quantidades mínimas por produto</p>
        </div>
        <a href="produtos.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar aos Produtos
        </a>
    </div>

    <!-- Alertas -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card info">
            <div class="stat-number"><?php echo $stats['total_products']; ?></div>
            <div class="stat-label">Total de Produtos</div>
        </div>
        <div class="stat-card success">
            <div class="stat-number"><?php echo $stats['products_with_minimum']; ?></div>
            <div class="stat-label">Com Quantidade Mínima</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number"><?php echo $stats['products_without_minimum']; ?></div>
            <div class="stat-label">Sem Quantidade Mínima</div>
        </div>
    </div>

    <!-- Configurações Globais -->
    <div class="section-card">
        <div class="section-header">
            <h3><i class="fas fa-cog"></i>Configurações Globais</h3>
        </div>
        <div class="section-content">
            <form method="post" class="row">
                <input type="hidden" name="action" value="update_global_settings">
                
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="enable_global_minimums" 
                               name="enable_global_minimums" <?php echo $settings['enable_global_minimums'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="enable_global_minimums">
                            <i class="fas fa-toggle-on text-success me-2"></i>
                            Forçar Quantidades Mínimas Globalmente
                        </label>
                        <div class="form-text">
                            Quando ativo, TODOS os produtos que têm quantidade mínima configurada serão obrigatórios.
                            Quando inativo, você controla individualmente por produto.
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold">Texto Explicativo</label>
                    <input type="text" class="form-control" name="minimum_explanation_text" 
                           value="<?php echo htmlspecialchars($settings['minimum_explanation_text']); ?>"
                           placeholder="Ex: Quantidade mínima para atacado">
                    <div class="form-text">Texto exibido junto aos produtos com quantidade mínima</div>
                </div>
                
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Configurações Globais
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Gerenciamento de Produtos -->
    <div class="section-card">
        <div class="section-header">
            <h3><i class="fas fa-list"></i>Gerenciamento por Produto</h3>
        </div>
        <div class="section-content">
            <!-- Ações em Lote -->
            <form method="post" id="bulkForm">
                <input type="hidden" name="action" value="bulk_update_minimums">
                
                <div class="bulk-actions">
                    <div class="flex-grow-1">
                        <strong>Ações em Lote:</strong>
                        <span class="text-muted">Selecione produtos e escolha uma ação</span>
                    </div>
                    <select name="bulk_action" class="form-select" style="width: auto;">
                        <option value="">Escolha uma ação...</option>
                        <option value="enable_minimums">Ativar Quantidade Mínima</option>
                        <option value="disable_minimums">Desativar Quantidade Mínima</option>
                    </select>
                    <button type="button" class="btn btn-primary" onclick="executeBulkAction()">
                        <i class="fas fa-play me-2"></i>Executar
                    </button>
                </div>

                <!-- Tabela de Produtos -->
                <div class="table-responsive">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleAllProducts(this)">
                                </th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Tipo/Unidade</th>
                                <th>Qtd. Mínima</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $productsResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_products[]" 
                                               value="<?php echo $product['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo htmlspecialchars($product['category_name'] ?? 'Sem categoria'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo ucfirst($product['unit_type']); ?> - 
                                            <?php echo htmlspecialchars($product['unit_display_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['has_min_quantity']): ?>
                                            <strong class="text-success">
                                                <?php echo $product['min_wholesale_quantity']; ?> 
                                                <?php echo htmlspecialchars($product['unit_display_name']); ?>
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted">Sem mínimo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="minimum-badge <?php echo $product['has_min_quantity'] ? 'enabled' : 'disabled'; ?>">
                                            <?php echo $product['has_min_quantity'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editProductMinimum(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
<div class="modal fade" id="editMinimumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Quantidade Mínima
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editMinimumForm">
                    <input type="hidden" name="action" value="update_product_minimum">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    
                    <div class="mb-3">
                        <strong id="edit_product_name">Nome do Produto</strong>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="edit_has_min_quantity" 
                                   name="has_min_quantity" onchange="toggleMinQuantityField()">
                            <label class="form-check-label fw-bold" for="edit_has_min_quantity">
                                Ativar Quantidade Mínima
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="minQuantityField" style="display: none;">
                        <label class="form-label fw-bold">Quantidade Mínima</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="min_wholesale_quantity" 
                                   id="edit_min_quantity" min="1" value="1">
                            <span class="input-group-text" id="edit_unit_display">unidades</span>
                        </div>
                        <div class="form-text">Quantidade mínima que o cliente deve comprar</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveProductMinimum()">
                    <i class="fas fa-save me-2"></i>Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Selecionar todos os produtos
function toggleAllProducts(checkbox) {
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    productCheckboxes.forEach(cb => cb.checked = checkbox.checked);
}

// Executar ação em lote
function executeBulkAction() {
    const form = document.getElementById('bulkForm');
    const action = form.querySelector('[name="bulk_action"]').value;
    const selectedProducts = form.querySelectorAll('input[name="selected_products[]"]:checked');
    
    if (!action) {
        alert('Selecione uma ação para executar');
        return;
    }
    
    if (selectedProducts.length === 0) {
        alert('Selecione pelo menos um produto');
        return;
    }
    
    const actionText = action === 'enable_minimums' ? 'ativar' : 'desativar';
    if (confirm(`Tem certeza que deseja ${actionText} quantidade mínima para ${selectedProducts.length} produto(s)?`)) {
        form.submit();
    }
}

// Editar produto específico
let currentProductData = {};

function editProductMinimum(productId) {
    // Buscar dados do produto na tabela
    const row = document.querySelector(`input[value="${productId}"]`).closest('tr');
    const cells = row.querySelectorAll('td');
    
    currentProductData = {
        id: productId,
        name: cells[1].querySelector('strong').textContent,
        hasMinQuantity: cells[5].querySelector('.minimum-badge').classList.contains('enabled'),
        minQuantity: cells[4].querySelector('strong') ? 
                    parseInt(cells[4].querySelector('strong').textContent.split(' ')[0]) : 1,
        unitDisplay: cells[3].textContent.split(' - ')[1] || 'unidades'
    };
    
    // Preencher modal
    document.getElementById('edit_product_id').value = currentProductData.id;
    document.getElementById('edit_product_name').textContent = currentProductData.name;
    document.getElementById('edit_has_min_quantity').checked = currentProductData.hasMinQuantity;
    document.getElementById('edit_min_quantity').value = currentProductData.minQuantity;
    document.getElementById('edit_unit_display').textContent = currentProductData.unitDisplay;
    
    toggleMinQuantityField();
    
    // Mostrar modal
    new bootstrap.Modal(document.getElementById('editMinimumModal')).show();
}

function toggleMinQuantityField() {
    const checkbox = document.getElementById('edit_has_min_quantity');
    const field = document.getElementById('minQuantityField');
    
    if (checkbox.checked) {
        field.style.display = 'block';
        document.getElementById('edit_min_quantity').required = true;
    } else {
        field.style.display = 'none';
        document.getElementById('edit_min_quantity').required = false;
    }
}

function saveProductMinimum() {
    const form = document.getElementById('editMinimumForm');
    const formData = new FormData(form);
    
    fetch('gestao_quantidades_minimas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produto atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar alterações');
    });
}

// Atualizar contador de produtos selecionados
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-checkbox')) {
        const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
        // Implementar feedback visual se necessário
    }
});
</script>

<?php include 'includes/footer.php'; ?> 