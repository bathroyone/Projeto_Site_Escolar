<?php
session_start();
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar inscrição em evento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $evento_id = intval($_POST['evento_id'] ?? 0);
    $nome_evento = sanitizeInput($_POST['nome_evento'] ?? '');
    $data_evento = sanitizeInput($_POST['data_evento'] ?? '');
    
    if (empty($nome) || empty($email) || empty($telefone) || empty($evento_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se tabela existe, se não, criar
            $pdo->query("CREATE TABLE IF NOT EXISTS inscricoes_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evento_id INT NOT NULL,
                nome_evento VARCHAR(255) NOT NULL,
                nome_participante VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                telefone VARCHAR(50) NOT NULL,
                data_evento DATE NOT NULL,
                status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
                data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Verificar se já existe inscrição
            $stmt = $pdo->prepare("SELECT id FROM inscricoes_eventos WHERE email = ? AND evento_id = ? AND status != 'cancelada'");
            $stmt->execute([$email, $evento_id]);
            
            if ($stmt->fetch()) {
                $error = 'Você já está inscrito neste evento.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO inscricoes_eventos (evento_id, nome_evento, nome_participante, email, telefone, data_evento) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$evento_id, $nome_evento, $nome, $email, $telefone, $data_evento]);
                
                $success = 'Inscrição realizada com sucesso! Você receberá um e-mail de confirmação.';
            }
        } catch (PDOException $e) {
            error_log("Erro ao inscrever no evento: " . $e->getMessage());
            $error = 'Erro ao realizar inscrição. Tente novamente.';
        }
    }
}

// Obter evento_id da URL se fornecido
$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;
$nome_evento = isset($_GET['nome']) ? sanitizeInput($_GET['nome']) : '';
$data_evento = isset($_GET['data']) ? sanitizeInput($_GET['data']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição em Evento | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                <div class="text-center mb-8">
                    <a href="index.php" class="inline-block mb-4">
                        <i class="fas fa-arrow-left text-white text-2xl"></i>
                    </a>
                    <h2 class="text-3xl font-display font-bold text-white mb-2">Inscrição em Evento</h2>
                    <p class="text-gray-400">Preencha o formulário para se inscrever no evento</p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-3 rounded-xl mb-6">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-500/20 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="evento_id" value="<?php echo $evento_id; ?>">
                    <input type="hidden" name="nome_evento" value="<?php echo htmlspecialchars($nome_evento); ?>">
                    <input type="hidden" name="data_evento" value="<?php echo htmlspecialchars($data_evento); ?>">
                    
                    <div class="space-y-4">
                        <?php if ($nome_evento): ?>
                            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/5 rounded-xl p-4 mb-4">
                                <p class="text-sm text-gray-400">Evento</p>
                                <p class="text-white font-semibold"><?php echo htmlspecialchars($nome_evento); ?></p>
                                <?php if ($data_evento): ?>
                                    <p class="text-sm text-amarelo-destaque"><?php echo date('d/m/Y', strtotime($data_evento)); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                            <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Telefone</label>
                            <input type="tel" name="telefone" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="(00) 00000-0000">
                        </div>
                        
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-calendar-check mr-2"></i>Confirmar Inscrição
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <a href="index.php" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar para o site
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

