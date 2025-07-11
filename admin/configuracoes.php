<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se há um parâmetro de sucesso
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 'logo') {
    $success_message = 'Logo atualizado com sucesso!';
}

// Verificar se a tabela store_settings existe
$check_table = $conn->query("SHOW TABLES LIKE 'store_settings'");
if ($check_table->num_rows === 0) {
    // Tabela não existe, criar
    $create_table = "CREATE TABLE `store_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `store_name` varchar(255) NOT NULL DEFAULT 'Productos Naturales Paraguay',
        `store_description` text DEFAULT NULL,
        `whatsapp_number` varchar(50) DEFAULT '595991234567',
        `shipping_rate` decimal(10,2) DEFAULT 1500.00,
        `address` text DEFAULT NULL,
        `business_hours` text DEFAULT NULL,
        `logo_url` varchar(255) DEFAULT NULL,
        `social_facebook` varchar(255) DEFAULT NULL,
        `social_instagram` varchar(255) DEFAULT NULL,
        `social_twitter` varchar(255) DEFAULT NULL,
        `show_retail_price` tinyint(1) DEFAULT 1,
        `show_mayoreo_priority` tinyint(1) DEFAULT 1,
        `use_consultar_precio` tinyint(1) DEFAULT 0,
        `consultar_precio_threshold` decimal(10,2) DEFAULT 100000.00,
        `currency_symbol` varchar(10) DEFAULT 'Gs.',
        `weight_unit` varchar(10) DEFAULT 'kg',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($create_table);
    
    // Inserir registro padrão
    $conn->query("INSERT INTO `store_settings` (id, store_name, store_description, whatsapp_number, shipping_rate) 
                 VALUES (1, 'Productos Naturales Paraguay', 'Catálogo de productos naturales y orgánicos', '595991234567', 1500.00)");
}

// Definir valores padrão
$store_name = 'Productos Naturales Paraguay';
$store_description = '';
$whatsapp_number = '595991234567';
$shipping_rate = 1500;
$address = '';
$business_hours = '';
$social_facebook = '';
$social_instagram = '';
$social_twitter = '';
$current_logo = '';

// Novos campos de controle global
$enable_shipping = 1;
$shipping_control_text = 'Frete calculado automaticamente';
$enable_global_minimums = 1;
$minimum_explanation_text = 'Vendemos somente no mínimo especificado';

// Obter configurações existentes
$query = "SELECT * FROM store_settings WHERE id = 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    
    // Atribuir valores existentes, se houverem
    if (isset($settings['store_name'])) $store_name = $settings['store_name'];
    if (isset($settings['store_description'])) $store_description = $settings['store_description'];
    if (isset($settings['whatsapp_number'])) $whatsapp_number = $settings['whatsapp_number'];
    if (isset($settings['shipping_rate'])) $shipping_rate = floatval($settings['shipping_rate']);
    if (isset($settings['address'])) $address = $settings['address'];
    if (isset($settings['business_hours'])) $business_hours = $settings['business_hours'];
    if (isset($settings['logo_url'])) $current_logo = $settings['logo_url'];
    if (isset($settings['social_facebook'])) $social_facebook = $settings['social_facebook'];
    if (isset($settings['social_instagram'])) $social_instagram = $settings['social_instagram'];
    if (isset($settings['social_twitter'])) $social_twitter = $settings['social_twitter'];
    
    // Carregar novos campos de controle global
    if (isset($settings['enable_shipping'])) $enable_shipping = $settings['enable_shipping'];
    if (isset($settings['shipping_control_text'])) $shipping_control_text = $settings['shipping_control_text'];
    if (isset($settings['enable_global_minimums'])) $enable_global_minimums = $settings['enable_global_minimums'];
    if (isset($settings['minimum_explanation_text'])) $minimum_explanation_text = $settings['minimum_explanation_text'];
}

