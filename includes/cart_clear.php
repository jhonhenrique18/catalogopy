<?php
/**
 * Limpa completamente o carrinho
 */

// Iniciar sessão
session_start();

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Limpar o carrinho da sessão
$_SESSION['cart'] = [];

// Retornar resposta de sucesso
echo json_encode([
    'success' => true,
    'message' => 'Carrinho limpo com sucesso',
    'count' => 0
]);
?> 