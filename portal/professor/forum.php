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

// Criar fórum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_forum') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($turma_id) || empty($titulo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO forum_discussao (turma_id, professor_id, disciplina, titulo, descricao, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$turma_id, $_SESSION['usuario_id'], $disciplina, $titulo, $descricao, $_SESSION['usuario_id']]);
            
            logAudit('FORUM_CREATE', 'forum_discussao', $pdo->lastInsertId(), null, ['titulo' => $titulo]);
            
            $success = 'Fórum criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar fórum.';
        }
    }
}

// Criar tópico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_topico') {
    $forum_id = intval($_POST['forum_id'] ?? 0);
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    
    if (empty($forum_id) || empty($titulo) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO forum_topicos (forum_id, usuario_id, titulo, conteudo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$forum_id, $_SESSION['usuario_id'], $titulo, $conteudo]);
            
            $success = 'Tópico criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar tópico.';
        }
    }
}

// Excluir fórum
if (isset($_GET['action']) && $_GET['action'] === 'excluir_forum' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM forum_discussao WHERE id = ? AND professor_id = ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: forum.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir fórum.';
    }
}

// Obter fóruns
$forums = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT fd.*, t.nome as turma_nome 
        FROM forum_discussao fd 
        JOIN turmas t ON fd.turma_id = t.id 
        WHERE fd.professor_id = ? 
        ORDER BY fd.created_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $forums = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter fóruns: " . $e->getMessage());
}

// Obter tópicos
$topicos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ft.*, u.nome_completo as autor_nome, fd.titulo as forum_nome 
        FROM forum_topicos ft 
        JOIN usuarios u ON ft.usuario_id = u.id 
        JOIN forum_discussao fd ON ft.forum_id = fd.id 
        WHERE fd.professor_id = ? 
        ORDER BY ft.updated_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $topicos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter tópicos: " . $e->getMessage());
}

// Obter turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome FROM turmas WHERE ano_letivo = YEAR(CURDATE()) ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum de Discussão | Portal do Professor</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Fórum de Discussão</h1>
                <p class="text-gray-600 mt-2">Espaço para discussões por turma</p>
            </div>
            <button onclick="toggleModalForum()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Fórum
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

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200">
            <button onclick="showTab('forums')" id="tab-forums" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Fóruns</button>
            <button onclick="showTab('topicos')" id="tab-topicos" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Tópicos</button>
        </div>

        <!-- Tab Fóruns -->
        <div id="content-forums" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Disciplina</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Turma</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forums as $forum): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($forum['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo htmlspecialchars($forum['disciplina'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($forum['turma_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $forum['status'] === 'ativo' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($forum['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_forum&id=<?php echo $forum['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($forums)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-comments text-4xl mb-2"></i>
                        <p>Nenhum fórum criado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Tópicos -->
        <div id="content-topicos" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Criar Novo Tópico</h3>
                <form method="POST" action="" class="grid grid-cols-1 gap-4">
                    <input type="hidden" name="action" value="criar_topico">
                    
                    <div>
                        <label for="forum_id" class="block text-sm font-semibold text-gray-700 mb-2">Fórum</label>
                        <select id="forum_id" name="forum_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($forums as $forum): ?>
                                <option value="<?php echo $forum['id']; ?>"><?php echo htmlspecialchars($forum['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título do Tópico</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="conteudo" class="block text-sm font-semibold text-gray-700 mb-2">Conteúdo</label>
                        <textarea id="conteudo" name="conteudo" rows="4" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                        <i class="fas fa-plus mr-2"></i>Criar Tópico
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Tópicos Recentes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Autor</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Fórum</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Visualizações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topicos as $topico): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($topico['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($topico['autor_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($topico['forum_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($topico['status']) {
                                                'aberto' => 'bg-green-100 text-green-600',
                                                'fechado' => 'bg-red-100 text-red-600',
                                                'fixado' => 'bg-yellow-100 text-yellow-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($topico['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $topico['visualizacoes']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($topicos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-comment-dots text-4xl mb-2"></i>
                        <p>Nenhum tópico criado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Fórum -->
    <div id="modal-forum" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalForum()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Fórum</h2>
                    <button onclick="toggleModalForum()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_forum">
                    
                    <div class="mb-4">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                        <select id="turma_id" name="turma_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina</label>
                        <input type="text" id="disciplina" name="disciplina"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Matemática">
                    </div>
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título do fórum">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do fórum"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Fórum
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

        function toggleModalForum() {
            const modal = document.getElementById('modal-forum');
            modal.classList.toggle('hidden');
        }

        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
                el.classList.add('text-gray-500');
            });
            
            document.getElementById('content-' + tab).classList.remove('hidden');
            
            const tabElement = document.getElementById('tab-' + tab);
            tabElement.classList.add('text-azul-principal', 'border-b-2', 'border-azul-principal');
            tabElement.classList.remove('text-gray-500');
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
