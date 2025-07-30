<?php
// Teste da API get_municipios.php
echo "<h2>Teste da API get_municipios.php</h2>";

// Simular chamada da API
$_GET['estado_id'] = 17; // Paraíba

// Capturar output da API
ob_start();
include 'api/get_municipios.php';
$output = ob_get_clean();

echo "<h3>Resposta da API:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Tentar decodificar JSON
$data = json_decode($output, true);
if ($data !== null) {
    echo "<h3>Dados decodificados:</h3>";
    if (is_array($data)) {
        echo "Total de municípios: " . count($data) . "<br>";
        if (count($data) > 0) {
            echo "<strong>Primeiros 5 municípios:</strong><br>";
            for ($i = 0; $i < min(5, count($data)); $i++) {
                echo "- {$data[$i]['id']}: {$data[$i]['nome']}<br>";
            }
        }
    } else {
        echo "Resposta não é um array: " . print_r($data, true);
    }
} else {
    echo "<h3>Erro ao decodificar JSON:</h3>";
    echo "JSON Error: " . json_last_error_msg() . "<br>";
    echo "Output bruto: " . htmlspecialchars($output);
}
?> 