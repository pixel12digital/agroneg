<?php
require_once("config/db.php");

// Validação simples
if (
    empty($_POST['nome']) ||
    empty($_POST['email']) ||
    empty($_POST['assunto']) ||
    empty($_POST['mensagem'])
) {
    header('Location: contato.php?erro=1');
    exit;
}

// Dados do formulário
$nome = strip_tags($_POST['nome']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$telefone = !empty($_POST['telefone']) ? strip_tags($_POST['telefone']) : null;
$assunto = strip_tags($_POST['assunto']);
$mensagem = strip_tags($_POST['mensagem']);

if (!$email) {
    header('Location: contato.php?erro=1');
    exit;
}

try {
    // Inserir mensagem no banco de dados
    $query = "INSERT INTO mensagens_contato (nome, email, telefone, assunto, mensagem) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $nome, $email, $telefone, $assunto, $mensagem);
    
    if ($stmt->execute()) {
        // Aqui você pode implementar o envio real de e-mail se desejar
        header('Location: contato.php?sucesso=1');
    } else {
        throw new Exception("Erro ao salvar mensagem");
    }
} catch (Exception $e) {
    // Log do erro (em produção, você deve usar um sistema de logging adequado)
    error_log("Erro ao processar contato: " . $e->getMessage());
    header('Location: contato.php?erro=1');
}

exit; 