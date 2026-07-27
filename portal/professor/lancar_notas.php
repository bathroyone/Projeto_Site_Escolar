<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Obter turmas do professor
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.* 
        FROM turmas t 
        JOIN grade_aulas ga ON t.id = ga.turma_id 
        WHERE ga.professor_id = ?
    ");
    $stmt->execute([$professor_id]);
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter alunos de uma turma específica
$alunos = [];
if (isset($_GET['turma_id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.* 
            FROM usuarios u 
            WHERE u.tipo_usuario = 'aluno' 
            AND u.turma_id = ?
            AND u.ativo = TRUE
            ORDER BY u.nome_completo
        ");
        $stmt->execute([intval($_GET['turma_id'])]);
        $alunos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter alunos: " . $e->getMessage());
    }
}

$success = '';
$error = '';

// Processar lançamento de notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lancar_notas') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $bimestre = sanitizeInput($_POST['bimestre'] ?? '1');
    $tipo_avaliacao = sanitizeInput($_POST['tipo_avaliacao'] ?? 'prova');
    $data_lancamento = $_POST['data_lancamento'] ?? date('Y-m-d');
    
    if (empty($turma_id) || empty($disciplina)) {
        $error = 'Por favor, selecione a turma e a disciplina.';
    } else {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            $notas_lancadas = 0;
            foreach ($_POST['notas'] as $aluno_id => $nota) {
                if (!empty($nota) && is_numeric($nota) && $nota >= 0 && $nota <= 10) {
                    $observacao = sanitizeInput($_POST['observacoes'][$aluno_id] ?? '');
                    $stmt = $pdo->prepare("
                        INSERT INTO notas (aluno_id, professor_id, turma_id, disciplina, bimestre, nota, tipo_avaliacao, data_lancamento, observacoes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE nota = ?, tipo_avaliacao = ?, data_lancamento = ?, observacoes = ?
                    ");
                    $stmt->execute([
                        $aluno_id, $professor_id, $turma_id, $disciplina, $bimestre, $nota, $tipo_avaliacao, $data_lancamento, $observacao,
                        $nota, $tipo_avaliacao, $data_lancamento, $observacao
                    ]);
                    $notas_lancadas++;
                }
            }
            
            $pdo->commit();
            $success = "$notas_lancadas nota(s) lançada(s) com sucesso!";
            
            // Recarregar alunos
            $stmt = $pdo->prepare("
                SELECT u.* 
                FROM usuarios u 
                WHERE u.tipo_usuario = 'aluno' 
                AND u.turma_id = ?
                AND u.ativo = TRUE
                ORDER BY u.nome_completo
            ");
            $stmt->execute([$turma_id]);
            $alunos = $stmt->fetchAll();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro ao lançar notas: " . $e->getMessage());
            $error = 'Erro ao lançar notas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Notas | Portal CEAA</title>
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
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #063b7a 0%, #0b4a8c 50%, #13843b 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-sm tracking-wide">LANÇAMENTO DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">NOTAS</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-11 h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden md:block text-left">
                                <span class="text-white text-sm font-medium block"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                                <span class="text-white/70 text-xs">Professor</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-3 w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-white/80">Professor</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Professor</span>
                                </a>
                                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Bem-vindo -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-2 h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-4xl font-display font-bold text-azul-principal">
                        Lançamento de Notas
                    </h1>
                    <p class="text-gray-600 mt-1 text-lg">Registre as notas dos alunos no boletim</p>
                </div>
            </div>
        </div>

        <!-- Seleção de Turma -->
        <div class="glass-card rounded-3xl shadow-xl p-8 mb-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-users text-white text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-display font-bold text-azul-principal">Selecionar Turma</h2>
                    <p class="text-gray-600">Escolha a turma para lançar as notas</p>
                </div>
            </div>

            <form method="GET" action="">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                        <select id="turma_id" name="turma_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo (isset($_GET['turma_id']) && $_GET['turma_id'] == $t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['nome']); ?> - <?php echo htmlspecialchars($t['serie']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-search mr-2"></i>Carregar Alunos
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Formulário de Lançamento de Notas -->
        <?php if (count($alunos) > 0): ?>
            <div class="glass-card rounded-3xl shadow-xl p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-edit text-white text-3xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-display font-bold text-azul-principal">Lançar Notas</h2>
                        <p class="text-gray-600">Preencha as informações e as notas dos alunos</p>
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="lancar_notas">
                    <input type="hidden" name="turma_id" value="<?php echo htmlspecialchars($_GET['turma_id']); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina *</label>
                            <input type="text" id="disciplina" name="disciplina" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: Matemática, Português">
                        </div>
                        
                        <div>
                            <label for="bimestre" class="block text-sm font-semibold text-gray-700 mb-2">Bimestre *</label>
                            <select id="bimestre" name="bimestre" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                <option value="1">1º Bimestre</option>
                                <option value="2">2º Bimestre</option>
                                <option value="3">3º Bimestre</option>
                                <option value="4">4º Bimestre</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tipo_avaliacao" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Avaliação *</label>
                            <select id="tipo_avaliacao" name="tipo_avaliacao" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                <option value="prova">Prova</option>
                                <option value="trabalho">Trabalho</option>
                                <option value="participacao">Participação</option>
                                <option value="recuperacao">Recuperação</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="data_lancamento" class="block text-sm font-semibold text-gray-700 mb-2">Data de Lançamento</label>
                        <input type="date" id="data_lancamento" name="data_lancamento" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Notas dos Alunos</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                                        <th class="pb-3">Aluno</th>
                                        <th class="pb-3">Nota (0-10)</th>
                                        <th class="pb-3">Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <tr class="border-b border-gray-100">
                                            <td class="py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                                        <?php echo strtoupper(substr($aluno['nome_completo'], 0, 1)); ?>
                                                    </div>
                                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($aluno['nome_completo']); ?></span>
                                                </div>
                                            </td>
                                            <td class="py-4">
                                                <input type="number" name="notas[<?php echo $aluno['id']; ?>]" min="0" max="10" step="0.1"
                                                    class="w-24 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                                    placeholder="0.0">
                                            </td>
                                            <td class="py-4">
                                                <input type="text" name="observacoes[<?php echo $aluno['id']; ?>]"
                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                                    placeholder="Observações (opcional)">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-4 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg text-lg">
                        <i class="fas fa-save mr-2"></i>Salvar Notas
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
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
