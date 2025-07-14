<?php
/**
 * SISTEMA DE EDIÇÃO DE PRODUTOS MELHORADO
 * Versão com tratamento robusto de erros, validações aprimoradas e feedback visual
 */

// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Incluir funções de câmbio
require_once '../includes/exchange_functions.php';

// Função para registrar log de erros
function logError($message, $details = '') {
    $log_file = '../logs/product_edit_errors.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['admin_id'] ?? 'unknown';
    $log_entry = "[$timestamp] User: $user_id - $message";
    
    if ($details) {
        $log_entry .= " - Details: $details";
    }
    
    file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Função para validar e sanitizar dados
function validateAndSanitizeData($data) {
    $errors = [];
    $sanitized = [];
    
    // Nome do produto
    $sanitized['name'] = trim($data['name'] ?? '');
    if (empty($sanitized['name']) || strlen($sanitized['name']) < 3) {
        $errors['name'] = 'O nome do produto deve ter pelo menos 3 caracteres';
    }
    
    // Descrição
    $sanitized['description'] = trim($data['description'] ?? '');
    
    // Preços - tratamento robusto
    $sanitized['wholesale_price'] = 0;
    $sanitized['retail_price'] = 0;
    
    if (!empty($data['wholesale_price'])) {
        // Remove pontos e substitui vírgula por ponto
        $price_clean = str_replace(['.', ','], ['', '.'], $data['wholesale_price']);
        if (is_numeric($price_clean) && $price_clean > 0) {
            $sanitized['wholesale_price'] = (float)$price_clean;
        } else {
            $errors['wholesale_price'] = 'Preço de atacado deve ser um valor numérico positivo';
        }
    } else {
        $errors['wholesale_price'] = 'Preço de atacado é obrigatório';
    }
    
    if (!empty($data['retail_price'])) {
        // Remove pontos e substitui vírgula por ponto
        $price_clean = str_replace(['.', ','], ['', '.'], $data['retail_price']);
        if (is_numeric($price_clean) && $price_clean > 0) {
            $sanitized['retail_price'] = (float)$price_clean;
        } else {
            $errors['retail_price'] = 'Preço de varejo deve ser um valor numérico positivo';
        }
    } else {
        $errors['retail_price'] = 'Preço de varejo é obrigatório';
    }
    
    // Quantidade mínima
    $sanitized['min_wholesale_quantity'] = (int)($data['min_wholesale_quantity'] ?? 0);
    if ($sanitized['min_wholesale_quantity'] <= 0) {
        $errors['min_wholesale_quantity'] = 'Quantidade mínima deve ser maior que zero';
    }
    
    // Peso unitário
    if (!empty($data['unit_weight'])) {
        $weight_clean = str_replace(',', '.', $data['unit_weight']);
        if (is_numeric($weight_clean) && $weight_clean > 0) {
            $sanitized['unit_weight'] = (float)$weight_clean;
        } else {
            $errors['unit_weight'] = 'Peso unitário deve ser um valor numérico positivo';
        }
    } else {
        $errors['unit_weight'] = 'Peso unitário é obrigatório';
    }
    
    // Estoque
    $sanitized['stock'] = (int)($data['stock'] ?? 0);
    if ($sanitized['stock'] < 0) {
        $errors['stock'] = 'Estoque não pode ser negativo';
    }
    
    // Categoria
    $sanitized['category_id'] = (int)($data['category_id'] ?? 0);
    if ($sanitized['category_id'] <= 0) {
        $errors['category_id'] = 'Selecione uma categoria válida';
    }
    
    // Flags booleanos
    $sanitized['featured'] = isset($data['featured']) ? 1 : 0;
    $sanitized['promotion'] = isset($data['promotion']) ? 1 : 0;
    $sanitized['status'] = isset($data['status']) ? 1 : 0;
    
    // Novos campos do sistema flexível
    $sanitized['show_price'] = isset($data['show_price']) ? 1 : 0;
    $sanitized['has_min_quantity'] = isset($data['has_min_quantity']) ? 1 : 0;
    $sanitized['unit_type'] = $data['unit_type'] ?? 'kg';
    $sanitized['unit_display_name'] = trim($data['unit_display_name'] ?? 'kg');
    
    return ['data' => $sanitized, 'errors' => $errors];
}

// Função para processar upload de imagem
function processImageUpload($file, $current_image = '') {
    $errors = [];
    $image_url = $current_image;
    
    if (isset($file) && $file['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validar tipo de arquivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors['image'] = 'Apenas imagens JPG, PNG, GIF ou WebP são permitidas';
        } elseif ($file['size'] > $max_size) {
            $errors['image'] = 'A imagem deve ter no máximo 5MB';
        } else {
            // Criar diretório de upload se não existir
            $upload_dir = '../uploads/produtos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_' . date('Ymd_His_')) . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Mover o arquivo para o diretório de upload
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Excluir imagem anterior se existir
                if (!empty($current_image) && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                
                $image_url = 'uploads/produtos/' . $file_name;
            } else {
                $errors['image'] = 'Erro ao fazer upload da imagem. Verifique as permissões do servidor.';
            }
        }
    }
    
    return ['image_url' => $image_url, 'errors' => $errors];
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID do produto não fornecido.';
    header('Location: produtos.php');
    exit;
}

