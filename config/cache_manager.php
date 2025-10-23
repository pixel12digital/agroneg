<?php
/**
 * Sistema de Cache Agressivo - AgroNeg
 * Reduz drasticamente as consultas ao banco de dados
 */

class CacheManager {
    private static $cache = [];
    private static $cache_dir = __DIR__ . '/../cache/';
    private static $default_ttl = 3600; // 1 hora
    
    /**
     * Obter item do cache
     */
    public static function get($key) {
        // Cache em memória (mais rápido)
        if (isset(self::$cache[$key])) {
            $item = self::$cache[$key];
            if ($item['expires'] > time()) {
                return $item['data'];
            } else {
                unset(self::$cache[$key]);
            }
        }
        
        // Cache em arquivo (persistente)
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // Verificar se o conteúdo não está vazio
            if (empty($content)) {
                unlink($file);
                return null;
            }
            
            $data = @unserialize($content);
            
            // Verificar se o unserialize foi bem-sucedido e se é um array válido
            if ($data === false || !is_array($data) || !isset($data['expires'])) {
                // Arquivo corrompido, remover
                unlink($file);
                return null;
            }
            
            if ($data['expires'] > time()) {
                // Carregar no cache de memória também
                self::$cache[$key] = $data;
                return $data['data'];
            } else {
                unlink($file);
            }
        }
        
        return null;
    }
    
    /**
     * Salvar item no cache
     */
    public static function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = self::$default_ttl;
        }
        
        $item = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        // Cache em memória
        self::$cache[$key] = $item;
        
        // Cache em arquivo (persistente)
        if (!file_exists(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        
        $file = self::$cache_dir . md5($key) . '.cache';
        file_put_contents($file, serialize($item), LOCK_EX);
    }
    
    /**
     * Cache para dados de municípios (TTL longo)
     */
    public static function getMunicipio($slug_estado, $slug_municipio) {
        $key = "municipio_{$slug_estado}_{$slug_municipio}";
        return self::get($key);
    }
    
    public static function setMunicipio($slug_estado, $slug_municipio, $data) {
        $key = "municipio_{$slug_estado}_{$slug_municipio}";
        self::set($key, $data, 7200); // 2 horas
    }
    
    /**
     * Cache para dados de parceiros (TTL médio)
     */
    public static function getParceiros($municipio_id, $categorias = []) {
        $key = "parceiros_{$municipio_id}_" . implode(',', $categorias);
        return self::get($key);
    }
    
    public static function setParceiros($municipio_id, $categorias, $data) {
        $key = "parceiros_{$municipio_id}_" . implode(',', $categorias);
        self::set($key, $data, 1800); // 30 minutos
    }
    
    /**
     * Cache para dados de estados/municípios (TTL muito longo)
     */
    public static function getEstados() {
        return self::get('estados_all');
    }
    
    public static function setEstados($data) {
        self::set('estados_all', $data, 86400); // 24 horas
    }
    
    public static function getMunicipios($estado_id) {
        return self::get("municipios_estado_{$estado_id}");
    }
    
    public static function setMunicipios($estado_id, $data) {
        self::set("municipios_estado_{$estado_id}", $data, 86400); // 24 horas
    }
    
    /**
     * Limpar cache
     */
    public static function clear($pattern = null) {
        if ($pattern) {
            // Limpar cache específico
            unset(self::$cache[$pattern]);
            $file = self::$cache_dir . md5($pattern) . '.cache';
            if (file_exists($file)) {
                unlink($file);
            }
        } else {
            // Limpar todo o cache
            self::$cache = [];
            if (is_dir(self::$cache_dir)) {
                $files = glob(self::$cache_dir . '*.cache');
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Limpar cache expirado
     */
    public static function cleanExpired() {
        $cleaned = 0;
        
        // Limpar cache de memória
        foreach (self::$cache as $key => $item) {
            if ($item['expires'] <= time()) {
                unset(self::$cache[$key]);
                $cleaned++;
            }
        }
        
        // Limpar cache de arquivo
        if (is_dir(self::$cache_dir)) {
            $files = glob(self::$cache_dir . '*.cache');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                
                // Verificar se o conteúdo não está vazio
                if (empty($content)) {
                    unlink($file);
                    $cleaned++;
                    continue;
                }
                
                $data = @unserialize($content);
                
                // Verificar se o unserialize foi bem-sucedido e se é um array válido
                if ($data === false || !is_array($data) || !isset($data['expires'])) {
                    // Arquivo corrompido, remover
                    unlink($file);
                    $cleaned++;
                    continue;
                }
                
                if ($data['expires'] <= time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Alias para cleanExpired (compatibilidade)
     */
    public static function cleanup() {
        return self::cleanExpired();
    }
    
    /**
     * Estatísticas do cache
     */
    public static function getStats() {
        $memory_count = count(self::$cache);
        $file_count = 0;
        
        if (is_dir(self::$cache_dir)) {
            $file_count = count(glob(self::$cache_dir . '*.cache'));
        }
        
        return [
            'memory_items' => $memory_count,
            'file_items' => $file_count,
            'total_items' => $memory_count + $file_count
        ];
    }
}

// Limpar cache expirado automaticamente (1% de chance)
if (rand(1, 100) === 1) {
    CacheManager::cleanExpired();
}
?>
