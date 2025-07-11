<?php
/**
 * Arquivo de funções utilitárias
 * 
 * Contém funções reutilizáveis em todo o site
 */

/**
 * Formata um valor em Guaranis
 * 
 * @param float $value O valor a ser formatado
 * @return string Valor formatado em Guaranis
 */
function formatGuarani($value) {
    return 'G$ ' . number_format($value, 0, ',', '.');
}

/**
 * Sanitiza input para evitar XSS
 * 
 * @param string $data Dados a serem sanitizados
 * @return string Dados sanitizados
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Verifica se um produto está no carrinho
 * 
 * @param int $productId ID do produto
 * @return bool Verdadeiro se o produto estiver no carrinho
 */
function isInCart($productId) {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if ($item['product_id'] == $productId) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Obtém a quantidade de um produto no carrinho
 * 
 * @param int $productId ID do produto
 * @return int Quantidade do produto no carrinho
 */
function getCartQuantity($productId) {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if ($item['product_id'] == $productId) {
                return $item['quantity'];
            }
        }
    }
    return 0;
}

/**
 * Calcula o total de itens no carrinho
 * 
 * @return int Total de itens
 */
function getCartItemsCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}

/**
 * Determina se um preço é mayorista ou minorista com base na quantidade
 * 
 * @param array $product Dados do produto
 * @param int $quantity Quantidade selecionada
 * @return float Preço apropriado
 */
function getPrice($product, $quantity) {
    if ($quantity >= $product['min_wholesale_quantity']) {
        return $product['wholesale_price'];
    } else {
        return $product['retail_price'];
    }
}

/**
 * Calcula o frete com base no peso total
 * 
 * @param float $totalWeight Peso total em kg
 * @param float $ratePerKg Taxa por kg (padrão: 1500 Guaranis)
 * @return float Valor do frete
 */
function calculateShipping($totalWeight, $ratePerKg = 1500) {
    // Arredonda o peso para cima para garantir cobertura adequada
    $roundedWeight = ceil($totalWeight);
    return $roundedWeight * $ratePerKg;
}

/**
 * Gera um link para WhatsApp com dados do pedido
 * 
 * @param array $order Dados do pedido
 * @param string $phone Número de telefone do WhatsApp (com código do país)
 * @param bool $include_shipping Se deve incluir informações de frete (padrão: true)
 * @return string URL formatada para WhatsApp
 */
function generateWhatsAppLink($order, $phone, $include_shipping = true) {
    $message = "*NUEVO PEDIDO*\n\n";
    
    // Informações do cliente
    $message .= "*Cliente:* " . $order['customer_name'] . "\n";
    $message .= "*Teléfono:* " . $order['customer_phone'] . "\n";
    $message .= "*Email:* " . $order['customer_email'] . "\n";
    $message .= "*Dirección:* " . $order['customer_address'] . "\n\n";
    
    // Separar produtos com e sem preço
    $products_with_price = [];
    $products_to_quote = [];
    
    foreach ($order['items'] as $item) {
        $has_price = isset($item['has_price']) ? $item['has_price'] : ($item['price'] > 0);
        if ($has_price) {
            $products_with_price[] = $item;
        } else {
            $products_to_quote[] = $item;
        }
    }
    
    // Produtos com preço definido
    if (!empty($products_with_price)) {
        $message .= "*PRODUCTOS CON PRECIO:*\n";
        foreach ($products_with_price as $index => $item) {
            $message .= ($index + 1) . ". " . $item['quantity'] . "kg " . $item['name'] . " - " . 
                        formatGuarani($item['price']) . "/kg = " . formatGuarani($item['subtotal']) . "\n";
        }
        $message .= "\n";
    }
    
    // Produtos que precisam de cotação
    if (!empty($products_to_quote)) {
        $message .= "*PRODUCTOS A COTIZAR:*\n";
        foreach ($products_to_quote as $index => $item) {
            $message .= ($index + 1) . ". " . $item['quantity'] . "kg " . $item['name'] . " - *PRECIO A CONSULTAR*\n";
        }
        $message .= "\n";
    }
    
    // Totais
    if (!empty($products_with_price)) {
        $message .= "*Subtotal productos con precio:* " . formatGuarani($order['subtotal']) . "\n";
        $message .= "*Peso total:* " . number_format($order['total_weight'], 2, ',', '.') . " kg\n";
        
        if ($include_shipping) {
            $message .= "*Flete:* " . formatGuarani($order['shipping']) . "\n";
        }
        
        if (!empty($products_to_quote)) {
            if ($include_shipping) {
                $message .= "*TOTAL (parcial):* " . formatGuarani($order['total']) . " *+ cotización*\n\n";
            } else {
                $message .= "*TOTAL (parcial):* " . formatGuarani($order['subtotal']) . " *+ cotización + flete*\n\n";
            }
            $message .= "⚠️ *SOLICITUD:* Por favor cotizar los productos marcados";
            if (!$include_shipping) {
                $message .= " y el flete";
            }
            $message .= " y enviar precio total final.\n\n";
        } else {
            if ($include_shipping) {
                $message .= "*TOTAL:* " . formatGuarani($order['total']) . "\n\n";
            } else {
                $message .= "*TOTAL (sin flete):* " . formatGuarani($order['subtotal']) . " *+ flete a acordar*\n\n";
            }
        }
    } else {
        // Solo productos para cotizar
        $message .= "*Peso total:* " . number_format($order['total_weight'], 2, ',', '.') . " kg\n";
        
        if ($include_shipping) {
            $message .= "*Flete estimado:* " . formatGuarani($order['shipping']) . "\n\n";
            $message .= "⚠️ *SOLICITUD:* Todos los productos requieren cotización. Por favor enviar precio total.\n\n";
        } else {
            $message .= "⚠️ *SOLICITUD:* Todos los productos requieren cotización + flete a acordar. Por favor enviar precio total.\n\n";
        }
    }
    
    if (!empty($order['notes'])) {
        $message .= "*Observaciones:*\n" . $order['notes'] . "\n";
    }
    
    // Codificar a mensagem para URL
    $encodedMessage = urlencode($message);
    
    // Gerar URL do WhatsApp
    return "https://api.whatsapp.com/send?phone={$phone}&text={$encodedMessage}";
}
?>