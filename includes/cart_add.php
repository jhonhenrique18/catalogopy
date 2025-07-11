<?php
/**
 * Processamento de adição ao carrinho
 * 
 * Recebe requisições AJAX para adicionar produtos ao carrinho
 */

// Iniciar sessão
session_start();

// Incluir conexão com banco de dados
require_once 'db_connect.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se os dados necessários foram enviados
    if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity'];
        
        // Validar quantidade
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
        // Verificar se o produto existe e obter configurações globais
        $query = "SELECT p.*, s.enable_global_minimums, s.minimum_explanation_text,
                  parent.name as parent_name
                  FROM products p 
                  CROSS JOIN store_settings s 
                  LEFT JOIN products parent ON p.parent_product_id = parent.id
                  WHERE p.id = ? AND p.status = 1 AND s.id = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $product = $row;
            $global_minimums_enabled = $row['enable_global_minimums'] ?? 1;
            
            // Detectar se é variação e preparar nome para exibição
            $is_variation = !empty($product['parent_product_id']);
            $display_name = $product['name'];
            $parent_name = $row['parent_name'] ?? null;
            $variation_display = $product['variation_display'] ?? null;
            
            // Se for variação, criar nome mais claro para o carrinho
            if ($is_variation && $parent_name && $variation_display) {
                $display_name = $parent_name . ' ' . $variation_display;
            }
            
            // Determinar se produto tem preço configurado
            $show_price = $product['show_price'] ?? 1;
            $has_price = $show_price && !empty($product['wholesale_price']);
            
            // Verificar quantidade mínima: apenas se configurações globais e do produto estiverem ativas
            $has_min_quantity = $product['has_min_quantity'] ?? 1;
            $should_check_minimum = $global_minimums_enabled && $has_price && $has_min_quantity && !empty($product['min_wholesale_quantity']);
            
            if ($should_check_minimum && $quantity < $product['min_wholesale_quantity']) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cantidad mínima requerida: {$product['min_wholesale_quantity']} {$product['unit_display_name']}"
                ]);
                exit;
            }
            
            // Inicializar o carrinho se não existir
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Verificar se o produto já está no carrinho
            $found = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    // Atualizar quantidade
                    $_SESSION['cart'][$key]['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            // Se o produto não estiver no carrinho, adicionar
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product_id,
                    'product_id' => $product_id,
                    'name' => $product['name'], // Nome original do produto
                    'display_name' => $display_name, // Nome para exibição no carrinho
                    'parent_product_id' => $product['parent_product_id'],
                    'parent_name' => $parent_name,
                    'variation_display' => $variation_display,
                    'is_variation' => $is_variation,
                    'wholesale_price' => $has_price ? $product['wholesale_price'] : null,
                    'retail_price' => null, // Preço de varejo removido conforme solicitado
                    'min_wholesale_quantity' => $has_min_quantity ? $product['min_wholesale_quantity'] : null,
                    'quantity' => $quantity,
                    'weight' => $product['unit_weight'],
                    'unit_type' => $product['unit_type'] ?? 'kg',
                    'unit_display_name' => $product['unit_display_name'] ?? 'kg',
                    'image_url' => $product['image_url'] ?: 'assets/images/no-image.png',
                    'show_price' => $show_price,
                    'has_min_quantity' => $has_min_quantity,
                    'has_price' => $has_price,
                    'price_status' => $has_price ? 'with_price' : 'quote_required'
                ];
            }
            
            // Calcular total de itens no carrinho
            $cart_count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
            
            // Retornar resposta de sucesso com mensagem adequada
            $success_message = $has_price ? 
                'Producto agregado al carrito' : 
                'Producto agregado al carrito - Precio a consultar';
            
            echo json_encode([
                'success' => true,
                'message' => $success_message,
                'cart_count' => $cart_count,
                'has_price' => $has_price
            ]);
            exit;
        } else {
            // Produto não encontrado
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
            exit;
        }
    }
}

// Se chegou aqui, houve um erro
echo json_encode([
    'success' => false,
    'message' => 'Error al agregar producto al carrito'
]);
exit;
?>