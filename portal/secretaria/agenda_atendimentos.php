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

// Agendar atendimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agendar_atendimento') {
    $responsavel_id = intval($_POST['responsavel_id'] ?? 0);
    $data = sanitizeInput($_POST['data'] ?? '');
    $horario = sanitizeInput($_POST['horario'] ?? '');
    $tipo_atendimento = sanitizeInput($_POST['tipo_atendimento'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($responsavel_id) || empty($data) || empty($horario) || empty($tipo_atendimento)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO agenda_atendimentos (responsavel_id, data, horario, tipo_atendimento, descricao, status, criado_por) VALUES (?, ?, ?, ?, ?, 'agendado', ?)");
            $stmt->execute([$responsavel_id, $data, $horario, $tipo_atendimento, $descricao, $_SESSION['usuario_id']]);
            
            logAudit('ATENDIMENTO_CREATE', 'agenda_atendimentos', $pdo->lastInsertId(), null, ['responsavel_id' => $responsavel_id, 'data' => $data]);
            
            $success = 'Atendimento agendado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao agendar atendimento: " . $e->getMessage());
            $error = 'Erro ao agendar atendimento.';
        }
    }
}

// Atualizar status do atendimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_status') {
    $atendimento_id = intval($_POST['atendimento_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'agendado');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($atendimento_id)) {
        $error = 'ID do atendimento é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE agenda_atendimentos SET status = ?, observacoes = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$status, $observacoes, $_SESSION['usuario_id'], $atendimento_id]);
            
            logAudit('ATENDIMENTO_UPDATE', 'agenda_atendimentos', $atendimento_id, null, ['status' => $status]);
            
            $success = 'Status do atendimento atualizado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            $error = 'Erro ao atualizar status.';
        }
    }
}

// Excluir atendimento
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM agenda_atendimentos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        logAudit('ATENDIMENTO_DELETE', 'agenda_atendimentos', intval($_GET['id']), null, []);
        
        header('Location: agenda_atendimentos.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir atendimento: " . $e->getMessage());
        $error = 'Erro ao excluir atendimento.';
    }
}

// Obter responsáveis
$responsaveis = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario IN ('responsavel', 'aluno') AND ativo = 1 ORDER BY nome_completo");
    $responsaveis = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter responsáveis: " . $e->getMessage());
}

// Obter atendimentos
$atendimentos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.nome_completo as responsavel_nome, u.tipo_usuario
        FROM agenda_atendimentos a
        JOIN usuarios u ON a.responsavel_id = u.id
        WHERE a.data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY a.data ASC, a.horario ASC
    ");
    $atendimentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter atendimentos: " . $e->getMessage());
}

// Obter estatísticas
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'agendado' THEN 1 END) as total_agendados,
            COUNT(CASE WHEN status = 'realizado' THEN 1 END) as total_realizados,
            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as total_cancelados
        FROM agenda_atendimentos
        WHERE data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Tipos de atendimento
$tipos_atendimento = [
    'matricula' => 'Matrícula',
    'financeiro' => 'Financeiro',
    'documentacao' => 'Documentação',
    'pedagogico' => 'Pedagógico',
    'administrativo' => 'Administrativo',
    'outro' => 'Outro'
];

// Status de atendimento
$status_atendimento = [
    'agendado' => 'Agendado',
    'realizado' => 'Realizado',
    'cancelado' => 'Cancelado',
    'remarcado' => 'Remarcado'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Atendimentos | Portal da Secretaria</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">AGENDA DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">ATENDIMENTOS</span>
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
                        <i class="fas fa-calendar-check text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Agendados</p>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $estatisticas['total_agendados'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Realizados</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_realizados'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-times-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Cancelados</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas['total_cancelados'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Agenda de Atendimentos</h2>
            <button onclick="document.getElementById('modal-novo-atendimento').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Novo Atendimento
            </button>
        </div>

        <!-- Lista de Atendimentos -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-calendar-alt mr-2"></i>Atendimentos Agendados
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($atendimentos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Responsável</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo</th>
                                    <th class="px-4 sm:px-6 py-4">Data</th>
                                    <th class="px-4 sm:px-6 py-4">Horário</th>
                                    <th class="px-4 sm:px-6 py-4">Descrição</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($atendimentos as $atendimento): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($atendimento['responsavel_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($tipos_atendimento[$atendimento['tipo_atendimento']] ?? $atendimento['tipo_atendimento']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($atendimento['data'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $atendimento['horario']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($atendimento['descricao'] ?? '-', 0, 30)); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($atendimento['status']) {
                                                    'agendado' => 'bg-yellow-100 text-yellow-600',
                                                    'realizado' => 'bg-green-100 text-green-600',
                                                    'cancelado' => 'bg-red-100 text-red-600',
                                                    'remarcado' => 'bg-blue-100 text-blue-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst($status_atendimento[$atendimento['status']] ?? $atendimento['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="atualizarStatus(<?php echo $atendimento['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=excluir&id=<?php echo $atendimento['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir este atendimento?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p>Nenhum atendimento agendado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Atendimento -->
    <div id="modal-novo-atendimento" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Novo Atendimento</h3>
                <button onclick="document.getElementById('modal-novo-atendimento').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="agendar_atendimento">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Responsável</label>
                    <select name="responsavel_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Selecione o responsável</option>
                        <?php foreach ($responsaveis as $responsavel): ?>
                            <option value="<?php echo $responsavel['id']; ?>"><?php echo htmlspecialchars($responsavel['nome_completo']); ?> (<?php echo ucfirst($responsavel['tipo_usuario']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Atendimento</label>
                    <select name="tipo_atendimento" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php foreach ($tipos_atendimento as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                        <input type="date" name="data" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Horário</label>
                        <input type="time" name="horario" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-novo-atendimento').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Agendar
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

        function atualizarStatus(id) {
            alert('Funcionalidade de atualização de status em desenvolvimento. ID: ' + id);
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
