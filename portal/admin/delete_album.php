<?php
require_once '../config.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $album_id = sanitizeInput($_GET['id'] ?? '');
    
    if (!empty($album_id)) {
        $album_dir = '../album/img/' . $album_id;
        
        if (is_dir($album_dir)) {
            // Remover todas as fotos do álbum
            $files = glob($album_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            // Remover o diretório
            if (rmdir($album_dir)) {
                header('Location: imagens.php?success=album_deleted');
                exit();
            } else {
                header('Location: imagens.php?error=album_deletion_failed');
                exit();
            }
        } else {
            header('Location: imagens.php?error=album_not_found');
            exit();
        }
    }
}

header('Location: imagens.php');
?>
