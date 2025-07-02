<?php
// Exibe mensagem de sucesso/erro se houver
$mensagem = '';
if (isset($_GET['sucesso'])) {
    $mensagem = '<div style="color: green; margin-bottom: 20px;">Mensagem enviada com sucesso!</div>';
} elseif (isset($_GET['erro'])) {
    $mensagem = '<div style="color: red; margin-bottom: 20px;">Erro ao enviar mensagem. Tente novamente.</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Fix para problemas potenciais de estilo */
        #site-header .desktop-nav ul,
        #site-header .mobile-nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }
        
        /* Estilos do formulário de contato */
        .contato-container {
            max-width: 700px;
            margin: 0 auto 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0001;
            padding: 30px 32px 32px 32px;
        }
        
        .contato-container h2 {
            color: #1A9B60;
            margin-bottom: 20px;
            font-size: 2em;
            font-weight: 700;
            text-align: left;
        }
        
        .contato-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .contato-form input,
        .contato-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 15px;
        }
        
        .contato-form textarea {
            height: 120px;
            resize: vertical;
        }
        
        .contato-form button {
            background: #1A9B60;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: background 0.2s;
        }
        
        .contato-form button:hover {
            background: #148350;
        }

        /* Bloco de destaque no padrão do site publicado */
        .bloco-destaque-contato {
            background: #f3fcf7;
            border-left: 6px solid #1A9B60;
            border-radius: 8px;
            max-width: 700px;
            margin: 0 auto 32px auto;
            padding: 32px 32px 28px 32px;
            box-sizing: border-box;
            box-shadow: 0 2px 8px #0001;
        }
        .bloco-destaque-contato h3 {
            color: #1A9B60;
            font-size: 1.6em;
            margin-bottom: 16px;
            font-weight: 700;
            text-align: left;
        }
        .bloco-destaque-contato h4 {
            color: #148350;
            font-size: 1.13em;
            margin-bottom: 6px;
            font-weight: 600;
            text-align: left;
        }
        .bloco-destaque-contato span, .bloco-destaque-contato p {
            color: #444;
            font-size: 1.08em;
            text-align: left;
        }
        .bloco-destaque-contato p.final {
            margin-top: 18px;
            color: #444;
            font-size: 1em;
            font-style: italic;
        }
        /* Espaçamento entre bloco e formulário */
        .main-content {
            margin-top: 0;
        }
        .contato-container {
            margin-top: 32px;
        }
        @media (max-width: 900px) {
            .bloco-destaque-contato, .contato-container {
                max-width: 98vw;
                padding: 18px 8px 18px 12px;
            }
        }
        @media (max-width: 700px) {
            .bloco-destaque-contato, .contato-container {
                max-width: 100vw;
                padding: 12px 2vw;
            }
            .bloco-destaque-contato h3, .contato-container h2 {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    
    <div class="main-content">
        <div class="contato-container">
            <h2>Fale Conosco</h2>
            <div class="bloco-destaque-contato">
                <h3><i class="fas fa-info-circle" style="color: #1A9B60; margin-right: 8px;"></i> Quer se conectar com o AgroNeg?</h3>
                <p style="margin-bottom: 16px;">
                    Preencha o formulário abaixo e nossa equipe entrará em contato o mais breve possível.
                </p>
                <div style="margin-bottom: 10px;">
                    <h4><i class="fas fa-seedling" style="color: #1A9B60; margin-right: 7px;"></i> Para Parceiros</h4>
                    <span>Produtores, criadores, veterinários, lojas e cooperativas: <b>Cadastre-se</b> para ampliar sua presença digital e alcançar novos clientes.</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <h4><i class="fas fa-university" style="color: #1A9B60; margin-right: 7px;"></i> Para Prefeituras</h4>
                    <span>Divulgue as potencialidades do seu município e conecte-se com o agronegócio regional.</span>
                </div>
                <div>
                    <h4><i class="fas fa-users" style="color: #1A9B60; margin-right: 7px;"></i> Para Usuários Finais</h4>
                    <span>Envie suas dúvidas, sugestões ou feedback. <b>Queremos ouvir você!</b></span>
                </div>
                <p class="final">Preencha seus dados e envie sua mensagem. Retornaremos em breve!</p>
            </div>
            <?php echo $mensagem; ?>
            <form class="contato-form" action="processa-contato.php" method="POST">
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div>
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div>
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone">
                </div>
                
                <div>
                    <label for="assunto">Assunto:</label>
                    <input type="text" id="assunto" name="assunto" required>
                </div>
                
                <div>
                    <label for="mensagem">Mensagem:</label>
                    <textarea id="mensagem" name="mensagem" required></textarea>
                </div>
                
                <button type="submit">Enviar Mensagem</button>
            </form>
        </div>
    </div>
    
    <?php include __DIR__.'/partials/footer.php'; ?>
    <script src="assets/js/header.js"></script>
</body>
</html> 