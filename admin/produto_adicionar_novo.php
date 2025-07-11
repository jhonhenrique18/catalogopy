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
$errors = [];
$success = false;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lógica de processamento aqui (implementaremos depois)
    // Por enquanto, vamos focar na interface
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- CSS customizado para interface profissional -->
<style>
.product-admin-container {
    max-width: 1200px;
    margin: 0 auto;
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
}

.product-wizard {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.wizard-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.wizard-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 16px;
}

.wizard-steps {
    display: flex;
    background: #f8f9fa;
    padding: 0;
    margin: 0;
    border-bottom: 1px solid #e9ecef;
}

.wizard-step {
    flex: 1;
    padding: 20px;
    text-align: center;
    border-right: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.wizard-step:last-child {
    border-right: none;
}

.wizard-step.active {
    background: white;
    color: #667eea;
    font-weight: 600;
}

.wizard-step.completed {
    background: #d4edda;
    color: #155724;
}

.wizard-step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    transition: all 0.3s;
}

.wizard-step.active .wizard-step-number {
    background: #667eea;
    color: white;
}

.wizard-step.completed .wizard-step-number {
    background: #28a745;
    color: white;
}

.wizard-content {
    padding: 40px;
}

.form-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.form-section-header {
    display: flex;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.form-section-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    font-size: 24px;
}

.form-section-title {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
    color: #2c3e50;
}

.form-section-subtitle {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 14px;
}

.product-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.product-type-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.product-type-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.product-type-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.product-type-icon {
    font-size: 48px;
    color: #667eea;
    margin-bottom: 15px;
}

.product-type-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
}

.product-type-description {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
}

.measure-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.measure-type-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.measure-type-card:hover {
    border-color: #28a745;
    transform: translateY(-2px);
}

.measure-type-card.selected {
    border-color: #28a745;
    background: rgba(40, 167, 69, 0.1);
}

.measure-type-icon {
    font-size: 36px;
    color: #28a745;
    margin-bottom: 10px;
}

.variation-builder {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 25px;
    margin-top: 20px;
}

.variation-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.btn-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 12px 25px;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-outline-modern {
    border: 2px solid #667eea;
    color: #667eea;
    background: transparent;
    padding: 10px 25px;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-outline-modern:hover {
    background: #667eea;
    color: white;
}

.preview-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 25px;
    margin-top: 20px;
}

.preview-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.preview-title i {
    margin-right: 10px;
    color: #667eea;
}

.form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 6px;
    padding: 12px 15px;
    font-size: 16px;
    transition: all 0.3s;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border-left: 4px solid #667eea;
    padding: 20px;
    border-radius: 0 8px 8px 0;
    margin: 20px 0;
}

.alert-tip {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left: 4px solid #28a745;
    color: #155724;
    padding: 15px;
    border-radius: 0 6px 6px 0;
    margin: 15px 0;
}

.hidden-section {
    display: none;
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .product-type-selector,
    .measure-type-selector {
        grid-template-columns: 1fr;
    }
    
    .wizard-steps {
        flex-direction: column;
    }
    
    .wizard-step {
        border-right: none;
        border-bottom: 1px solid #e9ecef;
    }
}
</style>

