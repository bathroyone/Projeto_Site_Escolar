<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Criar tabela de chamada se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS chamada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    data_chamada DATE NOT NULL,
    status ENUM('presente', 'ausente', 'atrasado', 'justificado') DEFAULT 'presente',
    observacao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    UNIQUE KEY (aluno_id, turma_id, data_chamada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$success = '';
$error = '';

// Obter turmas do professor
$turmas = [];
try {
    $stmt = $conn->prepare("
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

// Registrar chamada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_chamada') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $data_chamada = $_POST['data_chamada'] ?? date('Y-m-d');
    
    if (empty($turma_id) || empty($data_chamada)) {
        $error = 'Por favor, selecione a turma e a data.';
    } else {
        try {
            // Obter alunos da turma
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE turma_id = ? AND tipo_usuario = 'aluno' AND ativo = 1");
            $stmt->execute([$turma_id]);
            $alunos = $stmt->fetchAll();
            
            foreach ($alunos as $aluno) {
                $status = $_POST['status_' . $aluno['id']] ?? 'presente';
                $observacao = $_POST['observacao_' . $aluno['id']] ?? '';
                
                $stmt = $conn->prepare("INSERT INTO chamada (aluno_id, professor_id, turma_id, data_chamada, status, observacao) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?, observacao = ?");
                $stmt->execute([$aluno['id'], $professor_id, $turma_id, $data_chamada, $status, $observacao, $status, $observacao]);
            }
            
            $success = 'Chamada registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar chamada.';
        }
    }
}

// Obter alunos da turma selecionada
$alunos_turma = [];
$turma_selecionada = null;
$data_selecionada = date('Y-m-d');

if (isset($_GET['turma_id']) && isset($_GET['data'])) {
    $turma_selecionada = intval($_GET['turma_id']);
    $data_selecionada = $_GET['data'];
    
    try {
        $stmt = $conn->prepare("
            SELECT u.*, c.status as status_chamada, c.observacao 
            FROM usuarios u 
            LEFT JOIN chamada c ON u.id = c.aluno_id AND c.turma_id = ? AND c.data_chamada = ?
            WHERE u.turma_id = ? AND u.tipo_usuario = 'aluno' AND u.ativo = 1
            ORDER BY u.nome_completo
        ");
        $stmt->execute([$turma_selecionada, $data_selecionada, $turma_selecionada]);
        $alunos_turma = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter alunos: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada Digital | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Chamada Digital</h1>
                <p class="text-gray-600 mt-2">Registrar frequência dos alunos</p>
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

        <!-- Seleção de Turma e Data -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                    <select id="turma_id" name="turma_id" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="">Selecione</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>" <?php echo $turma_selecionada == $turma['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="data" class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                    <input type="date" id="data" name="data" value="<?php echo $data_selecionada; ?>" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                        <i class="fas fa-search mr-2"></i>Carregar Alunos
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Alunos para Chamada -->
        <?php if (!empty($alunos_turma)): ?>
            <form method="POST" action="" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <input type="hidden" name="action" value="registrar_chamada">
                <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada; ?>">
                <input type="hidden" name="data_chamada" value="<?php echo $data_selecionada; ?>">
                
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Chamada - <?php echo date('d/m/Y', strtotime($data_selecionada)); ?></h2>
                    <div class="flex gap-2">
                        <button type="button" onclick="marcarTodos('presente')" class="px-4 py-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors text-sm">
                            <i class="fas fa-check mr-1"></i>Todos Presentes
                        </button>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Salvar Chamada
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_turma as $aluno): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                                <?php echo strtoupper(substr($aluno['nome_completo'], 0, 1)); ?>
                                            </div>
                                            <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($aluno['nome_completo']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <select name="status_<?php echo $aluno['id']; ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                                            <?php 
                                            $cor_status = match($aluno['status_chamada'] ?? 'presente') {
                                                'presente' => 'bg-green-100 text-green-600',
                                                'ausente' => 'bg-red-100 text-red-600',
                                                'atrasado' => 'bg-yellow-100 text-yellow-600',
                                                'justificado' => 'bg-blue-100 text-blue-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <option value="presente" <?php echo ($aluno['status_chamada'] ?? 'presente') === 'presente' ? 'selected' : ''; ?>>Presente</option>
                                            <option value="ausente" <?php echo ($aluno['status_chamada'] ?? 'presente') === 'ausente' ? 'selected' : ''; ?>>Ausente</option>
                                            <option value="atrasado" <?php echo ($aluno['status_chamada'] ?? 'presente') === 'atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                                            <option value="justificado" <?php echo ($aluno['status_chamada'] ?? 'presente') === 'justificado' ? 'selected' : ''; ?>>Justificado</option>
                                        </select>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                        <input type="text" name="observacao_<?php echo $aluno['id']; ?>" value="<?php echo htmlspecialchars($aluno['observacao'] ?? ''); ?>"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="Observação">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function marcarTodos(status) {
            const selects = document.querySelectorAll('select[name^="status_"]');
            selects.forEach(select => {
                select.value = status;
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
