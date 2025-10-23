<?php
/**
 * CIRCUIT BREAKER - Sistema à Prova de Falhas
 * GARANTE que o banco não será sobrecarregado
 */

class CircuitBreaker {
    private static $failure_threshold = 10; // 10 falhas = circuito aberto (mais tolerante)
    private static $recovery_timeout = 60; // 1 minuto para tentar recuperar (mais rápido)
    private static $half_open_max_calls = 5; // Máximo 5 tentativas em half-open (mais permissivo)
    
    private static $state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
    private static $failure_count = 0;
    private static $last_failure_time = 0;
    private static $half_open_calls = 0;
    
    /**
     * VERIFICAR se pode fazer conexão com banco
     * RETORNA FALSE se banco está sobrecarregado
     */
    public static function canConnect() {
        // MODO DESENVOLVIMENTO: Sempre permitir conexão
        if (isset($GLOBALS['isLocal']) && $GLOBALS['isLocal']) {
            return true;
        }
        $current_time = time();
        
        switch (self::$state) {
            case 'CLOSED':
                // Circuito fechado - pode conectar
                return true;
                
            case 'OPEN':
                // Circuito aberto - verificar se pode tentar recuperar
                if (($current_time - self::$last_failure_time) >= self::$recovery_timeout) {
                    self::$state = 'HALF_OPEN';
                    self::$half_open_calls = 0;
                    return true;
                }
                return false;
                
            case 'HALF_OPEN':
                // Half-open - limitar tentativas
                if (self::$half_open_calls < self::$half_open_max_calls) {
                    self::$half_open_calls++;
                    return true;
                }
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * REGISTRAR sucesso na conexão
     */
    public static function onSuccess() {
        self::$failure_count = 0;
        self::$state = 'CLOSED';
        self::$half_open_calls = 0;
    }
    
    /**
     * REGISTRAR falha na conexão
     */
    public static function onFailure() {
        self::$failure_count++;
        self::$last_failure_time = time();
        
        if (self::$failure_count >= self::$failure_threshold) {
            self::$state = 'OPEN';
            
            // ATIVAR MODO EMERGÊNCIA
            self::activateEmergencyMode();
        }
    }
    
    /**
     * ATIVAR MODO EMERGÊNCIA - Bloqueia TUDO por 2 horas
     */
    private static function activateEmergencyMode() {
        $block_file = __DIR__ . '/../.emergency_block';
        $block_until = time() + 300; // 5 MINUTOS (muito mais tolerante)
        file_put_contents($block_file, $block_until, LOCK_EX);
        
        error_log("🚨 EMERGENCY MODE ACTIVATED - Sistema bloqueado por 2 horas");
    }
    
    /**
     * VERIFICAR se está em modo emergência
     */
    public static function isEmergencyMode() {
        $block_file = __DIR__ . '/../.emergency_block';
        if (file_exists($block_file)) {
            $block_until = (int)file_get_contents($block_file);
            return time() < $block_until;
        }
        return false;
    }
    
    /**
     * OBTER status do circuito
     */
    public static function getStatus() {
        return [
            'state' => self::$state,
            'failure_count' => self::$failure_count,
            'last_failure_time' => self::$last_failure_time,
            'half_open_calls' => self::$half_open_calls,
            'emergency_mode' => self::isEmergencyMode()
        ];
    }
    
    /**
     * RESETAR circuito (apenas para emergências)
     */
    public static function reset() {
        self::$state = 'CLOSED';
        self::$failure_count = 0;
        self::$last_failure_time = 0;
        self::$half_open_calls = 0;
        
        // Remover arquivos de bloqueio
        $files = [
            __DIR__ . '/../.emergency_block',
            __DIR__ . '/../.db_blocked',
            __DIR__ . '/../.apis_disabled'
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        error_log("🔄 Circuit Breaker RESETADO - Sistema liberado");
    }
    
    /**
     * FORÇAR sistema para funcionar (modo desenvolvimento)
     */
    public static function forceOpen() {
        self::$state = 'CLOSED';
        self::$failure_count = 0;
        self::$last_failure_time = 0;
        self::$half_open_calls = 0;
        
        // Remover TODOS os arquivos de bloqueio
        $files = [
            __DIR__ . '/../.emergency_block',
            __DIR__ . '/../.db_blocked',
            __DIR__ . '/../.apis_disabled',
            __DIR__ . '/../.global_rate_limit'
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        error_log("🚀 Circuit Breaker FORÇADO ABERTO - Modo desenvolvimento");
    }
}

// Verificar modo emergência a cada requisição
if (CircuitBreaker::isEmergencyMode()) {
    // SISTEMA EMERGÊNCIA - Retornar página estática
    http_response_code(503);
    die('
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AgroNeg - Manutenção</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; }
            .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚧 AgroNeg - Sistema em Manutenção</h1>
            <div class="alert">
                <h3>Sistema Temporariamente Indisponível</h3>
                <p>O sistema está passando por manutenção preventiva para garantir melhor performance.</p>
                <p><strong>Previsão de retorno:</strong> Em breve</p>
                <p>Pedimos desculpas pelo inconveniente.</p>
            </div>
            <a href="javascript:location.reload()" class="btn">🔄 Tentar Novamente</a>
        </div>
    </body>
    </html>
    ');
}
?>