<!-- Conteúdo principal -->
<div class="product-admin-container">
    <div class="product-wizard">
        <!-- Header -->
        <div class="wizard-header">
            <h1><i class="fas fa-magic me-3"></i>Assistente de Produtos Profissional</h1>
            <p>Crie produtos como um e-commerce profissional - simples, rápido e intuitivo</p>
        </div>
        
        <!-- Steps -->
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <div class="wizard-step-number">1</div>
                <div>Tipo de Produto</div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="wizard-step-number">2</div>
                <div>Informações</div>
            </div>
            <div class="wizard-step" data-step="3">
                <div class="wizard-step-number">3</div>
                <div>Medidas</div>
            </div>
            <div class="wizard-step" data-step="4">
                <div class="wizard-step-number">4</div>
                <div>Preços</div>
            </div>
            <div class="wizard-step" data-step="5">
                <div class="wizard-step-number">5</div>
                <div>Finalizar</div>
            </div>
        </div>
        
        <!-- Form -->
        <form id="productForm" method="post" enctype="multipart/form-data">
            <div class="wizard-content">
                
                <!-- STEP 1: Tipo de Produto -->
                <div class="wizard-step-content" data-step="1">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="form-section-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <h2 class="form-section-title">Que tipo de produto você quer criar?</h2>
                                <p class="form-section-subtitle">Escolha o tipo que melhor se adequa ao seu produto</p>
                            </div>
                        </div>
                        
                        <div class="product-type-selector">
                            <div class="product-type-card" data-type="simple">
                                <div class="product-type-icon">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <div class="product-type-title">Produto Simples</div>
                                <div class="product-type-description">
                                    Um produto único sem variações<br>
                                    <strong>Exemplo:</strong> Açúcar Cristal 1kg, Óleo de Coco 500ml
                                </div>
                            </div>
                            
                            <div class="product-type-card" data-type="variable">
                                <div class="product-type-icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="product-type-title">Produto com Variações</div>
                                <div class="product-type-description">
                                    Um produto com diferentes tamanhos/volumes<br>
                                    <strong>Exemplo:</strong> Óleo de Coco (100ml, 200ml, 500ml, 1L)
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert-tip">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Dica:</strong> Se você tem o mesmo produto em tamanhos diferentes (como 200ml e 500ml), 
                            escolha "Produto com Variações" para melhor organização e experiência do cliente.
                        </div>
                    </div>
                </div>
                
                <!-- STEP 2: Informações Básicas -->
                <div class="wizard-step-content hidden-section" data-step="2">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="form-section-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div>
                                <h2 class="form-section-title">Informações Básicas</h2>
                                <p class="form-section-subtitle">Nome, descrição e categoria do produto</p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label for="product_name" class="form-label fw-bold">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-modern" id="product_name" name="product_name" 
                                           placeholder="Ex: Óleo de Coco Extra Virgem Santo Óleo" required>
                                    <div class="form-text">Use um nome claro e descritivo que seus clientes possam encontrar facilmente</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="product_description" class="form-label fw-bold">Descrição</label>
                                    <textarea class="form-control form-control-modern" id="product_description" name="product_description" 
                                              rows="4" placeholder="Descreva os benefícios e características do produto..."></textarea>
                                    <div class="form-text">Uma boa descrição ajuda os clientes a entenderem o valor do produto</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="category_id" class="form-label fw-bold">Categoria <span class="text-danger">*</span></label>
                                            <select class="form-select form-control-modern" id="category_id" name="category_id" required>
                                                <option value="">Selecione uma categoria</option>
                                                <?php if ($result_categories && $result_categories->num_rows > 0): ?>
                                                    <?php while ($category = $result_categories->fetch_assoc()): ?>
                                                        <option value="<?php echo $category['id']; ?>">
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="product_stock" class="form-label fw-bold">Estoque Inicial</label>
                                            <input type="number" class="form-control form-control-modern" id="product_stock" 
                                                   name="product_stock" value="0" min="0">
                                            <div class="form-text">Quantidade disponível para venda</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Imagem do Produto</label>
                                    <div class="text-center">
                                        <div class="border border-2 border-dashed rounded p-4 mb-3" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <div id="imagePreview" class="text-muted">
                                                <i class="fas fa-image fa-3x mb-3"></i>
                                                <div>Clique para adicionar imagem</div>
                                                <small>JPG, PNG, GIF (máx. 2MB)</small>
                                            </div>
                                        </div>
                                        <input type="file" class="form-control form-control-modern" id="product_image" 
                                               name="product_image" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 3: Sistema de Medidas -->
                <div class="wizard-step-content hidden-section" data-step="3">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="form-section-icon">
                                <i class="fas fa-ruler"></i>
                            </div>
                            <div>
                                <h2 class="form-section-title">Como este produto é vendido?</h2>
                                <p class="form-section-subtitle">Defina a unidade de medida para vendas e cálculos de frete</p>
                            </div>
                        </div>
                        
                        <div class="measure-type-selector">
                            <div class="measure-type-card" data-measure="weight">
                                <div class="measure-type-icon">
                                    <i class="fas fa-weight"></i>
                                </div>
                                <div class="fw-bold">Por Peso</div>
                                <div class="text-muted small">kg/g</div>
                                <div class="small mt-2">Produtos a granel, vendidos por quilograma</div>
                            </div>
                            
                            <div class="measure-type-card" data-measure="volume">
                                <div class="measure-type-icon">
                                    <i class="fas fa-flask"></i>
                                </div>
                                <div class="fw-bold">Por Volume</div>
                                <div class="text-muted small">ml/L</div>
                                <div class="small mt-2">Líquidos, óleos, vendidos por mililitro/litro</div>
                            </div>
                            
                            <div class="measure-type-card" data-measure="unit">
                                <div class="measure-type-icon">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="fw-bold">Por Unidade</div>
                                <div class="text-muted small">unidades</div>
                                <div class="small mt-2">Frascos, pacotes, produtos individuais</div>
                            </div>
                        </div>
                        
                        <!-- Configurações específicas por tipo de medida -->
                        <div id="measureConfig" class="mt-4"></div>
                        
                        <!-- Seção de Variações (aparece apenas para produtos variáveis) -->
                        <div id="variationsSection" class="hidden-section">
                            <div class="variation-builder">
                                <h4><i class="fas fa-layer-group me-2"></i>Configurar Variações</h4>
                                <p class="text-muted mb-3">Crie as diferentes opções do seu produto (tamanhos, volumes, etc.)</p>
                                
                                <div id="variationsList"></div>
                                
                                <button type="button" class="btn btn-outline-modern" id="addVariation">
                                    <i class="fas fa-plus me-2"></i>Adicionar Variação
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 4: Preços -->
                <div class="wizard-step-content hidden-section" data-step="4">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="form-section-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h2 class="form-section-title">Preços e Configurações</h2>
                                <p class="form-section-subtitle">Defina preços e quantidades mínimas</p>
                            </div>
                        </div>
                        
                        <div id="pricingSection">
                            <!-- Conteúdo será preenchido dinamicamente -->
                        </div>
                    </div>
                </div>
                
                <!-- STEP 5: Preview e Finalização -->
                <div class="wizard-step-content hidden-section" data-step="5">
                    <div class="form-section">
                        <div class="form-section-header">
                            <div class="form-section-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h2 class="form-section-title">Revisar e Finalizar</h2>
                                <p class="form-section-subtitle">Confira as informações antes de salvar</p>
                            </div>
                        </div>
                        
                        <div class="preview-section">
                            <div class="preview-title">
                                <i class="fas fa-eye"></i>
                                Preview - Como ficará no site
                            </div>
                            <div id="productPreview">
                                <!-- Preview será gerado dinamicamente -->
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="product_featured" name="product_featured">
                                    <label class="form-check-label fw-bold" for="product_featured">
                                        <i class="fas fa-star text-warning me-1"></i> Produto Destacado
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="product_promotion" name="product_promotion">
                                    <label class="form-check-label fw-bold" for="product_promotion">
                                        <i class="fas fa-fire text-danger me-1"></i> Produto em Promoção
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="product_status" name="product_status" checked>
                                    <label class="form-check-label fw-bold" for="product_status">
                                        <i class="fas fa-toggle-on text-success me-1"></i> Produto Ativo
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert-tip">
                                    <strong>Quase pronto!</strong> Revise as informações ao lado e clique em "Criar Produto" para finalizar.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between p-4 border-top">
                    <button type="button" class="btn btn-outline-modern" id="prevStep" style="display: none;">
                        <i class="fas fa-arrow-left me-2"></i>Anterior
                    </button>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-modern" id="nextStep">
                            Próximo<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-modern" id="submitForm" style="display: none;">
                            <i class="fas fa-save me-2"></i>Criar Produto
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript para funcionalidade avançada -->
<script>
// Estado global do formulário
let currentStep = 1;
let maxStep = 5;
let productType = null;
let measureType = null;
let variations = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateStepVisibility();
});

