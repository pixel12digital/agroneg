<?php
require_once __DIR__ . '/../config/db.php';
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $evento ? 'Editar Evento' : 'Adicionar Evento'; ?> - AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .add-event-section { padding: 60px 0; background-color: #f8f9fa; }
        .form-container { max-width: 800px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); }
        .form-title { color: #333; margin-bottom: 30px; text-align: center; }
        .form-subtitle { color: #1A9B60; font-size: 18px; margin-bottom: 5px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; transition: border-color 0.3s; }
        .form-control:focus { border-color: #1A9B60; outline: none; }
        .form-select { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; transition: border-color 0.3s; background-color: #fff; }
        .form-textarea { min-height: 120px; resize: vertical; }
        .form-button { background-color: #1A9B60; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; margin-top: 10px; width: 100%; }
        .form-button:hover { background-color: #148350; }
        .form-row { display: flex; gap: 20px; }
        .form-col { flex: 1; }
        @media (max-width: 768px) { .form-row { flex-direction: column; gap: 0; } .form-container { padding: 20px; } }
    </style>
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    <div class="main-content">
        <section class="add-event-section">
            <div class="container">
                <div class="form-container">
                    <h4 class="form-subtitle">COMPARTILHE COM A COMUNIDADE</h4>
                    <h2 class="form-title"><?php echo $evento ? 'Editar Evento' : 'Adicionar Novo Evento'; ?></h2>
                    <form action="process-event.php" method="post" enctype="multipart/form-data">
                        <?php if ($evento): ?><input type="hidden" name="id" value="<?php echo $evento['id']; ?>"><?php endif; ?>
                        <div class="form-group">
                            <label for="titulo" class="form-label">Título do Evento*</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($evento['nome'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="categoria" class="form-label">Categoria*</label>
                                    <select id="categoria" name="categoria" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option value="agricolas" <?php if(($evento['categoria'] ?? '')==='agricolas') echo 'selected'; ?>>Eventos Agrícolas e Exposições</option>
                                        <option value="lancamentos" <?php if(($evento['categoria'] ?? '')==='lancamentos') echo 'selected'; ?>>Lançamentos e Novidades do Setor</option>
                                        <option value="cursos" <?php if(($evento['categoria'] ?? '')==='cursos') echo 'selected'; ?>>Cursos e Treinamentos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="modalidade" class="form-label">Modalidade*</label>
                                    <select id="modalidade" name="modalidade" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option value="presencial" <?php if(($evento['modalidade'] ?? '')==='presencial') echo 'selected'; ?>>Presencial</option>
                                        <option value="online" <?php if(($evento['modalidade'] ?? '')==='online') echo 'selected'; ?>>Online</option>
                                        <option value="hibrido" <?php if(($evento['modalidade'] ?? '')==='hibrido') echo 'selected'; ?>>Híbrido</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="data_inicio" class="form-label">Data de Início*</label>
                                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" required value="<?php echo htmlspecialchars($evento['data_inicio'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="data_fim" class="form-label">Data de Término</label>
                                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($evento['data_fim'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="estado_id" class="form-label">Estado*</label>
                                    <select id="estado_id" name="estado_id" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($estados as $est): ?>
                                            <option value="<?php echo $est['id']; ?>" <?php if(($evento['estado_id'] ?? '')==$est['id']) echo 'selected'; ?>><?php echo htmlspecialchars($est['nome']); ?> (<?php echo $est['sigla']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="municipio_id" class="form-label">Município*</label>
                                    <select id="municipio_id" name="municipio_id" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($municipios as $mun): ?>
                                            <option value="<?php echo $mun['id']; ?>" <?php if(($evento['municipio_id'] ?? '')==$mun['id']) echo 'selected'; ?>><?php echo htmlspecialchars($mun['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control form-textarea"><?php echo htmlspecialchars($evento['descricao'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="status" class="form-label">Status*</label>
                                    <select id="status" name="status" class="form-select" required>
                                        <option value="ativo" <?php if(($evento['status'] ?? 'ativo')==='ativo') echo 'selected'; ?>>Ativo</option>
                                        <option value="inativo" <?php if(($evento['status'] ?? '')==='inativo') echo 'selected'; ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="imagem" class="form-label">Imagem/Banner</label>
                                    <input type="file" id="imagem" name="imagem" class="form-control">
                                    <?php if (!empty($evento['imagem'])): ?>
                                        <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagem']); ?>" alt="Imagem do evento" style="max-width:120px; margin-top:8px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="form-button"><?php echo $evento ? 'Salvar Alterações' : 'Adicionar Evento'; ?></button>
                    </form>
                </div>
            </div>
        </section>
    </div>
    <script>
    // Carregar municípios dinamicamente
    document.getElementById('estado_id').addEventListener('change', function() {
        var estadoId = this.value;
        var municipioSelect = document.getElementById('municipio_id');
        municipioSelect.innerHTML = '<option value="">Carregando...</option>';
        municipioSelect.disabled = true;
        if (estadoId) {
            fetch('api/municipios.php?estado_id=' + estadoId)
                .then(response => response.json())
                .then(data => {
                    municipioSelect.innerHTML = '<option value="">Selecione</option>';
                    data.forEach(function(mun) {
                        var opt = document.createElement('option');
                        opt.value = mun.id;
                        opt.textContent = mun.nome;
                        municipioSelect.appendChild(opt);
                    });
                    municipioSelect.disabled = false;
                })
                .catch(() => {
                    municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                    municipioSelect.disabled = true;
                });
        } else {
            municipioSelect.innerHTML = '<option value="">Selecione</option>';
            municipioSelect.disabled = true;
        }
    });
    </script>
    <?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html> 