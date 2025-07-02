<?php
require_once(__DIR__ . '/../config/db.php');

// Filtros
$filtros = [
    'estado_id' => $_GET['estado_id'] ?? '',
    'municipio_id' => $_GET['municipio_id'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$where = [];
$params = [];
$types = '';
if (!empty($filtros['estado_id'])) {
    $where[] = 'e.estado_id = ?';
    $params[] = $filtros['estado_id'];
    $types .= 'i';
}
if (!empty($filtros['municipio_id'])) {
    $where[] = 'e.municipio_id = ?';
    $params[] = $filtros['municipio_id'];
    $types .= 'i';
}
if (!empty($filtros['categoria'])) {
    $where[] = 'e.categoria = ?';
    $params[] = $filtros['categoria'];
    $types .= 's';
}
if ($filtros['status'] !== '') {
    $where[] = 'e.status = ?';
    $params[] = $filtros['status'];
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT e.*, es.nome as estado_nome, es.sigla as estado_sigla, m.nome as municipio_nome 
        FROM eventos_municipio e 
        LEFT JOIN estados es ON e.estado_id = es.id 
        LEFT JOIN municipios m ON e.municipio_id = m.id 
        $where_sql 
        ORDER BY e.data_inicio DESC";

$eventos = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        // Usando call_user_func_array para máxima compatibilidade com diferentes versões do PHP
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $eventos[] = $row;
        }
    }
} else {
    // Se a preparação falhar, registre um erro.
    error_log("Erro ao preparar a consulta em admin/eventos.php: " . $conn->error);
    $eventos = [];
}

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $eventos[] = $row;
    }
}

// Buscar apenas estados que possuem municípios cadastrados, para o filtro
$estados = $conn->query("SELECT DISTINCT e.id, e.nome, e.sigla 
                         FROM estados e
                         INNER JOIN municipios m ON e.id = m.estado_id
                         ORDER BY e.nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Eventos - Admin AgroNeg</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="includes/admin-styles.css">
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <div class="admin-header">
        <h1>Painel de Eventos</h1>
        <a href="../adicionar-evento.php" class="add-btn"><i class="fas fa-plus"></i> Adicionar Evento</a>
    </div>

    <?php if (isset($_GET['acao']) && $_GET['acao'] === 'sucesso' && isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>

    <div class="filter-bar">
        <form id="filter-form" method="get" class="filter-form">
            <div class="filter-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado_id">
                    <option value="">Todos os estados</option>
                    <?php 
                    if ($estados && $estados->num_rows > 0) {
                        while ($e = $estados->fetch_assoc()) {
                            $sel = ($filtros['estado_id'] == $e['id']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($e['id']) . '" ' . $sel . '>' . htmlspecialchars($e['nome']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="municipio">Município</label>
                <select id="municipio" name="municipio_id" <?php echo empty($filtros['estado_id']) ? 'disabled' : ''; ?>>
                    <option value="">Selecione um estado primeiro</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    <option value="agricolas" <?php if($filtros['categoria']==='agricolas') echo 'selected'; ?>>Agrícolas/Exposições</option>
                    <option value="lancamentos" <?php if($filtros['categoria']==='lancamentos') echo 'selected'; ?>>Lançamentos</option>
                    <option value="cursos" <?php if($filtros['categoria']==='cursos') echo 'selected'; ?>>Cursos</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?php if($filtros['status']==='ativo') echo 'selected'; ?>>Ativo</option>
                    <option value="inativo" <?php if($filtros['status']==='inativo') echo 'selected'; ?>>Inativo</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="filter-btn">Filtrar</button>
            </div>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Modalidade</th>
                <th>Data Início</th>
                <th>Data Fim</th>
                <th>Local</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($eventos)): ?>
                <tr><td colspan="8" style="text-align:center;">Nenhum evento encontrado para os filtros selecionados.</td></tr>
            <?php else: foreach ($eventos as $ev): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ev['nome']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($ev['categoria'])); ?></td>
                    <td><?php echo htmlspecialchars($ev['modalidade']); ?></td>
                    <td><?php echo date("d/m/Y", strtotime($ev['data_inicio'])); ?></td>
                    <td><?php echo $ev['data_fim'] ? date("d/m/Y", strtotime($ev['data_fim'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($ev['municipio_nome']) . ' - ' . htmlspecialchars($ev['estado_sigla']); ?></td>
                    <td><span class="status-<?php echo htmlspecialchars($ev['status']); ?>"><?php echo ucfirst(htmlspecialchars($ev['status'])); ?></span></td>
                    <td class="actions">
                        <a href="../adicionar-evento.php?id=<?php echo $ev['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="eventos.php?delete=<?php echo $ev['id']; ?>" title="Excluir" onclick="return confirm('Deseja realmente excluir este evento?');"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estado');
    const municipioSelect = document.getElementById('municipio');
    const estadoIdAtual = '<?php echo $filtros['estado_id']; ?>';
    const municipioIdAtual = '<?php echo $filtros['municipio_id']; ?>';

    function carregarMunicipios(estadoId, municipioSelecionadoId) {
        if (!estadoId) {
            municipioSelect.innerHTML = '<option value="">Selecione um estado</option>';
            municipioSelect.disabled = true;
            return;
        }

        // Exibe um estado de carregamento
        municipioSelect.innerHTML = '<option value="">Carregando...</option>';
        municipioSelect.disabled = false;

        fetch(`../api/get_municipios.php?estado_id=${estadoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('A resposta da rede não foi boa');
                }
                return response.json();
            })
            .then(data => {
                municipioSelect.innerHTML = '<option value="">Todos os municípios</option>'; // Opção padrão
                if (data.length > 0) {
                    data.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        if (municipio.id == municipioSelecionadoId) {
                            option.selected = true;
                        }
                        municipioSelect.appendChild(option);
                    });
                } else {
                     municipioSelect.innerHTML = '<option value="">Nenhum município encontrado</option>';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar municípios:', error);
                municipioSelect.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    }

    // Ao carregar a página, se um estado já estiver selecionado do filtro anterior, carregue seus municípios
    if (estadoIdAtual) {
        carregarMunicipios(estadoIdAtual, municipioIdAtual);
    }

    // Evento para quando o usuário muda o estado
    estadoSelect.addEventListener('change', function() {
        carregarMunicipios(this.value, null); // Não pré-seleciona nenhum município ao mudar o estado
    });
});
</script>
</body>
</html> 