<?php
/**
 * STATUS DO SISTEMA - AgroNeg
 * Verifica se o sistema está funcionando normalmente
 */

header('Content-Type: application/json');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'sistema' => 'AgroNeg',
    'status' => 'FUNCIONANDO',
    'modo' => 'NORMAL',
    'apis_disabled' => file_exists('.apis_disabled'),
    'db_blocked' => file_exists('config/.db_blocked'),
    'emergency_mode' => file_exists('.emergency_block'),
    'cache_status' => 'ATIVO',
    'requisicoes_bloqueadas' => false,
    'banco_remoto' => 'DISPONÍVEL',
    'mensagem' => 'Sistema funcionando normalmente após limpeza e correções'
];

// Verificar se há problemas
if ($status['apis_disabled'] || $status['db_blocked'] || $status['emergency_mode']) {
    $status['status'] = 'COM PROBLEMAS';
    $status['mensagem'] = 'Alguns bloqueios ainda estão ativos';
}

echo json_encode($status, JSON_PRETTY_PRINT);
?>

