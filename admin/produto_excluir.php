<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se o ID foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error_message'] = 'ID do produto não fornecido.';
    header('Location: produtos.php');
    exit;
}

$product_id = (int)$_POST['id'];

// Verificar se o produto existe
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Produto não encontrado.';
    header('Location: produtos.php');
    exit;
}

$product = $result->fetch_assoc();

// Excluir o produto
$query = "DELETE FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    // Excluir imagem do produto, se existir
    if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
        unlink('../' . $product['image_url']);
    }
    
    $_SESSION['success_message'] = 'Produto excluído com sucesso!';
} else {
    $_SESSION['error_message'] = 'Erro ao excluir produto: ' . $conn->error;
}

// Redirecionar de volta para a lista de produtos
header('Location: produtos.php');
exit;
?>