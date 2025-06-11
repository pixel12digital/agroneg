<?php
// Configurações do MySQL
$servername = "localhost";
$username = "root";
$password = "";

// Criar conexão sem selecionar banco
$conn = new mysqli($servername, $username, $password);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

echo "=== Importando Banco de Dados Agroneg ===\n\n";

// 1. Criar banco de dados se não existir
echo "1. Criando banco de dados...\n";
$sql = "CREATE DATABASE IF NOT EXISTS agroneg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "✅ Banco de dados criado ou já existente\n";
} else {
    echo "❌ Erro ao criar banco de dados: " . $conn->error . "\n";
    exit;
}

// 2. Selecionar o banco
$conn->select_db("agroneg");

// 3. Importar arquivos SQL na ordem correta
$arquivos_sql = [
    'database.sql',
    'inserir_dados_iniciais.sql',
    'dados_iniciais_tags.sql',
    'atualizar_tags_descricoes.sql',
    'adicionar_whatsapp.sql',
    'adicionar_imagem_destaque.sql',
    'atualizar_tipos_parceiros.sql'
];

echo "\n2. Importando arquivos SQL...\n";
foreach ($arquivos_sql as $arquivo) {
    $caminho = "sql/" . $arquivo;
    if (file_exists($caminho)) {
        echo "Importando $arquivo...\n";
        $sql = file_get_contents($caminho);
        
        // Dividir o arquivo em comandos individuais
        $comandos = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($comandos as $comando) {
            if (!empty($comando)) {
                if ($conn->query($comando) === TRUE) {
                    echo "  ✅ Comando executado\n";
                } else {
                    echo "  ⚠️ Erro no comando: " . $conn->error . "\n";
                    // Continuar mesmo com erro, pois alguns comandos podem falhar se já existirem
                }
            }
        }
    } else {
        echo "⚠️ Arquivo $arquivo não encontrado\n";
    }
}

echo "\n3. Verificando tabelas criadas...\n";
$tabelas = [
    'usuarios',
    'estados',
    'municipios',
    'categorias',
    'parceiros',
    'fotos',
    'tags',
    'parceiros_tags',
    'eventos_municipio',
    'avaliacoes',
    'configuracoes'
];

foreach ($tabelas as $tabela) {
    $result = $conn->query("SHOW TABLES LIKE '$tabela'");
    if ($result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as total FROM $tabela")->fetch_assoc()['total'];
        echo "✅ Tabela '$tabela' criada com $count registros\n";
    } else {
        echo "❌ Tabela '$tabela' não foi criada\n";
    }
}

$conn->close();
echo "\n✅ Importação concluída!\n";
?> 