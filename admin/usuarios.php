<?php
ob_start();
// Página de gerenciamento de usuários
require_once(__DIR__ . '/../config/db.php');

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se a conexão foi estabelecida
if (!$conn) {
    die('Erro: Não foi possível conectar ao banco de dados');
}
include 'includes/header.php';

// Verificar se o usuário atual é administrador
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_admin = false;

$check_query = "SELECT nivel FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $is_admin = ($row['nivel'] === 'admin');
}
$stmt->close();

// Se não for administrador, redirecionar
if (!$is_admin) {
    header("Location: dashboard.php?error=unauthorized");
    exit;
}

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';

// Processar formulário de adicionar/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nome = $_POST['nome'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $nivel = $_POST['nivel'] ?? 'editor';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($usuario) || empty($email)) {
        $error_msg = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($action === 'add' && empty($senha)) {
        $error_msg = 'Por favor, defina uma senha para o usuário.';
    } elseif (($action === 'add' || !empty($senha)) && $senha !== $confirmar_senha) {
        $error_msg = 'As senhas digitadas não conferem.';
    } else {
        // Verificar se o nome de usuário ou email já existem
        $check_query = "SELECT id FROM usuarios WHERE (usuario = ? OR email = ?) AND id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ssi", $usuario, $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = ($result->num_rows > 0);
        $stmt->close();
        
        if ($exists) {
            $error_msg = 'Usuário ou email já estão em uso.';
        } else {
            if ($action === 'add') {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Adicionar novo usuário
                $query = "INSERT INTO usuarios (nome, usuario, email, senha, nivel, ativo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssi", $nome, $usuario, $email, $senha_hash, $nivel, $ativo);
                
                if ($stmt->execute()) {
                    $success_msg = 'Usuário adicionado com sucesso!';
                    header("Location: usuarios.php?success=added");
                    exit;
                } else {
                    $error_msg = 'Erro ao adicionar usuário: ' . $stmt->error;
                }
                $stmt->close();
            } else if ($action === 'edit' && $id > 0) {
                // Verificar se o e-mail já está em uso por outro usuário
                $check_query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param("si", $email, $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_msg = 'Este e-mail já está sendo usado por outro usuário.';
                } else {
                    // Atualizar usuário
                    if (!empty($senha)) {
                        // Atualizar com nova senha
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET nome = ?, usuario = ?, email = ?, senha = ?, nivel = ?, ativo = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssssii", $nome, $usuario, $email, $senha_hash, $nivel, $ativo, $id);
                    } else {
                        // Manter a senha atual
                        $query = "UPDATE usuarios SET nome = ?, usuario = ?, email = ?, nivel = ?, ativo = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssssii", $nome, $usuario, $email, $nivel, $ativo, $id);
                    }
                    
                    if ($stmt->execute()) {
                        $success_msg = 'Usuário atualizado com sucesso!';
                        header("Location: usuarios.php?success=updated");
                        exit;
                    } else {
                        $error_msg = 'Erro ao atualizar usuário: ' . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Excluir usuário
if ($action === 'delete' && $id > 0) {
    // Não permitir a exclusão do próprio usuário
    if ($id === $current_user_id) {
        $error_msg = 'Você não pode excluir seu próprio usuário.';
    } else {
        // Verificar se o usuário existe
        $query = "SELECT id FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Excluir o usuário
            $query = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_msg = 'Usuário excluído com sucesso!';
                // Redirecionar para a listagem
                header("Location: usuarios.php?success=deleted");
                exit;
            } else {
                $error_msg = 'Erro ao excluir usuário: ' . $stmt->error;
            }
        } else {
            $error_msg = 'Usuário não encontrado.';
        }
        $stmt->close();
    }
}

// Obter usuário para edição
$usuario_data = null;
if ($action === 'edit' && $id > 0) {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario_data = $result->fetch_assoc();
    } else {
        $error_msg = 'Usuário não encontrado.';
        $action = 'list'; // Volta para a listagem
    }
    $stmt->close();
}

// Listar usuários
$usuarios = [];
$query = "SELECT * FROM usuarios ORDER BY nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Verificar mensagens de sucesso nos parâmetros de URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_msg = 'Usuário adicionado com sucesso!';
    } else if ($_GET['success'] === 'updated') {
        $success_msg = 'Usuário atualizado com sucesso!';
    } else if ($_GET['success'] === 'deleted') {
        $success_msg = 'Usuário excluído com sucesso!';
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'add') ? 'Adicionar Novo Usuário' : (($action === 'edit') ? 'Editar Usuário' : 'Gerenciar Usuários'); ?>
    </h1>
    <?php if ($action === 'list'): ?>
    <div>
        <a href="usuarios.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Usuário
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Mensagens de alerta -->
<?php if (!empty($success_msg)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $success_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Tabela de Usuários -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Lista de Usuários</h6>
    </div>
    <div class="card-body">
        <?php if (empty($usuarios)): ?>
            <p class="text-center">Nenhum usuário cadastrado ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['nome']; ?></td>
                                <td><?php echo $u['usuario']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td>
                                    <?php if ($u['nivel'] === 'admin'): ?>
                                        <span class="badge bg-danger">Administrador</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Editor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['ativo'] == 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="usuarios.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($u['id'] !== $current_user_id): ?>
                                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $u['id']; ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    
                                    <!-- Modal de confirmação de exclusão -->
                                    <div class="modal fade" id="deleteModal<?php echo $u['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Tem certeza que deseja excluir o usuário <strong><?php echo $u['nome']; ?></strong>?
                                                    <br>Esta ação não pode ser desfeita.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <a href="usuarios.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger">Sim, Excluir</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Formulário de Adicionar/Editar -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold"><?php echo ($action === 'add') ? 'Adicionar Novo Usuário' : 'Editar Usuário'; ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="usuarios.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $id : ''; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome*</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo ($usuario_data['nome'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Nome de Usuário*</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo ($usuario_data['usuario'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email*</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo ($usuario_data['email'] ?? ''); ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label"><?php echo ($action === 'add') ? 'Senha*' : 'Nova Senha (deixe em branco para manter a atual)'; ?></label>
                    <input type="password" class="form-control" id="senha" name="senha" <?php echo ($action === 'add') ? 'required' : ''; ?>>
                </div>
                <div class="col-md-6">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nivel" class="form-label">Nível de Acesso*</label>
                    <select class="form-select" id="nivel" name="nivel" required>
                        <option value="editor" <?php echo (isset($usuario_data['nivel']) && $usuario_data['nivel'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                        <option value="admin" <?php echo (isset($usuario_data['nivel']) && $usuario_data['nivel'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo (!isset($usuario_data['ativo']) || $usuario_data['ativo'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i><?php echo ($action === 'add') ? 'Adicionar' : 'Salvar Alterações'; ?>
                </button>
                <a href="usuarios.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
                
                <?php if ($action === 'edit' && $id !== $current_user_id): ?>
                    <button type="button" class="btn btn-danger float-end" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </button>
                    
                    <!-- Modal de confirmação de exclusão -->
                    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Tem certeza que deseja excluir este usuário?
                                    <br>Esta ação não pode ser desfeita.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <a href="usuarios.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">Sim, Excluir</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 