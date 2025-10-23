<?php
/**
 * MONITOR DE REQUISIÇÕES - AgroNeg
 * Identifica exatamente onde estão as requisições excessivas ao banco
 */

// Ativar logs detalhados
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/requisicoes.log');

echo "<h1>🔍 MONITOR DE REQUISIÇÕES AO BANCO</h1>";

// Função para interceptar chamadas de conexão
function monitorConnection($function_name, $file, $line) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] CONEXÃO: $function_name em $file:$line\n";
    file_put_contents(__DIR__ . '/requisicoes.log', $log_entry, FILE_APPEND);
    echo "<p>🔗 <strong>$function_name</strong> chamado em <code>$file:$line</code></p>";
}

// Interceptar getAgronegConnection
$original_getConnection = null;
if (function_exists('getAgronegConnection')) {
    $original_getConnection = 'getAgronegConnection';
}

// Sobrescrever temporariamente para monitorar
function getAgronegConnectionMonitored() {
    $backtrace = debug_backtrace();
    $caller = $backtrace[1];
    monitorConnection('getAgronegConnection', $caller['file'], $caller['line']);
    
    // Chamar função original
    require_once(__DIR__ . '/config/db.php');
    return getAgronegConnection();
}

echo "<h2>📊 ANÁLISE DE REQUISIÇÕES:</h2>";

// Verificar arquivos que fazem conexões
$arquivos_com_conexao = [
    'admin/includes/header.php' => 'Header do admin (contador de mensagens)',
    'municipio.php' => 'Página de município (múltiplas conexões)',
    'api/filtrar_parceiros.php' => 'API de filtros',
    'api/filtrar_parceiros_slug.php' => 'API de filtros por slug',
    'api/get_municipios_fallback.php' => 'API de municípios',
    'admin/municipios.php' => 'Admin municípios',
    'admin/parceiros.php' => 'Admin parceiros',
    'admin/dashboard.php' => 'Admin dashboard'
];

echo "<h3>🎯 ARQUIVOS QUE FAZEM CONEXÕES:</h3>";
foreach ($arquivos_com_conexao as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $conexoes = substr_count(file_get_contents($arquivo), 'getAgronegConnection');
        $queries = substr_count(file_get_contents($arquivo), '->query(');
        $prepares = substr_count(file_get_contents($arquivo), '->prepare(');
        
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>$arquivo</strong> - $descricao<br>";
        echo "🔗 Conexões: $conexoes | 📝 Queries: $queries | 🔧 Prepares: $prepares";
        echo "</div>";
    }
}

echo "<h3>🚨 PROBLEMAS IDENTIFICADOS:</h3>";

// Verificar se há loops ou chamadas repetitivas
echo "<h4>1. Header do Admin (PROBLEMA CRÍTICO):</h4>";
echo "<p>❌ <strong>admin/includes/header.php</strong> faz conexão a CADA página do admin</p>";
echo "<p>❌ Conta mensagens não lidas em TODA requisição</p>";
echo "<p>❌ Se você navegar 10 páginas = 10 conexões desnecessárias</p>";

echo "<h4>2. Página Município (PROBLEMA CRÍTICO):</h4>";
echo "<p>❌ <strong>municipio.php</strong> faz MÚLTIPLAS conexões:</p>";
echo "<ul>";
echo "<li>Linha 24: Conexão para redirecionamento</li>";
echo "<li>Linha 63: Conexão principal</li>";
echo "<li>Linha 105: Conexão para fallback</li>";
echo "<li>Linha 204: Conexão para galeria</li>";
echo "<li>Linha 264: Conexão para parceiros</li>";
echo "</ul>";

echo "<h4>3. APIs (PROBLEMA MÉDIO):</h4>";
echo "<p>❌ Cada API faz conexão independente</p>";
echo "<p>❌ JavaScript pode chamar APIs repetidamente</p>";

echo "<h3>💡 SOLUÇÕES RECOMENDADAS:</h3>";
echo "<ol>";
echo "<li><strong>Header Admin:</strong> Usar cache para contador de mensagens</li>";
echo "<li><strong>Município:</strong> Reutilizar conexão existente</li>";
echo "<li><strong>APIs:</strong> Implementar cache mais agressivo</li>";
echo "<li><strong>JavaScript:</strong> Adicionar debounce (já feito)</li>";
echo "</ol>";

echo "<h3>📈 ESTATÍSTICAS:</h3>";
if (file_exists(__DIR__ . '/requisicoes.log')) {
    $log_content = file_get_contents(__DIR__ . '/requisicoes.log');
    $total_conexoes = substr_count($log_content, 'CONEXÃO:');
    echo "<p>Total de conexões registradas: <strong>$total_conexoes</strong></p>";
    
    // Mostrar últimas 10 conexões
    $linhas = explode("\n", $log_content);
    $ultimas = array_slice(array_filter($linhas), -10);
    echo "<h4>Últimas 10 conexões:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>";
    foreach ($ultimas as $linha) {
        echo htmlspecialchars($linha) . "\n";
    }
    echo "</pre>";
}

echo "<h3>🎯 PRÓXIMOS PASSOS:</h3>";
echo "<p><a href='corrigir-conexoes.php' style='background: #1A9B60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 CORRIGIR CONEXÕES EXCESSIVAS</a></p>";
echo "<p><a href='limpar-logs.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗑️ LIMPAR LOGS</a></p>";
?>

