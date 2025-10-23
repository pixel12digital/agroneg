<?php
/**
 * RESET COMPLETO DO SISTEMA - AgroNeg
 * Remove todos os bloqueios e reseta o circuit breaker
 */

echo "🔄 RESETANDO SISTEMA AGROneg...\n\n";

// Remover arquivos de bloqueio
$arquivos_bloqueio = [
    '.emergency_block',
    '.apis_disabled', 
    'config/.db_blocked',
    '.global_rate_limit',
    'cache/.cleanup_lock',
    'cache/.last_cleanup'
];

echo "🗑️ Removendo arquivos de bloqueio...\n";
foreach ($arquivos_bloqueio as $arquivo) {
    if (file_exists($arquivo)) {
        unlink($arquivo);
        echo "   ✅ Removido: $arquivo\n";
    } else {
        echo "   ⚪ Não encontrado: $arquivo\n";
    }
}

// Limpar cache
echo "\n🧹 Limpando cache...\n";
if (is_dir('cache')) {
    $files = glob('cache/*.cache');
    foreach ($files as $file) {
        unlink($file);
        echo "   ✅ Cache removido: " . basename($file) . "\n";
    }
}

// Limpar rate limit
echo "\n🚫 Limpando rate limit...\n";
if (is_dir('cache/rate_limit')) {
    $files = glob('cache/rate_limit/*.json');
    foreach ($files as $file) {
        unlink($file);
        echo "   ✅ Rate limit removido: " . basename($file) . "\n";
    }
}

echo "\n✅ RESET COMPLETO REALIZADO!\n";
echo "🎯 Sistema deve estar funcionando normalmente agora.\n";
echo "🌐 Acesse: http://localhost/Agroneg/\n\n";

// Verificar status
echo "📊 STATUS ATUAL:\n";
echo "   - Emergency Block: " . (file_exists('.emergency_block') ? '❌ ATIVO' : '✅ REMOVIDO') . "\n";
echo "   - APIs Disabled: " . (file_exists('.apis_disabled') ? '❌ ATIVO' : '✅ REMOVIDO') . "\n";
echo "   - DB Blocked: " . (file_exists('config/.db_blocked') ? '❌ ATIVO' : '✅ REMOVIDO') . "\n";
echo "   - Cache: " . (is_dir('cache') ? '✅ DISPONÍVEL' : '❌ INDISPONÍVEL') . "\n";
?>

