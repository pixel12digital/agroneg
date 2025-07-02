<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Processamento de exclusão ANTES de qualquer saída ou include
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && (int)$_GET['id'] > 0) {
    require_once(__DIR__ . '/../config/db.php');
    $id = (int)$_GET['id'];
    $error_msg = '';
    $success_msg = '';
    // Verificar se o parceiro existe
    $query = "SELECT id FROM parceiros WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        // Excluir o parceiro
        $query = "DELETE FROM parceiros WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: parceiros.php?success=deleted");
            exit;
        } else {
            $error_msg = 'Erro ao excluir parceiro: ' . $stmt->error;
        }
    } else {
        $error_msg = 'Parceiro não encontrado.';
    }
    $stmt->close();
    // Se houve erro, exibe na tela (sem redirecionar)
    if ($error_msg) {
        echo '<div class="alert alert-danger">' . $error_msg . '</div>';
        exit;
    }
}

// Página de gerenciamento de parceiros
require_once(__DIR__ . '/../config/db.php');

// Carregar tipos de parceiros antes de qualquer uso
$tipos_parceiros = [];
$query = "SELECT id, nome FROM tipos_parceiros ORDER BY nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tipos_parceiros[] = $row;
    }
}

// Definir variáveis
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_msg = '';
$error_msg = '';

// Processar formulário de adicionar/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    // Debug do POST
    error_log("POST recebido: " . print_r($_POST, true));
    
    // Validar e sanitizar dados
    $nome = trim($_POST['nome'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoria_ids = isset($_POST['categoria_id']) ? (array)$_POST['categoria_id'] : [];
    $municipio_id = (int)($_POST['municipio_id'] ?? 0);
    $tipo_id = isset($_POST['tipo']) ? (int)$_POST['tipo'] : 0;
    $descricao = trim($_POST['descricao'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $tiktok = trim($_POST['tiktok'] ?? '');
    $youtube = trim($_POST['youtube'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    // Validar campos obrigatórios
    if (empty($nome)) {
        $error_msg = 'O nome do parceiro é obrigatório.';
    } elseif (empty($categoria_ids)) {
        $error_msg = 'Selecione pelo menos uma categoria.';
    } elseif ($municipio_id <= 0) {
        $error_msg = 'Selecione um município válido.';
    } elseif ($tipo_id <= 0) {
        $error_msg = 'Selecione um tipo de parceiro válido.';
    } else {
        // Gerar slug se estiver vazio
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $nome));
            $slug = trim($slug, '-');
        }

        // Verificar se o slug já existe
        $query = "SELECT id FROM parceiros WHERE slug = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $slug, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_msg = 'Já existe um parceiro com este slug. Por favor, escolha outro.';
        } else {
            // Processar imagem de destaque
            $imagem_destaque = null;
            if (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['imagem_destaque']['tmp_name'];
                $name = $_FILES['imagem_destaque']['name'];
                $size = $_FILES['imagem_destaque']['size'];
                $type = $_FILES['imagem_destaque']['type'];
                
                // Verificar tamanho do arquivo (max 2MB)
                if ($size > 2 * 1024 * 1024) {
                    $error_msg = 'O tamanho máximo da imagem de destaque é 2MB.';
                } else {
                    // Verificar tipo de arquivo
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (!in_array($type, $allowed_types)) {
                        $error_msg = 'Formato de arquivo não suportado para imagem de destaque. Use JPG ou PNG.';
                    } else {
                        // Criar diretório se não existir
                        $upload_dir = '../uploads/parceiros/destaque/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Gerar nome único para o arquivo
                        $extension = pathinfo($name, PATHINFO_EXTENSION);
                        $new_filename = uniqid('destaque_' . ($id ?: 'novo') . '_') . '.' . $extension;
                        $destination = $upload_dir . $new_filename;
                        
                        // Mover o arquivo
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $imagem_destaque = $new_filename;
                        } else {
                            $error_msg = 'Erro ao mover o arquivo da imagem de destaque.';
                        }
                    }
                }
            } else if ($action === 'edit' && $id > 0) {
                // Se não enviou nova imagem, mantém a antiga
                $query = "SELECT imagem_destaque FROM parceiros WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $imagem_destaque = $row['imagem_destaque'];
                }
            }

            try {
                // Iniciar transação
                $conn->begin_transaction();

                if ($action === 'add') {
                    // Inserir parceiro
                    $query = "INSERT INTO parceiros (nome, slug, municipio_id, tipo_id, descricao, endereco, telefone, whatsapp, email, website, facebook, instagram, twitter, tiktok, youtube, imagem_destaque, status, destaque) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Erro ao preparar query: " . $conn->error);
                    }
                    
                    $stmt->bind_param("ssiissssssssssssii", 
                        $nome, $slug, $municipio_id, $tipo_id, $descricao, 
                        $endereco, $telefone, $whatsapp, $email, $website, 
                        $facebook, $instagram, $twitter, $tiktok, $youtube, 
                        $imagem_destaque, $status, $destaque
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao inserir parceiro: " . $stmt->error);
                    }

                    $id = $conn->insert_id;
                    
                    // Inserir categorias
                    foreach ($categoria_ids as $cat_id) {
                        $query = "INSERT INTO parceiros_categorias (parceiro_id, categoria_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ii", $id, $cat_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao inserir categoria: " . $stmt->error);
                        }
                    }

                    $conn->commit();
                    header("Location: parceiros.php?action=edit&id=$id&success=added");
                    exit;
                } else if ($action === 'edit' && $id > 0) {
                    // Atualizar parceiro
                    $query = "UPDATE parceiros SET nome = ?, slug = ?, municipio_id = ?, tipo_id = ?, descricao = ?, endereco = ?, telefone = ?, whatsapp = ?, email = ?, website = ?, facebook = ?, instagram = ?, twitter = ?, tiktok = ?, youtube = ?, imagem_destaque = ?, status = ?, destaque = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Erro ao preparar query: " . $conn->error);
                    }

                    $stmt->bind_param("ssiissssssssssssiii", 
                        $nome, $slug, $municipio_id, $tipo_id, $descricao, 
                        $endereco, $telefone, $whatsapp, $email, $website, 
                        $facebook, $instagram, $twitter, $tiktok, $youtube, 
                        $imagem_destaque, $status, $destaque, $id
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao atualizar parceiro: " . $stmt->error);
                    }

                    // Atualizar categorias
                    $conn->query("DELETE FROM parceiros_categorias WHERE parceiro_id = $id");
                    foreach ($categoria_ids as $cat_id) {
                        $query = "INSERT INTO parceiros_categorias (parceiro_id, categoria_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ii", $id, $cat_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar categoria: " . $stmt->error);
                        }
                    }

                    $conn->commit();
                    header("Location: parceiros.php?action=edit&id=$id&success=updated");
                    exit;
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Erro: " . $e->getMessage();
                error_log("Erro no cadastro de parceiro: " . $e->getMessage());
            }
        }
        $stmt->close();
    }
}

