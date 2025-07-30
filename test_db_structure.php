<?php
// Arquivo de teste para verificar estrutura do banco
require_once("config/db.php");

echo "<h2>Teste de Estrutura do Banco</h2>";

// Teste 1: Verificar conexão
echo "<h3>1. Teste de Conexão</h3>";
if ($conn->ping()) {
    echo "✅ Conexão OK<br>";
} else {
    echo "❌ Erro na conexão: " . $conn->error . "<br>";
}

// Teste 2: Verificar tabela estados
echo "<h3>2. Tabela Estados</h3>";
$result = $conn->query("SHOW TABLES LIKE 'estados'");
if ($result->num_rows > 0) {
    echo "✅ Tabela 'estados' existe<br>";
    
    // Verificar estrutura
    $result = $conn->query("DESCRIBE estados");
    echo "<strong>Estrutura da tabela estados:</strong><br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}<br>";
    }
    
    // Verificar dados
    $result = $conn->query("SELECT COUNT(*) as total FROM estados");
    $row = $result->fetch_assoc();
    echo "Total de estados: {$row['total']}<br>";
} else {
    echo "❌ Tabela 'estados' não existe<br>";
}

// Teste 3: Verificar tabela municipios
echo "<h3>3. Tabela Municipios</h3>";
$result = $conn->query("SHOW TABLES LIKE 'municipios'");
if ($result->num_rows > 0) {
    echo "✅ Tabela 'municipios' existe<br>";
    
    // Verificar estrutura
    $result = $conn->query("DESCRIBE municipios");
    echo "<strong>Estrutura da tabela municipios:</strong><br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}<br>";
    }
    
    // Verificar dados
    $result = $conn->query("SELECT COUNT(*) as total FROM municipios");
    $row = $result->fetch_assoc();
    echo "Total de municípios: {$row['total']}<br>";
    
    // Verificar relacionamento com estados
    $result = $conn->query("SELECT estado_id, COUNT(*) as total FROM municipios GROUP BY estado_id LIMIT 5");
    echo "<strong>Exemplo de municípios por estado:</strong><br>";
    while ($row = $result->fetch_assoc()) {
        echo "- Estado ID {$row['estado_id']}: {$row['total']} municípios<br>";
    }
} else {
    echo "❌ Tabela 'municipios' não existe<br>";
}

// Teste 4: Verificar tabela eventos_municipio
echo "<h3>4. Tabela Eventos</h3>";
$result = $conn->query("SHOW TABLES LIKE 'eventos_municipio'");
if ($result->num_rows > 0) {
    echo "✅ Tabela 'eventos_municipio' existe<br>";
    
    // Verificar estrutura
    $result = $conn->query("DESCRIBE eventos_municipio");
    echo "<strong>Estrutura da tabela eventos_municipio:</strong><br>";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}<br>";
    }
    
    // Verificar dados
    $result = $conn->query("SELECT COUNT(*) as total FROM eventos_municipio");
    $row = $result->fetch_assoc();
    echo "Total de eventos: {$row['total']}<br>";
} else {
    echo "❌ Tabela 'eventos_municipio' não existe<br>";
}

// Teste 5: Testar consulta específica da API
echo "<h3>5. Teste da Consulta da API</h3>";
$estado_id = 17; // Paraíba
$query = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $estado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "✅ Consulta preparada com sucesso<br>";
    echo "Estado ID testado: {$estado_id}<br>";
    echo "Municípios encontrados: {$result->num_rows}<br>";
    
    if ($result->num_rows > 0) {
        echo "<strong>Primeiros 5 municípios:</strong><br>";
        $count = 0;
        while ($row = $result->fetch_assoc() && $count < 5) {
            echo "- {$row['id']}: {$row['nome']}<br>";
            $count++;
        }
    }
} else {
    echo "❌ Erro na preparação da consulta: " . $conn->error . "<br>";
}

$conn->close();
?> 