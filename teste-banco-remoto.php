<?php
/**
 * Teste específico para verificação de banco remoto
 * Acesso: teste-banco-remoto.php
 */

header('Content-Type: application/json; charset=utf-8');

$resultado = [
    'timestamp' => date('Y-m-d H:i:s'),
    'teste' => 'Conexão com banco remoto',
    'status' => 'TESTANDO'
];

try {
    // Incluir configuração de banco
    require_once('config/db.php');
    
    // Obter conexão
    $conn = getAgronegConnection();
    
    if ($conn && $conn->ping()) {
        $resultado['status'] = 'CONECTADO';
        $resultado['detalhes'] = [
            'host' => $conn->host_info,
            'server' => $conn->server_info,
            'charset' => $conn->character_set_name()
        ];
        
        // Testar consultas básicas
        $consultas = [
            'estados' => "SELECT COUNT(*) as total FROM estados",
            'municipios' => "SELECT COUNT(*) as total FROM municipios", 
            'parceiros' => "SELECT COUNT(*) as total FROM parceiros WHERE status = 1",
            'tipos_parceiros' => "SELECT COUNT(*) as total FROM tipos_parceiros"
        ];
        
        $resultado['contadores'] = [];
        foreach ($consultas as $nome => $sql) {
            $result = $conn->query($sql);
            if ($result) {
                $row = $result->fetch_assoc();
                $resultado['contadores'][$nome] = $row['total'];
            } else {
                $resultado['contadores'][$nome] = 'ERRO';
            }
        }
        
        // Verificar cache
        $cache_dir = __DIR__ . '/cache/';
        $cache_files = glob($cache_dir . '*.json');
        $resultado['cache'] = [
            'diretorio_existe' => is_dir($cache_dir),
            'arquivos_count' => count($cache_files),
            'arquivos' => array_map('basename', $cache_files)
        ];
        
        $resultado['conclusao'] = 'Banco remoto funcionando perfeitamente!';
        
    } else {
        $resultado['status'] = 'ERRO_CONEXAO';
        $resultado['erro'] = 'Falha na conexão com banco remoto';
    }
    
} catch (Exception $e) {
    $resultado['status'] = 'ERRO';
    $resultado['erro'] = $e->getMessage();
    $resultado['trace'] = $e->getTraceAsString();
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
