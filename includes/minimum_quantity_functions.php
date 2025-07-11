<?php
/**
 * SISTEMA UNIFICADO DE QUANTIDADES MÍNIMAS
 * Garante comportamento consistente em todo o site
 * Focado na experiência do usuário paraguaio mobile
 */

/**
 * Obter configurações globais de quantidade mínima
 */
function getMinimumQuantitySettings() {
    global $conn;
    
    static $settings = null;
    
    if ($settings === null) {
        $query = "SELECT enable_global_minimums, minimum_explanation_text FROM store_settings WHERE id = 1";
        $result = $conn->query($query);
        $settings = $result ? $result->fetch_assoc() : [
            'enable_global_minimums' => 0,
            'minimum_explanation_text' => 'Quantidade mínima para atacado'
        ];
    }
    
    return $settings;
}

/**
 * Verificar se produto deve aplicar quantidade mínima
 * LÓGICA UNIFICADA PARA TODO O SITE
 */
function shouldApplyMinimumQuantity($product) {
    $settings = getMinimumQuantitySettings();
    
    // Produto deve ter has_min_quantity = 1 E min_wholesale_quantity > 0
    $productHasMinimum = isset($product['has_min_quantity']) && 
                        $product['has_min_quantity'] == 1 && 
                        isset($product['min_wholesale_quantity']) && 
                        $product['min_wholesale_quantity'] > 0;
    
    if (!$productHasMinimum) {
        return false;
    }
    
    // Se configuração global está ativa, aplicar sempre
    if ($settings['enable_global_minimums'] == 1) {
        return true;
    }
    
    // Se configuração global está inativa, aplicar apenas se produto tem mínimo individual
    return $productHasMinimum;
}

/**
 * Obter quantidade mínima efetiva para um produto
 */
function getEffectiveMinimumQuantity($product) {
    if (!shouldApplyMinimumQuantity($product)) {
        return 1;
    }
    
    return max(1, (int)$product['min_wholesale_quantity']);
}

/**
 * Verificar se deve mostrar texto de quantidade mínima
 */
function shouldShowMinimumText($product) {
    return shouldApplyMinimumQuantity($product);
}

/**
 * Gerar HTML do texto de quantidade mínima (unificado)
 */
function generateMinimumQuantityText($product) {
    if (!shouldShowMinimumText($product)) {
        return '';
    }
    
    $minQuantity = getEffectiveMinimumQuantity($product);
    $unitDisplay = $product['unit_display_name'] ?? 'unidades';
    
    return '<p class="product-min-qty">Mínimo: ' . $minQuantity . ' ' . $unitDisplay . '</p>';
}

/**
 * Gerar dados JSON para JavaScript (unificado)
 * Usado em index.php, categorias.php, etc.
 */
function generateProductMinimumData($product) {
    return [
        'has_min_quantity' => shouldApplyMinimumQuantity($product),
        'min_wholesale_quantity' => getEffectiveMinimumQuantity($product),
        'should_show_minimum_text' => shouldShowMinimumText($product),
        'minimum_text' => generateMinimumQuantityText($product)
    ];
}

/**
 * Gerar controles de quantidade unificados
 * Para uso em cards de produto, página individual, etc.
 */
function generateQuantityControls($product, $containerId = null) {
    $minQuantity = getEffectiveMinimumQuantity($product);
    $productId = $product['id'];
    
    $containerAttr = $containerId ? "data-container=\"{$containerId}\"" : "";
    
    return "
        <div class=\"quantity-control\" {$containerAttr}>
            <button type=\"button\" class=\"btn-qty-minus\" data-product-id=\"{$productId}\">-</button>
            <input type=\"number\" 
                   min=\"{$minQuantity}\" 
                   value=\"{$minQuantity}\" 
                   class=\"product-quantity\" 
                   data-product-id=\"{$productId}\" 
                   inputmode=\"numeric\">
            <button type=\"button\" class=\"btn-qty-plus\" data-product-id=\"{$productId}\">+</button>
        </div>
    ";
}

