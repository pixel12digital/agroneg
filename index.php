<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroNeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/banner.css">
    <link rel="stylesheet" href="assets/css/filters.css">
    <link rel="stylesheet" href="assets/css/features.css">
    <link rel="stylesheet" href="assets/css/about-section.css">
    <link rel="stylesheet" href="assets/css/profiles-section.css">
    <link rel="stylesheet" href="assets/css/events-section.css">
    <link rel="stylesheet" href="assets/css/contact-section.css">
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
    </style>
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    
    <div class="main-content">
        <section class="banner-section">
            <div class="container">
                <div class="banner-wrapper">
                    <img src="assets/img/banner-inicial.png" alt="Banner AgroNeg" class="banner-img">
                    
                    <!-- Filtro sobreposto ao banner -->
                    <div class="filter-container">
                        <div class="filter-content">
                            <h2 class="filter-title">Encontre parceiros agropecuários em sua região</h2>
                            <p class="filter-subtitle">Selecione seu estado, município e tipo de parceiro para começar</p>
                            
                            <form class="filter-form" method="GET" action="municipio.php">
                                <div class="filter-row">
                                    <label for="estado" class="filter-label">Estado</label>
                                    <select id="estado" name="estado" class="filter-select" required>
                                        <option value="">Selecione um estado</option>
                                        <?php
                                        // Sistema de cache para reduzir consultas ao banco
                                        $cache_file = 'cache/estados.json';
                                        $cache_duration = 3600; // 1 hora
                                        
                                        // Verificar se o cache existe e é válido
                                        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
                                            $estados = json_decode(file_get_contents($cache_file), true);
                                        } else {
                                            // Incluir arquivo de conexão
                                            require_once("config/db.php");
                                            
                                            // Obter conexão com banco de dados
                                            $conn = getAgronegConnection();
                                            
                                            // Consultar apenas estados que possuem municípios cadastrados
                                            $query = "SELECT DISTINCT e.id, e.nome 
                                                     FROM estados e
                                                     INNER JOIN municipios m ON e.id = m.estado_id
                                                     ORDER BY e.nome ASC";
                                            $resultado = $conn->query($query);
                                            
                                            $estados = [];
                                            if ($resultado && $resultado->num_rows > 0) {
                                                while ($estado = $resultado->fetch_assoc()) {
                                                    $estados[] = $estado;
                                                }
                                                
                                                // Criar diretório cache se não existir
                                                if (!is_dir('cache')) {
                                                    mkdir('cache', 0755, true);
                                                }
                                                
                                                // Salvar no cache
                                                file_put_contents($cache_file, json_encode($estados));
                                            }
                                        }
                                        
                                        // Exibir estados
                                        if (!empty($estados)) {
                                            foreach ($estados as $estado) {
                                                echo '<option value="' . htmlspecialchars($estado['id']) . '">' . htmlspecialchars($estado['nome']) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="" disabled>Não foi possível carregar os estados</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="filter-row">
                                    <label for="municipio" class="filter-label">Município</label>
                                    <select id="municipio" name="municipio" class="filter-select" required disabled>
                                        <option value="">Selecione um município</option>
                                    </select>
                                </div>
                                
                                <div class="filter-row button-row">
                                    <button type="submit" class="filter-button" id="buscar-btn">Buscar</button>
                                </div>
                            </form>
                            
                            <div class="filter-categories">
                                <div class="category-option" data-value="produtores">Produtores</div>
                                <div class="category-option" data-value="criadores">Criadores</div>
                                <div class="category-option" data-value="veterinarios">Veterinários</div>
                                <div class="category-option" data-value="lojas">Lojas Agropet</div>
                                <div class="category-option" data-value="cooperativas">Cooperativas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nova seção de recursos abaixo do filtro -->
        <section class="features-section">
            <div class="container">
                <div class="features-wrapper">
                    <!-- Feature 1 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="assets/img/icons/plant-icon.svg" alt="Ícone AgroNeg">
                        </div>
                        <h3 class="feature-title">O que é AgroNeg?</h3>
                        <p class="feature-text">
                            Plataforma que conecta produtores, criadores, cooperativas e veterinários do Nordeste, permitindo divulgação de produtos e serviços em um só lugar.
                        </p>
                        <a href="#" class="feature-link">Saiba mais →</a>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="assets/img/icons/settings-icon.svg" alt="Ícone Como Funciona">
                        </div>
                        <h3 class="feature-title">Como funciona?</h3>
                        <p class="feature-text">
                            Escolha o estado e a cidade, filtre pela categoria desejada e acesse o perfil completo dos profissionais, com fotos, contatos e mapa.
                        </p>
                        <a href="#" class="feature-link">Testar filtro →</a>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="assets/img/icons/user-icon.svg" alt="Ícone Participação">
                        </div>
                        <h3 class="feature-title">Participe você também</h3>
                        <p class="feature-text">
                            Cadastre-se como produtor ou anunciante e publique até 12 fotos dos seus produtos. Ganhe visibilidade e alcance novos clientes!
                        </p>
                        <a href="contato.php" class="feature-link">Cadastre-se →</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção Sobre / A Melhor Experiência -->
        <section class="about-section">
            <div class="about-container">
                <div class="about-content">
                    <h2 class="about-title">A Melhor Experiência</h2>
                    <h3 class="about-subtitle">Transformando o Campo Brasileiro</h3>
                    <div class="about-text">
                        <p>
                            O AgroNeg é mais que um simples site, é uma <span class="about-highlight">plataforma criada com o objetivo</span> de 
                            conectar produtores, agricultores, criadores e profissionais do agronegócio a 
                            consumidores de todo o Brasil.
                        </p>
                        <p>
                            Nosso propósito é proporcionar um ambiente digital onde o agronegócio local possa crescer, 
                            se expandir e alcançar novos horizontes, sem perder a essência e o cuidado do trabalho 
                            feito no campo.
                        </p>
                    </div>
                </div>
                <div class="about-image">
                    <img src="assets/images/agroneg-campo.jpg" alt="Plantação brasileira ao pôr do sol" class="img-fluid">
                </div>
            </div>
        </section>

        <!-- Seção de Perfis e Benefícios -->
        <?php include __DIR__.'/partials/profiles-section.php'; ?>
        
        <!-- Seção de Eventos -->
        <?php include __DIR__.'/partials/events-section.php'; ?>
    </div>
    
    <!-- Footer com seção de contato -->
    <?php include __DIR__.'/partials/footer.php'; ?>

    <script src="assets/js/header.js"></script>
    <script src="assets/js/filters.js"></script>
</body>
</html>
