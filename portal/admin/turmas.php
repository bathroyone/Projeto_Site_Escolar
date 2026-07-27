<?php
require_once '../config.php';

requireAdmin();

$error = '';
$success = '';

// Obter todas as turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT t.*, u.nome_completo as professor_nome FROM turmas t LEFT JOIN usuarios u ON t.professor_id = u.id ORDER BY t.serie, t.nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter professores disponíveis
$professores = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'professor' AND ativo = TRUE ORDER BY nome_completo");
    $professores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter professores: " . $e->getMessage());
}

// Adicionar nova turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    $professor_id = intval($_POST['professor_id'] ?? 0);
    
    if (empty($nome) || empty($serie)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se turma já existe
            $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND serie = ? AND ano_letivo = ?");
            $stmt->execute([$nome, $serie, $ano_letivo]);
            
            if ($stmt->fetch()) {
                $error = 'Esta turma já existe para este ano letivo.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO turmas (nome, serie, ano_letivo, professor_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $serie, $ano_letivo, $professor_id > 0 ? $professor_id : null]);
                
                $success = 'Turma adicionada com sucesso!';
                
                // Recarregar turmas
                $stmt = $pdo->query("SELECT t.*, u.nome_completo as professor_nome FROM turmas t LEFT JOIN usuarios u ON t.professor_id = u.id ORDER BY t.serie, t.nome");
                $turmas = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao adicionar turma: " . $e->getMessage());
            $error = 'Erro ao adicionar turma.';
        }
    }
}

// Excluir turma
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: turmas.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir turma: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas | Portal CEAA</title>
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
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">NOME DA ESCOLA</span>
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
                                <p class="text-sm text-gray-500">Administrador</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gerenciar Turmas</h1>
                <p class="text-gray-600 mt-2">Criar e gerenciar turmas e séries</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                Adicionar Turma
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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-4">Nome</th>
                            <th class="px-6 py-4">Série</th>
                            <th class="px-6 py-4">Ano Letivo</th>
                            <th class="px-6 py-4">Professor Responsável</th>
                            <th class="px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($turmas as $turma): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-verde-complementar to-teal-600 rounded-xl flex items-center justify-center text-white font-bold">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($turma['serie']); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo $turma['ano_letivo']; ?></td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php if ($turma['professor_nome']): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-sm font-semibold">
                                            <?php echo htmlspecialchars($turma['professor_nome']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Não atribuído</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="?action=excluir&id=<?php echo $turma['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta turma?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Turma -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Adicionar Nova Turma</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome da Turma *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: Turma A">
                    </div>
                    
                    <div class="mb-4">
                        <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série *</label>
                        <select id="serie" name="serie" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="1º Ano">1º Ano</option>
                            <option value="2º Ano">2º Ano</option>
                            <option value="3º Ano">3º Ano</option>
                            <option value="4º Ano">4º Ano</option>
                            <option value="5º Ano">5º Ano</option>
                            <option value="6º Ano">6º Ano</option>
                            <option value="7º Ano">7º Ano</option>
                            <option value="8º Ano">8º Ano</option>
                            <option value="9º Ano">9º Ano</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="ano_letivo" class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo *</label>
                        <input type="number" id="ano_letivo" name="ano_letivo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            value="<?php echo date('Y'); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label for="professor_id" class="block text-sm font-semibold text-gray-700 mb-2">Professor Responsável</label>
                        <select id="professor_id" name="professor_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Sem professor responsável</option>
                            <?php foreach ($professores as $prof): ?>
                                <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Turma
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
