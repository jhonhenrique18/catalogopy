<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Painel Administrativo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS personalizado -->
    <style>
        :root {
            --color-primary: #27AE60;
            --color-primary-dark: #219653;
            --color-primary-light: #6FCF97;
            --color-secondary: #3498DB;
            --color-danger: #E74C3C;
            --color-warning: #F39C12;
            --color-info: #3498DB;
            --color-success: #27AE60;
            --color-gray-light: #F5F5F5;
            --color-gray-medium: #E0E0E0;
            --color-gray-dark: #333333;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: var(--color-gray-dark);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            max-height: 50px;
            margin-bottom: 10px;
            width: auto;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .sidebar-logo-fallback {
            background-color: var(--color-primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            margin: 0 auto 10px auto;
        }
        
        .sidebar-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        
        .sidebar-nav {
            padding: 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-link.active {
            background-color: var(--color-primary);
            color: white;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        /* Navbar */
        .admin-navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 10px 20px;
        }
        
        .navbar-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        
        .admin-dropdown {
            margin-left: auto;
        }
        
        /* Dashboard cards */
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .dashboard-card-body {
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .dashboard-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-right: 20px;
        }
        
        .dashboard-card-content {
            flex-grow: 1;
        }
        
        .dashboard-card-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }
        
        .dashboard-card-text {
            margin: 0;
            color: var(--color-gray-dark);
            font-size: 14px;
        }
        
        .dashboard-card-link {
            display: block;
            padding: 10px 20px;
            text-align: center;
            background-color: rgba(0,0,0,0.05);
            color: var(--color-gray-dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .dashboard-card-link:hover {
            background-color: rgba(0,0,0,0.1);
            color: var(--color-gray-dark);
        }
        
        /* Page title */
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            color: var(--color-gray-dark);
        }
        
        /* Data tables */
        .datatable-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .datatable-search {
            width: 250px;
        }
        
        .datatable-header {
            background-color: var(--color-gray-light);
            font-weight: 600;
        }
        
        .datatable-actions {
            display: flex;
            gap: 5px;
        }
        
        /* Forms */
        .form-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--color-gray-light);
        }
        
        .form-label {
            font-weight: 600;
        }
        
        .image-preview {
            width: 150px;
            height: 150px;
            border: 1px solid var(--color-gray-medium);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar-menu-btn {
                display: block;
            }
        }
        
        /* Previne zoom em campos de formulário no iOS */
        @media screen and (-webkit-min-device-pixel-ratio:0) { 
            select,
            textarea,
            input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <!-- Sistema de logo seguro para admin -->
            <div id="admin-logo-container">
                <div class="sidebar-logo-fallback">
                    <i class="fas fa-store"></i>
                </div>
            </div>
            <h1 class="sidebar-title">Painel Administrativo</h1>
        </div>
        
        <ul class="sidebar-nav">
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span>
                    Dashboard
                </a>
            </li>
            <li class="sidebar-item">
                <a href="produtos.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'produtos.php' || basename($_SERVER['PHP_SELF']) === 'produto_adicionar.php' || basename($_SERVER['PHP_SELF']) === 'produto_editar.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                    Produtos
                </a>
            </li>
            <li class="sidebar-item">
                <a href="categorias.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'categorias.php' || basename($_SERVER['PHP_SELF']) === 'categoria_adicionar.php' || basename($_SERVER['PHP_SELF']) === 'categoria_editar.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-tags"></i></span>
                    Categorias
                </a>
            </li>
            <li class="sidebar-item">
                <a href="cotacao.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'cotacao.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-exchange-alt"></i></span>
                    Cotação Cambial
                </a>
            </li>
            <li class="sidebar-item">
                <a href="pedidos.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'pedidos.php' || basename($_SERVER['PHP_SELF']) === 'pedido_detalhes.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-shopping-cart"></i></span>
                    Gestão de Pedidos
                </a>
            </li>
            <li class="sidebar-item">
                <a href="popup_manager.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'popup_manager.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-bullhorn"></i></span>
                    Pop-ups Promocionais
                </a>
            </li>
            <li class="sidebar-item">
                <a href="configuracoes.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'configuracoes.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                    Configurações
                </a>
            </li>
            <li class="sidebar-item">
                <a href="perfil.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fas fa-user"></i></span>
                    Meu Perfil
                </a>
            </li>
            <li class="sidebar-item">
                <a href="logout.php" class="sidebar-link">
                    <span class="sidebar-icon"><i class="fas fa-sign-out-alt"></i></span>
                    Sair
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Conteúdo principal -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="admin-navbar">
            <button type="button" class="navbar-menu-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="admin-dropdown dropdown">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Administrador'; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i> Meu Perfil</a></li>
                    <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-2"></i> Ver Loja</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                </ul>
            </div>
        </nav>
