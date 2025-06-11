<?php
// Ativar a exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para exibir mensagem de status
function showStatus($step, $status, $message, $detail = '') {
    $color = 'green';
    $icon = '✓';
    
    if ($status == 'warn') {
        $color = 'orange';
        $icon = '⚠';
    } elseif ($status == 'error') {
        $color = 'red';
        $icon = '✗';
    }
    
    echo "
    <div style='margin-bottom: 10px; padding: 15px; border-radius: 5px; background-color: {$color}; color: white;'>
        <div style='font-weight: bold; margin-bottom: 5px;'>
            {$icon} Passo {$step}: {$message}
        </div>
        " . ($detail ? "<div style='margin-top: 5px; font-size: 0.9em;'>{$detail}</div>" : "") . "
    </div>";
}

// Cabeçalho HTML
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico PHP - AgroNeg</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico PHP - AgroNeg</h1>
    <p>Esta página executa testes para verificar a configuração do PHP e acesso ao banco de dados.</p>
";

// Passo 1: Verificar a versão do PHP
$phpVersion = phpversion();
showStatus(1, 'success', 'Versão do PHP verificada', "Versão: {$phpVersion}");

// Passo 2: Verificar configurações do PHP
$display_errors = ini_get('display_errors');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

showStatus(2, 'success', 'Configurações do PHP', "
    Display Errors: {$display_errors}<br>
    Memory Limit: {$memory_limit}<br>
    Max Execution Time: {$max_execution_time}
");

// Passo 3: Verificar extensões necessárias
$required_extensions = ['mysqli', 'pdo_mysql', 'json', 'session'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    showStatus(3, 'success', 'Todas as extensões necessárias estão instaladas');
} else {
    showStatus(3, 'error', 'Extensões ausentes: ' . implode(', ', $missing_extensions));
}

// Passo 4: Verificar conexão com o banco de dados
try {
    // Caminho para o arquivo de configuração
    $config_file = __DIR__ . '/config/db.php';
    
    if (file_exists($config_file)) {
        showStatus(4, 'success', 'Arquivo de configuração encontrado', "Caminho: {$config_file}");
        
        // Incluir arquivo de configuração
        require_once($config_file);
        
        // Verificar se a variável $conn existe
        if (isset($conn)) {
            if ($conn->connect_error) {
                showStatus(5, 'error', 'Falha na conexão com o banco de dados', "Erro: " . $conn->connect_error);
            } else {
                showStatus(5, 'success', 'Conexão com o banco de dados estabelecida com sucesso');
                
                // Verificar tabelas importantes
                $tables = ['categorias', 'parceiros', 'tags', 'estados', 'municipios'];
                $missing_tables = [];
                
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
                    if (!$result || $result->num_rows == 0) {
                        $missing_tables[] = $table;
                    }
                }
                
                if (empty($missing_tables)) {
                    showStatus(6, 'success', 'Todas as tabelas necessárias existem no banco de dados');
                } else {
                    showStatus(6, 'warn', 'Algumas tabelas estão ausentes', "Tabelas faltando: " . implode(', ', $missing_tables));
                }
                
                // Verificar a tabela de categorias
                $result = $conn->query("SELECT * FROM categorias WHERE slug = 'cooperativas'");
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    showStatus(7, 'success', 'Categoria "cooperativas" encontrada', "ID: {$row['id']}, Nome: {$row['nome']}");
                } else {
                    showStatus(7, 'warn', 'Categoria "cooperativas" não encontrada', "Você precisa criar esta categoria para que a página funcione corretamente.");
                }
            }
        } else {
            showStatus(5, 'error', 'Variável de conexão $conn não encontrada no arquivo de configuração');
        }
    } else {
        showStatus(4, 'error', 'Arquivo de configuração não encontrado', "Caminho esperado: {$config_file}");
    }
} catch (Exception $e) {
    showStatus(5, 'error', 'Erro ao conectar ao banco de dados', "Erro: " . $e->getMessage());
}

// Passo Final
echo "
    <div style='margin-top: 20px; padding: 15px; border-radius: 5px; background-color: #f0f0f0;'>
        <h2 style='margin-top: 0;'>Diagnóstico Concluído</h2>
        <p>Se você está vendo esta mensagem, o PHP está funcionando corretamente.</p>
        <p>Hora atual do servidor: " . date('Y-m-d H:i:s') . "</p>
    </div>
";

// Rodapé HTML
echo "
</body>
</html>
";
?> 