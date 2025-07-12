<?php
/**
 * PAINEL ADMINISTRATIVO - CONFIGURAÇÃO DE WEBHOOKS
 * Sistema completo para automação de pedidos com Make.com
 */

// Verificar autenticação
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/webhook_functions.php';

$page_title = "Configuração de Webhooks";

// Processar formulário
$success_message = '';
$error_message = '';
$test_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        // Salvar configurações
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        $secret_key = trim($_POST['secret_key'] ?? '');
        $timeout = intval($_POST['timeout'] ?? 30);
        $retry_attempts = intval($_POST['retry_attempts'] ?? 3);
        $retry_delay = intval($_POST['retry_delay'] ?? 5);
        
        $query = "UPDATE webhook_settings SET 
                    enabled = ?, webhook_url = ?, secret_key = ?, 
                    timeout = ?, retry_attempts = ?, retry_delay = ?, 
                    updated_at = NOW() 
                  WHERE id = 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issiii", $enabled, $webhook_url, $secret_key, $timeout, $retry_attempts, $retry_delay);
        
        if ($stmt->execute()) {
            $success_message = "Configurações salvas com sucesso!";
        } else {
            $error_message = "Erro ao salvar configurações: " . $stmt->error;
        }
    }
    
    if ($action === 'test_webhook') {
        // Testar webhook
        $webhook_url = trim($_POST['test_url'] ?? '');
        $secret_key = trim($_POST['test_secret'] ?? '');
        
        if (!empty($webhook_url)) {
            $test_result = testWebhookConnection($webhook_url, $secret_key);
        } else {
            $error_message = "URL do webhook é obrigatória para teste";
        }
    }
}

// Buscar configurações atuais
$webhook_settings = getWebhookSettings($conn);

// Buscar logs recentes
$logs_query = "SELECT wl.*, o.order_number 
               FROM webhook_logs wl 
               LEFT JOIN orders o ON wl.order_id = o.id 
               ORDER BY wl.created_at DESC 
               LIMIT 20";
$logs_result = $conn->query($logs_query);
$logs = $logs_result->fetch_all(MYSQLI_ASSOC);

include 'includes/admin_layout.php';
?>

<style>
.webhook-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.webhook-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 15px;
    font-weight: 600;
    color: #212529;
}

