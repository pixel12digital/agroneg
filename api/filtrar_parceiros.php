<?php
// API para filtrar parceiros via AJAX

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

// Usar cache para buscar município por IDs
$cache_key = "municipio_by_ids_{$estado_id}_{$municipio_id}";
$municipio = executeQueryWithCache(
    "SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla FROM municipios m JOIN estados e ON m.estado_id = e.id WHERE e.id = ? AND m.id = ?",
    [$estado_id, $municipio_id],
    $cache_key,
    7200 // 2 horas
);

if (!$municipio || empty($municipio)) {
    http_response_code(404);
    echo json_encode(['erro' => 'Município não encontrado']);
    exit;
}

$municipio = $municipio[0]; // Pegar primeiro resultado

// Usar função otimizada para buscar parceiros (com cache)
// Detectar tipo de página baseado na URL ou parâmetro
$tipo_slug = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'produtores';

// Filtrar por tipo específico se não especificar categorias
if (empty($categorias_slug)) {
    $categorias_slug = [$tipo_slug]; // Por padrão, mostrar apenas o tipo da página atual
}
$parceiros = getParceirosByMunicipio($municipio_id, $categorias_slug);

if ($parceiros === false) {
    $parceiros = [];
}

// Gerar HTML dos parceiros
$html_parceiros = '';
if (!empty($parceiros)) {
    foreach ($parceiros as $parceiro) {
        $html_parceiros .= '<div class="parceiro-card ' . ($parceiro['destaque'] ? 'destaque' : '') . '">';
        $html_parceiros .= '<div class="parceiro-image">';
        
        if (!empty($parceiro['imagem_destaque'])) {
            $html_parceiros .= '<img src="/uploads/parceiros/destaque/' . htmlspecialchars($parceiro['imagem_destaque']) . '" alt="' . htmlspecialchars($parceiro['nome']) . '">';
        } else {
            $html_parceiros .= '<img src="/assets/img/placeholder.svg" alt="' . htmlspecialchars($parceiro['nome']) . '">';
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
        
        $html_parceiros .= '<a href="/parceiro/' . htmlspecialchars($parceiro['slug']) . '" class="btn-ver-mais">Ver detalhes</a>';
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