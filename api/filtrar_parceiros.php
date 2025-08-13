<?php
// API para filtrar parceiros via AJAX
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir arquivo de conexão
$config_path = __DIR__ . "/../config/db.php";
if (file_exists($config_path)) {
    require_once($config_path);
} else {
    // Tentar caminho alternativo
    require_once("config/db.php");
}

// Validar e obter os IDs numéricos da URL
$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;

// Obter categorias para filtrar (tipos de parceiros)
$categorias_slug = isset($_GET['categorias']) ? explode(',', $_GET['categorias']) : [];

// Validar parâmetros
if (!$estado_id || !$municipio_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

// Obter informações do município
$query_municipio = "
    SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla
    FROM municipios m
    JOIN estados e ON m.estado_id = e.id
    WHERE e.id = ? AND m.id = ?
";
$stmt_municipio = $conn->prepare($query_municipio);
$stmt_municipio->bind_param("ii", $estado_id, $municipio_id);
$stmt_municipio->execute();
$resultado_municipio = $stmt_municipio->get_result();

if ($resultado_municipio->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['erro' => 'Município não encontrado']);
    exit;
}

$municipio = $resultado_municipio->fetch_assoc();

// Construir consulta para buscar parceiros
$params = [$municipio_id];
$types = 'i';

$sql_parceiros = "
    SELECT p.*, GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as categorias_parceiro, t.nome as tipo_nome, t.slug as tipo_slug
    FROM parceiros p
    LEFT JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
    LEFT JOIN categorias c ON pc.categoria_id = c.id
    JOIN tipos_parceiros t ON p.tipo_id = t.id
    WHERE p.municipio_id = ? AND p.status = 1
";

// Adicionar filtro de tipos de parceiros (categorias da URL), se houver
if (!empty($categorias_slug)) {
    // Mapear slugs para nomes de tipos
    $slug_to_nome = [
        'produtores' => 'Produtor',
        'criadores' => 'Criador', 
        'veterinarios' => 'Veterinário',
        'lojas-agropet' => 'Lojas Agropet',
        'cooperativas' => 'Cooperativas'
    ];
    
    // Mapear slugs da URL para slugs reais da tabela
    $slug_mapping = [
        'produtores' => 'produtores',
        'criadores' => 'criadores', 
        'veterinarios' => 'veterinarios',
        'lojas-agropet' => 'lojas-agropet',
        'cooperativas' => 'agroneg-cooper'
    ];
    
    $tipos_filtro = [];
    foreach ($categorias_slug as $slug) {
        if (isset($slug_mapping[$slug])) {
            $tipos_filtro[] = $slug_mapping[$slug];
        }
    }
    
    if (!empty($tipos_filtro)) {
        $placeholders = implode(',', array_fill(0, count($tipos_filtro), '?'));
        $sql_parceiros .= " AND t.slug IN ($placeholders)";
        foreach ($tipos_filtro as $tipo) {
            $params[] = $tipo;
            $types .= 's';
        }
    }
}

$sql_parceiros .= " GROUP BY p.id ORDER BY p.destaque DESC, p.nome ASC";

$stmt_parceiros = $conn->prepare($sql_parceiros);
if ($stmt_parceiros) {
    $stmt_parceiros->bind_param($types, ...$params);
    $stmt_parceiros->execute();
    $resultado_parceiros = $stmt_parceiros->get_result();
    $parceiros = $resultado_parceiros->fetch_all(MYSQLI_ASSOC);
} else {
    $parceiros = [];
}

// Gerar HTML dos parceiros
$html_parceiros = '';
if (!empty($parceiros)) {
    foreach ($parceiros as $parceiro) {
        $html_parceiros .= '<div class="parceiro-card ' . ($parceiro['destaque'] ? 'destaque' : '') . '">';
        $html_parceiros .= '<div class="parceiro-image">';
        
        if (!empty($parceiro['imagem_destaque'])) {
            $html_parceiros .= '<img src="uploads/parceiros/destaque/' . htmlspecialchars($parceiro['imagem_destaque']) . '" alt="' . htmlspecialchars($parceiro['nome']) . '">';
        } else {
            $html_parceiros .= '<img src="assets/img/placeholder.jpg" alt="' . htmlspecialchars($parceiro['nome']) . '">';
        }
        
        if ($parceiro['destaque']) {
            $html_parceiros .= '<span class="destaque-badge">Destaque</span>';
        }
        
        $html_parceiros .= '</div>';
        $html_parceiros .= '<div class="parceiro-content">';
        $html_parceiros .= '<h3 class="parceiro-title">' . htmlspecialchars($parceiro['nome']) . '</h3>';
        $html_parceiros .= '<div class="parceiro-categoria">' . htmlspecialchars($parceiro['categorias_parceiro']);
        if (isset($parceiro['tipo_nome'])) {
            $html_parceiros .= ' - ' . htmlspecialchars($parceiro['tipo_nome']);
        }
        $html_parceiros .= '</div>';
        
        if (!empty($parceiro['descricao'])) {
            $html_parceiros .= '<p class="parceiro-descricao">' . substr(htmlspecialchars($parceiro['descricao']), 0, 120) . '...</p>';
        }
        
        $html_parceiros .= '<a href="parceiro.php?slug=' . htmlspecialchars($parceiro['slug']) . '" class="btn-ver-mais">Ver detalhes</a>';
        $html_parceiros .= '</div>';
        $html_parceiros .= '</div>';
    }
}

// Gerar texto do contador
$contador_texto = '';
if (!empty($categorias_slug)) {
    $contador_texto = count($parceiros) . ' parceiro' . (count($parceiros) != 1 ? 's' : '') . ' encontrado' . (count($parceiros) != 1 ? 's' : '') . ' para ' . count($categorias_slug) . ' categoria' . (count($categorias_slug) != 1 ? 's' : '') . ' selecionada' . (count($categorias_slug) != 1 ? 's' : '');
} else {
    $contador_texto = '<span class="contador-numero">' . count($parceiros) . '</span> parceiro' . (count($parceiros) != 1 ? 's' : '') . ' encontrado' . (count($parceiros) != 1 ? 's' : '') . ' neste município';
}

// Retornar resposta JSON
echo json_encode([
    'sucesso' => true,
    'parceiros' => $parceiros,
    'html_parceiros' => $html_parceiros,
    'contador_texto' => $contador_texto,
    'total_parceiros' => count($parceiros),
    'filtros_aplicados' => $categorias_slug
]);
?> 