<?php
require_once 'config.php';

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header('Location: login.php');
exit();
?>