/**
 * Validar quantidade no carrinho (backend)
 */
function validateCartQuantity($productId, $requestedQuantity) {
    global $conn;
    
    // Buscar dados do produto
    $query = "SELECT * FROM products WHERE id = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        return ['valid' => false, 'message' => 'Produto não encontrado'];
    }
    
    $minQuantity = getEffectiveMinimumQuantity($product);
    
    if ($requestedQuantity < $minQuantity) {
        $unitDisplay = $product['unit_display_name'] ?? 'unidades';
        return [
            'valid' => false, 
            'message' => "Quantidade mínima para este produto é {$minQuantity} {$unitDisplay}",
            'minimum_quantity' => $minQuantity
        ];
    }
    
    return ['valid' => true, 'quantity' => $requestedQuantity];
}

/**
 * Preparar dados de produto para exibição unificada
 * Centraliza toda lógica de mínimos em um só lugar
 */
function prepareProductForDisplay($product) {
    // Adicionar dados de quantidade mínima
    $product['minimum_data'] = generateProductMinimumData($product);
    $product['effective_min_quantity'] = getEffectiveMinimumQuantity($product);
    $product['should_apply_minimum'] = shouldApplyMinimumQuantity($product);
    $product['minimum_text_html'] = generateMinimumQuantityText($product);
    
    return $product;
}

/**
 * Gerar JavaScript unificado para controles de quantidade
 * Para ser incluído em todas as páginas
 */
