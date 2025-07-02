document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.getElementById('menu-toggle');
  const closeMenu = document.getElementById('close-menu');
  const mobileMenu = document.getElementById('mobile-menu');
  const header = document.getElementById('site-header');
  
  // Open menu
  menuToggle.addEventListener('click', function() {
    mobileMenu.classList.add('open');
    document.body.classList.add('menu-open');
  });
  
  // Close menu
  closeMenu.addEventListener('click', function() {
    mobileMenu.classList.remove('open');
    document.body.classList.remove('menu-open');
  });
  
  // Close menu when clicking on links (optional)
  const mobileLinks = document.querySelectorAll('.mobile-nav a');
  mobileLinks.forEach(link => {
    link.addEventListener('click', function() {
      mobileMenu.classList.remove('open');
      document.body.classList.remove('menu-open');
    });
  });
  
  // Header shrink effect on scroll (desktop only)
  let scrollPosition = 0;
  const shrinkBreakpoint = 100;
  
  function handleScroll() {
    // Verifica se está em desktop pela largura
    if (window.innerWidth >= 993) {
      scrollPosition = window.scrollY;
      
      if (scrollPosition > shrinkBreakpoint) {
        header.classList.add('shrink');
      } else {
        header.classList.remove('shrink');
      }
    }
  }
  
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', handleScroll);
  
  // Executa ao carregar para verificar a posição inicial
  handleScroll();
}); 