<?php
session_start();
require_once 'portal/config.php';

header('Content-Type: application/json');

$success = false;
$message = '';
$redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $usuario = sanitizeInput($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($tipo) || empty($usuario) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
        exit;
    }
    
    $pdo = getDBConnection();
    
    try {
        if ($tipo === 'professor') {
            // Login de professor: matrícula + senha
            $stmt = $pdo->prepare("SELECT id, nome_completo, senha, matricula FROM usuarios WHERE matricula = ? AND tipo_usuario = 'professor' AND ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome_completo'];
                $_SESSION['tipo_usuario'] = 'professor';
                $_SESSION['matricula'] = $user['matricula'];
                $success = true;
                $redirect = 'portal/professor/index.php';
            } else {
                $message = 'Matrícula ou senha inválidos.';
            }
            
        } elseif ($tipo === 'aluno') {
            // Login de aluno: CPF do responsável + senha
            $cpf = preg_replace('/[^0-9]/', '', $usuario);
            
            // Buscar aluno limpando o CPF do banco também
            $stmt = $pdo->prepare("SELECT id, nome_completo, senha, cpf FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            $user = null;
            foreach ($users as $u) {
                $cpf_banco = preg_replace('/[^0-9]/', '', $u['cpf']);
                if ($cpf_banco === $cpf) {
                    $user = $u;
                    break;
                }
            }
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome_completo'];
                $_SESSION['tipo_usuario'] = 'aluno';
                $_SESSION['cpf_responsavel'] = $user['cpf'];
                $success = true;
                $redirect = 'portal/aluno/index.php';
            } else {
                $message = 'CPF do responsável ou senha inválidos.';
            }
            
        } elseif ($tipo === 'secretaria') {
            // Login de secretaria: usuario_login + senha
            $stmt = $pdo->prepare("SELECT id, nome_completo, senha, usuario_login FROM usuarios WHERE usuario_login = ? AND tipo_usuario = 'secretaria' AND ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome_completo'];
                $_SESSION['tipo_usuario'] = 'secretaria';
                $_SESSION['usuario'] = $user['usuario_login'];
                $success = true;
                $redirect = 'portal/secretaria/index.php';
            } else {
                $message = 'Usuário ou senha inválidos.';
            }
            
        } elseif ($tipo === 'admin') {
            // Login de admin: usuario_login + senha
            $stmt = $pdo->prepare("SELECT id, nome_completo, senha, usuario_login FROM usuarios WHERE usuario_login = ? AND tipo_usuario = 'admin' AND ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome_completo'];
                $_SESSION['tipo_usuario'] = 'admin';
                $_SESSION['usuario'] = $user['usuario_login'];
                $success = true;
                $redirect = 'portal/admin/index.php';
            } else {
                $message = 'Usuário ou senha inválidos.';
            }
            
        } else {
            $message = 'Tipo de usuário inválido.';
        }
        
    } catch (PDOException $e) {
        error_log("Erro no login: " . $e->getMessage());
        $message = 'Erro ao processar login. Tente novamente.';
    }
    
    echo json_encode(['success' => $success, 'message' => $message, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
