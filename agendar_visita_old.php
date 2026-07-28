<?php
session_start();
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar agendamento de visita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $data_visita = sanitizeInput($_POST['data_visita'] ?? '');
    $horario = sanitizeInput($_POST['horario'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($nome) || empty($email) || empty($telefone) || empty($data_visita) || empty($horario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO visitas (nome_visitante, tipo_visita, data_visita, horario, motivo, status, criado_por) VALUES (?, 'agendamento_site', ?, ?, ?, 'agendada', NULL)");
            $stmt->execute([$nome, $data_visita, $horario, $motivo]);
            
            $success = 'Visita agendada com sucesso! Entraremos em contato para confirmar.';
        } catch (PDOException $e) {
            error_log("Erro ao agendar visita: " . $e->getMessage());
            $error = 'Erro ao agendar visita. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Visita | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
                <div class="text-center mb-8">
                    <a href="index.php" class="inline-block mb-4">
                        <i class="fas fa-arrow-left text-white text-2xl"></i>
                    </a>
                    <h2 class="text-3xl font-display font-bold text-white mb-2">Agendar Visita</h2>
                    <p class="text-gray-400">Preencha o formulário para agendar sua visita à escola</p>
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
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                            <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Telefone</label>
                            <input type="tel" name="telefone" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="(00) 00000-0000">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Data</label>
                                <input type="date" name="data_visita" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Horário</label>
                                <select name="horario" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                                    <option value="">Selecione</option>
                                    <option value="08:00">08:00</option>
                                    <option value="09:00">09:00</option>
                                    <option value="10:00">10:00</option>
                                    <option value="11:00">11:00</option>
                                    <option value="14:00">14:00</option>
                                    <option value="15:00">15:00</option>
                                    <option value="16:00">16:00</option>
                                    <option value="17:00">17:00</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Motivo da Visita</label>
                            <textarea name="motivo" rows="3" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Conte-nos sobre o motivo da sua visita"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
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
