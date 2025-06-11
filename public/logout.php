<?php
// Iniciar sessão se não estiver iniciada
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: login.php?logout=1");
exit;
?> 