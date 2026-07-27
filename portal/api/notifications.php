<?php
require_once '../config.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

$usuarioId = $_SESSION['usuario_id'];
$lastCheck = $_GET['last_check'] ?? 0;

try {
    $pdo = getDBConnection();
    
    // Buscar notificações não lidas
    $stmt = $pdo->prepare("
        SELECT * FROM notificacoes 
        WHERE usuario_id = ? AND lida = FALSE 
        ORDER BY data_criacao DESC 
        LIMIT 10
    ");
    $stmt->execute([$usuarioId]);
    $notifications = $stmt->fetchAll();
    
    // Buscar notificações novas desde o último check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notificacoes 
        WHERE usuario_id = ? AND lida = FALSE 
        AND UNIX_TIMESTAMP(data_criacao) > ?
    ");
    $stmt->execute([$usuarioId, $lastCheck]);
    $newCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'new_count' => $newCount,
        'total_unread' => count($notifications),
        'timestamp' => time()
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao buscar notificações']);
}
?>
