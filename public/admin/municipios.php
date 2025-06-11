<?php
// Adicionar buffer de saída no início do arquivo
ob_start();

// Exibir erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Página de gerenciamento de municípios
require_once(__DIR__ . '/../../config/db.php');

// Função para verificar conexão
function verificar_conexao($conn) {
    if (!$conn || !$conn->ping()) {
        // Reconectar se a conexão foi perdida
        if ($conn) {
            $conn->close();
        }
        
        // Verificar se as constantes estão definidas
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $name = defined('DB_NAME') ? DB_NAME : 'agroneg';
        
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
    }
    return $conn;
}

include 'includes/header.php';

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// Verificar se é uma ação de exclusão
if ($action === 'delete' && $id > 0) {
    try {
        // Verificar conexão
        $conn = verificar_conexao($conn);
        
        // Abordagem simplificada para exclusão
        // 1. Excluir primeiro as relações (tabela municipio_galeria)
        $query = "DELETE FROM municipio_galeria WHERE municipio_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        
        // 2. Excluir o município
        $query = "DELETE FROM municipios WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $resultado = $stmt->execute();
            $stmt->close();
            
            if ($resultado) {
                header("Location: municipios.php?success=deleted");
                exit;
            } else {
                throw new Exception("Não foi possível excluir o município");
            }
        } else {
            throw new Exception("Erro ao preparar a consulta");
        }
    } catch (Exception $e) {
        $error_msg = 'Erro ao excluir município: ' . $e->getMessage();
        header("Location: municipios.php?error=" . urlencode($error_msg));
        exit;
    }
}

// Verificar se é uma ação de exclusão de imagem da galeria
if ($action === 'delete_gallery_image' && $id > 0 && isset($_GET['image_id'])) {
    $image_id = (int)$_GET['image_id'];
    
    // Buscar informações da imagem
    $query = "SELECT imagem, municipio_id FROM municipio_galeria WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $image_data = $result->fetch_assoc();
        $municipio_id = $image_data['municipio_id'];
        
        // Excluir registro do banco
        $query = "DELETE FROM municipio_galeria WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $image_id);
        
        if ($stmt->execute()) {
            // Tentar excluir arquivo físico
            $file_path = '../uploads/municipios/galeria/' . $image_data['imagem'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Redirecionar para a página de edição
            header("Location: municipios.php?action=edit&id=$municipio_id&success=image_deleted");
            exit;
        } else {
            $error_msg = 'Erro ao excluir imagem: ' . $stmt->error;
        }
    } else {
        $error_msg = 'Imagem não encontrada.';
    }
    
    // Redirecionar para a lista se algo deu errado
    header("Location: municipios.php");
    exit;
}

// Verificar mensagens de sucesso
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_msg = 'Município adicionado com sucesso!';
    } elseif ($_GET['success'] === 'updated') {
        $success_msg = 'Município atualizado com sucesso!';
    } elseif ($_GET['success'] === 'deleted') {
        $success_msg = 'Município excluído com sucesso!';
    } elseif ($_GET['success'] === 'image_deleted') {
        $success_msg = 'Imagem excluída com sucesso!';
    }
}

// Verificar mensagens de erro
if (isset($_GET['error'])) {
    $error_msg = urldecode($_GET['error']);
}

// Obter estados para o formulário
$estados = [];
$query = "SELECT id, nome, sigla FROM estados ORDER BY nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $estados[] = $row;
    }
}

// Listar municípios
$municipios = [];
$query = "SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla 
          FROM municipios m
          LEFT JOIN estados e ON m.estado_id = e.id
          ORDER BY e.sigla, m.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $municipios[] = $row;
    }
}

// Buscar fotos do município para a galeria
$galeria = [];
$query = "SELECT id, arquivo, legenda, ordem FROM fotos WHERE entidade_tipo = 'municipio' AND entidade_id = ? ORDER BY ordem ASC, id ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $galeria[] = $row;
}
$stmt->close();

// Verificar qual aba deve estar ativa
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'add') ? 'Adicionar Novo Município' : (($action === 'edit') ? 'Editar Município' : 'Gerenciar Municípios'); ?>
    </h1>
    <?php if ($action === 'list'): ?>
    <div>
        <a href="municipios.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Município
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
<!-- Tabela de Municípios -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Lista de Municípios</h6>
    </div>
    <div class="card-body">
        <?php if (empty($municipios)): ?>
            <p class="text-center">Nenhum município cadastrado ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($municipios as $municipio): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($municipio['nome']); ?></td>
                                <td><?php echo htmlspecialchars($municipio['estado_nome'] . ' (' . $municipio['estado_sigla'] . ')'); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="municipios.php?action=edit&id=<?php echo $municipio['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                onclick="prepararExclusao(<?php echo $municipio['id']; ?>, '<?php echo addslashes(htmlspecialchars($municipio['nome'])); ?>')" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add'): ?>
