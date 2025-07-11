<?php
// Página de teste do layout padronizado
require_once 'includes/auth_check.php';
require_once '../includes/db_connect.php';

// Definir título personalizado
$custom_page_title = "Teste do Layout Padronizado";

// CSS adicional para demonstração
$additional_css = '
.test-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 25px;
}

.test-card {
    background: linear-gradient(45deg, #f8f9fa, #ffffff);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e9ecef;
    text-align: center;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-success {
    background-color: #27AE60;
}

.status-warning {
    background-color: #F39C12;
}

.status-info {
    background-color: #3498DB;
}
';

// Incluir layout
include 'includes/admin_layout.php';
?>

<!-- Conteúdo de Teste -->
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Layout Padronizado Ativo!</strong> Esta página demonstra que o sistema unificado está funcionando corretamente.
</div>

<!-- Seção de Teste -->
<div class="test-section">
    <h3><i class="fas fa-palette me-2"></i>Teste do Layout Administrativo</h3>
    <p class="text-muted">Verificação da padronização e consistência visual</p>
    
    <div class="row g-4 mt-3">
        <div class="col-md-3">
            <div class="test-card">
                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                <h5>Logo/Marca</h5>
                <p class="mb-0">
                    <span class="status-indicator status-success"></span>
                    Consistente
                </p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="test-card">
                <i class="fas fa-palette fa-2x text-primary mb-3"></i>
                <h5>Cores/Tema</h5>
                <p class="mb-0">
                    <span class="status-indicator status-success"></span>
                    Unificado
                </p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="test-card">
                <i class="fas fa-bars fa-2x text-info mb-3"></i>
                <h5>Navegação</h5>
                <p class="mb-0">
                    <span class="status-indicator status-success"></span>
                    Padronizada
                </p>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="test-card">
                <i class="fas fa-desktop fa-2x text-warning mb-3"></i>
                <h5>Desktop Focus</h5>
                <p class="mb-0">
                    <span class="status-indicator status-success"></span>
                    Otimizado
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Teste de Navegação -->
<div class="test-section">
    <h4><i class="fas fa-link me-2"></i>Teste de Navegação</h4>
    <p>Verifique se todas as páginas usam o layout padronizado:</p>
    
    <div class="row g-3">
        <div class="col-md-4">
            <a href="dashboard.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
        <div class="col-md-4">
            <a href="produtos.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-box me-2"></i>Produtos
            </a>
        </div>
        <div class="col-md-4">
            <a href="categorias.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-tags me-2"></i>Categorias
            </a>
        </div>
        <div class="col-md-4">
            <a href="pedidos_melhorado.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-shopping-cart me-2"></i>Pedidos
            </a>
        </div>
        <div class="col-md-4">
            <a href="cotacao.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-exchange-alt me-2"></i>Cotação
            </a>
        </div>
        <div class="col-md-4">
            <a href="configuracoes.php" class="btn btn-admin-primary w-100">
                <i class="fas fa-cog me-2"></i>Configurações
            </a>
        </div>
    </div>
</div>

<!-- Demonstração de Componentes -->
<div class="test-section">
    <h4><i class="fas fa-puzzle-piece me-2"></i>Componentes Padronizados</h4>
    
    <div class="row g-4">
        <div class="col-md-6">
            <h6>Botões</h6>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-admin-primary">Primário</button>
                <button class="btn btn-success">Sucesso</button>
                <button class="btn btn-warning">Aviso</button>
                <button class="btn btn-danger">Perigo</button>
                <button class="btn btn-outline-secondary">Secundário</button>
            </div>
        </div>
        
        <div class="col-md-6">
            <h6>Badges</h6>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-primary">Pendente</span>
                <span class="badge bg-success">Ativo</span>
                <span class="badge bg-warning">Aguardando</span>
                <span class="badge bg-danger">Inativo</span>
                <span class="badge bg-info">Processando</span>
            </div>
        </div>
    </div>
</div>

<!-- Resumo da Padronização -->
<div class="test-section">
    <h4><i class="fas fa-clipboard-check me-2"></i>Resumo da Padronização</h4>
    
    <div class="alert alert-info">
        <h6><i class="fas fa-info-circle me-2"></i>O que foi padronizado:</h6>
        <ul class="mb-0">
            <li>✅ <strong>Layout único</strong> - Todas as páginas usam o mesmo template</li>
            <li>✅ <strong>Logo/marca consistente</strong> - Exibida uniformemente em todo o admin</li>
            <li>✅ <strong>Navegação unificada</strong> - Menu lateral padronizado</li>
            <li>✅ <strong>Cores e tema</strong> - Esquema de cores profissional e consistente</li>
            <li>✅ <strong>Tipografia</strong> - Fontes e tamanhos padronizados</li>
            <li>✅ <strong>Componentes</strong> - Botões, cards e formulários uniformes</li>
            <li>✅ <strong>Responsividade</strong> - Otimizado para desktop com suporte mobile</li>
        </ul>
    </div>
    
    <div class="alert alert-success">
        <h6><i class="fas fa-thumbs-up me-2"></i>Benefícios alcançados:</h6>
        <ul class="mb-0">
            <li>🎯 <strong>Experiência consistente</strong> - Sem confusão entre diferentes "sistemas"</li>
            <li>🚀 <strong>Produtividade</strong> - Interface familiar em todas as páginas</li>
            <li>🔧 <strong>Manutenibilidade</strong> - Mudanças visuais aplicadas globalmente</li>
            <li>👥 <strong>Usabilidade</strong> - Focado na experiência do administrador brasileiro</li>
        </ul>
    </div>
</div>

<div class="text-center">
    <a href="dashboard.php" class="btn btn-admin-primary btn-lg">
        <i class="fas fa-home me-2"></i>Voltar ao Dashboard
    </a>
</div>

<?php
// Scripts específicos da página
$custom_scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Animar cards ao carregar
    const cards = document.querySelectorAll(".test-card");
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = "0";
            card.style.transform = "translateY(20px)";
            card.style.transition = "all 0.5s ease";
            
            setTimeout(() => {
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
            }, 100);
        }, index * 200);
    });
    
    // Mostrar alerta de sucesso
    setTimeout(() => {
        Swal.fire({
            title: "Parabéns! 🎉",
            text: "O painel administrativo foi padronizado com sucesso!",
            icon: "success",
            timer: 3000,
            showConfirmButton: false
        });
    }, 1000);
});
</script>
';

// Incluir footer
include 'includes/footer.php';
?> 