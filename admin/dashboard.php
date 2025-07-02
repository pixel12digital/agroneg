<?php
// Página principal do dashboard
require_once(__DIR__ . '/../config/db.php');
include 'includes/header.php';

// Obter estatísticas
$stats = [
    'parceiros' => 0,
    'municipios' => 0,
    'categorias' => 0,
    'usuarios' => 0
];

// Total de parceiros
$query = "SELECT COUNT(*) as total FROM parceiros";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['parceiros'] = $row['total'];
}

// Total de municípios
$query = "SELECT COUNT(*) as total FROM municipios";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['municipios'] = $row['total'];
}

// Total de categorias
$query = "SELECT COUNT(*) as total FROM categorias";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['categorias'] = $row['total'];
}

// Total de usuários
$query = "SELECT COUNT(*) as total FROM usuarios";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['usuarios'] = $row['total'];
}

// Parceiros por categoria
$parceiros_por_categoria = [];
$query = "SELECT c.nome as categoria, COUNT(pc.parceiro_id) as total 
          FROM categorias c
          LEFT JOIN parceiros_categorias pc ON c.id = pc.categoria_id
          GROUP BY c.id ORDER BY total DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parceiros_por_categoria[] = $row;
    }
}

// Últimos parceiros cadastrados
$ultimos_parceiros = [];
$query = "SELECT p.*, m.nome as municipio, e.sigla as estado 
          FROM parceiros p
          LEFT JOIN municipios m ON p.municipio_id = m.id
          LEFT JOIN estados e ON m.estado_id = e.id
          ORDER BY p.created_at DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Buscar categorias relacionadas
        $catQuery = "SELECT c.nome FROM parceiros_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.parceiro_id = ?";
        $stmtCat = $conn->prepare($catQuery);
        $stmtCat->bind_param("i", $row['id']);
        $stmtCat->execute();
        $catResult = $stmtCat->get_result();
        $cats = [];
        while ($catRow = $catResult->fetch_assoc()) {
            $cats[] = $catRow['nome'];
        }
        $stmtCat->close();
        $row['categoria'] = implode(', ', $cats);
        $ultimos_parceiros[] = $row;
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">Painel de Controle</h1>
    <div>
        <a href="parceiros.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Adicionar Parceiro
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Parceiros</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $stats['parceiros']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-handshake fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-2">
                <a href="parceiros.php" class="text-decoration-none">
                    <span class="small text-primary">Ver detalhes</span>
                    <i class="fas fa-arrow-right ms-1 small"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Municípios</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $stats['municipios']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-2">
                <a href="municipios.php" class="text-decoration-none">
                    <span class="small text-success">Ver detalhes</span>
                    <i class="fas fa-arrow-right ms-1 small"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Categorias</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $stats['categorias']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tags fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-2">
                <a href="categorias.php" class="text-decoration-none">
                    <span class="small text-info">Ver detalhes</span>
                    <i class="fas fa-arrow-right ms-1 small"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col me-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Usuários</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $stats['usuarios']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-2">
                <a href="usuarios.php" class="text-decoration-none">
                    <span class="small text-warning">Ver detalhes</span>
                    <i class="fas fa-arrow-right ms-1 small"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Widgets -->
<div class="row">
    <!-- Últimos parceiros -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Últimos Parceiros Cadastrados</h6>
                <a href="parceiros.php" class="text-decoration-none">Ver Todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Localização</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimos_parceiros)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum parceiro cadastrado ainda.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimos_parceiros as $parceiro): ?>
                                    <tr>
                                        <td><?php echo $parceiro['nome']; ?></td>
                                        <td><?php echo $parceiro['categoria']; ?></td>
                                        <td><?php echo $parceiro['municipio'] . '/' . $parceiro['estado']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($parceiro['created_at'])); ?></td>
                                        <td>
                                            <a href="parceiros.php?action=edit&id=<?php echo $parceiro['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../parceiro.php?id=<?php echo $parceiro['id']; ?>" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas por categoria -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Parceiros por Categoria</h6>
            </div>
            <div class="card-body">
                <?php if (empty($parceiros_por_categoria)): ?>
                    <p class="text-center">Nenhum dado disponível.</p>
                <?php else: ?>
                    <?php foreach ($parceiros_por_categoria as $item): ?>
                        <h6 class="fw-bold"><?php echo $item['categoria']; ?></h6>
                        <div class="progress mb-4" style="height: 20px;">
                            <?php 
                                $porcentagem = ($stats['parceiros'] > 0) ? ($item['total'] / $stats['parceiros']) * 100 : 0;
                                $porcentagem = round($porcentagem);
                            ?>
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $porcentagem; ?>%;" 
                                 aria-valuenow="<?php echo $porcentagem; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $item['total']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Ações Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="parceiros.php?action=add" class="btn btn-primary btn-block w-100">
                            <i class="fas fa-plus-circle me-1"></i> Novo Parceiro
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="municipios.php?action=add" class="btn btn-success btn-block w-100">
                            <i class="fas fa-plus-circle me-1"></i> Novo Município
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="categorias.php" class="btn btn-info btn-block w-100">
                            <i class="fas fa-tags me-1"></i> Categorias
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="mensagens.php" class="btn btn-warning btn-block w-100">
                            <i class="fas fa-envelope me-1"></i> Mensagens
                            <?php
                            // Contar mensagens não lidas
                            $query = "SELECT COUNT(*) as total FROM mensagens_contato WHERE status = 'novo'";
                            $result = $conn->query($query);
                            if ($result) {
                                $row = $result->fetch_assoc();
                                if ($row && $row['total'] > 0) {
                                    echo '<span class="badge bg-danger ms-1">' . $row['total'] . '</span>';
                                }
                            }
                            ?>
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="configuracoes.php" class="btn btn-secondary btn-block w-100">
                            <i class="fas fa-cogs me-1"></i> Configurações
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 