<!-- Formulário de Adicionar -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Adicionar Novo Município</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Após salvar o município, você será redirecionado para adicionar imagens à galeria.
        </div>
        <form method="post" action="municipios.php?action=add" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome*</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="col-md-6">
                    <label for="estado_id" class="form-label">Estado*</label>
                    <select class="form-select" id="estado_id" name="estado_id" required>
                        <option value="">Selecione um estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado['id']; ?>">
                                <?php echo htmlspecialchars($estado['nome'] . ' (' . $estado['sigla'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="populacao" class="form-label">População</label>
                    <input type="text" class="form-control" id="populacao" name="populacao">
                </div>
                <div class="col-md-6">
                    <label for="area_rural" class="form-label">Área Rural</label>
                    <input type="text" class="form-control" id="area_rural" name="area_rural">
                </div>
            </div>

            <div class="mb-3">
                <label for="principais_culturas" class="form-label">Principais Culturas</label>
                <textarea class="form-control" id="principais_culturas" name="principais_culturas" rows="3"></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                </div>
                <div class="col-md-6">
                    <label for="imagem_principal" class="form-label">Imagem Principal</label>
                    <input type="file" class="form-control" id="imagem_principal" name="imagem_principal">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="facebook" class="form-label">Facebook</label>
                    <input type="url" class="form-control" id="facebook" name="facebook" placeholder="https://">
                </div>
                <div class="col-md-4">
                    <label for="instagram" class="form-label">Instagram</label>
                    <input type="url" class="form-control" id="instagram" name="instagram" placeholder="https://">
                </div>
                <div class="col-md-4">
                    <label for="twitter" class="form-label">Twitter</label>
                    <input type="url" class="form-control" id="twitter" name="twitter" placeholder="https://">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Adicionar
                </button>
                <a href="municipios.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit'): ?>
<!-- Formulário de Editar -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Editar Município</h6>
    </div>
    <div class="card-body">
        <?php
        // Buscar dados do município
        $municipio_data = null;
        if ($id > 0) {
            $query = "SELECT * FROM municipios WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $municipio_data = $result->fetch_assoc();
            }
            $stmt->close();
            
            // Buscar fotos do município para a galeria
            $galeria = [];
            $query = "SELECT id, arquivo, legenda, ordem FROM fotos WHERE entidade_tipo = 'municipio' AND entidade_id = ? ORDER BY ordem ASC, id ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $galeria[] = $row;
            }
            $stmt->close();
        }
        
        if (!$municipio_data) {
            echo '<div class="alert alert-danger">Município não encontrado.</div>';
        } else {
        ?>
        <ul class="nav nav-tabs mb-4" id="municipioTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab === 'info') ? 'active' : ''; ?>" 
                        id="info-tab" data-bs-toggle="tab" data-bs-target="#info" 
                        type="button" role="tab" aria-controls="info" 
                        aria-selected="<?php echo ($active_tab === 'info') ? 'true' : 'false'; ?>">
                    Informações Básicas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab === 'galeria') ? 'active' : ''; ?>" 
                        id="galeria-tab" data-bs-toggle="tab" data-bs-target="#galeria" 
                        type="button" role="tab" aria-controls="galeria" 
                        aria-selected="<?php echo ($active_tab === 'galeria') ? 'true' : 'false'; ?>">
                    Galeria de Imagens
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="municipioTabContent">
            <div class="tab-pane fade <?php echo ($active_tab === 'info') ? 'show active' : ''; ?>" 
                 id="info" role="tabpanel" aria-labelledby="info-tab">
                <form method="post" action="municipios.php?action=edit&id=<?php echo $id; ?>" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome*</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($municipio_data['nome']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="estado_id" class="form-label">Estado*</label>
                            <select class="form-select" id="estado_id" name="estado_id" required>
                                <option value="">Selecione um estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id']; ?>" <?php echo ($municipio_data['estado_id'] == $estado['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estado['nome'] . ' (' . $estado['sigla'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="populacao" class="form-label">População</label>
                            <input type="text" class="form-control" id="populacao" name="populacao" value="<?php echo htmlspecialchars($municipio_data['populacao'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="area_rural" class="form-label">Área Rural</label>
                            <input type="text" class="form-control" id="area_rural" name="area_rural" value="<?php echo htmlspecialchars($municipio_data['area_rural'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="principais_culturas" class="form-label">Principais Culturas</label>
                        <textarea class="form-control" id="principais_culturas" name="principais_culturas" rows="3"><?php echo ($municipio_data['principais_culturas'] === '0' ? '' : htmlspecialchars($municipio_data['principais_culturas'] ?? '')); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" placeholder="https://" value="<?php echo htmlspecialchars($municipio_data['website'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="imagem_principal" class="form-label">Imagem Principal</label>
                            <input type="file" class="form-control" id="imagem_principal" name="imagem_principal">
                            <?php if (!empty($municipio_data['imagem_principal'])): ?>
                                <div class="mt-2">
                                    <small>Imagem atual: <?php echo htmlspecialchars($municipio_data['imagem_principal']); ?></small>
                                    <img src="../uploads/municipios/<?php echo htmlspecialchars($municipio_data['imagem_principal']); ?>" alt="Imagem atual" class="img-thumbnail mt-2" style="max-height: 100px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="facebook" class="form-label">Facebook</label>
                            <input type="url" class="form-control" id="facebook" name="facebook" placeholder="https://" value="<?php echo htmlspecialchars($municipio_data['facebook'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="instagram" class="form-label">Instagram</label>
                            <input type="url" class="form-control" id="instagram" name="instagram" placeholder="https://" value="<?php echo htmlspecialchars($municipio_data['instagram'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="twitter" class="form-label">Twitter</label>
                            <input type="url" class="form-control" id="twitter" name="twitter" placeholder="https://" value="<?php echo htmlspecialchars($municipio_data['twitter'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="submit_info" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="municipios.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="tab-pane fade <?php echo ($active_tab === 'galeria') ? 'show active' : ''; ?>" 
                 id="galeria" role="tabpanel" aria-labelledby="galeria-tab">
                <div class="row mb-4">
                    <div class="col-12">
                        <form method="post" action="municipios.php?action=edit&id=<?php echo $id; ?>" enctype="multipart/form-data" class="mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Adicionar Imagens à Galeria</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="galeria_imagens" class="form-label">Selecione uma ou mais imagens</label>
                                        <input type="file" class="form-control" id="galeria_imagens" name="galeria_imagens[]" multiple accept="image/*">
                                        <div class="form-text">Você pode selecionar várias imagens de uma vez.</div>
                                    </div>
                                    <button type="submit" name="submit_galeria" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i>Enviar Imagens
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (empty($galeria)): ?>
                    <div class="alert alert-info">
                        Nenhuma imagem na galeria. Adicione imagens usando o formulário acima.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Imagens na Galeria</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($galeria as $imagem): ?>
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="card h-100">
                                            <img src="../uploads/municipios/galeria/<?php echo htmlspecialchars($imagem['arquivo']); ?>" 
                                                 class="card-img-top" alt="Imagem da galeria" 
                                                 style="height: 150px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title text-truncate"><?php echo htmlspecialchars($imagem['legenda'] ?: 'Sem legenda'); ?></h6>
                                                <a href="municipios.php?action=delete_gallery_image&id=<?php echo $id; ?>&image_id=<?php echo $imagem['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Tem certeza que deseja excluir esta imagem?');">
                                                    <i class="fas fa-trash"></i> Excluir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o município <strong id="municipio-nome"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita e removerá todas as imagens e dados associados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btn-confirmar-exclusao" class="btn btn-danger">Sim, Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
function prepararExclusao(id, nome) {
    document.getElementById('municipio-nome').textContent = nome;
    document.getElementById('btn-confirmar-exclusao').href = 'municipios.php?action=delete&id=' + id;
}
</script>

<?php 
// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id > 0) {
    // Processar galeria de imagens
    if (isset($_POST['submit_galeria']) && !empty($_FILES['galeria_imagens']['name'][0])) {
        $upload_dir = '../uploads/municipios/galeria/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $error = false;
        $success_count = 0;
        foreach ($_FILES['galeria_imagens']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['galeria_imagens']['error'][$key] === 0) {
                $file_name = time() . '_' . $_FILES['galeria_imagens']['name'][$key];
                $file_name = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file_name);
                $upload_file = $upload_dir . $file_name;
                if (move_uploaded_file($tmp_name, $upload_file)) {
                    $query = "INSERT INTO fotos (entidade_tipo, entidade_id, arquivo, legenda, ordem) VALUES ('municipio', ?, ?, '', 0)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $id, $file_name);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error = true;
                        $error_msg = 'Erro ao salvar imagem no banco de dados: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = true;
                    $error_msg = 'Erro ao fazer upload de uma ou mais imagens.';
                }
            }
        }
        if ($success_count > 0) {
            $success_msg = "Imagens adicionadas com sucesso à galeria!";
        }
        if (!$error) {
            header("Location: municipios.php?action=edit&id=$id&tab=galeria&success=images_added");
            exit;
        }
    }
    
    // Processar informações básicas
    if (isset($_POST['submit_info'])) {
        $nome = $_POST['nome'] ?? '';
        $slug = $_POST['slug'] ?? '';
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nome)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
        }
        $estado_id = (int)($_POST['estado_id'] ?? 0);
        $populacao = $_POST['populacao'] ?? '';
        $area_rural = $_POST['area_rural'] ?? '';
        $principais_culturas = $_POST['principais_culturas'] ?? '';
        if (trim($principais_culturas) === '0' && empty(trim($_POST['principais_culturas']))) {
            $principais_culturas = '';
        }
        $website = $_POST['website'] ?? '';
        $facebook = $_POST['facebook'] ?? '';
        $instagram = $_POST['instagram'] ?? '';
        $twitter = $_POST['twitter'] ?? '';
        
        // Validar campos obrigatórios
        if (empty($nome) || empty($estado_id)) {
            $error_msg = 'Por favor, preencha todos os campos obrigatórios.';
        } else {
            // Tratar o upload da imagem, se houver
            $imagem_principal = null;
            
            if (!empty($_FILES['imagem_principal']['name'])) {
                $upload_dir = '../uploads/municipios/';
                
                // Criar diretório se não existir
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . $_FILES['imagem_principal']['name'];
                $upload_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['imagem_principal']['tmp_name'], $upload_file)) {
                    $imagem_principal = $file_name;
                } else {
                    $error_msg = 'Erro ao fazer upload da imagem.';
                }
            }
            
            if (empty($error_msg)) {
                // Remover o UPDATE isolado de teste
                // Adicionar debug visual dos parâmetros
                echo "<pre>DEBUG PARAMS: "; print_r($params); echo "</pre>";
                // Atualizar o município - incluir todos os campos
                $query = "UPDATE municipios SET 
                          nome = ?, 
                          slug = ?, 
                          estado_id = ?,
                          populacao = ?,
                          area_rural = ?,
                          principais_culturas = ?,
                          website = ?,
                          facebook = ?,
                          instagram = ?,
                          twitter = ?";
                $params = [$nome, $slug, $estado_id, $populacao, $area_rural, $principais_culturas, $website, $facebook, $instagram, $twitter];
                $types = "ssisssssssi";  // 11 parâmetros: s (nome), s (slug), i (estado_id), s, s, s, s, s, s, s, i (id)
                // Adicionar imagem principal à query se foi enviada
                if ($imagem_principal !== null) {
                    $query .= ", imagem_principal = ?";
                    $params[] = $imagem_principal;
                    $types .= "s";
                }
                $query .= " WHERE id = ?";
                $params[] = $id;
                $stmt = $conn->prepare($query);
                try {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        header("Location: municipios.php?success=updated");
                        exit;
                    } else {
                        $error_msg = 'Erro ao atualizar município: ' . $stmt->error;
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error_msg = 'Erro na execução da query: ' . $e->getMessage();
                }
            }
        }
    }
}

