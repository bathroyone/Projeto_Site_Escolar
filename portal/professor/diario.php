<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Criar tabela de diário de classe se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS diario_classe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    data_aula DATE NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    conteudo TEXT NOT NULL,
    atividades TEXT,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
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

// Adicionar entrada no diário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $data_aula = $_POST['data_aula'] ?? date('Y-m-d');
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    $atividades = sanitizeInput($_POST['atividades'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($turma_id) || empty($disciplina) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO diario_classe (professor_id, turma_id, data_aula, disciplina, conteudo, atividades, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$professor_id, $turma_id, $data_aula, $disciplina, $conteudo, $atividades, $observacoes]);
            $success = 'Entrada adicionada ao diário com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar entrada.';
        }
    }
}

// Excluir entrada
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM diario_classe WHERE id = ? AND professor_id = ?");
        $stmt->execute([intval($_GET['id']), $professor_id]);
        header('Location: diario.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir entrada.';
    }
}

// Obter entradas do diário
$diario = [];
try {
    $stmt = $conn->prepare("
        SELECT dc.*, t.nome as turma_nome, t.serie 
        FROM diario_classe dc 
        JOIN turmas t ON dc.turma_id = t.id 
        WHERE dc.professor_id = ?
        ORDER BY dc.data_aula DESC, dc.created_at DESC
    ");
    $stmt->execute([$professor_id]);
    $diario = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter diário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Classe | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Diário de Classe</h1>
                <p class="text-gray-600 mt-2">Registrar conteúdo das aulas</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Entrada
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

        <!-- Diário de Classe -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Aulas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Data</th>
                            <th class="px-4 sm:px-6 py-4">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Conteúdo</th>
                            <th class="px-4 sm:px-6 py-4 hidden lg:table-cell">Atividades</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diario as $entrada): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 bg-azul-principal/10 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-calendar text-azul-principal"></i>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo date('d/m/Y', strtotime($entrada['data_aula'])); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($entrada['turma_nome'] . ' - ' . $entrada['serie']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                        <?php echo htmlspecialchars($entrada['disciplina']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars(substr($entrada['conteudo'], 0, 50)) . '...'; ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden lg:table-cell"><?php echo htmlspecialchars($entrada['atividades'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="?action=excluir&id=<?php echo $entrada['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir esta entrada?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($diario)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-book-open text-4xl mb-2"></i>
                    <p>Nenhuma entrada registrada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nova Entrada -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Entrada no Diário</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                            <select id="turma_id" name="turma_id" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="">Selecione</option>
                                <?php foreach ($turmas as $turma): ?>
                                    <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="data_aula" class="block text-sm font-semibold text-gray-700 mb-2">Data da Aula *</label>
                            <input type="date" id="data_aula" name="data_aula" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina *</label>
                        <input type="text" id="disciplina" name="disciplina" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Matemática, Português, História">
                    </div>
                    
                    <div class="mb-4">
                        <label for="conteudo" class="block text-sm font-semibold text-gray-700 mb-2">Conteúdo *</label>
                        <textarea id="conteudo" name="conteudo" rows="4" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descreva o conteúdo abordado na aula"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="atividades" class="block text-sm font-semibold text-gray-700 mb-2">Atividades Realizadas</label>
                        <textarea id="atividades" name="atividades" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descreva as atividades realizadas"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações adicionais"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Entrada
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
