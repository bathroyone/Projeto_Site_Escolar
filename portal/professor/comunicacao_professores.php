<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'professor') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_mensagem') {
    $destinatario_id = intval($_POST['destinatario_id'] ?? 0);
    $assunto = sanitizeInput($_POST['assunto'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($destinatario_id) || empty($assunto) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO comunicacao_professores (remetente_id, destinatario_id, assunto, mensagem, criado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $destinatario_id, $assunto, $mensagem, $_SESSION['usuario_id']]);
            
            logAudit('COMUNICACAO_ENVIAR', 'comunicacao_professores', $pdo->lastInsertId(), null, ['destinatario_id' => $destinatario_id, 'assunto' => $assunto]);
            
            $success = 'Mensagem enviada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar mensagem.';
        }
    }
}

// Marcar como lida
if (isset($_GET['action']) && $_GET['action'] === 'marcar_lida' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE comunicacao_professores SET status = 'lida', data_leitura = NOW() WHERE id = ? AND destinatario_id = ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: comunicacao_professores.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao marcar como lida.';
    }
}

// Arquivar mensagem
if (isset($_GET['action']) && $_GET['action'] === 'arquivar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE comunicacao_professores SET status = 'arquivada' WHERE id = ? AND (remetente_id = ? OR destinatario_id = ?)");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
        header('Location: comunicacao_professores.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao arquivar mensagem.';
    }
}

// Excluir mensagem
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM comunicacao_professores WHERE id = ? AND (remetente_id = ? OR destinatario_id = ?)");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
        header('Location: comunicacao_professores.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir mensagem.';
    }
}

// Obter mensagens recebidas
$mensagens_recebidas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT cp.*, u.nome_completo as remetente_nome 
        FROM comunicacao_professores cp 
        JOIN usuarios u ON cp.remetente_id = u.id 
        WHERE cp.destinatario_id = ? AND cp.status != 'arquivada'
        ORDER BY cp.data_envio DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $mensagens_recebidas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensagens recebidas: " . $e->getMessage());
}

// Obter mensagens enviadas
$mensagens_enviadas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT cp.*, u.nome_completo as destinatario_nome 
        FROM comunicacao_professores cp 
        JOIN usuarios u ON cp.destinatario_id = u.id 
        WHERE cp.remetente_id = ? AND cp.status != 'arquivada'
        ORDER BY cp.data_envio DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $mensagens_enviadas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensagens enviadas: " . $e->getMessage());
}

// Obter outros professores
$professores = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'professor' AND id != ? AND ativo = TRUE ORDER BY nome_completo");
    $stmt->execute([$_SESSION['usuario_id']]);
    $professores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter professores: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicação com Professores | Portal do Professor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: {
                            principal: '#063b7a',
                            escuro: '#082b54',
                            claro: '#0b4a8c'
                        },
                        amarelo: {
                            destaque: '#ffd000',
                            claro: '#ffe033'
                        },
                        verde: {
                            complementar: '#13843b',
                            claro: '#15a048'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2">
                        <img src="../img/logo.jpg" alt="Logo" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">[Inserir nome da escola aqui]</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">[Inserir nome da escola aqui]</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-gray-500">Professor</p>
                            </div>
                            <div class="p-2">
                                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-display font-bold text-azul-principal">Comunicação com Professores</h1>
                <p class="text-gray-600 mt-2">Troca de mensagens com colegas</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Nova Mensagem
            </button>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Mensagens Recebidas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-inbox mr-2"></i>Recebidas
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($mensagens_recebidas) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($mensagens_recebidas as $mensagem): ?>
                                <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors <?php echo $mensagem['status'] === 'enviada' ? 'border-l-4 border-blue-500' : ''; ?>">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                                <?php echo strtoupper(substr($mensagem['remetente_nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($mensagem['assunto']); ?></h3>
                                                <p class="text-sm text-gray-500">
                                                    De: <?php echo htmlspecialchars($mensagem['remetente_nome']); ?> |
                                                    <?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($mensagem['status']) {
                                                'enviada' => 'bg-blue-100 text-blue-600',
                                                'lida' => 'bg-green-100 text-green-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($mensagem['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mt-2"><?php echo htmlspecialchars(substr($mensagem['mensagem'], 0, 100)) . '...'; ?></p>
                                    <div class="flex gap-2 mt-3">
                                        <?php if ($mensagem['status'] === 'enviada'): ?>
                                            <a href="?action=marcar_lida&id=<?php echo $mensagem['id']; ?>" class="px-3 py-1 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors text-sm">
                                                <i class="fas fa-envelope-open mr-1"></i>Marcar Lida
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=arquivar&id=<?php echo $mensagem['id']; ?>" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                            <i class="fas fa-archive mr-1"></i>Arquivar
                                        </a>
                                        <a href="?action=excluir&id=<?php echo $mensagem['id']; ?>" class="px-3 py-1 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-sm" onclick="return confirm('Tem certeza que deseja excluir?');">
                                            <i class="fas fa-trash mr-1"></i>Excluir
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Nenhuma mensagem recebida.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mensagens Enviadas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-verde-complementar to-verde-claro">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-paper-plane mr-2"></i>Enviadas
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($mensagens_enviadas) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($mensagens_enviadas as $mensagem): ?>
                                <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                                <?php echo strtoupper(substr($mensagem['destinatario_nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($mensagem['assunto']); ?></h3>
                                                <p class="text-sm text-gray-500">
                                                    Para: <?php echo htmlspecialchars($mensagem['destinatario_nome']); ?> |
                                                    <?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                            <?php echo ucfirst($mensagem['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mt-2"><?php echo htmlspecialchars(substr($mensagem['mensagem'], 0, 100)) . '...'; ?></p>
                                    <div class="flex gap-2 mt-3">
                                        <a href="?action=arquivar&id=<?php echo $mensagem['id']; ?>" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                            <i class="fas fa-archive mr-1"></i>Arquivar
                                        </a>
                                        <a href="?action=excluir&id=<?php echo $mensagem['id']; ?>" class="px-3 py-1 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-sm" onclick="return confirm('Tem certeza que deseja excluir?');">
                                            <i class="fas fa-trash mr-1"></i>Excluir
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-paper-plane text-4xl mb-2"></i>
                            <p>Nenhuma mensagem enviada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nova Mensagem -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Mensagem</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="enviar_mensagem">
                    
                    <div class="mb-4">
                        <label for="destinatario_id" class="block text-sm font-semibold text-gray-700 mb-2">Destinatário *</label>
                        <select id="destinatario_id" name="destinatario_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione um professor</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?php echo $professor['id']; ?>"><?php echo htmlspecialchars($professor['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="assunto" class="block text-sm font-semibold text-gray-700 mb-2">Assunto *</label>
                        <input type="text" id="assunto" name="assunto" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Assunto da mensagem">
                    </div>
                    
                    <div class="mb-4">
                        <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" required rows="5"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Conteúdo da mensagem"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Mensagem
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function toggleModal() {
            const modal = document.getElementById('modal');
            modal.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