function setupEventListeners() {
    // Navegação entre steps
    document.getElementById('nextStep').addEventListener('click', nextStep);
    document.getElementById('prevStep').addEventListener('click', prevStep);
    
    // Seleção de tipo de produto
    document.querySelectorAll('.product-type-card').forEach(card => {
        card.addEventListener('click', function() {
            selectProductType(this.dataset.type);
        });
    });
    
    // Seleção de tipo de medida
    document.querySelectorAll('.measure-type-card').forEach(card => {
        card.addEventListener('click', function() {
            selectMeasureType(this.dataset.measure);
        });
    });
    
    // Preview de imagem
    document.getElementById('product_image').addEventListener('change', previewImage);
    
    // Adicionar variação
    document.getElementById('addVariation').addEventListener('click', addVariation);
}

function selectProductType(type) {
    productType = type;
    
    // Atualizar visual
    document.querySelectorAll('.product-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    
    console.log('Tipo de produto selecionado:', type);
}

function selectMeasureType(type) {
    measureType = type;
    
    // Atualizar visual
    document.querySelectorAll('.measure-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-measure="${type}"]`).classList.add('selected');
    
    // Gerar configurações específicas
    generateMeasureConfig(type);
    
    // Mostrar seção de variações se necessário
    if (productType === 'variable') {
        document.getElementById('variationsSection').classList.remove('hidden-section');
        document.getElementById('variationsSection').classList.add('fade-in');
    }
}

function generateMeasureConfig(type) {
    const configDiv = document.getElementById('measureConfig');
    let html = '';
    
    switch(type) {
        case 'weight':
            html = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Unidade de Venda</label>
                        <select class="form-select form-control-modern" name="weight_unit">
                            <option value="kg">Quilograma (kg)</option>
                            <option value="g">Grama (g)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Peso Unitário</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-modern" name="unit_weight" value="1" step="0.001" min="0">
                            <span class="input-group-text">kg</span>
                        </div>
                        <div class="form-text">Peso de 1 unidade (geralmente 1.00 para produtos a granel)</div>
                    </div>
                </div>
            `;
            break;
            
        case 'volume':
            html = `
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unidade de Venda</label>
                        <select class="form-select form-control-modern" name="volume_unit">
                            <option value="ml">Mililitro (ml)</option>
                            <option value="L">Litro (L)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Volume por Unidade</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-modern" name="unit_volume" value="200" min="1">
                            <span class="input-group-text">ml</span>
                        </div>
                        <div class="form-text">Volume de cada frasco/recipiente</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Peso Real (para frete)</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-modern" name="real_weight" value="0.2" step="0.001" min="0">
                            <span class="input-group-text">kg</span>
                        </div>
                        <div class="form-text">Peso físico de cada unidade</div>
                    </div>
                </div>
            `;
            break;
            
        case 'unit':
            html = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome da Unidade</label>
                        <select class="form-select form-control-modern" name="unit_name">
                            <option value="unidades">Unidades</option>
                            <option value="frascos">Frascos</option>
                            <option value="pacotes">Pacotes</option>
                            <option value="sachês">Sachês</option>
                            <option value="potes">Potes</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Peso por Unidade (para frete)</label>
                        <div class="input-group">
                            <input type="number" class="form-control form-control-modern" name="unit_weight" value="0.5" step="0.001" min="0">
                            <span class="input-group-text">kg</span>
                        </div>
                        <div class="form-text">Peso físico de cada unidade para cálculo de frete</div>
                    </div>
                </div>
            `;
            break;
    }
    
    configDiv.innerHTML = html;
    configDiv.classList.add('fade-in');
}

function addVariation() {
    const variationId = Date.now();
    let variationHtml = '';
    
    if (measureType === 'weight') {
        variationHtml = `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" placeholder="Nome da variação (ex: 1kg, 5kg)" name="variations[${variationId}][name]">
                </div>
                <div style="width: 150px;">
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" name="variations[${variationId}][weight]" step="0.001">
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariation(${variationId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    } else if (measureType === 'volume') {
        variationHtml = `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" placeholder="Nome da variação (ex: 100ml, 200ml)" name="variations[${variationId}][name]">
                </div>
                <div style="width: 120px;">
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Volume" name="variations[${variationId}][volume]">
                        <span class="input-group-text">ml</span>
                    </div>
                </div>
                <div style="width: 120px;">
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" name="variations[${variationId}][weight]" step="0.001">
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariation(${variationId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    } else {
        variationHtml = `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <input type="text" class="form-control" placeholder="Nome da variação (ex: Pack 6, Pack 12)" name="variations[${variationId}][name]">
                </div>
                <div style="width: 120px;">
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Qtd" name="variations[${variationId}][quantity]">
                        <span class="input-group-text">un</span>
                    </div>
                </div>
                <div style="width: 120px;">
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" name="variations[${variationId}][weight]" step="0.001">
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariation(${variationId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }
    
    document.getElementById('variationsList').insertAdjacentHTML('beforeend', variationHtml);
}

function removeVariation(variationId) {
    document.querySelector(`[data-variation="${variationId}"]`).remove();
}

function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">`;
        };
        reader.readAsDataURL(file);
    }
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < maxStep) {
            currentStep++;
            updateStepVisibility();
            
            // Gerar seção de preços no step 4
            if (currentStep === 4) {
                generatePricingSection();
            }
            
            // Gerar preview no step 5
            if (currentStep === 5) {
                generatePreview();
            }
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepVisibility();
    }
}

function validateCurrentStep() {
    switch(currentStep) {
        case 1:
            if (!productType) {
                alert('Por favor, selecione o tipo de produto');
                return false;
            }
            break;
        case 2:
            const name = document.getElementById('product_name').value;
            const category = document.getElementById('category_id').value;
            if (!name || !category) {
                alert('Por favor, preencha nome e categoria do produto');
                return false;
            }
            break;
        case 3:
            if (!measureType) {
                alert('Por favor, selecione como o produto é vendido');
                return false;
            }
            break;
    }
    return true;
}

function updateStepVisibility() {
    // Atualizar steps visuais
    document.querySelectorAll('.wizard-step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else if (stepNumber < currentStep) {
            step.classList.add('completed');
        }
    });
    
    // Atualizar conteúdo
    document.querySelectorAll('.wizard-step-content').forEach((content, index) => {
        const stepNumber = index + 1;
        if (stepNumber === currentStep) {
            content.classList.remove('hidden-section');
            content.classList.add('fade-in');
        } else {
            content.classList.add('hidden-section');
            content.classList.remove('fade-in');
        }
    });
    
    // Atualizar botões
    document.getElementById('prevStep').style.display = currentStep > 1 ? 'block' : 'none';
    document.getElementById('nextStep').style.display = currentStep < maxStep ? 'block' : 'none';
    document.getElementById('submitForm').style.display = currentStep === maxStep ? 'block' : 'none';
}

