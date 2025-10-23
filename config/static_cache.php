<?php
/**
 * CACHE ESTÁTICO CRÍTICO
 * Dados salvos localmente - FUNCIONA SEM BANCO
 */

class StaticCache {
    private static $cache_dir = __DIR__ . '/../cache/static/';
    private static $backup_dir = __DIR__ . '/../cache/backup/';
    
    /**
     * INICIALIZAR diretórios
     */
    private static function init() {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        if (!is_dir(self::$backup_dir)) {
            mkdir(self::$backup_dir, 0755, true);
        }
    }
    
    /**
     * SALVAR dados críticos localmente
     */
    public static function saveCritical($key, $data, $ttl = 86400) {
        self::init();
        
        $item = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $file = self::$cache_dir . md5($key) . '.json';
        file_put_contents($file, json_encode($item), LOCK_EX);
        
        // Backup também
        $backup_file = self::$backup_dir . md5($key) . '.json';
        copy($file, $backup_file);
    }
    
    /**
     * CARREGAR dados críticos
     */
    public static function loadCritical($key) {
        self::init();
        
        $file = self::$cache_dir . md5($key) . '.json';
        
        if (!file_exists($file)) {
            // Tentar backup
            $backup_file = self::$backup_dir . md5($key) . '.json';
            if (file_exists($backup_file)) {
                $file = $backup_file;
            } else {
                return null;
            }
        }
        
        $content = file_get_contents($file);
        $item = json_decode($content, true);
        
        if (!$item || $item['expires'] <= time()) {
            return null;
        }
        
        return $item['data'];
    }
    
    /**
     * GERAR cache estático de municípios críticos
     */
    public static function generateMunicipiosCache() {
        require_once(__DIR__ . '/db.php');
        
        $conn = getAgronegConnection();
        if (!$conn) {
            return false;
        }
        
        try {
            // Buscar municípios mais acessados
            $query = "
                SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla, e.id as estado_id, m.id as municipio_id
                FROM municipios m
                JOIN estados e ON m.estado_id = e.id
                WHERE m.status = 1
                ORDER BY m.id
                LIMIT 100
            ";
            
            $result = $conn->query($query);
            $municipios = [];
            
            while ($row = $result->fetch_assoc()) {
                $municipios[] = $row;
            }
            
            // Salvar cache estático
            self::saveCritical('municipios_all', $municipios, 86400 * 7); // 7 dias
            
            // Gerar cache individual para cada município
            foreach ($municipios as $municipio) {
                $key = "municipio_{$municipio['estado_sigla']}_{$municipio['slug']}";
                self::saveCritical($key, $municipio, 86400 * 7); // 7 dias
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao gerar cache de municípios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * GERAR cache estático de parceiros críticos
     */
    public static function generateParceirosCache() {
        require_once(__DIR__ . '/db.php');
        
        $conn = getAgronegConnection();
        if (!$conn) {
            return false;
        }
        
        try {
            // Buscar parceiros de municípios mais acessados
            $query = "
                SELECT p.*, m.slug as municipio_slug, e.sigla as estado_sigla, t.nome as tipo_nome, t.slug as tipo_slug
                FROM parceiros p
                JOIN municipios m ON p.municipio_id = m.id
                JOIN estados e ON m.estado_id = e.id
                JOIN tipos_parceiros t ON p.tipo_id = t.id
                WHERE p.status = 1 AND m.status = 1
                ORDER BY p.destaque DESC, p.nome ASC
                LIMIT 1000
            ";
            
            $result = $conn->query($query);
            $parceiros = [];
            
            while ($row = $result->fetch_assoc()) {
                $parceiros[] = $row;
            }
            
            // Agrupar por município
            $parceiros_por_municipio = [];
            foreach ($parceiros as $parceiro) {
                $key = "{$parceiro['estado_sigla']}_{$parceiro['municipio_slug']}";
                $parceiros_por_municipio[$key][] = $parceiro;
            }
            
            // Salvar cache por município
            foreach ($parceiros_por_municipio as $key => $parceiros_municipio) {
                self::saveCritical("parceiros_{$key}", $parceiros_municipio, 86400); // 1 dia
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao gerar cache de parceiros: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * CARREGAR município do cache estático
     */
    public static function getMunicipioStatic($slug_estado, $slug_municipio) {
        $key = "municipio_{$slug_estado}_{$slug_municipio}";
        return self::loadCritical($key);
    }
    
    /**
     * CARREGAR parceiros do cache estático
     */
    public static function getParceirosStatic($slug_estado, $slug_municipio, $categorias = []) {
        $key = "parceiros_{$slug_estado}_{$slug_municipio}";
        $parceiros = self::loadCritical($key);
        
        if (!$parceiros) {
            return [];
        }
        
        // Filtrar por categorias se especificado
        if (!empty($categorias)) {
            $slug_mapping = [
                'produtores' => 'produtores',
                'criadores' => 'criadores', 
                'veterinarios' => 'veterinarios',
                'lojas-agropet' => 'lojas-agropet',
                'cooperativas' => 'agroneg-cooper'
            ];
            
            $filtered = [];
            foreach ($parceiros as $parceiro) {
                // Verificar se o tipo do parceiro corresponde a alguma categoria selecionada
                foreach ($categorias as $categoria) {
                    if (isset($slug_mapping[$categoria]) && $parceiro['tipo_slug'] === $slug_mapping[$categoria]) {
                        $filtered[] = $parceiro;
                        break; // Evitar duplicatas
                    }
                }
            }
            return $filtered;
        }
        
        return $parceiros;
    }
    
    /**
     * VERIFICAR se cache estático existe
     */
    public static function hasStaticCache($key) {
        self::init();
        
        $file = self::$cache_dir . md5($key) . '.json';
        $backup_file = self::$backup_dir . md5($key) . '.json';
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $item = json_decode($content, true);
            return $item && $item['expires'] > time();
        }
        
        if (file_exists($backup_file)) {
            $content = file_get_contents($backup_file);
            $item = json_decode($content, true);
            return $item && $item['expires'] > time();
        }
        
        return false;
    }
    
    /**
     * LIMPAR cache estático
     */
    public static function clear() {
        self::init();
        
        $files = glob(self::$cache_dir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        
        $files = glob(self::$backup_dir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// Gerar cache estático automaticamente (apenas se não existir e não estiver em modo CLI)
if (!StaticCache::hasStaticCache('municipios_all') && php_sapi_name() !== 'cli') {
    StaticCache::generateMunicipiosCache();
}
?>

