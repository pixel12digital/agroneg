<?php
$current = basename($_SERVER['PHP_SELF']);

// Detectar caminho base para assets
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Detectar se está rodando localmente ou em produção
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$base_path = $is_local ? '/Agroneg/' : '/';
?>
<header id="site-header">
  <div class="container">
    <div class="logo">
      <a href="<?php echo $base_path; ?>">
        <img src="<?php echo $base_path; ?>assets/img/logo-agroneg.png" alt="AgroNeg">
      </a>
    </div>
    
    <nav class="desktop-nav">
      <ul>
        <li><a href="<?php echo $base_path; ?>" class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="<?php echo $base_path; ?>produtores" class="<?php echo ($current == 'produtores.php') ? 'active' : ''; ?>">Produtores</a></li>
        <li><a href="<?php echo $base_path; ?>criadores" class="<?php echo ($current == 'criadores.php') ? 'active' : ''; ?>">Criadores</a></li>
        <li><a href="<?php echo $base_path; ?>veterinarios" class="<?php echo ($current == 'veterinarios.php') ? 'active' : ''; ?>">Veterinários</a></li>
        <li><a href="<?php echo $base_path; ?>lojas-agropet" class="<?php echo ($current == 'lojas-agropet.php') ? 'active' : ''; ?>">Lojas Agropet</a></li>
        <li><a href="<?php echo $base_path; ?>cooperativas" class="<?php echo ($current == 'cooperativas.php') ? 'active' : ''; ?>">Cooperativas</a></li>
        <li><a href="<?php echo $base_path; ?>eventos" class="<?php echo ($current == 'eventos.php') ? 'active' : ''; ?>">Eventos</a></li>
      </ul>
    </nav>
    
    <div class="contact-btn-wrapper">
      <a href="<?php echo $base_path; ?>contato" class="contact-btn <?php echo ($current == 'contato.php') ? 'active' : ''; ?>">Contato</a>
    </div>
    
    <div class="mobile-menu-toggle">
      <button id="menu-toggle" aria-label="Abrir menu" type="button">
        <span class="menu-icon">☰</span>
      </button>
    </div>
  </div>
  
  <div id="mobile-menu" class="mobile-menu">
    <button id="close-menu" aria-label="Fechar menu" type="button">
      <span class="close-icon">×</span>
    </button>
    <nav class="mobile-nav">
      <ul>
        <li><a href="<?php echo $base_path; ?>" class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="<?php echo $base_path; ?>produtores" class="<?php echo ($current == 'produtores.php') ? 'active' : ''; ?>">Produtores</a></li>
        <li><a href="<?php echo $base_path; ?>criadores" class="<?php echo ($current == 'criadores.php') ? 'active' : ''; ?>">Criadores</a></li>
        <li><a href="<?php echo $base_path; ?>veterinarios" class="<?php echo ($current == 'veterinarios.php') ? 'active' : ''; ?>">Veterinários</a></li>
        <li><a href="<?php echo $base_path; ?>lojas-agropet" class="<?php echo ($current == 'lojas-agropet.php') ? 'active' : ''; ?>">Lojas Agropet</a></li>
        <li><a href="<?php echo $base_path; ?>cooperativas" class="<?php echo ($current == 'cooperativas.php') ? 'active' : ''; ?>">Cooperativas</a></li>
        <li><a href="<?php echo $base_path; ?>eventos" class="<?php echo ($current == 'eventos.php') ? 'active' : ''; ?>">Eventos</a></li>
        <li><a href="<?php echo $base_path; ?>contato" class="<?php echo ($current == 'contato.php') ? 'active' : ''; ?>">Contato</a></li>
      </ul>
    </nav>
  </div>
</header>