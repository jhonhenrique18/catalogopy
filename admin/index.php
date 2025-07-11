<?php
// Iniciar sessão
session_start();

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Inicializar variáveis
$email = '';
$error = '';
$success = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validação básica
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Consultar usuário no banco de dados
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verificar senha
            if (password_verify($password, $user['password'])) {
                // Login bem-sucedido
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Redirecionar para o dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Senha incorreta. Tente novamente.';
            }
        } else {
            $error = 'Usuário não encontrado. Verifique o email informado.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Administrativo</title>
    
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
            --color-gray-light: #F5F5F5;
            --color-gray-medium: #E0E0E0;
            --color-gray-dark: #333333;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            max-height: 80px;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            color: var(--color-gray-dark);
        }
        
        .login-form .form-control {
            height: 46px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid var(--color-gray-medium);
        }
        
        .login-form .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(39, 174, 96, 0.25);
        }
        
        .login-form .btn-login {
            background-color: var(--color-primary);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            height: 46px;
            width: 100%;
        }
        
        .login-form .btn-login:hover {
            background-color: var(--color-primary-dark);
        }
        
        .login-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: var(--color-gray-dark);
        }
        
        .login-footer a {
            color: var(--color-primary);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
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
    <div class="login-container">
        <div class="login-logo">
            <img src="../assets/images/logo.png" alt="Logo" onerror="this.src='../assets/images/default-logo.png';">
        </div>
        
        <h1 class="login-title">Acesso Administrativo</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="index.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-login">Entrar</button>
        </form>
        
        <div class="login-footer">
            <a href="../index.php">Voltar para a loja</a>
        </div>
    </div>
    
    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>