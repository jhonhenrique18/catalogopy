<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID da categoria não fornecido.';
    header('Location: categorias.php');
    exit;
}

$category_id = (int)$_GET['id'];

// Obter dados da categoria
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se a categoria existe
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Categoria não encontrada.';
    header('Location: categorias.php');
    exit;
}

// Obter dados da categoria
$category = $result->fetch_assoc();

// Obter categorias para seleção de categoria pai
$query_categories = "SELECT id, name FROM categories WHERE id != ? AND status = 1 ORDER BY name";
$stmt_categories = $conn->prepare($query_categories);
$stmt_categories->bind_param("i", $category_id);
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();

// Variáveis para armazenar os valores do formulário
$name = $category['name'];
$description = $category['description'];
$parent_id = $category['parent_id'];
$status = $category['status'];
$current_image = $category['image_url'];

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
    
    // Verificar se já existe categoria com o mesmo nome (exceto a atual)
    $check_query = "SELECT id FROM categories WHERE name = ? AND id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $name, $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors['name'] = 'Já existe uma categoria com este nome';
    }
    
    // Processar upload de imagem, se enviada
    $image_url = $current_image;
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
                // Excluir imagem anterior se existir
                if (!empty($current_image) && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                
                $image_url = 'uploads/categorias/' . $file_name;
            } else {
                $errors['image'] = 'Erro ao fazer upload da imagem';
            }
        }
    }
    
    // Verificar se não estamos tentando definir a própria categoria como pai
    if ($parent_id == $category_id) {
        $errors['parent_id'] = 'Uma categoria não pode ser pai dela mesma';
        $parent_id = null;
    }
    
    // Se não houver erros, atualizar categoria no banco de dados
    if (empty($errors)) {
        // Preparar query adequada baseada no parent_id
        if ($parent_id === null) {
            $query = "UPDATE categories 
                     SET name = ?, description = ?, image_url = ?, parent_id = NULL, status = ?, updated_at = NOW() 
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $name, $description, $image_url, $status, $category_id);
        } else {
            $query = "UPDATE categories 
                     SET name = ?, description = ?, image_url = ?, parent_id = ?, status = ?, updated_at = NOW() 
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssiii", $name, $description, $image_url, $parent_id, $status, $category_id);
        }
        
        if ($stmt->execute()) {
            // Definir mensagem de sucesso e redirecionar
            $_SESSION['success_message'] = 'Categoria atualizada com sucesso!';
            header('Location: categorias.php');
            exit;
        } else {
            $errors['db'] = 'Erro ao atualizar categoria: ' . $conn->error;
        }
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Editar Categoria</h1>
        <a href="categorias.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar
        </a>
    </div>
    
    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $errors['db']; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="form-section">
                <h2 class="form-section-title">Informações da Categoria</h2>
                
                <form action="categoria_editar.php?id=<?php echo $category_id; ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
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
                        <select class="form-select <?php echo isset($errors['parent_id']) ? 'is-invalid' : ''; ?>" id="parent_id" name="parent_id">
                            <option value="">Nenhuma (categoria principal)</option>
                            <?php if ($result_categories && $result_categories->num_rows > 0): ?>
                                <?php while ($cat = $result_categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $parent_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['parent_id'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['parent_id']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            Selecione uma categoria pai para criar uma subcategoria.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagem da Categoria</label>
                        <div class="mb-2">
                            <div class="image-preview" id="imagePreview">
                                <img src="<?php echo !empty($current_image) ? '../' . $current_image : '../assets/images/no-image.png'; ?>" alt="Preview" id="imagePreviewImg">
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
                            <i class="fas fa-save me-2"></i> Salvar Alterações
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
