<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

$success = '';
$error = '';

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_mensagem') {
    $professor_id = intval($_POST['professor_id'] ?? 0);
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($professor_id) || empty($mensagem)) {
        $error = 'Por favor, selecione um professor e escreva uma mensagem.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO chat_mensagens (remetente_id, destinatario_id, mensagem, data_envio, lida) VALUES (?, ?, ?, NOW(), FALSE)");
            $stmt->execute([$aluno_id, $professor_id, $mensagem]);
            
            logAudit('CHAT_ENVIAR', 'chat_mensagens', $pdo->lastInsertId(), null, ['destinatario_id' => $professor_id]);
            
            $success = 'Mensagem enviada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar mensagem.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter professores da turma
$professores = [];
try {
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.nome_completo, d.nome as disciplina
        FROM usuarios u
        JOIN chamadas_digitais cd ON u.id = cd.professor_id
        JOIN disciplinas d ON cd.disciplina_id = d.id
        WHERE cd.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND u.tipo_usuario = 'professor'
        ORDER BY u.nome_completo
    ");
    $stmt->execute([$turma, $serie]);
    $professores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter professores: " . $e->getMessage());
}

// Obter conversas
$conversas = [];
try {
    $stmt = $pdo->query("
        SELECT 
            cm.*,
            u_remetente.nome_completo as remetente_nome,
            u_destinatario.nome_completo as destinatario_nome,
            CASE 
                WHEN cm.remetente_id = ? THEN 'enviada'
                ELSE 'recebida'
            END as tipo
        FROM chat_mensagens cm
        JOIN usuarios u_remetente ON cm.remetente_id = u_remetente.id
        JOIN usuarios u_destinatario ON cm.destinatario_id = u_destinatario.id
        WHERE (cm.remetente_id = ? OR cm.destinatario_id = ?)
        ORDER BY cm.data_envio DESC
        LIMIT 50
    ");
    $stmt->execute([$aluno_id, $aluno_id, $aluno_id]);
    $conversas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter conversas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com Professores | Portal de Gestão Escolar</title>
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
                                <p class="text-sm text-gray-500">Aluno</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Chat com Professores</h1>
                <p class="text-gray-600 mt-2">Comunique-se com seus professores</p>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Lista de Professores -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Professores</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($professores)): ?>
                            <div class="space-y-3">
                                <?php foreach ($professores as $professor): ?>
                                    <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-all cursor-pointer" onclick="selecionarProfessor(<?php echo $professor['id']; ?>, '<?php echo htmlspecialchars($professor['nome_completo']); ?>')">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-azul-principal/10 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user-tie text-azul-principal"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($professor['nome_completo']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($professor['disciplina']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-users text-4xl mb-2"></i>
                                <p>Nenhum professor encontrado.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Conversas -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Mensagens</h2>
                    </div>
                    
                    <!-- Formulário de Envio -->
                    <div class="p-6 border-b border-gray-100">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="enviar_mensagem">
                            <input type="hidden" id="professor_id" name="professor_id">
                            
                            <div class="mb-4">
                                <label for="professor_select" class="block text-sm font-semibold text-gray-700 mb-2">Professor *</label>
                                <select id="professor_select" name="professor_select" required onchange="document.getElementById('professor_id').value = this.value"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                                    <option value="">Selecione um professor</option>
                                    <?php foreach ($professores as $professor): ?>
                                        <option value="<?php echo $professor['id']; ?>"><?php echo htmlspecialchars($professor['nome_completo']); ?> - <?php echo htmlspecialchars($professor['disciplina']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
                                <textarea id="mensagem" name="mensagem" rows="3" required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                    placeholder="Escreva sua mensagem..."></textarea>
                            </div>
                            
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Enviar Mensagem
                            </button>
                        </form>
                    </div>
                    
                    <!-- Lista de Mensagens -->
                    <div class="p-6">
                        <?php if (!empty($conversas)): ?>
                            <div class="space-y-4">
                                <?php foreach ($conversas as $msg): ?>
                                    <div class="p-4 rounded-xl <?php echo $msg['tipo'] === 'enviada' ? 'bg-azul-principal/10 ml-8' : 'bg-gray-50 mr-8'; ?>">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                                <?php echo $msg['tipo'] === 'enviada' ? 'bg-azul-principal' : 'bg-gray-300'; ?>">
                                                <i class="fas fa-user text-white text-xs"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="font-semibold text-gray-800 text-sm">
                                                        <?php echo htmlspecialchars($msg['tipo'] === 'enviada' ? 'Você' : $msg['remetente_nome']); ?>
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?>
                                                    </span>
                                                </div>
                                                <p class="text-gray-700 text-sm"><?php echo htmlspecialchars($msg['mensagem']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comments text-4xl mb-2"></i>
                                <p>Nenhuma mensagem encontrada.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function selecionarProfessor(id, nome) {
            document.getElementById('professor_id').value = id;
            document.getElementById('professor_select').value = id;
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
