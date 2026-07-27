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

// Atualizar vagas da turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_vagas') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $vagas = intval($_POST['vagas'] ?? 0);
    
    if (empty($turma_id) || $vagas < 0) {
        $error = 'Por favor, forneça valores válidos.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE turmas SET vagas = ? WHERE id = ?");
            $stmt->execute([$vagas, $turma_id]);
            
            logAudit('VAGAS_UPDATE', 'turmas', $turma_id, null, ['vagas' => $vagas]);
            
            $success = 'Vagas atualizadas com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar vagas: " . $e->getMessage());
            $error = 'Erro ao atualizar vagas.';
        }
    }
}

// Obter turmas com informações de vagas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.*, 
               COUNT(DISTINCT m.aluno_id) as alunos_matriculados,
               t.vagas - COUNT(DISTINCT CASE WHEN m.status = 'ativa' THEN m.aluno_id END) as vampas_disponiveis
        FROM turmas t
        LEFT JOIN matriculas m ON t.id = m.turma_id AND m.ano_letivo = t.ano_letivo AND m.status = 'ativa'
        WHERE t.ano_letivo = YEAR(CURDATE())
        GROUP BY t.id
        ORDER BY t.nome
    ");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter estatísticas gerais
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT t.id) as total_turmas,
            SUM(t.vagas) as total_vagas,
            SUM(t.vagas - COUNT(DISTINCT CASE WHEN m.status = 'ativa' THEN m.aluno_id END)) as total_vagas_disponiveis
        FROM turmas t
        LEFT JOIN matriculas m ON t.id = m.turma_id AND m.ano_letivo = t.ano_letivo AND m.status = 'ativa'
        WHERE t.ano_letivo = YEAR(CURDATE())
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
    <title>Controle de Vagas | Portal da Secretaria</title>
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
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">VAGAS</span>
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

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-school text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Total Turmas</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $estatisticas['total_turmas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-door-open text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Total Vagas</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_vagas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-azul-escuro text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Vagas Disponíveis</p>
                        <p class="text-3xl font-bold text-amarelo-destaque"><?php echo $estatisticas['total_vagas_disponiveis'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Controle de Vagas por Turma</h2>
        </div>

        <!-- Lista de Turmas -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Turmas e Vagas
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($turmas) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Série</th>
                                    <th class="px-4 sm:px-6 py-4">Ano Letivo</th>
                                    <th class="px-4 sm:px-6 py-4">Vagas Totais</th>
                                    <th class="px-4 sm:px-6 py-4">Alunos Matriculados</th>
                                    <th class="px-4 sm:px-6 py-4">Vagas Disponíveis</th>
                                    <th class="px-4 sm:px-6 py-4">Ocupação</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($turmas as $turma): ?>
                                    <?php 
                                    $vagas_disponiveis = $turma['vagas'] - $turma['alunos_matriculados'];
                                    $ocupacao = $turma['vagas'] > 0 ? round(($turma['alunos_matriculados'] / $turma['vagas']) * 100) : 0;
                                    $cor_ocupacao = $ocupacao >= 90 ? 'bg-red-500' : ($ocupacao >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($turma['serie']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($turma['ano_letivo']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $turma['vagas']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $turma['alunos_matriculados']; ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php echo $vagas_disponiveis > 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                                <?php echo $vagas_disponiveis; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full <?php echo $cor_ocupacao; ?>" style="width: <?php echo $ocupacao; ?>%"></div>
                                                </div>
                                                <span class="text-sm text-gray-600"><?php echo $ocupacao; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <button onclick="editarVagas(<?php echo $turma['id']; ?>, <?php echo $turma['vagas']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-school text-4xl mb-4"></i>
                        <p>Nenhuma turma cadastrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Editar Vagas -->
    <div id="modal-editar-vagas" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Editar Vagas</h3>
                <button onclick="document.getElementById('modal-editar-vagas').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="atualizar_vagas">
                <input type="hidden" name="turma_id" id="turma_id">
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantidade de Vagas</label>
                    <input type="number" name="vagas" id="vagas" min="0" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-editar-vagas').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Atualizar Vagas
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

        function editarVagas(turmaId, vagasAtuais) {
            document.getElementById('turma_id').value = turmaId;
            document.getElementById('vagas').value = vagasAtuais;
            document.getElementById('modal-editar-vagas').classList.remove('hidden');
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
