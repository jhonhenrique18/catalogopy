<?php
/**
 * Script para Atualizar Interface de Variações
 * 
 * FASE 6: Implementar visualização agrupada de produtos com variações
 */

require_once 'includes/db_connect.php';

echo "<h1>🎨 ATUALIZANDO INTERFACE DE VARIAÇÕES</h1>\n";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

echo "<h2>🔍 Análise Atual do Sistema</h2>\n";

// Verificar produtos pai vs variações
$query_analysis = "
    SELECT 
        CASE 
            WHEN parent_product_id IS NULL THEN 'Produto Pai'
            ELSE 'Variação'
        END as tipo,
        COUNT(*) as quantidade
    FROM products 
    WHERE status = 1 
    GROUP BY CASE WHEN parent_product_id IS NULL THEN 'Produto Pai' ELSE 'Variação' END
";

$result = $conn->query($query_analysis);

echo "<table border='1' style='border-collapse: collapse; margin: 15px 0;'>\n";
echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
echo "<td style='padding: 8px;'>Tipo</td><td style='padding: 8px;'>Quantidade</td>\n";
echo "</tr>\n";

while ($row = $result->fetch_assoc()) {
    echo "<tr>\n";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($row['tipo']) . "</td>\n";
    echo "<td style='padding: 8px;'>" . $row['quantidade'] . "</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// Mostrar grupos de variações criados
echo "<h2>📊 Grupos de Variações Criados</h2>\n";

$query_groups = "
    SELECT 
        parent.id as parent_id,
        parent.name as parent_name,
        COUNT(variations.id) as variations_count,
        GROUP_CONCAT(CONCAT(variations.variation_display, ' (', variations.id, ')') ORDER BY variations.variation_display SEPARATOR ', ') as variations_list
    FROM products parent
    LEFT JOIN products variations ON variations.parent_product_id = parent.id
    WHERE parent.parent_product_id IS NULL 
        AND parent.status = 1
        AND EXISTS (SELECT 1 FROM products v WHERE v.parent_product_id = parent.id)
    GROUP BY parent.id, parent.name
    ORDER BY parent.name
";

$result_groups = $conn->query($query_groups);

if ($result_groups->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
    echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
    echo "<td>Produto Pai</td><td>Variações</td><td>Total</td>\n";
    echo "</tr>\n";
    
    while ($group = $result_groups->fetch_assoc()) {
        echo "<tr>\n";
        echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($group['parent_name']) . "</strong><br><small>ID: " . $group['parent_id'] . "</small></td>\n";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($group['variations_list']) . "</td>\n";
        echo "<td style='padding: 8px; text-align: center;'>" . $group['variations_count'] . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>❌ Nenhum grupo de variações encontrado.</p>\n";
}

echo "<h2>🔧 Modificações Necessárias</h2>\n";

$modifications = [
    'index.php' => [
        'description' => 'Modificar query para mostrar apenas produtos pai na listagem principal',
        'changes' => [
            'Adicionar WHERE parent_product_id IS NULL na query de produtos',
            'Verificar se produto tem variações',
            'Mostrar indicador de variações disponíveis'
        ]
    ],
    'categorias.php' => [
        'description' => 'Modificar query para mostrar apenas produtos pai nas categorias',  
        'changes' => [
            'Adicionar WHERE parent_product_id IS NULL na query de produtos',
            'Aplicar mesma lógica do index.php',
            'Manter consistência entre páginas'
        ]
    ],
    'produto.php' => [
        'description' => 'Adicionar seletor de variações na página do produto',
        'changes' => [
            'Carregar variações do produto atual',
            'Criar interface de seleção mobile-friendly', 
            'Atualizar preço/info ao trocar variação via JavaScript'
        ]
    ]
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
echo "<td>Arquivo</td><td>Modificações</td>\n";
echo "</tr>\n";

foreach ($modifications as $file => $info) {
    echo "<tr>\n";
    echo "<td style='padding: 8px; vertical-align: top;'><strong>" . $file . "</strong><br><small>" . $info['description'] . "</small></td>\n";
    echo "<td style='padding: 8px;'>\n";
    echo "<ul style='margin: 0; padding-left: 20px;'>\n";
    foreach ($info['changes'] as $change) {
        echo "<li>" . htmlspecialchars($change) . "</li>\n";
    }
    echo "</ul>\n";
    echo "</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h2>🚀 Implementação Automática</h2>\n";

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<p><strong>Próximos passos:</strong></p>\n";
echo "<ol>\n";
echo "<li><a href='implement_index_variations.php' style='color: #007bff;'>🏠 Atualizar index.php para mostrar apenas produtos pai</a></li>\n";
echo "<li><a href='implement_categories_variations.php' style='color: #007bff;'>📂 Atualizar categorias.php com mesma lógica</a></li>\n";
echo "<li><a href='implement_product_variations.php' style='color: #007bff;'>📱 Criar seletor de variações em produto.php</a></li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><a href='organize_product_variations.php'>← Voltar à Organização</a> | <a href='index.php'>🏠 Início</a></p>\n";
?> 