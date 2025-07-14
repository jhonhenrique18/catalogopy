/**
 * Sistema de carrinho unificado e robusto - Versão 3.0
 * Sistema híbrido: server-side para persistência + client-side para performance
 * Funciona em todos os ambientes (index, categorias, carrinho)
 */

// Variável global para cache do carrinho
let cartCache = [];
let cartCountCache = 0;

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cart.js inicializado');
    
    // Configurar event listeners para produtos já carregados
    setupCartEvents();
    
    // Buscar carrinho do servidor ao inicializar
    loadCartFromServer();
    
    // Atualizar contador inicial com delay para garantir carregamento
    setTimeout(() => {
        updateCartCount();
        console.log('🛒 Cart.js - Contador inicial carregado');
    }, 150);
    
    // Força atualização periódica dos contadores a cada 2 segundos durante os primeiros 10 segundos
    let attempts = 0;
    const intervalId = setInterval(() => {
        updateCartCount();
        attempts++;
        console.log(`🔄 Sincronização ${attempts}/5`);
        if (attempts >= 5) {
            clearInterval(intervalId);
            console.log('✅ Sincronização do carrinho concluída');
        }
    }, 2000);
});

/**
 * Configura todos os event listeners do carrinho
 * Esta função é chamada sempre que novos produtos são carregados
 */
function setupCartEvents() {
    // Configurar botões de quantidade
    setupQuantityButtons();
    
    // Configurar botões de adicionar ao carrinho
    setupAddToCartButtons();
}

/**
 * Configura os botões de quantidade (+ e -)
 * Otimizado para o novo design moderno com feedback visual
 */
function setupQuantityButtons() {
    // Botões de diminuir
    document.querySelectorAll('.btn-qty-minus:not([data-cart-initialized]), .quantity-btn.btn-qty-minus:not([data-cart-initialized])').forEach(button => {
        button.setAttribute('data-cart-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.product-quantity[data-product-id="${productId}"], .quantity-input[data-product-id="${productId}"]`);
            
            // Feedback visual
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
            
            if (input) {
                let value = parseInt(input.value) || 1;
                const minValue = parseInt(input.getAttribute('min')) || 1;
                if (value > minValue) {
                    input.value = value - 1;
                    // Animação sutil no input
                    input.style.animation = 'none';
                    input.offsetHeight; // Força reflow
                    input.style.animation = 'pulse 0.3s ease';
                }
            }
        });
    });
    
    // Botões de aumentar
    document.querySelectorAll('.btn-qty-plus:not([data-cart-initialized]), .quantity-btn.btn-qty-plus:not([data-cart-initialized])').forEach(button => {
        button.setAttribute('data-cart-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const input = document.querySelector(`.product-quantity[data-product-id="${productId}"], .quantity-input[data-product-id="${productId}"]`);
            
            // Feedback visual
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = '';
            }, 100);
            
            if (input) {
                let value = parseInt(input.value) || 1;
                input.value = value + 1;
                // Animação sutil no input
                input.style.animation = 'none';
                input.offsetHeight; // Força reflow
                input.style.animation = 'pulse 0.3s ease';
            }
        });
    });
}

/**
 * Configura os botões de adicionar ao carrinho
 * Otimizado para o novo design moderno
 */
function setupAddToCartButtons() {
    // Todos os botões com data-product-id (produtos com e sem preço)
    document.querySelectorAll('.btn-agregar[data-product-id]:not([data-cart-initialized])').forEach(button => {
        button.setAttribute('data-cart-initialized', 'true');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = parseInt(this.getAttribute('data-product-id'));
            const input = document.querySelector(`.product-quantity[data-product-id="${productId}"]`);
            let quantity = 1;
            
            if (input) {
                const inputValue = parseInt(input.value);
                const minValue = parseInt(input.getAttribute('min')) || 1;
                quantity = (!isNaN(inputValue) && inputValue > 0) ? inputValue : minValue;
            }
            
            console.log('Clique no botão adicionar:', { productId, quantity });
            
            if (productId && quantity > 0) {
                // Adicionar feedback visual moderno
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                addToCart(productId, quantity);
                
                // Não resetar quantidade após adicionar ao carrinho
                // Quantidade mantida para facilitar múltiplas compras
            } else {
                showNotification('Dados inválidos do produto', 'error');
            }
        });
    });
}

/**
 * Adiciona produto ao carrinho
 */
