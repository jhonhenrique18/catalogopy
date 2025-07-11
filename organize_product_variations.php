<?php
/**
 * Script para Organizar Produtos Existentes em Variações
 * 
 * FASE 5: Identificar e agrupar produtos similares como variações
 */

require_once 'includes/db_connect.php';

echo "<h1>📊 ORGANIZAÇÃO DE PRODUTOS EM VARIAÇÕES</h1>\n";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

// Buscar produtos que parecem ser variações (mesma base, diferentes tamanhos)
echo "<h2>🔍 Análise de Produtos com Potencial para Variações</h2>\n";

$query = "SELECT id, name, unit_type, unit_display_name, unit_weight, category_id FROM products WHERE status = 1 ORDER BY name";
$result = $conn->query($query);

$product_groups = [];
$patterns_found = [];

while ($product = $result->fetch_assoc()) {
    $name = $product['name'];
    
    // Padrões para identificar variações por tamanho
    $size_patterns = [
        '/(\d+)\s*(ml|ML)/i' => 'ml',
        '/(\d+)\s*(l|L)/i' => 'L', 
        '/(\d+)\s*(g|G)/i' => 'g',
        '/(\d+)\s*(kg|KG)/i' => 'kg',
        '/(\d+\.?\d*)\s*(l|L)/i' => 'L'
    ];
    
    foreach ($size_patterns as $pattern => $unit) {
        if (preg_match($pattern, $name, $matches)) {
            // Remover o tamanho do nome para criar a base
            $base_name = preg_replace($pattern, '', $name);
            $base_name = trim(preg_replace('/\s+/', ' ', $base_name)); // Limpar espaços extras
            
            // Extrair o tamanho
            $size = $matches[1] . $unit;
            
            if (!isset($product_groups[$base_name])) {
                $product_groups[$base_name] = [];
            }
            
            $product_groups[$base_name][] = [
                'id' => $product['id'],
                'name' => $name,
                'size' => $size,
                'unit_type' => $product['unit_type'],
                'unit_weight' => $product['unit_weight'],
                'category_id' => $product['category_id']
            ];
            
            $patterns_found[] = [
                'original' => $name,
                'base' => $base_name,
                'size' => $size,
                'pattern' => $pattern
            ];
            break; // Parar no primeiro padrão encontrado
        }
    }
}

// Filtrar grupos com 2 ou mais produtos (candidatos a variações)
$variation_candidates = array_filter($product_groups, function($group) {
    return count($group) >= 2;
});

echo "<h3>📋 Grupos de Produtos Identificados:</h3>\n";

if (empty($variation_candidates)) {
    echo "<p>❌ Nenhum grupo de variação identificado automaticamente.</p>\n";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
    echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
    echo "<td>Produto Base</td><td>Variações Encontradas</td><td>Ação</td>\n";
    echo "</tr>\n";
    
    foreach ($variation_candidates as $base_name => $variations) {
        echo "<tr>\n";
        echo "<td style='padding: 8px; vertical-align: top;'><strong>" . htmlspecialchars($base_name) . "</strong></td>\n";
        echo "<td style='padding: 8px;'>\n";
        
        foreach ($variations as $variation) {
            echo "• " . htmlspecialchars($variation['name']) . " (ID: " . $variation['id'] . ")<br>\n";
            echo "&nbsp;&nbsp;<small>Tamanho: " . htmlspecialchars($variation['size']) . " | Tipo: " . htmlspecialchars($variation['unit_type']) . "</small><br><br>\n";
        }
        
        echo "</td>\n";
        echo "<td style='padding: 8px; text-align: center;'>\n";
        echo "<button onclick='organizeGroup(\"" . htmlspecialchars($base_name) . "\")' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Organizar</button>\n";
        echo "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

// Casos específicos conhecidos (aceite de coco)
echo "<h2>🎯 Casos Específicos Identificados</h2>\n";

$specific_cases = [
    'Aceite de Coco Virgen Extra' => [
        116 => 'Aceite de coco extra virgen show 200ML',
        115 => 'Aceite de coco virgen extra-coco show 500 ML', 
        121 => 'Coco show aceite de coco virgen extra 70 ML'
    ],
    'Aceite de Coco Santo Óleo' => [
        149 => 'aceite de coco virgen extra aceite santo 100ML',
        148 => 'aceite de coco virgen extra aceite santo 200ML', 
        147 => 'aceite de coco virgen extra aceite santo 400ML',
        146 => 'Aceite de coco virgen extra santo oleo 500ML',
        117 => 'Aceite de coco virgen extra con 1L Santo óleo'
    ],
    'Aceite de Coco Copra' => [
        17 => 'Aceite de coco virgen extra copra 200 ml',
        18 => 'Aceite de coco virgen extra copra 500 ml'
    ]
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
echo "<td>Grupo</td><td>Produtos</td><td>Ação</td>\n";
echo "</tr>\n";

foreach ($specific_cases as $group_name => $products) {
    echo "<tr>\n";
    echo "<td style='padding: 8px; vertical-align: top;'><strong>" . htmlspecialchars($group_name) . "</strong></td>\n";
    echo "<td style='padding: 8px;'>\n";
    
    foreach ($products as $id => $name) {
        echo "• " . htmlspecialchars($name) . " (ID: {$id})<br>\n";
    }
    
    echo "</td>\n";
    echo "<td style='padding: 8px; text-align: center;'>\n";
    echo "<button onclick='organizeSpecificGroup(\"" . htmlspecialchars($group_name) . "\")' style='background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Organizar</button>\n";
    echo "</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h2>🔧 Ações Disponíveis</h2>\n";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<p><strong>Próximos passos:</strong></p>\n";
echo "<ol>\n";
echo "<li>Revisar os grupos identificados</li>\n";
echo "<li>Escolher qual grupo organizar primeiro</li>\n";
echo "<li>Definir produto pai para cada grupo</li>\n";
echo "<li>Configurar variações automaticamente</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><a href='index.php'>🏠 Voltar ao Início</a></p>\n";

// JavaScript para interações
echo "<script>\n";
echo "function organizeGroup(baseName) {\n";
echo "    if (confirm('Organizar produtos do grupo: ' + baseName + '?')) {\n";
echo "        window.location.href = 'implement_group_variations.php?type=auto&group=' + encodeURIComponent(baseName);\n";
echo "    }\n";
echo "}\n";

echo "function organizeSpecificGroup(groupName) {\n";
echo "    if (confirm('Organizar grupo específico: ' + groupName + '?')) {\n";
echo "        window.location.href = 'implement_group_variations.php?type=specific&group=' + encodeURIComponent(groupName);\n";
echo "    }\n";
echo "}\n";
echo "</script>\n";
?> 