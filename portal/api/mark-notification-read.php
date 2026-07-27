<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    Exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'] ?? null;

if (!$notificationId) {
    echo json_encode(['success' => false, 'error' => 'ID da notificação não fornecido']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Verificar se a notificação pertence ao usuário
    $stmt = $pdo->prepare("SELECT usuario_id FROM notificacoes WHERE id = ?");
    $stmt->execute([$notificationId]);
    $notification = $stmt->fetch();

    if (!$notification || $notification['usuario_id'] != $_SESSION['usuario_id']) {
        echo json_encode(['success' => false, 'error' => 'Notificação não encontrada']);
        exit();
    }

    // Marcar como lida
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = TRUE WHERE id = ?");
    $stmt->execute([$notificationId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao marcar notificação']);
}
?>