function addToCart(productId, quantity) {
    console.log('Função addToCart chamada:', { productId, quantity });
    
    // Validações
    if (!productId || productId <= 0) {
        showNotification('ID do produto inválido', 'error');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        showNotification('Quantidade inválida', 'error');
        return;
    }
    
    // Desabilitar botão temporariamente para evitar cliques duplos
    const button = document.querySelector(`.btn-agregar[data-product-id="${productId}"]`);
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    }
    
    // Fazer requisição para o servidor
    fetch('includes/cart_add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        console.log('Response recebida:', response.status);
    
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Response data:', data);
            
            if (data.success) {
                showNotification(data.message || 'Produto adicionado com sucesso!', 'success');
                
                // Atualizar cache e contador
                cartCountCache = data.cart_count || (cartCountCache + quantity);
                updateCartCountDisplay();
                
                // Recarregar carrinho do servidor para manter sincronizado
                loadCartFromServer();
            } else {
                showNotification(data.message || 'Erro ao adicionar produto', 'error');
            }
        } catch (e) {
            console.error('Erro ao fazer parse do JSON:', e);
            console.error('Response text:', text);
            showNotification('Erro na resposta do servidor', 'error');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showNotification('Erro de conexão. Tente novamente.', 'error');
    })
    .finally(() => {
        // Reabilitar botão
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Agregar';
        }
    });
}

/**
 * Carrega o carrinho do servidor para o cache local
 */
function loadCartFromServer() {
    fetch('includes/cart_get.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cartCache = data.cart || [];
                cartCountCache = data.count || 0;
                updateCartCountDisplay();
                
                // Se estamos na página do carrinho, renderizar
                if (window.location.pathname.includes('carrinho.php')) {
                    renderCart();
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar carrinho:', error);
        });
}

/**
 * Atualiza o contador do carrinho no display
 */
function updateCartCountDisplay() {
    const countElements = document.querySelectorAll('#cart-count, .cart-count, #footer-cart-count, .footer-cart-count');
    console.log('🛒 Atualizando contadores:', countElements.length, 'elementos encontrados');
    console.log('🛒 Valor do cache:', cartCountCache);
    
    countElements.forEach(element => {
        element.textContent = cartCountCache;
        
        // Para o footer, sempre manter visível mas com o valor correto
        if (element.classList.contains('footer-cart-count') || element.id === 'footer-cart-count') {
            // Sempre manter o elemento visível no footer
            element.style.display = 'flex';
            element.classList.remove('hidden');
            
            // Aplicar animação apenas se contador > 0
            if (cartCountCache > 0) {
                element.style.animation = 'none';
                element.offsetHeight; // Força reflow
                element.style.animation = 'cartPulse 0.6s ease-out';
                // Adicionar classe para styling especial quando tem itens
                element.classList.add('has-items');
            } else {
                // Quando zero, remover animação mas manter visível
                element.style.animation = 'none';
                element.classList.remove('has-items');
            }
            
            console.log('🛒 Footer contador atualizado:', element.id, 'para:', cartCountCache);
        } else {
            // Para elementos normais do carrinho (navbar)
            if (cartCountCache > 0) {
                element.style.display = 'flex';
                element.classList.remove('hidden');
            } else {
                element.style.display = 'none';
            }
        }
    });
    
    console.log('✅ Todos os contadores atualizados para:', cartCountCache);
}

/**
 * Atualiza o contador do carrinho (busca do servidor)
 */
function updateCartCount() {
    fetch('includes/cart_count.php')
        .then(response => response.json())
        .then(data => {
            cartCountCache = data.count || 0;
            updateCartCountDisplay();
        })
        .catch(error => {
            console.error('Erro ao atualizar contador:', error);
        });
}

/**
 * Renderiza o carrinho na página carrinho.php
 */
