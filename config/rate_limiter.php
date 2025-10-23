<?php
/**
 * Sistema de Rate Limiting para APIs
 * Previne requisições excessivas que podem derrubar o site
 */

class RateLimiter {
    private $cache_dir;
    private $max_requests;
    private $time_window;
    
    public function __construct($max_requests = 50, $time_window = 60) { // 50 requisições por minuto (muito mais tolerante)
        $this->cache_dir = __DIR__ . '/../cache/rate_limit/';
        $this->max_requests = $max_requests;
        $this->time_window = $time_window;
        
        // Criar diretório se não existir
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    public function isAllowed($identifier = null) {
        // Usar IP do cliente como identificador padrão
        if (!$identifier) {
            $identifier = $this->getClientIP();
        }
        
        $cache_file = $this->cache_dir . md5($identifier) . '.json';
        $current_time = time();
        
        // Carregar dados existentes
        $data = [];
        if (file_exists($cache_file)) {
            $content = file_get_contents($cache_file);
            $data = json_decode($content, true) ?: [];
        }
        
        // Limpar requisições antigas
        $data = array_filter($data, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < $this->time_window;
        });
        
        // Verificar se excedeu o limite
        if (count($data) >= $this->max_requests) {
            return false;
        }
        
        // Adicionar nova requisição
        $data[] = $current_time;
        
        // Salvar dados
        file_put_contents($cache_file, json_encode($data));
        
        return true;
    }
    
    public function getRemainingRequests($identifier = null) {
        if (!$identifier) {
            $identifier = $this->getClientIP();
        }
        
        $cache_file = $this->cache_dir . md5($identifier) . '.json';
        $current_time = time();
        
        if (!file_exists($cache_file)) {
            return $this->max_requests;
        }
        
        $content = file_get_contents($cache_file);
        $data = json_decode($content, true) ?: [];
        
        // Limpar requisições antigas
        $data = array_filter($data, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < $this->time_window;
        });
        
        return max(0, $this->max_requests - count($data));
    }
    
    public function getResetTime($identifier = null) {
        if (!$identifier) {
            $identifier = $this->getClientIP();
        }
        
        $cache_file = $this->cache_dir . md5($identifier) . '.json';
        
        if (!file_exists($cache_file)) {
            return time() + $this->time_window;
        }
        
        $content = file_get_contents($cache_file);
        $data = json_decode($content, true) ?: [];
        
        if (empty($data)) {
            return time() + $this->time_window;
        }
        
        $oldest_request = min($data);
        return $oldest_request + $this->time_window;
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function cleanup() {
        $files = glob($this->cache_dir . '*.json');
        $current_time = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?: [];
            
            // Limpar requisições antigas
            $data = array_filter($data, function($timestamp) use ($current_time) {
                return ($current_time - $timestamp) < $this->time_window;
            });
            
            if (empty($data)) {
                unlink($file);
                $cleaned++;
            } else {
                file_put_contents($file, json_encode($data));
            }
        }
        
        return $cleaned;
    }
}

// Função helper para usar o rate limiter
function checkRateLimit($max_requests = 50, $time_window = 60) { // 50 requisições por minuto (muito mais tolerante)
    // Verificar se está em modo de economia
    if (isEconomyMode()) {
        http_response_code(503);
        echo json_encode(['erro' => 'Sistema em modo de economia. Tente novamente em alguns minutos.']);
        exit;
    }
    
    $limiter = new RateLimiter($max_requests, $time_window);
    
    if (!$limiter->isAllowed()) {
        $reset_time = $limiter->getResetTime();
        $remaining = $limiter->getRemainingRequests();
        
        // Em produção: ativar modo de economia se muitas requisições
        if (!$GLOBALS['isLocal']) {
            $block_file = __DIR__ . '/../.db_blocked';
            $block_until = time() + 1800; // 30 minutos
            file_put_contents($block_file, $block_until, LOCK_EX);
        }
        
        http_response_code(429);
        header('Content-Type: application/json');
        header('X-RateLimit-Limit: ' . $max_requests);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $reset_time);
        header('Retry-After: ' . ($reset_time - time()));
        
        echo json_encode([
            'erro' => 'Muitas requisições. Sistema temporariamente bloqueado.',
            'reset_time' => date('Y-m-d H:i:s', $reset_time),
            'remaining' => $remaining
        ]);
        
        exit;
    }
    
    // Registrar requisição global
    registerGlobalRequest();
    
    return $limiter;
}

/**
 * Verificar se o sistema está em modo de economia
 */
function isEconomyMode() {
    // Verificar se há arquivo de bloqueio
    $block_file = __DIR__ . '/../.db_blocked';
    if (file_exists($block_file)) {
        $block_until = (int)file_get_contents($block_file);
        return time() < $block_until;
    }
    
    // Verificar se há muitas requisições recentes
    $global_limit_file = __DIR__ . '/../.global_rate_limit';
    if (file_exists($global_limit_file)) {
        $data = json_decode(file_get_contents($global_limit_file), true);
        $current_hour = date('Y-m-d H:00:00');
        
        if (isset($data[$current_hour]) && $data[$current_hour] > 500) { // 500 requisições por hora (muito mais tolerante)
            return true;
        }
    }
    
    return false;
}

/**
 * Registrar requisição global
 */
function registerGlobalRequest() {
    $global_limit_file = __DIR__ . '/../.global_rate_limit';
    $current_hour = date('Y-m-d H:00:00');
    
    $data = [];
    if (file_exists($global_limit_file)) {
        $data = json_decode(file_get_contents($global_limit_file), true) ?: [];
    }
    
    $data[$current_hour] = ($data[$current_hour] ?? 0) + 1;
    
    // Manter apenas dados das últimas 24 horas
    $cutoff = time() - 86400;
    foreach ($data as $hour => $count) {
        if (strtotime($hour) < $cutoff) {
            unset($data[$hour]);
        }
    }
    
    file_put_contents($global_limit_file, json_encode($data), LOCK_EX);
}
?>
