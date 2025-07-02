<?php
// Incluir arquivo de conexão
require_once("config/db.php");

// Verificar se foi passado o slug do parceiro
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if (!$slug) {
    header('Location: index.php');
    exit;
}

// Consultar dados do parceiro
$query = "
    SELECT p.*, c.nome as categoria_nome, c.slug as categoria_slug, 
           m.nome as municipio_nome, m.slug as municipio_slug,
           e.nome as estado_nome, e.sigla as estado_sigla
    FROM parceiros p
    JOIN parceiros_categorias pc ON p.id = pc.parceiro_id
    JOIN categorias c ON pc.categoria_id = c.id
    JOIN municipios m ON p.municipio_id = m.id
    JOIN estados e ON m.estado_id = e.id
    WHERE p.slug = ? AND p.status = 1
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $slug);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar se o parceiro existe
if ($resultado->num_rows === 0) {
    header('Location: index.php');
    exit;
}

// Obter dados do parceiro
$parceiro = $resultado->fetch_assoc();

// Consultar fotos do parceiro
$query_fotos = "
    SELECT * FROM fotos
    WHERE entidade_tipo = 'parceiro' AND entidade_id = ?
    ORDER BY ordem ASC
";

$stmt_fotos = $conn->prepare($query_fotos);
$stmt_fotos->bind_param("i", $parceiro['id']);
$stmt_fotos->execute();
$resultado_fotos = $stmt_fotos->get_result();

// Obter todas as fotos
$fotos = [];
while ($foto = $resultado_fotos->fetch_assoc()) {
    $fotos[] = $foto;
}

// Título da página
$titulo_pagina = $parceiro['nome'] . ' - ' . $parceiro['municipio_nome'] . ' | AgroNeg';
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
    <link rel="stylesheet" href="assets/css/parceiro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    @media (max-width: 768px) {
      .parceiro-perfil-row {
        flex-direction: column !important;
        gap: 18px !important;
        align-items: center !important;
      }
      .parceiro-perfil-img, .parceiro-perfil-info {
        max-width: 100% !important;
        min-width: 0 !important;
        width: 100% !important;
        align-items: center !important;
      }
      .parceiro-aviso {
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
      }
      .parceiro-content .container {
        padding-left: 12px !important;
        padding-right: 12px !important;
      }
    }
    /* Estilo das setas do modal da galeria - círculo mais destacado e seta SVG centralizada */
    #galeria-prev, #galeria-next {
      background: #111;
      border: 3px solid #fff;
      color: #fff;
      cursor: pointer;
      z-index: 10002;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 24px #000b;
      transition: background 0.2s, transform 0.2s, box-shadow 0.2s, border 0.2s;
      opacity: 0.98;
      outline: none;
      padding: 0;
    }
    #galeria-prev:hover, #galeria-next:hover {
      background: #ff9800;
      color: #fff;
      border-color: #fff;
      transform: scale(1.13);
      box-shadow: 0 6px 32px #ff980088, 0 2px 12px #000b;
      opacity: 1;
    }
    #galeria-prev:focus, #galeria-next:focus {
      outline: 2px solid #fff;
    }
    #galeria-prev svg, #galeria-next svg {
      width: 28px;
      height: 28px;
      display: block;
      margin: auto;
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
    #modalGaleriaImg {
      display: block;
      margin: auto;
      width: auto;
      height: auto;
      max-width: 90vw;
      max-height: 90vh;
      cursor: grab;
      transition: transform 0.2s;
      box-shadow: 0 4px 24px #000b;
      border-radius: 8px;
      background: #222;
    }
    .redes-links a {
      font-size: 1.8em !important; /* 20% maior que 1.5em padrão */
    }
    .redes-links a[title="Website"] {
      color: #007bff !important;
    }
    </style>
