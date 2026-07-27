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
            // Login de professor: matrícula de funcionário + senha
            $stmt = $pdo->prepare("SELECT f.id, f.nome, f.matricula, f.senha, p.cargo 
                                   FROM funcionarios f 
                                   JOIN professores p ON f.id = p.funcionario_id 
                                   WHERE f.matricula = ? AND f.ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
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
            
            $stmt = $pdo->prepare("SELECT a.id, a.nome, a.senha, r.cpf, r.nome as responsavel_nome 
                                   FROM alunos a 
                                   JOIN responsaveis r ON a.responsavel_id = r.id 
                                   WHERE r.cpf = ? AND a.ativo = 1");
            $stmt->execute([$cpf]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['tipo_usuario'] = 'aluno';
                $_SESSION['cpf_responsavel'] = $user['cpf'];
                $success = true;
                $redirect = 'portal/aluno/index.php';
            } else {
                $message = 'CPF do responsável ou senha inválidos.';
            }
            
        } elseif ($tipo === 'secretaria') {
            // Login de secretaria: usuário + senha
            $stmt = $pdo->prepare("SELECT id, nome, usuario, senha FROM usuarios_secretaria WHERE usuario = ? AND ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['tipo_usuario'] = 'secretaria';
                $_SESSION['usuario'] = $user['usuario'];
                $success = true;
                $redirect = 'portal/secretaria/index.php';
            } else {
                $message = 'Usuário ou senha inválidos.';
            }
            
        } elseif ($tipo === 'admin') {
            // Login de admin: usuário + senha
            $stmt = $pdo->prepare("SELECT id, nome, usuario, senha FROM usuarios_admin WHERE usuario = ? AND ativo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['tipo_usuario'] = 'admin';
                $_SESSION['usuario'] = $user['usuario'];
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
