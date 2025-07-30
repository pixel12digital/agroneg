<?php
// Configurações de cabeçalho para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Em produção, restrinja para o seu domínio

// Ativar logs de erro para debug
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log para debug
error_log("API get_municipios.php chamada - " . date('Y-m-d H:i:s'));

// Incluir arquivo de conexão com o banco de dados
require_once(__DIR__ . '/../config/db.php');

// A API agora espera 'estado_id' em vez de 'estado' (sigla)
$estado_id = isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null;

// Log para debug
error_log("Estado ID recebido: " . ($estado_id ?? 'null'));

if (!$estado_id) {
    // Se 'estado_id' não for fornecido ou não for um inteiro, retorna erro
    error_log("Erro: ID do estado inválido ou não especificado");
    http_response_code(400); // Bad Request
    echo json_encode(['erro' => 'ID do estado inválido ou não especificado.']);
    exit;
}

// A consulta agora é direta, sem precisar buscar o ID primeiro
$query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
error_log("Query SQL: " . $query_municipios . " com estado_id: " . $estado_id);

$stmt_municipios = $conn->prepare($query_municipios);

if (!$stmt_municipios) {
    // Adiciona verificação de erro na preparação da consulta
    error_log("Erro na preparação da consulta: " . $conn->error);
    http_response_code(500); // Internal Server Error
    echo json_encode(['erro' => 'Falha ao preparar a consulta de municípios.']);
    exit;
}

$stmt_municipios->bind_param("i", $estado_id);
$stmt_municipios->execute();
$resultado_municipios = $stmt_municipios->get_result();

// Log para debug
error_log("Resultado da consulta - num_rows: " . $resultado_municipios->num_rows);

// Preparar o array de resposta
$municipios = [];
while ($municipio = $resultado_municipios->fetch_assoc()) {
    $municipios[] = [
        'id' => $municipio['id'],
        'nome' => $municipio['nome'],
    ];
}

error_log("Municípios encontrados: " . count($municipios));

// Retornar os municípios como JSON
echo json_encode($municipios);

// Fechar conexão
$stmt_municipios->close();
$conn->close(); 