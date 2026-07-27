<?php
/**
 * Script de Backup Automático do Banco de Dados
 * 
 * Este script deve ser executado via cron job diariamente
 * Uso: php backup-database.php
 */

// Configurações
require_once __DIR__ . '/../portal/config.php';

// Configurações de backup
define('BACKUP_DIR', __DIR__ . '/../backups');
define('BACKUP_RETENTION_DAYS', 30); // Manter backups por 30 dias
define('BACKUP_PREFIX', 'backup_escola_');

// Criar diretório de backups se não existir
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

/**
 * Função para gerar nome do arquivo de backup
 */
function generateBackupFilename() {
    return BACKUP_PREFIX . date('Y-m-d_H-i-s') . '.sql';
}

/**
 * Função para fazer backup do banco de dados
 */
function backupDatabase() {
    $filename = generateBackupFilename();
    $filepath = BACKUP_DIR . '/' . $filename;
    
    // Comando mysqldump
    $command = sprintf(
        'mysqldump -h%s -u%s -p%s %s > %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $filepath
    );
    
    // Executar comando
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception("Erro ao executar mysqldump. Código: $returnCode");
    }
    
    // Comprimir backup
    compressBackup($filepath);
    
    return $filepath . '.gz';
}

/**
 * Função para comprimir backup
 */
function compressBackup($filepath) {
    $gzFile = $filepath . '.gz';
    
    $fp = fopen($filepath, 'rb');
    $gz = gzopen($gzFile, 'wb9');
    
    while (!feof($fp)) {
        gzwrite($gz, fread($fp, 8192));
    }
    
    fclose($fp);
    gzclose($gz);
    
    // Remover arquivo original
    unlink($filepath);
    
    return $gzFile;
}

/**
 * Função para limpar backups antigos
 */
function cleanOldBackups() {
    $files = glob(BACKUP_DIR . '/' . BACKUP_PREFIX . '*.sql.gz');
    $now = time();
    
    foreach ($files as $file) {
        $fileTime = filemtime($file);
        $age = ($now - $fileTime) / (60 * 60 * 24); // Idade em dias
        
        if ($age > BACKUP_RETENTION_DAYS) {
            unlink($file);
            echo "Backup antigo removido: $file\n";
        }
    }
}

/**
 * Função para log do backup
 */
function logBackup($status, $message = '') {
    $logFile = BACKUP_DIR . '/backup.log';
    $logEntry = sprintf(
        "[%s] Status: %s | Message: %s\n",
        date('Y-m-d H:i:s'),
        $status,
        $message
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Função para enviar email de notificação
 */
function sendBackupNotification($status, $message = '') {
    $to = 'admin@escola.com.br';
    $subject = "Backup do Banco de Dados - $status";
    $body = "Status do backup: $status\n\n";
    $body .= "Data: " . date('Y-m-d H:i:s') . "\n";
    $body .= "Banco de dados: " . DB_NAME . "\n";
    
    if ($message) {
        $body .= "\nDetalhes:\n$message";
    }
    
    $headers = "From: backup@escola.com.br\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    mail($to, $subject, $body, $headers);
}

// Executar backup
try {
    echo "Iniciando backup do banco de dados...\n";
    
    $backupFile = backupDatabase();
    $fileSize = filesize($backupFile);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    
    echo "Backup concluído com sucesso!\n";
    echo "Arquivo: $backupFile\n";
    echo "Tamanho: {$fileSizeMB} MB\n";
    
    // Limpar backups antigos
    cleanOldBackups();
    echo "Backups antigos removidos.\n";
    
    // Log do backup
    logBackup('SUCCESS', "Backup criado: $backupFile ({$fileSizeMB} MB)");
    
    // Enviar notificação (opcional)
    // sendBackupNotification('SUCCESS', "Backup criado: $backupFile ({$fileSizeMB} MB)");
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    echo "Erro ao fazer backup: $errorMessage\n";
    
    // Log do erro
    logBackup('ERROR', $errorMessage);
    
    // Enviar notificação de erro
    sendBackupNotification('ERROR', $errorMessage);
    
    exit(1);
}

echo "Processo de backup finalizado.\n";
?>
