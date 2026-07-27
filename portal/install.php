<?php
// Script de instalação do banco de dados
// Execute este arquivo no navegador: portal/install.php

// Configurações temporárias para instalação
$install_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];

$errors = [];
$success = [];

try {
    // Conectar ao MySQL sem selecionar banco
    $dsn = "mysql:host={$install_config['host']};charset={$install_config['charset']}";
    $pdo = new PDO($dsn, $install_config['user'], $install_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Criar banco de dados
    $pdo->exec("CREATE DATABASE IF NOT EXISTS escola_gestao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $success[] = "Banco de dados 'escola_gestao' criado com sucesso!";
    
    // Selecionar o banco de dados
    $pdo->exec("USE escola_gestao");
    
    // Ler o arquivo schema.sql
    $schema_file = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Arquivo schema.sql não encontrado em: " . $schema_file);
    }
    
    $sql = file_get_contents($schema_file);
    
    // Remover comandos CREATE DATABASE e USE do schema (já estamos no banco correto)
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS \w+ CHARACTER SET [\w]+ COLLATE [\w_]+;/', '', $sql);
    $sql = preg_replace('/USE\s+\w+;/', '', $sql);
    
    // Separar comandos SQL mantendo a ordem correta
    $statements = [];
    $current_statement = '';
    $in_delimiter = false;
    
    foreach (explode("\n", $sql) as $line) {
        $line = trim($line);
        
        // Ignorar comentários e linhas vazias
        if (empty($line) || preg_match('/^--/', $line)) {
            continue;
        }
        
        $current_statement .= $line . "\n";
        
        // Se a linha termina com ;, é o fim do comando
        if (substr($line, -1) === ';') {
            $statements[] = trim($current_statement);
            $current_statement = '';
        }
    }
    
    // Executar comandos em ordem
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignorar erros de tabela/índice já existente
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate key') === false) {
                    $errors[] = "Erro ao executar: " . substr($statement, 0, 50) . "... - " . $e->getMessage();
                }
            }
        }
    }
    
    $success[] = "Tabelas criadas com sucesso!";
    $success[] = "Usuários de teste inseridos com sucesso!";
    $success[] = "Turmas de exemplo inseridas com sucesso!";
    
} catch (PDOException $e) {
    $errors[] = "Erro de conexão com MySQL: " . $e->getMessage();
    $errors[] = "Verifique se o MySQL está rodando e as credenciais estão corretas.";
    $errors[] = "Edite este arquivo para ajustar as configurações se necessário.";
} catch (Exception $e) {
    $errors[] = "Erro: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Sistema | Portal CEAA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="h-32 bg-gradient-to-br from-azul-principal to-verde-complementar flex items-center justify-center">
                <i class="fas fa-database text-white text-5xl"></i>
            </div>
            
            <div class="p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Instalação do Banco de Dados</h1>
                
                <?php if (!empty($success)): ?>
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                        <h3 class="font-bold text-green-700 mb-3">
                            <i class="fas fa-check-circle mr-2"></i>Sucesso!
                        </h3>
                        <ul class="space-y-2">
                            <?php foreach ($success as $msg): ?>
                                <li class="text-green-600 text-sm">
                                    <i class="fas fa-check mr-2"></i><?php echo $msg; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                        <h3 class="font-bold text-blue-700 mb-3">
                            <i class="fas fa-key mr-2"></i>Credenciais de Acesso
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="bg-white rounded-lg p-3">
                                <p class="font-semibold text-gray-700">Admin:</p>
                                <p class="text-gray-600">Usuário: <code class="bg-gray-100 px-2 py-1 rounded">admin</code></p>
                                <p class="text-gray-600">Senha: <code class="bg-gray-100 px-2 py-1 rounded">admin123</code></p>
                            </div>
                            <div class="bg-white rounded-lg p-3">
                                <p class="font-semibold text-gray-700">Professor:</p>
                                <p class="text-gray-600">Matrícula: <code class="bg-gray-100 px-2 py-1 rounded">PRO2026001</code></p>
                                <p class="text-gray-600">Senha: <code class="bg-gray-100 px-2 py-1 rounded">prof123</code></p>
                            </div>
                            <div class="bg-white rounded-lg p-3">
                                <p class="font-semibold text-gray-700">Aluno:</p>
                                <p class="text-gray-600">CPF: <code class="bg-gray-100 px-2 py-1 rounded">123.456.789-00</code></p>
                                <p class="text-gray-600">Senha: <code class="bg-gray-100 px-2 py-1 rounded">aluno123</code></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="login.php" class="flex-1 bg-gradient-to-r from-azul-principal to-verde-complementar text-white text-center py-3 rounded-xl font-semibold hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-sign-in-alt mr-2"></i>Fazer Login
                        </a>
                        <a href="../index.php" class="flex-1 bg-gray-200 text-gray-700 text-center py-3 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                            <i class="fas fa-home mr-2"></i>Voltar ao Site
                        </a>
                    </div>
                    
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                        <h3 class="font-bold text-red-700 mb-3">
                            <i class="fas fa-exclamation-circle mr-2"></i>Erros
                        </h3>
                        <ul class="space-y-2">
                            <?php foreach ($errors as $error): ?>
                                <li class="text-red-600 text-sm">
                                    <i class="fas fa-times mr-2"></i><?php echo $error; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <a href="install.php" class="block w-full bg-gray-200 text-gray-700 text-center py-3 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo mr-2"></i>Tentar Novamente
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-6 text-gray-500 text-sm">
            <p>Após a instalação, delete este arquivo por segurança.</p>
        </div>
    </div>
</body>
</html>
