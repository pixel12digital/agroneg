<?php
/**
 * Página inicial com roteamento inteligente
 * Se a URL for amigável, redireciona para o arquivo apropriado
 */

// Verificar se a URL é uma URL amigável
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Detectar caminho base para assets
// Sempre usar caminho absoluto para evitar problemas com servidor PHP built-in
$base_path = '/';

// Se for a raiz, index.php ou /Agroneg/, mostrar a página inicial
if ($path === '/' || $path === '/index.php' || $path === '' || $path === '/Agroneg/' || $path === '/Agroneg') {
    // Continuar para mostrar a página inicial (sem tentar conectar ao banco)
} else {
    // Verificar se é uma URL amigável de município: /ce/iracema ou /Agroneg/ce/iracema
    if (preg_match('/^\/Agroneg\/([a-z]{2})\/([a-z0-9-]+)\/?$/', $path, $matches) || 
        preg_match('/^\/([a-z]{2})\/([a-z0-9-]+)\/?$/', $path, $matches)) {
        $_GET['slug_estado'] = $matches[1];
        $_GET['slug_municipio'] = $matches[2];
        if (file_exists('municipio.php')) {
            include 'municipio.php';
        } else {
            http_response_code(404);
            echo "Arquivo municipio.php não encontrado";
        }
        exit;
    }
    
    // Verificar se é uma URL amigável de parceiro por tipo: /produtores/ce/iracema ou /Agroneg/produtores/ce/iracema
    if (preg_match('/^\/Agroneg\/(produtores|criadores|veterinarios|lojas-agropet|cooperativas)\/([a-z]{2})\/([a-z0-9-]+)\/?$/', $path, $matches) ||
        preg_match('/^\/(produtores|criadores|veterinarios|lojas-agropet|cooperativas)\/([a-z]{2})\/([a-z0-9-]+)\/?$/', $path, $matches)) {
        $_GET['slug_estado'] = $matches[2];
        $_GET['slug_municipio'] = $matches[3];
        $file = $matches[1] . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            http_response_code(404);
            echo "Arquivo $file não encontrado";
        }
        exit;
    }
    
    // Verificar se é uma URL amigável de parceiro individual: /parceiro/fazenda-sao-joao ou /Agroneg/parceiro/fazenda-sao-joao
    if (preg_match('/^\/Agroneg\/parceiro\/([a-z0-9-]+)\/?$/', $path, $matches) ||
        preg_match('/^\/parceiro\/([a-z0-9-]+)\/?$/', $path, $matches)) {
        $_GET['slug'] = $matches[1];
        if (file_exists('parceiro.php')) {
            include 'parceiro.php';
        } else {
            http_response_code(404);
            echo "Arquivo parceiro.php não encontrado";
        }
        exit;
    }
    
    // Verificar se é uma página simples: /produtores, /eventos ou /Agroneg/produtores, /Agroneg/eventos
    if (preg_match('/^\/Agroneg\/(produtores|criadores|veterinarios|lojas-agropet|cooperativas|eventos)\/?$/', $path, $matches) ||
        preg_match('/^\/(produtores|criadores|veterinarios|lojas-agropet|cooperativas|eventos)\/?$/', $path, $matches)) {
        $file = $matches[1] . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            http_response_code(404);
            echo "Arquivo $file não encontrado";
        }
        exit;
    }
    
    // Se não encontrou nenhum padrão, mostrar 404
    http_response_code(404);
    echo "Página não encontrada: $path";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroNeg</title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/banner.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/filters.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/features.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/about-section.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/profiles-section.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/events-section.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/contact-section.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/footer.css">
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
                    <img src="<?php echo $base_path; ?>assets/img/banner-inicial.png" alt="Banner AgroNeg" class="banner-img">
                    
                    <!-- Filtro sobreposto ao banner -->
                    <div class="filter-container">
                        <div class="filter-content">
                            <h2 class="filter-title">Encontre parceiros agropecuários em sua região</h2>
                            <p class="filter-subtitle">Selecione seu estado, município e tipo de parceiro para começar</p>
                            
                            <form class="filter-form" id="filter-form" action="javascript:void(0);">
                                <div class="filter-row">
                                    <label for="estado" class="filter-label">Estado</label>
                                    <select id="estado" class="filter-select" required>
                                        <option value="">Selecione um estado</option>
                                        <?php
                                        // Dados estáticos dos estados (funciona mesmo sem banco de dados)
                                        $estados = [
                                            ['id' => 6, 'nome' => 'Ceará', 'sigla' => 'CE'],
                                            ['id' => 15, 'nome' => 'Paraíba', 'sigla' => 'PB'],
                                            ['id' => 17, 'nome' => 'Pernambuco', 'sigla' => 'PE'],
                                            ['id' => 20, 'nome' => 'Rio Grande do Norte', 'sigla' => 'RN']
                                        ];
                                        
                                        // Exibir estados
                                        foreach ($estados as $estado) {
                                            echo '<option value="' . htmlspecialchars($estado['id']) . '" data-slug="' . strtolower($estado['sigla']) . '">' . htmlspecialchars($estado['nome']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="filter-row">
                                    <label for="municipio" class="filter-label">Município</label>
                                    <select id="municipio" class="filter-select" required disabled>
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
                                <div class="category-option" data-value="lojas-agropet">Lojas Agropet</div>
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
                            <img src="<?php echo $base_path; ?>assets/img/icons/plant-icon.svg" alt="Ícone AgroNeg">
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
                            <img src="<?php echo $base_path; ?>assets/img/icons/settings-icon.svg" alt="Ícone Como Funciona">
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
                            <img src="<?php echo $base_path; ?>assets/img/icons/user-icon.svg" alt="Ícone Participação">
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
                    <img src="<?php echo $base_path; ?>assets/images/agroneg-campo.jpg" alt="Plantação brasileira ao pôr do sol" class="img-fluid">
                </div>
            </div>
        </section>

        <!-- Seção de Perfis e Benefícios -->
        <?php include __DIR__.'/partials/profiles-section.php'; ?>
        
        <!-- Seção de Eventos -->
        <?php include __DIR__.'/partials/events-section.php'; ?>
    </div>
    
    <!-- Footer com seção de contato -->
    <?php include __DIR__.'/partials/footer_simple.php'; ?>

    <script>
        console.log('🧪 Teste 1 - JavaScript inline funcionando!');
        
        // JavaScript completo para filtros
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 DOM carregado - iniciando configuração dos filtros!');
            
            const estadoSelect = document.getElementById('estado');
            const municipioSelect = document.getElementById('municipio');
            const categorias = document.querySelectorAll('.category-option');
            const filterForm = document.querySelector('.filter-form');
            
            console.log('Elementos encontrados:');
            console.log('- estadoSelect:', estadoSelect ? 'SIM' : 'NÃO');
            console.log('- municipioSelect:', municipioSelect ? 'SIM' : 'NÃO');
            console.log('- categorias:', categorias.length);
            console.log('- filterForm:', filterForm ? 'SIM' : 'NÃO');
            
            // Desabilitar select de município inicialmente
            if (municipioSelect) {
                municipioSelect.disabled = true;
            }
            
            // Event listener para estados
            if (estadoSelect && municipioSelect) {
                estadoSelect.addEventListener('change', function() {
                    console.log('Estado alterado para:', this.value);
                    
                    if (this.value !== '') {
                        municipioSelect.disabled = false;
                        municipioSelect.innerHTML = '<option value="">Carregando...</option>';
                        
                        // Carregar municípios via AJAX
                        carregarMunicipios(this.value);
                    } else {
                        municipioSelect.disabled = true;
                        municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                    }
                });
            }
            
            // Event listener para categorias
            categorias.forEach(function(categoria) {
                categoria.addEventListener('click', function() {
                    this.classList.toggle('active');
                    console.log('Categoria clicada:', this.dataset.value, 'Ativa:', this.classList.contains('active'));
                });
            });
            
            // Event listener para o formulário
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Formulário submetido!');
                    
                    const estadoId = estadoSelect.value;
                    const municipioId = municipioSelect.value;
                    
                    console.log('Estado ID:', estadoId, 'Município ID:', municipioId);
                    
                    if (!estadoId || !municipioId) {
                        alert('Por favor, selecione um estado e um município.');
                        return;
                    }
                    
                    // Obter categorias selecionadas
                    const categoriasAtivas = [];
                    document.querySelectorAll('.category-option.active').forEach(function(cat) {
                        categoriasAtivas.push(cat.dataset.value);
                    });
                    
                    console.log('Categorias ativas:', categoriasAtivas);
                    
                    // Obter slugs
                    const estadoSlug = estadoSelect.options[estadoSelect.selectedIndex].dataset.slug;
                    const municipioOption = municipioSelect.options[municipioSelect.selectedIndex];
                    const municipioNome = municipioOption.textContent;
                    
                    // Gerar slug do município
                    const municipioSlug = municipioNome
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    
                    console.log('Slugs gerados:', estadoSlug, municipioSlug);
                    
                    let url;
                    
                    // Detectar se estamos no subdiretório /Agroneg/
                    const currentPath = window.location.pathname;
                    const basePath = currentPath.includes('/Agroneg/') ? '/Agroneg' : '';
                    
                    console.log('Caminho atual:', currentPath);
                    console.log('Base path detectado:', basePath);
                    
                    if (categoriasAtivas.length > 0) {
                        // Com categoria
                        const tipoSlug = categoriasAtivas[0];
                        url = `${basePath}/${tipoSlug}/${estadoSlug}/${municipioSlug}`;
                        
                        if (categoriasAtivas.length > 1) {
                            url += `?categorias=${categoriasAtivas.join(',')}`;
                        }
                    } else {
                        // Sem categoria
                        url = `${basePath}/${estadoSlug}/${municipioSlug}`;
                    }
                    
                    console.log('URL final gerada:', url);
                    console.log('Redirecionando...');
                    
                    window.location.href = url;
                });
            }
            
            // Função para carregar municípios
            function carregarMunicipios(estadoId) {
                console.log('Carregando municípios para estado:', estadoId);
                
                // Dados estáticos apenas dos municípios que existem no banco
                const municipiosPorEstado = {
                    6: [ // Ceará - apenas os que existem no banco
                        {id: 3, nome: 'Iracema', slug: 'iracema'}
                    ],
                    15: [ // Paraíba - apenas os que existem no banco
                        {id: 1, nome: 'Barra de São Miguel', slug: 'barra-de-sao-miguel'},
                        {id: 2, nome: 'João Pessoa', slug: 'joao-pessoa'}
                    ],
                    17: [ // Pernambuco - apenas os que existem no banco
                        {id: 2, nome: 'Santa Cruz do Capibaribe', slug: 'santa-cruz-do-capibaribe'},
                        {id: 5, nome: 'Jataúba', slug: 'jatauba'}
                    ],
                    20: [ // Rio Grande do Norte - apenas os que existem no banco
                        {id: 4, nome: 'Mossoró', slug: 'mossoro'}
                    ]
                };
                
                const municipios = municipiosPorEstado[estadoId] || [];
                
                setTimeout(function() {
                    municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                    
                    municipios.forEach(function(municipio) {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        option.dataset.slug = municipio.slug;
                        municipioSelect.appendChild(option);
                    });
                    
                    console.log('Municípios carregados:', municipios.length);
                }, 500);
            }
        });
    </script>
</body>
</html>