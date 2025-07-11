<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID do produto não fornecido.';
    header('Location: produtos.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Obter dados do produto
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se o produto existe
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Produto não encontrado.';
    header('Location: produtos.php');
    exit;
}

// Obter dados do produto
$product = $result->fetch_assoc();

// Obter categorias
$query_categories = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
$result_categories = $conn->query($query_categories);

// Variáveis para armazenar os valores do formulário
$name = $product['name'];
$description = $product['description'];
$wholesale_price = $product['wholesale_price'];
$retail_price = $product['retail_price'];
$min_wholesale_quantity = $product['min_wholesale_quantity'];
$unit_weight = $product['unit_weight'];
$stock = $product['stock'];
$category_id = $product['category_id'];
$featured = $product['featured'];
$promotion = $product['promotion'] ?? 0;
$status = $product['status'];
$current_image = $product['image_url'];

// Array para armazenar erros
$errors = [];

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $wholesale_price = str_replace(['.', ','], ['', '.'], $_POST['wholesale_price']);
    $retail_price = str_replace(['.', ','], ['', '.'], $_POST['retail_price']);
    $min_wholesale_quantity = (int)$_POST['min_wholesale_quantity'];
    $unit_weight = str_replace(',', '.', $_POST['unit_weight']);
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $promotion = isset($_POST['promotion']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validações reforçadas
    if (empty($name)) {
        $errors['name'] = 'O nome do produto é obrigatório';
    }
    
    if (empty($wholesale_price) || !is_numeric($wholesale_price) || $wholesale_price <= 0) {
        $errors['wholesale_price'] = 'O preço de atacado deve ser um valor numérico positivo';
    }
    
    if (empty($retail_price) || !is_numeric($retail_price) || $retail_price <= 0) {
        $errors['retail_price'] = 'O preço de varejo deve ser um valor numérico positivo';
    }
    
    if (empty($min_wholesale_quantity) || $min_wholesale_quantity <= 0) {
        $errors['min_wholesale_quantity'] = 'A quantidade mínima para atacado deve ser maior que zero';
    }
    
    if (empty($unit_weight) || !is_numeric($unit_weight) || $unit_weight <= 0) {
        $errors['unit_weight'] = 'O peso unitário deve ser um valor numérico positivo';
    }
    
    if ($stock < 0) {
        $errors['stock'] = 'O estoque não pode ser negativo';
    }
    
    if (empty($category_id) || $category_id <= 0) {
        $errors['category_id'] = 'Selecione uma categoria válida';
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
            $upload_dir = '../uploads/produtos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Mover o arquivo para o diretório de upload
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Excluir imagem anterior se existir
                if (!empty($current_image) && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                
                $image_url = 'uploads/produtos/' . $file_name;
            } else {
                $errors['image'] = 'Erro ao fazer upload da imagem';
            }
        }
    }
    
    // Se não houver erros, atualizar produto no banco de dados
    if (empty($errors)) {
        $query = "UPDATE products 
                 SET name = ?, description = ?, wholesale_price = ?, retail_price = ?, 
                 min_wholesale_quantity = ?, unit_weight = ?, stock = ?, image_url = ?, 
                 category_id = ?, featured = ?, promotion = ?, status = ?, updated_at = NOW() 
                 WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        // Corrigido: 13 parâmetros - string de tipos tem exatamente 13 caracteres
        $stmt->bind_param("ssddisisiiiii", 
                         $name,                  // s - string
                         $description,           // s - string
                         $wholesale_price,       // d - double
                         $retail_price,          // d - double
                         $min_wholesale_quantity, // i - int
                         $unit_weight,           // s - string
                         $stock,                 // i - int
                         $image_url,             // s - string
                         $category_id,           // i - int
                         $featured,              // i - int
                         $promotion,             // i - int
                         $status,                // i - int
                         $product_id);           // i - int (para WHERE id = ?)
        
        if ($stmt->execute()) {
            // Definir mensagem de sucesso e redirecionar
            $_SESSION['success_message'] = 'Produto atualizado com sucesso!';
            header('Location: produtos.php');
            exit;
        } else {
            $errors['db'] = 'Erro ao atualizar produto: ' . $conn->error;
        }
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Editar Produto</h1>
        <a href="produtos.php" class="btn btn-outline-secondary">
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
    
    <form action="produto_editar.php?id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-8">
                <!-- Informações básicas -->
                <div class="form-section">
                    <h2 class="form-section-title">Informações Básicas</h2>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        <div class="invalid-feedback">
                            <?php echo isset($errors['name']) ? $errors['name'] : 'Por favor, informe o nome do produto.'; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php 
                                $result_categories->data_seek(0); // Reset the pointer
                                while ($category = $result_categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?php echo isset($errors['category_id']) ? $errors['category_id'] : 'Por favor, selecione uma categoria.'; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="stock" class="form-label">Estoque <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?php echo isset($errors['stock']) ? 'is-invalid' : ''; ?>" id="stock" name="stock" value="<?php echo htmlspecialchars($stock); ?>" required min="0">
                            <div class="invalid-feedback">
                                <?php echo isset($errors['stock']) ? $errors['stock'] : 'Por favor, informe o estoque (mínimo 0).'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preços e Quantidades -->
                <div class="form-section">
                    <h2 class="form-section-title">Preços e Quantidades</h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="wholesale_price" class="form-label">Preço de Atacado (R$) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control <?php echo isset($errors['wholesale_price']) ? 'is-invalid' : ''; ?>" id="wholesale_price" name="wholesale_price" value="<?php echo number_format($wholesale_price, 2, ',', '.'); ?>" required>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['wholesale_price']) ? $errors['wholesale_price'] : 'Por favor, informe o preço de atacado.'; ?>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <small class="text-muted" id="wholesale_preview">
                                    Será convertido para <?php echo formatPriceInGuaranis($wholesale_price); ?> automaticamente
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="retail_price" class="form-label">Preço de Varejo (R$) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control <?php echo isset($errors['retail_price']) ? 'is-invalid' : ''; ?>" id="retail_price" name="retail_price" value="<?php echo number_format($retail_price, 2, ',', '.'); ?>" required>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['retail_price']) ? $errors['retail_price'] : 'Por favor, informe o preço de varejo.'; ?>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <small class="text-muted" id="retail_preview">
                                    Será convertido para <?php echo formatPriceInGuaranis($retail_price); ?> automaticamente
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="min_wholesale_quantity" class="form-label">Quantidade Mínima para Atacado (kg) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?php echo isset($errors['min_wholesale_quantity']) ? 'is-invalid' : ''; ?>" id="min_wholesale_quantity" name="min_wholesale_quantity" value="<?php echo htmlspecialchars($min_wholesale_quantity); ?>" required min="1">
                            <div class="invalid-feedback">
                                <?php echo isset($errors['min_wholesale_quantity']) ? $errors['min_wholesale_quantity'] : 'Por favor, informe a quantidade mínima para atacado (mínimo 1).'; ?>
                            </div>
                            <small class="form-text text-muted">Recomendado: 10kg ou mais para produtos de atacado</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="unit_weight" class="form-label">Peso Unitário (kg) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <input type="text" class="form-control <?php echo isset($errors['unit_weight']) ? 'is-invalid' : ''; ?>" id="unit_weight" name="unit_weight" value="<?php echo htmlspecialchars($unit_weight); ?>" required>
                                <span class="input-group-text">kg</span>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['unit_weight']) ? $errors['unit_weight'] : 'Por favor, informe o peso unitário.'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Imagem do produto -->
                <div class="form-section">
                    <h2 class="form-section-title">Imagem do Produto</h2>
                    
                    <div class="mb-3">
                        <div class="image-preview" id="imagePreview">
                            <img src="<?php echo !empty($current_image) ? '../' . $current_image : '../assets/images/no-image.png'; ?>" alt="Preview" id="imagePreviewImg">
                        </div>
                        
                        <input type="file" class="form-control mt-2 <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" id="image" name="image" accept="image/*">
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['image']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-text">
                            Tamanho máximo: 2MB. Formatos: JPG, PNG, GIF
                        </div>
                    </div>
                </div>
                
                <!-- Status e opções -->
                <div class="form-section">
                    <h2 class="form-section-title">Status e Opções</h2>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="featured" name="featured" <?php echo $featured ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">
                            <i class="fas fa-star text-warning me-1"></i> Produto Destacado
                        </label>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="promotion" name="promotion" <?php echo $promotion ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="promotion">
                            <i class="fas fa-fire text-danger me-1"></i> <strong>Produto em Promoção</strong>
                        </label>
                        <small class="form-text text-muted d-block">Produtos em promoção aparecem primeiro na loja</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $status ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Produto Ativo</label>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="form-section">
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-save me-2"></i> Salvar Alterações
                    </button>
                    
                    <a href="produtos.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Preview da imagem
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreviewImg').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Formatação de valores monetários e prévia em tempo real
    const exchangeRate = <?php echo getCurrentExchangeRate(); ?>;
    
    document.querySelectorAll('#wholesale_price, #retail_price').forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Formatar valor em Real
            let value = e.target.value.replace(/[^\d,]/g, '');
            if (value === '') {
                updatePreview(e.target.id, 0);
                return;
            }
            
            // Converter para número
            let numericValue = parseFloat(value.replace(',', '.'));
            
            // Atualizar prévia em Guaranis
            updatePreview(e.target.id, numericValue);
        });
    });
    
    function updatePreview(fieldId, priceInReal) {
        const priceInGuaranis = priceInReal * exchangeRate;
        const formattedPrice = new Intl.NumberFormat('es-PY', {
            style: 'currency',
            currency: 'PYG',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(priceInGuaranis).replace('PYG', 'G$');
        
        const previewId = fieldId === 'wholesale_price' ? 'wholesale_preview' : 'retail_preview';
        const previewElement = document.getElementById(previewId);
        
        if (priceInReal > 0) {
            previewElement.innerHTML = `Será convertido para ${formattedPrice} automaticamente`;
        } else {
            previewElement.innerHTML = 'Será convertido automaticamente';
        }
    }
    
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
</script>

<?php include 'includes/footer.php'; ?>