// Excluir parceiro
if ($action === 'delete' && $id > 0) {
    // Verificar se o parceiro existe
    $query = "SELECT id FROM parceiros WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Excluir o parceiro
        $query = "DELETE FROM parceiros WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_msg = 'Parceiro excluído com sucesso!';
            // Redirecionar para a listagem
            header("Location: parceiros.php?success=deleted");
            exit;
        } else {
            $error_msg = 'Erro ao excluir parceiro: ' . $stmt->error;
        }
    } else {
        $error_msg = 'Parceiro não encontrado.';
    }
    $stmt->close();
}

// Remover imagem de destaque
if ($action === 'removeimagemdestaque' && $id > 0) {
    // Verificar se o parceiro existe e obter o nome do arquivo
    $query = "SELECT imagem_destaque FROM parceiros WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $parceiro = $result->fetch_assoc();
        $imagem = $parceiro['imagem_destaque'];
        
        if (!empty($imagem)) {
            // Atualizar o registro no banco de dados
            $query = "UPDATE parceiros SET imagem_destaque = NULL WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Excluir o arquivo físico
                $arquivo_path = '../uploads/parceiros/destaque/' . $imagem;
                if (file_exists($arquivo_path)) {
                    unlink($arquivo_path);
                }
                $success_msg = 'Imagem de destaque removida com sucesso!';
            } else {
                $error_msg = 'Erro ao remover imagem de destaque: ' . $stmt->error;
            }
        } else {
            $error_msg = 'Parceiro não possui imagem de destaque.';
        }
    } else {
        $error_msg = 'Parceiro não encontrado.';
    }
    $stmt->close();
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Obter parceiro para edição
$parceiro = null;
if ($action === 'edit' && $id > 0) {
    $query = "SELECT * FROM parceiros WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $parceiro = $result->fetch_assoc();
        if (isset($parceiro['tipo_id'])) {
            $parceiro['tipo'] = $parceiro['tipo_id'];
        }
        // Buscar municipio_id e estado_id
        $query2 = "SELECT m.id as municipio_id, e.id as estado_id FROM municipios m JOIN estados e ON m.estado_id = e.id WHERE m.id = ?";
        $stmt2 = $conn->prepare($query2);
        $stmt2->bind_param("i", $parceiro['municipio_id']);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2->num_rows === 1) {
            $row = $result2->fetch_assoc();
            $parceiro['municipio_id'] = $row['municipio_id'];
            $parceiro['estado_id'] = $row['estado_id'];
        }
        $stmt2->close();
    } else {
        $error_msg = 'Parceiro não encontrado.';
        $action = 'list'; // Volta para a listagem
    }
    $stmt->close();
}

