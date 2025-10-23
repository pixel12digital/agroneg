<?php
/**
 * Manipulador de URLs amigáveis - Fallback caso .htaccess não funcione
 */

// Verificar se a URL é uma URL amigável
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Remover query string da URI
$path = parse_url($request_uri, PHP_URL_PATH);

// Log para debug
error_log("URL Handler - Path: $path");

// Verificar se é uma URL amigável (não termina em .php e não é um arquivo)
if (!preg_match('/\.php$/', $path) && !preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|ico|pdf)$/', $path)) {
    
    // Padrões de URL amigável
    $patterns = [
        // Municípios: /pb/barra-de-sao-miguel
        '/^\/?([a-z]{2})\/([a-z0-9-]+)\/?$/',
        
        // Páginas de parceiros: /produtores/pb/barra-de-sao-miguel
        '/^\/?(produtores|criadores|veterinarios|lojas-agropet|cooperativas)\/([a-z]{2})\/([a-z0-9-]+)\/?$/',
        
        // Parceiros individuais: /parceiro/fazenda-sao-joao
        '/^\/?parceiro\/([a-z0-9-]+)\/?$/',
        
        // Eventos: /eventos/pb/barra-de-sao-miguel
        '/^\/?eventos\/([a-z]{2})\/([a-z0-9-]+)\/?$/',
        
        // Páginas simples: /produtores, /eventos
        '/^\/?(produtores|criadores|veterinarios|lojas-agropet|cooperativas|eventos)\/?$/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $path, $matches)) {
            error_log("URL Handler - Match found: " . json_encode($matches));
            
            // Redirecionar para o arquivo PHP apropriado com parâmetros
            $params = [];
            
            if (count($matches) == 3) {
                // Município: /pb/barra-de-sao-miguel
                $params['slug_estado'] = $matches[1];
                $params['slug_municipio'] = $matches[2];
                $target = 'municipio.php';
            } elseif (count($matches) == 4) {
                // Página de parceiros: /produtores/pb/barra-de-sao-miguel
                $params['slug_estado'] = $matches[2];
                $params['slug_municipio'] = $matches[3];
                $target = $matches[1] . '.php';
            } elseif (count($matches) == 2 && $matches[1] !== 'parceiro') {
                // Página simples: /produtores, /eventos
                $target = $matches[1] . '.php';
            } elseif (count($matches) == 2 && $matches[1] == 'parceiro') {
                // Parceiro individual: /parceiro/fazenda-sao-joao
                $params['slug'] = $matches[2];
                $target = 'parceiro.php';
            }
            
            // Adicionar parâmetros da query string
            $query_string = $_SERVER['QUERY_STRING'] ?? '';
            if ($query_string) {
                parse_str($query_string, $query_params);
                $params = array_merge($params, $query_params);
            }
            
            error_log("URL Handler - Redirecting to: $target with params: " . json_encode($params));
            
            // Redirecionar internamente
            $_GET = $params;
            include $target;
            exit;
        }
    }
    
    error_log("URL Handler - No pattern matched for: $path");
}

// Se não for uma URL amigável, mostrar 404 ou redirecionar para home
error_log("URL Handler - URL não reconhecida: $path");

// Tentar redirecionar para home se for uma URL que parece ser do site
if (preg_match('/^\/[a-z]{2}\/index\.php/', $path)) {
    // URL como /pb/index.php - redirecionar para home
    header('Location: /');
    exit;
}

http_response_code(404);
echo "Página não encontrada";
?>
