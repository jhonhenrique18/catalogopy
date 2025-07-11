<?php
/**
 * Funções para sistema de cotação cambial
 * 
 * Produtos armazenados em Real (BRL)
 * Exibição em Guaranis (PYG) com conversão automática
 */

/**
 * Obter a cotação atual do banco de dados
 * 
 * @return float Cotação atual (1 Real = X Guaranis)
 */
function getCurrentExchangeRate() {
    global $conn;
    
    $query = "SELECT rate FROM exchange_rate ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float) $row['rate'];
    }
    
    // Valor padrão caso não encontre
    return 1420.0;
}

/**
 * Converter preço de Real para Guarani
 * 
 * @param float $price_brl Preço em Real
 * @return float Preço em Guaranis
 */
function convertBrlToPyg($price_brl) {
    $rate = getCurrentExchangeRate();
    return $price_brl * $rate;
}

/**
 * Converter preço de Guarani para Real
 * 
 * @param float $price_pyg Preço em Guaranis
 * @return float Preço em Real
 */
function convertPygToBrl($price_pyg) {
    $rate = getCurrentExchangeRate();
    return $price_pyg / $rate;
}

/**
 * Formatar preço em Guaranis para exibição
 * 
 * @param float $price_brl Preço em Real (do banco)
 * @return string Preço formatado em Guaranis (ex: "G$ 142.000")
 */
function formatPriceInGuaranis($price_brl) {
    $price_pyg = convertBrlToPyg($price_brl);
    return 'G$ ' . number_format($price_pyg, 0, ',', '.');
}

/**
 * Formatar preço em Guaranis sem símbolo
 * 
 * @param float $price_brl Preço em Real (do banco)
 * @return string Preço formatado sem símbolo (ex: "142.000")
 */
function formatPriceInGuaranisNoSymbol($price_brl) {
    $price_pyg = convertBrlToPyg($price_brl);
    return number_format($price_pyg, 0, ',', '.');
}

/**
 * Formatar preço por kg em Guaranis
 * 
 * @param float $price_brl Preço em Real (do banco)
 * @return string Preço formatado (ex: "G$ 142.000/kg")
 */
function formatPricePerKgInGuaranis($price_brl) {
    return formatPriceInGuaranis($price_brl) . '/kg';
}

/**
 * Atualizar cotação cambial
 * 
 * @param float $new_rate Nova cotação
 * @param string $updated_by Quem atualizou
 * @return bool Sucesso da operação
 */
function updateExchangeRate($new_rate, $updated_by = 'Admin') {
    global $conn;
    
    $query = "UPDATE exchange_rate SET rate = ?, updated_by = ?, updated_at = NOW() WHERE id = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ds", $new_rate, $updated_by);
    
    return $stmt->execute();
}

/**
 * Calcular desconto atacadista em Guaranis
 * 
 * @param float $wholesale_price_brl Preço atacado em Real
 * @param float $retail_price_brl Preço varejo em Real  
 * @return array Array com informações do desconto
 */
function calculateWholesaleDiscount($wholesale_price_brl, $retail_price_brl) {
    $wholesale_pyg = convertBrlToPyg($wholesale_price_brl);
    $retail_pyg = convertBrlToPyg($retail_price_brl);
    
    $discount_amount = $retail_pyg - $wholesale_pyg;
    $discount_percentage = ($discount_amount / $retail_pyg) * 100;
    
    return [
        'wholesale_price_pyg' => $wholesale_pyg,
        'retail_price_pyg' => $retail_pyg,
        'discount_amount' => $discount_amount,
        'discount_percentage' => round($discount_percentage, 1)
    ];
}

/**
 * Obter informações completas da cotação
 * 
 * @return array Informações da cotação atual
 */
function getExchangeRateInfo() {
    global $conn;
    
    $query = "SELECT * FROM exchange_rate ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [
        'rate' => 1420.0,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => 'Sistema'
    ];
}
?> 