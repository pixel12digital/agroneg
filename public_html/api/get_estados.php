<?php
// Configurações de cabeçalho para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/db.php';

// Consulta para obter todos os estados
$query = "SELECT id, nome, sigla FROM estados ORDER BY nome ASC";
$resultado = $conn->query($query);

// Preparar o array de resposta
$estados = [];
while ($estado = $resultado->fetch_assoc()) {
    $estados[] = [
        'id' => $estado['id'],
        'nome' => $estado['nome'],
        'sigla' => $estado['sigla']
    ];
}

// Retornar os estados como JSON
echo json_encode($estados);

// Fechar conexão
$conn->close(); 