<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<header id="site-header">
  <div class="container">
    <div class="logo">
      <a href="index.php">
        <img src="assets/img/logo-agroneg.png" alt="AgroNeg">
      </a>
    </div>
    
    <nav class="desktop-nav">
      <ul>
        <li><a href="index.php" class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="produtores.php" class="<?php echo ($current == 'produtores.php') ? 'active' : ''; ?>">Produtores</a></li>
        <li><a href="criadores.php" class="<?php echo ($current == 'criadores.php') ? 'active' : ''; ?>">Criadores</a></li>
        <li><a href="veterinarios.php" class="<?php echo ($current == 'veterinarios.php') ? 'active' : ''; ?>">Veterinários</a></li>
        <li><a href="lojas-agropet.php" class="<?php echo ($current == 'lojas-agropet.php') ? 'active' : ''; ?>">Lojas Agropet</a></li>
        <li><a href="cooperativas.php" class="<?php echo ($current == 'cooperativas.php') ? 'active' : ''; ?>">Cooperativas</a></li>
        <li><a href="eventos.php" class="<?php echo ($current == 'eventos.php') ? 'active' : ''; ?>">Eventos</a></li>
      </ul>
    </nav>
    
    <div class="contact-btn-wrapper">
      <a href="contato.php" class="contact-btn <?php echo ($current == 'contato.php') ? 'active' : ''; ?>">Contato</a>
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
        <li><a href="index.php" class="<?php echo ($current == 'index.php') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="produtores.php" class="<?php echo ($current == 'produtores.php') ? 'active' : ''; ?>">Produtores</a></li>
        <li><a href="criadores.php" class="<?php echo ($current == 'criadores.php') ? 'active' : ''; ?>">Criadores</a></li>
        <li><a href="veterinarios.php" class="<?php echo ($current == 'veterinarios.php') ? 'active' : ''; ?>">Veterinários</a></li>
        <li><a href="lojas-agropet.php" class="<?php echo ($current == 'lojas-agropet.php') ? 'active' : ''; ?>">Lojas Agropet</a></li>
        <li><a href="cooperativas.php" class="<?php echo ($current == 'cooperativas.php') ? 'active' : ''; ?>">Cooperativas</a></li>
        <li><a href="eventos.php" class="<?php echo ($current == 'eventos.php') ? 'active' : ''; ?>">Eventos</a></li>
        <li><a href="contato.php" class="<?php echo ($current == 'contato.php') ? 'active' : ''; ?>">Contato</a></li>
      </ul>
    </nav>
  </div>
</header> 