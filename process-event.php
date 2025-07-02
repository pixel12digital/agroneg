<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once("config/db.php");

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin/eventos.php');
    exit;
}

// Validar e sanitizar dados
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
$descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
$categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
$modalidade = filter_input(INPUT_POST, 'modalidade', FILTER_SANITIZE_STRING);
$data_inicio = $_POST['data_inicio'] ?? null;
$data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
$municipio_id = filter_input(INPUT_POST, 'municipio_id', FILTER_VALIDATE_INT);
$estado_id = filter_input(INPUT_POST, 'estado_id', FILTER_VALIDATE_INT);

// Validar campos obrigatórios
if (!$titulo || !$categoria || !$modalidade || !$data_inicio || !$status || !$municipio_id || !$estado_id) {
    $_SESSION['erro'] = "Por favor, preencha todos os campos obrigatórios.";
    $redirect_url = $id ? "adicionar-evento.php?id=$id" : "adicionar-evento.php";
    header("Location: $redirect_url");
    exit;
}

// Gerar um slug simples (sem verificação de unicidade para evitar erros complexos)
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));

try {
    if ($id) {
        // ATUALIZAR evento existente
        $sql = "UPDATE eventos_municipio SET municipio_id = ?, estado_id = ?, nome = ?, slug = ?, descricao = ?, categoria = ?, modalidade = ?, data_inicio = ?, data_fim = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssssssi", $municipio_id, $estado_id, $titulo, $slug, $descricao, $categoria, $modalidade, $data_inicio, $data_fim, $status, $id);
        $_SESSION['sucesso'] = "Evento atualizado com sucesso!";

    } else {
        // INSERIR novo evento
        $sql = "INSERT INTO eventos_municipio (municipio_id, estado_id, nome, slug, descricao, categoria, modalidade, data_inicio, data_fim, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssssss", $municipio_id, $estado_id, $titulo, $slug, $descricao, $categoria, $modalidade, $data_inicio, $data_fim, $status);
        $_SESSION['sucesso'] = "Evento cadastrado com sucesso!";
    }
    
    if ($stmt->execute()) {
        // Redireciona para o painel de admin com mensagem de sucesso
        header('Location: admin/eventos.php?acao=sucesso');
        exit;
    } else {
        throw new Exception("Erro na operação com o banco de dados: " . $stmt->error);
    }

} catch (Exception $e) {
    // Em caso de erro, redireciona de volta para o formulário com a mensagem
    $_SESSION['erro'] = "Erro ao processar o evento: " . $e->getMessage();
    $redirect_url = $id ? "adicionar-evento.php?id=$id" : "adicionar-evento.php";
    header("Location: $redirect_url");
    exit;
}
?> 