$product_id = (int)$_GET['id'];
$processing_message = '';
$processing_type = '';

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

// Inicializar variáveis com dados do produto
$form_data = [
    'name' => $product['name'],
    'description' => $product['description'],
    'wholesale_price' => $product['wholesale_price'],
    'retail_price' => $product['retail_price'],
    'min_wholesale_quantity' => $product['min_wholesale_quantity'],
    'unit_weight' => $product['unit_weight'],
    'stock' => $product['stock'],
    'category_id' => $product['category_id'],
    'featured' => $product['featured'],
    'promotion' => $product['promotion'] ?? 0,
    'status' => $product['status'],
    'show_price' => $product['show_price'] ?? 1,
    'has_min_quantity' => $product['has_min_quantity'] ?? 1,
    'unit_type' => $product['unit_type'] ?? 'kg',
    'unit_display_name' => $product['unit_display_name'] ?? 'kg'
];

$current_image = $product['image_url'];
$errors = [];

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados
        $validation_result = validateAndSanitizeData($_POST);
        $sanitized_data = $validation_result['data'];
        $errors = $validation_result['errors'];
        
        // Processar upload de imagem
        $image_result = processImageUpload($_FILES['image'] ?? null, $current_image);
        $image_url = $image_result['image_url'];
        $errors = array_merge($errors, $image_result['errors']);
        
        // Se não houver erros, atualizar produto
        if (empty($errors)) {
            // Começar transação
            $conn->begin_transaction();
            
            try {
                // Atualizar produto
                $query = "UPDATE products 
                         SET name = ?, description = ?, wholesale_price = ?, retail_price = ?, 
                             min_wholesale_quantity = ?, unit_weight = ?, stock = ?, image_url = ?, 
                             category_id = ?, featured = ?, promotion = ?, status = ?, 
                             show_price = ?, has_min_quantity = ?, unit_type = ?, unit_display_name = ?,
                             updated_at = NOW() 
                         WHERE id = ?";
                
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception('Erro na preparação da consulta: ' . $conn->error);
                }
                
                $stmt->bind_param("ssddiissiiiissssi", 
                    $sanitized_data['name'],
                    $sanitized_data['description'],
                    $sanitized_data['wholesale_price'],
                    $sanitized_data['retail_price'],
                    $sanitized_data['min_wholesale_quantity'],
                    $sanitized_data['unit_weight'],
                    $sanitized_data['stock'],
                    $image_url,
                    $sanitized_data['category_id'],
                    $sanitized_data['featured'],
                    $sanitized_data['promotion'],
                    $sanitized_data['status'],
                    $sanitized_data['show_price'],
                    $sanitized_data['has_min_quantity'],
                    $sanitized_data['unit_type'],
                    $sanitized_data['unit_display_name'],
                    $product_id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Erro ao executar atualização: ' . $stmt->error);
                }
                
                // Verificar se foi realmente atualizado
                if ($stmt->affected_rows === 0) {
                    // Pode não ter mudado nada, mas não é erro
                    logError("Produto atualizado mas nenhuma linha foi afetada", "Product ID: $product_id");
                }
                
                // Confirmar transação
                $conn->commit();
                
                // Mensagem de sucesso
                $_SESSION['success_message'] = 'Produto atualizado com sucesso!';
                logError("Produto atualizado com sucesso", "Product ID: $product_id, User: " . $_SESSION['admin_id']);
                
                // Redirecionar para evitar resubmissão
                header('Location: produtos.php?updated=' . $product_id);
                exit;
                
            } catch (Exception $e) {
                // Reverter transação
                $conn->rollback();
                
                $error_message = 'Erro ao atualizar produto: ' . $e->getMessage();
                $errors['database'] = $error_message;
                logError($error_message, $e->getTraceAsString());
                $processing_message = $error_message;
                $processing_type = 'error';
            }
        } else {
            // Atualizar dados do formulário com os dados enviados
            $form_data = array_merge($form_data, $_POST);
            $processing_message = 'Corrija os erros abaixo e tente novamente.';
            $processing_type = 'error';
        }
        
    } catch (Exception $e) {
        $error_message = 'Erro interno do servidor: ' . $e->getMessage();
        $errors['system'] = $error_message;
        logError($error_message, $e->getTraceAsString());
        $processing_message = $error_message;
        $processing_type = 'error';
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Incluir CSS específico para edição de produtos -->
<link rel="stylesheet" href="assets/css/product-edit.css">

<style>
/* Estilos específicos para o formulário de edição */
.form-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e3e6f0;
}

