<?php
// Página de edição de eventos no admin
require_once("includes/header.php");

// Se for edição, buscar dados do evento
$evento = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM eventos_municipio WHERE id = $id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $evento = $res->fetch_assoc();
    }
}

// Buscar estados do banco
$estados = [];
$qest = $conn->query("SELECT id, nome, sigla FROM estados ORDER BY nome");
while ($row = $qest->fetch_assoc()) {
    $estados[] = $row;
}

// Buscar municípios do estado selecionado (se edição)
$municipios = [];
$estado_id = $evento['estado_id'] ?? null;
if ($estado_id) {
    $qmun = $conn->prepare("SELECT id, nome FROM municipios WHERE estado_id = ? ORDER BY nome");
    $qmun->bind_param("i", $estado_id);
    $qmun->execute();
    $resmun = $qmun->get_result();
    while ($row = $resmun->fetch_assoc()) {
        $municipios[] = $row;
    }
    $qmun->close();
}

// Buscar categorias (usar categorias fixas para eventos)
$categorias = [
    ['id' => 'agricolas', 'nome' => 'Eventos Agrícolas e Exposições'],
    ['id' => 'pecuarios', 'nome' => 'Eventos Pecuários'],
    ['id' => 'tecnologicos', 'nome' => 'Eventos Tecnológicos'],
    ['id' => 'educativos', 'nome' => 'Eventos Educativos'],
    ['id' => 'comerciais', 'nome' => 'Eventos Comerciais'],
    ['id' => 'outros', 'nome' => 'Outros']
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-calendar-alt me-2"></i>
        <?php echo $evento ? 'Editar Evento' : 'Adicionar Evento'; ?>
    </h1>
    <a href="eventos.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Evento
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="processar-evento.php" enctype="multipart/form-data">
                    <?php if ($evento): ?>
                        <input type="hidden" name="id" value="<?php echo $evento['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="titulo" class="form-label">Título do Evento *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($evento['nome'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="categoria" class="form-label">Categoria *</label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($evento['categoria'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalidade" class="form-label">Modalidade *</label>
                            <select class="form-select" id="modalidade" name="modalidade" required>
                                <option value="">Selecione a modalidade</option>
                                <option value="presencial" <?php echo ($evento['modalidade'] ?? '') == 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                                <option value="online" <?php echo ($evento['modalidade'] ?? '') == 'online' ? 'selected' : ''; ?>>Online</option>
                                <option value="hibrido" <?php echo ($evento['modalidade'] ?? '') == 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1" <?php echo ($evento['status'] ?? '1') == '1' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="0" <?php echo ($evento['status'] ?? '1') == '0' ? 'selected' : ''; ?>>Inativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_inicio" class="form-label">Data de Início *</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                   value="<?php echo $evento['data_inicio'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="data_fim" class="form-label">Data de Término</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                   value="<?php echo $evento['data_fim'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">Estado *</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="">Selecione um estado</option>
                                <?php foreach ($estados as $est): ?>
                                    <option value="<?php echo $est['id']; ?>" 
                                            <?php echo ($evento['estado_id'] ?? '') == $est['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($est['nome'] . ' (' . $est['sigla'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="municipio" class="form-label">Município *</label>
                            <select class="form-select" id="municipio" name="municipio" required>
                                <option value="">Selecione um município</option>
                                <?php foreach ($municipios as $mun): ?>
                                    <option value="<?php echo $mun['id']; ?>" 
                                            <?php echo ($evento['municipio_id'] ?? '') == $mun['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mun['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($evento['descricao'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="local" class="form-label">Local</label>
                        <input type="text" class="form-control" id="local" name="local" 
                               value="<?php echo htmlspecialchars($evento['local'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Imagem do Evento</label>
                        <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                        <?php if ($evento && !empty($evento['imagem'])): ?>
                            <small class="form-text text-muted">
                                Imagem atual: <?php echo htmlspecialchars($evento['imagem']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="eventos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $evento ? 'Atualizar Evento' : 'Criar Evento'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-lightbulb me-2"></i>
                    Preencha todos os campos obrigatórios marcados com asterisco (*).
                </p>
                <p class="text-muted">
                    <i class="fas fa-calendar me-2"></i>
                    A data de término é opcional. Se não informada, o evento será considerado de um dia apenas.
                </p>
                <p class="text-muted">
                    <i class="fas fa-image me-2"></i>
                    A imagem será redimensionada automaticamente para otimizar o carregamento.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Carregar municípios quando estado for selecionado
document.getElementById('estado').addEventListener('change', function() {
    const estadoId = this.value;
    const municipioSelect = document.getElementById('municipio');
    
    if (estadoId) {
        fetch(`../api/get_municipios.php?estado=${estadoId}`)
            .then(response => response.json())
            .then(data => {
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                data.municipios.forEach(municipio => {
                    const option = document.createElement('option');
                    option.value = municipio.id;
                    option.textContent = municipio.nome;
                    municipioSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erro ao carregar municípios:', error);
            });
    } else {
        municipioSelect.innerHTML = '<option value="">Selecione um estado primeiro</option>';
    }
});
</script>

<?php require_once("includes/footer.php"); ?>
