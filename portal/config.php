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
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mudar para 1 em produção com HTTPS
    session_start();
}

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

// Função para verificar se é secretaria
function isSecretaria() {
    return isLoggedIn() && getUserType() === 'secretaria';
}

// Função para redirecionar se não for aluno
function requireAluno() {
    requireLogin();
    if (!isAluno() && !isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Função para redirecionar se não for secretaria
function requireSecretaria() {
    requireLogin();
    if (!isSecretaria() && !isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Função para redirecionar se não for aluno

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

// Função para registrar log de auditoria
function logAudit($acao, $tabela = null, $registroId = null, $dadosAntigos = null, $dadosNovos = null) {
    try {
        $pdo = getDBConnection();
        
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $usuarioNome = $_SESSION['nome'] ?? 'Sistema';
        $usuarioTipo = $_SESSION['tipo_usuario'] ?? 'sistema';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (usuario_id, usuario_nome, usuario_tipo, acao, tabela, registro_id, dados_antigos, dados_novos, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $usuarioId,
            $usuarioNome,
            $usuarioTipo,
            $acao,
            $tabela,
            $registroId,
            $dadosAntigos ? json_encode($dadosAntigos) : null,
            $dadosNovos ? json_encode($dadosNovos) : null,
            $ip,
            $userAgent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar log de auditoria: " . $e->getMessage());
        return false;
    }
}

// Função para obter logs de auditoria
function getAuditLogs($limit = 100, $offset = 0, $filtros = []) {
    try {
        $pdo = getDBConnection();
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['usuario_id'])) {
            $where[] = "usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        if (!empty($filtros['acao'])) {
            $where[] = "acao = ?";
            $params[] = $filtros['acao'];
        }
        
        if (!empty($filtros['tabela'])) {
            $where[] = "tabela = ?";
            $params[] = $filtros['tabela'];
        }
        
        if (!empty($filtros['data_inicio'])) {
            $where[] = "created_at >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $where[] = "created_at <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM audit_logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter logs de auditoria: " . $e->getMessage());
        return [];
    }
}

// Função para obter total de logs de auditoria
function getAuditLogsCount($filtros = []) {
    try {
        $pdo = getDBConnection();
        
        $where = [];
        $params = [];
        
        if (!empty($filtros['usuario_id'])) {
            $where[] = "usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        if (!empty($filtros['acao'])) {
            $where[] = "acao = ?";
            $params[] = $filtros['acao'];
        }
        
        if (!empty($filtros['tabela'])) {
            $where[] = "tabela = ?";
            $params[] = $filtros['tabela'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT COUNT(*) as total FROM audit_logs $whereClause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erro ao contar logs de auditoria: " . $e->getMessage());
        return 0;
    }
}

// Função para verificar se usuário tem permissão
function hasPermission($codigoPermissao) {
    try {
        $pdo = getDBConnection();
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        
        if (!$usuarioId) {
            return false;
        }
        
        // Verificar se é Super Admin (tem todas as permissões)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM usuario_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.usuario_id = ? AND r.nome = 'Super Admin'
        ");
        $stmt->execute([$usuarioId]);
        
        if ($stmt->fetch()['total'] > 0) {
            return true;
        }
        
        // Verificar permissão específica
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM usuario_roles ur 
            JOIN role_permissoes rp ON ur.role_id = rp.role_id 
            JOIN permissoes p ON rp.permissao_id = p.id 
            WHERE ur.usuario_id = ? AND p.codigo = ? AND p.ativo = 1
        ");
        $stmt->execute([$usuarioId, $codigoPermissao]);
        
        return $stmt->fetch()['total'] > 0;
    } catch (PDOException $e) {
        error_log("Erro ao verificar permissão: " . $e->getMessage());
        return false;
    }
}

// Função para obter permissões do usuário
function getUserPermissions($usuarioId) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.* 
            FROM usuario_roles ur 
            JOIN role_permissoes rp ON ur.role_id = rp.role_id 
            JOIN permissoes p ON rp.permissao_id = p.id 
            WHERE ur.usuario_id = ? AND p.ativo = 1
            ORDER BY p.modulo, p.nome
        ");
        $stmt->execute([$usuarioId]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter permissões: " . $e->getMessage());
        return [];
    }
}

// Função para obter roles do usuário
function getUserRoles($usuarioId) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT r.* 
            FROM usuario_roles ur 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.usuario_id = ? AND r.ativo = 1
            ORDER BY r.nivel DESC
        ");
        $stmt->execute([$usuarioId]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter roles: " . $e->getMessage());
        return [];
    }
}

// Função para atribuir role ao usuário
function assignRole($usuarioId, $roleId) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("INSERT INTO usuario_roles (usuario_id, role_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id = role_id");
        $stmt->execute([$usuarioId, $roleId]);
        
        logAudit('ROLE_ASSIGN', 'usuario_roles', $usuarioId, null, ['role_id' => $roleId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao atribuir role: " . $e->getMessage());
        return false;
    }
}

// Função para remover role do usuário
function removeRole($usuarioId, $roleId) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("DELETE FROM usuario_roles WHERE usuario_id = ? AND role_id = ?");
        $stmt->execute([$usuarioId, $roleId]);
        
        logAudit('ROLE_REMOVE', 'usuario_roles', $usuarioId, ['role_id' => $roleId], null);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao remover role: " . $e->getMessage());
        return false;
    }
}

// Função para redirecionar se não tiver permissão
function requirePermission($codigoPermissao) {
    if (!hasPermission($codigoPermissao)) {
        header('Location: dashboard.php');
        exit();
    }
}
?>
