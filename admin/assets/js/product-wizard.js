/**
 * PRODUCT WIZARD - JAVASCRIPT AVANÇADO
 * Sistema de cadastro profissional de produtos
 * Inspirado em e-commerces modernos
 */

class ProductWizard {
    constructor() {
        this.currentStep = 1;
        this.maxStep = 5;
        this.productType = null;
        this.measureType = null;
        this.variations = [];
        this.exchangeRate = window.exchangeRate || 7000; // Taxa de câmbio padrão
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.updateStepVisibility();
        this.loadProgress();
    }
    
    setupEventListeners() {
        // Navegação entre steps
        document.getElementById('nextStep')?.addEventListener('click', () => this.nextStep());
        document.getElementById('prevStep')?.addEventListener('click', () => this.prevStep());
        
        // Seleção de tipo de produto
        document.querySelectorAll('.product-type-card').forEach(card => {
            card.addEventListener('click', () => this.selectProductType(card.dataset.type));
        });
        
        // Seleção de tipo de medida
        document.querySelectorAll('.measure-type-card').forEach(card => {
            card.addEventListener('click', () => this.selectMeasureType(card.dataset.measure));
        });
        
        // Preview de imagem
        document.getElementById('product_image')?.addEventListener('change', (e) => this.previewImage(e));
        
        // Adicionar variação
        document.getElementById('addVariation')?.addEventListener('click', () => this.addVariation());
        
        // Auto-save em inputs importantes
        document.getElementById('product_name')?.addEventListener('input', () => this.autoSave());
        document.getElementById('product_description')?.addEventListener('input', () => this.autoSave());
        
        // Validação em tempo real
        this.setupRealTimeValidation();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }
    
