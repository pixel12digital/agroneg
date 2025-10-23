<?php
/**
 * Sistema de Limpeza Automática de Cache
 * Executa limpeza de cache expirado para manter o sistema otimizado
 */

require_once(__DIR__ . '/cache_manager.php');

class CacheCleanup {
    private static $cleanup_interval = 300; // 5 minutos
    private static $max_cache_age = 86400; // 24 horas
    private static $cache_dir = __DIR__ . '/../cache/';
    
    /**
     * Executar limpeza se necessário
     */
    public static function maybeCleanup() {
        $lock_file = self::$cache_dir . '.cleanup_lock';
        
        // Verificar se já está executando limpeza
        if (file_exists($lock_file)) {
            $lock_time = (int)file_get_contents($lock_file);
            if (time() - $lock_time < 60) { // Lock válido por 1 minuto
                return;
            }
        }
        
        // Verificar se é hora da limpeza
        $last_cleanup_file = self::$cache_dir . '.last_cleanup';
        $last_cleanup = 0;
        
        if (file_exists($last_cleanup_file)) {
            $last_cleanup = (int)file_get_contents($last_cleanup_file);
        }
        
        if (time() - $last_cleanup < self::$cleanup_interval) {
            return;
        }
        
        // Executar limpeza
        self::cleanup();
    }
    
    /**
     * Executar limpeza completa
     */
    public static function cleanup() {
        $lock_file = self::$cache_dir . '.cleanup_lock';
        $last_cleanup_file = self::$cache_dir . '.last_cleanup';
        
        // Criar lock
        file_put_contents($lock_file, time(), LOCK_EX);
        
        try {
            // Limpar cache expirado
            $cleaned = CacheManager::cleanup();
            
            // Limpar arquivos antigos
            $cleaned += self::cleanOldFiles();
            
            // Limpar rate limit antigo
            $cleaned += self::cleanRateLimitFiles();
            
            // Atualizar timestamp da última limpeza
            file_put_contents($last_cleanup_file, time(), LOCK_EX);
            
            error_log("Cache cleanup executado: $cleaned itens removidos");
            
        } catch (Exception $e) {
            error_log("Erro na limpeza de cache: " . $e->getMessage());
        } finally {
            // Remover lock
            if (file_exists($lock_file)) {
                unlink($lock_file);
            }
        }
    }
    
    /**
     * Limpar arquivos antigos
     */
    private static function cleanOldFiles() {
        $cleaned = 0;
        
        if (!is_dir(self::$cache_dir)) {
            return $cleaned;
        }
        
        $files = glob(self::$cache_dir . '*.cache');
        $cutoff_time = time() - self::$max_cache_age;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Limpar arquivos de rate limit antigos
     */
    private static function cleanRateLimitFiles() {
        $cleaned = 0;
        $rate_limit_dir = self::$cache_dir . 'rate_limit/';
        
        if (!is_dir($rate_limit_dir)) {
            return $cleaned;
        }
        
        $files = glob($rate_limit_dir . '*.json');
        $cutoff_time = time() - 3600; // 1 hora
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Forçar limpeza completa
     */
    public static function forceCleanup() {
        $cleaned = 0;
        
        // Limpar todo o cache
        CacheManager::clear();
        
        // Limpar arquivos de rate limit
        $rate_limit_dir = self::$cache_dir . 'rate_limit/';
        if (is_dir($rate_limit_dir)) {
            $files = glob($rate_limit_dir . '*.json');
            foreach ($files as $file) {
                unlink($file);
                $cleaned++;
            }
        }
        
        // Limpar arquivos de controle
        $control_files = [
            self::$cache_dir . '.cleanup_lock',
            self::$cache_dir . '.last_cleanup',
            __DIR__ . '/../.global_rate_limit',
            __DIR__ . '/../.db_blocked'
        ];
        
        foreach ($control_files as $file) {
            if (file_exists($file)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}

// Executar limpeza automática se necessário
CacheCleanup::maybeCleanup();
?>