.form-section-title {
    color: #5a5c69;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e3e6f0;
}

.image-preview {
    width: 100%;
    max-width: 300px;
    aspect-ratio: 1;
    border: 2px dashed #e3e6f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fc;
    margin-bottom: 1rem;
    overflow: hidden;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
}

.processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.processing-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.field-error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.field-success {
    color: #28a745;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.save-button {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,123,255,0.2);
}

.save-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.save-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.alert-enhanced {
    border: none;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-enhanced.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.alert-enhanced.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

.alert-enhanced.alert-info {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb);
    color: #0c5460;
}
</style>

<!-- Overlay de processamento -->
<div id="processingOverlay" class="processing-overlay" style="display: none;">
    <div class="processing-card">
        <div class="spinner"></div>
        <h4>Salvando produto...</h4>
        <p>Por favor, aguarde enquanto processamos as alterações.</p>
    </div>
</div>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">
            <i class="fas fa-edit me-2"></i>Editar Produto
        </h1>
        <div>
            <a href="produtos.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
            <span class="badge bg-info">ID: <?php echo $product_id; ?></span>
        </div>
    </div>
    
    <?php if (!empty($processing_message)): ?>
        <div class="alert alert-<?php echo $processing_type === 'error' ? 'danger' : 'success'; ?> alert-enhanced" role="alert">
            <i class="fas fa-<?php echo $processing_type === 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($processing_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-enhanced" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erros encontrados:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Alerta de campos obrigatórios -->
    <div class="alert alert-info alert-enhanced" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Dica:</strong> Os campos marcados com <span class="text-danger">*</span> são obrigatórios. 
        Certifique-se de preencher todos os campos antes de salvar.
    </div>
    
    <form id="editProductForm" action="produto_editar.php?id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-md-8">
                <!-- Informações básicas -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-info-circle me-2"></i>Informações Básicas
                    </h2>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>" 
                               required maxlength="255">
                        <div class="invalid-feedback">
                            <?php echo isset($errors['name']) ? $errors['name'] : 'Por favor, informe o nome do produto.'; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  maxlength="1000"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                        <div class="form-text">
                            <small class="text-muted">
                                <span id="descriptionCount">0</span>/1000 caracteres
                            </small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>" 
                                    id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php 
                                $result_categories->data_seek(0); // Reset the pointer
                                while ($category = $result_categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $form_data['category_id'] == $category['id'] ? 'selected' : ''; ?>>
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
                            <input type="number" class="form-control <?php echo isset($errors['stock']) ? 'is-invalid' : ''; ?>" 
                                   id="stock" name="stock" value="<?php echo htmlspecialchars($form_data['stock']); ?>" 
                                   required min="0" max="999999">
                            <div class="invalid-feedback">
                                <?php echo isset($errors['stock']) ? $errors['stock'] : 'Por favor, informe o estoque (mínimo 0).'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sistema de unidades -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-balance-scale me-2"></i>Sistema de Unidades
                    </h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="unit_type" class="form-label">Tipo de Unidade <span class="text-danger">*</span></label>
                            <select class="form-select" id="unit_type" name="unit_type" required>
                                <option value="kg" <?php echo $form_data['unit_type'] === 'kg' ? 'selected' : ''; ?>>Peso (kg)</option>
                                <option value="unit" <?php echo $form_data['unit_type'] === 'unit' ? 'selected' : ''; ?>>Unidade</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="unit_display_name" class="form-label">Nome da Unidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unit_display_name" name="unit_display_name" 
                                   value="<?php echo htmlspecialchars($form_data['unit_display_name']); ?>" 
                                   required maxlength="50">
                            <div class="form-text">
                                <small class="text-muted">Ex: kg, gramas, unidades, caixas, etc.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="unit_weight" class="form-label">Peso Unitário (kg) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control <?php echo isset($errors['unit_weight']) ? 'is-invalid' : ''; ?>" 
                                   id="unit_weight" name="unit_weight" 
                                   value="<?php echo htmlspecialchars($form_data['unit_weight']); ?>" 
                                   required>
                            <span class="input-group-text">kg</span>
                            <div class="invalid-feedback">
                                <?php echo isset($errors['unit_weight']) ? $errors['unit_weight'] : 'Por favor, informe o peso unitário.'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preços e Quantidades -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Preços e Quantidades
                    </h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="wholesale_price" class="form-label">Preço de Atacado (R$) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control <?php echo isset($errors['wholesale_price']) ? 'is-invalid' : ''; ?>" 
                                       id="wholesale_price" name="wholesale_price" 
                                       value="<?php echo number_format($form_data['wholesale_price'], 2, ',', '.'); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['wholesale_price']) ? $errors['wholesale_price'] : 'Por favor, informe o preço de atacado.'; ?>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <small class="text-muted" id="wholesale_preview">
                                    Será convertido para <?php echo formatPriceInGuaranis($form_data['wholesale_price']); ?> automaticamente
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="retail_price" class="form-label">Preço de Varejo (R$) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control <?php echo isset($errors['retail_price']) ? 'is-invalid' : ''; ?>" 
                                       id="retail_price" name="retail_price" 
                                       value="<?php echo number_format($form_data['retail_price'], 2, ',', '.'); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    <?php echo isset($errors['retail_price']) ? $errors['retail_price'] : 'Por favor, informe o preço de varejo.'; ?>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <small class="text-muted" id="retail_preview">
                                    Será convertido para <?php echo formatPriceInGuaranis($form_data['retail_price']); ?> automaticamente
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_wholesale_quantity" class="form-label">Quantidade Mínima para Atacado <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control <?php echo isset($errors['min_wholesale_quantity']) ? 'is-invalid' : ''; ?>" 
                                   id="min_wholesale_quantity" name="min_wholesale_quantity" 
                                   value="<?php echo htmlspecialchars($form_data['min_wholesale_quantity']); ?>" 
                                   required min="1" max="999999">
                            <span class="input-group-text" id="min_qty_unit">kg</span>
                            <div class="invalid-feedback">
                                <?php echo isset($errors['min_wholesale_quantity']) ? $errors['min_wholesale_quantity'] : 'Por favor, informe a quantidade mínima (mínimo 1).'; ?>
                            </div>
                        </div>
                        <small class="form-text text-muted">Quantidade mínima para aplicar preço de atacado</small>
                    </div>
                </div>
                
                <!-- Configurações avançadas -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-cogs me-2"></i>Configurações Avançadas
                    </h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="show_price" name="show_price" 
                                       <?php echo $form_data['show_price'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_price">
                                    <i class="fas fa-eye me-1"></i>Exibir preço na loja
                                </label>
                                <div class="form-text">
                                    <small class="text-muted">Se desmarcado, mostrará "Consultar preço"</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="has_min_quantity" name="has_min_quantity" 
                                       <?php echo $form_data['has_min_quantity'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="has_min_quantity">
                                    <i class="fas fa-sort-numeric-up me-1"></i>Aplicar quantidade mínima
                                </label>
                                <div class="form-text">
                                    <small class="text-muted">Forçar quantidade mínima na compra</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="featured" name="featured" 
                                       <?php echo $form_data['featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">
                                    <i class="fas fa-star text-warning me-1"></i>Produto Destacado
                                </label>
                                <div class="form-text">
                                    <small class="text-muted">Aparecerá primeiro na listagem</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="promotion" name="promotion" 
                                       <?php echo $form_data['promotion'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="promotion">
                                    <i class="fas fa-fire text-danger me-1"></i><strong>Produto em Promoção</strong>
                                </label>
                                <div class="form-text">
                                    <small class="text-muted">Destacado com badge de promoção</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" 
                               <?php echo $form_data['status'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">
                            <i class="fas fa-toggle-on me-1"></i>Produto Ativo
                        </label>
                        <div class="form-text">
                            <small class="text-muted">Produto visível na loja</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Imagem do produto -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-image me-2"></i>Imagem do Produto
                    </h2>
                    
                    <div class="mb-3">
                        <div class="image-preview" id="imagePreview">
                            <img src="<?php echo !empty($current_image) ? '../' . $current_image : '../assets/images/no-image.png'; ?>" 
                                 alt="Preview" id="imagePreviewImg">
                        </div>
                        
                        <input type="file" class="form-control <?php echo isset($errors['image']) ? 'is-invalid' : ''; ?>" 
                               id="image" name="image" accept="image/*">
                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['image']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            <small class="text-muted">
                                Tamanho máximo: 5MB<br>
                                Formatos: JPG, PNG, GIF, WebP<br>
                                Resolução recomendada: 800x800px
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-tools me-2"></i>Ações
                    </h2>
                    
                    <button type="submit" class="btn save-button w-100 mb-3" id="saveButton">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                    
                    <a href="produtos.php" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    
                    <a href="produto_editar.php?id=<?php echo $product_id; ?>" class="btn btn-outline-info w-100">
                        <i class="fas fa-undo me-2"></i>Resetar Formulário
                    </a>
                </div>
                
                <!-- Informações do produto -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-info me-2"></i>Informações
                    </h2>
                    
                    <div class="mb-2">
                        <strong>ID:</strong> <?php echo $product_id; ?>
                    </div>
                    
                    <div class="mb-2">
                        <strong>Criado:</strong> 
                        <?php 
                        $created_at = new DateTime($product['created_at']);
                        echo $created_at->format('d/m/Y H:i'); 
                        ?>
                    </div>
                    
                    <?php if (!empty($product['updated_at'])): ?>
                    <div class="mb-2">
                        <strong>Atualizado:</strong> 
                        <?php 
                        $updated_at = new DateTime($product['updated_at']);
                        echo $updated_at->format('d/m/Y H:i'); 
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const form = document.getElementById('editProductForm');
    const processingOverlay = document.getElementById('processingOverlay');
    const saveButton = document.getElementById('saveButton');
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreviewImg');
    const descriptionTextarea = document.getElementById('description');
    const descriptionCount = document.getElementById('descriptionCount');
    const unitTypeSelect = document.getElementById('unit_type');
    const minQtyUnit = document.getElementById('min_qty_unit');
    
    // Taxa de câmbio
    const exchangeRate = <?php echo getCurrentExchangeRate(); ?>;
    
    // Contador de caracteres da descrição
    function updateDescriptionCount() {
        const count = descriptionTextarea.value.length;
        descriptionCount.textContent = count;
        
        if (count > 900) {
            descriptionCount.style.color = '#dc3545';
        } else if (count > 800) {
            descriptionCount.style.color = '#ffc107';
        } else {
            descriptionCount.style.color = '#6c757d';
        }
    }
    
    descriptionTextarea.addEventListener('input', updateDescriptionCount);
    updateDescriptionCount();
    
    // Atualizar unidade do campo quantidade mínima
    function updateMinQtyUnit() {
        const unitType = unitTypeSelect.value;
        const unitDisplay = document.getElementById('unit_display_name').value || 'unidades';
        
        if (unitType === 'kg') {
            minQtyUnit.textContent = 'kg';
        } else {
            minQtyUnit.textContent = unitDisplay;
        }
    }
    
    unitTypeSelect.addEventListener('change', updateMinQtyUnit);
    document.getElementById('unit_display_name').addEventListener('input', updateMinQtyUnit);
    updateMinQtyUnit();
    
    // Preview da imagem
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tamanho
            if (file.size > 5 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 5MB');
                e.target.value = '';
                return;
            }
            
            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Apenas imagens JPG, PNG, GIF ou WebP são permitidas');
                e.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Formatação de preços e prévia em tempo real
    function formatPrice(input) {
        let value = input.value.replace(/[^\d,]/g, '');
        
        // Permitir apenas uma vírgula
        const parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts[1];
        }
        
        // Limitar casas decimais
        if (parts[1] && parts[1].length > 2) {
            value = parts[0] + ',' + parts[1].substring(0, 2);
        }
        
        input.value = value;
    }
    
    function updatePricePreview(fieldId, priceInReal) {
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
    
    // Aplicar formatação aos campos de preço
    document.querySelectorAll('#wholesale_price, #retail_price').forEach(function(input) {
        input.addEventListener('input', function(e) {
            formatPrice(e.target);
            
            // Atualizar prévia
            const value = e.target.value.replace(',', '.');
            const numericValue = parseFloat(value) || 0;
            updatePricePreview(e.target.id, numericValue);
        });
    });
    
    // Validação em tempo real
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Remover classes de validação
        field.classList.remove('is-invalid', 'is-valid');
        
        // Validar campos obrigatórios
        if (field.hasAttribute('required') && !value) {
            field.classList.add('is-invalid');
            return false;
        }
        
        // Validações específicas
        switch(fieldName) {
            case 'name':
                if (value.length < 3) {
                    field.classList.add('is-invalid');
                    return false;
                }
                break;
                
            case 'wholesale_price':
            case 'retail_price':
                const numValue = parseFloat(value.replace(',', '.'));
                if (isNaN(numValue) || numValue <= 0) {
                    field.classList.add('is-invalid');
                    return false;
                }
                break;
                
            case 'min_wholesale_quantity':
            case 'stock':
                const intValue = parseInt(value);
                if (isNaN(intValue) || intValue < 0) {
                    field.classList.add('is-invalid');
                    return false;
                }
                break;
        }
        
        field.classList.add('is-valid');
        return true;
    }
    
    // Aplicar validação em tempo real
    form.querySelectorAll('input, select, textarea').forEach(function(field) {
        field.addEventListener('blur', function() {
            validateField(field);
        });
    });
    
    // Submissão do formulário
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar todos os campos
        let isValid = true;
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(field) {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            alert('Por favor, corrija os erros no formulário antes de continuar.');
            return;
        }
        
        // Confirmar salvamento
        if (!confirm('Tem certeza que deseja salvar as alterações?')) {
            return;
        }
        
        // Mostrar overlay de processamento
        processingOverlay.style.display = 'flex';
        saveButton.disabled = true;
        
        // Submeter formulário
        form.submit();
    });
    
    // Auto-save (opcional)
    let autoSaveTimeout;
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Salvar dados no localStorage
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('product_edit_backup_' + <?php echo $product_id; ?>, JSON.stringify(data));
            
            // Mostrar indicador de salvamento
            const indicator = document.createElement('div');
            indicator.innerHTML = '<i class="fas fa-save"></i> Rascunho salvo';
            indicator.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #28a745; color: white; padding: 10px 15px; border-radius: 5px; z-index: 1000; font-size: 14px;';
            document.body.appendChild(indicator);
            
            setTimeout(function() {
                indicator.remove();
            }, 2000);
        }, 5000);
    }
    
    // Monitorar mudanças para auto-save
    form.addEventListener('input', scheduleAutoSave);
    
    // Restaurar dados do localStorage se disponível
    const savedData = localStorage.getItem('product_edit_backup_' + <?php echo $product_id; ?>);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(function(key) {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && field.value !== data[key]) {
                    if (confirm(`Dados de rascunho encontrados para o campo "${key}". Deseja restaurar?`)) {
                        field.value = data[key];
                    }
                }
            });
        } catch (e) {
            console.error('Erro ao restaurar dados do rascunho:', e);
        }
    }
    
    // Limpar dados do localStorage após salvamento bem-sucedido
    if (window.location.search.includes('updated=')) {
        localStorage.removeItem('product_edit_backup_' + <?php echo $product_id; ?>);
    }
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl+S para salvar
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
        
        // Escape para cancelar
        if (e.key === 'Escape') {
            if (confirm('Tem certeza que deseja cancelar? Alterações não salvas serão perdidas.')) {
                window.location.href = 'produtos.php';
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
