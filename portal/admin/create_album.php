<?php
require_once '../config.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $album_name = sanitizeInput($_POST['album_name'] ?? '');
    
    if (!empty($album_name)) {
        $album_dir = '../album/img/' . $album_name;
        
        if (!is_dir($album_dir)) {
            if (mkdir($album_dir, 0755, true)) {
                header('Location: imagens.php?success=album_created');
                exit();
            } else {
                header('Location: imagens.php?error=album_creation_failed');
                exit();
            }
        } else {
            header('Location: imagens.php?error=album_exists');
            exit();
        }
    }
}

header('Location: imagens.php');
?>
