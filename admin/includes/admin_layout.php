<?php
// Obter configuraÃ§Ãµes da loja se nÃ£o foram carregadas
if (!isset($store)) {
    require_once '../includes/db_connect.php';
    require_once '../includes/functions.php';
    require_once '../includes/exchange_functions.php';
    $store_query = "SELECT * FROM store_settings WHERE id = 1";
    $store_result = $conn->query($store_query);
    $store = $store_result->fetch_assoc();
}

// Detectar pÃ¡gina atual para menu ativo
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = 'Painel Administrativo';

// Definir tÃ­tulos das pÃ¡ginas
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'produtos.php' => 'GestÃ£o de Produtos',
    'produto_adicionar.php' => 'Adicionar Produto',
    'produto_editar.php' => 'Editar Produto',
    'categorias.php' => 'GestÃ£o de Categorias',
    'categoria_adicionar.php' => 'Adicionar Categoria',
    'categoria_editar.php' => 'Editar Categoria',
    'pedidos.php' => 'GestÃ£o de Pedidos',
    'pedidos_melhorado.php' => 'GestÃ£o AvanÃ§ada de Pedidos',
    'pedido_detalhes.php' => 'Detalhes do Pedido',
    'cotacao.php' => 'GestÃ£o de CotaÃ§Ã£o',
    'configuracoes.php' => 'ConfiguraÃ§Ãµes da Loja',
    'perfil.php' => 'Perfil do Administrador',
    'popup_manager.php' => 'GestÃ£o de Pop-ups',
    'barra_rotativa.php' => 'Barra Rotativa',
    'gestao_quantidades_minimas.php' => 'GestÃ£o de Quantidades MÃ­nimas'
];

if (isset($page_titles[$current_page])) {
    $page_title = $page_titles[$current_page];
}

