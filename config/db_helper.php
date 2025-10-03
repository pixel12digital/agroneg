<?php
/**
 * Arquivo auxiliar para funções de banco de dados
 * Usado para otimizar consultas e reduzir carga de conexões
 */

require_once __DIR__ . '/db.dart';

class DBHelper {
    private static $query_count = 0;
    private static $error_count = 0;
    
    /**
     * Executa uma consulta preparada com tratamento de erro
     */
    public static function executePrepared($sql, $types = "", $params = []) {
        self::$query_count++;
        
        try {
            $stmt = $GLOBALS['AGRONEG_DB_CONNECTION']->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Erro ao preparar SQL: " . $GLOBALS['AGRONEG_DB_CONNECTION'] . get_error());
            }
            
            if ($types && $params) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception("Erro na execução: " . $stmt->error);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            self::$error_count++;
            error_log("DB Helper Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Busca um único registro
     */
    public static function fetchOne($sql, $types = "", $params = []) {
        $stmt = self::executePrepared($sql, $types, $params);
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
    
    /**
     * Busca múltiplos registros
     */
    public static function fetchAll($sql, $types = "", $params = []) {
        $stmt = self::executePrepared($sql, $types, $params);
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
    
    /**
     * Insere um registro e retorna o ID
     */
    public static function insert($sql, $types = "", $params = []) {
        $stmt = self::executePrepared($sql, $types, $params);
        $insert_id = $GLOBALS['AGRONEG_DB_CONNECTION']->insert_id;
        $stmt->close();
        return $insert_id;
    }
    
    /**
     * Atualiza registros e retorna quantidade de linhas afetadas
     */
    public static function update($sql, $types = "", $params = []) {
        $stmt = self::executePrepared($sql, $types, $params);
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows;
    }
    
    /**
     * Remove registros e retorna quantidade de linhas afetadas
     */
    public static function delete($sql, $types = "", $params = []) {
        return self::update($sql, $types, $params);
    }
    
    /**
     * Escapa string para prevenir SQL injection
     */
    public static function escapeString($string) {
        return $GLOBALS['AGRONEG_DB_CONNECTION']->real_escape_string($string);
    }
    
    /**
     * Inicia transação
     */
    public static function beginTransaction() {
        return $GLOBALS['AGRONEG_DB_CONNECTION']->autocommit(false);
    }
    
    /**
     * Confirma transação
     */
    public static function commit() {
        $result = $GLOBALS['AGRONEG_DB_CONNECTION']->commit();
        $GLOBALS['AGRONEG_DB_CONNECTION']->autocommit(true);
        return $result;
    }
    
    /**
     * Desfaz transação
     */
    public static function rollback() {
        $result = $GLOBALS['AGRONEG_DB_CONNECTION']->rollback();
        $GLOBALS['AGRONEG_DB_CONNECTION']->autocommit(true);
        return $result;
    }
}

/**
 * Funções de conveniência (para compatibilidade com código existente)
 */
function db_fetch_one($sql, $types = "", $params = []) {
    return DBHelper::fetchOne($sql, $types, $params);
}

function db_fetch_all($sql, $types = "", $params = []) {
    return DBHelper::fetchAll($sql, $types, $params);
}

function db_insert($sql, $types = "", $params = []) {
    return DBHelper::insert($sql, $types, $params);
}

function db_update($sql, $types = "", $params = []) {
    return DBHelper::update($sql, $types, $params);
}

function db_delete($sql, $types = "", $params = []) {
    return DBHelper::delete($sql, $types, $params);
}

// Registrar função de fechamento para mostrar estatísticas
register_shutdown_function(function() {
    if ($GLOBALS['ambiente'] === 'desenvolvimento') {
        error_log("DB STATS: Query count: " . DBHelper::$query_count . ", Error count: " . $DBHelper::$error_count);
    }
});
?>