function generatePricingSection() {
    const pricingDiv = document.getElementById('pricingSection');
    let html = '';
    
    if (productType === 'simple') {
        html = `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Preço do Produto (R$) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control form-control-modern" name="product_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-text">Será convertido automaticamente para Guaranis</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Quantidade Mínima</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="min_quantity" value="1" min="1">
                        <span class="input-group-text">un</span>
                    </div>
                    <div class="form-text">Quantidade mínima para compra</div>
                </div>
            </div>
        `;
    } else {
        html = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Para produtos com variações, você definirá preços individuais para cada variação no próximo passo.
            </div>
            <div id="variationPricing"></div>
        `;
    }
    
    pricingDiv.innerHTML = html;
}

function generatePreview() {
    const previewDiv = document.getElementById('productPreview');
    const name = document.getElementById('product_name').value;
    const description = document.getElementById('product_description').value;
    
    let html = `
        <div class="border rounded p-3" style="background: white;">
            <h5>${name || 'Nome do Produto'}</h5>
            <p class="text-muted">${description || 'Descrição do produto...'}</p>
            <div><strong>Tipo:</strong> ${productType === 'simple' ? 'Produto Simples' : 'Produto com Variações'}</div>
            <div><strong>Medida:</strong> ${getMeasureTypeText()}</div>
        </div>
    `;
    
    previewDiv.innerHTML = html;
}

function getMeasureTypeText() {
    switch(measureType) {
        case 'weight': return 'Vendido por Peso (kg/g)';
        case 'volume': return 'Vendido por Volume (ml/L)';
        case 'unit': return 'Vendido por Unidade';
        default: return 'Não definido';
    }
}
</script>

<?php include 'includes/footer.php'; ?> 