<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';
require_once '../includes/exchange_functions.php';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $order_id = $_POST['order_id'] ?? null;
        
        if ($action === 'update_status' && $order_id) {
            $new_status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            
            if ($stmt->execute()) {
                // Log da alteração
                $admin_user = $_SESSION['admin_user'] ?? 'Sistema';
                $log_stmt = $conn->prepare("INSERT INTO order_logs (order_id, action_type, action_data, admin_user) VALUES (?, 'status_update', ?, ?)");
                $log_data = json_encode(['old_status' => '', 'new_status' => $new_status]);
                $log_stmt->bind_param("iss", $order_id, $log_data, $admin_user);
                $log_stmt->execute();
                
                $success_message = "Status atualizado com sucesso!";
            } else {
                $error_message = "Erro ao atualizar status.";
            }
        }
        
        if ($action === 'delete_order' && $order_id) {
            // LÓGICA DE EXCLUSÃO MELHORADA COM TRANSAÇÃO
            $conn->begin_transaction();
            try {
                // 1. Excluir itens do pedido da tabela 'order_items'
                $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                $stmt_items->bind_param("i", $order_id);
                $stmt_items->execute();
                
                // 2. Excluir logs do pedido da tabela 'order_logs'
                $stmt_logs = $conn->prepare("DELETE FROM order_logs WHERE order_id = ?");
                $stmt_logs->bind_param("i", $order_id);
                $stmt_logs->execute();
                
                // 3. Excluir o pedido principal da tabela 'orders'
                $stmt_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt_order->bind_param("i", $order_id);
                $stmt_order->execute();
                
                // Se tudo deu certo, confirma a transação
                $conn->commit();
                $success_message = "Pedido #" . htmlspecialchars($order_id) . " e todos os seus dados foram excluídos com sucesso!";
                
            } catch (Exception $e) {
                // Se algo deu errado, reverte a transação
                $conn->rollback();
                $error_message = "Erro ao excluir pedido: " . $e->getMessage();
            }
        }
        
        if ($action === 'add_note' && $order_id) {
            $note = $_POST['note'];
            $stmt = $conn->prepare("UPDATE orders SET admin_notes = ? WHERE id = ?");
            $stmt->bind_param("si", $note, $order_id);
            
            if ($stmt->execute()) {
                // Log da nota
                $admin_user = $_SESSION['admin_user'] ?? 'Sistema';
                $log_stmt = $conn->prepare("INSERT INTO order_logs (order_id, action_type, action_data, admin_user) VALUES (?, 'note_added', ?, ?)");
                $log_data = json_encode(['note' => $note]);
                $log_stmt->bind_param("iss", $order_id, $log_data, $admin_user);
                $log_stmt->execute();
                
                $success_message = "Nota adicionada com sucesso!";
            } else {
                $error_message = "Erro ao adicionar nota.";
            }
        }
        
        if ($action === 'update_customer' && $order_id) {
            $customer_name = $_POST['customer_name'];
            $customer_email = $_POST['customer_email'];
            $customer_phone = $_POST['customer_phone'];
            $customer_address = $_POST['customer_address'];
            
            $stmt = $conn->prepare("UPDATE orders SET customer_name = ?, customer_email = ?, customer_phone = ?, customer_address = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $customer_name, $customer_email, $customer_phone, $customer_address, $order_id);
            
            if ($stmt->execute()) {
                // Log da alteração
                $admin_user = $_SESSION['admin_user'] ?? 'Sistema';
                $log_stmt = $conn->prepare("INSERT INTO order_logs (order_id, action_type, action_data, admin_user) VALUES (?, 'customer_update', ?, ?)");
                $log_data = json_encode(['customer_name' => $customer_name, 'customer_email' => $customer_email]);
                $log_stmt->bind_param("iss", $order_id, $log_data, $admin_user);
                $log_stmt->execute();
                
                $success_message = "Dados do cliente atualizados com sucesso!";
            } else {
                $error_message = "Erro ao atualizar dados do cliente.";
            }
        }
    }
}

// Obter estatísticas (código original mantido)
$stats_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
    COUNT(CASE WHEN status = 'contatado' THEN 1 END) as contatados,
    COUNT(CASE WHEN status = 'confirmado' THEN 1 END) as confirmados,
    COUNT(CASE WHEN status = 'enviado' THEN 1 END) as enviados,
    COUNT(CASE WHEN status = 'entregue' THEN 1 END) as entregues,
    COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
    SUM(CASE WHEN status != 'cancelado' THEN total ELSE 0 END) as receita_total
    FROM orders";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Filtros (código original mantido)
$where_conditions = [];
$params = [];
$param_types = "";

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where_conditions[] = "status = ?";
    $params[] = $_GET['status'];
    $param_types .= "s";
}

if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $_GET['date_from'];
    $param_types .= "s";
}

if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $_GET['date_to'];
    $param_types .= "s";
}

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $where_conditions[] = "(customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR order_number LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $param_types .= "ssss";
}

