<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de conversão de moeda
require_once '../includes/exchange_functions.php';

// Verificar se há mensagem de sucesso
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Parâmetros de filtragem e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1; // -1 = todos

// Construir a consulta SQL com base nos filtros
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "p.name LIKE ?";
    $params[] = "%$search%";
    $param_types .= 's';
}

if ($category > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
    $param_types .= 'i';
}

if ($status != -1) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status;
    $param_types .= 'i';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Consulta para contar o total de produtos com os filtros
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$stmt_count = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt_count->bind_param($param_types, ...$params);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_items = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Consulta para obter os produtos com paginação
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $where_clause 
          ORDER BY p.id DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);

$param_types .= 'ii';
$params[] = $items_per_page;
$params[] = $offset;

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Obter categorias para o filtro
$query_categories = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
$result_categories = $conn->query($query_categories);

// CSS adicional
$additional_css = '
.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
}

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 25px;
}

.products-table {
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

.action-buttons {
    display: flex;
    gap: 5px;
}
';

// Incluir layout
include 'includes/admin_layout.php';
?>

<!-- Filtros e Conteúdo -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Gestão de Produtos</h2>
        <p class="text-muted">Gerencie o catálogo de produtos da loja</p>
    </div>
    <a href="produto_adicionar.php" class="btn btn-admin-primary">
        <i class="fas fa-plus me-2"></i> Adicionar Produto
    </a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="filter-card">
    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros de Busca</h5>
    <form action="produtos.php" method="get" class="row g-3">
        <div class="col-md-4">
            <label for="search" class="form-label">Buscar Produto</label>
            <input type="text" class="form-control" id="search" name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Nome do produto">
        </div>
        
        <div class="col-md-3">
            <label for="category" class="form-label">Categoria</label>
            <select class="form-select" id="category" name="category">
                <option value="0">Todas as categorias</option>
                <?php 
                $result_categories->data_seek(0); // Reset result pointer
                while ($cat = $result_categories->fetch_assoc()): 
                ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="-1" <?php echo $status == -1 ? 'selected' : ''; ?>>Todos</option>
                <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>Ativos</option>
                <option value="0" <?php echo $status === 0 ? 'selected' : ''; ?>>Inativos</option>
            </select>
        </div>
        
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-admin-primary w-100">
                <i class="fas fa-search me-2"></i> Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Listagem de produtos -->
<div class="products-table">
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col" width="80">ID</th>
                        <th scope="col" width="80">Imagem</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Unidade</th>
                        <th scope="col">Preço Atacado</th>
                        <th scope="col">Preço Varejo</th>
                        <th scope="col">Min. Atacado</th>
                        <th scope="col">Estoque</th>
                        <th scope="col">Status</th>
                        <th scope="col" width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $product['id']; ?></strong></td>
                            <td>
                                <img src="<?php echo !empty($product['image_url']) ? '../' . $product['image_url'] : '../assets/images/no-image.png'; ?>" 
                                    alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                    class="product-image">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $unit_type = $product['unit_type'] ?? 'kg';
                                $unit_display = $product['unit_display_name'] ?? 'kg';
                                $unit_weight = $product['unit_weight'] ?? '1.00';
                                ?>
                                <span class="badge bg-<?php echo $unit_type == 'kg' ? 'primary' : 'info'; ?>">
                                    <?php echo $unit_type == 'kg' ? 'Granel' : 'Unitário'; ?>
                                </span>
                                <br><small class="text-muted"><?php echo htmlspecialchars($unit_display); ?></small>
                            </td>
                            <td><strong><?php echo formatPriceInGuaranis($product['wholesale_price']); ?></strong></td>
                            <td><strong><?php echo formatPriceInGuaranis($product['retail_price']); ?></strong></td>
                            <td><?php echo $product['min_wholesale_quantity']; ?> <?php echo htmlspecialchars($unit_display); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['stock'] > 10 ? 'success' : ($product['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $product['stock']; ?> <?php echo htmlspecialchars($unit_display); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $product['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo $product['status'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="produto_editar.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-admin-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" 
                                            data-id="<?php echo $product['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted">
                Exibindo <?php echo min($offset + 1, $total_items); ?> a <?php echo min($offset + $items_per_page, $total_items); ?> de <?php echo $total_items; ?> produtos
            </div>
            
            <nav aria-label="Paginação de produtos">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhum produto encontrado</h5>
            <p class="text-muted">Não há produtos que correspondam aos critérios de busca.</p>
            <a href="produto_adicionar.php" class="btn btn-admin-primary">
                <i class="fas fa-plus me-2"></i> Adicionar Primeiro Produto
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts específicos da página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmar exclusão de produtos
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            
            Swal.fire({
                title: 'Confirmar exclusão',
                text: `Tem certeza que deseja excluir o produto "${productName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Criar formulário invisível para enviar POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'produto_excluir.php';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = productId;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>