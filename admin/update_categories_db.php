<?php
// Script para atualizar a tabela categories com novos campos
// Executar apenas uma vez para atualizar o banco de dados

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

echo "<h2>Atualizando tabela categories...</h2>";

// Verificar se os campos já existem
$check_columns = "SHOW COLUMNS FROM categories LIKE 'display_type'";
$result = $conn->query($check_columns);

if ($result->num_rows == 0) {
    // Adicionar campo display_type (image ou icon)
    $sql1 = "ALTER TABLE categories ADD COLUMN display_type ENUM('image', 'icon') DEFAULT 'icon' AFTER image_url";
    
    if ($conn->query($sql1) === TRUE) {
        echo "✅ Campo 'display_type' adicionado com sucesso!<br>";
    } else {
        echo "❌ Erro ao adicionar campo 'display_type': " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Campo 'display_type' já existe.<br>";
}

// Verificar se o campo icon_name existe
$check_icon = "SHOW COLUMNS FROM categories LIKE 'icon_name'";
$result_icon = $conn->query($check_icon);

if ($result_icon->num_rows == 0) {
    // Adicionar campo icon_name
    $sql2 = "ALTER TABLE categories ADD COLUMN icon_name VARCHAR(100) DEFAULT 'fa-tags' AFTER display_type";
    
    if ($conn->query($sql2) === TRUE) {
        echo "✅ Campo 'icon_name' adicionado com sucesso!<br>";
    } else {
        echo "❌ Erro ao adicionar campo 'icon_name': " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Campo 'icon_name' já existe.<br>";
}

// Verificar se o campo title_display existe
$check_title = "SHOW COLUMNS FROM categories LIKE 'title_display'";
$result_title = $conn->query($check_title);

if ($result_title->num_rows == 0) {
    // Adicionar campo title_display para títulos personalizados
    $sql3 = "ALTER TABLE categories ADD COLUMN title_display VARCHAR(255) NULL AFTER icon_name";
    
    if ($conn->query($sql3) === TRUE) {
        echo "✅ Campo 'title_display' adicionado com sucesso!<br>";
    } else {
        echo "❌ Erro ao adicionar campo 'title_display': " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Campo 'title_display' já existe.<br>";
}

// Atualizar categorias existentes para usar ícones baseados no nome
echo "<br><h3>Atualizando categorias existentes...</h3>";

// Função para mapear categoria para ícone
function getIconForCategory($name) {
    $name_lower = strtolower(trim($name));
    
    $iconMap = [
        // Alimentos básicos
        'arroz' => 'fa-bowl-rice',
        'frijol' => 'fa-seedling',
        'maíz' => 'fa-corn',
        'trigo' => 'fa-wheat-awn',
        'harina' => 'fa-wheat-awn',
        'azúcar' => 'fa-cube',
        'sal' => 'fa-cube',
        'pan' => 'fa-bread-slice',
        'pasta' => 'fa-bowl-food',
        'avena' => 'fa-wheat-awn',
        'quinoa' => 'fa-seedling',
        'lentejas' => 'fa-seedling',
        'garbanzos' => 'fa-seedling',
        
        // Proteínas
        'carne' => 'fa-drumstick-bite',
        'pollo' => 'fa-drumstick-bite',
        'pescado' => 'fa-fish',
        'huevo' => 'fa-egg',
        'leche' => 'fa-glass-water',
        'queso' => 'fa-cheese',
        'yogurt' => 'fa-ice-cream',
        'mantequilla' => 'fa-butter',
        'jamón' => 'fa-bacon',
        'salchicha' => 'fa-hotdog',
        'chorizo' => 'fa-sausage',
        
        // Frutas
        'fruta' => 'fa-apple-whole',
        'manzana' => 'fa-apple-whole',
        'banana' => 'fa-banana',
        'naranja' => 'fa-orange',
        'limón' => 'fa-lemon',
        'fresa' => 'fa-strawberry',
        'uva' => 'fa-grapes',
        'piña' => 'fa-pineapple',
        'mango' => 'fa-mango',
        'pera' => 'fa-pear',
        'durazno' => 'fa-peach',
        'sandía' => 'fa-watermelon',
        'melón' => 'fa-melon',
        'cereza' => 'fa-cherry',
        
        // Vegetais
        'verdura' => 'fa-leaf',
        'legumbre' => 'fa-carrot',
        'tomate' => 'fa-tomato',
        'cebolla' => 'fa-onion',
        'zanahoria' => 'fa-carrot',
        'papa' => 'fa-potato',
        'lechuga' => 'fa-lettuce',
        'espinaca' => 'fa-leaf',
        'brócoli' => 'fa-broccoli',
        'coliflor' => 'fa-cauliflower',
        'pepino' => 'fa-cucumber',
        'pimiento' => 'fa-pepper-hot',
        'apio' => 'fa-celery',
        'remolacha' => 'fa-beet',
        'calabaza' => 'fa-pumpkin',
        'choclo' => 'fa-corn',
        'ajo' => 'fa-garlic',
        'perejil' => 'fa-leaf',
        'cilantro' => 'fa-leaf',
        'albahaca' => 'fa-leaf',
        
        // Bebidas
        'agua' => 'fa-glass-water',
        'refresco' => 'fa-bottle-water',
        'jugo' => 'fa-glass-citrus',
        'té' => 'fa-mug-hot',
        'café' => 'fa-mug-hot',
        'yerba' => 'fa-leaf',
        'cerveza' => 'fa-beer',
        'vino' => 'fa-wine-glass',
        'bebida' => 'fa-glass-water',
        'energética' => 'fa-bolt',
        'isotónica' => 'fa-dumbbell',
        'gaseosa' => 'fa-bottle-water',
        
        // Condimentos y especias
        'aceite' => 'fa-oil-can',
        'vinagre' => 'fa-bottle-droplet',
        'condimento' => 'fa-mortar-pestle',
        'especia' => 'fa-pepper-hot',
        'pimienta' => 'fa-pepper-hot',
        'orégano' => 'fa-leaf',
        'comino' => 'fa-seedling',
        'canela' => 'fa-tree',
        'vainilla' => 'fa-seedling',
        'mostaza' => 'fa-jar',
        'ketchup' => 'fa-bottle-droplet',
        'mayonesa' => 'fa-jar',
        'salsa' => 'fa-bottle-droplet',
        
        // Dulces y postres
        'dulce' => 'fa-candy-cane',
        'chocolate' => 'fa-chocolate-bar',
        'caramelo' => 'fa-candy',
        'galleta' => 'fa-cookie',
        'torta' => 'fa-cake-candles',
        'helado' => 'fa-ice-cream',
        'mermelada' => 'fa-jar',
        'miel' => 'fa-honeypot',
        'gelatina' => 'fa-cube',
        'pudín' => 'fa-bowl-food',
        'flan' => 'fa-ice-cream',
        'golosina' => 'fa-candy',
        
        // Productos de limpieza
        'limpieza' => 'fa-spray-can',
        'jabón' => 'fa-soap',
        'detergente' => 'fa-bottle-droplet',
        'desinfectante' => 'fa-spray-can',
        'lavandina' => 'fa-bottle-droplet',
        'suavizante' => 'fa-bottle-droplet',
        'limpiador' => 'fa-spray-can',
        'esponja' => 'fa-sponge',
        'trapo' => 'fa-broom',
        'escoba' => 'fa-broom',
        'papel' => 'fa-toilet-paper',
        'pañal' => 'fa-baby',
        'toalla' => 'fa-towel',
        
        // Higiene personal
        'higiene' => 'fa-pump-soap',
        'shampoo' => 'fa-bottle-droplet',
        'acondicionador' => 'fa-bottle-droplet',
        'crema' => 'fa-jar',
        'loción' => 'fa-bottle-droplet',
        'desodorante' => 'fa-spray-can',
        'perfume' => 'fa-spray-can',
        'cepillo' => 'fa-brush',
        'peine' => 'fa-comb',
        'pasta' => 'fa-toothbrush',
        'enjuague' => 'fa-bottle-droplet',
        'protector' => 'fa-sun',
        'bronceador' => 'fa-sun',
        
        // Otros productos
        'conserva' => 'fa-can-food',
        'enlatado' => 'fa-can-food',
        'congelado' => 'fa-snowflake',
        'cereales' => 'fa-bowl-food',
        'snack' => 'fa-cookie-bite',
        'frutos' => 'fa-nuts',
        'nuez' => 'fa-nuts',
        'almendra' => 'fa-nuts',
        'maní' => 'fa-peanuts',
        'semilla' => 'fa-seedling',
        'integral' => 'fa-wheat-awn',
        'orgánico' => 'fa-leaf',
        'natural' => 'fa-leaf',
        'dietético' => 'fa-weight-scale',
        'light' => 'fa-feather',
        'sin' => 'fa-ban',
        'gluten' => 'fa-wheat-awn-slash',
        'vegano' => 'fa-leaf',
        'vegetariano' => 'fa-leaf',
        'mascotas' => 'fa-paw',
        'perro' => 'fa-dog',
        'gato' => 'fa-cat',
        'pájaro' => 'fa-dove',
        'pez' => 'fa-fish'
    ];
    
    // Verificar se alguma palavra-chave do nome da categoria corresponde a um ícone
    foreach ($iconMap as $keyword => $icon) {
        if (strpos($name_lower, $keyword) !== false) {
            return $icon;
        }
    }
    
    // Ícone padrão se nenhuma correspondência for encontrada
    return 'fa-tags';
}

// Buscar todas as categorias existentes
$categories_query = "SELECT id, name FROM categories";
$categories_result = $conn->query($categories_query);

if ($categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $icon = getIconForCategory($category['name']);
        
        // Atualizar categoria com ícone apropriado
        $update_sql = "UPDATE categories SET display_type = 'icon', icon_name = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $icon, $category['id']);
        
        if ($stmt->execute()) {
            echo "✅ Categoria '{$category['name']}' atualizada com ícone '{$icon}'<br>";
        } else {
            echo "❌ Erro ao atualizar categoria '{$category['name']}': " . $conn->error . "<br>";
        }
    }
} else {
    echo "ℹ️ Nenhuma categoria encontrada para atualizar.<br>";
}

echo "<br><h3>✅ Atualização concluída!</h3>";
echo "<p>Agora você pode usar o painel administrativo para escolher entre imagem ou ícone para cada categoria.</p>";
echo "<p><a href='categorias.php'>← Voltar para Categorias</a></p>";

$conn->close();
?> 