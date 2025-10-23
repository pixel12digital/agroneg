<?php
/**
 * Sistema de Conexão com Banco de Dados - AgroNeg
 * Versão final otimizada para desenvolvimento e produção
 */

// Define o fuso horário padrão para toda a aplicação
date_default_timezone_set('America/Sao_Paulo');

// Limpa o OPcache para garantir versão atualizada
if (function_exists('opcache_reset')) {
    opcache_reset();
}

/* ------------------------------------------------------------------
   1. Detecção de ambiente
-------------------------------------------------------------------*/
$isLocal = (
    (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
    || (php_sapi_name() === 'cli' && stripos(getcwd(), 'xampp') !== false)
);

$GLOBALS['ambiente'] = $isLocal ? 'desenvolvimento' : 'producao';
$GLOBALS['isLocal'] = $isLocal;

/* ------------------------------------------------------------------
   2. Configurações do sistema
-------------------------------------------------------------------*/
$GLOBALS['AGRONEG_DB_CONNECTION'] = null;
$GLOBALS['AGRONEG_DB_CONNECTION_COUNT'] = 0;
$GLOBALS['AGRONEG_DB_LAST_ERROR'] = null;
$GLOBALS['AGRONEG_DB_RETRY_AFTER'] = null;

// Configurações diferentes por ambiente
if ($GLOBALS['isLocal']) {
    // Desenvolvimento: mais permissivo
    $GLOBALS['AGRONEG_DB_MAX_RETRIES'] = 2;
    $GLOBALS['AGRONEG_DB_TIMEOUT'] = 10;
} else {
    // Produção: ultra conservador
    $GLOBALS['AGRONEG_DB_MAX_RETRIES'] = 1;
    $GLOBALS['AGRONEG_DB_TIMEOUT'] = 3;
    $GLOBALS['AGRONEG_DB_BLOCKED_UNTIL'] = null;
}

/* ------------------------------------------------------------------
   3. Configurações do Banco de Dados
-------------------------------------------------------------------*/
$GLOBALS['AGRONEG_DB_CONFIG'] = [
    'servername' => 'srv1890.hstgr.io',
    'username' => 'u664918047_agroneg',
    'password' => 'Los@ngo#081081',
    'dbname' => 'u664918047_agroneg',
    'charset' => 'utf8mb4'
];

/* ------------------------------------------------------------------
   4. Função principal para obter conexão
-------------------------------------------------------------------*/
function getAgronegConnection() {
    // Versão simplificada - sempre criar nova conexão
    $config = $GLOBALS['AGRONEG_DB_CONFIG'];
    
    try {
        $conn = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        
        if ($conn->connect_error) {
            error_log("Erro de conexão: " . $conn->connect_error);
            return null;
        }
        
        // Configurar charset
        $conn->set_charset($config['charset']);
        
        error_log("Nova conexão DB estabelecida");
        return $conn;
        
    } catch (Exception $e) {
        error_log("Erro ao criar conexão: " . $e->getMessage());
        return null;
    }
}

/* ------------------------------------------------------------------
   5. Função para fechar conexão
-------------------------------------------------------------------*/
function closeAgronegConnection() {
    if ($GLOBALS['AGRONEG_DB_CONNECTION'] !== null) {
        try {
            if (is_object($GLOBALS['AGRONEG_DB_CONNECTION']) && 
                method_exists($GLOBALS['AGRONEG_DB_CONNECTION'], 'close')) {
                $GLOBALS['AGRONEG_DB_CONNECTION']->close();
            }
        } catch (Exception $e) {
            // Ignorar erro se conexão já foi fechada
        }
        $GLOBALS['AGRONEG_DB_CONNECTION'] = null;
        error_log("Conexão DB fechada");
    }
}

/* ------------------------------------------------------------------
   6. Função para executar consultas com cache inteligente
-------------------------------------------------------------------*/
function executeQueryWithCache($query, $params = [], $cache_key = null, $cache_ttl = 3600) {
    // Verificar cache primeiro
    if ($cache_key) {
        require_once(__DIR__ . '/cache_manager.php');
        $cached = CacheManager::get($cache_key);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    $conn = getAgronegConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar consulta: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        $stmt->close();
        
        // Salvar no cache se especificado
        if ($cache_key && !empty($data)) {
            CacheManager::set($cache_key, $data, $cache_ttl);
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Erro na consulta: " . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------
   7. Função otimizada para buscar município - COM FALLBACK
-------------------------------------------------------------------*/
function getMunicipioBySlug($slug_estado, $slug_municipio) {
    // Versão simplificada sem cache para evitar problemas de conexão
    $conn = getAgronegConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        // Primeira tentativa: slug exato
        $query = "
            SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla, e.id as estado_id, m.id as municipio_id
            FROM municipios m
            JOIN estados e ON m.estado_id = e.id
            WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Erro ao preparar consulta: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("ss", $slug_estado, $slug_municipio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $municipio = $result->fetch_assoc();
            $stmt->close();
            return $municipio;
        }
        $stmt->close();
        
        // Segunda tentativa: variações comuns de slug
        $variacoes = [
            // Barra de São Miguel: variações comuns
            'barra-de-so-miguel' => 'barra-de-sao-miguel',
            'barra-de-s-ao-miguel' => 'barra-de-sao-miguel',
            'barra-de-são-miguel' => 'barra-de-sao-miguel',
            
            // Outras variações comuns
            'sao-paulo' => 'sao-paulo',
            'sao-jose' => 'sao-jose',
            'sao-joao' => 'sao-joao',
            'sao-pedro' => 'sao-pedro',
            'sao-miguel' => 'sao-miguel',
            'sao-luis' => 'sao-luis',
            'sao-francisco' => 'sao-francisco',
            'sao-sebastiao' => 'sao-sebastiao'
        ];
        
        // Se o slug atual está nas variações, tentar o slug correto
        if (isset($variacoes[$slug_municipio])) {
            $slug_correto = $variacoes[$slug_municipio];
            
            $query_fallback = "
                SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla, e.id as estado_id, m.id as municipio_id
                FROM municipios m
                JOIN estados e ON m.estado_id = e.id
                WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
            ";
            
            $stmt_fallback = $conn->prepare($query_fallback);
            if ($stmt_fallback) {
                $stmt_fallback->bind_param("ss", $slug_estado, $slug_correto);
                $stmt_fallback->execute();
                $result_fallback = $stmt_fallback->get_result();
                
                if ($result_fallback->num_rows > 0) {
                    $municipio = $result_fallback->fetch_assoc();
                    $stmt_fallback->close();
                    
                    // Log para debug
                    error_log("MunicipioBySlug: Slug '{$slug_municipio}' redirecionado para '{$slug_correto}'");
                    
                    return $municipio;
                }
                $stmt_fallback->close();
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Erro ao buscar município: " . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------
   8. Função otimizada para buscar parceiros - COM FALLBACK
-------------------------------------------------------------------*/
function getParceirosByMunicipio($municipio_id, $categorias = []) {
    require_once(__DIR__ . '/cache_manager.php');
    require_once(__DIR__ . '/static_cache.php');
    
    // FALLBACK 1: Tentar cache estático primeiro (SEM BANCO)
    // Nota: Para usar cache estático, precisamos dos slugs, não IDs
    // Isso será implementado nas APIs específicas
    
    // FALLBACK 2: Tentar cache dinâmico
    $cached = CacheManager::getParceiros($municipio_id, $categorias);
    if ($cached !== null) {
        return $cached;
    }
    
    $conn = getAgronegConnection();
    if (!$conn) {
        return false;
    }
    
    try {
        $params = [$municipio_id];
        $types = 'i';
        
        $sql = "
            SELECT p.*, GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as categorias_parceiro, t.nome as tipo_nome, t.slug as tipo_slug
            FROM parceiros p
            LEFT JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
            LEFT JOIN categorias c ON pc.categoria_id = c.id
            JOIN tipos_parceiros t ON p.tipo_id = t.id
            WHERE p.municipio_id = ? AND p.status = 1
        ";
        
        // Adicionar filtro de categorias se especificado
        if (!empty($categorias)) {
            $slug_mapping = [
                'produtores' => 'produtores',
                'criadores' => 'criadores', 
                'veterinarios' => 'veterinarios',
                'lojas-agropet' => 'lojas-agropet',
                'cooperativas' => 'cooperativas'
            ];
            
            $tipos_filtro = [];
            foreach ($categorias as $slug) {
                if (isset($slug_mapping[$slug])) {
                    $tipos_filtro[] = $slug_mapping[$slug];
                }
            }
            
            if (!empty($tipos_filtro)) {
                $placeholders = implode(',', array_fill(0, count($tipos_filtro), '?'));
                $sql .= " AND t.slug IN ($placeholders)";
                foreach ($tipos_filtro as $tipo) {
                    $params[] = $tipo;
                    $types .= 's';
                }
            }
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.destaque DESC, p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $parceiros = [];
        while ($row = $result->fetch_assoc()) {
            $parceiros[] = $row;
        }
        
        $stmt->close();
        
        // Salvar no cache por 30 minutos
        CacheManager::setParceiros($municipio_id, $categorias, $parceiros);
        
        return $parceiros;
        
    } catch (Exception $e) {
        error_log("Erro ao buscar parceiros: " . $e->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------
   7. Fechar conexão ao final do script (desabilitado temporariamente)
-------------------------------------------------------------------*/
// register_shutdown_function('closeAgronegConnection');

/* ------------------------------------------------------------------
   8. Inicializar limpeza de cache
-------------------------------------------------------------------*/
require_once(__DIR__ . '/cache_cleanup.php');

/* ------------------------------------------------------------------
   8. Log de monitoramento (apenas em desenvolvimento)
-------------------------------------------------------------------*/
if ($GLOBALS['isLocal']) {
    $status = "DB CONFIG → ambiente=desenvolvimento; conexões=" . $GLOBALS['AGRONEG_DB_CONNECTION_COUNT'];
    if ($GLOBALS['AGRONEG_DB_LAST_ERROR']) {
        $status .= "; último_erro=" . substr($GLOBALS['AGRONEG_DB_LAST_ERROR'], 0, 50);
    }
    error_log($status);
}
?>