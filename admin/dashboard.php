<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';
require_once '../includes/exchange_functions.php';

// Contar total de produtos
$query_products = "SELECT COUNT(*) as total FROM products";
$result_products = $conn->query($query_products);
$total_products = $result_products->fetch_assoc()['total'];

// Contar total de categorias
$query_categories = "SELECT COUNT(*) as total FROM categories";
$result_categories = $conn->query($query_categories);
$total_categories = $result_categories->fetch_assoc()['total'];

// Contar total de pedidos do mês
$query_orders = "SELECT COUNT(*) as total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$result_orders = $conn->query($query_orders);
$total_orders = $result_orders->fetch_assoc()['total'];

// Calcular receita do mês
$query_revenue = "SELECT SUM(total) as total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status != 'cancelado'";
$result_revenue = $conn->query($query_revenue);
$total_revenue = $result_revenue->fetch_assoc()['total'] ?? 0;

// Obter produtos com estoque baixo
$query_low_stock = "SELECT id, name, stock FROM products WHERE stock < 10 AND status = 1 ORDER BY stock ASC LIMIT 5";
$result_low_stock = $conn->query($query_low_stock);

// Obter pedidos recentes
$query_recent_orders = "SELECT id, order_number, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5";
$result_recent_orders = $conn->query($query_recent_orders);

// Obter estatísticas de status dos pedidos
$query_status_stats = "SELECT 
    status,
    COUNT(*) as count
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY status";
$result_status_stats = $conn->query($query_status_stats);

$status_stats = [];
while ($row = $result_status_stats->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}

// CSS adicional para dashboard
$additional_css = '
.stats-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--admin-accent);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stats-number {
    font-size: 32px;
    font-weight: 700;
    color: var(--admin-primary);
    margin-bottom: 8px;
    line-height: 1;
}

.stats-label {
    font-size: 14px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin: 0;
}

.stats-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(52, 152, 219, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--admin-accent);
}

.quick-actions {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 25px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background: var(--admin-accent);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.action-btn:hover {
    background: #2980b9;
    color: white;
    transform: translateY(-2px);
}

.admin-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.admin-table .table {
    margin: 0;
}

.admin-table .table th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    color: #6c757d;
    padding: 15px 20px;
}

.admin-table .table td {
    border: none;
    padding: 15px 20px;
    vertical-align: middle;
}

.admin-table .table tbody tr:not(:last-child) {
    border-bottom: 1px solid #f8f9fa;
}
';

// Incluir layout
include 'includes/admin_layout.php';
?>

<!-- Dashboard Content -->
<div class="row g-4 mb-4">
    <!-- Quick Actions -->
    <div class="col-12">
        <div class="quick-actions">
            <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Ações Rápidas</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="produto_adicionar.php" class="action-btn">
                        <i class="fas fa-plus me-2"></i>Novo Produto
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="categoria_adicionar.php" class="action-btn">
                        <i class="fas fa-tags me-2"></i>Nova Categoria
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="pedidos.php" class="action-btn">
                        <i class="fas fa-eye"></i>
                        <span>Ver Pedidos</span>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="cotacao.php" class="action-btn">
                        <i class="fas fa-exchange-alt me-2"></i>Atualizar Câmbio
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stats-number"><?php echo $total_products; ?></div>
            <p class="stats-label">Produtos Cadastrados</p>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stats-number"><?php echo $total_categories; ?></div>
            <p class="stats-label">Categorias Ativas</p>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stats-number"><?php echo $total_orders; ?></div>
            <p class="stats-label">Pedidos Este Mês</p>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stats-number"><?php echo formatGuarani($total_revenue); ?></div>
            <p class="stats-label">Receita do Mês</p>
        </div>
    </div>
</div>

<!-- Status dos Pedidos -->
<?php if (!empty($status_stats)): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Status dos Pedidos (Este Mês)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($status_stats as $status => $count): ?>
                        <div class="col-md-2">
                            <div class="text-center p-3 border rounded">
                                <div class="h4 text-primary"><?php echo $count; ?></div>
                                <small class="text-muted text-uppercase"><?php echo $status; ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tables Row -->
<div class="row g-4">
    <!-- Produtos com Estoque Baixo -->
    <div class="col-md-6">
        <div class="admin-table">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                    Produtos com Estoque Baixo
                </h5>
            </div>
            <div class="table-responsive">
                <?php if ($result_low_stock && $result_low_stock->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Estoque</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $result_low_stock->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'danger' : 'warning'; ?>">
                                            <?php echo $product['stock']; ?> unidades
                                        </span>
                                    </td>
                                    <td>
                                        <a href="produto_editar.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-admin-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <p>Todos os produtos têm estoque adequado!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Pedidos Recentes -->
    <div class="col-md-6">
        <div class="admin-table">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2 text-info"></i>
                    Pedidos Recentes
                </h5>
            </div>
            <div class="table-responsive">
                <?php if ($result_recent_orders && $result_recent_orders->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $result_recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $order['order_number']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo formatGuarani($order['total']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($order['status']) {
                                                'pendente' => 'warning',
                                                'contatado' => 'info',
                                                'confirmado' => 'primary',
                                                'enviado' => 'success',
                                                'entregue' => 'success',
                                                'cancelado' => 'danger',
                                                default => 'secondary'
                                            }; 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pedidos.php?view_order=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-admin-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>Nenhum pedido recente encontrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>
