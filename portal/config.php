<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'escola_gestao');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações do Sistema
define('SITE_URL', 'http://localhost/Projeto_Site_Escolar/portal');
define('SITE_NAME', 'Portal de Gestão Escolar');

// Configurações de Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm']);

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mudar para 1 em produção com HTTPS
session_start();

// Função de conexão com o banco de dados
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
        die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
    }
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

// Função para verificar o tipo de usuário
function getUserType() {
    return $_SESSION['tipo_usuario'] ?? null;
}

// Função para verificar se é admin
function isAdmin() {
    return isLoggedIn() && getUserType() === 'admin';
}

// Função para verificar se é professor
function isProfessor() {
    return isLoggedIn() && getUserType() === 'professor';
}

// Função para verificar se é aluno
function isAluno() {
    return isLoggedIn() && getUserType() === 'aluno';
}

// Função para redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Função para redirecionar se não for admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Função para redirecionar se não for professor
function requireProfessor() {
    requireLogin();
    if (!isProfessor() && !isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Função para redirecionar se não for aluno
function requireAluno() {
    requireLogin();
    if (!isAluno() && !isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Função para sanitizar entrada
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Função para fazer upload de arquivo
function uploadFile($file, $subdir = '') {
    $targetDir = UPLOAD_DIR . $subdir;
    
    // Criar diretório se não existir
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validar extensão
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    // Validar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Arquivo muito grande (máximo 10MB)'];
    }
    
    // Gerar nome único
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $subdir . $fileName, 'original_name' => $file['name']];
    } else {
        return ['success' => false, 'message' => 'Erro ao fazer upload do arquivo'];
    }
}

// Função para criar notificação
function createNotification($usuarioId, $titulo, $mensagem, $tipo, $link = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo_notificacao, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$usuarioId, $titulo, $mensagem, $tipo, $link]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar notificação: " . $e->getMessage());
        return false;
    }
}

// Função para obter notificações não lidas
function getUnreadNotifications($usuarioId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM notificacoes WHERE usuario_id = ? AND lida = FALSE ORDER BY data_criacao DESC LIMIT 10");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter notificações: " . $e->getMessage());
        return [];
    }
}

// Função para marcar notificação como lida
function markNotificationAsRead($notificacaoId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = TRUE WHERE id = ?");
        $stmt->execute([$notificacaoId]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
        return false;
    }
}
?>
