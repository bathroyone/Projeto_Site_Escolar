<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Marcar todas as notificações do usuário como lidas
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = TRUE WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao marcar notificações']);
}
?>