.alert {
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.status-enabled {
    color: #28a745;
    font-weight: bold;
}

.status-disabled {
    color: #dc3545;
    font-weight: bold;
}

.test-result {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.test-success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.test-error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.logs-table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.logs-table th {
    background: #f8f9fa;
    border: none;
    padding: 15px;
    font-weight: 600;
}

.logs-table td {
    padding: 12px 15px;
    border-top: 1px solid #e9ecef;
}

.badge-success {
    background: #28a745;
}

.badge-danger {
    background: #dc3545;
}

.help-box {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.help-box h5 {
    color: #004085;
    margin-bottom: 15px;
}

.help-box p {
    color: #004085;
    margin-bottom: 10px;
}

.code-example {
    background: #2d3748;
    color: #e2e8f0;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    margin: 10px 0;
    overflow-x: auto;
}
</style>

<div class="content-wrapper">
    <div class="webhook-header">
        <h1><i class="fas fa-link"></i> Configuração de Webhooks</h1>
        <p class="mb-0">Sistema de automação para integração com Make.com, Zapier e outras plataformas</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Status Atual -->
    <div class="webhook-card">
        <h4><i class="fas fa-info-circle"></i> Status Atual</h4>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Status:</strong> 
                    <?php if ($webhook_settings['enabled']): ?>
                        <span class="status-enabled"><i class="fas fa-check-circle"></i> ATIVO</span>
                    <?php else: ?>
                        <span class="status-disabled"><i class="fas fa-times-circle"></i> INATIVO</span>
                    <?php endif; ?>
                </p>
                <p><strong>URL:</strong> <?php echo $webhook_settings['webhook_url'] ? htmlspecialchars($webhook_settings['webhook_url']) : '<em>Não configurada</em>'; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Timeout:</strong> <?php echo $webhook_settings['timeout']; ?>s</p>
                <p><strong>Tentativas:</strong> <?php echo $webhook_settings['retry_attempts']; ?>x</p>
            </div>
        </div>
    </div>

    <!-- Guia Completo Make.com -->
    <div class="help-box">
        <h5><i class="fab fa-app-store"></i> Guia Completo para Make.com</h5>
        
        <h6><strong>1. Criar Cenário no Make.com:</strong></h6>
        <p>• Acesse <a href="https://make.com" target="_blank">make.com</a> e crie uma nova conta ou faça login</p>
        <p>• Clique em "Create a new scenario"</p>
        <p>• Adicione o módulo "Webhooks" > "Custom webhook"</p>
        <p>• Copie a URL gerada (algo como: https://hook.eu1.make.com/xxxxxxxxx)</p>
        
        <h6><strong>2. Configurar no Catalogopy:</strong></h6>
        <p>• Cole a URL do Make.com no campo "URL do Webhook" abaixo</p>
        <p>• Ative o webhook marcando "Habilitar Webhook"</p>
        <p>• Clique em "Salvar Configurações"</p>
        
        <h6><strong>3. Dados que você receberá:</strong></h6>
        <div class="code-example">
{
    "event": "new_order",
    "order": {
        "id": 123,
        "number": "ORD-20241201-0001",
        "customer": {
            "name": "João Silva",
            "phone": "595991234567",
            "email": "joao@email.com",
            "address": "Rua das Flores, 123"
        },
        "items": [...],
        "totals": {...},
        "whatsapp_message": "⚡ MENSAGEM PRONTA PARA WHATSAPP!",
        "whatsapp_phone": "595991234567"
    }
}
        </div>
        
        <h6><strong>4. Como usar a mensagem do WhatsApp:</strong></h6>
        <p>• A variável <code>whatsapp_message</code> já contém a mensagem formatada</p>
        <p>• Use <code>whatsapp_phone</code> como destinatário</p>
        <p>• Conecte com módulo WhatsApp Business ou ZAPI</p>
        <p>• A mensagem já inclui todos os detalhes do pedido!</p>
    </div>

    <!-- Configurações -->
    <div class="webhook-card">
        <h4><i class="fas fa-cogs"></i> Configurações</h4>
        
        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">URL do Webhook *</label>
                        <input type="url" class="form-control" name="webhook_url" 
                               value="<?php echo htmlspecialchars($webhook_settings['webhook_url']); ?>"
                               placeholder="https://hook.eu1.make.com/xxxxxxxxx">
                        <small class="text-muted">URL gerada pelo Make.com, Zapier ou outra plataforma</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="enabled" 
                                   <?php echo $webhook_settings['enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Habilitar Webhook</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Chave Secreta (Opcional)</label>
                        <input type="text" class="form-control" name="secret_key" 
                               value="<?php echo htmlspecialchars($webhook_settings['secret_key']); ?>"
                               placeholder="Deixe vazio se não usar">
                        <small class="text-muted">Para autenticação adicional (header X-Webhook-Secret)</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Timeout</label>
                        <input type="number" class="form-control" name="timeout" min="5" max="120"
                               value="<?php echo $webhook_settings['timeout']; ?>">
                        <small class="text-muted">Segundos</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Tentativas</label>
                        <input type="number" class="form-control" name="retry_attempts" min="1" max="5"
                               value="<?php echo $webhook_settings['retry_attempts']; ?>">
                        <small class="text-muted">Reenvios</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Delay</label>
                        <input type="number" class="form-control" name="retry_delay" min="1" max="60"
                               value="<?php echo $webhook_settings['retry_delay']; ?>">
                        <small class="text-muted">Segundos</small>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </form>
    </div>

    <!-- Teste de Conexão -->
    <div class="webhook-card">
        <h4><i class="fas fa-flask"></i> Testar Conexão</h4>
        
        <form method="POST">
            <input type="hidden" name="action" value="test_webhook">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">URL para Teste</label>
                        <input type="url" class="form-control" name="test_url" 
                               value="<?php echo htmlspecialchars($webhook_settings['webhook_url']); ?>"
                               placeholder="https://hook.eu1.make.com/xxxxxxxxx">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Chave Secreta</label>
                        <input type="text" class="form-control" name="test_secret" 
                               value="<?php echo htmlspecialchars($webhook_settings['secret_key']); ?>"
                               placeholder="Opcional">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> Enviar Teste
            </button>
        </form>
        
        <?php if ($test_result): ?>
            <div class="test-result <?php echo $test_result['success'] ? 'test-success' : 'test-error'; ?>">
                <?php if ($test_result['success']): ?>
                    <i class="fas fa-check-circle"></i> <strong>Teste realizado com sucesso!</strong><br>
                    Status HTTP: <?php echo $test_result['http_code']; ?><br>
                    Resposta: <?php echo htmlspecialchars($test_result['response']); ?>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle"></i> <strong>Erro no teste:</strong><br>
                    Status HTTP: <?php echo $test_result['http_code'] ?? 'N/A'; ?><br>
                    <?php if (!empty($test_result['error'])): ?>
                        Erro cURL: <?php echo htmlspecialchars($test_result['error']); ?><br>
                    <?php endif; ?>
                    Resposta: <?php echo htmlspecialchars($test_result['response'] ?? 'Sem resposta'); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Logs Recentes -->
    <div class="webhook-card">
        <h4><i class="fas fa-history"></i> Logs Recentes</h4>
        
        <?php if (empty($logs)): ?>
            <p class="text-muted">Nenhum log de webhook encontrado ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table logs-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Pedido</th>
                            <th>Status</th>
                            <th>Código HTTP</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php if ($log['order_number']): ?>
                                        <a href="pedido_detalhes.php?id=<?php echo $log['order_id']; ?>">
                                            <?php echo htmlspecialchars($log['order_number']); ?>
                                        </a>
                                    <?php else: ?>
                                        #<?php echo $log['order_id']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['success']): ?>
                                        <span class="badge badge-success">Sucesso</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Erro</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log['response_code'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if (!$log['success']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="retry_webhook">
                                            <input type="hidden" name="order_id" value="<?php echo $log['order_id']; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-redo"></i> Reenviar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Exemplo de Payload -->
    <div class="webhook-card">
        <h4><i class="fas fa-code"></i> Exemplo de Payload</h4>
        <p>Este é um exemplo dos dados que serão enviados para seu webhook:</p>
        
        <div class="code-example">
{
  "event": "new_order",
  "timestamp": "2024-12-01T15:30:00Z",
  "order": {
    "id": 123,
    "number": "ORD-20241201-0001",
    "status": "pendente",
    "created_at": "2024-12-01 12:30:00",
    "customer": {
      "name": "João Silva",
      "email": "joao@email.com",
      "phone": "595991234567",
      "address": "Rua das Flores, 123",
      "city": "Assunção",
      "notes": "Entregar pela manhã"
    },
    "items": [
      {
        "product_id": 45,
        "product_name": "Açúcar Cristal",
        "quantity": 5,
        "unit_price": 3500,
        "total_price": 17500
      }
    ],
    "totals": {
      "subtotal": 17500,
      "shipping": 7500,
      "total": 25000,
      "total_weight": 5.0
    },
    "whatsapp_message": "*NUEVO PEDIDO*\n\n*Cliente:* João Silva\n*Teléfono:* 595991234567\n...",
    "whatsapp_phone": "595991234567"
  }
}
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb"></i> <strong>Dica:</strong> 
            Use a variável <code>whatsapp_message</code> diretamente para enviar mensagens formatadas no WhatsApp. 
            Ela já contém todos os detalhes do pedido prontos para uso!
        </div>
    </div>
</div>

<script>
// Auto-focus no campo URL quando página carrega
document.addEventListener('DOMContentLoaded', function() {
    const urlField = document.querySelector('input[name="webhook_url"]');
    if (urlField && !urlField.value) {
        urlField.focus();
    }
});

// Confirmação antes de reenviar webhook
document.querySelectorAll('form input[value="retry_webhook"]').forEach(function(input) {
    input.closest('form').addEventListener('submit', function(e) {
        if (!confirm('Tem certeza que deseja reenviar este webhook?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 