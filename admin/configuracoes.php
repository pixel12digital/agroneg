<?php
require_once(__DIR__ . '/../config/db.php');

// Obter conexão com banco de dados
$conn = getAgronegConnection();

// Verificar se a conexão foi estabelecida
if (!$conn) {
    die('Erro: Não foi possível conectar ao banco de dados');
}
include 'includes/header.php';

// Função para obter configuração
function get_config($conn, $chave) {
    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
    $stmt->bind_param("s", $chave);
    $stmt->execute();
    $result = $stmt->get_result();
    $valor = '';
    if ($row = $result->fetch_assoc()) {
        $valor = $row['valor'];
    }
    $stmt->close();
    return $valor;
}

// Função para salvar configuração
function set_config($conn, $chave, $valor) {
    $stmt = $conn->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $stmt->bind_param("ss", $chave, $valor);
    $stmt->execute();
    $stmt->close();
}

$success_msg = '';
$error_msg = '';

// Lista de campos
$campos = [
    'nome_sistema' => 'Nome do Sistema',
    'email' => 'E-mail de Contato',
    'telefone' => 'Telefone de Contato',
    'whatsapp' => 'WhatsApp',
    'instagram_url' => 'URL do Instagram',
    'facebook_url' => 'URL do Facebook',
    'tiktok_url' => 'URL do TikTok',
    'idioma' => 'Idioma Padrão',
    'fuso_horario' => 'Fuso Horário'
];

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $chave => $label) {
        $valor = $_POST[$chave] ?? '';
        set_config($conn, $chave, $valor);
    }
    $success_msg = 'Configurações salvas com sucesso!';
}

// Carregar valores atuais
$valores = [];
foreach ($campos as $chave => $label) {
    $valores[$chave] = get_config($conn, $chave);
}
?>
<div class="container mt-5">
    <h1 class="h3 mb-4 text-gray-800">Configurações</h1>
    <?php if ($success_msg): ?>
        <div class="alert alert-success"> <?php echo $success_msg; ?> </div>
    <?php endif; ?>
    <form method="post">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nome_sistema" class="form-label">Nome do Sistema</label>
                <input type="text" class="form-control" id="nome_sistema" name="nome_sistema" value="<?php echo htmlspecialchars($valores['nome_sistema']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">E-mail de Contato</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($valores['email']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="telefone" class="form-label">Telefone de Contato</label>
                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($valores['telefone']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="whatsapp" class="form-label">WhatsApp</label>
                <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($valores['whatsapp']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="instagram_url" class="form-label">URL do Instagram</label>
                <input type="text" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($valores['instagram_url']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="facebook_url" class="form-label">URL do Facebook</label>
                <input type="text" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($valores['facebook_url']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="tiktok_url" class="form-label">URL do TikTok</label>
                <input type="text" class="form-control" id="tiktok_url" name="tiktok_url" value="<?php echo htmlspecialchars($valores['tiktok_url']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="idioma" class="form-label">Idioma Padrão</label>
                <input type="text" class="form-control" id="idioma" name="idioma" value="<?php echo htmlspecialchars($valores['idioma']); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="fuso_horario" class="form-label">Fuso Horário</label>
                <input type="text" class="form-control" id="fuso_horario" name="fuso_horario" value="<?php echo htmlspecialchars($valores['fuso_horario']); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?> 