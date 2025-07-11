<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Obter categorias
$query_categories = "SELECT id, name FROM categories WHERE status = 1 ORDER BY name";
$result_categories = $conn->query($query_categories);

// Variáveis para armazenar os valores do formulário
$name = '';
$description = '';
$wholesale_price = '';
$retail_price = '';
$min_wholesale_quantity = 10;
$unit_weight = '1.00';
$unit_type = 'kg';
$unit_display_name = '';
$stock = '0';
$category_id = '';
$featured = 0;
$promotion = 0;
$status = 1;

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
    $unit_type = $_POST['unit_type'];
    $unit_display_name = trim($_POST['unit_display_name']);
    $stock = (int)$_POST['stock'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $promotion = isset($_POST['promotion']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Novos campos do sistema flexível
    $show_price = isset($_POST['show_price']) ? 1 : 0;
    $has_min_quantity = isset($_POST['has_min_quantity']) ? 1 : 0;
    
    // Validações reforçadas - agora flexíveis
    if (empty($name)) {
        $errors['name'] = 'O nome do produto é obrigatório';
    }
    
    // Preço atacado é obrigatório apenas se show_price = 1
    if ($show_price && (empty($wholesale_price) || !is_numeric($wholesale_price) || $wholesale_price <= 0)) {
        $errors['wholesale_price'] = 'O preço de atacado deve ser um valor numérico positivo quando o produto tem preço';
    }
    
    // Preço de varejo agora é sempre opcional (será removido conforme solicitado)
    // if (empty($retail_price) || !is_numeric($retail_price) || $retail_price <= 0) {
    //     $errors['retail_price'] = 'O preço de varejo deve ser um valor numérico positivo';
    // }
    
    // Quantidade mínima é obrigatória apenas se has_min_quantity = 1
    if ($has_min_quantity && (empty($min_wholesale_quantity) || $min_wholesale_quantity <= 0)) {
        $errors['min_wholesale_quantity'] = 'A quantidade mínima para atacado deve ser maior que zero quando habilitada';
    }
    
    if (empty($unit_weight) || !is_numeric($unit_weight) || $unit_weight <= 0) {
        $errors['unit_weight'] = 'O peso unitário deve ser um valor numérico positivo';
    }
    
    if ($stock < 0) {
        $errors['stock'] = 'O estoque não pode ser negativo';
    }
    
    if (empty($category_id) && $category_id !== null) {
        $errors['category_id'] = 'Selecione uma categoria';
    }
    
    if (!in_array($unit_type, ['kg', 'unit'])) {
        $errors['unit_type'] = 'Tipo de unidade inválido';
    }
    
    if (empty($unit_display_name)) {
        $errors['unit_display_name'] = 'Nome da unidade é obrigatório';
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
                $image_url = 'uploads/produtos/' . $file_name;
            } else {
                $errors['image'] = 'Erro ao fazer upload da imagem';
            }
        }
    }
    
    // Se não houver erros, inserir produto no banco de dados
    if (empty($errors)) {
        // Ajustar valores para campos opcionais
        $wholesale_price_db = $show_price ? $wholesale_price : null;
        $retail_price_db = null; // Preço de varejo removido conforme solicitado
        $min_wholesale_quantity_db = $has_min_quantity ? $min_wholesale_quantity : null;
        
        // Verificar o tipo de preparação de query necessário (incluindo novos campos)
        if ($category_id === null) {
            $query = "INSERT INTO products (name, description, wholesale_price, retail_price, min_wholesale_quantity, 
                     unit_weight, unit_type, unit_display_name, stock, image_url, category_id, featured, promotion, status, 
                     show_price, has_min_quantity, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdddssssiiiiii", 
                           $name,                      // s - string
                           $description,               // s - string
                           $wholesale_price_db,        // d - double (nullable)
                           $retail_price_db,           // d - double (nullable)
                           $min_wholesale_quantity_db, // i - int (nullable)
                           $unit_weight,               // d - decimal/double
                           $unit_type,                 // s - string
                           $unit_display_name,         // s - string
                           $stock,                     // i - int
                           $image_url,                 // s - string
                           $featured,                  // i - int
                           $promotion,                 // i - int
                           $status,                    // i - int
                           $show_price,                // i - int (tinyint)
                           $has_min_quantity);         // i - int (tinyint)
        } else {
            $query = "INSERT INTO products (name, description, wholesale_price, retail_price, min_wholesale_quantity, 
                     unit_weight, unit_type, unit_display_name, stock, image_url, category_id, featured, promotion, status, 
                     show_price, has_min_quantity, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssddisssiiiiii", 
                           $name,                      // s - string
                           $description,               // s - string
                           $wholesale_price_db,        // d - double (nullable)
                           $retail_price_db,           // d - double (nullable)
                           $min_wholesale_quantity_db, // i - int (nullable)
                           $unit_weight,               // s - string (pode ser decimal como string)
                           $unit_type,                 // s - string
                           $unit_display_name,         // s - string
                           $stock,                     // s - string (estoque como string)
                           $image_url,                 // s - string
                           $category_id,               // i - int
                           $featured,                  // i - int
                           $promotion,                 // i - int
                           $status,                    // i - int
                           $show_price,                // i - int (tinyint)
                           $has_min_quantity);         // i - int (tinyint)
        }
        
        if ($stmt->execute()) {
            // Definir mensagem de sucesso e redirecionar
            $_SESSION['success_message'] = 'Produto adicionado com sucesso!';
            header('Location: produtos.php');
            exit;
        } else {
            $errors['db'] = 'Erro ao adicionar produto: ' . $conn->error;
        }
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Adicionar Produto</h1>
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
    
    <form action="produto_adicionar.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
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
                                <?php if ($result_categories && $result_categories->num_rows > 0): ?>
                                    <?php while ($category = $result_categories->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
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
                    
                    <!-- Opções de configuração de preço -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="show_price" name="show_price" checked onchange="togglePriceFields()">
                                    <label class="form-check-label" for="show_price">
                                        <i class="fas fa-dollar-sign text-success me-1"></i> <strong>Mostrar Preço</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Se desmarcado, será exibido "Consultar con el vendedor" em espanhol
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="has_min_quantity" name="has_min_quantity" checked onchange="toggleMinQuantityField()">
                                    <label class="form-check-label" for="has_min_quantity">
                                        <i class="fas fa-sort-numeric-up text-info me-1"></i> <strong>Tem Quantidade Mínima</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">
                                        Se desmarcado, cliente pode comprar qualquer quantidade
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo de preço (agora apenas atacado) -->
                    <div class="row mb-3" id="price_section">
                        <div class="col-md-6">
                            <label for="wholesale_price" class="form-label">Preço (R$) <span class="text-danger" id="price_required">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control <?php echo isset($errors['wholesale_price']) ? 'is-invalid' : ''; ?>" id="wholesale_price" name="wholesale_price" value="<?php echo htmlspecialchars($wholesale_price); ?>" required>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['wholesale_price']) ? $errors['wholesale_price'] : 'Por favor, informe o preço do produto.'; ?>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <small class="text-muted" id="wholesale_preview">
                                    Será convertido automaticamente para Guaranis
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Espaço reservado para futuras funcionalidades -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>Sistema simplificado: apenas um preço por produto conforme solicitado</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuração de Unidade -->
                    <div class="mb-3">
                        <label for="unit_type" class="form-label">Tipo de Unidade <span class="text-danger">*</span></label>
                        <select class="form-select <?php echo isset($errors['unit_type']) ? 'is-invalid' : ''; ?>" id="unit_type" name="unit_type" required onchange="updateUnitLabels()">
                            <option value="kg" <?php echo $unit_type == 'kg' ? 'selected' : ''; ?>>Quilogramas (kg) - Produtos a granel</option>
                            <option value="unit" <?php echo $unit_type == 'unit' ? 'selected' : ''; ?>>Unidades - Produtos unitários</option>
                        </select>
                        <div class="invalid-feedback">
                            <?php echo isset($errors['unit_type']) ? $errors['unit_type'] : 'Por favor, selecione o tipo de unidade.'; ?>
                        </div>
                        <small class="form-text text-muted">Escolha 'kg' para produtos vendidos por peso (granel) ou 'Unidades' para produtos individuais (frascos, sachês, etc.)</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="unit_display_name" class="form-label">Nome da Unidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['unit_display_name']) ? 'is-invalid' : ''; ?>" id="unit_display_name" name="unit_display_name" value="<?php echo htmlspecialchars($unit_display_name); ?>" required placeholder="Ex: kg, ml, unidades, frascos">
                            <div class="invalid-feedback">
                                <?php echo isset($errors['unit_display_name']) ? $errors['unit_display_name'] : 'Por favor, informe o nome da unidade.'; ?>
                            </div>
                            <small class="form-text text-muted">Como a unidade será exibida no site (ex: "kg", "ml", "unidades")</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="unit_weight" class="form-label"><span id="unit_weight_label">Peso/Quantidade por Unidade</span> <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <input type="text" class="form-control <?php echo isset($errors['unit_weight']) ? 'is-invalid' : ''; ?>" id="unit_weight" name="unit_weight" value="<?php echo htmlspecialchars($unit_weight); ?>" required>
                                <span class="input-group-text" id="unit_weight_suffix">kg</span>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['unit_weight']) ? $errors['unit_weight'] : 'Por favor, informe o peso/quantidade.'; ?>
                                </div>
                            </div>
                            <small class="form-text text-muted" id="unit_weight_help">Para kg: peso de 1 unidade. Para produtos unitários: quantidade (ex: 500 para 500ml)</small>
                        </div>
                    </div>
                    
                    <!-- Campo de quantidade mínima (condicional) -->
                    <div class="row mb-3" id="min_quantity_section">
                        <div class="col-md-6">
                            <label for="min_wholesale_quantity" class="form-label"><span id="min_qty_label">Quantidade Mínima para Compra</span> <span class="text-danger" id="min_qty_required">*</span></label>
                            <div class="input-group has-validation">
                                <input type="number" class="form-control <?php echo isset($errors['min_wholesale_quantity']) ? 'is-invalid' : ''; ?>" id="min_wholesale_quantity" name="min_wholesale_quantity" value="<?php echo htmlspecialchars($min_wholesale_quantity ?: 10); ?>" required min="1">
                                <span class="input-group-text" id="min_qty_suffix">kg</span>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['min_wholesale_quantity']) ? $errors['min_wholesale_quantity'] : 'Por favor, informe a quantidade mínima (mínimo 1).'; ?>
                                </div>
                            </div>
                            <small class="form-text text-muted" id="min_qty_help">Quantidade mínima que o cliente deve comprar deste produto</small>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                <small><strong>Importante:</strong> Manter mínimo de 10kg conforme política da empresa (pode ajustar conforme necessário)</small>
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
                            <img src="../assets/images/no-image.png" alt="Preview" id="imagePreviewImg">
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
                        <i class="fas fa-save me-2"></i> Salvar Produto
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
    
    // Função para controlar exibição do campo de preço
    function togglePriceFields() {
        const showPrice = document.getElementById('show_price').checked;
        const priceSection = document.getElementById('price_section');
        const priceField = document.getElementById('wholesale_price');
        const priceRequired = document.getElementById('price_required');
        
        if (showPrice) {
            priceSection.style.opacity = '1';
            priceField.required = true;
            priceRequired.style.display = 'inline';
            priceField.disabled = false;
        } else {
            priceSection.style.opacity = '0.5';
            priceField.required = false;
            priceRequired.style.display = 'none';
            priceField.disabled = true;
            priceField.value = '';
        }
    }
    
    // Função para controlar exibição do campo de quantidade mínima
    function toggleMinQuantityField() {
        const hasMinQuantity = document.getElementById('has_min_quantity').checked;
        const minQuantitySection = document.getElementById('min_quantity_section');
        const minQuantityField = document.getElementById('min_wholesale_quantity');
        const minQtyRequired = document.getElementById('min_qty_required');
        
        if (hasMinQuantity) {
            minQuantitySection.style.opacity = '1';
            minQuantityField.required = true;
            minQtyRequired.style.display = 'inline';
            minQuantityField.disabled = false;
            if (!minQuantityField.value) {
                minQuantityField.value = '10'; // Valor padrão
            }
        } else {
            minQuantitySection.style.opacity = '0.5';
            minQuantityField.required = false;
            minQtyRequired.style.display = 'none';
            minQuantityField.disabled = true;
        }
    }

    // Função para atualizar labels baseado no tipo de unidade
    function updateUnitLabels() {
        const unitType = document.getElementById('unit_type').value;
        const unitDisplayName = document.getElementById('unit_display_name');
        const unitWeightLabel = document.getElementById('unit_weight_label');
        const unitWeightSuffix = document.getElementById('unit_weight_suffix');
        const unitWeightHelp = document.getElementById('unit_weight_help');
        const minQtyLabel = document.getElementById('min_qty_label');
        const minQtySuffix = document.getElementById('min_qty_suffix');
        const minQtyHelp = document.getElementById('min_qty_help');
        
        if (unitType === 'kg') {
            // Configurações para produtos por kg
            unitDisplayName.value = 'kg';
            unitDisplayName.placeholder = 'Ex: kg, gramas';
            unitWeightLabel.textContent = 'Peso Unitário';
            unitWeightSuffix.textContent = 'kg';
            unitWeightHelp.textContent = 'Peso em kg de 1 unidade (geralmente 1.00 para produtos a granel)';
            minQtyLabel.textContent = 'Quantidade Mínima para Compra';
            minQtySuffix.textContent = 'kg';
            minQtyHelp.textContent = 'Quantidade mínima em kg que o cliente deve comprar';
        } else {
            // Configurações para produtos unitários
            unitDisplayName.value = 'unidades';
            unitDisplayName.placeholder = 'Ex: ml, unidades, frascos, sachês';
            unitWeightLabel.textContent = 'Quantidade/Volume por Unidade';
            unitWeightSuffix.textContent = 'qtd';
            unitWeightHelp.textContent = 'Quantidade ou volume de cada unidade (ex: 500 para 500ml, 1 para 1 frasco)';
            minQtyLabel.textContent = 'Quantidade Mínima para Compra';
            minQtySuffix.textContent = 'unid';
            minQtyHelp.textContent = 'Quantidade mínima em unidades que o cliente deve comprar';
        }
    }
    
    // Executar ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        updateUnitLabels();
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
