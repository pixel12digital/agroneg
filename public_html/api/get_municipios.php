<?php
// Configurações de cabeçalho para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/db.php';

// Verificar se o parâmetro estado foi passado
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;

if (!$estado) {
    http_response_code(400);
    echo json_encode(['erro' => 'Estado não especificado']);
    exit;
}

// Consulta para obter o ID do estado pelo código (sigla)
$query_estado = "SELECT id FROM estados WHERE sigla = ?";
$stmt_estado = $conn->prepare($query_estado);
$stmt_estado->bind_param("s", $estado);
$stmt_estado->execute();
$resultado_estado = $stmt_estado->get_result();

if ($resultado_estado->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['erro' => 'Estado não encontrado']);
    exit;
}

$estado_id = $resultado_estado->fetch_assoc()['id'];

// Consulta para obter municípios do estado
$query_municipios = "SELECT id, nome, slug FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
$stmt_municipios = $conn->prepare($query_municipios);
$stmt_municipios->bind_param("i", $estado_id);
$stmt_municipios->execute();
$resultado_municipios = $stmt_municipios->get_result();

// Preparar o array de resposta
$municipios = [];
while ($municipio = $resultado_municipios->fetch_assoc()) {
    $municipios[] = [
        'id' => $municipio['id'],
        'nome' => $municipio['nome'],
        'slug' => $municipio['slug']
    ];
}

// Retornar os municípios como JSON
echo json_encode($municipios);

// Fechar conexão
$stmt_estado->close();
$stmt_municipios->close();
$conn->close(); 