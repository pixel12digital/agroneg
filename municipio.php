<?php
// Incluir arquivo de conexão
require_once("config/db.php");

// --- Lógica de Filtros e Busca ---

// Validar e obter os IDs numéricos da URL
$estado_id = isset($_GET['estado']) ? filter_var($_GET['estado'], FILTER_VALIDATE_INT) : null;
$municipio_id = isset($_GET['municipio']) ? filter_var($_GET['municipio'], FILTER_VALIDATE_INT) : null;

// Obter categorias para filtrar (tipos de parceiros)
$categorias_slug = isset($_GET['categorias']) ? explode(',', $_GET['categorias']) : [];

// Se os IDs não forem válidos, redireciona para a home
if (!$estado_id || !$municipio_id) {
    header('Location: index.php');
    exit;
}

// Obter informações do município usando os IDs
$query_municipio = "
    SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla
    FROM municipios m
    JOIN estados e ON m.estado_id = e.id
    WHERE e.id = ? AND m.id = ?
";
$stmt_municipio = $conn->prepare($query_municipio);
$stmt_municipio->bind_param("ii", $estado_id, $municipio_id);
$stmt_municipio->execute();
$resultado_municipio = $stmt_municipio->get_result();

// Se o município não for encontrado, redireciona
if ($resultado_municipio->num_rows === 0) {
    header('Location: index.php');
    exit;
}
$municipio = $resultado_municipio->fetch_assoc();

// Buscar imagens da galeria do município
$query_galeria = "SELECT arquivo, legenda FROM fotos WHERE entidade_tipo = 'municipio' AND entidade_id = ? ORDER BY ordem, id";
$stmt_galeria = $conn->prepare($query_galeria);
$stmt_galeria->bind_param("i", $municipio['id']);
$stmt_galeria->execute();
$resultado_galeria = $stmt_galeria->get_result();
$galeria = $resultado_galeria->fetch_all(MYSQLI_ASSOC);

// --- Construir consulta para buscar parceiros (carregamento inicial) ---
$params = [$municipio_id];
$types = 'i';

$sql_parceiros = "
    SELECT p.*, GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as categorias_parceiro, t.nome as tipo_nome, t.slug as tipo_slug
    FROM parceiros p
    LEFT JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
    LEFT JOIN categorias c ON pc.categoria_id = c.id
    JOIN tipos_parceiros t ON p.tipo_id = t.id
    WHERE p.municipio_id = ? AND p.status = 1
    GROUP BY p.id ORDER BY p.destaque DESC, p.nome ASC
";

$stmt_parceiros = $conn->prepare($sql_parceiros);
if ($stmt_parceiros) {
    $stmt_parceiros->bind_param($types, ...$params);
    $stmt_parceiros->execute();
    $resultado_parceiros = $stmt_parceiros->get_result();
    $parceiros = $resultado_parceiros->fetch_all(MYSQLI_ASSOC);
} else {
    $parceiros = []; // Falha na consulta
}

