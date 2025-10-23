<?php
// API para buscar categorias de parceiros por município e tipo

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

// Verificar rate limit (30 requisições por minuto para categorias)
checkRateLimit(30, 60);

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
$tipo_slug = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'produtores';

// Validar parâmetros
if (!$estado_id || !$municipio_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Buscar categorias dos parceiros no município específico
    $sql = "
        SELECT DISTINCT c.id, c.nome, c.slug, COUNT(p.id) as total_parceiros
        FROM categorias c
        INNER JOIN parceiros_categorias pc ON c.id = pc.categoria_id
        INNER JOIN parceiros p ON pc.parceiro_id = p.id
        INNER JOIN tipos_parceiros t ON p.tipo_id = t.id
        WHERE p.municipio_id = ? AND p.status = 1 AND t.slug = ?
        GROUP BY c.id, c.nome, c.slug
        ORDER BY total_parceiros DESC, c.nome ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $municipio_id, $tipo_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'slug' => $row['slug'],
            'total_parceiros' => $row['total_parceiros']
        ];
    }
    
    $stmt->close();
    
    // Retornar resposta JSON
    echo json_encode([
        'sucesso' => true,
        'categorias' => $categorias,
        'total_categorias' => count($categorias),
        'municipio_id' => $municipio_id,
        'tipo' => $tipo_slug
    ]);
    
} catch (Exception $e) {
    error_log("Erro na API get_categorias_municipio: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>
