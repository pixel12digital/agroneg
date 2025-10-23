<?php
// API que tenta primeiro o banco de dados e usa fallback com dados estáticos

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

// Verificar rate limit (30 requisições por minuto - mais tolerante)
checkRateLimit(30, 60);

// Configurações de cabeçalho para API
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

$estado_id = isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null;

if (!$estado_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do estado inválido ou não especificado.']);
    exit;
}

$municipios = [];

// Tentar conectar ao banco de dados primeiro
try {
    require_once(__DIR__ . '/../config/db.php');
    $conn = getAgronegConnection();
    
    if ($conn) {
        $query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
        $stmt_municipios = $conn->prepare($query_municipios);
        
        if ($stmt_municipios) {
            $stmt_municipios->bind_param("i", $estado_id);
            $stmt_municipios->execute();
            $resultado_municipios = $stmt_municipios->get_result();
            
            while ($municipio = $resultado_municipios->fetch_assoc()) {
                $municipios[] = [
                    'id' => $municipio['id'],
                    'nome' => $municipio['nome'],
                    'slug' => strtolower(str_replace([' ', '-'], ['-', '-'], $municipio['nome']))
                ];
            }
            $stmt_municipios->close();
        }
        $conn->close();
    }
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco: " . $e->getMessage());
}

// Se não conseguiu carregar do banco, usar dados mínimos apenas dos que existem
if (empty($municipios)) {
    $municipios_por_estado = [
        6 => [ // Ceará - apenas os que existem no banco
            ['id' => 3, 'nome' => 'Iracema', 'slug' => 'iracema'],
        ],
        15 => [ // Paraíba - apenas os que existem no banco
            ['id' => 1, 'nome' => 'Barra de São Miguel', 'slug' => 'barra-de-sao-miguel'],
            ['id' => 2, 'nome' => 'João Pessoa', 'slug' => 'joao-pessoa'],
        ],
        17 => [ // Pernambuco - apenas os que existem no banco
            ['id' => 2, 'nome' => 'Santa Cruz do Capibaribe', 'slug' => 'santa-cruz-do-capibaribe'],
            ['id' => 5, 'nome' => 'Jataúba', 'slug' => 'jatauba'],
        ],
        20 => [ // Rio Grande do Norte - apenas os que existem no banco
            ['id' => 4, 'nome' => 'Mossoró', 'slug' => 'mossoro'],
        ]
    ];
    
    $municipios = isset($municipios_por_estado[$estado_id]) ? $municipios_por_estado[$estado_id] : [];
}

// Ordenar por nome
usort($municipios, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Retornar os municípios como JSON
echo json_encode($municipios);
?>
