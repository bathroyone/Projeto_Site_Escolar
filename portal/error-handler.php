<?php
// Sistema de Tratamento de Erros

// Configuração de exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros em produção
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Função para tratamento de erros
function handleError($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];

    $error_type = $error_types[$errno] ?? 'Unknown Error';
    
    $message = sprintf(
        "[%s] %s: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $error_type,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($message);
    
    // Em produção, não exibir detalhes do erro
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        showErrorPage('Ocorreu um erro interno. Por favor, tente novamente mais tarde.');
    }
    
    return true;
}

// Função para tratamento de exceções
function handleException($exception) {
    $message = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($message);
    showErrorPage('Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.');
}

// Função para tratamento de erros fatais
function handleShutdown() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = sprintf(
            "[%s] Fatal Error: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        error_log($message);
        showErrorPage('Ocorreu um erro fatal. Por favor, contate o suporte.');
    }
}

// Função para exibir página de erro
function showErrorPage($message = 'Ocorreu um erro.') {
    if (headers_sent()) {
        echo "<div style='color: red; padding: 20px; text-align: center;'>$message</div>";
    } else {
        http_response_code(500);
        require_once __DIR__ . '/error-page.php';
    }
    exit();
}

// Função para retornar erro JSON (para AJAX)
function returnJsonError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

// Função para log de ações do usuário
function logUserAction($action, $details = []) {
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/user_actions.log';
    $user_id = $_SESSION['usuario_id'] ?? 'anonymous';
    $user_type = $_SESSION['tipo_usuario'] ?? 'guest';
    
    $log_entry = sprintf(
        "[%s] User ID: %s | Type: %s | Action: %s | Details: %s | IP: %s\n",
        date('Y-m-d H:i:s'),
        $user_id,
        $user_type,
        $action,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Registrar handlers
set_error_handler('handleError');
set_exception_handler('handleException');
register_shutdown_function('handleShutdown');
?>
