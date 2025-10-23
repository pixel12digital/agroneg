<?php
// Configurações padrão (sem tentar conectar ao banco)
$telefone = '(85) 99999-9999';
$email = 'contato@agroneg.com.br';
$whatsapp = '(85) 99999-9999';
$instagram_url = '';
$facebook_url = '';
$tiktok_url = '';
?>
<footer id="site-footer">
    <div class="footer-content">
        <!-- Coluna 1: Logo e Descrição -->
        <div class="footer-col">
            <img src="/Agroneg/assets/img/logo-agroneg.png" alt="AgroNeg" class="footer-logo">
            <p class="footer-description">
                O AgroNeg é a vitrine digital que aproxima produtores, criadores, veterinários e cooperativas do Nordeste dos seus clientes. Uma plataforma única, fácil de usar e sempre atualizada.
            </p>
            <p class="footer-description">
                Produções agrícolas, criações animais, serviços veterinários e cooperação em um só lugar: deixe seu município em evidência.
            </p>
        </div>
        
        <!-- Coluna 2: Institucional -->
        <div class="footer-col">
            <h3 class="footer-title">Institucional</h3>
            <ul class="footer-nav">
                <li><a href="index.php">Home</a></li>
                <li><a href="sobre.php">Sobre</a></li>
                <li><a href="produtores.php">Produtores</a></li>
                <li><a href="criadores.php">Criadores</a></li>
                <li><a href="veterinarios.php">Veterinários</a></li>
                <li><a href="cooperativas.php">Cooperativas</a></li>
                <li><a href="eventos.php">Eventos</a></li>
                <li><a href="contato.php">Contato</a></li>
            </ul>
        </div>
        
        <!-- Coluna 3: Contato -->
        <div class="footer-col">
            <h3 class="footer-title">Contato</h3>
            
            <!-- Telefone -->
            <div class="footer-contact-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#ea6d36" style="margin-right: 12px;">
                    <path d="M20 10.999h2c0-4.5-3.5-8-8-8v2c3.309 0 6 2.691 6 6z"></path>
                    <path d="M13 7.999v-2c-2.5 0-4.5 2-4.5 4.5h2c0-1.4 1.1-2.5 2.5-2.5z"></path>
                    <path d="M19.23 15.26l-2.54-.29c-.61-.07-1.21.14-1.64.57l-1.84 1.84c-2.83-1.44-5.15-3.75-6.59-6.59l1.85-1.85c.43-.43.64-1.03.57-1.64l-.29-2.52c-.12-1.01-.97-1.77-1.99-1.77h-1.73c-1.13 0-2.07.94-2 2.07.53 8.54 7.36 15.36 15.89 15.89 1.13.07 2.07-.87 2.07-2v-1.73c.01-1.01-.75-1.86-1.76-1.98z"></path>
                </svg>
                <span><?php echo htmlspecialchars($telefone); ?></span>
            </div>
            
            <!-- E-mail -->
            <div class="footer-contact-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#ea6d36" style="margin-right: 12px;">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-.4 4.25l-7.07 4.42c-.32.2-.74.2-1.06 0L4.4 8.25c-.25-.16-.4-.43-.4-.72 0-.67.73-1.07 1.3-.72L12 11l6.7-4.19c.57-.35 1.3.05 1.3.72 0 .29-.15.56-.4.72z"></path>
                </svg>
                <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
            </div>
            
            <!-- WhatsApp -->
            <div class="footer-contact-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#ea6d36" style="margin-right: 12px;">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564c.173.087.288.131.332.202.043.72.043.433-.101.824z"></path>
                    <path d="M12 1c-6.103 0-11 4.896-11 11 0 1.86.463 3.614 1.279 5.153l-1.267 4.637 4.805-1.26c1.477.706 3.13 1.108 4.896 1.108 3.706 0 7.031-1.74 9.178-4.449 2.147-2.709 3.002-6.2 2.27-9.612-1.079-5.01-5.501-8.557-10.725-8.557z" fill="none" stroke="#ea6d36" stroke-width="1.5"></path>
                </svg>
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $whatsapp); ?>?text=Olá,%20visitei%20o%20site%20AGRONEG%20e%20gostaria%20de%20mais%20informações" target="_blank"><?php echo htmlspecialchars($whatsapp); ?></a>
            </div>
            
            <!-- CTA para Prefeituras -->
            <p class="footer-cta">
                Cadastre sua prefeitura e coloque sua cidade em destaque agora mesmo!
            </p>
            
            <!-- Redes Sociais -->
            <h4 class="social-title">Conheça nossas redes sociais:</h4>
            <div class="social-links" style="display: flex; gap: 16px; align-items: center;">
                <!-- Facebook -->
                <?php if ($facebook_url): ?>
                    <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" style="display: inline-flex; align-items: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.408.595 24 1.325 24h11.495v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.408 24 22.674V1.326C24 .592 23.406 0 22.675 0"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <span style="display: inline-flex; align-items: center; opacity: 0.5; cursor: default;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.408.595 24 1.325 24h11.495v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.408 24 22.674V1.326C24 .592 23.406 0 22.675 0"/>
                        </svg>
                    </span>
                <?php endif; ?>
                <!-- Instagram -->
                <?php if ($instagram_url): ?>
                    <a href="<?php echo htmlspecialchars($instagram_url); ?>" target="_blank" style="display: inline-flex; align-items: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.974.974 1.246 2.241 1.308 3.608.058 1.266.069 1.646.069 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.974.974-2.241 1.246-3.608 1.308-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.974-.974-1.246-2.241-1.308-3.608C2.175 15.747 2.163 15.367 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608.974-.974 2.241-1.246 3.608-1.308C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.013 7.052.072 5.775.131 4.602.425 3.635 1.392 2.668 2.359 2.374 3.532 2.315 4.809 2.256 6.089 2.243 6.498 2.243 12c0 5.502.013 5.911.072 7.191.059 1.277.353 2.45 1.32 3.417.967.967 2.14 1.261 3.417 1.32 1.28.059 1.689.072 7.191.072s5.911-.013 7.191-.072c1.277-.059 2.45-.353 3.417-1.32.967-.967 1.261-2.14 1.32-3.417.059-1.28.072-1.689.072-7.191s-.013-5.911-.072-7.191c-.059-1.277-.353-2.45-1.32-3.417C21.05.425 19.877.131 18.6.072 17.32.013 16.911 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a3.999 3.999 0 1 1 0-7.998 3.999 3.999 0 0 1 0 7.998zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <span style="display: inline-flex; align-items: center; opacity: 0.5; cursor: default;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.974.974 1.246 2.241 1.308 3.608.058 1.266.069 1.646.069 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.974.974-2.241 1.246-3.608 1.308-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.974-.974-1.246-2.241-1.308-3.608C2.175 15.747 2.163 15.367 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608.974-.974 2.241-1.246 3.608-1.308C8.416 2.175 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.332.013 7.052.072 5.775.131 4.602.425 3.635 1.392 2.668 2.359 2.374 3.532 2.315 4.809 2.256 6.089 2.243 6.498 2.243 12c0 5.502.013 5.911.072 7.191.059 1.277.353 2.45 1.32 3.417.967.967 2.14 1.261 3.417 1.32 1.28.059 1.689.072 7.191.072s5.911-.013 7.191-.072c1.277-.059 2.45-.353 3.417-1.32.967-.967 1.261-2.14 1.32-3.417.059-1.28.072-1.689.072-7.191s-.013-5.911-.072-7.191c-.059-1.277-.353-2.45-1.32-3.417C21.05.425 19.877.131 18.6.072 17.32.013 16.911 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a3.999 3.999 0 1 1 0-7.998 3.999 3.999 0 0 1 0 7.998zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                        </svg>
                    </span>
                <?php endif; ?>
                <!-- TikTok -->
                <?php if ($tiktok_url): ?>
                    <a href="<?php echo htmlspecialchars($tiktok_url); ?>" target="_blank" style="display: inline-flex; align-items: center;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.75 2h2.25v12.25a2.25 2.25 0 1 1-2.25-2.25h.25V10h-.25a4.25 4.25 0 1 0 4.25 4.25V8.5a5.5 5.5 0 0 0 5.5 5.5V12a3.75 3.75 0 0 1-3.75-3.75V2h-6.25z"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <span style="display: inline-flex; align-items: center; opacity: 0.5; cursor: default;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ea6d36" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.75 2h2.25v12.25a2.25 2.25 0 1 1-2.25-2.25h.25V10h-.25a4.25 4.25 0 1 0 4.25 4.25V8.5a5.5 5.5 0 0 0 5.5 5.5V12a3.75 3.75 0 0 1-3.75-3.75V2h-6.25z"/>
                        </svg>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Copyright -->
    <div class="footer-bottom">
        <p class="copyright">© <?php echo date('Y'); ?> AgroNeg. Todos os direitos reservados. Criado por <a href="https://pixel12digital.com" target="_blank">Pixel12Digital</a></p>
    </div>
</footer>
