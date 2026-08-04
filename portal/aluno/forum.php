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

// Criar nova pergunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_pergunta') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
    
    if (empty($titulo) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO forum_perguntas (aluno_id, turma_id, disciplina_id, titulo, conteudo, data_criacao, status) VALUES (?, (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1), ?, ?, ?, NOW(), 'ativo')");
            $stmt->execute([$aluno_id, $turma, $serie, $disciplina_id, $titulo, $conteudo]);
            
            logAudit('FORUM_PERGUNTA_CRIAR', 'forum_perguntas', $pdo->lastInsertId(), null, ['titulo' => $titulo]);
            
            $success = 'Pergunta criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar pergunta.';
        }
    }
}

// Responder pergunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'responder') {
    $pergunta_id = intval($_POST['pergunta_id'] ?? 0);
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    
    if (empty($conteudo)) {
        $error = 'Por favor, escreva sua resposta.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO forum_respostas (pergunta_id, aluno_id, conteudo, data_resposta) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$pergunta_id, $aluno_id, $conteudo]);
            
            logAudit('FORUM_RESPONDER', 'forum_respostas', $pdo->lastInsertId(), null, ['pergunta_id' => $pergunta_id]);
            
            $success = 'Resposta enviada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar resposta.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter perguntas do fórum
$perguntas = [];
try {
    $stmt = $pdo->query("
        SELECT 
            fp.*,
            u.nome_completo as aluno_nome,
            d.nome as disciplina_nome,
            (SELECT COUNT(*) FROM forum_respostas fr WHERE fr.pergunta_id = fp.id) as num_respostas
        FROM forum_perguntas fp
        JOIN usuarios u ON fp.aluno_id = u.id
        LEFT JOIN disciplinas d ON fp.disciplina_id = d.id
        WHERE fp.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND fp.status = 'ativo'
        ORDER BY fp.data_criacao DESC
    ");
    $stmt->execute([$turma, $serie]);
    $perguntas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter perguntas: " . $e->getMessage());
}

// Obter disciplinas
$disciplinas = [];
try {
    $stmt = $pdo->query("SELECT * FROM disciplinas ORDER BY nome");
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter disciplinas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum de Dúvidas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Fórum de Dúvidas</h1>
                <p class="text-gray-600 mt-2">Tire suas dúvidas com colegas e professores</p>
            </div>
            <button onclick="abrirModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Pergunta
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

        <!-- Lista de Perguntas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Perguntas Recentes</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($perguntas as $pergunta): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-azul-principal/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-question-circle text-azul-principal text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($pergunta['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($pergunta['aluno_nome']); ?> | 
                                            <?php echo htmlspecialchars($pergunta['disciplina_nome'] ?? 'Geral'); ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <i class="fas fa-comments mr-1"></i>
                                            <?php echo $pergunta['num_respostas']; ?> respostas
                                        </span>
                                    </div>
                                </div>
                                <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($pergunta['conteudo'], 0, 150)) . '...'; ?></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'])); ?>
                                    </span>
                                    <button onclick="verRespostas(<?php echo $pergunta['id']; ?>)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver Respostas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($perguntas)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-comments text-4xl mb-2"></i>
                    <p>Nenhuma pergunta encontrada. Seja o primeiro a perguntar!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nova Pergunta -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Pergunta</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_pergunta">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Sobre o que é sua dúvida?">
                    </div>
                    
                    <div class="mb-4">
                        <label for="disciplina_id" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina</label>
                        <select id="disciplina_id" name="disciplina_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Geral</option>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?php echo $disciplina['id']; ?>"><?php echo htmlspecialchars($disciplina['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="conteudo" class="block text-sm font-semibold text-gray-700 mb-2">Conteúdo *</label>
                        <textarea id="conteudo" name="conteudo" rows="4" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descreva sua dúvida em detalhes"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Publicar Pergunta
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

        function abrirModal() {
            document.getElementById('modal').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function verRespostas(perguntaId) {
            alert('Funcionalidade de ver respostas será implementada em breve. ID: ' + perguntaId);
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
