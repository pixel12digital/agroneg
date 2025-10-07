<?php
/**
 * Redirecionador para URLs antigas de municípios
 * Converte municipio.php?estado=X&municipio=Y para URLs amigáveis
 */
require_once("config/db.php");

$estado_id = isset($_GET['estado']) ? (int)$_GET['estado'] : null;
$municipio_id = isset($_GET['municipio']) ? (int)$_GET['municipio'] : null;

if (!$estado_id || !$municipio_id) {
    header('Location: /');
    exit;
}

try {
    $conn = getAgronegConnection();
    
    // Buscar slugs do estado e município
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
        
        // Redirecionamento 301 (permanente) para SEO
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $nova_url");
        exit;
    } else {
        // Município não encontrado, redirecionar para home
        header('Location: /');
        exit;
    }
    
} catch (Exception $e) {
    // Em caso de erro, redirecionar para home
    header('Location: /');
    exit;
}
?>
