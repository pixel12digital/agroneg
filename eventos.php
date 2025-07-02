<?php
ini_set('display_errors', 1); // Ativar exibição de erros temporariamente
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1); // Ativar log de erros em produção
error_reporting(E_ALL);
require_once("config/db.php");

// --- Lógica de Filtros ---
$filtros = [
    'estado_id' => isset($_GET['estado_id']) ? filter_var($_GET['estado_id'], FILTER_VALIDATE_INT) : null,
    'municipio_id' => isset($_GET['municipio_id']) ? filter_var($_GET['municipio_id'], FILTER_VALIDATE_INT) : null,
    'categoria' => isset($_GET['categoria']) ? htmlspecialchars($_GET['categoria']) : null,
];
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
        .event-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 24px; margin-bottom: 24px; }
        .event-card h4 { margin: 0 0 10px 0; color: #1A9B60; }
        .event-card .event-meta { font-size: 15px; color: #666; margin-bottom: 12px; line-height: 1.6; }
        .event-card .event-desc { color: #444; margin-bottom: 12px; }
        .event-card .event-status { font-size: 13px; font-weight: bold; color: #fff; background: #1A9B60; border-radius: 4px; padding: 2px 10px; display: inline-block; }
        .event-card .event-status.inativo { background: #aaa; }
        .results-section { padding: 40px 0; }
        .results-title { text-align: center; margin-bottom: 30px; }
        .filter-categories a.category-option.active { background-color: #1A9B60; color: white; }
    </style>
</head>
<body>
<?php include __DIR__.'/partials/header.php'; ?>
<div class="main-content">
    <section class="banner-section">
        <div class="container">
            <div class="banner-wrapper">
                <img src="assets/images/agroneg-campo.jpg" alt="Banner AgroNeg" class="banner-img">
                <div class="filter-container">
                    <div class="filter-content">
                        <h2 class="filter-title">Encontre Eventos</h2>
                        <p class="filter-subtitle">Selecione seu estado, município e categoria para começar</p>
                        <form class="filter-form" method="get" action="eventos.php">
                            <div class="filter-row">
                                <label for="estado_id" class="filter-label">Estado</label>
                                <select id="estado_id" name="estado_id" class="filter-select">
                                    <option value="">Todos os estados</option>
                                    <?php
                                    $q_estados = "SELECT DISTINCT e.id, e.nome FROM estados e INNER JOIN eventos_municipio ev ON e.id = ev.estado_id ORDER BY e.nome ASC";
                                    $res_estados = $conn->query($q_estados);
                                    if ($res_estados) {
                                        while ($estado = $res_estados->fetch_assoc()) {
                                            $selected = ($filtros['estado_id'] == $estado['id']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($estado['id']) . '" ' . $selected . '>' . htmlspecialchars($estado['nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="municipio_id" class="filter-label">Município</label>
                                <select id="municipio_id" name="municipio_id" class="filter-select" <?php echo !$filtros['estado_id'] ? 'disabled' : ''; ?>>
                                    <option value="">Selecione um estado</option>
                                </select>
                            </div>
                            <div class="filter-row button-row">
                                <button type="submit" class="filter-button" id="buscar-btn">Filtrar</button>
                            </div>
                        </form>
                        <div class="filter-categories">
                            <?php
                            $categorias = [ 'agricolas' => 'Agrícolas/Exposições', 'lancamentos' => 'Lançamentos', 'cursos' => 'Cursos' ];
                            $url_params = http_build_query(array_filter(['estado_id' => $filtros['estado_id'], 'municipio_id' => $filtros['municipio_id']]));
                            $active_class = empty($filtros['categoria']) ? 'active' : '';
                            echo '<a href="eventos.php?' . $url_params . '" class="category-option ' . $active_class . '">Todos</a>';

                            foreach ($categorias as $slug => $nome) {
                                $active_class = ($filtros['categoria'] === $slug) ? 'active' : '';
                                $cat_params = http_build_query(array_filter(['estado_id' => $filtros['estado_id'], 'municipio_id' => $filtros['municipio_id'], 'categoria' => $slug]));
                                echo '<a href="eventos.php?' . $cat_params . '" class="category-option ' . $active_class . '">' . htmlspecialchars($nome) . '</a>';
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
            <h3 class="results-title">Resultados da Busca</h3>
            <div class="results-list">
                <?php
                $where = [];
                $params = [];
                $types = '';

                if ($filtros['estado_id']) { $where[] = 'ev.estado_id = ?'; $params[] = $filtros['estado_id']; $types .= 'i'; }
                if ($filtros['municipio_id']) { $where[] = 'ev.municipio_id = ?'; $params[] = $filtros['municipio_id']; $types .= 'i'; }
                if ($filtros['categoria']) { $where[] = 'ev.categoria = ?'; $params[] = $filtros['categoria']; $types .= 's'; }
                
                $where_sql = 'WHERE ' . implode(' AND ', $where);
                
                $sql = "SELECT ev.*, m.nome as municipio_nome, es.sigla as estado_sigla 
                        FROM eventos_municipio ev
                        JOIN municipios m ON ev.municipio_id = m.id
                        JOIN estados es ON ev.estado_id = es.id
                        $where_sql 
                        ORDER BY ev.data_inicio ASC";
                
                $eventos = [];
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    if ($types) { $stmt->bind_param($types, ...$params); }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        while ($ev = $res->fetch_assoc()) {
                            echo '<div class="event-card">';
                            echo '<h4>' . htmlspecialchars($ev['nome']) . '</h4>';
                            echo '<div class="event-meta">';
                            echo '<b>Categoria:</b> ' . htmlspecialchars(ucfirst($ev['categoria'])) . '<br>';
                            echo '<b>Modalidade:</b> ' . htmlspecialchars(ucfirst($ev['modalidade'])) . '<br>';
                            echo '<b>Data:</b> ' . date('d/m/Y', strtotime($ev['data_inicio']));
                            if (!empty($ev['data_fim'])) { echo ' até ' . date('d/m/Y', strtotime($ev['data_fim'])); }
                            echo '<br><b>Local:</b> ' . htmlspecialchars($ev['municipio_nome']) . ' - ' . htmlspecialchars($ev['estado_sigla']);
                            echo '</div>';
                            if (!empty($ev['descricao'])) { echo '<div class="event-desc">' . nl2br(htmlspecialchars($ev['descricao'])) . '</div>'; }
                            echo '</div>';
                        }
                        if ($res->num_rows === 0) {
                             echo '<p style="text-align:center; color:#888;">Nenhum evento encontrado para os filtros selecionados.</p>';
                        }
                    } else {
                        echo '<p style="text-align:center; color:red;">Erro ao executar a busca.</p>';
                    }
                } else {
                    echo '<p style="text-align:center; color:red;">Erro na preparação da consulta.</p>';
                }
                ?>
            </div>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estado_id');
    const municipioSelect = document.getElementById('municipio_id');
    const estadoIdAtual = '<?php echo $filtros['estado_id'] ?? ''; ?>';
    const municipioIdAtual = '<?php echo $filtros['municipio_id'] ?? ''; ?>';

    function carregarMunicipios(estadoId, municipioSelecionadoId) {
        if (!estadoId) {
            municipioSelect.innerHTML = '<option value="">Selecione um estado</option>';
            municipioSelect.disabled = true;
            return;
        }
        municipioSelect.disabled = false;
        municipioSelect.innerHTML = '<option value="">Carregando...</option>';

        fetch(`api/get_municipios.php?estado_id=${estadoId}`)
            .then(response => response.json())
            .then(data => {
                municipioSelect.innerHTML = '<option value="">Todos os municípios</option>';
                data.forEach(function(municipio) {
                    const option = document.createElement('option');
                    option.value = municipio.id;
                    option.textContent = municipio.nome;
                    if (municipio.id == municipioSelecionadoId) {
                        option.selected = true;
                    }
                    municipioSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erro:', error);
                municipioSelect.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    }
    if (estadoIdAtual) {
        carregarMunicipios(estadoIdAtual, municipioIdAtual);
    }
    estadoSelect.addEventListener('change', function() {
        carregarMunicipios(this.value, null);
    });
});
</script>
<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html> 