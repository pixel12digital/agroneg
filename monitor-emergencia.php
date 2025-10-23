<?php
/**
 * MONITOR DE PERFORMANCE - AgroNeg
 * Verifica status do sistema e requisições
 */

header('Content-Type: application/json');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'sistema' => 'AgroNeg',
    'status' => 'EMERGÊNCIA',
    'modo' => 'ECONOMIA ATIVO',
    'apis_disabled' => file_exists('.apis_disabled'),
    'db_blocked' => file_exists('config/.db_blocked'),
    'emergency_mode' => file_exists('.emergency_block'),
    'cache_status' => 'ATIVO',
    'requisicoes_bloqueadas' => true,
    'banco_remoto' => 'PROTEGIDO',
    'mensagem' => 'Sistema em modo de economia para proteger o banco remoto'
];

// Verificar arquivos de controle
if (file_exists('.apis_disabled')) {
    $disabled_until = (int)file_get_contents('.apis_disabled');
    $status['apis_disabled_until'] = date('Y-m-d H:i:s', $disabled_until);
}

if (file_exists('config/.db_blocked')) {
    $blocked_until = (int)file_get_contents('config/.db_blocked');
    $status['db_blocked_until'] = date('Y-m-d H:i:s', $blocked_until);
}

if (file_exists('.emergency_block')) {
    $emergency_until = (int)file_get_contents('.emergency_block');
    $status['emergency_until'] = date('Y-m-d H:i:s', $emergency_until);
}

echo json_encode($status, JSON_PRETTY_PRINT);
?>
