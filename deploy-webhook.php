<?php
// Deploy Webhook para GitHub
// Configurar no GitHub: Settings → Webhooks → Add webhook

// Verificar se é um POST válido do GitHub
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Verificar o payload (opcional - para segurança)
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Verificar se é um push no branch main
if (!isset($data['ref']) || $data['ref'] !== 'refs/heads/main') {
    die('Not a push to main branch');
}

// Executar comandos de deploy
$output = [];
$return_var = 0;

// Comandos para fazer deploy
$commands = [
    'cd /home/u664918047/domains/seudominio.com/public_html',
    'git pull origin main',
    'chmod -R 755 .',
    'chmod -R 777 uploads/',
    'chmod -R 777 assets/'
];

foreach ($commands as $command) {
    exec($command . ' 2>&1', $output, $return_var);
    if ($return_var !== 0) {
        error_log("Deploy error: " . implode("\n", $output));
        http_response_code(500);
        die('Deploy failed');
    }
}

// Log do sucesso
error_log("Deploy successful: " . implode("\n", $output));
echo "Deploy completed successfully!";
?>
