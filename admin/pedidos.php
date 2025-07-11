<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';
require_once '../includes/exchange_functions.php';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $order_id = $_POST['order_id'] ?? null;
    
    if ($action === 'delete_order' && $order_id) {
        $conn->begin_transaction();
        try {
            $conn->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
            $conn->prepare("DELETE FROM order_logs WHERE order_id = ?")->execute([$order_id]);
            $conn->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
            $conn->commit();
            $success_message = "Pedido #" . htmlspecialchars($order_id) . " foi excluído com sucesso!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Erro ao excluir pedido: " . $e->getMessage();
        }
    }
    // Outras ações como 'update_status' podem ser adicionadas aqui
}

// Obter estatísticas
$stats_query = "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes, SUM(CASE WHEN status != 'cancelado' THEN total ELSE 0 END) as receita_total FROM orders";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Filtros
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($_GET['status'])) { $where_conditions[] = "status = ?"; $params[] = $_GET['status']; $param_types .= "s"; }
if (!empty($_GET['date_from'])) { $where_conditions[] = "DATE(created_at) >= ?"; $params[] = $_GET['date_from']; $param_types .= "s"; }
if (!empty($_GET['date_to'])) { $where_conditions[] = "DATE(created_at) <= ?"; $params[] = $_GET['date_to']; $param_types .= "s"; }
if (!empty($_GET['search'])) {
    $search_term = "%" . $_GET['search'] . "%";
    $where_conditions[] = "(customer_name LIKE ? OR customer_phone LIKE ? OR order_number LIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $param_types .= "sss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$orders_query = "SELECT * FROM orders $where_clause ORDER BY created_at DESC";

$stmt = $conn->prepare($orders_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();

$page_title = "Gestão de Pedidos";
include 'includes/admin_layout.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <h1>Gestão de Pedidos</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestão de Pedidos</li>
            </ol>
        </nav>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> Ver Loja
            </a>
        </div>
    </div>

    <div class="content-body">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar"></i> Visão Geral & Filtros
                </h5>
            </div>
            <div class="card-body">
                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Total de Pedidos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $stats['pendentes']; ?></div>
                                <div class="stat-label">Pedidos Pendentes</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number">G$ <?php echo number_format($stats['receita_total'], 0, ',', '.'); ?></div>
                                <div class="stat-label">Receita Total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" action="" class="filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   placeholder="Cliente, telefone ou número..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos os Status</option>
                                <option value="pendente" <?php if(($_GET['status'] ?? '') == 'pendente') echo 'selected';?>>Pendente</option>
                                <option value="confirmado" <?php if(($_GET['status'] ?? '') == 'confirmado') echo 'selected';?>>Confirmado</option>
                                <option value="enviado" <?php if(($_GET['status'] ?? '') == 'enviado') echo 'selected';?>>Enviado</option>
                                <option value="entregue" <?php if(($_GET['status'] ?? '') == 'entregue') echo 'selected';?>>Entregue</option>
                                <option value="cancelado" <?php if(($_GET['status'] ?? '') == 'cancelado') echo 'selected';?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="orders-list">
            <?php if ($orders_result->num_rows > 0): ?>
                <?php while($order = $orders_result->fetch_assoc()): ?>
                <div class="card order-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="order-number">Pedido #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                            <span class="badge badge-<?php echo $order['status']; ?> ms-2">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                        <div class="order-total">
                            <strong>G$ <?php echo number_format($order['total'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="order-info">
                                    <p class="mb-1">
                                        <i class="fas fa-user"></i>
                                        <strong>Cliente:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-phone"></i>
                                        <strong>Telefone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar"></i>
                                        <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </p>
                                    <?php if (!empty($order['customer_address'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <strong>Endereço:</strong> <?php echo htmlspecialchars($order['customer_address']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="order-actions">
                                    <a href="pedido_detalhes.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Ver Detalhes
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="confirmDelete(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                        <i class="fas fa-trash-alt"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum pedido encontrado</h5>
                        <p class="text-muted">Não há pedidos que correspondam aos filtros aplicados.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmação para exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir permanentemente o pedido <strong id="orderNumber"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="" class="d-inline" id="deleteForm">
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Excluir Pedido
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid var(--primary-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.5rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.filter-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.order-card {
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary-color);
}

.order-number {
    color: var(--primary-color);
    font-size: 1.1rem;
}

.order-total {
    font-size: 1.25rem;
    color: var(--success-color);
}

.badge-pendente { background-color: #ffc107; color: #000; }
.badge-confirmado { background-color: #17a2b8; color: #fff; }
.badge-enviado { background-color: #007bff; color: #fff; }
.badge-entregue { background-color: #28a745; color: #fff; }
.badge-cancelado { background-color: #dc3545; color: #fff; }

.order-info p {
    margin-bottom: 0.5rem;
}

.order-info i {
    width: 16px;
    margin-right: 0.5rem;
    color: var(--text-secondary);
}

.order-actions .btn {
    margin-left: 0.5rem;
}

.orders-list {
    margin-top: 2rem;
}
</style>

<script>
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderId').value = orderId;
    document.getElementById('orderNumber').textContent = '#' + orderNumber;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>