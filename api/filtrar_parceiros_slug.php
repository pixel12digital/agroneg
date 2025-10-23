<?php
// API para filtrar parceiros via AJAX usando slugs

// Verificar se APIs estão desabilitadas
$control_file = __DIR__ . '/../.apis_disabled';
if (file_exists($control_file)) {
    $disabled_until = (int)file_get_contents($control_file);
    if (time() < $disabled_until) {
        http_response_code(503);
        echo json_encode(['erro' => 'APIs temporariamente desabilitadas. Tente novamente em ' . ($disabled_until - time()) . ' segundos.']);
        exit;
    } else {
        // Tempo expirado, remover arquivo
        unlink($control_file);
    }
}

// Incluir rate limiter
require_once(__DIR__ . '/../config/rate_limiter.php');

// Verificar rate limit (20 requisições por minuto para filtros - mais tolerante)
checkRateLimit(20, 60);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir arquivo de conexão com banco de dados
$config_path = __DIR__ . "/../config/db.php";
if (file_exists($config_path)) {
    require_once($config_path);
} else {
    // Tentar caminho alternativo
    require_once("config/db.php");
}

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se a conexão foi estabelecida
if (!$conn) {
    http_response_code(503);
    echo json_encode(['erro' => 'Serviço temporariamente indisponível']);
    exit;
}

// Validar e obter os slugs da URL
$slug_estado = isset($_GET['slug_estado']) ? trim($_GET['slug_estado']) : null;
$slug_municipio = isset($_GET['slug_municipio']) ? trim($_GET['slug_municipio']) : null;

// Obter categorias para filtrar (tipos de parceiros)
$categorias_slug = isset($_GET['categorias']) ? explode(',', $_GET['categorias']) : [];

// Validar parâmetros
if (!$slug_estado || !$slug_municipio) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

// MODO EMERGÊNCIA: Usar APENAS cache estático (SEM BANCO)
require_once(__DIR__ . '/../config/static_cache.php');

$municipio = StaticCache::getMunicipioStatic($slug_estado, $slug_municipio);
$parceiros = [];

if ($municipio) {
    // MODO OFFLINE: Usar apenas cache estático
    $parceiros = StaticCache::getParceirosStatic($slug_estado, $slug_municipio, $categorias_slug);
} else {
    // SEM CACHE: Fazer consulta direta no banco
    $query = "
        SELECT m.id as municipio_id, e.id as estado_id, m.nome as municipio_nome, e.nome as estado_nome
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $slug_estado, $slug_municipio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estado_id = $row['estado_id'];
        $municipio_id = $row['municipio_id'];
        $municipio = [
            'nome' => $row['municipio_nome'],
            'estado_nome' => $row['estado_nome'],
            'estado_sigla' => strtoupper($slug_estado)
        ];
        
        // Construir consulta de parceiros
        $where_conditions = ["p.status = 1", "p.municipio_id = ?"];
        $params = [$municipio_id];
        $types = 'i';
        
        // Adicionar filtro de categorias se especificado
        if (!empty($categorias_slug)) {
            $slug_mapping = [
                'produtores' => 'produtores',
                'criadores' => 'criadores', 
                'veterinarios' => 'veterinarios',
                'lojas-agropet' => 'lojas-agropet',
                'cooperativas' => 'agroneg-cooper'
            ];
            
            $tipo_slugs = [];
            foreach ($categorias_slug as $categoria) {
                if (isset($slug_mapping[$categoria])) {
                    $tipo_slugs[] = $slug_mapping[$categoria];
                }
            }
            
            if (!empty($tipo_slugs)) {
                $placeholders = str_repeat('?,', count($tipo_slugs) - 1) . '?';
                $where_conditions[] = "t.slug IN ($placeholders)";
                $params = array_merge($params, $tipo_slugs);
                $types .= str_repeat('s', count($tipo_slugs));
            }
        }
        
        $sql = "
            SELECT p.*, GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as categorias_parceiro, t.nome as tipo_nome, t.slug as tipo_slug
            FROM parceiros p
            LEFT JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
            LEFT JOIN categorias c ON pc.categoria_id = c.id
            JOIN tipos_parceiros t ON p.tipo_id = t.id
            WHERE " . implode(' AND ', $where_conditions) . "
            GROUP BY p.id
            ORDER BY p.destaque DESC, p.nome ASC
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $parceiros = [];
            while ($row = $result->fetch_assoc()) {
                $parceiros[] = $row;
            }
        } else {
            $parceiros = [];
        }
    } else {
        $parceiros = [];
        $municipio = [
            'nome' => ucfirst(str_replace('-', ' ', $slug_municipio)),
            'estado_nome' => ucfirst($slug_estado),
            'estado_sigla' => strtoupper($slug_estado)
        ];
    }
}

// Gerar HTML dos parceiros
$html_parceiros = '';
if (!empty($parceiros)) {
    foreach ($parceiros as $parceiro) {
        $html_parceiros .= '<div class="parceiro-card ' . ($parceiro['destaque'] ? 'destaque' : '') . '">';
        $html_parceiros .= '<div class="parceiro-image">';
        
        if (!empty($parceiro['imagem_destaque'])) {
            $html_parceiros .= '<img src="/Agroneg/uploads/parceiros/destaque/' . htmlspecialchars($parceiro['imagem_destaque']) . '" alt="' . htmlspecialchars($parceiro['nome']) . '">';
        } else {
            $html_parceiros .= '<img src="/Agroneg/assets/img/placeholder.jpg" alt="' . htmlspecialchars($parceiro['nome']) . '">';
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
        
        $html_parceiros .= '<a href="/Agroneg/parceiro/' . htmlspecialchars($parceiro['slug']) . '" class="btn-ver-mais">Ver detalhes</a>';
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

