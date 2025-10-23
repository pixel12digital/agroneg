<?php
/**
 * SOLUÇÃO DEFINITIVA PARA REDIRECIONAMENTO DE URLs AMIGÁVEIS
 * 
 * Este arquivo deve ser renomeado para municipio.php para substituir o atual
 * e garantir que o redirecionamento funcione corretamente.
 */

// Incluir arquivo de conexão com banco de dados
require_once("config/db.php");

// --- DETECTAR URLs AMIGÁVEIS VIA PATH ---
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Padrão de URL amigável: /ce/iracema
if (preg_match('/^\/([a-z]{2})\/([a-z0-9-]+)\/?$/', $path, $matches)) {
    $_GET['slug_estado'] = $matches[1];
    $_GET['slug_municipio'] = $matches[2];
}

// --- REDIRECIONAMENTO AUTOMÁTICO PARA URLs AMIGÁVEIS ---
// Se está usando IDs (URL antiga), redirecionar para slug
if (isset($_GET['estado']) && isset($_GET['municipio']) && !isset($_GET['slug_estado'])) {
    $estado_id = filter_var($_GET['estado'], FILTER_VALIDATE_INT);
    $municipio_id = filter_var($_GET['municipio'], FILTER_VALIDATE_INT);
    
    if ($estado_id && $municipio_id) {
        try {
            $conn = getAgronegConnection();
            
            $query = "
                SELECT m.slug as municipio_slug, e.sigla as estado_sigla
                FROM municipios m
                JOIN estados e ON m.estado_id = e.id
                WHERE e.id = ? AND m.id = ?
                LIMIT 1
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $estado_id, $municipio_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $nova_url = "/" . strtolower($row['estado_sigla']) . "/" . $row['municipio_slug'];
                
                // Preservar parâmetros adicionais
                $params = $_GET;
                unset($params['estado'], $params['municipio']);
                if (!empty($params)) {
                    $nova_url .= "?" . http_build_query($params);
                }
                
                // Redirecionamento 301 permanente
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $nova_url");
                exit;
            }
        } catch (Exception $e) {
            // Em caso de erro de conexão, continuar sem redirecionamento
            error_log("Erro ao redirecionar: " . $e->getMessage());
        }
    }
}

// --- LÓGICA PRINCIPAL DO MUNICÍPIO ---

// Obter conexão com banco de dados
$conn = getAgronegConnection();
if (!$conn) {
    // Se não conseguir conectar, mostrar página de erro
    http_response_code(503);
    die('
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Serviço Temporariamente Indisponível</h2>
        <p>O sistema está temporariamente fora do ar devido a limitações de conexão.</p>
        <p>Tente novamente em alguns minutos.</p>
        <p><a href="index.php">Voltar para a página inicial</a></p>
    </div>
    ');
}

// Verificar se está usando slugs ou IDs
$slug_estado = isset($_GET['slug_estado']) ? $_GET['slug_estado'] : null;
$slug_municipio = isset($_GET['slug_municipio']) ? $_GET['slug_municipio'] : null;
$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;

$municipio = null;

// Buscar município por slugs (URLs amigáveis)
if ($slug_estado && $slug_municipio) {
    $query_municipio = "
        SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla, e.id as estado_id, m.id as municipio_id
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
    ";
    $stmt_municipio = $conn->prepare($query_municipio);
    $stmt_municipio->bind_param("ss", $slug_estado, $slug_municipio);
    $stmt_municipio->execute();
    $resultado_municipio = $stmt_municipio->get_result();
    
    if ($resultado_municipio->num_rows > 0) {
        $municipio = $resultado_municipio->fetch_assoc();
        $estado_id = $municipio['estado_id'];
        $municipio_id = $municipio['municipio_id'];
    }
}
// Buscar município por IDs (URLs antigas - compatibilidade)
elseif ($estado_id && $municipio_id) {
    $query_municipio = "
        SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla, e.id as estado_id, m.id as municipio_id
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE e.id = ? AND m.id = ?
    ";
    $stmt_municipio = $conn->prepare($query_municipio);
    $stmt_municipio->bind_param("ii", $estado_id, $municipio_id);
    $stmt_municipio->execute();
    $resultado_municipio = $stmt_municipio->get_result();
    
    if ($resultado_municipio->num_rows > 0) {
        $municipio = $resultado_municipio->fetch_assoc();
    }
}

// Se o município não for encontrado, redireciona
if (!$municipio) {
    header('Location: index.php');
    exit;
}

// Resto do código do municipio.php original continua aqui...
// (buscar galeria, parceiros, etc.)

// Título da página
$titulo_pagina = $municipio['nome'] . ' - ' . $municipio['estado_nome'] . ' | AgroNeg';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/municipio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    
    <div class="main-content">
        <section class="municipio-header">
            <div class="container">
                <!-- Breadcrumb -->
                <div class="municipio-breadcrumb">
                    <a href="index.php">Home</a> &gt; 
                    <a href="index.php">Estados</a> &gt; 
                    <a href="index.php"><?php echo $municipio['estado_nome']; ?></a> &gt; 
                    <span><?php echo $municipio['nome']; ?></span>
                </div>
                
                <h1><?php echo $municipio['nome']; ?> - <?php echo $municipio['estado_nome']; ?></h1>
                
                <!-- Debug info (remover em produção) -->
                <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 12px;">
                    <strong>Debug Info:</strong><br>
                    URL atual: <?php echo $_SERVER['REQUEST_URI']; ?><br>
                    Slug estado: <?php echo $slug_estado ?? 'não definido'; ?><br>
                    Slug município: <?php echo $slug_municipio ?? 'não definido'; ?><br>
                    Estado ID: <?php echo $estado_id ?? 'não definido'; ?><br>
                    Município ID: <?php echo $municipio_id ?? 'não definido'; ?>
                </div>
                
                <?php if (!empty($municipio['imagem_principal'])): ?>
                <div class="municipio-imagem">
                    <img src="uploads/municipios/<?php echo $municipio['imagem_principal']; ?>" alt="<?php echo $municipio['nome']; ?>">
                </div>
                <?php endif; ?>
                
                <!-- Demais informações do município -->
                <div class="municipio-info">
                    <div class="municipio-dados">
                        <?php if (!empty($municipio['populacao'])): ?>
                        <div class="municipio-dado">
                            <h4><i class="fas fa-users"></i> População</h4>
                            <p><?php echo $municipio['populacao']; ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($municipio['area_rural'])): ?>
                        <div class="municipio-dado">
                            <h4><i class="fas fa-map-marked-alt"></i> Área Rural</h4>
                            <p><?php echo $municipio['area_rural']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($municipio['principais_culturas'])): ?>
                    <div class="municipio-culturas">
                        <h3>Principais Culturas</h3>
                        <p><?php echo $municipio['principais_culturas']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    
    <?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>