// Obter categorias para o formulário (todas, mas o JS vai filtrar pelo tipo)
$categorias = [];
$query = "SELECT id, nome, tipo_id FROM categorias ORDER BY nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// Obter estados para o formulário
$estados = [];
$query = "SELECT e.id, e.nome, e.sigla FROM estados e WHERE EXISTS (SELECT 1 FROM municipios m WHERE m.estado_id = e.id) ORDER BY e.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $estados[] = $row;
    }
}

// Obter municípios para o formulário
$municipios = [];
$query = "SELECT m.id, m.nome, e.sigla as estado FROM municipios m
          JOIN estados e ON m.estado_id = e.id
          ORDER BY e.sigla, m.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $municipios[] = $row;
    }
}

// Listar parceiros
$parceiros = [];
$query = "SELECT p.*, m.nome as municipio_nome, e.sigla as estado_sigla, t.nome as tipo_nome 
          FROM parceiros p
          LEFT JOIN municipios m ON p.municipio_id = m.id
          LEFT JOIN estados e ON m.estado_id = e.id
          LEFT JOIN tipos_parceiros t ON p.tipo_id = t.id
          ORDER BY p.nome";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parceiros[] = $row;
    }
}

// Verificar mensagens de sucesso nos parâmetros de URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $success_msg = 'Parceiro adicionado com sucesso!';
    } else if ($_GET['success'] === 'updated') {
        $success_msg = 'Parceiro atualizado com sucesso!';
    } else if ($_GET['success'] === 'deleted') {
        $success_msg = 'Parceiro excluído com sucesso!';
    }
}

