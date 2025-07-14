/**
 * JavaScript específico para melhorias das categorias
 * Foco em usabilidade e experiência do usuário mobile
 */

// ===== CONFIGURAÇÕES GLOBAIS =====
const CATEGORY_CONFIG = {
    animationDelay: 100,
    hoverScale: 1.02,
    hoverTranslate: -8,
    badgeAnimationDelay: 800,
    loadingAnimationDelay: 300
};

// ===== CLASSE PRINCIPAL PARA CATEGORIAS =====
class CategoryManager {
    constructor() {
        this.categories = [];
        this.initialized = false;
        this.touchDevice = 'ontouchstart' in window;
        
        this.init();
    }
    
    init() {
        if (this.initialized) return;
        
        document.addEventListener('DOMContentLoaded', () => {
            this.collectCategories();
            this.setupAnimations();
            this.setupInteractions();
            this.setupAccessibility();
            this.setupPerformanceOptimizations();
            this.initialized = true;
        });
    }
    
    collectCategories() {
        this.categories = Array.from(document.querySelectorAll('.modern-category'));
        console.log(`📂 ${this.categories.length} categorias encontradas`);
    }
    
    setupAnimations() {
        // Animação de entrada escalonada
        setTimeout(() => {
            this.animateEntrance();
        }, CATEGORY_CONFIG.loadingAnimationDelay);
        
        // Animação dos badges após as categorias
        setTimeout(() => {
            this.animateBadges();
        }, CATEGORY_CONFIG.badgeAnimationDelay);
    }
    
    animateEntrance() {
        this.categories.forEach((category, index) => {
            // Estado inicial
            category.style.opacity = '0';
            category.style.transform = 'translateY(30px) scale(0.9)';
            
            // Animação de entrada
            setTimeout(() => {
                category.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                category.style.opacity = '1';
                category.style.transform = 'translateY(0) scale(1)';
            }, index * CATEGORY_CONFIG.animationDelay);
        });
    }
    
    animateBadges() {
        const badges = document.querySelectorAll('.product-count-badge');
        
        badges.forEach((badge, index) => {
            badge.style.animation = 'none';
            badge.offsetHeight; // Forçar reflow
            
            setTimeout(() => {
                badge.style.animation = 'bounceIn 0.6s ease-out';
            }, index * 50);
        });
    }
    
    setupInteractions() {
        this.categories.forEach((category, index) => {
            this.setupCategoryInteractions(category, index);
        });
    }
    
    setupCategoryInteractions(category, index) {
        const categoryImage = category.querySelector('.category-image');
        const categoryIcon = category.querySelector('.category-image i');
        const categoryBadge = category.querySelector('.product-count-badge');
        const categorySubtitle = category.querySelector('.category-subtitle');
        
        // Hover effects para desktop
        if (!this.touchDevice) {
            category.addEventListener('mouseenter', () => {
                this.onCategoryHover(category, categoryIcon, categoryBadge, categorySubtitle);
            });
            
            category.addEventListener('mouseleave', () => {
                this.onCategoryLeave(category, categoryIcon, categoryBadge, categorySubtitle);
            });
        }
        
        // Touch effects para mobile
        category.addEventListener('touchstart', () => {
            this.onCategoryTouch(category);
        });
        
        category.addEventListener('touchend', () => {
            this.onCategoryTouchEnd(category);
        });
        
        // Click analytics
        category.addEventListener('click', () => {
            this.trackCategoryClick(category, index);
        });
    }
    
    onCategoryHover(category, icon, badge, subtitle) {
        category.style.transform = `translateY(${CATEGORY_CONFIG.hoverTranslate}px) scale(${CATEGORY_CONFIG.hoverScale})`;
        category.style.boxShadow = '0 12px 36px rgba(0,0,0,0.16)';
        
        if (icon) {
            icon.style.transform = 'scale(1.1) rotate(5deg)';
        }
        
        if (badge) {
            badge.style.transform = 'scale(1.1)';
        }
        
        if (subtitle) {
            subtitle.style.color = 'var(--primary-green)';
            subtitle.style.transform = 'translateY(-2px)';
        }
    }
    
    onCategoryLeave(category, icon, badge, subtitle) {
        category.style.transform = 'translateY(0) scale(1)';
        category.style.boxShadow = '0 2px 4px rgba(0,0,0,0.06)';
        
        if (icon) {
            icon.style.transform = 'scale(1) rotate(0deg)';
        }
        
        if (badge) {
            badge.style.transform = 'scale(1)';
        }
        
        if (subtitle) {
            subtitle.style.color = 'var(--text-secondary)';
            subtitle.style.transform = 'translateY(0)';
        }
    }
    
