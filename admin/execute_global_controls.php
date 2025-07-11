<?php
/**
 * Script para adicionar controles globais ao sistema
 * Execute este arquivo UMA ÚNICA VEZ pelo navegador
 */

// Requer autenticação administrativa
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

$success = false;
$error = '';
$log = [];

try {
    // Verificar se os campos já existem
    $check_query = "SHOW COLUMNS FROM store_settings LIKE 'enable_shipping'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        throw new Exception("Os controles globais já foram instalados anteriormente.");
    }
    
    // Início da transação
    $conn->begin_transaction();
    
    // 1. Adicionar campo enable_shipping
    $sql1 = "ALTER TABLE `store_settings` 
             ADD COLUMN `enable_shipping` TINYINT(1) DEFAULT 1 COMMENT 'Se 1 calcula frete, se 0 não mostra frete'";
    if (!$conn->query($sql1)) {
        throw new Exception("Erro ao adicionar campo enable_shipping: " . $conn->error);
    }
    $log[] = "✅ Campo enable_shipping adicionado com sucesso";
    
    // 2. Adicionar campo shipping_control_text
    $sql2 = "ALTER TABLE `store_settings` 
             ADD COLUMN `shipping_control_text` VARCHAR(255) DEFAULT 'Frete calculado automaticamente' COMMENT 'Texto explicativo para o frete'";
    if (!$conn->query($sql2)) {
        throw new Exception("Erro ao adicionar campo shipping_control_text: " . $conn->error);
    }
    $log[] = "✅ Campo shipping_control_text adicionado com sucesso";
    
    // 3. Adicionar campo enable_global_minimums
    $sql3 = "ALTER TABLE `store_settings` 
             ADD COLUMN `enable_global_minimums` TINYINT(1) DEFAULT 1 COMMENT 'Se 1 respeita mínimos dos produtos, se 0 ignora todos os mínimos'";
    if (!$conn->query($sql3)) {
        throw new Exception("Erro ao adicionar campo enable_global_minimums: " . $conn->error);
    }
    $log[] = "✅ Campo enable_global_minimums adicionado com sucesso";
    
    // 4. Adicionar campo minimum_explanation_text
    $sql4 = "ALTER TABLE `store_settings` 
             ADD COLUMN `minimum_explanation_text` VARCHAR(255) DEFAULT 'Vendemos somente no mínimo especificado' COMMENT 'Texto explicativo para mínimos'";
    if (!$conn->query($sql4)) {
        throw new Exception("Erro ao adicionar campo minimum_explanation_text: " . $conn->error);
    }
    $log[] = "✅ Campo minimum_explanation_text adicionado com sucesso";
    
    // 5. Atualizar configurações padrão
    $sql5 = "UPDATE `store_settings` SET 
             `enable_shipping` = 1, 
             `enable_global_minimums` = 1,
             `shipping_control_text` = 'Frete calculado automaticamente',
             `minimum_explanation_text` = 'Vendemos somente no mínimo especificado'
             WHERE id = 1";
    if ($conn->query($sql5)) {
        $affected = $conn->affected_rows;
        $log[] = "✅ Configurações padrão atualizadas ({$affected} registro afetado)";
    }
    
    // 6. Criar índices para otimização
    $sql6 = "CREATE INDEX idx_store_shipping_enabled ON store_settings(enable_shipping)";
    if ($conn->query($sql6)) {
        $log[] = "✅ Índice para enable_shipping criado";
    }
    
    $sql7 = "CREATE INDEX idx_store_minimums_enabled ON store_settings(enable_global_minimums)";
    if ($conn->query($sql7)) {
        $log[] = "✅ Índice para enable_global_minimums criado";
    }
    
    // Confirmar transação
    $conn->commit();
    $success = true;
    $log[] = "🎉 Todos os controles globais foram instalados com sucesso!";
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $error = $e->getMessage();
    $log[] = "❌ Erro: " . $error;
}

// Incluir cabeçalho admin
include 'includes/admin_layout.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Instalação de Controles Globais</h1>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resultado da Instalação</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Instalação Concluída!</h4>
                            <p>Os controles globais foram instalados com sucesso. Agora você pode:</p>
                            <ul>
                                <li><strong>Ativar/Desativar Frete:</strong> Controlar se o sistema calcula frete automaticamente</li>
                                <li><strong>Ativar/Desativar Mínimos:</strong> Controlar se os produtos respeitam quantidades mínimas</li>
                                <li><strong>Personalizar Textos:</strong> Modificar mensagens explicativas</li>
                            </ul>
                            <a href="configuracoes.php" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Ir para Configurações
                            </a>
                        </div>
                    <?php elseif (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-exclamation-circle"></i> Erro na Instalação</h4>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Log de execução -->
                    <div class="mt-4">
                        <h5>Log de Execução:</h5>
                        <div class="bg-light p-3 rounded">
                            <?php foreach ($log as $entry): ?>
                                <div><?php echo $entry; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Próximos Passos</h3>
                </div>
                <div class="card-body">
                    <ol>
                        <li><strong>Configurar Frete:</strong> Vá em Configurações para ativar/desativar o cálculo de frete</li>
                        <li><strong>Configurar Mínimos:</strong> Defina se quer usar mínimos globalmente</li>
                        <li><strong>Personalizar Textos:</strong> Ajuste as mensagens explicativas</li>
                        <li><strong>Testar Sistema:</strong> Verifique se tudo funciona corretamente</li>
                    </ol>
                    
                    <div class="alert alert-warning mt-3">
                        <small><strong>Importante:</strong> Execute este script apenas uma vez. Se executar novamente, será exibido um erro de compatibilidade.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.log-entry {
    font-family: monospace;
    font-size: 14px;
    margin-bottom: 5px;
}

.log-entry.success {
    color: #28a745;
}

.log-entry.error {
    color: #dc3545;
}
</style> 