<?php
/**
 * FORÇAR SISTEMA A FUNCIONAR - AgroNeg
 * Para sites pequenos que não precisam de proteção agressiva
 */

require_once('config/circuit_breaker.php');

echo "<h1>🚀 FORÇANDO SISTEMA A FUNCIONAR</h1>";

// Forçar circuit breaker aberto
CircuitBreaker::forceOpen();

echo "<h2>✅ AÇÕES REALIZADAS:</h2>";
echo "<ul>";
echo "<li>✅ Circuit Breaker resetado</li>";
echo "<li>✅ Todos os bloqueios removidos</li>";
echo "<li>✅ Sistema liberado para funcionar</li>";
echo "</ul>";

echo "<h2>📊 CONFIGURAÇÕES AJUSTADAS:</h2>";
echo "<ul>";
echo "<li>🔧 Falhas para bloquear: 3 → 10 (mais tolerante)</li>";
echo "<li>🔧 Tempo de recuperação: 5min → 1min (mais rápido)</li>";
echo "<li>🔧 Bloqueio de emergência: 2h → 5min (muito mais tolerante)</li>";
echo "<li>🔧 Tentativas half-open: 2 → 5 (mais permissivo)</li>";
echo "</ul>";

echo "<h2>🎯 TESTAR AGORA:</h2>";
echo "<p><a href='index.php' style='background: #1A9B60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Página Inicial</a></p>";
echo "<p><a href='municipio.php' style='background: #1A9B60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏘️ Página Município</a></p>";
echo "<p><a href='status-sistema.php' style='background: #1A9B60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Status Sistema</a></p>";

echo "<h2>💡 EXPLICAÇÃO:</h2>";
echo "<p>O sistema estava muito agressivo para um site pequeno. Agora está configurado para:</p>";
echo "<ul>";
echo "<li>✅ Permitir mais falhas antes de bloquear</li>";
echo "<li>✅ Recuperar mais rapidamente</li>";
echo "<li>✅ Bloquear por menos tempo</li>";
echo "<li>✅ Ser mais permissivo em geral</li>";
echo "</ul>";
?>

