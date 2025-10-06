<?php
/**
 * Arquivo de diagnóstico para verificar status da produção
 * Acesso: status_producao.php?key=agroneg2024
 */

// Verificar autorização
$authorized = isset($_GET['key']) && $_GET['key'] === 'agroneg2024';

if (!$authorized) {
    http_response_code(403);
    die('Acesso negado');
}

header('Content-Type: application/json; charset=utf-8');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ambiente' => 'produção',
    'checks' => []
];

// 1. Verificar conexão com banco de dados
try {
    require_once('config/db.php');
    $conn = getAgronegConnection();
    
    if ($conn && $conn->ping()) {
        $status['checks']['database'] = [
            'status' => 'OK',
            'connection' => 'Ativa',
            'server' => 'srv1890.hstgr.io'
        ];
        
        // Testar uma consulta simples
        $result = $conn->query("SELECT COUNT(*) as total FROM estados");
        if ($result) {
            $row = $result->fetch_assoc();
            $status['checks']['database']['estados_count'] = $row['total'];
        }
    } else {
        $status['checks']['database'] = [
            'status' => 'ERRO',
            'message' => 'Falha na conexão com banco de dados'
        ];
    }
} catch (Exception $e) {
    $status['checks']['database'] = [
        'status' => 'ERRO',
        'message' => $e->getMessage()
    ];
}

// 2. Verificar sistema de cache
$cache_dir = __DIR__ . '/cache/';
$cache_files = [];
$cache_total_size = 0;

if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '*.json');
    foreach ($files as $file) {
        $cache_files[] = [
            'file' => basename($file),
            'size' => filesize($file),
            'modified' => date('Y-m-d H:i:s', filemtime($file)),
            'age_minutes' => round((time() - filemtime($file)) / 60)
        ];
        $cache_total_size += filesize($file);
    }
}

$status['checks']['cache'] = [
    'status' => 'OK',
    'directory_exists' => is_dir($cache_dir),
    'files_count' => count($cache_files),
    'total_size_bytes' => $cache_total_size,
    'files' => $cache_files
];

// 3. Verificar arquivos críticos
$critical_files = [
    'config/db.php',
    'login.php',
    'index.php',
    'municipio.php',
    'api/get_municipios.php'
];

$files_status = [];
foreach ($critical_files as $file) {
    $files_status[$file] = [
        'exists' => file_exists($file),
        'readable' => is_readable($file),
        'size' => file_exists($file) ? filesize($file) : 0,
        'modified' => file_exists($file) ? date('Y-m-d H:i:s', filemtime($file)) : null
    ];
}

$status['checks']['files'] = $files_status;

// 4. Verificar APIs
$api_status = [];

// Testar API de estados (via cache)
if (file_exists($cache_dir . 'estados.json')) {
    $estados_cache = json_decode(file_get_contents($cache_dir . 'estados.json'), true);
    $api_status['estados_cache'] = [
        'status' => 'OK',
        'count' => count($estados_cache),
        'source' => 'cache'
    ];
} else {
    $api_status['estados_cache'] = [
        'status' => 'PENDING',
        'message' => 'Cache ainda não foi criado'
    ];
}

$status['checks']['apis'] = $api_status;

// 5. Informações do servidor
$status['server_info'] = [
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'server_timezone' => date_default_timezone_get()
];

// 6. Resumo geral
$overall_status = 'OK';
$errors = [];

foreach ($status['checks'] as $check_name => $check_data) {
    if (isset($check_data['status']) && $check_data['status'] === 'ERRO') {
        $overall_status = 'ERRO';
        $errors[] = $check_name . ': ' . ($check_data['message'] ?? 'Erro desconhecido');
    }
}

$status['overall_status'] = $overall_status;
$status['errors'] = $errors;

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
