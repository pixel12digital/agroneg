<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: eventos.php');
    exit;
}

// Função para gerar slug
function gerarSlug($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/[áàãâä]/ui', 'a', $texto);
    $texto = preg_replace('/[éèêë]/ui', 'e', $texto);
    $texto = preg_replace('/[íìîï]/ui', 'i', $texto);
    $texto = preg_replace('/[óòõôö]/ui', 'o', $texto);
    $texto = preg_replace('/[úùûü]/ui', 'u', $texto);
    $texto = preg_replace('/[ç]/ui', 'c', $texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    return trim($texto, '-');
}

// Validar e sanitizar dados
$nome = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
$descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
$categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);
$modalidade = filter_input(INPUT_POST, 'modalidade', FILTER_SANITIZE_STRING);
$link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
$contato = filter_input(INPUT_POST, 'contato', FILTER_SANITIZE_STRING);
$municipio_id = filter_input(INPUT_POST, 'municipio_id', FILTER_VALIDATE_INT);
$estado_id = filter_input(INPUT_POST, 'estado_id', FILTER_VALIDATE_INT);

// Validar campos obrigatórios
if (!$nome || !$descricao || !$categoria || !$modalidade || !$municipio_id || !$estado_id) {
    $_SESSION['erro'] = "Por favor, preencha todos os campos obrigatórios.";
    header('Location: adicionar-evento.php');
    exit;
}

// Gerar slug único
$slug = gerarSlug($nome);
$slug_base = $slug;
$contador = 1;

// Verificar se o slug já existe
while (true) {
    $stmt = $conn->prepare("SELECT id FROM eventos_municipio WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        break;
    }
    
    $slug = $slug_base . '-' . $contador;
    $contador++;
}

// Processar upload de imagem
$imagem = null;
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    $ext_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($ext, $ext_permitidas)) {
        $_SESSION['erro'] = "Formato de imagem não permitido. Use: " . implode(', ', $ext_permitidas);
        header('Location: adicionar-evento.php');
        exit;
    }
    
    $nome_arquivo = uniqid('evento_') . '.' . $ext;
    $diretorio = 'uploads/eventos/';
    
    // Criar diretório se não existir
    if (!file_exists($diretorio)) {
        mkdir($diretorio, 0777, true);
    }
    
    $caminho_completo = $diretorio . $nome_arquivo;
    
    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_completo)) {
        $imagem = $caminho_completo;
    }
}

try {
    // Inserir evento
    $stmt = $conn->prepare("INSERT INTO eventos_municipio (municipio_id, estado_id, nome, slug, descricao, categoria, modalidade, data_inicio, data_fim, status, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssssss", $municipio_id, $estado_id, $nome, $slug, $descricao, $categoria, $modalidade, $_POST['data_inicio'], $_POST['data_fim'], $_POST['status'], $imagem);
    
    if ($stmt->execute()) {
        $evento_id = $stmt->insert_id;
        
        // Inserir tag baseada na categoria
        $stmt_tag = $conn->prepare("SELECT id FROM tags WHERE slug = ? AND categoria_id = (SELECT id FROM categorias WHERE slug = 'eventos')");
        $stmt_tag->bind_param("s", $categoria);
        $stmt_tag->execute();
        $result_tag = $stmt_tag->get_result();
        
        if ($tag = $result_tag->fetch_assoc()) {
            $stmt_evento_tag = $conn->prepare("INSERT INTO eventos_tags (evento_id, tag_id) VALUES (?, ?)");
            $stmt_evento_tag->bind_param("ii", $evento_id, $tag['id']);
            $stmt_evento_tag->execute();
        }
        
        $_SESSION['sucesso'] = "Evento cadastrado com sucesso!";
        header('Location: eventos.php');
        exit;
    } else {
        throw new Exception("Erro ao cadastrar evento: " . $stmt->error);
    }
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao processar o evento: " . $e->getMessage();
    header('Location: adicionar-evento.php');
    exit;
}
?> 