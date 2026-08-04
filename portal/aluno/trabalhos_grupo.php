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

// Criar novo grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_grupo') {
    $nome_grupo = sanitizeInput($_POST['nome_grupo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $disciplina_id = intval($_POST['disciplina_id'] ?? 0);
    
    if (empty($nome_grupo)) {
        $error = 'Por favor, informe o nome do grupo.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO trabalhos_grupo (nome, descricao, disciplina_id, turma_id, criador_id, data_criacao, status) VALUES (?, ?, ?, (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1), ?, NOW(), 'ativo')");
            $stmt->execute([$nome_grupo, $descricao, $disciplina_id, $turma, $serie, $aluno_id]);
            
            $grupo_id = $pdo->lastInsertId();
            
            // Adicionar criador ao grupo
            $stmt = $pdo->prepare("INSERT INTO trabalhos_grupo_membros (grupo_id, aluno_id, data_entrada, papel) VALUES (?, ?, NOW(), 'lider')");
            $stmt->execute([$grupo_id, $aluno_id]);
            
            logAudit('GRUPO_CRIAR', 'trabalhos_grupo', $grupo_id, null, ['nome' => $nome_grupo]);
            
            $success = 'Grupo criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar grupo.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter grupos do aluno
$grupos = [];
try {
    $stmt = $pdo->query("
        SELECT 
            tg.*,
            d.nome as disciplina_nome,
            (SELECT COUNT(*) FROM trabalhos_grupo_membros tgm WHERE tgm.grupo_id = tg.id) as num_membros,
            CASE 
                WHEN tg.criador_id = ? THEN 'lider'
                ELSE 'membro'
            END as papel_aluno
        FROM trabalhos_grupo tg
        JOIN trabalhos_grupo_membros tgm ON tg.id = tgm.grupo_id
        LEFT JOIN disciplinas d ON tg.disciplina_id = d.id
        WHERE tgm.aluno_id = ?
        AND tg.status = 'ativo'
        ORDER BY tg.data_criacao DESC
    ");
    $stmt->execute([$aluno_id, $aluno_id]);
    $grupos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter grupos: " . $e->getMessage());
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
    <title>Trabalhos em Grupo | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Trabalhos em Grupo</h1>
                <p class="text-gray-600 mt-2">Gerencie seus grupos de trabalho</p>
            </div>
            <button onclick="abrirModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Criar Grupo
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

        <!-- Lista de Grupos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Meus Grupos</h2>
            </div>
            <div class="p-6">
                <?php if (!empty($grupos)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($grupos as $grupo): ?>
                            <div class="p-6 bg-gray-50 rounded-xl border border-gray-100 hover:shadow-md transition-all">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center">
                                        <i class="fas fa-users text-white text-xl"></i>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                        <?php echo $grupo['papel_aluno'] === 'lider' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'; ?>">
                                        <?php echo ucfirst($grupo['papel_aluno']); ?>
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($grupo['nome']); ?></h3>
                                <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($grupo['descricao'] ?? '', 0, 80)) . '...'; ?></p>
                                <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                                    <span>
                                        <i class="fas fa-book mr-1"></i>
                                        <?php echo htmlspecialchars($grupo['disciplina_nome'] ?? 'Geral'); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo $grupo['num_membros']; ?> membros
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <button class="flex-1 px-4 py-2 bg-azul-principal text-white rounded-lg text-sm font-semibold hover:bg-azul-escuro transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver Detalhes
                                    </button>
                                    <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-2"></i>
                        <p>Você ainda não participa de nenhum grupo.</p>
                        <button onclick="abrirModal()" class="mt-4 px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Criar Primeiro Grupo
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Criar Grupo -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Criar Novo Grupo</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_grupo">
                    
                    <div class="mb-4">
                        <label for="nome_grupo" class="block text-sm font-semibold text-gray-700 mb-2">Nome do Grupo *</label>
                        <input type="text" id="nome_grupo" name="nome_grupo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Grupo de Matemática">
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
                    
                    <div class="mb-6">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descreva o objetivo do grupo..."></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Criar Grupo
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

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