// Consulta principal (código original mantido)
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$orders_query = "SELECT * FROM orders $where_clause ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($orders_query);
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $orders_result = $stmt->get_result();
} else {
    $orders_result = $conn->query($orders_query);
}

// CSS (código original mantido)
$additional_css = '
.order-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #e9ecef; margin-bottom: 20px; transition: all 0.3s ease; }
.order-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.15); transform: translateY(-2px); }
.order-header { padding: 20px 25px; border-bottom: 1px solid #f0f0f0; background: linear-gradient(45deg, #f8f9fa, #ffffff); }
.order-body { padding: 25px; }
.status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #e9ecef; margin-bottom: 25px; }
.stats-overview { background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary)); color: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; }
.stat-item { text-align: center; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; }
.stat-number { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
.stat-label { font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; }
.action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
.product-list { background: #f8f9fa; border-radius: 8px; padding: 15px; margin: 15px 0; }
.product-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
.product-item:last-child { border-bottom: none; }
.btn-action { padding: 6px 12px; font-size: 12px; border-radius: 6px; transition: all 0.3s ease; }
.modal-content { border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
.modal-header { background: var(--admin-primary); color: white; border-radius: 12px 12px 0 0; border-bottom: none; }
.quick-status-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
.status-btn { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none; cursor: pointer; transition: all 0.3s ease; }
.status-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
';

// Incluir layout (código original mantido)
include 'includes/admin_layout.php'; 
?>

<!-- Interface HTML (código original com adição do botão de exclusão) -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestor de Pedidos Melhorado</h1>
    
    <?php if(isset($success_message)): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if(isset($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

    <!-- Seção de filtros e estatísticas (código original mantido) -->
    <div class="stats-overview">
        <h5 class="mb-4">Visão Geral</h5>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['pendentes']; ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['confirmados']; ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['cancelados']; ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">G$ <?php echo number_format($stats['receita_total'], 0, ',', '.'); ?></div>
                <div class="stat-label">Receita</div>
            </div>
        </div>
    </div>
    
    <div class="filter-card">
        <form method="GET" action="">
            <div class="row align-items-end">
                <div class="col-md-3 mb-2"><label>Buscar</label><input type="text" name="search" class="form-control" placeholder="Nome, fone, email, pedido..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"></div>
                <div class="col-md-2 mb-2"><label>De</label><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>"></div>
                <div class="col-md-2 mb-2"><label>Até</label><input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>"></div>
                <div class="col-md-3 mb-2"><label>Status</label><select name="status" class="form-control"><option value="">Todos</option><option value="pendente" <?php if(($_GET['status'] ?? '') == 'pendente') echo 'selected';?>>Pendente</option><option value="contatado" <?php if(($_GET['status'] ?? '') == 'contatado') echo 'selected';?>>Contatado</option><option value="confirmado" <?php if(($_GET['status'] ?? '') == 'confirmado') echo 'selected';?>>Confirmado</option><option value="enviado" <?php if(($_GET['status'] ?? '') == 'enviado') echo 'selected';?>>Enviado</option><option value="entregue" <?php if(($_GET['status'] ?? '') == 'entregue') echo 'selected';?>>Entregue</option><option value="cancelado" <?php if(($_GET['status'] ?? '') == 'cancelado') echo 'selected';?>>Cancelado</option></select></div>
                <div class="col-md-2 mb-2 d-grid"><button type="submit" class="btn btn-primary">Filtrar</button></div>
            </div>
        </form>
    </div>

    <!-- Loop de Pedidos (código original com adição do botão de exclusão) -->
    <?php while($order = $orders_result->fetch_assoc()): ?>
    <div class="order-card">
        <div class="order-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Pedido #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
            </div>
            <span class="status-badge" style="background-color: #ddd;"><?php echo htmlspecialchars($order['status']); ?></span>
        </div>
        <div class="order-body">
            <h6>Cliente</h6>
            <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
            <?php echo htmlspecialchars($order['customer_phone']); ?><br>
            <?php echo htmlspecialchars($order['customer_email']); ?></p>
            
            <div class="d-flex justify-content-between align-items-center">
                <h6>Total: <span class="text-success">G$ <?php echo number_format($order['total'], 0, ',', '.'); ?></span></h6>
                <div class="action-buttons">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailsModal-<?php echo $order['id']; ?>">
                        <i class="fas fa-search-plus me-1"></i> Ver Detalhes
                    </button>
                    <!-- BOTÃO DE EXCLUSÃO ADICIONADO AQUI -->
                    <form method="POST" action="pedidos_melhorado.php" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente o pedido #<?php echo htmlspecialchars($order['order_number']); ?>? Esta ação não pode ser desfeita.');">
                        <input type="hidden" name="action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modais de detalhes (código original mantido) -->
    <?php endwhile; ?>
</div>

<?php include 'includes/footer_admin.php'; ?>