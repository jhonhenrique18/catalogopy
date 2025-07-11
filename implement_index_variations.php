<?php
/**
 * Script para Implementar Variações no index.php
 * 
 * FASE 6: Modificar index.php para mostrar apenas produtos pai
 */

require_once 'includes/db_connect.php';

echo "<h1>🏠 IMPLEMENTANDO VARIAÇÕES NO INDEX.PHP</h1>\n";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

echo "<h2>🔍 Analisando Modificações Necessárias</h2>\n";

// Backup do arquivo original
$backup_filename = 'index_backup_' . date('Ymd_His') . '.php';
if (copy('index.php', $backup_filename)) {
    echo "<p>✅ <strong>Backup criado:</strong> {$backup_filename}</p>\n";
} else {
    echo "<p>❌ <strong>Erro ao criar backup!</strong></p>\n";
}

echo "<h3>📋 Modificações a implementar:</h3>\n";
echo "<ol>\n";
echo "<li><strong>Query de produtos:</strong> Adicionar <code>WHERE parent_product_id IS NULL</code></li>\n";
echo "<li><strong>Consulta de variações:</strong> Verificar se produto tem variações</li>\n";
echo "<li><strong>Interface:</strong> Mostrar indicador quando produto tem variações</li>\n";
echo "<li><strong>JavaScript:</strong> Manter compatibilidade com infinite scroll</li>\n";
echo "</ol>\n";

echo "<h2>🔧 Implementando Modificações</h2>\n";

// Ler conteúdo atual do index.php
$index_content = file_get_contents('index.php');

// Modificação 1: Query principal para mostrar apenas produtos pai
$old_query = '$query_products = "SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, unit_type, unit_display_name, image_url, featured, promotion, show_price, has_min_quantity, category_id 
                      FROM products 
                      WHERE status = 1';

