<?php
// Configurações de cabeçalho para API
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *'); // Em produção, restrinja para o seu domínio
}

// Ativar logs de erro para debug
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// A API agora espera 'estado_id' em vez de 'estado' (sigla)
$estado_id = isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null;

if (!$estado_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['erro' => 'ID do estado inválido ou não especificado.']);
    exit;
}

// Sistema de cache para reduzir consultas
$cache_file = __DIR__ . '/../cache/municipios_' . $estado_id . '.json';
$cache_duration = 3600; // 1 hora

// Verificar se o cache existe e é válido
if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
    $municipios = json_decode(file_get_contents($cache_file), true);
} else {
    // Incluir arquivo de conexão com o banco de dados
    require_once(__DIR__ . '/../config/db.php');

    // Obter conexão quando necessário
    $conn = getAgronegConnection();

    // A consulta agora é direta, sem precisar buscar o ID primeiro
    $query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";

    $stmt_municipios = $conn->prepare($query_municipios);

    if (!$stmt_municipios) {
        error_log("Erro na preparação da consulta: " . $conn->error);
        http_response_code(500); // Internal Server Error
        echo json_encode(['erro' => 'Falha ao preparar a consulta de municípios.']);
        exit;
    }

    $stmt_municipios->bind_param("i", $estado_id);
    $stmt_municipios->execute();
    $resultado_municipios = $stmt_municipios->get_result();

    // Preparar o array de resposta
    $municipios = [];
    while ($municipio = $resultado_municipios->fetch_assoc()) {
        $municipios[] = [
            'id' => $municipio['id'],
            'nome' => $municipio['nome'],
        ];
    }

    // Criar diretório cache se não existir
    if (!is_dir(__DIR__ . '/../cache')) {
        mkdir(__DIR__ . '/../cache', 0755, true);
    }

    // Salvar no cache
    file_put_contents($cache_file, json_encode($municipios));

    // Fechar statement
    $stmt_municipios->close();
}

// Retornar os municípios como JSON
echo json_encode($municipios); 