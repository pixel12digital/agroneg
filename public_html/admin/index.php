<?php
// Arquivo de redirecionamento para o dashboard
session_start();
if (isset($_SESSION["logado"]) && $_SESSION["logado"] === true) {
    header("Location: dashboard.php");
} else {
    header("Location: ../login.php");
}
exit;
?> 