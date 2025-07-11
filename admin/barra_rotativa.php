<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Criar tabela se não existir
$create_table = "CREATE TABLE IF NOT EXISTS rotating_banner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    background_color VARCHAR(10) DEFAULT '#27AE60',
    text_color VARCHAR(10) DEFAULT '#FFFFFF',
    is_active BOOLEAN DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table);

// Processar ações do formulário
$success_message = '';
$error_message = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $message = trim($_POST['message']);
                $background_color = $_POST['background_color'];
                $text_color = $_POST['text_color'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $sort_order = (int)$_POST['sort_order'];
                
                if (!empty($message)) {
                    $stmt = $conn->prepare("INSERT INTO rotating_banner (message, background_color, text_color, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssii", $message, $background_color, $text_color, $is_active, $sort_order);
                    
                    if ($stmt->execute()) {
                        $success_message = "Mensagem adicionada com sucesso!";
                    } else {
                        $error_message = "Erro ao adicionar mensagem: " . $conn->error;
                    }
                } else {
                    $error_message = "Por favor, digite uma mensagem.";
                }
                break;
                
            case 'update':
                $id = (int)$_POST['banner_id'];
                $message = trim($_POST['message']);
                $background_color = $_POST['background_color'];
                $text_color = $_POST['text_color'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $sort_order = (int)$_POST['sort_order'];
                
                if (!empty($message)) {
                    $stmt = $conn->prepare("UPDATE rotating_banner SET message = ?, background_color = ?, text_color = ?, is_active = ?, sort_order = ? WHERE id = ?");
                    $stmt->bind_param("sssiii", $message, $background_color, $text_color, $is_active, $sort_order, $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Mensagem atualizada com sucesso!";
                    } else {
                        $error_message = "Erro ao atualizar mensagem: " . $conn->error;
                    }
                } else {
                    $error_message = "Por favor, digite uma mensagem.";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['banner_id'];
                $stmt = $conn->prepare("DELETE FROM rotating_banner WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Mensagem removida com sucesso!";
                } else {
                    $error_message = "Erro ao remover mensagem: " . $conn->error;
                }
                break;
                
            case 'toggle':
                $id = (int)$_POST['banner_id'];
                $stmt = $conn->prepare("UPDATE rotating_banner SET is_active = NOT is_active WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success_message = "Status da mensagem alterado!";
                } else {
                    $error_message = "Erro ao alterar status: " . $conn->error;
                }
                break;
        }
    }
}

// Buscar todas as mensagens
$result = $conn->query("SELECT * FROM rotating_banner ORDER BY sort_order ASC, created_at DESC");
$banners = $result->fetch_all(MYSQLI_ASSOC);

// Incluir layout administrativo
include 'includes/admin_layout.php';
?>

<!-- Conteúdo Principal -->
<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-scroll me-2 text-primary"></i>Barra Rotativa</h4>
            <p class="text-muted">Gerencie as mensagens que aparecem no topo do site</p>
        </div>
        <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
            <i class="fas fa-plus me-2"></i>Nova Mensagem
        </button>
    </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

    <!-- Visualização da Barra -->
    <div class="admin-card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-eye me-2"></i>Visualização da Barra Rotativa
            </h5>
        </div>
                <div class="card-body p-0">
                    <?php if (count($banners) > 0): ?>
                        <div id="banner-preview" style="position: relative; overflow: hidden; height: 50px;">
                            <?php foreach ($banners as $index => $banner): ?>
                                <?php if ($banner['is_active']): ?>
                                    <div class="preview-banner" 
                                         style="position: absolute; width: 100%; height: 100%; 
                                                background-color: <?php echo htmlspecialchars($banner['background_color']); ?>; 
                                                color: <?php echo htmlspecialchars($banner['text_color']); ?>; 
                                                display: flex; align-items: center; justify-content: center; 
                                                font-weight: 500; font-size: 14px;
                                                <?php echo $index === 0 ? 'opacity: 1;' : 'opacity: 0;'; ?>">
                                        <?php echo htmlspecialchars($banner['message']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <p>Nenhuma mensagem configurada</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Lista de Mensagens -->
    <div class="admin-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Mensagens Configuradas
            </h5>
        </div>
                <div class="card-body">
                    <?php if (count($banners) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Mensagem</th>
                                        <th>Cores</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banners as $banner): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $banner['sort_order']; ?></span>
                                            </td>
                                            <td>
                                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?php echo htmlspecialchars($banner['message']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?php echo $banner['background_color']; ?>; border-radius: 3px; margin-right: 5px;"></div>
                                                    <div style="width: 20px; height: 20px; background-color: <?php echo $banner['text_color']; ?>; border-radius: 3px; border: 1px solid #ddd;"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($banner['is_active']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y H:i', strtotime($banner['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-banner" 
                                                            data-id="<?php echo $banner['id']; ?>"
                                                            data-message="<?php echo htmlspecialchars($banner['message']); ?>"
                                                            data-bg-color="<?php echo $banner['background_color']; ?>"
                                                            data-text-color="<?php echo $banner['text_color']; ?>"
                                                            data-active="<?php echo $banner['is_active']; ?>"
                                                            data-order="<?php echo $banner['sort_order']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-<?php echo $banner['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Tem certeza que deseja excluir esta mensagem?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <p>Nenhuma mensagem cadastrada</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                                <i class="fas fa-plus"></i> Criar primeira mensagem
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
</div>

<!-- Modal Adicionar -->
<div class="modal fade" id="addBannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="message" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="message" name="message" rows="3" required
                                  placeholder="Digite a mensagem que aparecerá na barra..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="background_color" class="form-label">Cor de Fundo</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="background_color" name="background_color" value="#27AE60">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="text_color" class="form-label">Cor do Texto</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="text_color" name="text_color" value="#FFFFFF">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" id="sort_order" 
                                       name="sort_order" value="0" min="0">
                                <small class="form-text text-muted">Menor número aparece primeiro</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editBannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="banner_id" id="edit_banner_id">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_message" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="edit_message" name="message" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_background_color" class="form-label">Cor de Fundo</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="edit_background_color" name="background_color">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_text_color" class="form-label">Cor do Texto</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="edit_text_color" name="text_color">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_sort_order" class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" id="edit_sort_order" 
                                       name="sort_order" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        Ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animação da prévia da barra
    const previewBanners = document.querySelectorAll('.preview-banner');
    if (previewBanners.length > 1) {
        let currentIndex = 0;
        
        setInterval(() => {
            previewBanners[currentIndex].style.opacity = '0';
            currentIndex = (currentIndex + 1) % previewBanners.length;
            previewBanners[currentIndex].style.opacity = '1';
        }, 3000);
    }
    
    // Editar banner
    document.querySelectorAll('.edit-banner').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const message = this.dataset.message;
            const bgColor = this.dataset.bgColor;
            const textColor = this.dataset.textColor;
            const isActive = this.dataset.active === '1';
            const order = this.dataset.order;
            
            document.getElementById('edit_banner_id').value = id;
            document.getElementById('edit_message').value = message;
            document.getElementById('edit_background_color').value = bgColor;
            document.getElementById('edit_text_color').value = textColor;
            document.getElementById('edit_is_active').checked = isActive;
            document.getElementById('edit_sort_order').value = order;
            
            new bootstrap.Modal(document.getElementById('editBannerModal')).show();
        });
    });
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?> 