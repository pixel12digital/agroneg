<?php
require_once('config/db.php');
$conn = getAgronegConnection();

echo "=== ESTRUTURA DA TABELA PARCEIROS ===\n";
$result = $conn->query('DESCRIBE parceiros');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== CONTAGEM DE PARCEIROS ===\n";
$result = $conn->query('SELECT COUNT(*) as total FROM parceiros');
$row = $result->fetch_assoc();
echo "Total de parceiros: " . $row['total'] . "\n";
?>