// Processar o formulário de adição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $nome = $_POST['nome'] ?? '';
    $slug = $_POST['slug'] ?? '';
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nome)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
    }
    $estado_id = (int)($_POST['estado_id'] ?? 0);
    $populacao = $_POST['populacao'] ?? '';
    $area_rural = $_POST['area_rural'] ?? '';
    $principais_culturas = $_POST['principais_culturas'] ?? '';
    if (trim($principais_culturas) === '0' && empty(trim($_POST['principais_culturas']))) {
        $principais_culturas = '';
    }
    $website = $_POST['website'] ?? '';
    $facebook = $_POST['facebook'] ?? '';
    $instagram = $_POST['instagram'] ?? '';
    $twitter = $_POST['twitter'] ?? '';
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($estado_id)) {
        $error_msg = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Criar slug a partir do nome
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nome)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Tratar o upload da imagem, se houver
        $imagem_principal = null;
        
        if (!empty($_FILES['imagem_principal']['name'])) {
            $upload_dir = '../uploads/municipios/';
            
            // Criar diretório se não existir
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['imagem_principal']['name'];
            $upload_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['imagem_principal']['tmp_name'], $upload_file)) {
                $imagem_principal = $file_name;
            } else {
                $error_msg = 'Erro ao fazer upload da imagem.';
            }
        }
        
        if (empty($error_msg)) {
            // Vamos tentar inserir um registro completo
            try {
                $stmt = $conn->prepare("INSERT INTO municipios (nome, slug, estado_id, populacao, area_rural, principais_culturas, website, facebook, instagram, twitter, imagem_principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissssssss", 
                    $nome, $slug, $estado_id, $populacao, $area_rural, $principais_culturas, 
                    $website, $facebook, $instagram, $twitter, $imagem_principal);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    // Redirecionar para a página de edição com a aba galeria aberta
                    header("Location: municipios.php?action=edit&id=$new_id&tab=galeria&success=added");
                    exit;
                } else {
                    $error_msg = 'Erro ao adicionar município: ' . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_msg = 'Erro na execução da query: ' . $e->getMessage();
            }
        }
    }
}

include 'includes/footer.php'; ?> 