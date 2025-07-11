<?php
/**
 * Script para Implementar Organização de Grupos de Variações
 * 
 * FASE 5: Executar a organização de produtos em variações
 */

require_once 'includes/db_connect.php';

$type = $_GET['type'] ?? '';
$group = $_GET['group'] ?? '';

echo "<h1>🛠️ IMPLEMENTAÇÃO DE GRUPOS DE VARIAÇÕES</h1>\n";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

if (empty($type) || empty($group)) {
    echo "<p style='color: red;'>❌ Parâmetros inválidos!</p>\n";
    echo "<p><a href='organize_product_variations.php'>← Voltar à Análise</a></p>\n";
    exit;
}

echo "<h2>📋 Organizando Grupo: " . htmlspecialchars($group) . "</h2>\n";
echo "<p><strong>Tipo:</strong> " . htmlspecialchars($type) . "</p>\n";

// Definir grupos específicos
$specific_groups = [
    'Aceite de Coco Virgen Extra' => [
        116 => ['name' => 'Aceite de coco extra virgen show 200ML', 'variation' => '200ml'],
        115 => ['name' => 'Aceite de coco virgen extra-coco show 500 ML', 'variation' => '500ml'], 
        121 => ['name' => 'Coco show aceite de coco virgen extra 70 ML', 'variation' => '70ml']
    ],
    'Aceite de Coco Santo Óleo' => [
        149 => ['name' => 'aceite de coco virgen extra aceite santo 100ML', 'variation' => '100ml'],
        148 => ['name' => 'aceite de coco virgen extra aceite santo 200ML', 'variation' => '200ml'], 
        147 => ['name' => 'aceite de coco virgen extra aceite santo 400ML', 'variation' => '400ml'],
        146 => ['name' => 'Aceite de coco virgen extra santo oleo 500ML', 'variation' => '500ml'],
        117 => ['name' => 'Aceite de coco virgen extra con 1L Santo óleo', 'variation' => '1L']
    ],
    'Aceite de Coco Copra' => [
        17 => ['name' => 'Aceite de coco virgen extra copra 200 ml', 'variation' => '200ml'],
        18 => ['name' => 'Aceite de coco virgen extra copra 500 ml', 'variation' => '500ml']
    ]
];

if ($type === 'specific' && isset($specific_groups[$group])) {
    $products = $specific_groups[$group];
    
    echo "<h3>🎯 Produtos do Grupo:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
    echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
    echo "<td>ID</td><td>Nome</td><td>Variação</td><td>Será</td>\n";
    echo "</tr>\n";
    
    $parent_id = min(array_keys($products)); // Primeiro produto como pai
    
    foreach ($products as $id => $product) {
        $role = ($id == $parent_id) ? 'PRODUTO PAI' : 'VARIAÇÃO';
        $color = ($id == $parent_id) ? '#d4edda' : '#f8f9fa';
        
        echo "<tr style='background-color: {$color};'>\n";
        echo "<td style='padding: 8px;'>{$id}</td>\n";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($product['name']) . "</td>\n";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($product['variation']) . "</td>\n";
        echo "<td style='padding: 8px;'><strong>{$role}</strong></td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3>🔧 Executando Organização:</h3>\n";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($products as $id => $product) {
        if ($id == $parent_id) {
            // Produto pai - limpar campos de variação se existirem
            $sql = "UPDATE products SET parent_product_id = NULL, variation_display = NULL, variation_type = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            echo "<p><strong>Produto Pai (ID: {$id}):</strong> ";
            if ($stmt->execute()) {
                echo "<span style='color: green;'>✅ Configurado como produto pai</span></p>\n";
                $success_count++;
            } else {
                echo "<span style='color: red;'>❌ Erro: " . htmlspecialchars($conn->error) . "</span></p>\n";
                $error_count++;
            }
        } else {
            // Produto variação
            $sql = "UPDATE products SET parent_product_id = ?, variation_display = ?, variation_type = 'size' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $parent_id, $product['variation'], $id);
            
            echo "<p><strong>Variação (ID: {$id}):</strong> ";
            if ($stmt->execute()) {
                echo "<span style='color: green;'>✅ Configurado como variação '{$product['variation']}'</span></p>\n";
                $success_count++;
            } else {
                echo "<span style='color: red;'>❌ Erro: " . htmlspecialchars($conn->error) . "</span></p>\n";
                $error_count++;
            }
        }
    }
    
    echo "<hr>\n";
    echo "<h3>📊 Resultado da Organização:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Sucessos:</strong> {$success_count}</li>\n";
    echo "<li><strong>Erros:</strong> {$error_count}</li>\n";
    echo "</ul>\n";
    
    if ($error_count === 0) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h4>🎉 Grupo Organizado com Sucesso!</h4>\n";
        echo "<p><strong>Produto Pai:</strong> " . htmlspecialchars($products[$parent_id]['name']) . " (ID: {$parent_id})</p>\n";
        echo "<p><strong>Variações configuradas:</strong> " . (count($products) - 1) . "</p>\n";
        echo "</div>\n";
        
        // Verificar resultado
        echo "<h3>🔍 Verificação:</h3>\n";
        $verification_sql = "SELECT id, name, parent_product_id, variation_display, variation_type FROM products WHERE id IN (" . implode(',', array_keys($products)) . ") ORDER BY parent_product_id IS NULL DESC, variation_display";
        $result = $conn->query($verification_sql);
        
        if ($result) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
            echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
            echo "<td>ID</td><td>Nome</td><td>Parent ID</td><td>Variação</td><td>Tipo</td>\n";
            echo "</tr>\n";
            
            while ($row = $result->fetch_assoc()) {
                $bg_color = is_null($row['parent_product_id']) ? '#d4edda' : '#f8f9fa';
                echo "<tr style='background-color: {$bg_color};'>\n";
                echo "<td style='padding: 8px;'>" . $row['id'] . "</td>\n";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($row['name']) . "</td>\n";
                echo "<td style='padding: 8px;'>" . ($row['parent_product_id'] ?: 'PRODUTO PAI') . "</td>\n";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($row['variation_display'] ?: '-') . "</td>\n";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($row['variation_type'] ?: '-') . "</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h4>⚠️ Organização com Problemas</h4>\n";
        echo "<p>Alguns produtos não foram organizados corretamente. Verifique os erros acima.</p>\n";
        echo "</div>\n";
    }
    
} else {
    echo "<p style='color: red;'>❌ Grupo não encontrado ou tipo inválido!</p>\n";
}

echo "<h3>🚀 Próximas Ações:</h3>\n";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<p><a href='organize_product_variations.php' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>← Organizar Mais Grupos</a></p>\n";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🏠 Voltar ao Início</a></p>\n";
echo "<p><a href='categorias.php?id=33' style='background: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>👀 Ver Resultado na Loja</a></p>\n";
echo "</div>\n";
?> 