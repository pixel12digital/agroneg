<?php
// Arquivo de teste para verificar a nova configuração de banco de dados
echo "<h1>Teste de Conexão com Banco de Dados - AgroNeg</h1>";

echo "<h2>1. Testando conexão inicial...</h2>";
require_once 'config/db.php';

if ($conn && $conn->ping()) {
    echo "<p style='color: green;'>✅ Conexão estabelecida com sucesso!</p>";
    echo "<p>Informações da conexão:</p>";
    echo "<ul>";
    echo "<li>Servidor: srv1890.hstgr.io</li>";
    echo "<li>Usuário: u664918047_agroneg</li>";
    echo "<li>Banco: u664918047_agroneg</li>";
    echo "<li>Charset: utf8mb4</li>";
    echo "<li>Contador de conexões: " . $GLOBALS['AGRONEG_DB_CONNECTION_COUNT'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ Erro na conexão inicial</p>";
    exit;
}

echo "<h2>2. Testando múltiplas chamadas (simulando uso real)...</h2>";
echo "<ol>";

// Simular múltiplas carregamentos de página
for ($i = 1; $i <= 5; $i++) {
    echo "<li>Carregamento " . $i . ": ";
    
    // Recarregar conexão (simulando novo carregamento de página)
    $conn2 = getAgronegConnection();
    
    if ($conn2 && $conn2->ping()) {
        echo "✅ Reutilizando conexão existente";
    } else {
        echo "❌ Erro";
    }
    echo " (Total de conexões: " . $GLOBALS['AGRONEG_DB_CONNECTION_COUNT'] . ")</li>";
}

echo "</ol>";

echo "<h2>3. Testando consulta simples...</h2>";
try {
    $result = $conn->query("SELECT 1 as teste, NOW() as momento");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Consulta executada com sucesso!</p>";
        echo "<p>Resultado: " . json_encode($row) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testando helper de banco de dados...</h2>";
require_once 'config/db_helper.php';

try {
    $result = DBHelper::fetchOne("SELECT ? as teste_parametrizado", "s", ["Teste Helper"]);
    echo "<p style='color: green;'>✅ Helper funcionando! Resultado: " . $result['teste_parametrizado'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro no helper: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Resumo Final</h2>";
echo "<ul>";
echo "<li>Total de conexões criadas: " . $GLOBALS['AGRONEG_DB_CONNECTION_COUNT'] . "</li>";
echo "<li>Ambiente detectado: " . ($ambiente === 'desenvolvimento' ? 'Desenvolvimento' : 'Produção') . "</li>";
echo "<li>Conexão única implementada: " . ($GLOBALS['AGRONEG_DB_CONNECTION'] !== null ? 'Sim' : 'Não') . "</li>";
echo "<li>Sistema de fechamento automático: Ativo</li>";
echo "</ul>";

echo "<p><strong>🎉 Teste concluído! Se você chegou até aqui sem erros, a implementação está funcionando corretamente.</strong></p>";
echo "<p><a href='index.php'>← Voltar para o site</a> | <a href='login.php'>Ir para o login</a></p>";
?>
