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

// Criar recuperação de nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_recuperacao') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $nota_original = floatval($_POST['nota_original'] ?? 0);
    $nota_recuperacao = floatval($_POST['nota_recuperacao'] ?? 0);
    $nota_final = floatval($_POST['nota_final'] ?? 0);
    $data_recuperacao = sanitizeInput($_POST['data_recuperacao'] ?? '');
    $tipo_recuperacao = sanitizeInput($_POST['tipo_recuperacao'] ?? 'prova');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO recuperacao_notas (professor_id, aluno_id, turma_id, disciplina, nota_original, nota_recuperacao, nota_final, data_recuperacao, tipo_recuperacao, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $aluno_id, $turma_id, $disciplina, $nota_original ?: null, $nota_recuperacao ?: null, $nota_final ?: null, $data_recuperacao ?: null, $tipo_recuperacao, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('RECUPERACAO_NOTA_CREATE', 'recuperacao_notas', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'disciplina' => $disciplina]);
            
            $success = 'Recuperação registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar recuperação.';
        }
    }
}

// Atualizar status
if (isset($_GET['action']) && $_GET['action'] === 'atualizar_status' && isset($_GET['id']) && isset($_GET['status'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE recuperacao_notas SET status = ? WHERE id = ?");
        $stmt->execute([sanitizeInput($_GET['status']), intval($_GET['id'])]);
        header('Location: recuperacao.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar status.';
    }
}

// Excluir recuperação
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM recuperacao_notas WHERE id = ? AND professor_id = ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: recuperacao.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir recuperação.';
    }
}

// Obter recuperações
$recuperacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT rn.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM recuperacao_notas rn 
        JOIN usuarios u ON rn.aluno_id = u.id 
        JOIN turmas t ON rn.turma_id = t.id 
        WHERE rn.professor_id = ? 
        ORDER BY rn.created_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $recuperacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter recuperações: " . $e->getMessage());
}

// Obter alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
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
    <title>Recuperação de Notas | Portal do Professor</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Recuperação de Notas</h1>
                <p class="text-gray-600 mt-2">Gestão de recuperações de alunos</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Recuperação
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

        <!-- Recuperações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Nota Original</th>
                            <th class="px-4 sm:px-6 py-4">Nota Recuperação</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recuperacoes as $recuperacao): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($recuperacao['aluno_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                        <?php echo htmlspecialchars($recuperacao['disciplina'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($recuperacao['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 font-bold text-gray-700"><?php echo $recuperacao['nota_original'] ? number_format($recuperacao['nota_original'], 1) : '-'; ?></td>
                                <td class="px-4 sm:px-6 py-4 font-bold text-azul-principal"><?php echo $recuperacao['nota_recuperacao'] ? number_format($recuperacao['nota_recuperacao'], 1) : '-'; ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($recuperacao['status']) {
                                            'pendente' => 'bg-yellow-100 text-yellow-600',
                                            'aplicada' => 'bg-blue-100 text-blue-600',
                                            'aprovado' => 'bg-green-100 text-green-600',
                                            'reprovado' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($recuperacao['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex gap-1">
                                        <?php if ($recuperacao['status'] === 'pendente'): ?>
                                            <a href="?action=atualizar_status&id=<?php echo $recuperacao['id']; ?>&status=aplicada" class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors" title="Aplicar">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($recuperacao['status'] === 'aplicada'): ?>
                                            <a href="?action=atualizar_status&id=<?php echo $recuperacao['id']; ?>&status=aprovado" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Aprovar">
                                                <i class="fas fa-thumbs-up"></i>
                                            </a>
                                            <a href="?action=atualizar_status&id=<?php echo $recuperacao['id']; ?>&status=reprovado" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Reprovar">
                                                <i class="fas fa-thumbs-down"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=excluir&id=<?php echo $recuperacao['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($recuperacoes)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-redo text-4xl mb-2"></i>
                    <p>Nenhuma recuperação registrada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nova Recuperação -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Recuperação de Nota</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_recuperacao">
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                            <select id="aluno_id" name="aluno_id" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="">Selecione</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
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
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina</label>
                            <input type="text" id="disciplina" name="disciplina"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Ex: Matemática">
                        </div>
                        
                        <div>
                            <label for="tipo_recuperacao" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                            <select id="tipo_recuperacao" name="tipo_recuperacao"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="prova">Prova</option>
                                <option value="trabalho">Trabalho</option>
                                <option value="projeto">Projeto</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="nota_original" class="block text-sm font-semibold text-gray-700 mb-2">Nota Original</label>
                            <input type="number" id="nota_original" name="nota_original" step="0.1" max="10"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="0.0">
                        </div>
                        
                        <div>
                            <label for="nota_recuperacao" class="block text-sm font-semibold text-gray-700 mb-2">Nota Recuperação</label>
                            <input type="number" id="nota_recuperacao" name="nota_recuperacao" step="0.1" max="10"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="0.0">
                        </div>
                        
                        <div>
                            <label for="nota_final" class="block text-sm font-semibold text-gray-700 mb-2">Nota Final</label>
                            <input type="number" id="nota_final" name="nota_final" step="0.1" max="10"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="0.0">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="data_recuperacao" class="block text-sm font-semibold text-gray-700 mb-2">Data da Recuperação</label>
                        <input type="date" id="data_recuperacao" name="data_recuperacao"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações sobre a recuperação"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Recuperação
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
