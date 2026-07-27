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

// Enviar feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_feedback') {
    $aula_id = intval($_POST['aula_id'] ?? 0);
    $avaliacao = intval($_POST['avaliacao'] ?? 0);
    $comentario = sanitizeInput($_POST['comentario'] ?? '');
    
    if (empty($aula_id) || $avaliacao < 1 || $avaliacao > 5) {
        $error = 'Por favor, selecione uma aula e dê uma avaliação.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO feedback_aulas (aluno_id, aula_id, avaliacao, comentario, data_feedback) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$aluno_id, $aula_id, $avaliacao, $comentario]);
            
            logAudit('FEEDBACK_ENVIAR', 'feedback_aulas', $pdo->lastInsertId(), null, ['aula_id' => $aula_id, 'avaliacao' => $avaliacao]);
            
            $success = 'Feedback enviado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar feedback.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter aulas do aluno
$aulas = [];
try {
    $stmt = $pdo->query("
        SELECT 
            cd.id,
            cd.data_aula,
            cd.conteudo,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome,
            (SELECT COUNT(*) FROM feedback_aulas fa WHERE fa.aula_id = cd.id AND fa.aluno_id = ?) as ja_avaliou
        FROM chamadas_digitais cd
        JOIN disciplinas d ON cd.disciplina_id = d.id
        JOIN usuarios u ON cd.professor_id = u.id
        WHERE cd.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND cd.data_aula <= CURDATE()
        ORDER BY cd.data_aula DESC
        LIMIT 30
    ");
    $stmt->execute([$aluno_id, $turma, $serie]);
    $aulas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter aulas: " . $e->getMessage());
}

// Obter feedbacks enviados
$feedbacks = [];
try {
    $stmt = $pdo->query("
        SELECT 
            fa.*,
            cd.conteudo as aula_conteudo,
            cd.data_aula,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM feedback_aulas fa
        JOIN chamadas_digitais cd ON fa.aula_id = cd.id
        JOIN disciplinas d ON cd.disciplina_id = d.id
        JOIN usuarios u ON cd.professor_id = u.id
        WHERE fa.aluno_id = ?
        ORDER BY fa.data_feedback DESC
        LIMIT 20
    ");
    $stmt->execute([$aluno_id]);
    $feedbacks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter feedbacks: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback sobre Aulas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Feedback sobre Aulas</h1>
                <p class="text-gray-600 mt-2">Avalie suas aulas e ajude a melhorar o ensino</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Aulas para Avaliar -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                        <h2 class="text-xl font-display font-bold text-white">
                            <i class="fas fa-star mr-2"></i>Aulas para Avaliar
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($aulas)): ?>
                            <div class="space-y-4">
                                <?php foreach ($aulas as $aula): ?>
                                    <?php if (!$aula['ja_avaliou']): ?>
                                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                            <div class="flex items-start justify-between mb-3">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aula['disciplina_nome']); ?></h3>
                                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($aula['professor_nome']); ?></p>
                                                    <p class="text-xs text-gray-400 mt-1">
                                                        <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($aula['conteudo'] ?? '', 0, 60)) . '...'; ?></p>
                                            <button onclick="abrirModal(<?php echo $aula['id']; ?>, '<?php echo htmlspecialchars($aula['disciplina_nome']); ?>')"
                                                class="w-full px-4 py-2 bg-azul-principal text-white rounded-lg text-sm font-semibold hover:bg-azul-escuro transition-colors">
                                                <i class="fas fa-star mr-1"></i>
                                                Avaliar Aula
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php 
                            $nao_avaliadas = array_filter($aulas, fn($a) => !$a['ja_avaliou']);
                            if (empty($nao_avaliadas)): 
                            ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                                    <p>Todas as aulas foram avaliadas!</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-2"></i>
                                <p>Nenhuma aula encontrada.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Histórico de Feedbacks -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Feedbacks</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($feedbacks)): ?>
                            <div class="space-y-4">
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($feedback['disciplina_nome']); ?></h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($feedback['professor_nome']); ?></p>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $feedback['avaliacao'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-sm"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars(substr($feedback['aula_conteudo'] ?? '', 0, 50)) . '...'; ?></p>
                                        <?php if ($feedback['comentario']): ?>
                                            <p class="text-gray-700 text-sm italic mb-2">"<?php echo htmlspecialchars($feedback['comentario']); ?>"</p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400">
                                            <?php echo date('d/m/Y H:i', strtotime($feedback['data_feedback'])); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-comments text-4xl mb-2"></i>
                                <p>Nenhum feedback enviado ainda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Feedback -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Avaliar Aula</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="enviar_feedback">
                    <input type="hidden" id="aula_id" name="aula_id">
                    
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Disciplina: <span id="disciplina_nome" class="text-azul-principal"></span></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Avaliação *</label>
                        <div class="flex items-center gap-2">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <button type="button" onclick="selecionarAvaliacao(<?php echo $i; ?>)" 
                                    class="avaliacao-btn w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center hover:border-yellow-400 transition-colors"
                                    data-avaliacao="<?php echo $i; ?>">
                                    <i class="fas fa-star text-gray-300"></i>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="avaliacao" name="avaliacao" value="0">
                    </div>
                    
                    <div class="mb-6">
                        <label for="comentario" class="block text-sm font-semibold text-gray-700 mb-2">Comentário (opcional)</label>
                        <textarea id="comentario" name="comentario" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Deixe seu comentário sobre a aula..."></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Feedback
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

        function abrirModal(aulaId, disciplinaNome) {
            document.getElementById('aula_id').value = aulaId;
            document.getElementById('disciplina_nome').textContent = disciplinaNome;
            document.getElementById('modal').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('avaliacao').value = 0;
            document.querySelectorAll('.avaliacao-btn').forEach(btn => {
                btn.classList.remove('border-yellow-400');
                btn.querySelector('i').classList.remove('text-yellow-400');
                btn.querySelector('i').classList.add('text-gray-300');
            });
        }

        function selecionarAvaliacao(valor) {
            document.getElementById('avaliacao').value = valor;
            document.querySelectorAll('.avaliacao-btn').forEach(btn => {
                const btnValor = parseInt(btn.dataset.avaliacao);
                if (btnValor <= valor) {
                    btn.classList.add('border-yellow-400');
                    btn.querySelector('i').classList.remove('text-gray-300');
                    btn.querySelector('i').classList.add('text-yellow-400');
                } else {
                    btn.classList.remove('border-yellow-400');
                    btn.querySelector('i').classList.remove('text-yellow-400');
                    btn.querySelector('i').classList.add('text-gray-300');
                }
            });
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
