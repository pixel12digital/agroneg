<?php
// Detectar caminho base para assets
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Detectar se está rodando localmente ou em produção
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$base_path = $is_local ? '/Agroneg/' : '/';
?>
/**
 * Seção "A Melhor Experiência" - Página inicial
 * AgroNeg - Agricultura Conectada
 */
?>
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