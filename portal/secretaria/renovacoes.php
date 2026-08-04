<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é secretaria
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'secretaria') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Criar renovação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_renovacao') {
    $matricula_id = intval($_POST['matricula_id'] ?? 0);
    $ano_letivo = sanitizeInput($_POST['ano_letivo'] ?? date('Y'));
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($matricula_id) || empty($ano_letivo) || empty($data_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se já existe renovação para esta matrícula neste ano
            $stmt = $pdo->prepare("SELECT id FROM renovacoes WHERE matricula_id = ? AND ano_letivo = ?");
            $stmt->execute([$matricula_id, $ano_letivo]);
            if ($stmt->fetch()) {
                $error = 'Já existe uma renovação para esta matrícula neste ano letivo.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO renovacoes (matricula_id, ano_letivo, data_inicio, status, observacoes, criado_por) VALUES (?, ?, ?, 'pendente', ?, ?)");
                $stmt->execute([$matricula_id, $ano_letivo, $data_inicio, $observacoes, $_SESSION['usuario_id']]);
                
                logAudit('RENOVACAO_CREATE', 'renovacoes', $pdo->lastInsertId(), null, ['matricula_id' => $matricula_id, 'ano_letivo' => $ano_letivo]);
                
                $success = 'Renovação criada com sucesso!';
            }
        } catch (PDOException $e) {
            error_log("Erro ao criar renovação: " . $e->getMessage());
            $error = 'Erro ao criar renovação.';
        }
    }
}

// Aprovar renovação
if (isset($_GET['action']) && $_GET['action'] === 'aprovar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE renovacoes SET status = 'aprovada', data_aprovacao = CURDATE(), atualizado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        
        logAudit('RENOVACAO_APROVAR', 'renovacoes', intval($_GET['id']), null, []);
        
        header('Location: renovacoes.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao aprovar renovação: " . $e->getMessage());
        $error = 'Erro ao aprovar renovação.';
    }
}

// Rejeitar renovação
if (isset($_GET['action']) && $_GET['action'] === 'rejeitar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE renovacoes SET status = 'rejeitada', data_aprovacao = CURDATE(), atualizado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        
        logAudit('RENOVACAO_REJEITAR', 'renovacoes', intval($_GET['id']), null, []);
        
        header('Location: renovacoes.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao rejeitar renovação: " . $e->getMessage());
        $error = 'Erro ao rejeitar renovação.';
    }
}

// Obter matrículas ativas
$matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.id, u.nome_completo, t.nome as turma_nome, t.serie
        FROM matriculas m
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE m.status = 'ativa'
        ORDER BY u.nome_completo
    ");
    $matriculas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas: " . $e->getMessage());
}

// Obter renovações
$renovacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT r.*, 
               u.nome_completo as aluno_nome,
               t.nome as turma_nome,
               t.serie
        FROM renovacoes r
        JOIN matriculas m ON r.matricula_id = m.id
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        ORDER BY r.ano_letivo DESC, r.created_at DESC
    ");
    $renovacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao.obter renovações: " . $e->getMessage());
}

// Obter estatísticas
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'pendente' THEN 1 END) as total_pendentes,
            COUNT(CASE WHEN status = 'aprovada' THEN 1 END) as total_aprovadas,
            COUNT(CASE WHEN status = 'rejeitada' THEN 1 END) as total_rejeitadas
        FROM renovacoes
        WHERE ano_letivo = YEAR(CURDATE())
    ");
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Renovações | Portal da Secretaria</title>
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
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">CONTROLE DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">RENOVAÇÕES</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="text-white text-xs sm:text-sm font-medium block"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 15)); ?></span>
                                <span class="text-white/70 text-xs">Secretaria</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>

                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Secretaria</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Secretaria</span>
                                </a>
                                <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
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

    <main class="px-4 sm:px-6 lg:px-8 py-8">
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

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Pendentes</p>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $estatisticas['total_pendentes'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Aprovadas</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_aprovadas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-times text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Rejeitadas</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas['total_rejeitadas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Controle de Renovações</h2>
            <button onclick="document.getElementById('modal-renovacao').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Nova Renovação
            </button>
        </div>

        <!-- Lista de Renovações -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-sync-alt mr-2"></i>Renovações
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($renovacoes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Ano Letivo</th>
                                    <th class="px-4 sm:px-6 py-4">Data Início</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($renovacoes as $renovacao): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($renovacao['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($renovacao['turma_nome'] ?? '-'); ?> - <?php echo htmlspecialchars($renovacao['serie'] ?? ''); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $renovacao['ano_letivo']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($renovacao['data_inicio'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($renovacao['status']) {
                                                    'aprovada' => 'bg-green-100 text-green-600',
                                                    'rejeitada' => 'bg-red-100 text-red-600',
                                                    'pendente' => 'bg-yellow-100 text-yellow-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst($renovacao['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <?php if ($renovacao['status'] === 'pendente'): ?>
                                                <div class="flex gap-2">
                                                    <a href="?action=aprovar&id=<?php echo $renovacao['id']; ?>" class="text-verde-complementar hover:text-verde-claro transition-colors" onclick="return confirm('Deseja aprovar esta renovação?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?action=rejeitar&id=<?php echo $renovacao['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja rejeitar esta renovação?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-sync-alt text-4xl mb-4"></i>
                        <p>Nenhuma renovação registrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Renovação -->
    <div id="modal-renovacao" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Nova Renovação</h3>
                <button onclick="document.getElementById('modal-renovacao').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_renovacao">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Matrícula</label>
                    <select name="matricula_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Selecione a matrícula</option>
                        <?php foreach ($matriculas as $matricula): ?>
                            <option value="<?php echo $matricula['id']; ?>"><?php echo htmlspecialchars($matricula['nome_completo']); ?> - <?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Sem turma'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo</label>
                    <input type="number" name="ano_letivo" value="<?php echo date('Y'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data Início</label>
                    <input type="date" name="data_inicio" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-renovacao').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Criar Renovação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
