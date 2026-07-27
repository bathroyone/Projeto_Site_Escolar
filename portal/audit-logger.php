<?php
/**
 * Sistema de Logs para Auditoria
 * 
 * Registra todas as ações importantes do sistema para fins de auditoria e segurança
 */

class AuditLogger {
    private $logDir;
    private $pdo;
    
    public function __construct($pdo = null) {
        $this->logDir = __DIR__ . '/logs/audit';
        $this->pdo = $pdo;
        
        // Criar diretório de logs se não existir
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Registrar ação de auditoria
     */
    public function log($action, $details = [], $userId = null, $userType = null) {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId ?? ($_SESSION['usuario_id'] ?? 'anonymous'),
            'user_type' => $userType ?? ($_SESSION['tipo_usuario'] ?? 'guest'),
            'action' => $action,
            'details' => $details,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        // Escrever no arquivo de log
        $this->writeToFile($entry);
        
        // Se tiver conexão com banco, também salvar no banco
        if ($this->pdo) {
            $this->writeToDatabase($entry);
        }
    }
    
    /**
     * Escrever log no arquivo
     */
    private function writeToFile($entry) {
        $logFile = $this->logDir . '/audit_' . date('Y-m-d') . '.log';
        $logLine = json_encode($entry) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Escrever log no banco de dados
     */
    private function writeToDatabase($entry) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs 
                (user_id, user_type, action, details, ip_address, user_agent, request_uri, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $entry['user_id'],
                $entry['user_type'],
                $entry['action'],
                json_encode($entry['details']),
                $entry['ip'],
                $entry['user_agent'],
                $entry['request_uri'],
                $entry['timestamp']
            ]);
        } catch (PDOException $e) {
            // Se falhar ao escrever no banco, apenas log no arquivo
            error_log("Erro ao escrever audit log no banco: " . $e->getMessage());
        }
    }
    
    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        return $ip;
    }
    
    /**
     * Buscar logs por período
     */
    public function getLogs($startDate, $endDate, $filters = []) {
        $logFiles = glob($this->logDir . '/audit_*.log');
        $logs = [];
        
        foreach ($logFiles as $file) {
            $fileDate = substr(basename($file), 6, 10);
            
            if ($fileDate >= $startDate && $fileDate <= $endDate) {
                $fileLogs = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($fileLogs as $logLine) {
                    $log = json_decode($logLine, true);
                    
                    if ($this->matchesFilters($log, $filters)) {
                        $logs[] = $log;
                    }
                }
            }
        }
        
        return $logs;
    }
    
    /**
     * Verificar se log corresponde aos filtros
     */
    private function matchesFilters($log, $filters) {
        foreach ($filters as $key => $value) {
            if (isset($log[$key]) && $log[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Limpar logs antigos
     */
    public function cleanOldLogs($days = 90) {
        $files = glob($this->logDir . '/audit_*.log');
        $cutoffDate = date('Y-m-d', strtotime("-$days days"));
        
        foreach ($files as $file) {
            $fileDate = substr(basename($file), 6, 10);
            
            if ($fileDate < $cutoffDate) {
                unlink($file);
            }
        }
    }
}

// Tipos de ações para auditoria
class AuditActions {
    // Login/Logout
    const LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    const LOGIN_FAILED = 'LOGIN_FAILED';
    const LOGOUT = 'LOGOUT';
    
    // Usuários
    const USER_CREATED = 'USER_CREATED';
    const USER_UPDATED = 'USER_UPDATED';
    const USER_DELETED = 'USER_DELETED';
    const USER_PASSWORD_CHANGED = 'USER_PASSWORD_CHANGED';
    
    // Arquivos
    const FILE_UPLOADED = 'FILE_UPLOADED';
    const FILE_DOWNLOADED = 'FILE_DOWNLOADED';
    const FILE_DELETED = 'FILE_DELETED';
    const FILE_UPDATED = 'FILE_UPDATED';
    
    // Turmas
    const TURMA_CREATED = 'TURMA_CREATED';
    const TURMA_UPDATED = 'TURMA_UPDATED';
    const TURMA_DELETED = 'TURMA_DELETED';
    
    // Sistema
    const SYSTEM_CONFIG_CHANGED = 'SYSTEM_CONFIG_CHANGED';
    const BACKUP_CREATED = 'BACKUP_CREATED';
    const BACKUP_RESTORED = 'BACKUP_RESTORED';
    
    // Segurança
    const SECURITY_ALERT = 'SECURITY_ALERT';
    const UNAUTHORIZED_ACCESS = 'UNAUTHORIZED_ACCESS';
    const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
}

// Instância global
$auditLogger = new AuditLogger(isset($pdo) ? $pdo : null);

// Funções helper
function logAudit($action, $details = [], $userId = null, $userType = null) {
    global $auditLogger;
    $auditLogger->log($action, $details, $userId, $userType);
}
?>