// Se uma pÃ¡gina especÃ­fica definir $custom_page_title, usar esse
if (isset($custom_page_title)) {
    $page_title = $custom_page_title;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($store['store_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --admin-primary: #2C3E50;
            --admin-secondary: #34495E;
            --admin-accent: #3498DB;
            --admin-success: #27AE60;
            --admin-warning: #F39C12;
            --admin-danger: #E74C3C;
            --admin-info: #17A2B8;
            --admin-light: #F8F9FA;
            --admin-dark: #212529;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--admin-light);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* SIDEBAR */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            background: rgba(0,0,0,0.1);
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--admin-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            color: white;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .brand-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: white;
        }
        
        .brand-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            margin: 5px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* NAVIGATION */
        .admin-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-section-title {
            padding: 0 20px 10px 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .nav-item {
            margin-bottom: 2px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--admin-accent);
        }
        
        .nav-link.active {
            background: rgba(52, 152, 219, 0.15);
            color: white;
            border-left-color: var(--admin-accent);
        }
        
        .nav-icon {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .nav-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* MAIN CONTENT */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* HEADER BAR */
        .admin-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: between;
            padding: 15px 30px;
        }
        
        .page-header {
            flex-grow: 1;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--admin-dark);
            margin: 0;
        }
        
        .page-breadcrumb {
            font-size: 14px;
            color: #6c757d;
            margin: 5px 0 0 0;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--admin-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            background: var(--admin-primary);
        }
        
        /* CONTENT AREA */
        .admin-content {
            flex-grow: 1;
            padding: 30px;
        }
        
        /* CARDS */
        .admin-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .admin-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--admin-dark);
            margin: 0;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* BUTTONS */
        .btn-admin-primary {
            background: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
        }
        
        .btn-admin-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
    
    <?php if (isset($additional_css)): ?>
        <style><?php echo $additional_css; ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- SIDEBAR -->
    <nav class="admin-sidebar">
        <!-- BRAND -->
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-store"></i>
            </div>
            <h1 class="brand-name"><?php echo htmlspecialchars($store['store_name']); ?></h1>
            <p class="brand-subtitle">Painel Administrativo</p>
        </div>
        
        <!-- NAVIGATION -->
        <div class="admin-nav">
            <!-- PRINCIPAL -->
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </div>
            </div>
            
            <!-- PRODUTOS -->
            <div class="nav-section">
                <div class="nav-section-title">CatÃ¡logo</div>
                <div class="nav-item">
                    <a href="produtos.php" class="nav-link <?php echo in_array($current_page, ['produtos.php', 'produto_adicionar.php', 'produto_editar.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-box"></i></span>
                        <span class="nav-text">Produtos</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="categorias.php" class="nav-link <?php echo in_array($current_page, ['categorias.php', 'categoria_adicionar.php', 'categoria_editar.php']) ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-tags"></i></span>
                        <span class="nav-text">Categorias</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="gestao_quantidades_minimas.php" class="nav-link <?php echo $current_page === 'gestao_quantidades_minimas.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-sort-numeric-up"></i></span>
                        <span class="nav-text">Quantidades MÃ­nimas</span>
                    </a>
                </div>
            </div>
            
            <!-- VENDAS -->
            <div class="nav-section">
                <div class="nav-section-title">Vendas</div>
                <div class="nav-item">
                    <a href="pedidos.php" class="nav-link <?php echo $current_page === 'pedidos.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span>
                        <span class="nav-text">GestÃ£o de Pedidos</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="cotacao.php" class="nav-link <?php echo $current_page === 'cotacao.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-exchange-alt"></i></span>
                        <span class="nav-text">CotaÃ§Ã£o Cambial</span>
                    </a>
                </div>
            </div>
            
            <!-- MARKETING -->
            <div class="nav-section">
                <div class="nav-section-title">Marketing</div>
                <div class="nav-item">
                    <a href="popup_manager.php" class="nav-link <?php echo $current_page === 'popup_manager.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-bullhorn"></i></span>
                        <span class="nav-text">Pop-ups & PromoÃ§Ãµes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="barra_rotativa.php" class="nav-link <?php echo $current_page === 'barra_rotativa.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-scroll"></i></span>
                        <span class="nav-text">Barra Rotativa</span>
                    </a>
                </div>
            </div>
            
            <!-- SISTEMA -->
            <div class="nav-section">
                <div class="nav-section-title">Sistema</div>
                <div class="nav-item">
                    <a href="configuracoes.php" class="nav-link <?php echo $current_page === 'configuracoes.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-cog"></i></span>
                        <span class="nav-text">ConfiguraÃ§Ãµes</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="perfil.php" class="nav-link <?php echo $current_page === 'perfil.php' ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-user"></i></span>
                        <span class="nav-text">Meu Perfil</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link" onclick="return confirm('Tem certeza que deseja sair?')">
                        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span class="nav-text">Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- MAIN CONTENT -->
    <main class="admin-main">
        <!-- HEADER BAR -->
        <header class="admin-header">
            <div class="header-content">
                <div class="page-header">
                    <h1 class="page-title"><?php echo $page_title; ?></h1>
                    <div class="page-breadcrumb">
                        <i class="fas fa-home"></i> 
                        <a href="dashboard.php">Dashboard</a>
                        <?php if ($current_page !== 'dashboard.php'): ?>
                            <i class="fas fa-chevron-right mx-2"></i>
                            <span><?php echo $page_title; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()" title="Atualizar pÃ¡gina">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="../index.php" class="btn btn-outline-primary btn-sm" target="_blank" title="Ver loja">
                        <i class="fas fa-external-link-alt"></i> Ver Loja
                    </a>
                    <div class="user-menu">
                        <div class="user-avatar" title="<?php echo $_SESSION['admin_user'] ?? 'Admin'; ?>">
                            <?php echo strtoupper(substr($_SESSION['admin_user'] ?? 'A', 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- CONTENT AREA -->
        <div class="admin-content">
            <?php if (isset($content_start)): ?>
                <?php echo $content_start; ?>
            <?php endif; ?> 
