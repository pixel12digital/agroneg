<?php
require_once('config/db.php');
$conn = getAgronegConnection();

echo "=== VERIFICAÇÃO DE SLUGS EXISTENTES ===\n\n";

// Verificar slugs de municípios
echo "1. MUNICÍPIOS:\n";
$query = "SELECT m.id, m.nome, m.slug, e.nome as estado_nome, e.sigla as estado_sigla 
          FROM municipios m 
          JOIN estados e ON m.estado_id = e.id 
          ORDER BY e.nome, m.nome 
          LIMIT 10";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "- {$row['nome']} ({$row['estado_sigla']}) - Slug: '{$row['slug']}'\n";
}

echo "\n2. PARCEIROS:\n";
$query = "SELECT id, nome, slug FROM parceiros WHERE status = 1 ORDER BY nome LIMIT 10";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "- {$row['nome']} - Slug: '{$row['slug']}'\n";
}

echo "\n3. ESTADOS:\n";
$query = "SELECT id, nome, sigla FROM estados ORDER BY nome LIMIT 10";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "- {$row['nome']} ({$row['sigla']}) - ID: {$row['id']}\n";
}

echo "\n4. TIPOS DE PARCEIROS:\n";
$query = "SELECT id, nome, slug FROM tipos_parceiros ORDER BY nome";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "- {$row['nome']} - Slug: '{$row['slug']}'\n";
}
?>
