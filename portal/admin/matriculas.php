<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin ou secretaria
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'secretaria')) {
    header('Location: ../login.php');
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$success = '';
$error = '';

// Criar tabela de matrículas se não existir
$conn->query("CREATE TABLE IF NOT EXISTS matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    ano_letivo INT NOT NULL,
    data_matricula DATE NOT NULL,
    status ENUM('ativa', 'cancelada', 'transferida', 'concluida') DEFAULT 'ativa',
    observacao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    UNIQUE KEY (aluno_id, turma_id, ano_letivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar nova matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nova_matricula') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    $data_matricula = $_POST['data_matricula'] ?? date('Y-m-d');
    $observacao = sanitizeInput($_POST['observacao'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id)) {
        $error = 'Por favor, selecione o aluno e a turma.';
    } else {
        try {
            // Atualizar turma do aluno
            $stmt = $conn->prepare("UPDATE usuarios SET turma_id = ? WHERE id = ?");
            $stmt->execute([$turma_id, $aluno_id]);
            
            // Criar matrícula
            $stmt = $conn->prepare("INSERT INTO matriculas (aluno_id, turma_id, ano_letivo, data_matricula, observacao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $turma_id, $ano_letivo, $data_matricula, $observacao]);
            
            $success = 'Matrícula realizada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao realizar matrícula. Verifique se o aluno já está matriculado nesta turma.';
        }
    }
}

// Cancelar matrícula
if (isset($_GET['action']) && $_GET['action'] === 'cancelar' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("UPDATE matriculas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: matriculas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao cancelar matrícula.';
    }
}

// Obter alunos sem matrícula ativa
$alunos_sem_matricula = [];
try {
    $stmt = $conn->prepare("
        SELECT u.* FROM usuarios u 
        WHERE u.tipo_usuario = 'aluno' AND u.ativo = 1 
        AND u.id NOT IN (SELECT aluno_id FROM matriculas WHERE status = 'ativa' AND ano_letivo = ?)
        ORDER BY u.nome_completo
    ");
    $stmt->execute([date('Y')]);
    $alunos_sem_matricula = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Obter turmas ativas
$turmas = [];
try {
    $stmt = $conn->prepare("SELECT * FROM turmas WHERE ano_letivo = ? ORDER BY nome, serie");
    $stmt->execute([date('Y')]);
    $turmas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter matrículas ativas
$matriculas = [];
try {
    $stmt = $conn->prepare("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome, t.serie 
        FROM matriculas m 
        JOIN usuarios u ON m.aluno_id = u.id 
        JOIN turmas t ON m.turma_id = t.id 
        WHERE m.status = 'ativa' AND m.ano_letivo = ?
        ORDER BY u.nome_completo
    ");
    $stmt->execute([date('Y')]);
    $matriculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Matrículas | Portal de Gestão Escolar</title>
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
                    <a href="../dashboard_secretaria.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
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
                                <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['tipo_usuario']); ?></p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Matrículas</h1>
                <p class="text-gray-600 mt-2">Gerenciar matrículas dos alunos</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Matrícula
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

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Matrículas Ativas</p>
                        <p class="text-4xl font-bold text-green-600"><?php echo count($matriculas); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Alunos Pendentes</p>
                        <p class="text-4xl font-bold text-orange-600"><?php echo count($alunos_sem_matricula); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-user-clock text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Turmas Disponíveis</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo count($turmas); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chalkboard text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Matrículas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Matrículas Ativas - <?php echo date('Y'); ?></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Série</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Matrícula</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matriculas as $matricula): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                            <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($matricula['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($matricula['serie']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">Ativa</span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="?action=cancelar&id=<?php echo $matricula['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja cancelar esta matrícula?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Nova Matrícula -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Matrícula</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="nova_matricula">
                    
                    <div class="mb-4">
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos_sem_matricula as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                        <select id="turma_id" name="turma_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="ano_letivo" class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo *</label>
                            <input type="number" id="ano_letivo" name="ano_letivo" required value="<?php echo date('Y'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_matricula" class="block text-sm font-semibold text-gray-700 mb-2">Data Matrícula *</label>
                            <input type="date" id="data_matricula" name="data_matricula" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacao" class="block text-sm font-semibold text-gray-700 mb-2">Observação</label>
                        <textarea id="observacao" name="observacao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações sobre a matrícula"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Realizar Matrícula
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