function generateMinimumQuantityJavaScript() {
    $settings = getMinimumQuantitySettings();
    
    return "
    <script>
    // CONFIGURAÇÕES GLOBAIS DE QUANTIDADE MÍNIMA
    window.minimumQuantitySettings = {
        globalEnabled: " . ($settings['enable_global_minimums'] ? 'true' : 'false') . ",
        explanationText: '" . addslashes($settings['minimum_explanation_text']) . "'
    };
    
    // FUNÇÃO UNIFICADA PARA APLICAR MÍNIMOS
    function applyMinimumQuantityLogic(product, globalEnabled = null) {
        if (globalEnabled === null) {
            globalEnabled = window.minimumQuantitySettings.globalEnabled;
        }
        
        // Produto deve ter has_min_quantity = 1 E min_wholesale_quantity > 0
        const productHasMinimum = product.has_min_quantity == 1 && 
                                 product.min_wholesale_quantity > 0;
        
        if (!productHasMinimum) {
            return {
                shouldApply: false,
                effectiveMinimum: 1,
                shouldShowText: false
            };
        }
        
        // Se global ativo, aplicar sempre
        // Se global inativo, aplicar apenas se produto tem mínimo individual
        const shouldApply = globalEnabled || productHasMinimum;
        
        return {
            shouldApply: shouldApply,
            effectiveMinimum: shouldApply ? Math.max(1, parseInt(product.min_wholesale_quantity)) : 1,
            shouldShowText: shouldApply
        };
    }
    
    // FUNÇÃO PARA ATUALIZAR CONTROLES DE QUANTIDADE
    function updateQuantityControls(productId, productData) {
        const logic = applyMinimumQuantityLogic(productData);
        
        // Encontrar controles do produto
        const quantityInput = document.querySelector('.product-quantity[data-product-id=\"' + productId + '\"]');
        if (quantityInput) {
            quantityInput.min = logic.effectiveMinimum;
            quantityInput.value = Math.max(quantityInput.value, logic.effectiveMinimum);
        }
        
        return logic;
    }
    
    // EVENT LISTENERS UNIFICADOS
    document.addEventListener('DOMContentLoaded', function() {
        // Botões de quantidade
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-qty-plus')) {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                const input = document.querySelector('.product-quantity[data-product-id=\"' + productId + '\"]');
                if (input) {
                    input.value = parseInt(input.value) + 1;
                    input.dispatchEvent(new Event('change'));
                }
            }
            
            if (e.target.classList.contains('btn-qty-minus')) {
                e.preventDefault();
                const productId = e.target.dataset.productId;
                const input = document.querySelector('.product-quantity[data-product-id=\"' + productId + '\"]');
                if (input) {
                    const newValue = Math.max(parseInt(input.min), parseInt(input.value) - 1);
                    input.value = newValue;
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
        
        // Validação de inputs de quantidade
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-quantity')) {
                const min = parseInt(e.target.min);
                const current = parseInt(e.target.value);
                
                if (current < min) {
                    e.target.value = min;
                    
                    // Mostrar notificação amigável
                    showMinimumQuantityNotification(min, e.target.dataset.productId);
                }
            }
        });
    });
    
    // NOTIFICAÇÃO AMIGÁVEL PARA USUÁRIO PARAGUAIO
    function showMinimumQuantityNotification(minQuantity, productId) {
        // Remover notificações existentes
        const existing = document.querySelectorAll('.minimum-notification');
        existing.forEach(n => n.remove());
        
        // Criar notificação
        const notification = document.createElement('div');
        notification.className = 'minimum-notification alert alert-warning alert-dismissible fade show';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        notification.innerHTML = \`
            <div class=\"d-flex align-items-center\">
                <i class=\"fas fa-info-circle me-2\"></i>
                <span>Cantidad mínima: <strong>\${minQuantity} unidades</strong></span>
                <button type=\"button\" class=\"btn-close ms-auto\" data-bs-dismiss=\"alert\"></button>
            </div>
        \`;
        
        document.body.appendChild(notification);
        
        // Auto-remove após 3 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
    </script>
    ";
}

/**
 * Incluir CSS unificado para controles de quantidade
 */
function generateMinimumQuantityCSS() {
    return "
    <style>
    /* CONTROLES DE QUANTIDADE UNIFICADOS */
    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        width: 100%;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        overflow: hidden;
        background: white;
    }
    
    .btn-qty-minus,
    .btn-qty-plus {
        background: #f8f9fa;
        border: none;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #495057;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    
    .btn-qty-minus:hover,
    .btn-qty-plus:hover {
        background: #e9ecef;
        color: #212529;
    }
    
    .btn-qty-minus:active,
    .btn-qty-plus:active {
        background: #dee2e6;
    }
    
    .product-quantity {
        border: none;
        width: 60px;
        height: 35px;
        text-align: center;
        font-weight: 600;
        background: white;
        color: #495057;
        outline: none;
        -moz-appearance: textfield;
    }
    
    .product-quantity::-webkit-outer-spin-button,
    .product-quantity::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    .product-quantity:focus {
        background: #f8f9fa;
        box-shadow: inset 0 0 0 1px #007bff;
    }
    
    /* TEXTO DE QUANTIDADE MÍNIMA */
    .product-min-qty {
        color: #6c757d;
        font-size: 12px;
        margin: 5px 0 0 0;
        display: flex;
        align-items: center;
    }
    
    .product-min-qty::before {
        content: '\f1de';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-right: 5px;
        color: #ffc107;
    }
    
    /* RESPONSIVO PARA MOBILE PARAGUAIO */
    @media (max-width: 768px) {
        .quantity-control {
            width: 100%;
            justify-content: center;
        }
        
        .btn-qty-minus,
        .btn-qty-plus {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        .product-quantity {
            width: 50px;
            height: 40px;
            font-size: 16px;
        }
        
        .product-min-qty {
            font-size: 11px;
        }
    }
    
    /* TOUCH FRIENDLY PARA MOBILE */
    @media (hover: none) {
        .btn-qty-minus,
        .btn-qty-plus {
            min-height: 44px;
            min-width: 44px;
        }
    }
    
    /* NOTIFICAÇÕES */
    .minimum-notification {
        animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    </style>
    ";
}

?> 