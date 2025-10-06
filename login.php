<?php
session_name('agroneg_admin');
session_set_cookie_params(['path' => '/']);
session_start();
require_once("config/db.php");

$erro = '';
$mensagem = '';

// Verifica se é um logout
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $mensagem = "Você foi desconectado com sucesso.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"] ?? '';
    $senha = $_POST["senha"] ?? '';

    // Obter conexão com banco de dados
    $conn = getAgronegConnection();
    
    // Consulta ao banco de dados
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verifica senha (texto simples)
        if ($senha === $user['senha']) {
            $_SESSION["logado"] = true;
            $_SESSION["nome_usuario"] = $user['nome'];
            $_SESSION["user_id"] = $user['id'];
            $_SESSION["nivel"] = $user['nivel'];
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Painel Administrativo AgroNeg - Login">
    <meta name="theme-color" content="#006837">
    <title>Login - AgroNeg</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #006837;
            --primary-dark: #004d27;
            --secondary: #F7941D;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: var(--primary);
        }
        
        .login-logo h2 {
            margin-top: 0.5rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            width: 100%;
            padding: 0.7rem;
            font-size: 1.1rem;
            min-height: 48px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(247, 148, 29, 0.25);
        }
        
        .form-control, .input-group-text {
            padding: 0.7rem 0.75rem;
            min-height: 48px;
        }
        
        .input-group-text {
            background-color: var(--light-bg);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            display: inline-block;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        /* Para dispositivos pequenos */
        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .login-logo i {
                font-size: 2.5rem;
            }
            
            .login-logo h2 {
                font-size: 1.5rem;
            }
            
            .form-label {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-leaf"></i>
            <h2>AgroNeg</h2>
            <p class="text-muted">Painel Administrativo</p>
        </div>
        
        <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuário</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Seu nome de usuário" required autocomplete="username">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Entrar
            </button>
        </form>
        
        <div class="login-footer">
            <p>AgroNeg - Parceiros do Agronegócio</p>
            <a href="index.php" class="d-block">Voltar para o site</a>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 