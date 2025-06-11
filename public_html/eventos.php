<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos | AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/banner.css">
    <link rel="stylesheet" href="assets/css/filters.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .event-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 24px; margin-bottom: 24px; }
        .event-card h4 { margin: 0 0 10px 0; color: #1A9B60; }
        .event-card .event-meta { font-size: 15px; color: #666; margin-bottom: 8px; }
        .event-card .event-desc { color: #444; margin-bottom: 8px; }
        .event-card .event-status { font-size: 13px; font-weight: bold; color: #fff; background: #1A9B60; border-radius: 4px; padding: 2px 10px; display: inline-block; }
        .event-card .event-status.inativo { background: #aaa; }
    </style>
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
                        <h2 class="filter-title">Encontre Eventos</h2>
                        <p class="filter-subtitle">Selecione seu estado, município e categoria para começar</p>
                        <form class="filter-form" method="get" action="eventos.php">
                            <div class="filter-row">
                                <label for="estado" class="filter-label">Estado</label>
                                <select id="estado" name="estado" class="filter-select" required>
                                    <option value="">Selecione um estado</option>
                                    <?php
                                    $query = "SELECT DISTINCT estado_id FROM eventos_municipio ORDER BY estado_id ASC";
                                    $resultado = $conn->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while ($estado = $resultado->fetch_assoc()) {
                                            $selected = (isset($_GET['estado']) && $_GET['estado'] === $estado['estado_id']) ? 'selected' : '';
                                            echo '<option value="' . $estado['estado_id'] . '" ' . $selected . '>' . $estado['estado_id'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="cidade" class="filter-label">Cidade</label>
                                <input type="text" id="cidade" name="cidade" class="filter-select" value="<?php echo isset($_GET['cidade']) ? htmlspecialchars($_GET['cidade']) : ''; ?>" placeholder="Digite a cidade">
                            </div>
                            <div class="filter-row button-row">
                                <button type="submit" class="filter-button" id="buscar-btn">Filtrar</button>
                            </div>
                        </form>
                        <div class="filter-categories">
                            <?php
                            $categorias = [
                                'agricolas' => 'Agrícolas/Exposições',
                                'lancamentos' => 'Lançamentos',
                                'cursos' => 'Cursos',
                            ];
                            $active = (!isset($_GET['categoria']) || empty($_GET['categoria'])) ? 'active' : '';
                            $url_base = 'eventos.php?';
                            if (isset($_GET['estado'])) $url_base .= 'estado=' . urlencode($_GET['estado']) . '&';
                            if (isset($_GET['cidade'])) $url_base .= 'cidade=' . urlencode($_GET['cidade']) . '&';
                            echo '<a href="' . $url_base . '" class="category-option ' . $active . '" data-value="">Todos</a>';
                            foreach ($categorias as $slug => $nome) {
                                $active = (isset($_GET['categoria']) && $_GET['categoria'] === $slug) ? 'active' : '';
                                echo '<a href="' . $url_base . 'categoria=' . $slug . '" class="category-option ' . $active . '" data-value="' . $slug . '">' . htmlspecialchars($nome) . '</a>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="results-section">
        <div class="container">
            <h3 class="results-title">Resultados</h3>
            <div class="results-list">
                <?php
                // Montar filtros
                $where = [];
                $params = [];
                $types = '';
                if (isset($_GET['estado']) && $_GET['estado'] !== '') {
                    $where[] = 'estado_id = ?';
                    $params[] = $_GET['estado'];
                    $types .= 'i';
                }
                if (isset($_GET['cidade']) && $_GET['cidade'] !== '') {
                    $where[] = 'municipio_id = ?';
                    $params[] = $_GET['cidade'];
                    $types .= 'i';
                }
                if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                    $where[] = 'categoria = ?';
                    $params[] = $_GET['categoria'];
                    $types .= 's';
                }
                // Apenas eventos ativos e futuros
                $where[] = "status = 'ativo'";
                $where[] = "(data_fim >= CURDATE() OR data_inicio >= CURDATE())";
                $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
                $sql = "SELECT * FROM eventos_municipio $where_sql ORDER BY data_inicio ASC";
                $eventos = [];
                if ($types) {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $res = $stmt->get_result();
                } else {
                    $res = $conn->query($sql);
                }
                if ($res && $res->num_rows > 0) {
                    while ($ev = $res->fetch_assoc()) {
                        echo '<div class="event-card">';
                        echo '<h4>' . htmlspecialchars($ev['nome']) . '</h4>';
                        echo '<div class="event-meta">';
                        echo '<b>Categoria:</b> ' . htmlspecialchars($ev['categoria']) . ' | ';
                        echo '<b>Modalidade:</b> ' . htmlspecialchars($ev['modalidade']) . ' | ';
                        echo '<b>Data:</b> ' . date('d/m/Y', strtotime($ev['data_inicio']));
                        if (!empty($ev['data_fim'])) {
                            echo ' até ' . date('d/m/Y', strtotime($ev['data_fim']));
                        }
                        echo ' | <b>Local:</b> ' . htmlspecialchars($ev['cidade']) . ' - ' . htmlspecialchars($ev['estado']);
                        echo '</div>';
                        if (!empty($ev['descricao'])) {
                            echo '<div class="event-desc">' . nl2br(htmlspecialchars(mb_strimwidth($ev['descricao'], 0, 180, '...'))) . '</div>';
                        }
                        echo '<span class="event-status ' . ($ev['status'] === 'ativo' ? '' : 'inativo') . '">' . ucfirst($ev['status']) . '</span>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align:center; color:#888;">Nenhum evento encontrado para os filtros selecionados.</p>';
                }
                ?>
            </div>
        </div>
    </section>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html> 