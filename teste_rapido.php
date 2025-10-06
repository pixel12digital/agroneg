<?php
/**
 * Teste rápido de conectividade - Acesso público
 * Use para verificar se o site está funcionando
 */

header('Content-Type: application/json; charset=utf-8');

$teste = [
    'timestamp' => date('Y-m-d H:i:s'),
    'site' => 'AgroNeg',
    'status' => 'ONLINE',
    'versao' => '2.0'
];

// Teste básico de conexão (sem expor dados sensíveis)
try {
    require_once('config/db.php');
    $conn = getAgronegConnection();
    
    if ($conn && $conn->ping()) {
        $teste['database'] = 'CONECTADO';
        
        // Contagem rápida de registros
        $result = $conn->query("SELECT COUNT(*) as total FROM estados");
        if ($result) {
            $row = $result->fetch_assoc();
            $teste['estados_cadastrados'] = $row['total'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as total FROM municipios");
        if ($result) {
            $row = $result->fetch_assoc();
            $teste['municipios_cadastrados'] = $row['total'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as total FROM parceiros WHERE ativo = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            $teste['parceiros_ativos'] = $row['total'];
        }
        
    } else {
        $teste['database'] = 'ERRO_CONEXAO';
    }
} catch (Exception $e) {
    $teste['database'] = 'ERRO';
    $teste['error'] = 'Erro na conexão';
}

// Verificar cache
$cache_files = glob(__DIR__ . '/cache/*.json');
$teste['cache_files'] = count($cache_files);
$teste['cache_status'] = count($cache_files) > 0 ? 'ATIVO' : 'VAZIO';

echo json_encode($teste, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
