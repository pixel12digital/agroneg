<?php
require_once(__DIR__ . '/../config/db.php');
include 'includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['nivel'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'atualizar_status':
                $novo_status = $_POST['status'];
                $observacoes = $_POST['observacoes'] ?? '';
                $mensagem_id = (int)$_POST['mensagem_id'];
                
                $query = "UPDATE mensagens_contato SET status = ?, observacoes = ?, respondido_por = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssii", $novo_status, $observacoes, $_SESSION['user_id'], $mensagem_id);
                
                if ($stmt->execute()) {
                    $success_msg = 'Status atualizado com sucesso!';
                } else {
                    $error_msg = 'Erro ao atualizar status.';
                }
                break;
        }
    }
}

// Buscar mensagem específica
$mensagem = null;
if ($action === 'view' && $id > 0) {
    $query = "SELECT m.*, u.nome as respondido_por_nome 
              FROM mensagens_contato m 
              LEFT JOIN usuarios u ON m.respondido_por = u.id 
              WHERE m.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mensagem = $result->fetch_assoc();
    $stmt->close();
}

// Listar mensagens
$mensagens = [];
$query = "SELECT m.*, u.nome as respondido_por_nome 
          FROM mensagens_contato m 
          LEFT JOIN usuarios u ON m.respondido_por = u.id 
          ORDER BY m.data_envio DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mensagens[] = $row;
    }
}

// Verificar mensagens de sucesso nos parâmetros de URL
if (isset($_GET['success'])) {
    $success_msg = 'Operação realizada com sucesso!';
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'view') ? 'Visualizar Mensagem' : 'Mensagens de Contato'; ?>
    </h1>
    <?php if ($action === 'view'): ?>
    <div>
        <a href="mensagens.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
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

<?php if ($action === 'view' && $mensagem): ?>
<!-- Visualizar Mensagem -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mensagem #<?php echo $mensagem['id']; ?></h5>
        <span class="badge bg-<?php 
            echo match($mensagem['status']) {
                'novo' => 'danger',
                'lido' => 'warning',
                'respondido' => 'success',
                'arquivado' => 'secondary',
                default => 'primary'
            };
        ?>">
            <?php echo ucfirst($mensagem['status']); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($mensagem['nome']); ?></p>
                <p><strong>E-mail:</strong> <a href="mailto:<?php echo htmlspecialchars($mensagem['email']); ?>"><?php echo htmlspecialchars($mensagem['email']); ?></a></p>
                <?php if ($mensagem['telefone']): ?>
                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($mensagem['telefone']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <p><strong>Data de Envio:</strong> <?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?></p>
                <p><strong>Assunto:</strong> <?php echo htmlspecialchars($mensagem['assunto']); ?></p>
                <?php if ($mensagem['respondido_por_nome']): ?>
                <p><strong>Respondido por:</strong> <?php echo htmlspecialchars($mensagem['respondido_por_nome']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <h6>Mensagem:</h6>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br(htmlspecialchars($mensagem['mensagem'])); ?>
            </div>
        </div>
        
        <?php if ($mensagem['observacoes']): ?>
        <div class="mb-4">
            <h6>Observações:</h6>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br(htmlspecialchars($mensagem['observacoes'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="post" class="mt-4">
            <input type="hidden" name="action" value="atualizar_status">
            <input type="hidden" name="mensagem_id" value="<?php echo $mensagem['id']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="novo" <?php echo $mensagem['status'] === 'novo' ? 'selected' : ''; ?>>Novo</option>
                        <option value="lido" <?php echo $mensagem['status'] === 'lido' ? 'selected' : ''; ?>>Lido</option>
                        <option value="respondido" <?php echo $mensagem['status'] === 'respondido' ? 'selected' : ''; ?>>Respondido</option>
                        <option value="arquivado" <?php echo $mensagem['status'] === 'arquivado' ? 'selected' : ''; ?>>Arquivado</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($mensagem['observacoes'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Atualizar Status</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Lista de Mensagens -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Assunto</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mensagens as $msg): ?>
                    <tr>
                        <td><?php echo $msg['id']; ?></td>
                        <td><?php echo htmlspecialchars($msg['nome']); ?></td>
                        <td><?php echo htmlspecialchars($msg['email']); ?></td>
                        <td><?php echo htmlspecialchars($msg['assunto']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($msg['status']) {
                                    'novo' => 'danger',
                                    'lido' => 'warning',
                                    'respondido' => 'success',
                                    'arquivado' => 'secondary',
                                    default => 'primary'
                                };
                            ?>">
                                <?php echo ucfirst($msg['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?></td>
                        <td>
                            <a href="mensagens.php?action=view&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 