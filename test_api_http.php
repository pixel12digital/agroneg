<?php
// Teste da API via HTTP
echo "<h2>Teste da API via HTTP</h2>";

// URL base
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$current_path = dirname($_SERVER['REQUEST_URI']);
$api_url = $base_url . $current_path . '/api/get_municipios.php?estado_id=17';

echo "<h3>Testando URL: {$api_url}</h3>";

// Teste com cURL
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "<h3>Resultado cURL:</h3>";
    echo "HTTP Code: {$http_code}<br>";
    
    if ($error) {
        echo "Erro cURL: {$error}<br>";
    } else {
        echo "Resposta:<br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Tentar decodificar JSON
        $data = json_decode($response, true);
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
            }
        } else {
            echo "Erro ao decodificar JSON: " . json_last_error_msg() . "<br>";
        }
    }
} else {
    echo "cURL não está disponível<br>";
}

// Teste alternativo com file_get_contents
echo "<h3>Teste alternativo com file_get_contents:</h3>";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)'
    ]
]);

$response_alt = @file_get_contents($api_url, false, $context);
if ($response_alt !== false) {
    echo "Resposta file_get_contents:<br>";
    echo "<pre>" . htmlspecialchars($response_alt) . "</pre>";
} else {
    echo "Erro ao acessar via file_get_contents<br>";
    $error = error_get_last();
    if ($error) {
        echo "Erro: " . $error['message'] . "<br>";
    }
}
?> 