// Array para armazenar erros
$errors = [];
$success = false;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se estamos apenas fazendo upload do logo
    $is_logo_only_update = isset($_POST['logo_only_update']) || (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0 && !isset($_POST['address']));
    
    // Obter dados do formulário com verificação para evitar avisos de índices indefinidos
    $store_name = isset($_POST['store_name']) ? trim($_POST['store_name']) : '';
    $store_description = isset($_POST['store_description']) ? trim($_POST['store_description']) : '';
    $whatsapp_number = isset($_POST['whatsapp_number']) ? trim($_POST['whatsapp_number']) : '';
    $shipping_rate = isset($_POST['shipping_rate']) ? str_replace(['.', ','], ['', '.'], $_POST['shipping_rate']) : 0;
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $business_hours = isset($_POST['business_hours']) ? trim($_POST['business_hours']) : '';
    $social_facebook = isset($_POST['social_facebook']) ? trim($_POST['social_facebook']) : '';
    $social_instagram = isset($_POST['social_instagram']) ? trim($_POST['social_instagram']) : '';
    $social_twitter = isset($_POST['social_twitter']) ? trim($_POST['social_twitter']) : '';
    
    // Obter novos campos de controle global
    $enable_shipping = isset($_POST['enable_shipping']) ? 1 : 0;
    $shipping_control_text = isset($_POST['shipping_control_text']) ? trim($_POST['shipping_control_text']) : 'Frete calculado automaticamente';
    $enable_global_minimums = isset($_POST['enable_global_minimums']) ? 1 : 0;
    $minimum_explanation_text = isset($_POST['minimum_explanation_text']) ? trim($_POST['minimum_explanation_text']) : 'Vendemos somente no mínimo especificado';
    
    // Validações - pular algumas validações se for apenas atualização de logo
    if (!$is_logo_only_update) {
        if (empty($store_name)) {
            $errors['store_name'] = 'O nome da loja é obrigatório';
        }
        
        if (empty($whatsapp_number)) {
            $errors['whatsapp_number'] = 'O número de WhatsApp é obrigatório';
        }
        
        if (!is_numeric($shipping_rate) || $shipping_rate <= 0) {
            $errors['shipping_rate'] = 'A taxa de frete deve ser um valor numérico positivo';
        }
    }
    
    // Processar upload de logo, se enviado
    $logo_url = $current_logo;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors['logo'] = 'Apenas imagens JPG, PNG ou GIF são permitidas';
        } else if ($_FILES['logo']['size'] > $max_size) {
            $errors['logo'] = 'A imagem deve ter no máximo 2MB';
        } else {
            // Criar diretórios de upload se não existirem
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Certificar-se de que o diretório uploads/logos existe
            $logos_dir = '../uploads/logos/';
            if (!file_exists($logos_dir)) {
                mkdir($logos_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = 'logo_' . uniqid() . '.' . $file_extension;
            $upload_path = $logos_dir . $file_name;
            
            // Mover o arquivo para o diretório de upload
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_url = 'uploads/logos/' . $file_name;
                
                // Remover logo anterior, se existir
                if (!empty($current_logo) && file_exists('../' . $current_logo)) {
                    unlink('../' . $current_logo);
                }
            } else {
                $errors['logo'] = 'Erro ao fazer upload do logo: ' . $_FILES['logo']['error'];
            }
        }
    }
    
    // Se não houver erros, atualizar configurações no banco de dados
    if (empty($errors)) {
        if ($is_logo_only_update) {
            // Se for apenas atualização de logo, atualizar apenas o campo logo_url
            $query = "UPDATE store_settings SET logo_url = ? WHERE id = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $logo_url);
            
            if ($stmt->execute()) {
                $success = true;
                $current_logo = $logo_url; // Atualizar variável local para exibição
                
                // Redirecionamento para evitar reenvio do formulário
                header("Location: configuracoes.php?success=logo");
                exit;
            } else {
                $errors['db'] = 'Erro ao atualizar logo: ' . $conn->error;
            }
        } else {
            // Atualização completa de todos os campos
            $query = "UPDATE store_settings SET 
                     store_name = ?, 
                     store_description = ?, 
                     whatsapp_number = ?, 
                     shipping_rate = ?, 
                     address = ?, 
                     business_hours = ?, 
                     logo_url = ?, 
                     social_facebook = ?, 
                     social_instagram = ?, 
                     social_twitter = ?,
                     enable_shipping = ?,
                     shipping_control_text = ?,
                     enable_global_minimums = ?,
                     minimum_explanation_text = ?
                     WHERE id = 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssdssssssisis", 
                             $store_name, 
                             $store_description, 
                             $whatsapp_number, 
                             $shipping_rate, 
                             $address, 
                             $business_hours, 
                             $logo_url, 
                             $social_facebook, 
                             $social_instagram, 
                             $social_twitter,
                             $enable_shipping,
                             $shipping_control_text,
                             $enable_global_minimums,
                             $minimum_explanation_text);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Atualizar variáveis locais
                $current_logo = $logo_url;
            } else {
                $errors['db'] = 'Erro ao atualizar configurações: ' . $conn->error;
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
        <h1 class="page-title">Configurações da Loja</h1>
    </div>
    
    <?php if (isset($errors['db'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errors['db']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Configurações atualizadas com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <form action="configuracoes.php" method="post" enctype="multipart/form-data">
                <!-- Informações Gerais -->
                <div class="form-section mb-4">
                    <h2 class="form-section-title">Informações Gerais</h2>
                    
                    <div class="mb-3">
                        <label for="store_name" class="form-label">Nome da Loja *</label>
                        <input type="text" class="form-control <?php echo isset($errors['store_name']) ? 'is-invalid' : ''; ?>" id="store_name" name="store_name" value="<?php echo htmlspecialchars($store_name); ?>" required>
                        <?php if (isset($errors['store_name'])): ?>
                            <div class="invalid-feedback">
                                <?php echo $errors['store_name']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="store_description" class="form-label">Descrição da Loja</label>
                        <textarea class="form-control" id="store_description" name="store_description" rows="3"><?php echo htmlspecialchars($store_description); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="whatsapp_number" class="form-label">Número de WhatsApp *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                            <input type="text" class="form-control <?php echo isset($errors['whatsapp_number']) ? 'is-invalid' : ''; ?>" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($whatsapp_number); ?>" placeholder="595991234567" required>
                            <?php if (isset($errors['whatsapp_number'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['whatsapp_number']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">
                            Digite o número completo com código do país, sem espaços ou caracteres especiais. Exemplo: 595991234567
                        </div>
                    </div>
                </div>
                
                <!-- Entrega e Frete -->
                <div class="form-section mb-4">
                    <h2 class="form-section-title">Entrega e Frete</h2>
                    
                    <div class="mb-3">
                        <label for="shipping_rate" class="form-label">Taxa de Frete por kg (Guaranis) *</label>
                        <div class="input-group">
                            <span class="input-group-text">G$</span>
                            <input type="text" class="form-control <?php echo isset($errors['shipping_rate']) ? 'is-invalid' : ''; ?>" id="shipping_rate" name="shipping_rate" value="<?php echo number_format(floatval($shipping_rate ?: 0), 0, ',', '.'); ?>" required>
                            <?php if (isset($errors['shipping_rate'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['shipping_rate']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">
                            Valor cobrado por kg de produto. O padrão é 1.500 Guaranis.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Endereço da Loja</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="business_hours" class="form-label">Horário de Funcionamento</label>
                        <textarea class="form-control" id="business_hours" name="business_hours" rows="2"><?php echo htmlspecialchars($business_hours); ?></textarea>
                        <div class="form-text">
                            Exemplo: Segunda a Sexta: 8h às 18h | Sábado: 8h às 12h
                        </div>
                    </div>
                </div>
                
                <!-- Controles Globais do Sistema -->
                <div class="form-section mb-4">
                    <h2 class="form-section-title">
                        <i class="fas fa-cogs text-primary me-2"></i>Controles Globais do Sistema
                    </h2>
                    <p class="text-muted small mb-4">Configure funcionalidades globais que afetam todo o sistema e experiência do cliente.</p>
                    
                    <!-- Controle do Frete -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-truck me-2"></i>Controle de Frete
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="enable_shipping" name="enable_shipping" <?php echo $enable_shipping ? 'checked' : ''; ?> onchange="toggleShippingControls()">
                                        <label class="form-check-label" for="enable_shipping">
                                            <strong>Ativar Cálculo de Frete</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Quando <strong>desativado</strong>, o sistema não mostrará cálculos de frete no carrinho e checkout. Apenas o subtotal e peso serão exibidos.
                                    </small>
                                    
                                    <div id="shipping_controls" class="<?php echo !$enable_shipping ? 'opacity-50' : ''; ?>">
                                        <div class="mb-3">
                                            <label for="shipping_control_text" class="form-label">Texto Explicativo do Frete</label>
                                            <input type="text" class="form-control" id="shipping_control_text" name="shipping_control_text" value="<?php echo htmlspecialchars($shipping_control_text); ?>" <?php echo !$enable_shipping ? 'disabled' : ''; ?>>
                                            <div class="form-text">
                                                Texto que aparece próximo ao cálculo de frete (quando ativado)
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <small>
                                            <strong>Impacto:</strong> Desativar o frete afeta carrinho, checkout e mensagens do WhatsApp. Use quando quiser negociar frete diretamente com clientes.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Controle de Mínimos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-sort-numeric-up me-2"></i>Controle de Quantidades Mínimas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="enable_global_minimums" name="enable_global_minimums" <?php echo $enable_global_minimums ? 'checked' : ''; ?> onchange="toggleMinimumControls()">
                                        <label class="form-check-label" for="enable_global_minimums">
                                            <strong>Respeitar Quantidades Mínimas dos Produtos</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Quando <strong>desativado</strong>, clientes podem comprar qualquer quantidade, ignorando os mínimos configurados nos produtos.
                                    </small>
                                    
                                    <div id="minimum_controls" class="<?php echo !$enable_global_minimums ? 'opacity-50' : ''; ?>">
                                        <div class="mb-3">
                                            <label for="minimum_explanation_text" class="form-label">Texto Explicativo para Mínimos</label>
                                            <input type="text" class="form-control" id="minimum_explanation_text" name="minimum_explanation_text" value="<?php echo htmlspecialchars($minimum_explanation_text); ?>" <?php echo !$enable_global_minimums ? 'disabled' : ''; ?>>
                                            <div class="form-text">
                                                Texto que aparece abaixo da quantidade mínima nos produtos (mobile-friendly)
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <small>
                                            <strong>Vantagem:</strong> Desativar permite vender pequenas quantidades sem precisar editar <?php
                                                // Contar produtos com mínimos
                                                $count_query = "SELECT COUNT(*) as total FROM products WHERE has_min_quantity = 1 AND min_wholesale_quantity > 0";
                                                $count_result = $conn->query($count_query);
                                                $product_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                                                echo $product_count;
                                            ?> produtos individualmente.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Redes Sociais -->
                <div class="form-section mb-4">
                    <h2 class="form-section-title">Redes Sociais</h2>
                    
                    <div class="mb-3">
                        <label for="social_facebook" class="form-label">Facebook</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                            <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($social_facebook); ?>" placeholder="https://facebook.com/sua-pagina">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="social_instagram" class="form-label">Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                            <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($social_instagram); ?>" placeholder="https://instagram.com/seu-perfil">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="social_twitter" class="form-label">Twitter</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                            <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($social_twitter); ?>" placeholder="https://twitter.com/seu-perfil">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </form>
        </div>
        
        <div class="col-md-4">
            <!-- Logo da Loja -->
            <div class="form-section mb-4">
                <h2 class="form-section-title">Logo da Loja</h2>
                
                <form action="configuracoes.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="logo_only_update" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Logo Atual</label>
                        <div class="image-preview mb-3">
                            <?php if (!empty($current_logo)): ?>
                                <img src="../<?php echo htmlspecialchars($current_logo); ?>" alt="Logo da Loja">
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p>Nenhum logo definido</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo" class="form-label">Enviar Novo Logo</label>
                            <input class="form-control <?php echo isset($errors['logo']) ? 'is-invalid' : ''; ?>" type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/gif">
                            <?php if (isset($errors['logo'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['logo']; ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">
                                Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary">Atualizar Logo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>

<script>
function toggleShippingControls() {
    const enableShipping = document.getElementById('enable_shipping').checked;
    const shippingControls = document.getElementById('shipping_controls');
    const shippingControlText = document.getElementById('shipping_control_text');
    
    if (enableShipping) {
        shippingControls.classList.remove('opacity-50');
        shippingControlText.disabled = false;
    } else {
        shippingControls.classList.add('opacity-50');
        shippingControlText.disabled = true;
    }
}

function toggleMinimumControls() {
    const enableMinimums = document.getElementById('enable_global_minimums').checked;
    const minimumControls = document.getElementById('minimum_controls');
    const minimumExplanationText = document.getElementById('minimum_explanation_text');
    
    if (enableMinimums) {
        minimumControls.classList.remove('opacity-50');
        minimumExplanationText.disabled = false;
    } else {
        minimumControls.classList.add('opacity-50');
        minimumExplanationText.disabled = true;
    }
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    toggleShippingControls();
    toggleMinimumControls();
});
</script>
