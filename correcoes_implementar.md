# 🛠️ Correções Prontas para Implementar

## 1. 🔒 Correção de Segurança - Remover Senha do Código

### Criar arquivo `.env` na raiz do projeto:
```env
# Produção
DB_HOST_PROD=localhost
DB_NAME_PROD=lollad10_catalogo2
DB_USER_PROD=lollad10_jhonatan
DB_PASS_PROD=jhonatan2727A@

# Desenvolvimento
DB_HOST_DEV=localhost
DB_NAME_DEV=catalogo_graos
DB_USER_DEV=root
DB_PASS_DEV=
```

### Adicionar ao `.gitignore`:
```gitignore
.env
.env.local
.env.production
```

### Atualizar `includes/db_connect.php`:
```php
<?php
// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Buffer de saída para evitar problemas de header
if (!ob_get_level()) {
    ob_start();
}

// Configurações de encoding
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Detectar ambiente
function isProduction() {
    $production_indicators = [
        isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'graosfoz.com.br') !== false,
        isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.com.br') !== false,
        isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost',
        isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost'
    ];
    
    foreach ($production_indicators as $indicator) {
        if ($indicator) return true;
    }
    return false;
}

$is_production = isProduction();

// Configurações de banco baseadas no ambiente
if ($is_production) {
    // PRODUÇÃO - usando variáveis de ambiente
    $host = $_ENV['DB_HOST_PROD'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME_PROD'] ?? '';
    $username = $_ENV['DB_USER_PROD'] ?? '';
    $password = $_ENV['DB_PASS_PROD'] ?? '';
    
    // Configurações para produção
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
} else {
    // DESENVOLVIMENTO - usando variáveis de ambiente
    $host = $_ENV['DB_HOST_DEV'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME_DEV'] ?? 'catalogo_graos';
    $username = $_ENV['DB_USER_DEV'] ?? 'root';
    $password = $_ENV['DB_PASS_DEV'] ?? '';
    
    // Configurações para desenvolvimento
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Resto do código permanece igual...
```

## 2. 🛡️ Implementar Proteção CSRF

### Criar `includes/csrf_protection.php`:
```php
<?php
/**
 * Sistema de proteção CSRF
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera ou retorna token CSRF
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gera campo HTML com token CSRF
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Valida token CSRF
 */
function validate_csrf($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Middleware para validar CSRF em requisições POST
 */
function csrf_middleware() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}
```

### Atualizar formulários - Exemplo em `checkout.php`:
```php
// No início do arquivo, após includes
require_once 'includes/csrf_protection.php';

// Validar CSRF em POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        die('Requisição inválida. Por favor, recarregue a página.');
    }
    // Resto do processamento...
}

// No formulário HTML
<form method="post" action="checkout.php" class="needs-validation" novalidate>
    <?php echo csrf_field(); ?>
    <!-- resto do formulário -->
</form>
```

## 3. 📱 Correções Mobile - CSS Global

### Criar `assets/css/mobile-fixes.css`:
```css
/* Prevenir zoom em inputs no iOS */
@media screen and (-webkit-min-device-pixel-ratio:0) { 
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="tel"],
    input[type="password"],
    input[type="date"],
    input[type="datetime-local"],
    textarea,
    select {
        font-size: 16px !important;
    }
}

/* Touch targets maiores (44x44px mínimo) */
.quantity-control button {
    min-width: 44px !important;
    min-height: 44px !important;
    margin: 0 4px;
    font-size: 18px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-agregar {
    min-height: 48px !important;
    font-size: 16px !important;
    font-weight: 600;
    padding: 12px 20px;
}

/* Melhorar espaçamento em mobile */
@media (max-width: 576px) {
    .product-card {
        margin-bottom: 12px;
    }
    
    .product-info {
        padding: 10px !important;
    }
    
    .product-title {
        font-size: 14px !important;
        height: 36px !important;
    }
    
    /* Prevenir overflow horizontal */
    body {
        overflow-x: hidden !important;
    }
    
    .container, .container-fluid {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
}

/* Fix para teclado virtual */
@media (max-height: 500px) {
    .footer {
        display: none;
    }
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* Desabilitar seleção em botões */
.btn, button {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

/* Loading state para botões */
.btn.loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}
```

### Incluir em todas as páginas:
```html
<!-- Adicionar após Bootstrap CSS -->
<link rel="stylesheet" href="assets/css/mobile-fixes.css">
```

## 4. 🚀 Remover Console.logs - Atualizar `assets/js/cart.js`:

### Criar wrapper de debug no início do arquivo:
```javascript
// Sistema de debug condicional
const DEBUG_MODE = window.location.hostname === 'localhost';

const debug = {
    log: (...args) => {
        if (DEBUG_MODE) {
            console.log(...args);
        }
    },
    error: (...args) => {
        if (DEBUG_MODE) {
            console.error(...args);
        }
    },
    warn: (...args) => {
        if (DEBUG_MODE) {
            console.warn(...args);
        }
    }
};

// Substituir todos os console.log por debug.log
// Exemplo:
// console.log('Cart.js inicializado'); 
// vira:
debug.log('Cart.js inicializado');
```

## 5. 🛒 Melhorar Sincronização do Carrinho

