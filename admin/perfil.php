<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Obter informações do usuário logado
$user_id = $_SESSION['admin_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Variáveis para armazenar os valores do formulário
$name = $user['name'];
$email = $user['email'];

// Array para armazenar erros e sucesso
$errors = [];
$success = '';

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    // Atualizar informações do perfil
    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        // Validações
        if (empty($name)) {
            $errors['name'] = 'O nome é obrigatório';
        }
        
        if (empty($email)) {
            $errors['email'] = 'O email é obrigatório';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        
        // Verificar se o email já está em uso por outro usuário
        if ($email !== $user['email']) {
            $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors['email'] = 'Este email já está em uso por outro usuário';
            }
        }
        
        // Se não houver erros, atualizar usuário no banco de dados
        if (empty($errors)) {
            $query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $email, $user_id);
            
            if ($stmt->execute()) {
                // Atualizar dados da sessão
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_email'] = $email;
                
                $success = 'Perfil atualizado com sucesso!';
            } else {
                $errors['db'] = 'Erro ao atualizar perfil: ' . $conn->error;
            }
        }
    }
    
    // Alterar senha
    else if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validações
        if (empty($current_password)) {
            $errors['current_password'] = 'A senha atual é obrigatória';
        }
        
        if (empty($new_password)) {
            $errors['new_password'] = 'A nova senha é obrigatória';
        } else if (strlen($new_password) < 6) {
            $errors['new_password'] = 'A nova senha deve ter pelo menos 6 caracteres';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'As senhas não coincidem';
        }
        
        // Verificar se a senha atual está correta
        if (!empty($current_password) && !password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Senha atual incorreta';
        }
        
        // Se não houver erros, atualizar senha no banco de dados
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Senha alterada com sucesso!';
            } else {
                $errors['db'] = 'Erro ao alterar senha: ' . $conn->error;
            }
        }
    }
}

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Meu Perfil</h1>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errors['db']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Informações do Perfil -->
            <div class="form-section">
                <h2 class="form-section-title">Informações do Perfil</h2>
                
                <form action="perfil.php" method="post">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Função</label>
                        <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Atualizar Perfil
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Alterar Senha -->
            <div class="form-section">
                <h2 class="form-section-title">Alterar Senha</h2>
                
                <form action="perfil.php" method="post">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha Atual *</label>
                        <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password" required>
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['current_password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha *</label>
                        <input type="password" class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password" required>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['new_password']; ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">
                            A senha deve ter pelo menos 6 caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha *</label>
                        <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['confirm_password']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i> Alterar Senha
                    </button>
                </form>
            </div>
            
            <!-- Segurança da Conta -->
            <div class="form-section">
                <h2 class="form-section-title">Segurança da Conta</h2>
                
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-shield-alt me-2"></i> Dicas de Segurança:</h5>
                    <hr>
                    <ul class="mb-0">
                        <li>Use senhas fortes com pelo menos 8 caracteres</li>
                        <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
                        <li>Evite usar a mesma senha em vários sites</li>
                        <li>Nunca compartilhe suas credenciais com outras pessoas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>