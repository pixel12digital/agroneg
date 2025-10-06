<?php
/**
 * Script para limpar cache do sistema
 * Use este arquivo para limpar o cache quando necessário
 */

// Verificar se é uma chamada autorizada (adicione autenticação se necessário)
$authorized = isset($_GET['key']) && $_GET['key'] === 'agroneg2024';

if (!$authorized) {
    http_response_code(403);
    die('Acesso negado');
}

$cache_dir = __DIR__ . '/cache/';
$cleared_files = 0;

if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '*.json');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $cleared_files++;
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => "Cache limpo com sucesso. {$cleared_files} arquivo(s) removido(s).",
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