    setupRealTimeValidation() {
        const inputs = document.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }
    
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Remove validações anteriores
        field.classList.remove('is-invalid', 'is-valid');
        
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'Este campo é obrigatório');
            return false;
        }
        
        // Validações específicas
        switch(fieldName) {
            case 'product_name':
                if (value.length < 3) {
                    this.showFieldError(field, 'Nome deve ter pelo menos 3 caracteres');
                    return false;
                }
                break;
                
            case 'product_price':
                if (parseFloat(value) <= 0) {
                    this.showFieldError(field, 'Preço deve ser maior que zero');
                    return false;
                }
                break;
        }
        
        field.classList.add('is-valid');
        return true;
    }
    
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }
    
    clearFieldError(field) {
        field.classList.remove('is-invalid');
    }
    
    handleKeyboardShortcuts(e) {
        // Ctrl + Enter para próximo step
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            this.nextStep();
        }
        
        // Escape para step anterior
        if (e.key === 'Escape' && this.currentStep > 1) {
            e.preventDefault();
            this.prevStep();
        }
    }
    
    selectProductType(type) {
        this.productType = type;
        
        // Atualizar visual
        document.querySelectorAll('.product-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`[data-type="${type}"]`).classList.add('selected');
        
        // Salvar progresso
        this.saveProgress();
        
        // Feedback visual
        this.showNotification(`Tipo selecionado: ${type === 'simple' ? 'Produto Simples' : 'Produto com Variações'}`, 'success');
        
        console.log('Tipo de produto selecionado:', type);
    }
    
    selectMeasureType(type) {
        this.measureType = type;
        
        // Atualizar visual
        document.querySelectorAll('.measure-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`[data-measure="${type}"]`).classList.add('selected');
        
        // Gerar configurações específicas
        this.generateMeasureConfig(type);
        
        // Mostrar seção de variações se necessário
        if (this.productType === 'variable') {
            const variationsSection = document.getElementById('variationsSection');
            if (variationsSection) {
                variationsSection.classList.remove('hidden-section');
                variationsSection.classList.add('fade-in');
            }
        }
        
        // Salvar progresso
        this.saveProgress();
        
        // Feedback visual
        const typeNames = {
            'weight': 'Por Peso (kg/g)',
            'volume': 'Por Volume (ml/L)', 
            'unit': 'Por Unidade'
        };
        this.showNotification(`Medida selecionada: ${typeNames[type]}`, 'success');
    }
    
    generateMeasureConfig(type) {
        const configDiv = document.getElementById('measureConfig');
        if (!configDiv) return;
        
        let html = '';
        
        switch(type) {
            case 'weight':
                html = this.generateWeightConfig();
                break;
            case 'volume':
                html = this.generateVolumeConfig();
                break;
            case 'unit':
                html = this.generateUnitConfig();
                break;
        }
        
        configDiv.innerHTML = html;
        configDiv.classList.add('fade-in');
        
        // Setup event listeners para novos campos
        this.setupMeasureConfigListeners();
    }
    
    generateWeightConfig() {
        return `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Unidade de Venda <span class="text-danger">*</span></label>
                    <select class="form-select form-control-modern" name="weight_unit" required>
                        <option value="kg" selected>Quilograma (kg)</option>
                        <option value="g">Grama (g)</option>
                    </select>
                    <div class="form-text">Como o produto será vendido no site</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Peso Unitário <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="unit_weight" 
                               value="1" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="form-text">Peso físico de 1 unidade mínima (geralmente 1.00 para produtos a granel)</div>
                </div>
            </div>
            <div class="alert-tip mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Exemplo:</strong> Para produtos a granel como açúcar, castanhas, etc., 
                mantenha "1 kg" como peso unitário. O cliente comprará múltiplos de 1kg.
            </div>
        `;
    }
    
    generateVolumeConfig() {
        return `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Unidade de Venda <span class="text-danger">*</span></label>
                    <select class="form-select form-control-modern" name="volume_unit" required>
                        <option value="ml" selected>Mililitro (ml)</option>
                        <option value="L">Litro (L)</option>
                    </select>
                    <div class="form-text">Como será mostrado no site</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Volume por Unidade <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="unit_volume" 
                               value="200" min="1" required>
                        <span class="input-group-text" id="volume-suffix">ml</span>
                    </div>
                    <div class="form-text">Volume de cada frasco/recipiente</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Peso Real (para frete) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="real_weight" 
                               value="0.2" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="form-text">Peso físico real de cada unidade</div>
                </div>
            </div>
            <div class="alert-tip mt-3">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Dica:</strong> Para um óleo de 200ml, o peso real é aproximadamente 0.2kg. 
                Este peso é usado para calcular o frete corretamente.
            </div>
        `;
    }
    
    generateUnitConfig() {
        return `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nome da Unidade <span class="text-danger">*</span></label>
                    <select class="form-select form-control-modern" name="unit_name" required>
                        <option value="unidades" selected>Unidades</option>
                        <option value="frascos">Frascos</option>
                        <option value="pacotes">Pacotes</option>
                        <option value="sachês">Sachês</option>
                        <option value="potes">Potes</option>
                        <option value="caixas">Caixas</option>
                    </select>
                    <div class="form-text">Como será exibido no site (ex: "6 frascos")</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Peso por Unidade (para frete) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="unit_weight" 
                               value="0.5" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                    <div class="form-text">Peso físico de cada unidade individual</div>
                </div>
            </div>
            <div class="alert-tip mt-3">
                <i class="fas fa-box me-2"></i>
                <strong>Exemplo:</strong> Se você vende frascos de 300g cada, coloque "0.3" no peso por unidade. 
                O sistema calculará automaticamente o frete baseado na quantidade.
            </div>
        `;
    }
    
    setupMeasureConfigListeners() {
        // Listener para mudança de unidade de volume
        const volumeUnit = document.querySelector('[name="volume_unit"]');
        if (volumeUnit) {
            volumeUnit.addEventListener('change', (e) => {
                const suffix = document.getElementById('volume-suffix');
                if (suffix) {
                    suffix.textContent = e.target.value;
                }
            });
        }
        
        // Auto-calculate weight para volumes
        const volumeInput = document.querySelector('[name="unit_volume"]');
        const weightInput = document.querySelector('[name="real_weight"]');
        if (volumeInput && weightInput) {
            volumeInput.addEventListener('input', (e) => {
                const volume = parseFloat(e.target.value) || 0;
                // Assumir densidade aproximada de óleo (0.9-1.0)
                const estimatedWeight = (volume / 1000) * 0.95; // ml para kg
                weightInput.value = estimatedWeight.toFixed(3);
            });
        }
    }
    
    addVariation() {
        const variationId = Date.now();
        let variationHtml = '';
        
        switch(this.measureType) {
            case 'weight':
                variationHtml = this.generateWeightVariationRow(variationId);
                break;
            case 'volume':
                variationHtml = this.generateVolumeVariationRow(variationId);
                break;
            case 'unit':
                variationHtml = this.generateUnitVariationRow(variationId);
                break;
        }
        
        const variationsList = document.getElementById('variationsList');
        if (variationsList) {
            variationsList.insertAdjacentHTML('beforeend', variationHtml);
            
            // Animar entrada
            const newRow = variationsList.lastElementChild;
            newRow.style.opacity = '0';
            newRow.style.transform = 'translateY(20px)';
            setTimeout(() => {
                newRow.style.transition = 'all 0.3s ease';
                newRow.style.opacity = '1';
                newRow.style.transform = 'translateY(0)';
            }, 10);
        }
        
        this.variations.push({id: variationId, type: this.measureType});
        this.saveProgress();
    }
    
    generateWeightVariationRow(variationId) {
        return `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <label class="form-label">Nome da Variação</label>
                    <input type="text" class="form-control" placeholder="Ex: 1kg, 5kg, 10kg" 
                           name="variations[${variationId}][name]" required>
                </div>
                <div style="width: 150px;">
                    <label class="form-label">Peso</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" 
                               name="variations[${variationId}][weight]" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div style="width: 50px; display: flex; align-items: end;">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="productWizard.removeVariation(${variationId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    generateVolumeVariationRow(variationId) {
        return `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <label class="form-label">Nome da Variação</label>
                    <input type="text" class="form-control" placeholder="Ex: 100ml, 200ml, 500ml" 
                           name="variations[${variationId}][name]" required>
                </div>
                <div style="width: 120px;">
                    <label class="form-label">Volume</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Volume" 
                               name="variations[${variationId}][volume]" min="1" required>
                        <span class="input-group-text">ml</span>
                    </div>
                </div>
                <div style="width: 120px;">
                    <label class="form-label">Peso Real</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" 
                               name="variations[${variationId}][weight]" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div style="width: 50px; display: flex; align-items: end;">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="productWizard.removeVariation(${variationId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    generateUnitVariationRow(variationId) {
        return `
            <div class="variation-row" data-variation="${variationId}">
                <div class="flex-grow-1">
                    <label class="form-label">Nome da Variação</label>
                    <input type="text" class="form-control" placeholder="Ex: Pack 6, Pack 12, Caixa 24" 
                           name="variations[${variationId}][name]" required>
                </div>
                <div style="width: 120px;">
                    <label class="form-label">Quantidade</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Qtd" 
                               name="variations[${variationId}][quantity]" min="1" required>
                        <span class="input-group-text">un</span>
                    </div>
                </div>
                <div style="width: 120px;">
                    <label class="form-label">Peso Total</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Peso" 
                               name="variations[${variationId}][weight]" step="0.001" min="0.001" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </div>
                <div style="width: 50px; display: flex; align-items: end;">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="productWizard.removeVariation(${variationId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    removeVariation(variationId) {
        const variationRow = document.querySelector(`[data-variation="${variationId}"]`);
        if (variationRow) {
            // Animar saída
            variationRow.style.transition = 'all 0.3s ease';
            variationRow.style.opacity = '0';
            variationRow.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                variationRow.remove();
            }, 300);
        }
        
        this.variations = this.variations.filter(v => v.id !== variationId);
        this.saveProgress();
    }
    
    previewImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        
        if (!preview) return;
        
        if (file) {
            // Validar tamanho e tipo
            if (file.size > 2 * 1024 * 1024) {
                this.showNotification('Imagem muito grande (máx. 2MB)', 'error');
                event.target.value = '';
                return;
            }
            
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                this.showNotification('Tipo de arquivo inválido (use JPG, PNG ou GIF)', 'error');
                event.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" 
                         style="max-width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px;">
                    <div class="mt-2 small text-success">
                        <i class="fas fa-check-circle me-1"></i>Imagem carregada com sucesso
                    </div>
                `;
            };
            reader.readAsDataURL(file);
            
            this.showNotification('Imagem carregada com sucesso', 'success');
        }
    }
    
    nextStep() {
        if (this.validateCurrentStep()) {
            if (this.currentStep < this.maxStep) {
                this.currentStep++;
                this.updateStepVisibility();
                
                // Ações específicas por step
                switch(this.currentStep) {
                    case 4:
                        this.generatePricingSection();
                        break;
                    case 5:
                        this.generatePreview();
                        break;
                }
                
                this.saveProgress();
                this.showNotification(`Avançando para ${this.getStepName(this.currentStep)}`, 'info');
            }
        }
    }
    
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepVisibility();
            this.saveProgress();
            this.showNotification(`Voltando para ${this.getStepName(this.currentStep)}`, 'info');
        }
    }
    
    getStepName(step) {
        const stepNames = {
            1: 'Tipo de Produto',
            2: 'Informações Básicas',
            3: 'Sistema de Medidas',
            4: 'Preços e Configurações',
            5: 'Revisão Final'
        };
        return stepNames[step] || 'Step Desconhecido';
    }
    
    validateCurrentStep() {
        switch(this.currentStep) {
            case 1:
                if (!this.productType) {
                    this.showNotification('Por favor, selecione o tipo de produto', 'error');
                    return false;
                }
                break;
                
            case 2:
                const name = document.getElementById('product_name')?.value;
                const category = document.getElementById('category_id')?.value;
                if (!name || name.length < 3) {
                    this.showNotification('Por favor, informe um nome válido para o produto (mín. 3 caracteres)', 'error');
                    document.getElementById('product_name')?.focus();
                    return false;
                }
                if (!category) {
                    this.showNotification('Por favor, selecione uma categoria', 'error');
                    document.getElementById('category_id')?.focus();
                    return false;
                }
                break;
                
            case 3:
                if (!this.measureType) {
                    this.showNotification('Por favor, selecione como o produto é vendido', 'error');
                    return false;
                }
                
                // Validar campos específicos da medida
                const measureInputs = document.querySelectorAll('#measureConfig input[required], #measureConfig select[required]');
                for (let input of measureInputs) {
                    if (!this.validateField(input)) {
                        input.focus();
                        return false;
                    }
                }
                
                // Validar variações se produto variável
                if (this.productType === 'variable' && this.variations.length === 0) {
                    this.showNotification('Para produtos com variações, adicione pelo menos uma variação', 'error');
                    return false;
                }
                break;
                
            case 4:
                // Validar preços
                const priceInputs = document.querySelectorAll('#pricingSection input[required]');
                for (let input of priceInputs) {
                    if (!this.validateField(input)) {
                        input.focus();
                        return false;
                    }
                }
                break;
        }
        return true;
    }
    
    updateStepVisibility() {
        // Atualizar steps visuais
        document.querySelectorAll('.wizard-step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber === this.currentStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentStep) {
                step.classList.add('completed');
            }
        });
        
        // Atualizar conteúdo
        document.querySelectorAll('.wizard-step-content').forEach((content, index) => {
            const stepNumber = index + 1;
            if (stepNumber === this.currentStep) {
                content.classList.remove('hidden-section');
                content.classList.add('fade-in');
            } else {
                content.classList.add('hidden-section');
                content.classList.remove('fade-in');
            }
        });
        
        // Atualizar botões
        const prevBtn = document.getElementById('prevStep');
        const nextBtn = document.getElementById('nextStep');
        const submitBtn = document.getElementById('submitForm');
        
        if (prevBtn) prevBtn.style.display = this.currentStep > 1 ? 'block' : 'none';
        if (nextBtn) nextBtn.style.display = this.currentStep < this.maxStep ? 'block' : 'none';
        if (submitBtn) submitBtn.style.display = this.currentStep === this.maxStep ? 'block' : 'none';
        
        // Scroll para o topo
        document.querySelector('.wizard-content')?.scrollIntoView({ behavior: 'smooth' });
    }
    
    generatePricingSection() {
        const pricingDiv = document.getElementById('pricingSection');
        if (!pricingDiv) return;
        
        let html = '';
        
        if (this.productType === 'simple') {
            html = this.generateSimplePricingForm();
        } else {
            html = this.generateVariablePricingForm();
        }
        
        pricingDiv.innerHTML = html;
        this.setupPricingListeners();
    }
    
    generateSimplePricingForm() {
        return `
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Preço do Produto (R$) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control form-control-modern" name="product_price" 
                               step="0.01" min="0.01" required placeholder="0,00">
                    </div>
                    <div class="form-text" id="price_preview">Será convertido automaticamente para Guaranis</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Quantidade Mínima</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-modern" name="min_quantity" 
                               value="1" min="1" placeholder="1">
                        <span class="input-group-text" id="min_quantity_unit">un</span>
                    </div>
                    <div class="form-text">Quantidade mínima para compra (deixe 1 se não houver mínimo)</div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="show_price" name="show_price" checked>
                        <label class="form-check-label fw-bold" for="show_price">
                            <i class="fas fa-eye me-1"></i> Mostrar preço no site
                        </label>
                        <div class="form-text">Se desmarcado, será exibido "Consultar con el vendedor"</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateVariablePricingForm() {
        return `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Produto com Variações:</strong> Você definirá preços individuais para cada variação abaixo.
            </div>
            <div id="variationPricingTable">
                ${this.generateVariationPricingTable()}
            </div>
        `;
    }
    
    generateVariationPricingTable() {
        if (this.variations.length === 0) {
            return '<div class="text-muted">Nenhuma variação adicionada ainda.</div>';
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Variação</th>
                            <th>Preço (R$)</th>
                            <th>Estoque</th>
                            <th>Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        this.variations.forEach(variation => {
            const nameInput = document.querySelector(`[name="variations[${variation.id}][name]"]`);
            const variationName = nameInput ? nameInput.value : `Variação ${variation.id}`;
            
            html += `
                <tr>
                    <td>${variationName}</td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" name="variation_prices[${variation.id}]" 
                                   step="0.01" min="0.01" required>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="variation_stocks[${variation.id}]" 
                               value="0" min="0">
                    </td>
                    <td>
                        <input type="number" class="form-control" name="variation_minimums[${variation.id}]" 
                               value="1" min="1">
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        return html;
    }
    
    setupPricingListeners() {
        // Listener para preview de preço
        const priceInput = document.querySelector('[name="product_price"]');
        if (priceInput) {
            priceInput.addEventListener('input', (e) => {
                const price = parseFloat(e.target.value) || 0;
                const guaraniPrice = price * this.exchangeRate;
                const preview = document.getElementById('price_preview');
                if (preview) {
                    if (price > 0) {
                        const formatted = new Intl.NumberFormat('es-PY', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(guaraniPrice);
                        preview.innerHTML = `<strong>≈ G$ ${formatted}</strong> (será convertido automaticamente)`;
                        preview.className = 'form-text text-success';
                    } else {
                        preview.innerHTML = 'Será convertido automaticamente para Guaranis';
                        preview.className = 'form-text';
                    }
                }
            });
        }
        
        // Atualizar unidade do mínimo baseado no tipo de medida
        const minQuantityUnit = document.getElementById('min_quantity_unit');
        if (minQuantityUnit && this.measureType) {
            const units = {
                'weight': 'kg',
                'volume': 'un',
                'unit': 'un'
            };
            minQuantityUnit.textContent = units[this.measureType] || 'un';
        }
    }
    
    generatePreview() {
        const previewDiv = document.getElementById('productPreview');
        if (!previewDiv) return;
        
        // Coletar dados do formulário
        const formData = this.collectFormData();
        
        let html = `
            <div class="preview-product-card" style="background: white; border: 1px solid #e9ecef; border-radius: 12px; padding: 25px;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="preview-image" style="text-align: center;">
                            ${this.generatePreviewImage()}
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h4 style="color: #2c3e50; margin-bottom: 15px;">${formData.name || 'Nome do Produto'}</h4>
                        <p style="color: #6c757d; margin-bottom: 20px;">${formData.description || 'Descrição do produto...'}</p>
                        
                        <div class="preview-details">
                            <div style="margin-bottom: 10px;">
                                <strong>Tipo:</strong> ${this.productType === 'simple' ? 'Produto Simples' : 'Produto com Variações'}
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>Medida:</strong> ${this.getMeasureTypeText()}
                            </div>
                            ${this.generatePreviewPrice(formData)}
                            ${this.generatePreviewVariations()}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        previewDiv.innerHTML = html;
    }
    
    generatePreviewImage() {
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview && imagePreview.querySelector('img')) {
            const img = imagePreview.querySelector('img');
            return `<img src="${img.src}" alt="Preview" style="max-width: 100%; max-height: 150px; object-fit: contain; border-radius: 8px;">`;
        }
        return `
            <div style="width: 150px; height: 150px; background: #f8f9fa; border: 2px dashed #dee2e6; 
                        border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                <div style="text-align: center; color: #6c757d;">
                    <i class="fas fa-image fa-2x mb-2"></i><br>
                    <small>Sem imagem</small>
                </div>
            </div>
        `;
    }
    
    generatePreviewPrice(formData) {
        if (this.productType === 'simple') {
            const price = formData.price || 0;
            const guaraniPrice = price * this.exchangeRate;
            const formatted = new Intl.NumberFormat('es-PY').format(guaraniPrice);
            
            return `
                <div style="margin-bottom: 10px;">
                    <strong>Preço:</strong> ${price > 0 ? `G$ ${formatted}` : 'Consultar con el vendedor'}
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>Mínimo:</strong> ${formData.minQuantity || 1} ${this.getUnitText()}
                </div>
            `;
        }
        return '<div style="margin-bottom: 10px;"><strong>Preços:</strong> Definidos por variação</div>';
    }
    
    generatePreviewVariations() {
        if (this.productType === 'variable' && this.variations.length > 0) {
            let html = '<div style="margin-top: 15px;"><strong>Variações disponíveis:</strong><ul style="margin: 5px 0 0 20px;">';
            
            this.variations.forEach(variation => {
                const nameInput = document.querySelector(`[name="variations[${variation.id}][name]"]`);
                const variationName = nameInput ? nameInput.value : `Variação ${variation.id}`;
                html += `<li>${variationName}</li>`;
            });
            
            html += '</ul></div>';
            return html;
        }
        return '';
    }
    
    getUnitText() {
        switch(this.measureType) {
            case 'weight': return 'kg';
            case 'volume': return 'unidades';
            case 'unit': return 'unidades';
            default: return 'un';
        }
    }
    
    collectFormData() {
        return {
            name: document.getElementById('product_name')?.value || '',
            description: document.getElementById('product_description')?.value || '',
            category: document.getElementById('category_id')?.value || '',
            stock: document.getElementById('product_stock')?.value || 0,
            price: document.querySelector('[name="product_price"]')?.value || 0,
            minQuantity: document.querySelector('[name="min_quantity"]')?.value || 1
        };
    }
    
    getMeasureTypeText() {
        const types = {
            'weight': 'Vendido por Peso (kg/g)',
            'volume': 'Vendido por Volume (ml/L)',
            'unit': 'Vendido por Unidade'
        };
        return types[this.measureType] || 'Não definido';
    }
    
    showNotification(message, type = 'info') {
        // Remover notificações existentes
        const existingNotifications = document.querySelectorAll('.wizard-notification');
        existingNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `wizard-notification alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        const icons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="${icons[type]} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    saveProgress() {
        const progress = {
            currentStep: this.currentStep,
            productType: this.productType,
            measureType: this.measureType,
            variations: this.variations,
            timestamp: Date.now()
        };
        
        localStorage.setItem('productWizardProgress', JSON.stringify(progress));
    }
    
    loadProgress() {
        const saved = localStorage.getItem('productWizardProgress');
        if (saved) {
            try {
                const progress = JSON.parse(saved);
                
                // Verificar se o progresso não é muito antigo (24 horas)
                if (Date.now() - progress.timestamp < 24 * 60 * 60 * 1000) {
                    this.currentStep = progress.currentStep || 1;
                    this.productType = progress.productType;
                    this.measureType = progress.measureType;
                    this.variations = progress.variations || [];
                    
                    // Restaurar seleções visuais
                    if (this.productType) {
                        document.querySelector(`[data-type="${this.productType}"]`)?.classList.add('selected');
                    }
                    if (this.measureType) {
                        document.querySelector(`[data-measure="${this.measureType}"]`)?.classList.add('selected');
                    }
                }
            } catch (e) {
                console.log('Erro ao carregar progresso salvo:', e);
            }
        }
    }
    
    autoSave() {
        // Debounce auto-save
        clearTimeout(this.autoSaveTimeout);
        this.autoSaveTimeout = setTimeout(() => {
            this.saveProgress();
        }, 1000);
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    window.productWizard = new ProductWizard();
});

// Expor globalmente para callbacks inline
window.ProductWizard = ProductWizard; 