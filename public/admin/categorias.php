<?php
// Adicionar buffer de saída no início do arquivo
ob_start();

require_once "../../config/db.php";

// Garantir que todos os outputs sejam em UTF-8
header('Content-Type: text/html; charset=utf-8');

include 'includes/header.php';

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';

// Obter tipos de parceiros para o formulário
$tipos_parceiros = [];
$query = "SELECT id, nome FROM tipos_parceiros ORDER BY nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tipos_parceiros[] = $row;
    }
}

// Processar formulário de adicionar/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $nome = $_POST['nome'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $tipo_id = isset($_POST['tipo_id']) ? (int)$_POST['tipo_id'] : 0;
    
    // Criar slug base
    $base_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', 
        iconv('UTF-8', 'ASCII//TRANSLIT', trim($nome))));
    $base_slug = preg_replace('/-+/', '-', $base_slug);
    $base_slug = trim($base_slug, '-');
    
    // Verificar se o slug já existe e torná-lo único
    $slug = $base_slug;
    $count = 0;
    
    do {
        if ($count > 0) {
            $slug = $base_slug . '-' . $count;
        }
        
        // Verificar se o slug existe, exceto para o próprio registro em caso de edição
        $query = "SELECT id FROM categorias WHERE slug = ?";
        $params = [$slug];
        $types = "s";
        
        if ($action === 'edit' && $id > 0) {
            $query .= " AND id != ?";
            $params[] = $id;
            $types .= "i";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        $count++;
    } while ($exists && $count < 100); // Limite de 100 tentativas para evitar loop infinito
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($tipo_id)) {
        $error_msg = 'Por favor, preencha o nome da categoria e selecione o tipo.';
    } else {
        if ($action === 'add') {
            // Adicionar nova categoria
            $query = "INSERT INTO categorias (nome, slug, descricao, tipo_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nome, $slug, $descricao, $tipo_id);
            
            if ($stmt->execute()) {
                $success_msg = 'Categoria adicionada com sucesso!';
                header("Location: categorias.php?success=added");
                exit;
            } else {
                $error_msg = 'Erro ao adicionar categoria: ' . $stmt->error;
            }
            $stmt->close();
        } else if ($action === 'edit' && $id > 0) {
            // Atualizar categoria existente
            $query = "UPDATE categorias SET nome = ?, slug = ?, descricao = ?, tipo_id = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $nome, $slug, $descricao, $tipo_id, $id);
            
            if ($stmt->execute()) {
                $success_msg = 'Categoria atualizada com sucesso!';
                header("Location: categorias.php?success=updated");
                exit;
            } else {
                $error_msg = 'Erro ao atualizar categoria: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Excluir categoria
if ($action === 'delete' && $id > 0) {
    // Verificar se a categoria está sendo usada por algum parceiro
    $check_query = "SELECT COUNT(*) AS total FROM parceiros WHERE categoria_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $error_msg = 'Esta categoria não pode ser excluída pois está associada a ' . $row['total'] . ' parceiro(s).';
        $action = 'list';
    } else {
        // Excluir a categoria
        $query = "DELETE FROM categorias WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_msg = 'Categoria excluída com sucesso!';
            header("Location: categorias.php?success=deleted");
            exit;
        } else {
            $error_msg = 'Erro ao excluir categoria: ' . $stmt->error;
        }
    }
    $stmt->close();
}

// Obter categoria para edição
$categoria_data = null;
if ($action === 'edit' && $id > 0) {
    $query = "SELECT * FROM categorias WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $categoria_data = $result->fetch_assoc();
    } else {
        $error_msg = 'Categoria não encontrada.';
        $action = 'list'; // Volta para a listagem
    }
    $stmt->close();
}

// Listar categorias
$categorias = [];
$query = "SELECT c.*, COUNT(pc.parceiro_id) AS total_parceiros 
          FROM categorias c 
          LEFT JOIN parceiros_categorias pc ON c.id = pc.categoria_id 
          GROUP BY c.id 
          ORDER BY c.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// Verificar mensagens de sucesso nos parâmetros de URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_msg = 'Categoria adicionada com sucesso!';
    } else if ($_GET['success'] === 'updated') {
        $success_msg = 'Categoria atualizada com sucesso!';
    } else if ($_GET['success'] === 'deleted') {
        $success_msg = 'Categoria excluída com sucesso!';
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'add') ? 'Adicionar Nova Categoria' : (($action === 'edit') ? 'Editar Categoria' : 'Gerenciar Categorias'); ?>
    </h1>
    <?php if ($action === 'list'): ?>
    <div>
        <a href="categorias.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Categoria
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
<!-- Tabela de Categorias -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Lista de Categorias</h6>
    </div>
    <div class="card-body">
        <?php if (empty($categorias)): ?>
            <p class="text-center">Nenhuma categoria cadastrada ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Parceiros</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                                <td>
                                    <?php
                                    $tipo_nome = '';
                                    $tipo_id_categoria = isset($categoria['tipo_id']) ? $categoria['tipo_id'] : null;
                                    foreach ($tipos_parceiros as $tipo) {
                                        if ($tipo['id'] == $tipo_id_categoria) {
                                            $tipo_nome = $tipo['nome'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($tipo_nome);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($categoria['descricao'] ?: 'Sem descrição'); ?></td>
                                <td><?php echo $categoria['total_parceiros']; ?></td>
                                <td class="text-center">
                                    <a href="categorias.php?action=edit&id=<?php echo $categoria['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($categoria['total_parceiros'] == 0): ?>
                                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $categoria['id']; ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    
                                    <!-- Modal de confirmação de exclusão -->
                                    <div class="modal fade" id="deleteModal<?php echo $categoria['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Tem certeza que deseja excluir a categoria <strong><?php echo htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>?
                                                    <br>Esta ação não pode ser desfeita.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <a href="categorias.php?action=delete&id=<?php echo $categoria['id']; ?>" class="btn btn-danger">Sim, Excluir</a>
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
                            <th>Tipo</th>
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
        <h6 class="m-0 font-weight-bold"><?php echo ($action === 'add') ? 'Adicionar Nova Categoria' : 'Editar Categoria'; ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="categorias.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $id : ''; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome*</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($categoria_data['nome'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="tipo_id" class="form-label">Tipo*</label>
                    <select class="form-select" id="tipo_id" name="tipo_id" required>
                        <option value="">Selecione um tipo</option>
                        <?php foreach ($tipos_parceiros as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($categoria_data['tipo_id']) && $categoria_data['tipo_id'] == $tipo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($categoria_data['descricao'] ?? ''); ?>">
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i><?php echo ($action === 'add') ? 'Adicionar' : 'Salvar Alterações'; ?>
                </button>
                <a href="categorias.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
                
                <?php if ($action === 'edit' && !isset($categoria_data['total_parceiros']) || $categoria_data['total_parceiros'] == 0): ?>
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
                                    Tem certeza que deseja excluir esta categoria?
                                    <br>Esta ação não pode ser desfeita.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <a href="categorias.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">Sim, Excluir</a>
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