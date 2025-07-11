<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se o ID foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'ID da categoria não fornecido.';
    header('Location: categorias.php');
    exit;
}

$category_id = (int)$_POST['id'];

// Verificar se a categoria existe
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Categoria não encontrada.';
    header('Location: categorias.php');
    exit;
}

$category = $result->fetch_assoc();

// Iniciar transação
$conn->begin_transaction();

try {
    // Atualizar produtos da categoria para sem categoria (NULL)
    $update_products = "UPDATE products SET category_id = NULL WHERE category_id = ?";
    $stmt_products = $conn->prepare($update_products);
    $stmt_products->bind_param("i", $category_id);
    $stmt_products->execute();
    
    // Atualizar subcategorias para categoria principal (parent_id = NULL)
    $update_subcategories = "UPDATE categories SET parent_id = NULL WHERE parent_id = ?";
    $stmt_subcategories = $conn->prepare($update_subcategories);
    $stmt_subcategories->bind_param("i", $category_id);
    $stmt_subcategories->execute();
    
    // Excluir a categoria
    $delete_category = "DELETE FROM categories WHERE id = ?";
    $stmt_delete = $conn->prepare($delete_category);
    $stmt_delete->bind_param("i", $category_id);
    $stmt_delete->execute();
    
    // Excluir imagem da categoria, se existir
    if (!empty($category['image_url']) && file_exists('../' . $category['image_url'])) {
        unlink('../' . $category['image_url']);
    }
    
    // Confirmar transação
    $conn->commit();
    
    $_SESSION['success_message'] = 'Categoria excluída com sucesso!';
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $_SESSION['error_message'] = 'Erro ao excluir categoria: ' . $e->getMessage();
}

// Redirecionar de volta para a lista de categorias
header('Location: categorias.php');
exit;
?>