// Título da página
$titulo_pagina = $municipio['nome'] . ' - ' . $municipio['estado_nome'] . ' | AgroNeg';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/municipio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    .redes-sociais {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 16px;
        align-items: center;
        margin-top: 10px;
        margin-bottom: 10px;
        justify-content: flex-start;
    }
    .rede-social {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 66px;
        height: 66px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 4px #0001;
        color: #1A9B60;
        transition: background 0.2s, color 0.2s;
        text-decoration: none;
        font-size: 1em;
        position: relative;
    }
    .rede-social i, .rede-social svg {
        font-size: 2.7em;
        color: #1A9B60;
        margin: 0;
        width: 80%;
        height: 80%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .rede-social:hover {
        background: #1A9B60;
        color: #fff;
    }
    .rede-social:hover i, .rede-social:hover svg {
        color: #fff;
    }
    @media (max-width: 700px) {
        .redes-sociais {
            justify-content: center;
            gap: 12px;
        }
        .rede-social {
            width: 48px;
            height: 48px;
        }
        .rede-social i, .rede-social svg {
            font-size: 1.8em;
        }
    }
    /* Miniaturas da galeria: mostrar imagem inteira, centralizada, sem cortes */
    .miniatura {
        width: 140px; /* ajuste conforme seu layout */
        height: 84px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 6px #0001;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .miniatura:hover {
        transform: scale(1.05);
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
    }
    .miniatura img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f5f5f5;
        border-radius: 8px;
        display: block;
    }
    #galeria-prev, #galeria-next {
        background: rgba(0, 0, 0, 0.7);
        border: 2px solid rgba(255, 255, 255, 0.8);
        color: #fff;
        cursor: pointer;
        z-index: 10002;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
        opacity: 0.9;
        outline: none;
        padding: 0;
        backdrop-filter: blur(10px);
    }
    #galeria-prev:hover, #galeria-next:hover {
        background: rgba(255, 152, 0, 0.9);
        color: #fff;
        border-color: #fff;
        transform: scale(1.1);
        box-shadow: 0 6px 32px rgba(255, 152, 0, 0.4), 0 2px 12px rgba(0, 0, 0, 0.3);
        opacity: 1;
    }
    #galeria-prev:focus, #galeria-next:focus {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }
    #galeria-prev svg, #galeria-next svg {
        width: 28px;
        height: 28px;
        display: block;
        margin: auto;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
    }
    @media (max-width: 600px) {
        #galeria-prev, #galeria-next {
            width: 38px;
            height: 38px;
        }
        #galeria-prev svg, #galeria-next svg {
            width: 18px;
            height: 18px;
        }
    }
    </style>
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    
    <div class="main-content">
        <section class="municipio-header">
            <div class="container">
                <!-- Breadcrumb -->
                <div class="municipio-breadcrumb">
                    <a href="index.php">Home</a> &gt; 
                    <a href="index.php">Estados</a> &gt; 
                    <a href="index.php"><?php echo $municipio['estado_nome']; ?></a> &gt; 
                    <span><?php echo $municipio['nome']; ?></span>
                </div>
                <?php if (!empty($municipio['imagem_principal'])): ?>
                <div class="municipio-imagem">
                    <img src="uploads/municipios/<?php echo $municipio['imagem_principal']; ?>" alt="<?php echo $municipio['nome']; ?>">
                </div>
                <?php endif; ?>

                <!-- Filtros adicionais -->
                <div class="municipio-filters">
                    <h3>
                        Filtrar por Parceiros:
                        <span class="filtro-help" title="Clique nas categorias para filtrar os parceiros. Use clique duplo para seleção múltipla.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </h3>
                    <div class="filter-categories">
                        <div class="category-option <?php echo empty($categorias_slug) ? 'active' : ''; ?>" data-value="todos" onclick="window.location.href='municipio.php?estado=<?php echo $estado_id; ?>&municipio=<?php echo $municipio_id; ?>'">Todos</div>
                        <div class="category-option <?php echo in_array('produtores', $categorias_slug) ? 'active' : ''; ?>" data-value="produtores">Produtores</div>
                        <div class="category-option <?php echo in_array('criadores', $categorias_slug) ? 'active' : ''; ?>" data-value="criadores">Criadores</div>
                        <div class="category-option <?php echo in_array('veterinarios', $categorias_slug) ? 'active' : ''; ?>" data-value="veterinarios">Veterinários</div>
                        <div class="category-option <?php echo in_array('lojas-agropet', $categorias_slug) ? 'active' : ''; ?>" data-value="lojas-agropet">Lojas Agropet</div>
                        <div class="category-option <?php echo in_array('cooperativas', $categorias_slug) ? 'active' : ''; ?>" data-value="cooperativas">Cooperativas</div>
                    </div>
                </div>
                <!-- Galeria de fotos do município -->
                <?php if (!empty($galeria)): ?>
                <div class="municipio-galeria">
                    <h3>Galeria de Imagens</h3>
                    <div class="galeria-miniaturas">
                        <?php 
                        $debug_index = 0;
                        foreach ($galeria as $i => $imagem): 
                            $caminho_imagem = "uploads/municipios/galeria/" . $imagem['arquivo'];
                            if (file_exists($caminho_imagem)):
                        ?>
                        <div class="miniatura" style="height: 84px; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 6px #0001; cursor: pointer; position: relative;">
                            <img src="<?php echo $caminho_imagem; ?>" 
                                 alt="<?php echo !empty($imagem['legenda']) ? $imagem['legenda'] : $municipio['nome']; ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; border-radius: 0; box-shadow: none;"
                                 onclick="abrirModal('<?php echo $caminho_imagem; ?>', '<?php echo !empty($imagem['legenda']) ? htmlspecialchars($imagem['legenda'], ENT_QUOTES) : htmlspecialchars($municipio['nome'], ENT_QUOTES); ?>', <?php echo $debug_index; ?>)">
                        </div>
                        <?php 
                            $debug_index++;
                            endif; 
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Demais informações do município -->
                <div class="municipio-info">
                    <div class="municipio-dados">
                        <?php if (!empty($municipio['populacao'])): ?>
                        <div class="municipio-dado">
                            <h4><i class="fas fa-users"></i> População</h4>
                            <p><?php echo $municipio['populacao']; ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($municipio['area_rural'])): ?>
                        <div class="municipio-dado">
                            <h4><i class="fas fa-map-marked-alt"></i> Área Rural</h4>
                            <p><?php echo $municipio['area_rural']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($municipio['principais_culturas'])): ?>
                    <div class="municipio-culturas">
                        <h3>Principais Culturas</h3>
                        <p><?php echo $municipio['principais_culturas']; ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($municipio['website']) || !empty($municipio['facebook']) || !empty($municipio['instagram']) || !empty($municipio['twitter'])): ?>
                    <div class="municipio-redes">
                        <h3>Redes e Contatos</h3>
                        <div class="redes-sociais">
                            <?php if (!empty($municipio['website'])): ?>
                            <a href="<?php echo $municipio['website']; ?>" target="_blank" class="rede-social" title="Site Oficial">
                                <i class="fas fa-globe"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($municipio['facebook'])): ?>
                            <a href="<?php echo $municipio['facebook']; ?>" target="_blank" class="rede-social" title="Facebook">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($municipio['instagram'])): ?>
                            <a href="<?php echo $municipio['instagram']; ?>" target="_blank" class="rede-social" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($municipio['twitter'])): ?>
                            <a href="<?php echo $municipio['twitter']; ?>" target="_blank" class="rede-social" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Listagem de parceiros (AGORA AQUI) -->
                <section class="parceiros-lista">
                    <div class="container">
                        <!-- Contador de resultados -->
                        <div class="resultados-contador">
                            <h4>
                                <span class="contador-numero"><?php echo count($parceiros); ?></span> parceiro<?php echo count($parceiros) != 1 ? 's' : ''; ?> encontrado<?php echo count($parceiros) != 1 ? 's' : ''; ?> neste município
                            </h4>
                        </div>
                        
                        <!-- Container para resultados dinâmicos -->
                        <div class="parceiros-grid" id="parceiros-container">
                            <?php if (count($parceiros) > 0): ?>
                                <?php foreach ($parceiros as $parceiro): ?>
                                    <div class="parceiro-card <?php echo $parceiro['destaque'] ? 'destaque' : ''; ?>">
                                        <div class="parceiro-image">
                                            <?php if (!empty($parceiro['imagem_destaque'])): ?>
                                                <img src="uploads/parceiros/destaque/<?php echo $parceiro['imagem_destaque']; ?>" alt="<?php echo $parceiro['nome']; ?>">
                                            <?php else: ?>
                                                <img src="assets/img/placeholder.jpg" alt="<?php echo $parceiro['nome']; ?>">
                                            <?php endif; ?>
                                            <?php if ($parceiro['destaque']): ?>
                                                <span class="destaque-badge">Destaque</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="parceiro-content">
                                            <h3 class="parceiro-title"><?php echo $parceiro['nome']; ?></h3>
                                            <div class="parceiro-categoria"><?php echo $parceiro['categorias_parceiro']; ?><?php if(isset($parceiro['tipo_nome'])) echo ' - ' . $parceiro['tipo_nome']; ?></div>
                                            <?php if (!empty($parceiro['descricao'])): ?>
                                                <p class="parceiro-descricao"><?php echo substr($parceiro['descricao'], 0, 120); ?>...</p>
                                            <?php endif; ?>
                                            <a href="parceiro.php?slug=<?php echo $parceiro['slug']; ?>" class="btn-ver-mais">Ver detalhes</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="sem-resultados">
                                    <p>Nenhum parceiro cadastrado neste município ainda.</p>
                                    <p>Entre em contato conosco para cadastrar parceiros em <?php echo $municipio['nome']; ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
    
    <?php include __DIR__.'/partials/footer.php'; ?>
    
    <!-- Modal/Lightbox para imagens -->
    <div id="imagemModal" class="modal-imagem">
        <span class="modal-galeria-close" onclick="fecharModal()" title="Fechar (ESC)">&times;</span>
        <button id="galeria-prev" class="galeria-nav-btn galeria-prev" onclick="navegarGaleria(-1)">
          <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24 10L16 20L24 30" stroke="white" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <button id="galeria-next" class="galeria-nav-btn galeria-next" onclick="navegarGaleria(1)">
          <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 10L24 20L16 30" stroke="white" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div class="modal-conteudo">
            <img id="imagemAmpliada" src="" alt="" />
            <div id="legendaImagem" class="legenda-imagem"></div>
            <div class="zoom-dica">Clique duplo para ampliar</div>
        </div>
    </div>
    
    <script src="assets/js/header.js"></script>
    <script src="assets/js/municipio-filters.js"></script>
    <script>
        console.log('Script da galeria carregado');
        
        // Array das fotos da galeria
        var galeriaFotos = [
        <?php foreach ($galeria as $imagem): 
            $caminho_imagem = "uploads/municipios/galeria/" . $imagem['arquivo'];
            if (file_exists($caminho_imagem)):
        ?>
            {
                src: '<?php echo $caminho_imagem; ?>',
                legenda: '<?php echo !empty($imagem['legenda']) ? htmlspecialchars($imagem['legenda'], ENT_QUOTES) : htmlspecialchars($municipio['nome'], ENT_QUOTES); ?>'
            },
        <?php 
            endif;
        endforeach; ?>
        ];
        var galeriaIndexAtual = 0;
        
        console.log('Fotos da galeria carregadas:', galeriaFotos);
        console.log('Total de fotos:', galeriaFotos.length);
        
        // Verificar se há fotos duplicadas
        var srcs = galeriaFotos.map(f => f.src);
        var uniqueSrcs = [...new Set(srcs)];
        if (srcs.length !== uniqueSrcs.length) {
            console.warn('⚠️ ATENÇÃO: Fotos duplicadas detectadas!');
            console.warn('Fotos únicas:', uniqueSrcs);
        }
        
        // Verificar se os índices estão corretos
        galeriaFotos.forEach((foto, index) => {
            console.log(`📸 Foto ${index}:`, foto.src);
        });

        function abrirModal(imagemSrc, legenda, index) {
            console.log('Abrindo modal para:', imagemSrc, legenda, index);
            
            // Verificar se há fotos na galeria
            if (galeriaFotos.length === 0) {
                console.error('Nenhuma foto encontrada na galeria');
                return;
            }
            
            galeriaIndexAtual = typeof index === 'number' ? index : galeriaFotos.findIndex(f => f.src === imagemSrc);
            
            // Verificar se o índice é válido
            if (galeriaIndexAtual < 0 || galeriaIndexAtual >= galeriaFotos.length) {
                galeriaIndexAtual = 0;
            }
            
            atualizarModalGaleria();
            document.getElementById('imagemModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Configurar interações da imagem
            setTimeout(setupImageInteractions, 100);
        }
        
        function atualizarModalGaleria() {
            console.log('Atualizando modal galeria, índice:', galeriaIndexAtual);
            
            // Verificar se há fotos
            if (galeriaFotos.length === 0) {
                console.error('Nenhuma foto para exibir');
                return;
            }
            
            var foto = galeriaFotos[galeriaIndexAtual];
            console.log('Foto selecionada:', foto);
            
            var img = document.getElementById('imagemAmpliada');
            if (img) {
                img.src = foto.src;
                img.style.transform = 'scale(1)';
                img.dataset.zoom = '1';
                img.dataset.offsetX = '0';
                img.dataset.offsetY = '0';
                console.log('Imagem definida:', img.src);
            } else {
                console.error('Elemento imagemAmpliada não encontrado!');
                return;
            }
            
            var legenda = document.getElementById('legendaImagem');
            if (legenda) {
                legenda.textContent = foto.legenda;
            }
            
            // Atualizar botões de navegação
            var prevBtn = document.getElementById('galeria-prev');
            var nextBtn = document.getElementById('galeria-next');
            
            if (prevBtn) prevBtn.style.display = galeriaIndexAtual > 0 ? 'block' : 'none';
            if (nextBtn) nextBtn.style.display = galeriaIndexAtual < galeriaFotos.length-1 ? 'block' : 'none';
        }
        
        function navegarGaleria(delta) {
            if (galeriaFotos.length === 0) {
                console.error('Nenhuma foto para navegar');
                return;
            }
            
            galeriaIndexAtual += delta;
            
            // Garantir que o índice esteja dentro dos limites
            if (galeriaIndexAtual < 0) galeriaIndexAtual = galeriaFotos.length - 1;
            if (galeriaIndexAtual >= galeriaFotos.length) galeriaIndexAtual = 0;
            
            console.log('Navegando para índice:', galeriaIndexAtual);
            atualizarModalGaleria();
        }
        function fecharModal() {
            console.log('Fechando modal');
            
            // Resetar zoom e posição
            zoom = 1;
            offsetX = 0;
            offsetY = 0;
            
            const img = document.getElementById('imagemAmpliada');
            if (img) {
                img.style.transform = 'scale(1)';
                img.style.cursor = 'zoom-in';
                img.dataset.zoom = '1';
            }
            
            document.getElementById('imagemModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        // Fechar com ESC e navegação por teclado
        document.addEventListener('keydown', function(event) {
            if (document.getElementById('imagemModal').style.display === 'flex') {
                if (event.key === 'Escape') fecharModal();
                if (event.key === 'ArrowLeft') navegarGaleria(-1);
                if (event.key === 'ArrowRight') navegarGaleria(1);
            }
        });
        // Fechar ao clicar fora da imagem
        document.getElementById('imagemModal').onclick = function(e) {
            if (e.target === this) fecharModal();
        };
        
        // Zoom e arrastar
        let isDragging = false, startX = 0, startY = 0, offsetX = 0, offsetY = 0, zoom = 1;
        
        function setupImageInteractions() {
            const img = document.getElementById('imagemAmpliada');
            if (!img) return;
            
            img.addEventListener('wheel', function(e) {
                e.preventDefault();
                zoom += (e.deltaY < 0) ? 0.15 : -0.15;
                if (zoom < 1) zoom = 1;
                if (zoom > 5) zoom = 5;
                img.style.transform = `scale(${zoom}) translate(${offsetX}px, ${offsetY}px)`;
                img.dataset.zoom = zoom;
            });
            
            img.addEventListener('mousedown', function(e) {
                if (zoom === 1) return;
                isDragging = true;
                startX = e.clientX - offsetX;
                startY = e.clientY - offsetY;
                img.style.cursor = 'grabbing';
            });
            
            img.addEventListener('dblclick', function() {
                if (zoom === 1) {
                    zoom = 2;
                    img.style.transform = `scale(${zoom})`;
                    img.style.cursor = 'zoom-out';
                } else {
                    zoom = 1;
                    offsetX = 0;
                    offsetY = 0;
                    img.style.transform = 'scale(1)';
                    img.style.cursor = 'zoom-in';
                }
                img.dataset.zoom = zoom;
            });
        }
        
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            offsetX = e.clientX - startX;
            offsetY = e.clientY - startY;
            const img = document.getElementById('imagemAmpliada');
            if (img) {
                img.style.transform = `scale(${zoom}) translate(${offsetX}px, ${offsetY}px)`;
            }
        });
        
        document.addEventListener('mouseup', function() {
            isDragging = false;
            const img = document.getElementById('imagemAmpliada');
            if (img) {
                img.style.cursor = 'grab';
            }
        });
        
        document.getElementById('imagemModal').addEventListener('scroll', function(e) { 
            e.preventDefault(); 
        });
        
        // Ajustar tamanho da imagem quando a janela for redimensionada
        window.addEventListener('resize', function() {
            if (document.getElementById('imagemModal').style.display === 'flex') {
                // Resetar zoom e posição quando redimensionar
                zoom = 1;
                offsetX = 0;
                offsetY = 0;
                const img = document.getElementById('imagemAmpliada');
                if (img) {
                    img.style.transform = 'scale(1)';
                    img.style.cursor = 'zoom-in';
                    img.dataset.zoom = '1';
                }
            }
        });

        // Verificação final quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, verificando galeria...');
            console.log('Total de fotos na galeria:', galeriaFotos.length);
            
            if (galeriaFotos.length > 0) {
                console.log('✅ Galeria carregada com sucesso');
                console.log('Fotos disponíveis:', galeriaFotos.map(f => f.src));
            } else {
                console.warn('⚠️ Nenhuma foto encontrada na galeria');
                console.warn('Verifique se há fotos cadastradas no banco de dados');
            }
        });
    </script>
</body>
</html>
