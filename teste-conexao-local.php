<?php
/**
 * Teste local de conexão com banco de dados
 */

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    require_once('config/db.php');
    
    echo "1. Carregando configuração DB... ";
    echo "OK\n";
    
    echo "2. Obtendo conexão... ";
    $conn = getAgronegConnection();
    
    if ($conn && $conn->ping()) {
        echo "CONECTADO\n";
        echo "   Host: " . $conn->host_info . "\n";
        echo "   Server: " . $conn->server_info . "\n\n";
        
        echo "3. Testando consultas...\n";
        
        // Teste 1: Contar estados
        $result = $conn->query("SELECT COUNT(*) as total FROM estados");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "   Estados cadastrados: " . $row['total'] . "\n";
        } else {
            echo "   ERRO: Falha na consulta de estados\n";
        }
        
        // Teste 2: Contar municípios
        $result = $conn->query("SELECT COUNT(*) as total FROM municipios");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "   Municípios cadastrados: " . $row['total'] . "\n";
        } else {
            echo "   ERRO: Falha na consulta de municípios\n";
        }
        
        // Teste 3: Contar parceiros ativos
        $result = $conn->query("SELECT COUNT(*) as total FROM parceiros WHERE status = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "   Parceiros ativos: " . $row['total'] . "\n";
        } else {
            echo "   ERRO: Falha na consulta de parceiros\n";
        }
        
        echo "\n4. Verificando cache...\n";
        $cache_dir = __DIR__ . '/cache/';
        if (is_dir($cache_dir)) {
            $cache_files = glob($cache_dir . '*.json');
            echo "   Arquivos de cache: " . count($cache_files) . "\n";
            foreach ($cache_files as $file) {
                echo "   - " . basename($file) . " (" . filesize($file) . " bytes)\n";
            }
        } else {
            echo "   Diretório de cache não existe ainda\n";
        }
        
        echo "\n=== RESULTADO: CONEXÃO OK ===\n";
        
    } else {
        echo "ERRO: Falha na conexão\n";
        echo "=== RESULTADO: FALHA NA CONEXÃO ===\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "=== RESULTADO: ERRO ===\n";
}
?>