    onCategoryTouch(category) {
        category.style.transform = 'scale(0.95)';
        category.style.transition = 'transform 0.1s ease';
    }
    
    onCategoryTouchEnd(category) {
        setTimeout(() => {
            category.style.transform = 'scale(1)';
        }, 100);
    }
    
    trackCategoryClick(category, index) {
        const categoryTitle = category.querySelector('.category-title')?.textContent;
        const productCount = category.querySelector('.product-count-badge')?.textContent;
        
        console.log(`📊 Categoria clicada: ${categoryTitle} (${productCount || 0} produtos) - Posição: ${index + 1}`);
        
        // Aqui você pode adicionar tracking real (Google Analytics, etc.)
        // gtag('event', 'category_click', {
        //     category_name: categoryTitle,
        //     product_count: productCount,
        //     position: index + 1
        // });
    }
    
    setupAccessibility() {
        this.categories.forEach((category) => {
            // Adicionar atributos de acessibilidade
            category.setAttribute('role', 'button');
            category.setAttribute('tabindex', '0');
            
            // Melhorar descrição para leitores de tela
            const categoryTitle = category.querySelector('.category-title')?.textContent;
            const productCount = category.querySelector('.product-count-badge')?.textContent;
            
            if (categoryTitle) {
                const description = `Categoria ${categoryTitle}${productCount ? ` com ${productCount} produtos` : ''}`;
                category.setAttribute('aria-label', description);
            }
            
            // Navegação por teclado
            category.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    category.click();
                }
            });
        });
    }
    
    setupPerformanceOptimizations() {
        // Lazy loading para imagens de categorias (se necessário)
        const categoryImages = document.querySelectorAll('.category-image');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.style.willChange = 'auto';
                    imageObserver.unobserve(img);
                }
            });
        });
        
        categoryImages.forEach((img) => {
            imageObserver.observe(img);
        });
        
        // Throttle para resize events
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 250);
        });
    }
    
    handleResize() {
        // Reajustar animações em resize
        this.categories.forEach((category) => {
            category.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    }
    
    // Método público para reinicializar categorias (útil para carregamento dinâmico)
    reinitialize() {
        this.categories = [];
        this.initialized = false;
        this.init();
    }
}

// ===== UTILITÁRIOS ESPECÍFICOS =====
class CategoryUtils {
    static getVisibleCategories() {
        return Array.from(document.querySelectorAll('.modern-category:not([hidden])'));
    }
    
    static getCategoryByName(name) {
        return document.querySelector(`.modern-category .category-title[textContent="${name}"]`)?.closest('.modern-category');
    }
    
    static animateSuccessOnCategory(categoryElement) {
        const originalTransform = categoryElement.style.transform;
        
        categoryElement.style.transform = 'scale(1.05)';
        categoryElement.style.boxShadow = '0 0 20px rgba(0, 200, 81, 0.3)';
        
        setTimeout(() => {
            categoryElement.style.transform = originalTransform;
            categoryElement.style.boxShadow = '';
        }, 300);
    }
    
    static showCategoryLoadingState(categoryElement) {
        categoryElement.style.opacity = '0.7';
        categoryElement.style.pointerEvents = 'none';
        
        const icon = categoryElement.querySelector('.category-image i');
        if (icon) {
            icon.style.animation = 'spin 1s linear infinite';
        }
    }
    
    static hideCategoryLoadingState(categoryElement) {
        categoryElement.style.opacity = '1';
        categoryElement.style.pointerEvents = 'auto';
        
        const icon = categoryElement.querySelector('.category-image i');
        if (icon) {
            icon.style.animation = 'none';
        }
    }
}

// ===== INICIALIZAÇÃO =====
const categoryManager = new CategoryManager();

// Tornar disponível globalmente
window.CategoryManager = CategoryManager;
window.CategoryUtils = CategoryUtils;
window.categoryManager = categoryManager;

// ===== ANIMAÇÕES CSS ADICIONAIS =====
const additionalStyles = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .modern-category:focus {
        outline: 2px solid var(--primary-green);
        outline-offset: 2px;
    }
    
    .modern-category:focus-visible {
        outline: 2px solid var(--primary-green);
        outline-offset: 2px;
    }
    
    @media (prefers-reduced-motion: reduce) {
        .modern-category,
        .modern-category *,
        .product-count-badge {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
`;

// Adicionar estilos ao documento
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

console.log('🎨 Sistema de categorias aprimorado carregado!'); 