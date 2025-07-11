<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Verificar se há mensagem de sucesso
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Processar atualização da cotação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rate'])) {
    $new_rate = (float) str_replace(',', '.', $_POST['exchange_rate']);
    
    if ($new_rate > 0) {
        if (updateExchangeRate($new_rate, 'Admin')) {
            $_SESSION['success_message'] = 'Cotação atualizada com sucesso!';
        } else {
            $_SESSION['error_message'] = 'Erro ao atualizar cotação.';
        }
    } else {
        $_SESSION['error_message'] = 'Valor da cotação deve ser maior que zero.';
    }
    
    header('Location: cotacao.php');
    exit;
}

// Obter informações da cotação atual
$rate_info = getExchangeRateInfo();

// Obter alguns produtos para mostrar exemplo
$query = "SELECT id, name, wholesale_price, retail_price FROM products WHERE status = 1 ORDER BY name LIMIT 5";
$result_products = $conn->query($query);

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">
            <i class="fas fa-exchange-alt me-2"></i>
            Cotação Cambial
        </h1>
        <div class="d-flex gap-2">
            <a href="produtos.php" class="btn btn-outline-secondary">
                <i class="fas fa-boxes me-2"></i> Ver Produtos
            </a>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Card da cotação atual -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Cotação Atual
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2 class="text-primary mb-2">
                            1 Real = <?php echo rtrim(rtrim(number_format($rate_info['rate'], 4, ',', '.'), '0'), ','); ?> Guaranis
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-clock me-1"></i>
                            Última atualização: <?php echo date('d/m/Y H:i:s', strtotime($rate_info['updated_at'])); ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            Por: <?php echo htmlspecialchars($rate_info['updated_by']); ?>
                        </p>
                    </div>
                    
                    <!-- Formulário para atualizar cotação -->
                    <form method="post" action="cotacao.php">
                        <div class="mb-3">
                            <label for="exchange_rate" class="form-label">Nova Cotação (1 Real = X Guaranis)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       name="exchange_rate" 
                                       id="exchange_rate" 
                                       class="form-control" 
                                       value="<?php echo rtrim(rtrim(number_format($rate_info['rate'], 2, '.', ''), '0'), '.'); ?>" 
                                       required>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Use ponto (.) para decimais. Ex: 1420 ou 1420.50
                            </div>
                        </div>
                        
                        <button type="submit" name="update_rate" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>
                            Atualizar Cotação
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Como Funciona
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Produtos armazenados em Real (BRL)</strong> no banco de dados
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Conversão automática</strong> para Guaranis na exibição
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Atualização instantânea</strong> de todos os preços
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Controle centralizado</strong> da cotação cambial
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Facilidade de manutenção</strong> dos preços
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Exemplos de produtos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-eye me-2"></i>
                Prévia dos Preços (Primeiros 5 produtos)
            </h5>
        </div>
        <div class="card-body">
            <?php if ($result_products && $result_products->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Preço Real (BRL)</th>
                                <th>Preço Guarani (PYG)</th>
                                <th>Diferença</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $result_products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <small>Atacado:</small> R$ <?php echo number_format($product['wholesale_price'], 2, ',', '.'); ?><br>
                                            <small>Varejo:</small> R$ <?php echo number_format($product['retail_price'], 2, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <small>Atacado:</small> <?php echo formatPriceInGuaranis($product['wholesale_price']); ?><br>
                                            <small>Varejo:</small> <?php echo formatPriceInGuaranis($product['retail_price']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $discount = calculateWholesaleDiscount($product['wholesale_price'], $product['retail_price']);
                                        ?>
                                        <span class="badge bg-success">
                                            <?php echo $discount['discount_percentage']; ?>% desconto atacado
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Dica:</strong> Quando você alterar a cotação acima, todos estes preços serão automaticamente recalculados para os clientes!
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Nenhum produto encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no campo de cotação
    const exchangeRateField = document.getElementById('exchange_rate');
    if (exchangeRateField) {
        exchangeRateField.focus();
        exchangeRateField.select();
    }
    
    // Confirmação antes de atualizar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const newRate = parseFloat(exchangeRateField.value);
            const currentRate = <?php echo $rate_info['rate']; ?>;
            
            if (Math.abs(newRate - currentRate) > (currentRate * 0.1)) {
                if (!confirm('A diferença na cotação é maior que 10%. Tem certeza que deseja continuar?')) {
                    e.preventDefault();
                }
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?> 