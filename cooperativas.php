<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once("config/db.php");

// Validar e obter os IDs numéricos da URL
$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;
$categoria_slug = isset($_GET['categoria']) ? htmlspecialchars($_GET['categoria']) : null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooperativas | AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/banner.css">
    <link rel="stylesheet" href="assets/css/filters.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__.'/partials/header.php'; ?>
<div class="main-content">
    <section class="banner-section">
        <div class="container">
            <div class="banner-wrapper">
                <img src="assets/img/banner-inicial.png" alt="Banner AgroNeg" class="banner-img">
                <div class="filter-container">
                    <div class="filter-content">
                        <h2 class="filter-title">Encontre Cooperativas</h2>
                        <p class="filter-subtitle">Selecione seu estado e município para começar</p>
                        <form class="filter-form" method="get" action="municipio.php">
                            <div class="filter-row">
                                <label for="estado" class="filter-label">Estado</label>
                                <select id="estado" name="estado" class="filter-select" required>
                                    <option value="">Selecione um estado</option>
                                    <?php
                                    $query_estados = "SELECT DISTINCT e.id, e.nome FROM estados e INNER JOIN municipios m ON e.id = m.estado_id ORDER BY e.nome ASC";
                                    $resultado_estados = $conn->query($query_estados);
                                    if ($resultado_estados && $resultado_estados->num_rows > 0) {
                                        while ($estado = $resultado_estados->fetch_assoc()) {
                                            $selected = ($estado_id == $estado['id']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($estado['id']) . '" ' . $selected . '>' . htmlspecialchars($estado['nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="municipio" class="filter-label">Município</label>
                                <select id="municipio" name="municipio" class="filter-select" required disabled>
                                    <option value="">Selecione um estado</option>
                                </select>
                            </div>
                            <div class="filter-row button-row">
                                <button type="submit" class="filter-button" id="buscar-btn">Buscar</button>
                            </div>
                             <input type="hidden" name="categorias" value="cooperativas">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="results-section" style="padding: 40px 0;">
        <div class="container">
            <h3 class="results-title" style="text-align: center; margin-bottom: 30px;">Resultados da Busca</h3>
            <div class="results-list">
                <?php
                if ($estado_id && $municipio_id) {
                    $where = ["p.status = 1", "p.municipio_id = ?", "t.slug = 'cooperativas'"];
                    $params = [$municipio_id];
                    $types = 'i';

                    $sql = "SELECT p.*, m.nome as municipio, e.sigla as estado 
                            FROM parceiros p
                            JOIN tipos_parceiros t ON p.tipo_id = t.id
                            JOIN municipios m ON p.municipio_id = m.id
                            JOIN estados e ON m.estado_id = e.id
                            WHERE " . implode(' AND ', $where) . "
                            GROUP BY p.id
                            ORDER BY p.destaque DESC, p.nome";
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res && $res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                echo '<div class="result-card" style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
                                echo '<h4>' . htmlspecialchars($row['nome']) . '</h4>';
                                echo '<p><b>Município:</b> ' . htmlspecialchars($row['municipio']) . ' / ' . htmlspecialchars($row['estado']) . '</p>';
                                if(!empty($row['telefone'])) echo '<p><b>Contato:</b> ' . htmlspecialchars($row['telefone']) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="text-align:center;">Nenhuma cooperativa encontrada neste município.</p>';
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
<script src="assets/js/header.js"></script>
<script src="assets/js/filters.js"></script>
</body>
</html> 