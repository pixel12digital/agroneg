<?php
// RESET SIMPLES DO SISTEMA
echo "<h1>🔄 RESET DO SISTEMA AGROneg</h1>";

// Remover arquivos de bloqueio
$arquivos = ['.emergency_block', '.apis_disabled', 'config/.db_blocked'];
foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        unlink($arquivo);
        echo "<p>✅ Removido: $arquivo</p>";
    }
}

echo "<h2>📊 STATUS ATUAL:</h2>";
echo "<p>Emergency Block: " . (file_exists('.emergency_block') ? '❌ ATIVO' : '✅ REMOVIDO') . "</p>";
echo "<p>APIs Disabled: " . (file_exists('.apis_disabled') ? '❌ ATIVO' : '✅ REMOVIDO') . "</p>";
echo "<p>DB Blocked: " . (file_exists('config/.db_blocked') ? '❌ ATIVO' : '✅ REMOVIDO') . "</p>";

echo "<h2>🎯 PRÓXIMOS PASSOS:</h2>";
echo "<p>1. <a href='index.php'>Testar página inicial</a></p>";
echo "<p>2. <a href='status-sistema.php'>Verificar status do sistema</a></p>";
echo "<p>3. <a href='municipio.php'>Testar página de município</a></p>";
?>

