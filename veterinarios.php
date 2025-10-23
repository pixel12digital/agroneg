<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once("config/db.php");

// Obter conexão com banco de dados
$conn = getAgronegConnection();

$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;
$categoria_slug = isset($_GET['categoria']) ? htmlspecialchars($_GET['categoria']) : null;

// Detectar caminho base para assets
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Sempre usar caminho absoluto para evitar problemas com servidor PHP built-in
$base_path = '/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinários | AgroNeg</title>
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
                        <h2 class="filter-title">Encontre Veterinários</h2>
                        <p class="filter-subtitle">Selecione seu estado, município e categoria para começar</p>
                        <form class="filter-form" method="get" action="">
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
                                    <option value="">Selecione um estado</option>
                                    <?php
                                    $disabled = empty($estado_id) ? 'disabled' : '';
                                    echo $disabled;
                                    if (!empty($estado_id)) {
                                        $query_municipios = "SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome ASC";
                                        $stmt_municipios = $conn->prepare($query_municipios);
                                        $stmt_municipios->bind_param('i', $estado_id);
                                        $stmt_municipios->execute();
                                        $res_municipios = $stmt_municipios->get_result();
                                        if ($res_municipios && $res_municipios->num_rows > 0) {
                                            while ($mun = $res_municipios->fetch_assoc()) {
                                                $selected = ($municipio_id == $mun['id']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($mun['id']) . '" ' . $selected . '>' . htmlspecialchars($mun['nome']) . ' (ID: ' . $mun['id'] . ')</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row button-row">
                                <button type="submit" class="filter-button" id="buscar-btn">Buscar</button>
                            </div>
                        </form>
                        <div class="filter-categories">
                            <?php
                            $query_categorias = "SELECT nome, slug FROM categorias WHERE tipo_id = (SELECT id FROM tipos_parceiros WHERE slug = 'veterinarios' LIMIT 1) ORDER BY nome";
                            $res_categorias = $conn->query($query_categorias);
                            if ($res_categorias) {
                                while ($cat = $res_categorias->fetch_assoc()) {
                                    echo '<div class="category-option" data-value="' . htmlspecialchars($cat['slug']) . '">' . htmlspecialchars($cat['nome']) . '</div>';
                                }
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
                    $where = ["p.status = 1", "p.municipio_id = ?", "t.slug = 'veterinarios'"];
                    $params = [$municipio_id];
                    $types = 'i';

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
                                    echo '<img src="<?php echo $base_path; ?>uploads/parceiros/destaque/' . htmlspecialchars($parceiro['imagem_destaque']) . '" alt="' . htmlspecialchars($parceiro['nome']) . '">';
                                } else {
                                    echo '<img src="<?php echo $base_path; ?>assets/img/placeholder.jpg" alt="' . htmlspecialchars($parceiro['nome']) . '">';
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
                            echo '<p style="text-align:center;">Nenhum veterinário encontrado neste município.</p>';
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
</body>
</html> 