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

// Criar prova
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_prova') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? '');
    $hora_inicio = sanitizeInput($_POST['hora_inicio'] ?? '');
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $hora_fim = sanitizeInput($_POST['hora_fim'] ?? '');
    $duracao_minutos = intval($_POST['duracao_minutos'] ?? 60);
    $tentativas_permitidas = intval($_POST['tentativas_permitidas'] ?? 1);
    $nota_maxima = floatval($_POST['nota_maxima'] ?? 10.00);
    
    if (empty($turma_id) || empty($disciplina) || empty($titulo) || empty($data_inicio) || empty($hora_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $data_inicio_completa = $data_inicio . ' ' . $hora_inicio;
            $data_fim_completa = $data_fim ? $data_fim . ' ' . $hora_fim : null;
            
            $stmt = $pdo->prepare("INSERT INTO provas_online (professor_id, turma_id, disciplina, titulo, descricao, data_inicio, data_fim, duracao_minutos, tentativas_permitidas, nota_maxima, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $turma_id, $disciplina, $titulo, $descricao, $data_inicio_completa, $data_fim_completa, $duracao_minutos, $tentativas_permitidas, $nota_maxima, $_SESSION['usuario_id']]);
            
            logAudit('PROVA_CREATE', 'provas_online', $pdo->lastInsertId(), null, ['titulo' => $titulo, 'disciplina' => $disciplina]);
            
            $success = 'Prova criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar prova.';
        }
    }
}

// Publicar prova
if (isset($_GET['action']) && $_GET['action'] === 'publicar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE provas_online SET status = 'agendada' WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: provas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao publicar prova.';
    }
}

// Excluir prova
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM provas_online WHERE id = ? AND professor_id = ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: provas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir prova.';
    }
}

// Obter provas
$provas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT p.*, t.nome as turma_nome 
        FROM provas_online p 
        JOIN turmas t ON p.turma_id = t.id 
        WHERE p.professor_id = ? 
        ORDER BY p.data_inicio DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $provas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter provas: " . $e->getMessage());
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
    <title>Provas Online | Portal do Professor</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Provas Online</h1>
                <p class="text-gray-600 mt-2">Crie e gerencie provas online</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Prova
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

        <!-- Provas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Turma</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Período</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($provas as $prova): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($prova['titulo']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                        <?php echo htmlspecialchars($prova['disciplina']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($prova['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                    <?php echo date('d/m/Y H:i', strtotime($prova['data_inicio'])); ?>
                                    <?php echo $prova['data_fim'] ? ' - ' . date('d/m/Y H:i', strtotime($prova['data_fim'])) : ''; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($prova['status']) {
                                            'rascunho' => 'bg-gray-100 text-gray-600',
                                            'agendada' => 'bg-blue-100 text-blue-600',
                                            'em_andamento' => 'bg-yellow-100 text-yellow-600',
                                            'finalizada' => 'bg-green-100 text-green-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $prova['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex gap-1">
                                        <?php if ($prova['status'] === 'rascunho'): ?>
                                            <a href="?action=publicar&id=<?php echo $prova['id']; ?>" class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors" title="Publicar">
                                                <i class="fas fa-paper-plane"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=excluir&id=<?php echo $prova['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($provas)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-file-alt text-4xl mb-2"></i>
                    <p>Nenhuma prova criada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nova Prova -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Prova Online</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_prova">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                            <select id="turma_id" name="turma_id" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="">Selecione</option>
                                <?php foreach ($turmas as $turma): ?>
                                    <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina *</label>
                            <input type="text" id="disciplina" name="disciplina" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Ex: Matemática">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título da prova">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição da prova"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início *</label>
                            <input type="date" id="data_inicio" name="data_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="hora_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Hora Início *</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="hora_fim" class="block text-sm font-semibold text-gray-700 mb-2">Hora Fim</label>
                            <input type="time" id="hora_fim" name="hora_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="duracao_minutos" class="block text-sm font-semibold text-gray-700 mb-2">Duração (min)</label>
                            <input type="number" id="duracao_minutos" name="duracao_minutos" value="60"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="tentativas_permitidas" class="block text-sm font-semibold text-gray-700 mb-2">Tentativas</label>
                            <input type="number" id="tentativas_permitidas" name="tentativas_permitidas" value="1"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="nota_maxima" class="block text-sm font-semibold text-gray-700 mb-2">Nota Máxima</label>
                            <input type="number" id="nota_maxima" name="nota_maxima" value="10" step="0.5"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Prova
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
