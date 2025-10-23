<?php
require_once(__DIR__ . '/../config/db.php');

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se a conexão foi estabelecida
if (!$conn) {
    die('Erro: Não foi possível conectar ao banco de dados');
}

// Garantir que todos os outputs sejam em UTF-8
header('Content-Type: text/html; charset=utf-8');

include 'includes/header.php';

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';

// Processar formulário de adicionar/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    
    // Criar slug para a tag
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', 
        iconv('UTF-8', 'ASCII//TRANSLIT', trim($nome))));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Validar campos obrigatórios
    if (empty($nome)) {
        $error_msg = 'Por favor, preencha o nome da tag.';
    } else {
        if ($action === 'add') {
            // Adicionar nova tag
            $query = "INSERT INTO tags (nome, slug, descricao) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $nome, $slug, $descricao);
            
            if ($stmt->execute()) {
                $success_msg = 'Tag adicionada com sucesso!';
                header("Location: tags.php?success=added");
                exit;
            } else {
                $error_msg = 'Erro ao adicionar tag: ' . $stmt->error;
            }
            $stmt->close();
        } else if ($action === 'edit' && $id > 0) {
            // Atualizar tag existente
            $query = "UPDATE tags SET nome = ?, slug = ?, descricao = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nome, $slug, $descricao, $id);
            
            if ($stmt->execute()) {
                $success_msg = 'Tag atualizada com sucesso!';
                header("Location: tags.php?success=updated");
                exit;
            } else {
                $error_msg = 'Erro ao atualizar tag: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Excluir tag
if ($action === 'delete' && $id > 0) {
    // Verificar se a tag está sendo usada por algum parceiro
    $check_query = "SELECT COUNT(*) AS total FROM parceiros_tags WHERE tag_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $error_msg = 'Esta tag não pode ser excluída pois está associada a ' . $row['total'] . ' parceiro(s).';
        $action = 'list';
    } else {
        // Excluir a tag
        $query = "DELETE FROM tags WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_msg = 'Tag excluída com sucesso!';
            header("Location: tags.php?success=deleted");
            exit;
        } else {
            $error_msg = 'Erro ao excluir tag: ' . $stmt->error;
        }
    }
    $stmt->close();
}

// Obter tag para edição
$tag_data = null;
if ($action === 'edit' && $id > 0) {
    $query = "SELECT * FROM tags WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $tag_data = $result->fetch_assoc();
    } else {
        $error_msg = 'Tag não encontrada.';
        $action = 'list'; // Volta para a listagem
    }
    $stmt->close();
}

// Listar tags
$tags = [];
$query = "SELECT t.*, COUNT(pt.parceiro_id) AS total_parceiros 
          FROM tags t 
          LEFT JOIN parceiros_tags pt ON t.id = pt.tag_id 
          GROUP BY t.id 
          ORDER BY t.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}

// Verificar mensagens de sucesso nos parâmetros de URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_msg = 'Tag adicionada com sucesso!';
    } else if ($_GET['success'] === 'updated') {
        $success_msg = 'Tag atualizada com sucesso!';
    } else if ($_GET['success'] === 'deleted') {
        $success_msg = 'Tag excluída com sucesso!';
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'add') ? 'Adicionar Nova Tag' : (($action === 'edit') ? 'Editar Tag' : 'Gerenciar Tags'); ?>
    </h1>
    <?php if ($action === 'list'): ?>
    <div>
        <a href="tags.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Tag
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
<!-- Tabela de Tags -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Lista de Tags</h6>
    </div>
    <div class="card-body">
        <?php if (empty($tags)): ?>
            <p class="text-center">Nenhuma tag cadastrada ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Parceiros</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tag['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($tag['descricao'] ?: 'Sem descrição', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $tag['total_parceiros']; ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="tags.php?action=edit&id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($tag['total_parceiros'] == 0): ?>
                                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $tag['id']; ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    
                                    <!-- Modal de confirmação de exclusão -->
                                    <div class="modal fade" id="deleteModal<?php echo $tag['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Tem certeza que deseja excluir a tag <strong><?php echo htmlspecialchars($tag['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>?
                                                    <br>Esta ação não pode ser desfeita.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <a href="tags.php?action=delete&id=<?php echo $tag['id']; ?>" class="btn btn-danger">Sim, Excluir</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Parceiros</th>
                            <th>Ações</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Formulário de Adicionar/Editar -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold"><?php echo ($action === 'add') ? 'Adicionar Nova Tag' : 'Editar Tag'; ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="tags.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $id : ''; ?>">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome*</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($tag_data['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($tag_data['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text">Breve descrição sobre a tag.</div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i><?php echo ($action === 'add') ? 'Adicionar' : 'Salvar Alterações'; ?>
                </button>
                <a href="tags.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
                
                <?php if ($action === 'edit' && !isset($tag_data['total_parceiros']) || $tag_data['total_parceiros'] == 0): ?>
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
                                    Tem certeza que deseja excluir esta tag?
                                    <br>Esta ação não pode ser desfeita.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <a href="tags.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">Sim, Excluir</a>
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