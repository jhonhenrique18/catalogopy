<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Obter categorias para seleção de categoria pai
$query_categories = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
$result_categories = $conn->query($query_categories);

// Variáveis para armazenar os valores do formulário
$name = '';
$description = '';
$parent_id = null;
$status = 1;

// Array para armazenar erros
$errors = [];

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validações
    if (empty($name)) {
        $errors['name'] = 'O nome da categoria é obrigatório';
    }
    
    // Verificar se já existe categoria com o mesmo nome
    $check_query = "SELECT id FROM categories WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors['name'] = 'Já existe uma categoria com este nome';
    }
    
    // Processar upload de imagem, se enviada
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors['image'] = 'Apenas imagens JPG, PNG ou GIF são permitidas';
        } else if ($_FILES['image']['size'] > $max_size) {
            $errors['image'] = 'A imagem deve ter no máximo 2MB';
        } else {
            // Criar diretório de upload se não existir
            $upload_dir = '../uploads/categorias/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('category_') . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Mover o arquivo para o diretório de upload
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = 'uploads/categorias/' . $file_name;
            } else {
                $errors['image'] = 'Erro ao fazer upload da imagem';
            }
        }
    }
    
    // Se não houver erros, inserir categoria no banco de dados
    if (empty($errors)) {
        // Verifica se parent_id é válido
        if ($parent_id !== null) {
            $check_parent = "SELECT id FROM categories WHERE id = ?";
            $check_stmt = $conn->prepare($check_parent);
            $check_stmt->bind_param("i", $parent_id);
            $check_stmt->execute();
            $result_parent = $check_stmt->get_result();
            
            // Se a categoria pai não existir, definir como NULL
            if ($result_parent->num_rows === 0) {
                $parent_id = null;
            }
            
            // Verificar se não estamos tentando definir a própria categoria como pai
            if (isset($id) && $parent_id == $id) {
                $parent_id = null;
            }
        }
        
        // Preparar query adequada baseada no parent_id
        if ($parent_id === null) {
            $query = "INSERT INTO categories (name, description, image_url, parent_id, status, created_at, updated_at) 
                     VALUES (?, ?, ?, NULL, ?, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $name, $description, $image_url, $status);
        } else {
            $query = "INSERT INTO categories (name, description, image_url, parent_id, status, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $name, $description, $image_url, $parent_id, $status);
        }
        
        if ($stmt->execute()) {
            // Definir mensagem de sucesso e redirecionar
            $_SESSION['success_message'] = 'Categoria adicionada com sucesso!';
            header('Location: categorias.php');
            exit;
        } else {
            $errors['db'] = 'Erro ao adicionar categoria: ' . $conn->error;
        }
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Adicionar Categoria</h1>
        <a href="categorias.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar
        </a>
    </div>
    
    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $errors['db']; ?>
        </div>
    <?php endif; ?>
    
    <!-- Alerta de campos obrigatórios -->
    <div class="alert alert-info mb-4" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Atenção:</strong> Os campos marcados com <span class="text-danger">*</span> são obrigatórios. 
        Certifique-se de preencher todos os campos obrigatórios para evitar erros.
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-section">
                <h2 class="form-section-title">Informações da Categoria</h2>
                
                <form action="categoria_adicionar.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Categoria <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        <div class="invalid-feedback">
                            <?php echo isset($errors['name']) ? $errors['name'] : 'Por favor, informe o nome da categoria.'; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Categoria Pai</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Nenhuma (categoria principal)</option>
                            <?php if ($result_categories && $result_categories->num_rows > 0): ?>
                                <?php 
                                // Reset result pointer
                                $result_categories->data_seek(0);
                                while ($category = $result_categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $parent_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            Selecione uma categoria pai para criar uma subcategoria.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagem da Categoria</label>
                        <div class="mb-2">
                            <div class="image-preview" id="imagePreview">
                                <img src="../assets/images/no-image.png" alt="Preview" id="imagePreviewImg">
                            </div>
                        </div>
                        
                        <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" id="image" name="image" accept="image/*">
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['image']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-text">
                            Tamanho máximo: 2MB. Formatos: JPG, PNG, GIF
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $status ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Categoria Ativa</label>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Salvar Categoria
                        </button>
                        <a href="categorias.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-section">
                <h2 class="form-section-title">Ajuda</h2>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Dicas para Categorias</h5>
                        <p class="card-text">Categorias bem estruturadas ajudam os clientes a encontrar produtos mais facilmente.</p>
                        <ul>
                            <li>Use nomes curtos e descritivos</li>
                            <li>Organize hierarquicamente (categorias e subcategorias)</li>
                            <li>Evite criar muitas categorias principais</li>
                            <li>Use imagens relevantes e de boa qualidade</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Preview de imagem
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreviewImg');
        
        imageInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Validação do formulário
        (function() {
            'use strict';
            
            // Fetch all forms we want to apply custom validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    });
</script>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>
