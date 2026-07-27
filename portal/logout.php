<?php
require_once 'config.php';

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para a página principal
header('Location: ../index.php');
exit();
?>
