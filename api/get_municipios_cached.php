<?php
// API com cache para reduzir conexões ao banco
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Verificar se estado_id foi fornecido
$estado_id = isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null;

if (!$estado_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do estado inválido ou não especificado.']);
    exit;
}

// Sistema de cache simples
$cache_file = __DIR__ . '/../cache/municipios_' . $estado_id . '.json';
$cache_duration = 3600; // 1 hora

// Verificar se existe cache válido
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
    // Retornar dados do cache
    echo file_get_contents($cache_file);
    exit;
}

// Se não há cache válido, conectar ao banco
try {
    // Incluir configuração de banco de dados
    require_once(__DIR__ . '/../config/db.php');
    
    // Obter conexão
    $conn = getAgronegConnection();
    
    if (!$conn) {
        // Se não conseguir conectar, tentar usar cache antigo se existir
        if (file_exists($cache_file)) {
            echo file_get_contents($cache_file);
            exit;
        }
        
        http_response_code(503);
        echo json_encode(['erro' => 'Serviço temporariamente indisponível. Tente novamente em alguns minutos.']);
        exit;
    }
    
    // Consulta
    $query = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        $conn->close();
        http_response_code(500);
        echo json_encode(['erro' => 'Falha ao preparar a consulta.']);
        exit;
    }
    
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $municipios = [];
    while ($municipio = $result->fetch_assoc()) {
        $municipios[] = [
            'id' => $municipio['id'],
            'nome' => $municipio['nome'],
        ];
    }
    
    $json_result = json_encode($municipios);
    
    // Salvar no cache
    if (!is_dir(__DIR__ . '/../cache')) {
        mkdir(__DIR__ . '/../cache', 0755, true);
    }
    file_put_contents($cache_file, $json_result);
    
    echo $json_result;
    $stmt->close();
    
} catch (Exception $e) {
    // Se houver erro, tentar usar cache antigo
    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file);
        exit;
    }
    
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
}
?>