### Atualizar `assets/js/cart.js`:
```javascript
// Adicionar debounce para evitar múltiplas chamadas
let syncTimeout = null;
let pendingUpdates = [];

function debouncedCartUpdate(productId, quantity) {
    // Adicionar à fila de atualizações
    pendingUpdates.push({ productId, quantity });
    
    // Cancelar timeout anterior
    clearTimeout(syncTimeout);
    
    // Agendar nova sincronização
    syncTimeout = setTimeout(() => {
        processPendingUpdates();
    }, 500); // Aguarda 500ms de inatividade
}

function processPendingUpdates() {
    if (pendingUpdates.length === 0) return;
    
    // Processar todas as atualizações de uma vez
    const updates = [...pendingUpdates];
    pendingUpdates = [];
    
    // Enviar para o servidor
    fetch('includes/cart_batch_update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('[name="csrf_token"]')?.value || ''
        },
        body: JSON.stringify({ updates })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartUI(data);
        }
    })
    .catch(error => {
        debug.error('Erro ao sincronizar carrinho:', error);
    });
}
```

## 6. ⚡ Otimização de Imagens com Lazy Loading

### Melhorar sistema atual em `index.php`:
```javascript
// Sistema de lazy loading otimizado
class ImageLazyLoader {
    constructor() {
        this.imageObserver = null;
        this.init();
    }
    
    init() {
        // Configuração otimizada do Intersection Observer
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                }
            });
        }, {
            // Carregar imagens 100px antes de aparecerem
            rootMargin: '100px 0px',
            threshold: 0.01
        });
        
        // Observar todas as imagens lazy
        this.observeImages();
    }
    
    observeImages() {
        const lazyImages = document.querySelectorAll('.lazy-bg:not(.loaded)');
        lazyImages.forEach(img => this.imageObserver.observe(img));
    }
    
    loadImage(element) {
        const imageUrl = element.dataset.bg;
        if (!imageUrl) return;
        
        // Criar nova imagem para pré-carregar
        const img = new Image();
        img.onload = () => {
            element.style.backgroundImage = `url('${imageUrl}')`;
            element.classList.add('loaded');
            this.imageObserver.unobserve(element);
        };
        img.onerror = () => {
            // Usar imagem padrão em caso de erro
            element.style.backgroundImage = `url('assets/images/no-image.png')`;
            element.classList.add('loaded', 'error');
            this.imageObserver.unobserve(element);
        };
        img.src = imageUrl;
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new ImageLazyLoader();
});
```

## 7. 📊 Sistema de Monitoramento de Erros

### Criar `includes/error_handler.php`:
```php
<?php
/**
 * Sistema centralizado de tratamento de erros
 */

class ErrorHandler {
    private static $logFile = 'logs/app_errors.log';
    
    /**
     * Registra erro no log
     */
    public static function log($message, $level = 'ERROR', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logMessage = "[{$timestamp}] [{$level}] {$message}";
        if ($contextStr) {
            $logMessage .= " | Context: {$contextStr}";
        }
        $logMessage .= PHP_EOL;
        
        // Criar diretório se não existir
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Escrever no arquivo
        error_log($logMessage, 3, self::$logFile);
        
        // Em desenvolvimento, também mostrar no console
        if (!isProduction()) {
            error_log($logMessage);
        }
    }
    
    /**
     * Handler customizado para erros PHP
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $context = [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ];
        
        self::log($errstr, 'ERROR', $context);
        
        // Não impedir o handler padrão do PHP
        return false;
    }
    
    /**
     * Handler para exceções não capturadas
     */
    public static function handleException($exception) {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        self::log($exception->getMessage(), 'EXCEPTION', $context);
    }
    
    /**
     * Registra os handlers
     */
    public static function register() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }
}

// Registrar handlers
ErrorHandler::register();
```

## 8. 🔄 Script de Migração Segura

### Criar `migrate_to_env.php`:
```php
<?php
/**
 * Script para migrar configurações para variáveis de ambiente
 * EXECUTAR UMA VEZ E DEPOIS DELETAR
 */

// Verificar se já existe .env
if (file_exists('.env')) {
    die("Arquivo .env já existe! Migração cancelada por segurança.\n");
}

// Criar conteúdo do .env
$envContent = <<<ENV
# Configurações de Produção
DB_HOST_PROD=localhost
DB_NAME_PROD=lollad10_catalogo2
DB_USER_PROD=lollad10_jhonatan
DB_PASS_PROD=jhonatan2727A@

# Configurações de Desenvolvimento
DB_HOST_DEV=localhost
DB_NAME_DEV=catalogo_graos
DB_USER_DEV=root
DB_PASS_DEV=

# Outras configurações
APP_ENV=production
APP_DEBUG=false
ENV;

// Criar arquivo .env
if (file_put_contents('.env', $envContent)) {
    echo "✅ Arquivo .env criado com sucesso!\n";
    
    // Definir permissões seguras
    chmod('.env', 0600);
    echo "✅ Permissões configuradas (apenas leitura para o proprietário)\n";
    
    // Adicionar ao .gitignore se não existir
    $gitignore = file_get_contents('.gitignore');
    if (strpos($gitignore, '.env') === false) {
        file_put_contents('.gitignore', "\n# Variáveis de ambiente\n.env\n.env.*\n", FILE_APPEND);
        echo "✅ .env adicionado ao .gitignore\n";
    }
    
    echo "\n⚠️  IMPORTANTE:\n";
    echo "1. Teste a conexão com o banco de dados\n";
    echo "2. Se tudo estiver funcionando, delete este arquivo\n";
    echo "3. NUNCA commite o arquivo .env no git!\n";
} else {
    echo "❌ Erro ao criar arquivo .env\n";
}
```

## 🚀 Ordem de Implementação

1. **Imediato (Crítico)**:
   - Executar `migrate_to_env.php`
   - Atualizar `db_connect.php`
   - Adicionar proteção CSRF

2. **Hoje**:
   - Aplicar mobile-fixes.css
   - Remover console.logs
   - Implementar error handler

3. **Esta semana**:
   - Melhorar lazy loading
   - Otimizar sincronização do carrinho
   - Testes em dispositivos reais

Estas correções resolverão os problemas mais críticos do sistema!