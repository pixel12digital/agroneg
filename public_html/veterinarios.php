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
    <title>Veterinários | AgroNeg</title>
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
                        <h2 class="filter-title">Encontre Veterinários</h2>
                        <p class="filter-subtitle">Selecione seu estado, município e categoria para começar</p>
                        <form class="filter-form" method="get" action="veterinarios.php">
                            <div class="filter-row">
                                <label for="estado" class="filter-label">Estado</label>
                                <select id="estado" name="estado" class="filter-select" required>
                                    <option value="">Selecione um estado</option>
                                    <?php
                                    $query = "SELECT DISTINCT e.sigla, e.nome FROM estados e INNER JOIN municipios m ON e.id = m.estado_id ORDER BY e.nome ASC";
                                    $resultado = $conn->query($query);
                                    if ($resultado && $resultado->num_rows > 0) {
                                        while ($estado = $resultado->fetch_assoc()) {
                                            $selected = (isset($_GET['estado']) && $_GET['estado'] === $estado['sigla']) ? 'selected' : '';
                                            echo '<option value="' . $estado['sigla'] . '" ' . $selected . '>' . $estado['nome'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="municipio" class="filter-label">Município</label>
                                <select id="municipio" name="municipio" class="filter-select" required disabled>
                                    <option value="">Selecione um município</option>
                                </select>
                            </div>
                            <div class="filter-row button-row">
                                <button type="submit" class="filter-button" id="buscar-btn">Buscar</button>
                            </div>
                        </form>
                        <div class="filter-categories">
                            <?php
                            // Buscar categorias do tipo Veterinário
                            $categorias = [];
                            $query_categorias = "SELECT id, nome, slug FROM categorias WHERE tipo_id = (SELECT id FROM tipos_parceiros WHERE nome = 'Veterinário' LIMIT 1) ORDER BY nome";
                            $res_categorias = $conn->query($query_categorias);
                            if ($res_categorias && $res_categorias->num_rows > 0) {
                                while ($cat = $res_categorias->fetch_assoc()) {
                                    $categorias[] = $cat;
                                }
                            }
                            // Link "Todos"
                            $active = (!isset($_GET['categoria']) || empty($_GET['categoria'])) ? 'active' : '';
                            $url_base = 'veterinarios.php?';
                            if (isset($_GET['estado'])) $url_base .= 'estado=' . urlencode($_GET['estado']) . '&';
                            if (isset($_GET['municipio'])) $url_base .= 'municipio=' . urlencode($_GET['municipio']) . '&';
                            echo '<a href="' . $url_base . '" class="category-option ' . $active . '" data-value="">Todos</a>';
                            // Exibir categorias
                            foreach ($categorias as $cat) {
                                $active = (isset($_GET['categoria']) && $_GET['categoria'] === $cat['slug']) ? 'active' : '';
                                echo '<a href="' . $url_base . 'categoria=' . $cat['slug'] . '" class="category-option ' . $active . '" data-value="' . $cat['slug'] . '">' . htmlspecialchars($cat['nome']) . '</a>';
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
                    $where[] = 'e.sigla = ?';
                    $params[] = $_GET['estado'];
                    $types .= 's';
                }
                if (isset($_GET['municipio']) && $_GET['municipio'] !== '') {
                    $where[] = 'm.id = ?';
                    $params[] = $_GET['municipio'];
                    $types .= 'i';
                }
                if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                    $where[] = 'c.slug = ?';
                    $params[] = $_GET['categoria'];
                    $types .= 's';
                } else {
                    $where[] = "c.tipo_id = (SELECT id FROM tipos_parceiros WHERE nome = 'Veterinário' LIMIT 1)";
                }
                $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
                $sql = "SELECT p.*, m.nome as municipio, e.sigla as estado FROM parceiros p
                        JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
                        JOIN categorias c ON pc.categoria_id = c.id
                        LEFT JOIN municipios m ON p.municipio_id = m.id
                        LEFT JOIN estados e ON m.estado_id = e.id
                        $where_sql
                        GROUP BY p.id
                        ORDER BY p.nome";
                $veterinarios = [];
                if ($types) {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $res = $stmt->get_result();
                } else {
                    $res = $conn->query($sql);
                }
                if ($res && $res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        echo '<div class="result-card">';
                        echo '<h4>' . htmlspecialchars($row['nome']) . '</h4>';
                        echo '<p><b>Município:</b> ' . htmlspecialchars($row['municipio']) . ' / ' . htmlspecialchars($row['estado']) . '</p>';
                        echo '<p><b>Contato:</b> ' . htmlspecialchars($row['telefone']) . '</p>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>
</div>
<?php include __DIR__.'/partials/footer.php'; ?>
<script src="assets/js/header.js"></script>
<script src="assets/js/filters.js"></script>
<script>
// Carregar municípios dinamicamente
const estadoSelect = document.getElementById('estado');
const municipioSelect = document.getElementById('municipio');
if (estadoSelect && municipioSelect) {
    estadoSelect.addEventListener('change', function() {
        const estadoSigla = this.value;
        municipioSelect.innerHTML = '<option value="">Carregando...</option>';
        municipioSelect.disabled = true;
        if (estadoSigla) {
            fetch('api/municipios.php?estado=' + estadoSigla)
                .then(response => response.json())
                .then(data => {
                    municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                    data.forEach(function(municipio) {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        municipioSelect.appendChild(option);
                    });
                    municipioSelect.disabled = false;
                })
                .catch(() => {
                    municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                    municipioSelect.disabled = true;
                });
        } else {
            municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
            municipioSelect.disabled = true;
        }
    });
    // Se já houver estado selecionado, carregar municípios
    <?php if (isset($_GET['estado']) && $_GET['estado'] !== ''): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const estadoSigla = '<?php echo $_GET['estado']; ?>';
        const municipioId = '<?php echo isset($_GET['municipio']) ? $_GET['municipio'] : ''; ?>';
        if (estadoSigla) {
            fetch('api/municipios.php?estado=' + estadoSigla)
                .then(response => response.json())
                .then(data => {
                    municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                    data.forEach(function(municipio) {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        if (municipio.id == municipioId) option.selected = true;
                        municipioSelect.appendChild(option);
                    });
                    municipioSelect.disabled = false;
                });
        }
    });
    <?php endif; ?>
}
</script>
</body>
</html> 