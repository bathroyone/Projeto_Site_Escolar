<?php
// Script para executar o SQL de criação de tabelas do painel admin
$host = 'localhost';
$dbname = 'escola_gestao';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sqlFile = __DIR__ . '/create_admin_tables.sql';
    $sql = file_get_contents($sqlFile);
    
    // Separar as instruções SQL
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executado com sucesso: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Erro: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\nScript SQL executado com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
}
?>
