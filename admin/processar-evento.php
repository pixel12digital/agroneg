<?php
// Processar formulário de eventos
require_once("includes/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $modalidade = trim($_POST['modalidade'] ?? '');
    $data_inicio = trim($_POST['data_inicio'] ?? '');
    $data_fim = trim($_POST['data_fim'] ?? '');
    $estado_id = (int)($_POST['estado'] ?? 0);
    $municipio_id = (int)($_POST['municipio'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $evento_id = (int)($_POST['id'] ?? 0);
    
    // Validações básicas
    $erros = [];
    
    if (empty($titulo)) {
        $erros[] = "Título é obrigatório";
    }
    
    if (empty($categoria)) {
        $erros[] = "Categoria é obrigatória";
    }
    
    if (empty($modalidade)) {
        $erros[] = "Modalidade é obrigatória";
    }
    
    if (empty($data_inicio)) {
        $erros[] = "Data de início é obrigatória";
    }
    
    if ($estado_id <= 0) {
        $erros[] = "Estado é obrigatório";
    }
    
    if ($municipio_id <= 0) {
        $erros[] = "Município é obrigatório";
    }
    
    if (empty($erros)) {
        try {
            // Processar upload de imagem
            $imagem_nome = null;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $upload_dir = '../uploads/eventos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($extensao, $extensoes_permitidas)) {
                    $imagem_nome = uniqid() . '_' . time() . '.' . $extensao;
                    $caminho_imagem = $upload_dir . $imagem_nome;
                    
                    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_imagem)) {
                        // Redimensionar imagem se necessário
                        // Aqui você pode adicionar código para redimensionar a imagem
                    } else {
                        $erros[] = "Erro ao fazer upload da imagem";
                    }
                } else {
                    $erros[] = "Formato de imagem não permitido";
                }
            }
            
            if (empty($erros)) {
                if ($evento_id > 0) {
                    // Atualizar evento existente
                    $sql = "UPDATE eventos_municipio SET 
                            nome = ?, categoria = ?, modalidade = ?, data_inicio = ?, data_fim = ?, 
                            estado_id = ?, municipio_id = ?, descricao = ?, local = ?, status = ?";
                    
                    $params = [$titulo, $categoria, $modalidade, $data_inicio, $data_fim, 
                              $estado_id, $municipio_id, $descricao, $local, $status];
                    
                    if ($imagem_nome) {
                        $sql .= ", imagem = ?";
                        $params[] = $imagem_nome;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $evento_id;
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Evento atualizado com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao atualizar evento: " . $conn->error;
                    }
                } else {
                    // Criar novo evento
                    $sql = "INSERT INTO eventos_municipio 
                            (nome, categoria, modalidade, data_inicio, data_fim, estado_id, municipio_id, descricao, local, status, imagem) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssiisss", $titulo, $categoria, $modalidade, $data_inicio, $data_fim, 
                                     $estado_id, $municipio_id, $descricao, $local, $status, $imagem_nome);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Evento criado com sucesso!";
                    } else {
                        $_SESSION['error'] = "Erro ao criar evento: " . $conn->error;
                    }
                }
                
                $stmt->close();
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $erros);
    }
    
    // Redirecionar de volta para a página de eventos
    header("Location: eventos.php");
    exit();
} else {
    // Se não for POST, redirecionar para eventos
    header("Location: eventos.php");
    exit();
}
?>
