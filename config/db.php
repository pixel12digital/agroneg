<?php
// Define o fuso horário padrão para toda a aplicação, corrigindo problemas de data
date_default_timezone_set('America/Sao_Paulo');

// —­­— Limpa o OPcache (garante que não fique versão antiga em cache)
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

/* ------------------------------------------------------------------
   2. Credenciais (sempre banco remoto Hostinger)
-------------------------------------------------------------------*/
$servername = "srv1607.hstgr.io";
$username   = "u342734079_agroneg";
$password   = "o6Ve3mAjf~";
$dbname     = "u342734079_agroneg";

/* ------------------------------------------------------------------
   3. Conexão
-------------------------------------------------------------------*/
$conn = new mysqli($servername, $username, $password, $dbname);

/* Log para conferência (agora as variáveis existem) */
error_log("DB CONFIG ATIVO → ambiente={$ambiente}; user={$username}; db={$dbname}");

/* ------------------------------------------------------------------
   4. Tratamento de erro de conexão
-------------------------------------------------------------------*/
if ($conn->connect_error) {
    error_log("Erro de conexão com o banco: " . $conn->connect_error);
    die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
}

/* ------------------------------------------------------------------
   5. Charset UTF-8
-------------------------------------------------------------------*/
$conn->set_charset("utf8mb4");
mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET character_set_client = 'utf8mb4'");
mysqli_query($conn, "SET character_set_results = 'utf8mb4'");
mysqli_query($conn, "SET collation_connection = 'utf8mb4_unicode_ci'");
// echo "Conectado com sucesso!";
?> 