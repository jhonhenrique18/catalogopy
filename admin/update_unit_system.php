<?php
// Script para atualizar banco de dados com sistema de unidades
// Execute este arquivo apenas UMA VEZ pelo navegador ou linha de comando

// Verificar se está sendo executado via CLI ou navegador
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // Se não for CLI, requer autenticação administrativa
    require_once 'includes/auth_check.php';
}

require_once ($is_cli ? 'includes/db_connect.php' : '../includes/db_connect.php');

$success = false;
$error = '';
$log = [];

try {
    // Verificar se os campos já existem
    $check_query = "SHOW COLUMNS FROM products LIKE 'unit_type'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        throw new Exception("O sistema de unidades já foi instalado anteriormente.");
    }
    
    // Início da transação
    $conn->begin_transaction();
    
    // 1. Adicionar campo unit_type
    $sql1 = "ALTER TABLE products ADD COLUMN unit_type ENUM('kg', 'unit') NOT NULL DEFAULT 'kg' AFTER unit_weight";
    if (!$conn->query($sql1)) {
        throw new Exception("Erro ao adicionar campo unit_type: " . $conn->error);
    }
    $log[] = "✅ Campo unit_type adicionado com sucesso";
    
    // 2. Adicionar campo unit_display_name
    $sql2 = "ALTER TABLE products ADD COLUMN unit_display_name VARCHAR(20) DEFAULT NULL AFTER unit_type";
    if (!$conn->query($sql2)) {
        throw new Exception("Erro ao adicionar campo unit_display_name: " . $conn->error);
    }
    $log[] = "✅ Campo unit_display_name adicionado com sucesso";
    
    // 3. Atualizar produtos com ml/ML para unidades
    $sql3 = "UPDATE products SET unit_type = 'unit', unit_display_name = 'ml' 
             WHERE name LIKE '%ml%' OR name LIKE '%ML%'";
    if ($conn->query($sql3)) {
        $affected = $conn->affected_rows;
        $log[] = "✅ {$affected} produtos com 'ml' atualizados para unidades";
    }
    
    // 4. Atualizar produtos claramente unitários
    $sql4 = "UPDATE products SET unit_type = 'unit', unit_display_name = 'unidades'
             WHERE name LIKE '%vidrinho%' OR name LIKE '%frasco%' OR name LIKE '%sachê%' OR name LIKE '%sache%'";
    if ($conn->query($sql4)) {
        $affected = $conn->affected_rows;
        $log[] = "✅ {$affected} produtos unitários identificados e atualizados";
    }
    
    // 5. Definir display_name padrão para produtos kg
    $sql5 = "UPDATE products SET unit_display_name = 'kg' 
             WHERE unit_type = 'kg' AND unit_display_name IS NULL";
    if ($conn->query($sql5)) {
        $affected = $conn->affected_rows;
        $log[] = "✅ {$affected} produtos configurados com unidade 'kg'";
    }
    
    // 6. Definir display_name padrão para produtos unit sem nome específico
    $sql6 = "UPDATE products SET unit_display_name = 'unidades' 
             WHERE unit_type = 'unit' AND unit_display_name IS NULL";
    if ($conn->query($sql6)) {
        $affected = $conn->affected_rows;
        $log[] = "✅ {$affected} produtos unitários configurados com 'unidades'";
    }
    
    // Confirmar transação
    $conn->commit();
    $success = true;
    $log[] = "🎉 Sistema de unidades instalado com sucesso!";
    
} catch (Exception $e) {
    // Reverter em caso de erro
    $conn->rollback();
    $error = $e->getMessage();
    $log[] = "❌ Erro: " . $error;
}

// Se executado via CLI, mostrar apenas o log
if ($is_cli) {
    echo "=== MIGRAÇÃO DO SISTEMA DE UNIDADES ===\n";
    foreach ($log as $entry) {
        echo $entry . "\n";
    }
    echo "==========================================\n";
    exit($success ? 0 : 1);
}

// Incluir layout administrativo
include 'includes/admin_layout.php';
?>

<!-- Conteúdo da página -->
<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Atualização do Sistema de Unidades</h2>
            <p class="text-muted">Script para implementar sistema kg vs unidades nos produtos</p>
        </div>
        <a href="produtos.php" class="btn btn-admin-primary">
            <i class="fas fa-arrow-left me-2"></i>Voltar aos Produtos
        </a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <h5><i class="fas fa-check-circle me-2"></i>Atualização Concluída!</h5>
            <p>O sistema de unidades foi instalado com sucesso. Agora você pode configurar produtos para serem vendidos por kg ou por unidades.</p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Erro na Atualização</h5>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Log de execução -->
    <div class="admin-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-ul me-2"></i>Log de Execução
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($log)): ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($log as $entry): ?>
                        <li class="mb-2">
                            <code><?php echo htmlspecialchars($entry); ?></code>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Nenhuma operação executada ainda.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="admin-card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Próximos Passos
                </h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Revisar produtos:</strong> Vá em "Gestão de Produtos" e verifique se os produtos foram categorizados corretamente</li>
                    <li><strong>Editar se necessário:</strong> Alguns produtos podem precisar de ajuste manual na unidade</li>
                    <li><strong>Testar frontend:</strong> Verifique se o site está exibindo as unidades corretas</li>
                    <li><strong>Excluir este arquivo:</strong> Por segurança, delete este arquivo após a execução</li>
                </ol>
            </div>
        </div>
    <?php endif; ?>
</div> 