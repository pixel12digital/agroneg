<?php
// Configurações de cabeçalho para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Em produção, restrinja para o seu domínio

// Incluir arquivo de conexão com o banco de dados
require_once(__DIR__ . '/../config/db.php');

// A API agora espera 'estado_id' em vez de 'estado' (sigla)
$estado_id = isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null;

if (!$estado_id) {
    // Se 'estado_id' não for fornecido ou não for um inteiro, retorna erro
    http_response_code(400); // Bad Request
    echo json_encode(['erro' => 'ID do estado inválido ou não especificado.']);
    exit;
}

// A consulta agora é direta, sem precisar buscar o ID primeiro
$query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
$stmt_municipios = $conn->prepare($query_municipios);

if (!$stmt_municipios) {
    // Adiciona verificação de erro na preparação da consulta
    http_response_code(500); // Internal Server Error
    echo json_encode(['erro' => 'Falha ao preparar a consulta de municípios.']);
    exit;
}

$stmt_municipios->bind_param("i", $estado_id);
$stmt_municipios->execute();
$resultado_municipios = $stmt_municipios->get_result();

// Preparar o array de resposta
$municipios = [];
while ($municipio = $resultado_municipios->fetch_assoc()) {
    $municipios[] = [
        'id' => $municipio['id'],
        'nome' => $municipio['nome'],
    ];
}

// Retornar os municípios como JSON
echo json_encode($municipios);

// Fechar conexão
$stmt_municipios->close();
$conn->close(); 