function renderCart() {
    const cartContainer = document.getElementById('cart-container');
    if (!cartContainer) {
        console.log('Container do carrinho não encontrado');
        return;
    }
    
    console.log('Renderizando carrinho com cache:', cartCache);
    
    if (!cartCache || cartCache.length === 0) {
        // Carrinho vazio
        cartContainer.innerHTML = `
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <p class="cart-empty-message">Tu carrito está vacío</p>
                <a href="index.php" class="btn btn-primary">Continuar Comprando</a>
            </div>
        `;
        
        // Esconder botão de limpar carrinho
        const clearButton = document.getElementById('btn-clear-cart');
        if (clearButton) clearButton.style.display = 'none';
        return;
    }
    
    // Mostrar botão de limpar carrinho
    const clearButton = document.getElementById('btn-clear-cart');
    if (clearButton) clearButton.style.display = 'flex';
    
    // Calcular totais
    let subtotal = 0;
    let totalWeight = 0;
    let totalQuantity = 0;
    const shippingRate = 1500; // Taxa padrão de frete
    
    // Construir HTML dos itens do carrinho
    let cartItemsHtml = '';
    
    cartCache.forEach((item, index) => {
        // Garantir que todas as propriedades existam
        const minWholesaleQuantity = Number(item.min_wholesale_quantity) || 10;
        const wholesalePrice = Number(item.wholesale_price_pyg) || 0;
        const retailPrice = Number(item.retail_price_pyg) || 0;
        const quantity = Number(item.quantity) || 1;
        const weight = Number(item.weight) || 1;
        const imageUrl = item.image_url || 'assets/images/no-image.png';
        const hasPrice = item.has_price !== false && (item.wholesale_price || item.wholesale_price_pyg);
        
        // Determinar preço e tipo
        let price = 0;
        let itemSubtotal = 0;
        let priceInfo = '';
        
        if (hasPrice) {
            // Verificar se os mínimos globais estão ativados
            const shouldRespectMinimums = typeof window.areMinimumQuantitiesEnabled === 'function' ? 
                window.areMinimumQuantitiesEnabled() : true;
            const effectiveMinQuantity = shouldRespectMinimums ? minWholesaleQuantity : 1;
            
            const isWholesale = quantity >= effectiveMinQuantity;
            price = isWholesale ? wholesalePrice : retailPrice;
            itemSubtotal = price * quantity;
            priceInfo = isWholesale ? 'Precio mayorista' : 'Precio minorista';
        } else {
            priceInfo = 'Precio a consultar';
            itemSubtotal = 0; // Não contribui para o subtotal
        }
    
        // Atualizar totais (apenas produtos com preço)
        if (hasPrice) {
            subtotal += itemSubtotal;
        }
        totalWeight += weight * quantity;
        totalQuantity += quantity;
        
        // Obter informações de unidade
        const unitType = item.unit_type || 'kg';
        const unitDisplayName = item.unit_display_name || 'kg';
        
        // HTML específico para produtos com ou sem preço
        let priceDisplay, subtotalDisplay;
        
        if (hasPrice) {
            // Para produtos por unidade, mostrar "por unidade" ao invés da unidade específica
            const priceUnit = unitType === 'kg' ? unitDisplayName : 'unidade';
            priceDisplay = `<p class="cart-item-price">G$ ${formatNumber(price)} por ${priceUnit}</p>`;
            subtotalDisplay = `<p class="cart-item-subtotal">Subtotal: G$ ${formatNumber(itemSubtotal)}</p>`;
        } else {
            priceDisplay = `<p class="cart-item-price text-info"><i class="fas fa-comments-dollar me-1"></i>Precio a consultar</p>`;
            subtotalDisplay = `<p class="cart-item-subtotal text-muted"><small>Cotizar con vendedor</small></p>`;
        }
        
        // Preparar nome para exibição - priorizar display_name se disponível
        const productName = item.display_name || item.name;
        
        // Adicionar indicador visual se for variação
        let variationIndicator = '';
        if (item.is_variation && item.variation_display) {
            variationIndicator = `<span class="variation-badge">${item.variation_display}</span>`;
        }
        
        cartItemsHtml += `
            <div class="cart-item ${!hasPrice ? 'quote-required' : ''}">
                <div class="d-flex">
                    <div class="cart-item-image" style="background-image: url('${imageUrl}')"></div>
                    <div class="cart-item-info">
                        <h3 class="cart-item-name">
                            ${productName}
                            ${variationIndicator}
                        </h3>
                        ${priceDisplay}
                        <p class="cart-item-type">${priceInfo}</p>
                        <p class="cart-item-weight">${unitType === 'kg' ? `Peso: ${weight} kg` : `${quantity} unidade${quantity > 1 ? 's' : ''}${unitDisplayName === 'ml' ? ` de ${weight * 1000}ml cada` : ''}`}</p>
                        ${subtotalDisplay}
                        
                        <div class="cart-item-actions">
                            <div class="quantity-control">
                                <button type="button" class="btn-qty-minus" data-index="${index}">-</button>
                                <input type="number" min="1" value="${quantity}" class="cart-quantity" data-index="${index}">
                                <button type="button" class="btn-qty-plus" data-index="${index}">+</button>
                            </div>
                            <button type="button" class="btn-remove" data-index="${index}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Calcular frete e total baseado na configuração
    const shippingEnabled = typeof window.isShippingEnabled === 'function' ? 
        window.isShippingEnabled() : true;
    const shipping = shippingEnabled ? calculateShipping(totalWeight, shippingRate) : 0;
    const total = subtotal + shipping;
    
    // Calcular produtos com e sem preço
    const productsWithPrice = cartCache.filter(item => {
        const hasPrice = item.has_price !== false && (item.wholesale_price || item.wholesale_price_pyg);
        return hasPrice;
    }).length;
    const productsToQuote = cartCache.length - productsWithPrice;
    
    // Construir HTML do resumo
    const totalProducts = cartCache.length;
    let quoteAlert = '';
    
    if (productsToQuote > 0) {
        quoteAlert = `
            <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>${productsToQuote} ${productsToQuote === 1 ? 'producto requiere' : 'productos requieren'} cotización</strong><br>
                <small>El vendedor te enviará el precio total por WhatsApp</small>
            </div>
        `;
    }
    
    // Construir resumo baseado nas configurações
    let shippingSection = '';
    let totalLabel = 'Total';
    
    if (shippingEnabled) {
        shippingSection = `
            <div class="summary-item">
                <span>Flete:</span>
                <span>G$ ${formatNumber(shipping)}</span>
            </div>
        `;
        totalLabel = productsToQuote > 0 ? 'Total (parcial)' : 'Total';
    } else {
        totalLabel = productsToQuote > 0 ? 'Subtotal (parcial)' : 'Subtotal';
    }
    
    // Verificar se mínimos estão ativados para a mensagem de incentivo
    const minimumsEnabled = typeof window.areMinimumQuantitiesEnabled === 'function' ? 
        window.areMinimumQuantitiesEnabled() : true;
    
    const incentiveMessage = minimumsEnabled ? 
        '¡Compra más cantidad para obtener mejores precios mayoristas!' :
        '¡Tenemos los mejores precios en productos naturales!';
    
    const summaryHtml = `
        ${quoteAlert}
        <div class="cart-summary">
            <div class="summary-item">
                <span>Subtotal (${productsWithPrice} con precio):</span>
                <span>G$ ${formatNumber(subtotal)}</span>
            </div>
            <div class="summary-item">
                <span>Peso total:</span>
                <span>${formatNumber(totalWeight, 2)} kg</span>
            </div>
            ${shippingSection}
            <div class="summary-item total">
                <span>${totalLabel}:</span>
                <span>G$ ${formatNumber(total)}${productsToQuote > 0 ? ' + cotización' : ''}</span>
            </div>
            <div class="summary-incentive">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    ${incentiveMessage}
                </small>
            </div>
        </div>
    `;
    
    // Botão de checkout
    const checkoutButtonHtml = `
        <form action="checkout.php" method="post" id="checkout-form">
            <input type="hidden" name="cart_data" value='${JSON.stringify(cartCache)}'>
            <input type="hidden" name="subtotal" value="${subtotal}">
            <input type="hidden" name="shipping" value="${shipping}">
            <input type="hidden" name="total" value="${total}">
            <input type="hidden" name="total_weight" value="${totalWeight}">
            <button type="submit" class="btn-continuar">
                Continuar al Checkout <i class="fas fa-chevron-right ms-2"></i>
            </button>
        </form>
    `;
    
    // Montar HTML completo
    cartContainer.innerHTML = `
        ${cartItemsHtml}
        ${summaryHtml}
        ${checkoutButtonHtml}
    `;
    
    // Configurar event listeners para os controles do carrinho
    setupCartControls();
}

/**
 * Configura os controles do carrinho (página carrinho.php)
 */
function setupCartControls() {
    // Botões de quantidade
    document.querySelectorAll('.btn-qty-minus').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            updateCartItemQuantity(index, -1);
        });
    });
    
    document.querySelectorAll('.btn-qty-plus').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            updateCartItemQuantity(index, 1);
        });
    });
    
    // Inputs de quantidade
    document.querySelectorAll('.cart-quantity').forEach(input => {
        input.addEventListener('change', function() {
            const index = parseInt(this.dataset.index);
            const newQuantity = parseInt(this.value);
            
            if (newQuantity > 0) {
                setCartItemQuantity(index, newQuantity);
            } else {
                this.value = cartCache[index].quantity;
    }
        });
    });
    
    // Botões de remover
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            removeCartItem(index);
        });
    });
}

/**
 * Atualiza quantidade de um item do carrinho
 */
function updateCartItemQuantity(index, change) {
    if (cartCache[index]) {
        const newQuantity = cartCache[index].quantity + change;
        if (newQuantity > 0) {
            setCartItemQuantity(index, newQuantity);
        }
    }
}

/**
 * Define quantidade específica de um item do carrinho
 */
function setCartItemQuantity(index, quantity) {
    if (cartCache[index]) {
        cartCache[index].quantity = quantity;
        syncCartWithServer();
        renderCart();
            updateCartCount();
    }
}

/**
 * Remove item do carrinho
 */
function removeCartItem(index) {
    if (cartCache[index]) {
        cartCache.splice(index, 1);
        syncCartWithServer();
        renderCart();
        updateCartCount();
    }
}

/**
 * Limpa todo o carrinho
 */
function clearCart() {
    fetch('includes/cart_clear.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cartCache = [];
                cartCountCache = 0;
                updateCartCountDisplay();
                
                if (window.location.pathname.includes('carrinho.php')) {
                    renderCart();
}

                showNotification('Carrinho limpo com sucesso', 'success');
            }
        })
        .catch(error => {
            console.error('Erro ao limpar carrinho:', error);
        });
}

/**
 * Sincroniza carrinho local com o servidor
 */
function syncCartWithServer() {
    fetch('includes/cart_sync.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ cart: cartCache })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Carrinho sincronizado:', data);
    })
    .catch(error => {
        console.error('Erro ao sincronizar carrinho:', error);
    });
}

/**
 * Calcula frete baseado no peso
 */
function calculateShipping(weight, rate) {
    return Math.ceil(weight * rate);
}

/**
 * Formata números no padrão brasileiro/paraguaio
 */
function formatNumber(number, decimals = 0) {
    const num = parseFloat(number);
    if (isNaN(num)) return "0";
    
    return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Mostra notificação moderna com design atualizado
 */
function showNotification(message, type = 'success') {
    console.log('Notificação:', message, type);
    
    // Tentar usar o toast do Bootstrap primeiro
    const toast = document.getElementById('toast-notification');
    if (toast) {
        const toastBody = toast.querySelector('.toast-body');
        
        if (toastBody) {
            // Configurar ícone baseado no tipo
            let icon = 'fas fa-check-circle';
            if (type === 'error') {
                icon = 'fas fa-exclamation-circle';
            } else if (type === 'warning') {
                icon = 'fas fa-exclamation-triangle';
            } else if (type === 'info') {
                icon = 'fas fa-info-circle';
            }
            
            toastBody.innerHTML = `<i class="${icon} me-2"></i>${message}`;
            
            // Configurar cor baseada no tipo com gradientes modernos
            const toastElement = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            
            // Aplicar estilos modernos baseados no tipo
            toast.style.background = '';
            toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info');
            
            if (type === 'error') {
                toast.style.background = 'linear-gradient(135deg, #FF4444, #E53935)';
            } else if (type === 'warning') {
                toast.style.background = 'linear-gradient(135deg, #FF8800, #F57F17)';
            } else if (type === 'info') {
                toast.style.background = 'linear-gradient(135deg, #33B5E5, #1976D2)';
            } else {
                toast.style.background = 'linear-gradient(135deg, #00C851, #00A441)';
            }
            
            toastElement.show();
            return;
        }
    }
    
    // Fallback: Criar elemento de notificação moderno
    let notification = document.getElementById('cart-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'cart-notification';
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            z-index: 9999;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            min-width: 280px;
            max-width: 90vw;
            text-align: center;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        `;
        document.body.appendChild(notification);
    }
    
    // Configurar ícone e cor baseado no tipo
    let icon = 'fas fa-check-circle';
    let backgroundColor = 'linear-gradient(135deg, #00C851, #00A441)';
    
    if (type === 'error') {
        icon = 'fas fa-exclamation-circle';
        backgroundColor = 'linear-gradient(135deg, #FF4444, #E53935)';
    } else if (type === 'warning') {
        icon = 'fas fa-exclamation-triangle';
        backgroundColor = 'linear-gradient(135deg, #FF8800, #F57F17)';
    } else if (type === 'info') {
        icon = 'fas fa-info-circle';
        backgroundColor = 'linear-gradient(135deg, #33B5E5, #1976D2)';
    }
    
    notification.style.background = backgroundColor;
    notification.innerHTML = `<i class="${icon}" style="margin-right: 0.5rem;"></i>${message}`;
    
    // Mostrar notificação com animação suave
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(-50%) translateY(0)';
    }, 10);
    
    // Esconder após 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(-50%) translateY(-20px)';
    }, 3000);
}

// Tornar funções disponíveis globalmente para uso em outras páginas
window.renderCart = renderCart;
window.clearCart = clearCart;
window.updateCartCount = updateCartCount;
window.formatNumber = formatNumber;
window.calculateShipping = calculateShipping;
window.setupQuantityButtons = setupQuantityButtons;
window.setupAddToCartButtons = setupAddToCartButtons;