// Adicionar tag ao parceiro
if ($action === 'addtag' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_id = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
    
    if ($tag_id > 0) {
        // Verificar se a relação já existe
        $query = "SELECT 1 FROM parceiros_tags WHERE parceiro_id = ? AND tag_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id, $tag_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Adicionar a relação
            $query = "INSERT INTO parceiros_tags (parceiro_id, tag_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $id, $tag_id);
            
            if ($stmt->execute()) {
                $success_msg = 'Tag adicionada com sucesso!';
            } else {
                $error_msg = 'Erro ao adicionar tag: ' . $stmt->error;
            }
        } else {
            $error_msg = 'Esta tag já está associada ao parceiro.';
        }
        $stmt->close();
    } else {
        $error_msg = 'Tag inválida.';
    }
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Remover tag do parceiro
if ($action === 'removetag' && isset($_GET['parceiro_id']) && isset($_GET['tag_id'])) {
    $parceiro_id = (int)$_GET['parceiro_id'];
    $tag_id = (int)$_GET['tag_id'];
    
    if ($parceiro_id > 0 && $tag_id > 0) {
        $query = "DELETE FROM parceiros_tags WHERE parceiro_id = ? AND tag_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $parceiro_id, $tag_id);
        
        if ($stmt->execute()) {
            $success_msg = 'Tag removida com sucesso!';
        } else {
            $error_msg = 'Erro ao remover tag: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = 'Parâmetros inválidos.';
    }
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$parceiro_id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Adicionar foto ao parceiro
if ($action === 'addphoto' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $legenda = $_POST['legenda'] ?? '';
    $ordem = isset($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
    
    // Verificar se o arquivo foi enviado
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['foto']['tmp_name'];
        $name = $_FILES['foto']['name'];
        $size = $_FILES['foto']['size'];
        $type = $_FILES['foto']['type'];
        
        // Verificar tamanho do arquivo (max 2MB)
        if ($size > 2 * 1024 * 1024) {
            $error_msg = 'O tamanho máximo da foto é 2MB.';
        } else {
            // Verificar tipo de arquivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($type, $allowed_types)) {
                $error_msg = 'Formato de arquivo não suportado. Use JPG ou PNG.';
            } else {
                // Criar diretório se não existir
                $upload_dir = '../uploads/parceiros/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Gerar nome único para o arquivo
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $new_filename = uniqid('parceiro_' . $id . '_') . '.' . $extension;
                $destination = $upload_dir . $new_filename;
                
                // Mover o arquivo
                if (move_uploaded_file($tmp_name, $destination)) {
                    // Salvar no banco de dados
                    $query = "INSERT INTO fotos (entidade_tipo, entidade_id, arquivo, legenda, ordem) VALUES ('parceiro', ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("issi", $id, $new_filename, $legenda, $ordem);
                    
                    if ($stmt->execute()) {
                        $success_msg = 'Foto adicionada com sucesso!';
                    } else {
                        $error_msg = 'Erro ao adicionar foto: ' . $stmt->error;
                        // Remover arquivo se houver erro no banco de dados
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                    }
                    $stmt->close();
                } else {
                    $error_msg = 'Erro ao mover o arquivo.';
                }
            }
        }
    } else {
        $error_msg = 'Nenhum arquivo enviado ou erro no upload.';
    }
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Editar foto do parceiro
if ($action === 'editphoto' && isset($_GET['parceiro_id']) && isset($_GET['foto_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $parceiro_id = (int)$_GET['parceiro_id'];
    $foto_id = (int)$_GET['foto_id'];
    $legenda = $_POST['legenda'] ?? '';
    $ordem = isset($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
    
    if ($parceiro_id > 0 && $foto_id > 0) {
        // Verificar se a foto pertence ao parceiro
        $query = "SELECT 1 FROM fotos WHERE id = ? AND entidade_tipo = 'parceiro' AND entidade_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $foto_id, $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Atualizar a foto
            $query = "UPDATE fotos SET legenda = ?, ordem = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $legenda, $ordem, $foto_id);
            
            if ($stmt->execute()) {
                $success_msg = 'Foto atualizada com sucesso!';
            } else {
                $error_msg = 'Erro ao atualizar foto: ' . $stmt->error;
            }
        } else {
            $error_msg = 'Foto não encontrada ou não pertence a este parceiro.';
        }
        $stmt->close();
    } else {
        $error_msg = 'Parâmetros inválidos.';
    }
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$parceiro_id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Excluir foto do parceiro
if ($action === 'deletephoto' && isset($_GET['parceiro_id']) && isset($_GET['foto_id'])) {
    $parceiro_id = (int)$_GET['parceiro_id'];
    $foto_id = (int)$_GET['foto_id'];
    
    if ($parceiro_id > 0 && $foto_id > 0) {
        // Verificar se a foto pertence ao parceiro e obter o nome do arquivo
        $query = "SELECT arquivo FROM fotos WHERE id = ? AND entidade_tipo = 'parceiro' AND entidade_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $foto_id, $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $foto = $result->fetch_assoc();
            $arquivo = $foto['arquivo'];
            
            // Excluir do banco de dados
            $query = "DELETE FROM fotos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $foto_id);
            
            if ($stmt->execute()) {
                // Excluir o arquivo físico
                $arquivo_path = '../uploads/parceiros/' . $arquivo;
                if (file_exists($arquivo_path)) {
                    unlink($arquivo_path);
                }
                $success_msg = 'Foto excluída com sucesso!';
            } else {
                $error_msg = 'Erro ao excluir foto: ' . $stmt->error;
            }
        } else {
            $error_msg = 'Foto não encontrada ou não pertence a este parceiro.';
        }
        $stmt->close();
    } else {
        $error_msg = 'Parâmetros inválidos.';
    }
    
    // Redirecionar para a edição
    header("Location: parceiros.php?action=edit&id=$parceiro_id&" . (!empty($error_msg) ? 'error=' . urlencode($error_msg) : 'success=' . urlencode($success_msg)));
    exit;
}

// Buscar categorias relacionadas ao parceiro (edição)
$categorias_selecionadas = [];
if ($action === 'edit' && $id > 0) {
    $query = "SELECT categoria_id FROM parceiros_categorias WHERE parceiro_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categorias_selecionadas[] = (string)$row['categoria_id'];
    }
    $stmt->close();
    $categorias_selecionadas = array_unique($categorias_selecionadas);
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">
        <?php echo ($action === 'add') ? 'Adicionar Novo Parceiro' : (($action === 'edit') ? 'Editar Parceiro' : 'Gerenciar Parceiros'); ?>
    </h1>
    <?php if ($action === 'list'): ?>
    <div>
        <a href="parceiros.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Parceiro
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
<!-- Tabela de Parceiros -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">Lista de Parceiros</h6>
    </div>
    <div class="card-body">
        <?php if (empty($parceiros)): ?>
            <p class="text-center">Nenhum parceiro cadastrado ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Município</th>
                            <th>Telefone</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parceiros as $parceiro): ?>
                            <tr>
                                <td><?php echo $parceiro['nome']; ?></td>
                                <td>
                                    <?php
                                    // Buscar categorias do parceiro
                                    $queryCat = "SELECT c.nome FROM parceiros_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.parceiro_id = ?";
                                    $stmtCat = $conn->prepare($queryCat);
                                    $stmtCat->bind_param("i", $parceiro['id']);
                                    $stmtCat->execute();
                                    $resultCat = $stmtCat->get_result();
                                    $cats = [];
                                    while ($rowCat = $resultCat->fetch_assoc()) {
                                        $cats[] = $rowCat['nome'];
                                    }
                                    $stmtCat->close();
                                    echo implode(', ', $cats);
                                    ?>
                                </td>
                                <td><?php echo $parceiro['tipo_nome']; ?></td>
                                <td><?php echo $parceiro['municipio_nome'] . '/' . $parceiro['estado_sigla']; ?></td>
                                <td><?php echo $parceiro['telefone']; ?></td>
                                <td class="text-center">
                                    <a href="parceiros.php?action=edit&id=<?php echo $parceiro['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../parceiro.php?id=<?php echo $parceiro['id']; ?>" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $parceiro['id']; ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    
                                    <!-- Modal de confirmação de exclusão -->
                                    <div class="modal fade" id="deleteModal<?php echo $parceiro['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Tem certeza que deseja excluir o parceiro <strong><?php echo $parceiro['nome']; ?></strong>?
                                                    <br>Esta ação não pode ser desfeita e todos os dados relacionados serão removidos.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <a href="parceiros.php?action=delete&id=<?php echo $parceiro['id']; ?>" class="btn btn-danger">Sim, Excluir</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Município</th>
                            <th>Telefone</th>
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
        <h6 class="m-0 font-weight-bold"><?php echo ($action === 'add') ? 'Adicionar Novo Parceiro' : 'Editar Parceiro'; ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="parceiros.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $id : ''; ?>" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome*</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo ($parceiro['nome'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="slug" class="form-label">Slug (URL)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo ($parceiro['slug'] ?? ''); ?>" placeholder="Ex: nome-do-parceiro">
                    <small class="text-muted">Deixe em branco para gerar automaticamente</small>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="tipo" class="form-label">Tipo*</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="">Selecione um tipo</option>
                        <?php foreach ($tipos_parceiros as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php
                                if (isset($parceiro['tipo']) && $parceiro['tipo'] == $tipo['id']) echo 'selected';
                                if (isset($_POST['tipo']) && $_POST['tipo'] == $tipo['id']) echo 'selected';
                            ?>>
                                <?php echo $tipo['nome']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Selecione o tipo específico de parceiro</small>
                </div>
                <div class="col-md-6">
                    <label for="categoria_id" class="form-label">Categoria*</label>
                    <select class="form-select" id="categoria_id" name="categoria_id[]" multiple required>
                        <option value="">Selecione uma ou mais categorias</option>
                    </select>
                    <small class="text-muted">Segure Ctrl ou Shift para selecionar mais de uma</small>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="estado_id" class="form-label">Estado*</label>
                    <select class="form-select" id="estado_id" name="estado_id" required>
                        <option value="">Selecione um estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo $estado['id']; ?>" data-sigla="<?php echo $estado['sigla']; ?>" <?php echo (isset($parceiro['estado_id']) && $parceiro['estado_id'] == $estado['id']) ? 'selected' : ''; ?>>
                                <?php echo $estado['nome'] . ' (' . $estado['sigla'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="municipio_id" class="form-label">Município*</label>
                    <select class="form-select" id="municipio_id" name="municipio_id" required>
                        <option value="">Selecione um município</option>
                        <?php foreach ($municipios as $municipio): ?>
                            <option value="<?php echo $municipio['id']; ?>" data-estado="<?php echo $municipio['estado']; ?>" <?php echo (isset($parceiro['municipio_id']) && $parceiro['municipio_id'] == $municipio['id']) ? 'selected' : ''; ?>>
                                <?php echo $municipio['nome']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3 align-items-start">
                <div class="col-md-3 d-flex flex-column align-items-center">
                    <?php if (!empty($parceiro['imagem_destaque'])): ?>
                    <img src="../uploads/parceiros/destaque/<?php echo htmlspecialchars($parceiro['imagem_destaque']); ?>" class="img-thumbnail mb-2" alt="Imagem de destaque" style="max-height: 150px;">
                    <?php else: ?>
                    <div class="alert alert-info h-100 d-flex align-items-center justify-content-center mb-2">
                        <div class="text-center">
                            <i class="fas fa-image fa-2x mb-2"></i>
                            <p class="mb-0">Nenhuma imagem<br>de destaque</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Campo de upload de imagem de destaque -->
                    <label for="imagem_destaque" class="form-label mt-2">Imagem de destaque</label>
                    <input type="file" class="form-control" id="imagem_destaque" name="imagem_destaque" accept="image/*">
                    <small class="text-muted">Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB.</small>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="endereco" class="form-label">Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo ($parceiro['endereco'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo ($parceiro['telefone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo ($parceiro['whatsapp'] ?? ''); ?>" placeholder="Ex: 5583999999999" maxlength="15" pattern="[0-9]+">
                            <small class="text-muted">Digite apenas números, com DDD e código do país. Exemplo: <b>5583999999999</b></small>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo ($parceiro['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo ($parceiro['website'] ?? ''); ?>" placeholder="https://www.exemplo.com.br">
                        </div>
                        <div class="col-md-6">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2"><?php echo ($parceiro['descricao'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <!-- Redes sociais -->
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="facebook" class="form-label">Facebook</label>
                            <input type="url" class="form-control" id="facebook" name="facebook" value="<?php echo ($parceiro['facebook'] ?? ''); ?>" placeholder="https://www.facebook.com/exemplo">
                        </div>
                        <div class="col-md-6">
                            <label for="instagram" class="form-label">Instagram</label>
                            <input type="url" class="form-control" id="instagram" name="instagram" value="<?php echo ($parceiro['instagram'] ?? ''); ?>" placeholder="https://www.instagram.com/exemplo">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="twitter" class="form-label">Twitter</label>
                            <input type="url" class="form-control" id="twitter" name="twitter" value="<?php echo ($parceiro['twitter'] ?? ''); ?>" placeholder="https://twitter.com/exemplo">
                        </div>
                        <div class="col-md-6">
                            <label for="tiktok" class="form-label">TikTok</label>
                            <input type="url" class="form-control" id="tiktok" name="tiktok" value="<?php echo ($parceiro['tiktok'] ?? ''); ?>" placeholder="https://www.tiktok.com/@exemplo">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="youtube" class="form-label">YouTube</label>
                            <input type="url" class="form-control" id="youtube" name="youtube" value="<?php echo ($parceiro['youtube'] ?? ''); ?>" placeholder="https://www.youtube.com/@exemplo">
                        </div>
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4 mb-3">Configurações</h5>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status" name="status" <?php echo (!isset($parceiro['status']) || $parceiro['status'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Ativo</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="destaque" name="destaque" <?php echo (isset($parceiro['destaque']) && $parceiro['destaque'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="destaque">Destacar no site</label>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i><?php echo ($action === 'add') ? 'Adicionar' : 'Salvar Alterações'; ?>
                </button>
                <a href="parceiros.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
                
                <?php if ($action === 'edit'): ?>
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
                                    Tem certeza que deseja excluir este parceiro?
                                    <br>Esta ação não pode ser desfeita.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <a href="parceiros.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">Sim, Excluir</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($action === 'add'): ?>
            <!-- Seção de upload de galeria para novos parceiros -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Galeria de Fotos</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Você pode adicionar múltiplas fotos à galeria durante a criação do parceiro.
                    </div>
                    
                    <div class="mb-3">
                        <label for="galeria_fotos" class="form-label">Selecione múltiplas fotos (opcional)</label>
                        <input type="file" class="form-control" id="galeria_fotos" name="galeria_fotos[]" accept="image/*" multiple>
                        <small class="text-muted">Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB por arquivo. Dimensões recomendadas: 1200x800px (formato 3:2 para galeria).</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="galeria_legenda" class="form-label">Legenda padrão para as fotos</label>
                        <input type="text" class="form-control" id="galeria_legenda" name="galeria_legenda" placeholder="Legenda padrão para todas as fotos">
                        <small class="text-muted">Esta legenda será aplicada a todas as fotos. Você poderá editar individualmente depois.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($action === 'edit'): ?>
<!-- Seção de Tags -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Tags Associadas</h6>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Tag
        </button>
    </div>
    <div class="card-body">
        <?php
        // Buscar tags associadas ao parceiro
        $query = "SELECT t.id, t.nome
                  FROM tags t
                  JOIN parceiros_tags pt ON t.id = pt.tag_id
                  WHERE pt.parceiro_id = ?
                  ORDER BY t.nome";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags_parceiro = [];
        
        while ($row = $result->fetch_assoc()) {
            $tags_parceiro[] = $row;
        }
        $stmt->close();
        
        if (empty($tags_parceiro)) {
            echo '<p class="text-center">Nenhuma tag associada.</p>';
        } else {
            echo '<div class="row">';
            foreach ($tags_parceiro as $tag) {
                echo '<div class="col-md-3 mb-2">';
                echo '<div class="d-flex align-items-center">';
                echo '<span class="badge bg-primary me-2">' . htmlspecialchars($tag['nome']) . '</span>';
                echo '<a href="parceiros.php?action=removetag&parceiro_id=' . $id . '&tag_id=' . $tag['id'] . '" class="text-danger" title="Remover Tag" onclick="return confirm(\'Deseja remover esta tag?\');">';
                echo '<i class="fas fa-times-circle"></i>';
                echo '</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Modal para adicionar tags -->
<div class="modal fade" id="addTagModal" tabindex="-1" aria-labelledby="addTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTagModalLabel">Adicionar Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="parceiros.php?action=addtag&id=<?php echo $id; ?>" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tag_id" class="form-label">Selecione uma Tag</label>
                        <select class="form-select" id="tag_id" name="tag_id" required>
                            <option value="">Selecione...</option>
                            <?php
                            // Buscar todas as tags disponíveis que ainda não estão associadas ao parceiro
                            $query = "SELECT id, nome FROM tags 
                                      WHERE id NOT IN (
                                          SELECT tag_id FROM parceiros_tags WHERE parceiro_id = ?
                                      )
                                      ORDER BY nome";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($tag = $result->fetch_assoc()) {
                                echo '<option value="' . $tag['id'] . '">' . htmlspecialchars($tag['nome']) . '</option>';
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Seção de Fotos -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Galeria de Fotos</h6>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Foto
        </button>
    </div>
    <div class="card-body">
        <?php
        // Buscar fotos do parceiro
        $query = "SELECT id, arquivo, legenda, ordem FROM fotos 
                  WHERE entidade_tipo = 'parceiro' AND entidade_id = ? 
                  ORDER BY ordem ASC, id ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $fotos = [];
        
        while ($row = $result->fetch_assoc()) {
            $fotos[] = $row;
        }
        $stmt->close();
        
        if (empty($fotos)) {
            echo '<p class="text-center">Nenhuma foto adicionada.</p>';
        } else {
            echo '<div class="row">';
            foreach ($fotos as $foto) {
                echo '<div class="col-md-3 mb-3">';
                echo '<div class="card h-100">';
                echo '<img src="../uploads/parceiros/' . htmlspecialchars($foto['arquivo']) . '" class="card-img-top" alt="Foto do parceiro" style="height: 150px; object-fit: cover;">';
                echo '<div class="card-body p-2">';
                echo '<p class="small mb-1">' . htmlspecialchars($foto['legenda'] ?? 'Sem legenda') . '</p>';
                echo '<div class="d-flex justify-content-between">';
                echo '<small class="text-muted">Ordem: ' . $foto['ordem'] . '</small>';
                echo '<div>';
                echo '<a href="#" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editPhotoModal' . $foto['id'] . '" title="Editar"><i class="fas fa-edit"></i></a>';
                echo '<a href="parceiros.php?action=deletephoto&parceiro_id=' . $id . '&foto_id=' . $foto['id'] . '" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm(\'Deseja excluir esta foto?\');"><i class="fas fa-trash"></i></a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                // Modal para editar foto
                echo '<div class="modal fade" id="editPhotoModal' . $foto['id'] . '" tabindex="-1" aria-labelledby="editPhotoModalLabel' . $foto['id'] . '" aria-hidden="true">';
                echo '<div class="modal-dialog">';
                echo '<div class="modal-content">';
                echo '<div class="modal-header">';
                echo '<h5 class="modal-title" id="editPhotoModalLabel' . $foto['id'] . '">Editar Foto</h5>';
                echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                echo '</div>';
                echo '<form action="parceiros.php?action=editphoto&parceiro_id=' . $id . '&foto_id=' . $foto['id'] . '" method="post">';
                echo '<div class="modal-body">';
                echo '<div class="mb-3">';
                echo '<label for="legenda' . $foto['id'] . '" class="form-label">Legenda</label>';
                echo '<input type="text" class="form-control" id="legenda' . $foto['id'] . '" name="legenda" value="' . htmlspecialchars($foto['legenda'] ?? '') . '">';
                echo '</div>';
                echo '<div class="mb-3">';
                echo '<label for="ordem' . $foto['id'] . '" class="form-label">Ordem</label>';
                echo '<input type="number" class="form-control" id="ordem' . $foto['id'] . '" name="ordem" value="' . $foto['ordem'] . '" min="0">';
                echo '</div>';
                echo '</div>';
                echo '<div class="modal-footer">';
                echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
                echo '<button type="submit" class="btn btn-primary">Salvar</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Modal para adicionar foto -->
<div class="modal fade" id="addPhotoModal" tabindex="-1" aria-labelledby="addPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPhotoModalLabel">Adicionar Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="parceiros.php?action=addphoto&id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Selecione uma Foto</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*" required>
                        <small class="text-muted">Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB. Dimensões recomendadas: 1200x800px (formato 3:2 para galeria).</small>
                    </div>
                    <div class="mb-3">
                        <label for="legenda" class="form-label">Legenda</label>
                        <input type="text" class="form-control" id="legenda" name="legenda">
                    </div>
                    <div class="mb-3">
                        <label for="ordem" class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
// Script para gerar slug automaticamente
document.addEventListener('DOMContentLoaded', function() {
    const nomeInput = document.getElementById('nome');
    const slugInput = document.getElementById('slug');
    
    if (nomeInput && slugInput) {
        nomeInput.addEventListener('blur', function() {
            if (slugInput.value === '') {
                // Função segura para remover acentos e caracteres especiais
                function slugify(str) {
                    return str
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
                        .replace(/[^a-zA-Z0-9\s-]/g, '') // Remove caracteres especiais
                        .replace(/\s+/g, '-') // Espaços por hífen
                        .replace(/-+/g, '-') // Vários hífens por um só
                        .replace(/^-+|-+$/g, '') // Remove hífens do início/fim
                        .toLowerCase();
                }
                slugInput.value = slugify(nomeInput.value);
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estado_id');
    const municipioSelect = document.getElementById('municipio_id');

    if (!estadoSelect || !municipioSelect) return;

    // Desabilita o select de município inicialmente se não houver estado selecionado
    if (!estadoSelect.value) {
        municipioSelect.disabled = true;
    }

    estadoSelect.addEventListener('change', function() {
        const estadoId = this.value;
        if (!estadoId) {
            municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
            municipioSelect.disabled = true;
            return;
        }
        municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
        municipioSelect.disabled = true;
        fetch('../api/get_municipios.php?estado_id=' + estadoId)
            .then(response => {
                if (!response.ok) throw new Error('Erro ao buscar municípios');
                return response.json();
            })
            .then(data => {
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                if (data && data.length > 0) {
                    data.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        municipioSelect.appendChild(option);
                    });
                } else {
                    municipioSelect.innerHTML = '<option value="">Nenhum município encontrado</option>';
                }
                municipioSelect.disabled = false;
            })
            .catch(() => {
                municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                municipioSelect.disabled = true;
            });
    });

    // Se já houver um estado selecionado ao carregar a página, dispara o evento para carregar municípios
    if (estadoSelect.value) {
        const event = new Event('change');
        estadoSelect.dispatchEvent(event);
    }
});

const categoriasAll = <?php echo json_encode($categorias); ?>;
console.log('categoriasAll:', categoriasAll);

document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('tipo');
    const categoriaSelect = document.getElementById('categoria_id');
    // Inicializar Choices.js
    const choices = new Choices(categoriaSelect, {
        removeItemButton: true,
        searchEnabled: true,
        placeholder: true,
        placeholderValue: 'Selecione uma ou mais categorias',
        noResultsText: 'Nenhuma categoria encontrada',
        noChoicesText: 'Nenhuma categoria disponível',
        itemSelectText: 'Selecionar',
        removeItemText: 'Remover',
        shouldSort: false
    });
    function filtrarCategorias() {
        const tipoId = tipoSelect.value;
        choices.clearChoices();
        if (!tipoId) {
            choices.setChoices([], 'value', 'label', true);
            return;
        }
        let selected = <?php echo json_encode($categorias_selecionadas); ?>;
        const filtradas = categoriasAll.filter(cat => String(cat.tipo_id) === String(tipoId));
        choices.setChoices(
            filtradas.map(cat => ({
                value: cat.id,
                label: cat.nome,
                selected: selected.includes(String(cat.id)),
                disabled: false
            })),
            'value',
            'label',
            true
        );
    }
    tipoSelect.addEventListener('change', filtrarCategorias);
    if (tipoSelect.value) {
        filtrarCategorias();
    }
});

// Validação do campo WhatsApp
const form = document.querySelector('form');
if(form) {
  form.addEventListener('submit', function(e) {
    const whatsapp = document.getElementById('whatsapp');
    const valor = whatsapp.value.replace(/\D/g, '');
    if (valor.length < 12 || !/^\d+$/.test(valor)) {
      alert('Por favor, digite o número do WhatsApp apenas com números, incluindo DDI e DDD. Exemplo: 5583999999999');
      whatsapp.focus();
      e.preventDefault();
      return false;
    }
    whatsapp.value = valor;
  });
}
</script>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
    .choices__button { font-size: 0; }
    .choices__button::after { content: 'Remover'; font-size: 14px; }
    </style>
</head>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
