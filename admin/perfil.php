<?php
// Página de perfil do usuário
require_once(__DIR__ . '/../config/db.php');

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se a conexão foi estabelecida
if (!$conn) {
    die('Erro: Não foi possível conectar ao banco de dados');
}
include 'includes/header.php';

// Obter dados do usuário logado
$user_id = $_SESSION["user_id"];
$usuario = [];

$query = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
} else {
    // Redirecionar se usuário não for encontrado
    header("Location: dashboard.php");
    exit();
}

// Processar atualizações de perfil
$mensagem = '';
$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email)) {
        $erro = "Nome e email são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } elseif (!empty($nova_senha)) {
        // Se quiser mudar a senha
        if ($senha_atual !== $usuario['senha']) {
            $erro = "Senha atual incorreta.";
        } elseif ($nova_senha !== $confirmar_senha) {
            $erro = "A nova senha e a confirmação não correspondem.";
        } elseif (strlen($nova_senha) < 6) {
            $erro = "A nova senha deve ter pelo menos 6 caracteres.";
        }
    }
    
    // Verificar se o email já existe para outro usuário
    if (empty($erro) && $email !== $usuario['email']) {
        $check_email = "SELECT COUNT(*) as count FROM usuarios WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $email_result = $stmt->get_result()->fetch_assoc();
        if ($email_result['count'] > 0) {
            $erro = "Este email já está sendo utilizado por outro usuário.";
        }
    }
    
    // Atualizar o perfil
    if (empty($erro)) {
        if (!empty($nova_senha)) {
            // Atualizar com nova senha
            $query = "UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nome, $email, $nova_senha, $user_id);
        } else {
            // Atualizar sem alterar senha
            $query = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $nome, $email, $user_id);
        }
        
        if ($stmt->execute()) {
            $mensagem = "Perfil atualizado com sucesso!";
            $_SESSION['nome_usuario'] = $nome; // Atualizar o nome na sessão
            
            // Recarregar os dados do usuário
            $query = "SELECT * FROM usuarios WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
            }
        } else {
            $erro = "Erro ao atualizar perfil. Tente novamente.";
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">Meu Perfil</h1>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações do Usuário</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nome de usuário</label>
                        <input type="text" class="form-control" id="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" readonly disabled>
                        <div class="form-text text-muted">O nome de usuário não pode ser alterado.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nivel" class="form-label">Nível de acesso</label>
                        <input type="text" class="form-control" id="nivel" value="<?php echo ucfirst(htmlspecialchars($usuario['nivel'])); ?>" readonly disabled>
                        <div class="form-text text-muted">O nível de acesso só pode ser alterado por um administrador.</div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3">Alterar Senha</h5>
                    
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                    </div>
                    
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="atualizar_perfil" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 