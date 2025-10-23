<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once("config/db.php");

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Detectar caminho base para assets
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Sempre usar caminho absoluto para evitar problemas com servidor PHP built-in
$base_path = '/';

// Verificar se está usando slugs ou IDs
$slug_estado = isset($_GET['slug_estado']) ? $_GET['slug_estado'] : null;
$slug_municipio = isset($_GET['slug_municipio']) ? $_GET['slug_municipio'] : null;
$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;
$categoria_slug = isset($_GET['categoria']) ? htmlspecialchars($_GET['categoria']) : null;

// Processar busca via POST (formulário de busca)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado_id = isset($_POST['estado']) ? filter_var($_POST['estado'], FILTER_VALIDATE_INT) : null;
    $municipio_id = isset($_POST['municipio']) ? filter_var($_POST['municipio'], FILTER_VALIDATE_INT) : null;
    $categoria_slug = isset($_POST['categoria']) ? htmlspecialchars($_POST['categoria']) : null;
}

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
        $estado_id = $row['estado_id'];
        $municipio_id = $row['municipio_id'];
        $municipio_nome = $row['municipio_nome'];
        $estado_nome = $row['estado_nome'];
    } else {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtores | AgroNeg</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/banner.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/filters.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .parceiros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 32px;
            margin-bottom: 40px;
        }
        .parceiro-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .parceiro-card.destaque {
            border: 2px solid #F7941D;
        }
        .parceiro-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .parceiro-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .destaque-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #F7941D;
            color: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .parceiro-content {
            padding: 20px;
        }
        .parceiro-title {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        .parceiro-categoria {
            font-size: 14px;
            color: #006837;
            margin-bottom: 15px;
        }
        .parceiro-descricao {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .btn-ver-mais {
            display: inline-block;
            padding: 8px 15px;
            background-color: #006837;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .btn-ver-mais:hover {
            background-color: #004d27;
        }
        @media (min-width: 992px) {
            .parceiros-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Animação do spinner */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #006837;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        .no-categories {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<?php include __DIR__.'/partials/header.php'; ?>
<div class="main-content">
    <section class="banner-section">
        <div class="container">
            <div class="banner-wrapper">
                <img src="<?php echo $base_path; ?>assets/img/banner-inicial.png" alt="Banner AgroNeg" class="banner-img">
                <div class="filter-container">
                    <div class="filter-content">
                        <h2 class="filter-title">Encontre Produtores</h2>
                        <p class="filter-subtitle">Selecione seu estado, município e categoria para começar</p>
                        <form class="filter-form" method="post" action="">
                            <div class="filter-row">
                                <label for="estado" class="filter-label">Estado</label>
                                <select id="estado" name="estado" class="filter-select" required>
                                    <option value="">Selecione um estado</option>
                                    <?php
                                    $query_estados = "SELECT DISTINCT e.id, e.nome, e.sigla FROM estados e INNER JOIN municipios m ON e.id = m.estado_id ORDER BY e.nome ASC";
                                    $resultado_estados = $conn->query($query_estados);
                                    if ($resultado_estados && $resultado_estados->num_rows > 0) {
                                        while ($estado = $resultado_estados->fetch_assoc()) {
                                            $selected = ($estado_id == $estado['id']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($estado['id']) . '" data-slug="' . htmlspecialchars(strtolower($estado['sigla'])) . '" ' . $selected . '>' . htmlspecialchars($estado['nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="municipio" class="filter-label">Município</label>
                                <select id="municipio" name="municipio" class="filter-select" required>
                                    <option value="">Selecione um município</option>
                                    <?php
                                    if ($estado_id) {
                                        $query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
                                        $stmt_municipios = $conn->prepare($query_municipios);
                                        if ($stmt_municipios) {
                                            $stmt_municipios->bind_param("i", $estado_id);
                                            $stmt_municipios->execute();
                                            $resultado_municipios = $stmt_municipios->get_result();
                                            if ($resultado_municipios && $resultado_municipios->num_rows > 0) {
                                                while ($municipio = $resultado_municipios->fetch_assoc()) {
                                                    $selected = ($municipio_id == $municipio['id']) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($municipio['id']) . '" ' . $selected . '>' . htmlspecialchars($municipio['nome']) . '</option>';
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row button-row">
                                <input type="hidden" id="categoria-hidden" name="categoria" value="">
                                <button type="submit" class="filter-button" id="buscar-btn">Buscar</button>
                            </div>
                        </form>
                        <div class="filter-categories" id="filter-categories">
                            <?php
                            // Se há estado e município selecionados, mostrar categorias dos parceiros encontrados
                            if ($estado_id && $municipio_id) {
                                $query_categorias_dinamicas = "
                                    SELECT DISTINCT c.nome, c.slug 
                                    FROM categorias c
                                    INNER JOIN parceiros_categorias pc ON c.id = pc.categoria_id
                                    INNER JOIN parceiros p ON pc.parceiro_id = p.id
                                    INNER JOIN tipos_parceiros t ON p.tipo_id = t.id
                                    WHERE p.municipio_id = ? AND p.status = 1 AND t.slug = 'produtores'
                                    ORDER BY c.nome
                                ";
                                $stmt_categorias = $conn->prepare($query_categorias_dinamicas);
                                if ($stmt_categorias) {
                                    $stmt_categorias->bind_param("i", $municipio_id);
                                    $stmt_categorias->execute();
                                    $res_categorias = $stmt_categorias->get_result();
                                    
                                    if ($res_categorias && $res_categorias->num_rows > 0) {
                                        while ($cat = $res_categorias->fetch_assoc()) {
                                            echo '<div class="category-option" data-value="' . htmlspecialchars($cat['slug']) . '">' . htmlspecialchars($cat['nome']) . '</div>';
                                        }
                                    } else {
                                        echo '<div class="no-categories">Nenhuma categoria específica encontrada para este município.</div>';
                                    }
                                }
                            } else {
                                // Se não há seleção, mostrar mensagem
                                echo '<div class="no-categories">Selecione um estado e município para ver as categorias disponíveis.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="results-section" style="padding: 40px 0;">
        <div class="container">
            <h3 class="results-title" style="text-align: center; margin-bottom: 30px;">Resultados da Busca</h3>
            <div class="parceiros-grid">
                <?php
                if ($estado_id && $municipio_id) {
                    $where = ["p.status = 1", "p.municipio_id = ?", "t.slug = 'produtores'"];
                    $params = [$municipio_id];
                    $types = 'i';
                    
                    // Adicionar filtro por categoria se especificado
                    if ($categoria_slug) {
                        $where[] = "c.slug = ?";
                        $params[] = $categoria_slug;
                        $types .= 's';
                    }

                    $sql = "SELECT p.*, m.nome as municipio, e.sigla as estado, GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as categorias_parceiro, t.nome as tipo_nome, t.slug as tipo_slug
                            FROM parceiros p
                            LEFT JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
                            LEFT JOIN categorias c ON pc.categoria_id = c.id
                            JOIN municipios m ON p.municipio_id = m.id
                            JOIN estados e ON m.estado_id = e.id
                            JOIN tipos_parceiros t ON p.tipo_id = t.id
                            WHERE " . implode(' AND ', $where) . "
                            GROUP BY p.id
                            ORDER BY p.destaque DESC, p.nome";
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res && $res->num_rows > 0) {
                            while ($parceiro = $res->fetch_assoc()) {
                                echo '<div class="parceiro-card ' . ($parceiro['destaque'] ? 'destaque' : '') . '">';
                                echo '<div class="parceiro-image">';
                                if (!empty($parceiro['imagem_destaque'])) {
                                    echo '<img src="' . $base_path . 'uploads/parceiros/destaque/' . htmlspecialchars($parceiro['imagem_destaque']) . '" alt="' . htmlspecialchars($parceiro['nome']) . '">';
                                } else {
                                    echo '<img src="' . $base_path . 'assets/img/placeholder.svg" alt="' . htmlspecialchars($parceiro['nome']) . '">';
                                }
                                if ($parceiro['destaque']) {
                                    echo '<span class="destaque-badge">Destaque</span>';
                                }
                                echo '</div>';
                                echo '<div class="parceiro-content">';
                                echo '<h3 class="parceiro-title">' . htmlspecialchars($parceiro['nome']) . '</h3>';
                                echo '<div class="parceiro-categoria">' . htmlspecialchars($parceiro['categorias_parceiro']);
                                if (isset($parceiro['tipo_nome'])) echo ' - ' . htmlspecialchars($parceiro['tipo_nome']);
                                echo '</div>';
                                if (!empty($parceiro['descricao'])) {
                                    echo '<p class="parceiro-descricao">' . substr(htmlspecialchars($parceiro['descricao']), 0, 120) . '...</p>';
                                }
                                echo '<a href="parceiro.php?slug=' . htmlspecialchars($parceiro['slug']) . '" class="btn-ver-mais">Ver detalhes</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="text-align:center;">Nenhum produtor encontrado neste município.</p>';
                        }
                    }
                } else {
                    echo '<p style="text-align:center;">Selecione um estado e município para ver os resultados.</p>';
                }
                ?>
            </div>
        </div>
    </section>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
<script src="<?php echo $base_path; ?>assets/js/header.js"></script>
<script src="<?php echo $base_path; ?>assets/js/filters.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar campo hidden quando categoria é selecionada
    const categoriaOptions = document.querySelectorAll('.category-option');
    const categoriaHidden = document.getElementById('categoria-hidden');
    
    categoriaOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remover classe active de todas as opções
            categoriaOptions.forEach(opt => opt.classList.remove('active'));
            
            // Adicionar classe active à opção clicada
            this.classList.add('active');
            
            // Atualizar campo hidden
            categoriaHidden.value = this.dataset.value || '';
        });
    });
    
    // Preencher categoria selecionada se houver
    const urlParams = new URLSearchParams(window.location.search);
    const categoriaParam = urlParams.get('categoria');
    if (categoriaParam) {
        categoriaHidden.value = categoriaParam;
        categoriaOptions.forEach(option => {
            if (option.dataset.value === categoriaParam) {
                option.classList.add('active');
            }
        });
    }
});
</script>
</body>
</html> 