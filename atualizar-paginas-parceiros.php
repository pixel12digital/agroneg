<?php
/**
 * Script para atualizar todas as páginas de parceiros para aceitar slugs
 */

$paginas = ['criadores.php', 'veterinarios.php', 'lojas-agropet.php', 'cooperativas.php'];

foreach ($paginas as $pagina) {
    if (file_exists($pagina)) {
        echo "Atualizando $pagina...\n";
        
        $conteudo = file_get_contents($pagina);
        
        // Substituir a lógica de parâmetros
        $antigo = '<?php
ini_set(\'display_errors\', 0);
error_reporting(E_ALL);
require_once("config/db.php");

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Validar e obter os IDs numéricos da URL
$estado_id = isset($_GET[\'estado\']) ? filter_var($_GET[\'estado\'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET[\'municipio\']) ? filter_var($_GET[\'municipio\'], FILTER_VALIDATE_INT) : null;
$categoria_slug = isset($_GET[\'categoria\']) ? htmlspecialchars($_GET[\'categoria\']) : null;';

        $novo = '<?php
ini_set(\'display_errors\', 0);
error_reporting(E_ALL);
require_once("config/db.php");

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se está usando slugs ou IDs
$slug_estado = isset($_GET[\'slug_estado\']) ? $_GET[\'slug_estado\'] : null;
$slug_municipio = isset($_GET[\'slug_municipio\']) ? $_GET[\'slug_municipio\'] : null;
$estado_id = isset($_GET[\'estado\']) ? filter_var($_GET[\'estado\'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET[\'municipio\']) ? filter_var($_GET[\'municipio\'], FILTER_VALIDATE_INT) : null;
$categoria_slug = isset($_GET[\'categoria\']) ? htmlspecialchars($_GET[\'categoria\']) : null;

// Se está usando slugs, converter para IDs
if ($slug_estado && $slug_municipio) {
    $query = "
        SELECT m.id as municipio_id, e.id as estado_id, m.nome as municipio_nome, e.nome as estado_nome
        FROM municipios m
        JOIN estados e ON m.estado_id = e.id
        WHERE LOWER(e.sigla) = LOWER(?) AND m.slug = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $slug_estado, $slug_municipio);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estado_id = $row[\'estado_id\'];
        $municipio_id = $row[\'municipio_id\'];
        $municipio_nome = $row[\'municipio_nome\'];
        $estado_nome = $row[\'estado_nome\'];
    } else {
        header(\'Location: index.php\');
        exit;
    }
}';

        $conteudo = str_replace($antigo, $novo, $conteudo);
        file_put_contents($pagina, $conteudo);
        
        echo "✅ $pagina atualizada com sucesso!\n";
    } else {
        echo "❌ $pagina não encontrada!\n";
    }
}

echo "\n🎉 Todas as páginas foram atualizadas!\n";
?>
