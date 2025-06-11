<?php
// Configuração do ambiente
$ambiente = 'desenvolvimento'; // ou 'producao'

if ($ambiente === 'producao') {
    // Configurações da Hostinger
    $servername = "localhost"; // Hostinger usa localhost para MySQL
    $username = "u342734079_agroneg";
    $password = "Los@ngo081081";
    $dbname = "u342734079_agroneg";
} else {
    // Configurações de desenvolvimento (XAMPP)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "agro-neg";
}

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checar conexão
if ($conn->connect_error) {
    // Em produção, não exibir detalhes do erro
    if ($ambiente === 'producao') {
        error_log("Erro de conexão com o banco de dados: " . $conn->connect_error);
        die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
    } else {
        die("Conexão falhou: " . $conn->connect_error);
    }
}

// Configurar o charset para UTF-8
$conn->set_charset("utf8mb4");
mysqli_query($conn, "SET NAMES 'utf8mb4'");
mysqli_query($conn, "SET character_set_client = 'utf8mb4'");
mysqli_query($conn, "SET character_set_results = 'utf8mb4'");
mysqli_query($conn, "SET collation_connection = 'utf8mb4_unicode_ci'");

// echo "Conectado com sucesso!";
?> 