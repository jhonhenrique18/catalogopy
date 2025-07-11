<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Criar tabela se não existir
$create_table = "CREATE TABLE IF NOT EXISTS promotional_popup (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table);

// Processar ações
$success = '';
$error = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] == 0) {
                    $upload_dir = '../assets/images/popup/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['popup_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'popup_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['popup_image']['tmp_name'], $upload_path)) {
                        $title = $_POST['title'];
                        $image_url = 'assets/images/popup/' . $new_filename;
                        $is_active = isset($_POST['is_active']) ? 1 : 0;
                        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
                        
                        $stmt = $conn->prepare("INSERT INTO promotional_popup (title, image_url, is_active, end_date) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssis", $title, $image_url, $is_active, $end_date);
                        
                        if ($stmt->execute()) {
                            $success = "Pop-up criado com sucesso!";
                        } else {
                            $error = "Erro ao criar pop-up: " . $conn->error;
                        }
                    } else {
                        $error = "Erro ao fazer upload da imagem.";
                    }
                } else {
                    $error = "Por favor, selecione uma imagem válida.";
                }
                break;
                
            case 'toggle':
                $id = $_POST['popup_id'];
                $stmt = $conn->prepare("UPDATE promotional_popup SET is_active = NOT is_active WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $success = "Status do pop-up alterado!";
                break;
                
            case 'delete':
                $id = $_POST['popup_id'];
                
                // Buscar imagem para deletar arquivo
                $stmt = $conn->prepare("SELECT image_url FROM promotional_popup WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $image_path = '../' . $row['image_url'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                $stmt = $conn->prepare("DELETE FROM promotional_popup WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $success = "Pop-up deletado com sucesso!";
                break;
        }
    }
}

// Buscar todos os pop-ups
$popups = $conn->query("SELECT * FROM promotional_popup ORDER BY created_at DESC");

// CSS adicional para pop-ups integrado ao admin layout
$additional_css = '
.popup-form-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 25px;
}

.popup-form-section h5 {
    color: var(--admin-dark);
    font-weight: 600;
    margin-bottom: 20px;
}

.dimensions-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #f0f9ff 100%);
    border: 1px solid #bbdefb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.dimensions-info h6 {
    color: #0277bd;
    margin-bottom: 15px;
    font-weight: 600;
}

.dimensions-info ul {
    color: #01579b;
    margin-left: 20px;
    margin-bottom: 0;
}

.dimensions-info li {
    margin-bottom: 8px;
}

.popup-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.popup-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.popup-preview {
    width: 100px;
    height: 150px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    flex-shrink: 0;
}

.popup-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.popup-info h6 {
    color: var(--admin-dark);
    font-weight: 600;
    margin-bottom: 8px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.popup-meta {
    color: #6c757d;
    font-size: 14px;
}

.popup-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .popup-item .row {
        text-align: center;
    }
    
    .popup-preview {
        margin: 0 auto 15px auto;
    }
    
    .popup-actions {
        justify-content: center;
        flex-direction: column;
    }
    
    .popup-actions .btn {
        margin-bottom: 5px;
    }
    
    .dimensions-info {
        padding: 15px;
    }
    
    .popup-form-section {
        padding: 20px;
    }
}
';

// Incluir layout administrativo
include 'includes/admin_layout.php';
?>

<!-- Conteúdo Principal -->
<div class="admin-content">
    <!-- Mensagens de Sucesso/Erro -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulário de Criação -->
    <div class="popup-form-section">
        <h5><i class="fas fa-plus-circle me-2 text-primary"></i>Criar Novo Pop-up</h5>
        
        <div class="dimensions-info">
            <h6><i class="fas fa-ruler me-2"></i>Dimensões Recomendadas para Mobile</h6>
            <ul>
                <li><strong>Largura:</strong> 300-350px (ideal: 320px)</li>
                <li><strong>Altura:</strong> 400-500px (ideal: 450px)</li>
                <li><strong>Formato:</strong> JPG ou PNG</li>
                <li><strong>Tamanho máximo:</strong> 500KB</li>
                <li><strong>Proporção:</strong> 3:4 (vertical) para melhor visualização mobile</li>
                <li><strong>Resolução:</strong> 320x450px (ideal para todos os dispositivos)</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="action" value="add">
            
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-edit me-1"></i>Título do Pop-up</label>
                <input type="text" class="form-control" name="title" required placeholder="Ex: Promoção de Fim de Semana">
            </div>
            
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-image me-1"></i>Imagem do Pop-up</label>
                <input type="file" class="form-control" name="popup_image" accept="image/*" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-calendar me-1"></i>Data de Expiração (opcional)</label>
                <input type="datetime-local" class="form-control" name="end_date">
            </div>
            
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" id="is_active" checked>
                    <label class="form-check-label" for="is_active">
                        <i class="fas fa-toggle-on me-1 text-success"></i>Ativar pop-up imediatamente
                    </label>
                </div>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-admin-primary">
                    <i class="fas fa-rocket me-2"></i>Criar Pop-up
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Pop-ups -->
    <div class="admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-list me-2"></i>Pop-ups Criados</h5>
        </div>
        <div class="card-body">
            <?php if ($popups && $popups->num_rows > 0): ?>
                <?php while ($popup = $popups->fetch_assoc()): ?>
                    <div class="popup-item">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="popup-preview mx-auto">
                                    <?php if (file_exists('../' . $popup['image_url'])): ?>
                                        <img src="../<?= $popup['image_url'] ?>" alt="<?= htmlspecialchars($popup['title']) ?>">
                                    <?php else: ?>
                                        <span class="text-muted small">Imagem não encontrada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="popup-info">
                                    <h6><?= htmlspecialchars($popup['title']) ?></h6>
                                    <span class="status-badge <?= $popup['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $popup['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <div class="popup-meta">
                                        <p class="mb-1"><strong>Criado:</strong> <?= date('d/m/Y H:i', strtotime($popup['created_at'])) ?></p>
                                        <?php if ($popup['end_date']): ?>
                                            <p class="mb-0"><strong>Expira:</strong> <?= date('d/m/Y H:i', strtotime($popup['end_date'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <div class="popup-actions">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="popup_id" value="<?= $popup['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $popup['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                            <i class="fas <?= $popup['is_active'] ? 'fa-pause' : 'fa-play' ?> me-1"></i>
                                            <?= $popup['is_active'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja deletar este pop-up?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="popup_id" value="<?= $popup['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash me-1"></i>Deletar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum pop-up criado ainda</h5>
                    <p class="text-muted">Crie seu primeiro pop-up promocional acima!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Script para melhorar a experiência do usuário
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Preview da imagem antes do upload
    const imageInput = document.querySelector('input[name="popup_image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Você pode adicionar uma prévia aqui se desejar
                    console.log('Imagem selecionada:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script> 