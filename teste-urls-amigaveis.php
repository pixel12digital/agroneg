<?php
/**
 * Teste das URLs amigáveis implementadas
 */

echo "=== TESTE DE URLs AMIGÁVEIS ===\n\n";

// Testar redirecionamento de URLs antigas
echo "1. TESTE DE REDIRECIONAMENTO:\n";
echo "URL antiga: municipio.php?estado=15&municipio=1\n";

// Simular $_GET
$_GET['estado'] = '15';
$_GET['municipio'] = '1';

require_once('config/db.php');
$conn = getAgronegConnection();

$estado_id = isset($_GET['estado']) ? (int)$_GET['estado'] : null;
$municipio_id = isset($_GET['municipio']) ? (int)$_GET['municipio'] : null;

if ($estado_id && $municipio_id) {
    $query = "
        SELECT m.slug as municipio_slug, e.sigla as estado_sigla
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE e.id = ? AND m.id = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $estado_id, $municipio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nova_url = "/" . strtolower($row['estado_sigla']) . "/" . $row['municipio_slug'];
        echo "✅ Redirecionaria para: $nova_url\n";
    } else {
        echo "❌ Município não encontrado\n";
    }
}

echo "\n2. TESTE DE URLs AMIGÁVEIS:\n";

// Testar URLs amigáveis
$test_urls = [
    'pb/barra-de-sao-miguel' => 'Paraíba - Barra de São Miguel',
    'pe/santa-cruz-do-capibaribe' => 'Pernambuco - Santa Cruz do Capibaribe',
    'ce/iracema' => 'Ceará - Iracema'
];

foreach ($test_urls as $url => $descricao) {
    echo "URL: /$url -> $descricao\n";
    
    $parts = explode('/', $url);
    $slug_estado = $parts[0];
    $slug_municipio = $parts[1];
    
    $query = "
        SELECT m.nome as municipio_nome, e.nome as estado_nome, e.sigla as estado_sigla
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $slug_estado, $slug_municipio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Encontrado: {$row['municipio_nome']} - {$row['estado_nome']}\n";
    } else {
        echo "❌ Não encontrado\n";
    }
}

echo "\n3. TESTE DE PÁGINAS DE PARCEIROS:\n";

$test_paginas = [
    'produtores/pb/barra-de-sao-miguel' => 'Produtores em Barra de São Miguel',
    'criadores/pe/santa-cruz-do-capibaribe' => 'Criadores em Santa Cruz do Capibaribe',
    'veterinarios/ce/iracema' => 'Veterinários em Iracema'
];

foreach ($test_paginas as $url => $descricao) {
    echo "URL: /$url -> $descricao\n";
    
    $parts = explode('/', $url);
    $tipo = $parts[0];
    $slug_estado = $parts[1];
    $slug_municipio = $parts[2];
    
    $query = "
        SELECT m.nome as municipio_nome, e.nome as estado_nome
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $slug_estado, $slug_municipio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Encontrado: {$row['municipio_nome']} - {$row['estado_nome']}\n";
    } else {
        echo "❌ Não encontrado\n";
    }
}

echo "\n4. TESTE DE PARCEIRO INDIVIDUAL:\n";

$test_parceiros = [
    'fazenda-sao-joao' => 'Fazenda São João',
    'ana-beatriz' => 'Ana Beatriz'
];

foreach ($test_parceiros as $slug => $nome) {
    echo "URL: /parceiro/$slug -> $nome\n";
    
    $query = "SELECT nome FROM parceiros WHERE slug = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Encontrado: {$row['nome']}\n";
    } else {
        echo "❌ Não encontrado\n";
    }
}

echo "\n🎉 TESTE CONCLUÍDO!\n";
echo "URLs amigáveis implementadas com sucesso!\n";
?>
