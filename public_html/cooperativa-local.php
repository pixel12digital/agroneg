<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';

// Verificar se foi fornecido um ID de cooperativa
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: cooperativas.php');
    exit;
}

$cooperativa_id = (int)$_GET['id'];

// Buscar informações da cooperativa
$sql = "SELECT p.*, m.nome as municipio, e.sigla as estado, e.nome as estado_nome 
        FROM parceiros p
        LEFT JOIN municipios m ON p.municipio_id = m.id
        LEFT JOIN estados e ON m.estado_id = e.id
        JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
        JOIN categorias c ON pc.categoria_id = c.id
        WHERE p.id = ? AND c.slug = 'cooperativas'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cooperativa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: cooperativas.php');
    exit;
}

$cooperativa = $result->fetch_assoc();

// Buscar tags da cooperativa
$sql_tags = "SELECT t.nome, t.slug 
             FROM tags t
             JOIN parceiros_tags pt ON t.id = pt.tag_id
             WHERE pt.parceiro_id = ?
             ORDER BY t.nome";
$stmt_tags = $conn->prepare($sql_tags);
$stmt_tags->bind_param("i", $cooperativa_id);
$stmt_tags->execute();
$tags_result = $stmt_tags->get_result();
$tags = [];
while ($tag = $tags_result->fetch_assoc()) {
    $tags[] = $tag;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cooperativa['nome']); ?> | AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .cooperativa-detail {
            padding: 2rem 0;
        }
        .cooperativa-header {
            background-color: #f5f5f5;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .cooperativa-header h1 {
            margin: 0;
            color: #333;
            font-size: 2rem;
        }
        .cooperativa-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .info-item {
            margin-bottom: 0.8rem;
        }
        .info-item strong {
            display: block;
            color: #666;
            margin-bottom: 0.3rem;
        }
        .tags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .tag {
            background: #e9ecef;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #495057;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #333;
        }
        .back-link i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
<?php include __DIR__.'/partials/header.php'; ?>

<div class="main-content">
    <div class="cooperativa-header">
        <div class="container">
            <a href="cooperativas.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para lista de cooperativas
            </a>
            <h1><?php echo htmlspecialchars($cooperativa['nome']); ?></h1>
        </div>
    </div>

    <div class="cooperativa-detail">
        <div class="container">
            <div class="cooperativa-info">
                <div class="info-card">
                    <h3>Informações de Contato</h3>
                    <?php if (!empty($cooperativa['telefone'])): ?>
                    <div class="info-item">
                        <strong>Telefone</strong>
                        <?php echo htmlspecialchars($cooperativa['telefone']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cooperativa['email'])): ?>
                    <div class="info-item">
                        <strong>E-mail</strong>
                        <?php echo htmlspecialchars($cooperativa['email']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cooperativa['site'])): ?>
                    <div class="info-item">
                        <strong>Site</strong>
                        <a href="<?php echo htmlspecialchars($cooperativa['site']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo htmlspecialchars($cooperativa['site']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Localização</h3>
                    <?php if (!empty($cooperativa['municipio'])): ?>
                    <div class="info-item">
                        <strong>Município</strong>
                        <?php echo htmlspecialchars($cooperativa['municipio']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cooperativa['estado_nome'])): ?>
                    <div class="info-item">
                        <strong>Estado</strong>
                        <?php echo htmlspecialchars($cooperativa['estado_nome']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($cooperativa['endereco'])): ?>
                    <div class="info-item">
                        <strong>Endereço</strong>
                        <?php echo htmlspecialchars($cooperativa['endereco']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($tags)): ?>
                <div class="info-card">
                    <h3>Especialidades</h3>
                    <div class="tags-list">
                        <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag['nome']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cooperativa['descricao'])): ?>
            <div class="info-card">
                <h3>Sobre a Cooperativa</h3>
                <div class="info-item">
                    <?php echo nl2br(htmlspecialchars($cooperativa['descricao'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__.'/partials/footer.php'; ?>
<script src="assets/js/header.js"></script>
</body>
</html> 