<?php
require_once __DIR__ . '/../../config/db.php';

// Filtros
$filtros = [
    'estado' => $_GET['estado'] ?? '',
    'cidade' => $_GET['cidade'] ?? '',
    'categoria' => $_GET['categoria'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$where = [];
$params = [];
$types = '';
if ($filtros['estado']) {
    $where[] = 'estado_id = ?';
    $params[] = $filtros['estado'];
    $types .= 'i';
}
if ($filtros['cidade']) {
    $where[] = 'municipio_id = ?';
    $params[] = $filtros['cidade'];
    $types .= 'i';
}
if ($filtros['categoria']) {
    $where[] = 'categoria = ?';
    $params[] = $filtros['categoria'];
    $types .= 's';
}
if ($filtros['status'] !== '') {
    $where[] = 'status = ?';
    $params[] = $filtros['status'];
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT e.*, es.nome as estado_nome, es.sigla as estado_sigla, m.nome as municipio_nome FROM eventos_municipio e LEFT JOIN estados es ON e.estado_id = es.id LEFT JOIN municipios m ON e.municipio_id = m.id $where_sql ORDER BY data_inicio DESC";

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
    while ($row = $res->fetch_assoc()) {
        $eventos[] = $row;
    }
}

// Buscar estados e municípios para os filtros
$estados = $conn->query("SELECT id, nome, sigla FROM estados ORDER BY nome");
$municipios = $conn->query("SELECT id, nome FROM municipios ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Eventos - Admin AgroNeg</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/filters.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .admin-table th, .admin-table td { padding: 10px; border: 1px solid #eee; text-align: left; }
        .admin-table th { background: #f5f5f5; }
        .admin-table tr:nth-child(even) { background: #fafafa; }
        .actions a { margin-right: 8px; }
        .filter-bar { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-bar select, .filter-bar input { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; }
        .add-btn { background: #1A9B60; color: #fff; padding: 8px 18px; border-radius: 5px; text-decoration: none; font-weight: 600; }
        .add-btn:hover { background: #148350; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Painel de Eventos</h1>
    <div class="filter-bar">
        <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <select name="estado">
                <option value="">Estado</option>
                <?php while ($e = $estados->fetch_assoc()) {
                    $sel = ($filtros['estado'] == $e['id']) ? 'selected' : '';
                    echo '<option value="' . $e['id'] . '" ' . $sel . '>' . htmlspecialchars($e['nome']) . ' (' . $e['sigla'] . ')</option>';
                } ?>
            </select>
            <select name="cidade">
                <option value="">Município</option>
                <?php while ($m = $municipios->fetch_assoc()) {
                    $sel = ($filtros['cidade'] == $m['id']) ? 'selected' : '';
                    echo '<option value="' . $m['id'] . '" ' . $sel . '>' . htmlspecialchars($m['nome']) . '</option>';
                } ?>
            </select>
            <select name="categoria">
                <option value="">Categoria</option>
                <option value="agricolas" <?php if($filtros['categoria']==='agricolas') echo 'selected'; ?>>Agrícolas/Exposições</option>
                <option value="lancamentos" <?php if($filtros['categoria']==='lancamentos') echo 'selected'; ?>>Lançamentos</option>
                <option value="cursos" <?php if($filtros['categoria']==='cursos') echo 'selected'; ?>>Cursos</option>
            </select>
            <select name="status">
                <option value="">Status</option>
                <option value="ativo" <?php if($filtros['status']==='ativo') echo 'selected'; ?>>Ativo</option>
                <option value="inativo" <?php if($filtros['status']==='inativo') echo 'selected'; ?>>Inativo</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>
        <a href="../adicionar-evento.php" class="add-btn"><i class="fas fa-plus"></i> Adicionar Evento</a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Modalidade</th>
                <th>Data Início</th>
                <th>Data Fim</th>
                <th>Estado</th>
                <th>Município</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($eventos)): ?>
                <tr><td colspan="9" style="text-align:center;">Nenhum evento encontrado.</td></tr>
            <?php else: foreach ($eventos as $ev): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ev['nome']); ?></td>
                    <td><?php echo htmlspecialchars($ev['categoria']); ?></td>
                    <td><?php echo htmlspecialchars($ev['modalidade']); ?></td>
                    <td><?php echo htmlspecialchars($ev['data_inicio']); ?></td>
                    <td><?php echo htmlspecialchars($ev['data_fim']); ?></td>
                    <td><?php echo htmlspecialchars($ev['estado_nome']); ?></td>
                    <td><?php echo htmlspecialchars($ev['municipio_nome']); ?></td>
                    <td><?php echo htmlspecialchars($ev['status']); ?></td>
                    <td class="actions">
                        <a href="../adicionar-evento.php?id=<?php echo $ev['id']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="eventos.php?delete=<?php echo $ev['id']; ?>" title="Excluir" onclick="return confirm('Deseja excluir este evento?');"><i class="fas fa-trash-alt" style="color:#c00;"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html> 