</head>
<body>
    <?php include __DIR__.'/partials/header.php'; ?>
    
    <div class="main-content">
        <!-- Cabeçalho da página -->
        <section class="parceiro-header">
            <div class="container">
                <div class="parceiro-breadcrumb">
                    <a href="index.php">Home</a> &gt; 
                    <a href="estados.php?estado=<?php echo $parceiro['estado_sigla']; ?>"><?php echo $parceiro['estado_nome']; ?></a> &gt; 
                    <a href="municipio.php?estado=<?php echo $parceiro['estado_sigla']; ?>&municipio=<?php echo $parceiro['municipio_slug']; ?>"><?php echo $parceiro['municipio_nome']; ?></a> &gt; 
                    <span><?php echo $parceiro['nome']; ?></span>
                </div>
                
                <div class="parceiro-title-wrapper">
                    <h1 class="parceiro-title"><?php echo $parceiro['nome']; ?></h1>
                    <div class="parceiro-categoria-badges" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px; align-items:center; font-size:0.98em; line-height:1.2; max-width:100%; margin-bottom:18px;">
                        <?php if (!empty($parceiro['tipo'])): ?>
                            <span class="badge badge-tipo" style="background:#25d366; color:#fff; padding:4px 12px; border-radius:14px; font-weight:600; font-size:0.98em; letter-spacing:0.5px;"> <?php echo $parceiro['tipo']; ?> </span>
                        <?php endif; ?>
                        <?php
                        // Buscar todas as categorias relacionadas ao parceiro (N:N)
                        $categorias_badges = [];
                        $query_cats = "SELECT c.nome FROM parceiros_categorias pc JOIN categorias c ON pc.categoria_id = c.id WHERE pc.parceiro_id = ? ORDER BY c.nome";
                        $stmt_cats = $conn->prepare($query_cats);
                        $stmt_cats->bind_param("i", $parceiro['id']);
                        $stmt_cats->execute();
                        $result_cats = $stmt_cats->get_result();
                        while ($cat = $result_cats->fetch_assoc()) {
                            $categorias_badges[] = $cat['nome'];
                        }
                        $stmt_cats->close();
                        ?>
                        <?php foreach ($categorias_badges as $cat_nome): ?>
                            <span class="badge badge-categoria" style="background:#ff9800; color:#fff; padding:4px 12px; border-radius:14px; font-weight:600; font-size:0.98em; letter-spacing:0.5px; white-space:normal; word-break:break-word;"> <?php echo $cat_nome; ?> </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Conteúdo do parceiro -->
        <section class="parceiro-content">
            <div class="container">
                <div class="parceiro-perfil-row" style="display: flex; flex-wrap: wrap; gap: 32px;">
                    <!-- Coluna esquerda: Imagem de destaque -->
                    <div class="parceiro-perfil-img" style="flex: 1 1 350px; max-width: 50%; min-width: 300px; display: flex; flex-direction: column; align-items: center;">
                        <?php if (!empty($parceiro['imagem_destaque'])): ?>
                            <img src="uploads/parceiros/destaque/<?php echo $parceiro['imagem_destaque']; ?>" alt="<?php echo $parceiro['nome']; ?>" style="width:100%; max-width:450px; border-radius:12px; box-shadow:0 2px 12px #0001; display:block; margin-bottom:10px;">
                        <?php else: ?>
                            <img src="assets/img/placeholder.jpg" alt="<?php echo $parceiro['nome']; ?>" style="width:100%; max-width:450px; border-radius:12px; box-shadow:0 2px 12px #0001; display:block; margin-bottom:10px;">
                        <?php endif; ?>
                        <!-- Aviso abaixo da imagem -->
                        <div class="parceiro-aviso" style="font-size:0.98em; color:#555; background:#fffbe7; border-left:4px solid #ff9800; padding:8px 14px; border-radius:8px; box-shadow:0 1px 4px #0001; margin-top:10px; margin-bottom:0; width:100%; max-width:400px;">
                            <strong>Aviso:</strong> A AgroNeg exibe perfis de parceiros do agronegócio. Não nos responsabilizamos por informações ou negociações; nosso papel é apenas facilitar o contato.
                        </div>
                    </div>
                    <!-- Coluna direita: Informações de contato -->
                    <div class="parceiro-perfil-info" style="flex: 1 1 350px; max-width: 50%; min-width: 300px;">
                        <div class="sidebar-card">
                            <h3>Informações de Contato</h3>
                            <div class="contato-info">
                                <div class="contato-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Localização:</strong>
                                        <p><?php echo $parceiro['endereco']; ?></p>
                                        <p><?php echo $parceiro['municipio_nome']; ?> - <?php echo $parceiro['estado_sigla']; ?></p>
                                    </div>
                                </div>
                                <?php if (!empty($parceiro['telefone'])): ?>
                                    <div class="contato-item">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <strong>Telefone:</strong>
                                            <p><?php echo $parceiro['telefone']; ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($parceiro['email'])): ?>
                                    <div class="contato-item">
                                        <i class="fas fa-envelope"></i>
                                        <div>
                                            <strong>E-mail:</strong>
                                            <p><a href="mailto:<?php echo $parceiro['email']; ?>"><?php echo $parceiro['email']; ?></a></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (
                                !empty($parceiro['facebook']) || !empty($parceiro['instagram']) || !empty($parceiro['twitter']) ||
                                !empty($parceiro['tiktok']) || !empty($parceiro['youtube']) || !empty($parceiro['website']) || !empty($parceiro['whatsapp'])
                            ): ?>
                                <div class="redes-sociais">
                                    <h4 style="margin-bottom: 8px;">Redes Sociais:</h4>
                                    <div class="redes-links" style="display: flex; gap: 18px; margin-bottom: 18px; align-items: center;">
                                        <?php if (!empty($parceiro['facebook'])): ?>
                                            <a href="<?php echo $parceiro['facebook']; ?>" target="_blank" title="Facebook" style="color:#4267B2; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['instagram'])): ?>
                                            <a href="<?php echo $parceiro['instagram']; ?>" target="_blank" title="Instagram" style="color:#E4405F; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['twitter'])): ?>
                                            <a href="<?php echo $parceiro['twitter']; ?>" target="_blank" title="Twitter" style="color:#1DA1F2; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-twitter"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['tiktok'])): ?>
                                            <a href="<?php echo $parceiro['tiktok']; ?>" target="_blank" title="TikTok" style="color:#000; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-tiktok"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['youtube'])): ?>
                                            <a href="<?php echo $parceiro['youtube']; ?>" target="_blank" title="YouTube" style="color:#FF0000; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-youtube"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['website'])): ?>
                                            <a href="<?php echo $parceiro['website']; ?>" target="_blank" title="Website" style="color:#1a9d64; font-size:1.5em; display:inline-flex; align-items:center;">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($parceiro['whatsapp'])): ?>
                                            <?php $whatsapp_link = preg_replace('/\D+/', '', $parceiro['whatsapp']); ?>
                                            <a href="https://wa.me/<?php echo $whatsapp_link; ?>" target="_blank" title="WhatsApp" style="color:#25d366; font-size:1.7em; display:inline-flex; align-items:center;">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Descrição e galeria abaixo -->
                <div class="parceiro-descricao-galeria" style="margin-top: 48px;">
                    <?php if (!empty($parceiro['descricao'])): ?>
                        <div class="parceiro-descricao">
                            <h2>Sobre <?php echo $parceiro['nome']; ?></h2>
                            <div class="descricao-texto">
                                <?php echo nl2br($parceiro['descricao']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (count($fotos) > 0): ?>
                        <div class="parceiro-galeria">
                            <h2>Galeria de Fotos</h2>
                            <div class="fotos-thumbnails" style="display: flex; flex-wrap: wrap; gap: 16px;">
                                <?php foreach (
                                    $fotos as $i => $foto): ?>
                                    <div class="foto-thumb" style="width: 84px; height: 84px; border-radius: 8px; overflow: hidden; box-shadow:0 1px 6px #0001; cursor:pointer; position:relative; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                                        <img 
                                            src="uploads/parceiros/<?php echo $foto['arquivo']; ?>" 
                                            alt="<?php echo $foto['legenda'] ?? $parceiro['nome']; ?>" 
                                            class="foto-thumb-img"
                                            style="width: 100%; height: 100%; object-fit: contain; background: #f5f5f5; border-radius: 0; transition: transform 0.2s; display: block;"
                                            onclick="abrirModalGaleria('uploads/parceiros/<?php echo $foto['arquivo']; ?>', '<?php echo htmlspecialchars($foto['legenda'] ?? $parceiro['nome'], ENT_QUOTES); ?>', <?php echo $i; ?>)"
                                            onmouseover="this.style.transform='scale(1.07)';"
                                            onmouseout="this.style.transform='scale(1)';"
                                        >
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Modal/Lightbox para galeria -->
                        <div id="modalGaleria" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; overflow:auto;">
                            <span class="modal-galeria-close" onclick="fecharModalGaleria()">&times;</span>
                            <button id="galeria-prev" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;cursor:pointer;z-index:10002;" onclick="navegarGaleria(-1)">
                              <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 10L16 20L24 30" stroke="white" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </button>
                            <button id="galeria-next" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;cursor:pointer;z-index:10002;" onclick="navegarGaleria(1)">
                              <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 10L24 20L16 30" stroke="white" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </button>
                            <div style="display:flex; flex-direction:column; align-items:center;">
                                <img id="modalGaleriaImg" src="" alt="" />
                                <div id="modalGaleriaLegenda" style="color:#fff; margin-top:12px; font-size:1.1em;"></div>
                            </div>
                        </div>
                        <script>
                        // Array das fotos da galeria
                        var galeriaFotos = [
                        <?php foreach ($fotos as $foto): ?>
                            {
                                src: 'uploads/parceiros/<?php echo $foto['arquivo']; ?>',
                                legenda: '<?php echo htmlspecialchars($foto['legenda'] ?? $parceiro['nome'], ENT_QUOTES); ?>'
                            },
                        <?php endforeach; ?>
                        ];
                        var galeriaIndexAtual = 0;

                        function abrirModalGaleria(src, legenda, index) {
                            galeriaIndexAtual = typeof index === 'number' ? index : galeriaFotos.findIndex(f => f.src === src);
                            atualizarModalGaleria();
                            document.getElementById('modalGaleria').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                        function atualizarModalGaleria() {
                            var foto = galeriaFotos[galeriaIndexAtual];
                            var img = document.getElementById('modalGaleriaImg');
                            img.src = foto.src;
                            img.style.transform = 'scale(1)';
                            img.dataset.zoom = '1';
                            img.dataset.offsetX = '0';
                            img.dataset.offsetY = '0';
                            document.getElementById('modalGaleriaLegenda').textContent = foto.legenda;
                            // Esconde seta se for primeira/última
                            document.getElementById('galeria-prev').style.display = galeriaIndexAtual > 0 ? 'block' : 'none';
                            document.getElementById('galeria-next').style.display = galeriaIndexAtual < galeriaFotos.length-1 ? 'block' : 'none';
                        }
                        function navegarGaleria(delta) {
                            galeriaIndexAtual += delta;
                            if (galeriaIndexAtual < 0) galeriaIndexAtual = 0;
                            if (galeriaIndexAtual > galeriaFotos.length-1) galeriaIndexAtual = galeriaFotos.length-1;
                            atualizarModalGaleria();
                        }
                        function fecharModalGaleria() {
                            document.getElementById('modalGaleria').style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }
                        // Fechar com ESC e navegação por teclado
                        document.addEventListener('keydown', function(event) {
                            if (document.getElementById('modalGaleria').style.display === 'flex') {
                                if (event.key === 'Escape') fecharModalGaleria();
                                if (event.key === 'ArrowLeft') navegarGaleria(-1);
                                if (event.key === 'ArrowRight') navegarGaleria(1);
                            }
                        });
                        // Fechar ao clicar fora da imagem
                        document.getElementById('modalGaleria').onclick = function(e) {
                            if (e.target === this) fecharModalGaleria();
                        };
                        // Zoom e arrastar
                        const img = document.getElementById('modalGaleriaImg');
                        let isDragging = false, startX = 0, startY = 0, offsetX = 0, offsetY = 0, zoom = 1;
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
                        document.addEventListener('mousemove', function(e) {
                            if (!isDragging) return;
                            offsetX = e.clientX - startX;
                            offsetY = e.clientY - startY;
                            img.style.transform = `scale(${zoom}) translate(${offsetX}px, ${offsetY}px)`;
                        });
                        document.addEventListener('mouseup', function() {
                            isDragging = false;
                            img.style.cursor = 'grab';
                        });
                        document.getElementById('modalGaleria').addEventListener('scroll', function(e) { e.preventDefault(); });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    
    <?php include __DIR__.'/partials/footer.php'; ?>
    
    <script src="assets/js/header.js"></script>
</body>
</html> 