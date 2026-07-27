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

// Adicionar pontos ao aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_pontos') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $pontos = intval($_POST['pontos'] ?? 0);
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id) || empty($pontos)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se já existe registro de gamificação
            $stmt = $pdo->prepare("SELECT * FROM gamificacao WHERE professor_id = ? AND turma_id = ? AND aluno_id = ?");
            $stmt->execute([$_SESSION['usuario_id'], $turma_id, $aluno_id]);
            $gamificacao = $stmt->fetch();
            
            if ($gamificacao) {
                // Atualizar pontos e XP
                $novo_pontos = $gamificacao['pontos'] + $pontos;
                $novo_xp = $gamificacao['xp'] + ($pontos * 10);
                $novo_nivel = floor($novo_xp / 100) + 1;
                
                $stmt = $pdo->prepare("UPDATE gamificacao SET pontos = ?, xp = ?, nivel = ? WHERE id = ?");
                $stmt->execute([$novo_pontos, $novo_xp, $novo_nivel, $gamificacao['id']]);
            } else {
                // Criar novo registro
                $xp = $pontos * 10;
                $nivel = floor($xp / 100) + 1;
                
                $stmt = $pdo->prepare("INSERT INTO gamificacao (professor_id, turma_id, aluno_id, pontos, nivel, xp, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['usuario_id'], $turma_id, $aluno_id, $pontos, $nivel, $xp, $_SESSION['usuario_id']]);
            }
            
            logAudit('GAMIFICACAO_ADD_PONTOS', 'gamificacao', $aluno_id, null, ['pontos' => $pontos, 'motivo' => $motivo]);
            
            $success = 'Pontos adicionados com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar pontos.';
        }
    }
}

// Obter gamificação dos alunos
$gamificacao_alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT g.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM gamificacao g 
        JOIN usuarios u ON g.aluno_id = u.id 
        JOIN turmas t ON g.turma_id = t.id 
        WHERE g.professor_id = ? 
        ORDER BY g.pontos DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $gamificacao_alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter gamificação: " . $e->getMessage());
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

// Obter alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = TRUE ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamificação | Portal do Professor</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gamificação da Turma</h1>
                <p class="text-gray-600 mt-2">Sistema de pontos e conquistas</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Adicionar Pontos
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

        <!-- Ranking -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h2 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-trophy mr-2"></i>Ranking da Turma
                </h2>
            </div>
            <div class="p-6">
                <?php if (count($gamificacao_alunos) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($gamificacao_alunos as $index => $aluno): ?>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($aluno['turma_nome']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-azul-principal"><?php echo number_format($aluno['pontos']); ?></p>
                                    <p class="text-xs text-gray-500">pontos</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-amarelo-destaque">Nível <?php echo $aluno['nivel']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo number_format($aluno['xp']); ?> XP</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-trophy text-4xl mb-2"></i>
                        <p>Nenhum aluno no ranking ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Pontos -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Adicionar Pontos</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar_pontos">
                    
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
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="pontos" class="block text-sm font-semibold text-gray-700 mb-2">Pontos *</label>
                        <input type="number" id="pontos" name="pontos" required min="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Quantidade de pontos">
                    </div>
                    
                    <div class="mb-4">
                        <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                        <textarea id="motivo" name="motivo" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Motivo dos pontos"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Adicionar Pontos
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
