<?php
// Define o fuso horário padrão para toda a aplicação, corrigindo problemas de data
date_default_timezone_set('America/Sao_Paulo');

// –––— Limpa o OPcache (garante que não fique versão antiga em cache)
if (function_exists('opcache_reset')) {
    opcache_reset();
}

/* ------------------------------------------------------------------
   1. Detecta ambiente
-------------------------------------------------------------------*/
if (
    (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
    || (php_sapi_name() === 'cli' && stripos(getcwd(), 'xampp') !== false)
) {
    $ambiente = 'desenvolvimento';
} else {
    $ambiente = 'producao';
}

// Tornar a variável ambiente global para uso em outros arquivos
$GLOBALS['ambiente'] = $ambiente;

/* ------------------------------------------------------------------
   2. Variável global para conexão única
-------------------------------------------------------------------*/
$GLOBALS['AGRONEG_DB_CONNECTION'] = null;
$GLOBALS['AGRONEG_DB_CONNECTION_COUNT'] = 0;
$GLOBALS['AGRONEG_MAX_RETRIES'] = 3;

/* ------------------------------------------------------------------
   3. Função para obter conexão única (Singleton Pattern)
-------------------------------------------------------------------*/
function getAgronegConnection() {
    // Se já existe uma conexão válida, use ela
    if ($GLOBALS['AGRONEG_DB_CONNECTION'] !== null && 
        $GLOBALS['AGRONEG_DB_CONNECTION']->ping()) {
        return $GLOBALS['AGRONEG_DB_CONNECTION'];
    }
    
    // Credenciais do banco
    $servername = "srv1890.hstgr.io";
    $username   = "u664918047_agroneg";
    $password   = "Los@ngo#081081";
    $dbname     = "u664918047_agroneg";
    
    $conn = null;
    $tentativas = 0;
    
    // Loop de retry com espera progressiva
    while ($tentativas < $GLOBALS['AGRONEG_MAX_RETRIES'] && $conn === null) {
        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            if ($conn->connect_error) {
                throw new Exception("Erro de conexão: " . $conn->connect_error);
            }
            
            // Configurar charset
            $conn->set_charset("utf8mb4");
            mysqli_query($conn, "SET NAMES 'utf8mb4'");
            mysqli_query($conn, "SET character_set_client = 'utf8mb4'");
            mysqli_query($conn, "SET character_set_results = 'utf8mb4'");
            mysqli_query($conn, "SET collation_connection = 'utf8mb4_unicode_ci'");
            
            // Salvar conexão globalmente
            $GLOBALS['AGRONEG_DB_CONNECTION'] = $conn;
            $GLOBALS['AGRONEG_DB_CONNECTION_COUNT']++;
            
            error_log("Nova conexão DB estabelecida (tentativa " . ($tentativas + 1) . ")");
            
        } catch (Exception $e) {
            $tentativas++;
            error_log("Erro de conexão DB (tentativa " . $tentativas . "): " . $e->getMessage());
            
            if ($tentativas < $GLOBALS['AGRONEG_MAX_RETRIES']) {
                // Espera progressiva: 1s, 2s, 3s...
                sleep($tentativas);
            }
        }
    }
    
    if ($conn === null) {
        error_log("CRÍTICO: Não foi possível estabelecer conexão após " . $GLOBALS['AGRONEG_MAX_RETRIES'] . " tentativas");
        
        // Página de erro amigável
        if (!headers_sent()) {
            http_response_code(503);
        }
        
        die('
        <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
            <h2>Serviço Temporariamente Indisponível</h2>
            <p>O sistema está temporariamente fora do ar devido a limitações de conexão.</p>
            <p>Tente novamente em alguns minutos.</p>
            <p><a href="index.php">Voltar para a página inicial</a></p>
        </div>
        ');
    }
    
    return $conn;
}

/* ------------------------------------------------------------------
   4. Função para fechar conexão quando necessário
-------------------------------------------------------------------*/
function closeAgronegConnection() {
    if ($GLOBALS['AGRONEG_DB_CONNECTION'] !== null) {
        $GLOBALS['AGRONEG_DB_CONNECTION']->close();
        $GLOBALS['AGRONEG_DB_CONNECTION'] = null;
        error_log("Conexão DB fechada");
    }
}

/* ------------------------------------------------------------------
   5. Obter conexão para uso nos arquivos (compatibilidade)
   NOTA: Conexão não é criada automaticamente para evitar múltiplas conexões
-------------------------------------------------------------------*/
// $conn = getAgronegConnection(); // Removido para evitar conexões desnecessárias

/* ------------------------------------------------------------------
   6. Fechar conexão ao final do script
-------------------------------------------------------------------*/
register_shutdown_function('closeAgronegConnection');

/* ------------------------------------------------------------------
   7. Log de monitoramento (apenas em desenvolvimento)
-------------------------------------------------------------------*/
if ($ambiente === 'desenvolvimento') {
    error_log("DB CONFIG ATIVO → ambiente={$ambiente}; conexões_feitas=" . $GLOBALS['AGRONEG_DB_CONNECTION_COUNT']);
}
?>