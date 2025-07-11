<?php
// Iniciar sessão para gerenciar carrinho
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'includes/db_connect.php';

// Inicializar carrinho se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Verificar se os dados foram recebidos via POST como JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Verificar se temos dados de carrinho enviados diretamente (nova funcionalidade)
if (isset($data['cart']) && is_array($data['cart'])) {
    // Atualizar o carrinho na sessão com os dados recebidos
    $_SESSION['cart'] = $data['cart'];
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Carrito sincronizado correctamente',
        'count' => count($_SESSION['cart'])
    ]);
    exit;
}

// Verificar ação solicitada (para manter compatibilidade com código existente)
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Processar ação
switch ($action) {
    case 'add':
        // Verificar se os parâmetros necessários foram enviados
        if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
            exit;
        }
        
        // Obter e validar parâmetros
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];
        
        if ($product_id <= 0 || $quantity <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros inválidos'
            ]);
            exit;
        }
        
        // Verificar se o produto existe
        $query = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity FROM products WHERE id = ? AND status = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
            exit;
        }
        
        $product = $result->fetch_assoc();
        
        // Adicionar ao carrinho
        if (isset($_SESSION['cart'][$product_id])) {
            // Se o produto já existe no carrinho, atualizar quantidade
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Se é um novo produto, adicionar ao carrinho
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['wholesale_price'],
                'retail_price' => $product['retail_price'],
                'min_quantity' => $product['min_wholesale_quantity'],
                'quantity' => $quantity
            ];
        }
        
        // Retornar sucesso e contagem atualizada
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'count' => count($_SESSION['cart']),
            'product' => [
                'id' => $product_id,
                'name' => $product['name'],
                'quantity' => $_SESSION['cart'][$product_id]['quantity']
            ]
        ]);
        break;
        
    case 'update':
        // Verificar se os parâmetros necessários foram enviados
        if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros incompletos'
            ]);
            exit;
        }
        
        // Obter e validar parâmetros
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];
        
        if ($product_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto inválido'
            ]);
            exit;
        }
        
        // Verificar se o produto existe no carrinho
        if (!isset($_SESSION['cart'][$product_id])) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado en el carrito'
            ]);
            exit;
        }
        
        // Atualizar quantidade ou remover se quantidade for 0
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            $message = 'Cantidad actualizada';
        } else {
            unset($_SESSION['cart'][$product_id]);
            $message = 'Producto eliminado del carrito';
        }
        
        // Calcular total do carrinho
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Retornar sucesso e contagem atualizada
        echo json_encode([
            'success' => true,
            'message' => $message,
            'count' => count($_SESSION['cart']),
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.')
        ]);
        break;
        
    case 'remove':
        // Verificar se o ID do produto foi enviado
        if (!isset($_POST['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'ID del producto no especificado'
            ]);
            exit;
        }
        
        // Obter e validar parâmetro
        $product_id = (int) $_POST['product_id'];
        
        if ($product_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID del producto inválido'
            ]);
            exit;
        }
        
        // Verificar se o produto existe no carrinho
        if (!isset($_SESSION['cart'][$product_id])) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado en el carrito'
            ]);
            exit;
        }
        
        // Remover produto do carrinho
        unset($_SESSION['cart'][$product_id]);
        
        // Calcular total do carrinho
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Retornar sucesso e contagem atualizada
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
            'count' => count($_SESSION['cart']),
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.')
        ]);
        break;
        
    case 'clear':
        // Limpar carrinho
        $_SESSION['cart'] = [];
        
        // Retornar sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Carrito vaciado',
            'count' => 0,
            'total' => 0,
            'total_formatted' => '0'
        ]);
        break;
        
    case 'count':
        // Retornar contagem de itens no carrinho
        echo json_encode([
            'success' => true,
            'count' => count($_SESSION['cart'])
        ]);
        break;
        
    case 'get':
        // Obter todos os itens do carrinho
        $items = [];
        $total = 0;
        
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            
            $items[] = [
                'id' => $product_id,
                'name' => $item['name'],
                'price' => $item['price'],
                'price_formatted' => number_format($item['price'], 0, ',', '.'),
                'retail_price' => $item['retail_price'],
                'retail_price_formatted' => number_format($item['retail_price'], 0, ',', '.'),
                'min_quantity' => $item['min_quantity'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
                'subtotal_formatted' => number_format($subtotal, 0, ',', '.')
            ];
        }
        
        // Retornar itens e total
        echo json_encode([
            'success' => true,
            'items' => $items,
            'count' => count($_SESSION['cart']),
            'total' => $total,
            'total_formatted' => number_format($total, 0, ',', '.')
        ]);
        break;
        
    default:
        // Ação desconhecida - mas agora retornamos o estado atual do carrinho
        // em vez de um erro, para manter compatibilidade com o frontend
        echo json_encode([
            'success' => true,
            'message' => 'Estado actual del carrito',
            'count' => count($_SESSION['cart'])
        ]);
        break;
}
?>
