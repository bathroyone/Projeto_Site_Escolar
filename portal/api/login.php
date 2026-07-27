<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$login_field = sanitizeInput($_POST['login_field'] ?? '');
$senha = $_POST['senha'] ?? '';
$tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');

if (empty($login_field) || empty($senha) || empty($tipo_usuario)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Verificar baseado no tipo de usuário
    if ($tipo_usuario === 'professor') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND tipo_usuario = 'professor' AND ativo = TRUE");
        $stmt->execute([$login_field]);
    } elseif ($tipo_usuario === 'aluno') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = ? AND tipo_usuario = 'aluno' AND ativo = TRUE");
        $stmt->execute([$login_field]);
    } elseif ($tipo_usuario === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario_login = ? AND tipo_usuario = 'admin' AND ativo = TRUE");
        $stmt->execute([$login_field]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (email = ? OR matricula = ? OR cpf = ? OR usuario_login = ?) AND ativo = TRUE");
        $stmt->execute([$login_field, $login_field, $login_field, $login_field]);
    }
    
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado. Verifique suas credenciais.']);
        exit();
    }
    
    if (!verifyPassword($senha, $usuario['senha'])) {
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
        exit();
    }
    
    // Login bem-sucedido
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nome'] = $usuario['nome_completo'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
    $_SESSION['turma'] = $usuario['turma'];
    $_SESSION['serie'] = $usuario['serie'];
    
    // Registrar log de auditoria
    if (function_exists('logAudit')) {
        logAudit(AuditActions::LOGIN_SUCCESS, [
            'login_field' => $login_field,
            'tipo_usuario' => $tipo_usuario
        ], $usuario['id'], $tipo_usuario);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'redirect' => 'portal/dashboard.php',
        'user' => [
            'nome' => $usuario['nome_completo'],
            'tipo' => $usuario['tipo_usuario'],
            'email' => $usuario['email']
        ]
    ]);
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao fazer login: ' . $e->getMessage()]);
}
?>