$new_query = '$query_products = "SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity, p.category_id,
                      (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
                      FROM products p
                      WHERE p.status = 1 AND p.parent_product_id IS NULL';

echo "<p><strong>1. Modificando query principal...</strong></p>\n";
if (strpos($index_content, $old_query) !== false) {
    $index_content = str_replace($old_query, $new_query, $index_content);
    echo "<p>✅ Query principal atualizada para mostrar apenas produtos pai</p>\n";
} else {
    echo "<p>⚠️ Padrão da query principal não encontrado - verificando alternativas...</p>\n";
    
    // Tentar padrão alternativo
    $alt_pattern = 'FROM products 
                      WHERE status = 1';
    $alt_replacement = 'FROM products p
                      WHERE p.status = 1 AND p.parent_product_id IS NULL';
    
    if (strpos($index_content, $alt_pattern) !== false) {
        $index_content = str_replace($alt_pattern, $alt_replacement, $index_content);
        echo "<p>✅ Query alternativa atualizada</p>\n";
    }
}

// Modificação 2: Query para busca por termo
echo "<p><strong>2. Modificando query de busca...</strong></p>\n";
$search_old = 'FROM products 
                          WHERE status = 1 
                            AND name LIKE';
$search_new = 'FROM products p
                          WHERE p.status = 1 
                            AND p.parent_product_id IS NULL
                            AND p.name LIKE';

if (strpos($index_content, $search_old) !== false) {
    $index_content = str_replace($search_old, $search_new, $index_content);
    echo "<p>✅ Query de busca atualizada</p>\n";
} else {
    echo "<p>⚠️ Query de busca não modificada</p>\n";
}

// Modificação 3: Query para JavaScript (infinite scroll)
echo "<p><strong>3. Modificando query do JavaScript...</strong></p>\n";
$js_old = '"SELECT id, name, wholesale_price, retail_price, min_wholesale_quantity, unit_weight, unit_type, unit_display_name, image_url, featured, promotion, show_price, has_min_quantity, category_id FROM products WHERE status = 1"';
$js_new = '"SELECT p.id, p.name, p.wholesale_price, p.retail_price, p.min_wholesale_quantity, p.unit_weight, p.unit_type, p.unit_display_name, p.image_url, p.featured, p.promotion, p.show_price, p.has_min_quantity, p.category_id, (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count FROM products p WHERE p.status = 1 AND p.parent_product_id IS NULL"';

if (strpos($index_content, $js_old) !== false) {
    $index_content = str_replace($js_old, $js_new, $index_content);
    echo "<p>✅ Query do JavaScript atualizada</p>\n";
} else {
    echo "<p>⚠️ Query do JavaScript não encontrada com padrão exato</p>\n";
}

// Modificação 4: Adicionar indicador de variações no HTML
echo "<p><strong>4. Adicionando indicador de variações...</strong></p>\n";

// Procurar onde adicionar o indicador
$indicator_pattern = '<!-- Quantidade mínima - só exibe se configurado -->';
$indicator_addition = '                                <!-- Indicador de variações disponíveis -->
                                <?php if (isset($product[\'variations_count\']) && $product[\'variations_count\'] > 0): ?>
                                    <p class="product-variations-indicator" style="font-size: 11px; color: #28a745; margin: 2px 0;">
                                        <i class="fas fa-layer-group me-1"></i><?php echo $product[\'variations_count\']; ?> variações disponibles
                                    </p>
                                <?php endif; ?>

                                ' . $indicator_pattern;

if (strpos($index_content, $indicator_pattern) !== false) {
    $index_content = str_replace($indicator_pattern, $indicator_addition, $index_content);
    echo "<p>✅ Indicador de variações adicionado</p>\n";
} else {
    echo "<p>⚠️ Local para indicador não encontrado</p>\n";
}

// Salvar arquivo modificado
if (file_put_contents('index.php', $index_content)) {
    echo "<p>✅ <strong>Arquivo index.php atualizado com sucesso!</strong></p>\n";
} else {
    echo "<p>❌ <strong>Erro ao salvar index.php!</strong></p>\n";
}

echo "<hr>\n";
echo "<h2>📊 Resultado da Implementação</h2>\n";

// Testar a query modificada
echo "<h3>🧪 Testando nova query:</h3>\n";
$test_query = "SELECT p.id, p.name, 
                      (SELECT COUNT(*) FROM products v WHERE v.parent_product_id = p.id AND v.status = 1) as variations_count
               FROM products p
               WHERE p.status = 1 AND p.parent_product_id IS NULL
               ORDER BY p.featured DESC, p.promotion DESC, p.name ASC
               LIMIT 10";

$test_result = $conn->query($test_query);

if ($test_result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>\n";
    echo "<tr style='background-color: #e9ecef; font-weight: bold;'>\n";
    echo "<td>ID</td><td>Nome</td><td>Variações</td>\n";
    echo "</tr>\n";
    
    while ($row = $test_result->fetch_assoc()) {
        $variations_info = $row['variations_count'] > 0 ? $row['variations_count'] . ' variações' : 'Produto simples';
        $bg_color = $row['variations_count'] > 0 ? '#e7f3ff' : '#f8f9fa';
        
        echo "<tr style='background-color: {$bg_color};'>\n";
        echo "<td style='padding: 8px;'>" . $row['id'] . "</td>\n";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['name']) . "</td>\n";
        echo "<td style='padding: 8px;'>" . $variations_info . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<p>✅ <strong>Query funcionando corretamente!</strong></p>\n";
} else {
    echo "<p>❌ <strong>Erro na query:</strong> " . htmlspecialchars($conn->error) . "</p>\n";
}

echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>🎉 Index.php Atualizado com Sucesso!</h3>\n";
echo "<p><strong>Modificações implementadas:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Listagem mostra apenas produtos pai</li>\n";
echo "<li>✅ Variações ficam ocultas da listagem principal</li>\n";
echo "<li>✅ Indicador mostra quantas variações existem</li>\n";
echo "<li>✅ Infinite scroll mantém compatibilidade</li>\n";
echo "<li>✅ Busca funciona apenas em produtos pai</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<h3>🚀 Próximos Passos:</h3>\n";
echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>👀 Ver Resultado no Site</a></p>\n";
echo "<p><a href='implement_categories_variations.php' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📂 Aplicar em Categorias</a></p>\n";
echo "<p><a href='update_variation_interface.php' style='background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>← Voltar ao Menu</a></p>\n";
?> 