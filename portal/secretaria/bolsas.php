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

// Conceder bolsa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'conceder_bolsa') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo_bolsa = sanitizeInput($_POST['tipo_bolsa'] ?? '');
    $percentual = floatval($_POST['percentual'] ?? 0);
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-d'));
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($aluno_id) || empty($tipo_bolsa) || empty($percentual)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO bolsas (aluno_id, tipo_bolsa, percentual, data_inicio, data_fim, motivo, status, criado_por) VALUES (?, ?, ?, ?, ?, ?, 'ativa', ?)");
            $stmt->execute([$aluno_id, $tipo_bolsa, $percentual, $data_inicio, $data_fim ?: null, $motivo, $_SESSION['usuario_id']]);
            
            logAudit('BOLSA_CREATE', 'bolsas', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'tipo' => $tipo_bolsa]);
            
            $success = 'Bolsa concedida com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao conceder bolsa: " . $e->getMessage());
            $error = 'Erro ao conceder bolsa.';
        }
    }
}

// Atualizar bolsa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_bolsa') {
    $bolsa_id = intval($_POST['bolsa_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'ativa');
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    
    if (empty($bolsa_id)) {
        $error = 'ID da bolsa é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE bolsas SET status = ?, data_fim = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$status, $data_fim ?: null, $_SESSION['usuario_id'], $bolsa_id]);
            
            logAudit('BOLSA_UPDATE', 'bolsas', $bolsa_id, null, ['status' => $status]);
            
            $success = 'Bolsa atualizada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar bolsa: " . $e->getMessage());
            $error = 'Erro ao atualizar bolsa.';
        }
    }
}

// Excluir bolsa
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM bolsas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        logAudit('BOLSA_DELETE', 'bolsas', intval($_GET['id']), null, []);
        
        header('Location: bolsas.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir bolsa: " . $e->getMessage());
        $error = 'Erro ao excluir bolsa.';
    }
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

// Obter bolsas
$bolsas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT b.*, u.nome_completo as aluno_nome
        FROM bolsas b
        JOIN usuarios u ON b.aluno_id = u.id
        ORDER BY b.data_inicio DESC
    ");
    $bolsas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter bolsas: " . $e->getMessage());
}

// Obter estatísticas
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'ativa' THEN 1 END) as total_ativas,
            COUNT(CASE WHEN status = 'suspensa' THEN 1 END) as total_suspensas,
            COUNT(CASE WHEN status = 'encerrada' THEN 1 END) as total_encerradas,
            AVG(CASE WHEN status = 'ativa' THEN percentual END) as media_percentual
        FROM bolsas
    ");
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Tipos de bolsa
$tipos_bolsa = [
    'merito' => 'Bolsa de Mérito',
    'social' => 'Bolsa Social',
    'esportiva' => 'Bolsa Esportiva',
    'artistica' => 'Bolsa Artística',
    'irmaos' => 'Desconto por Irmãos',
    'funcionario' => 'Bolsa Funcionário',
    'outro' => 'Outro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Bolsas e Descontos | Portal da Secretaria</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">GESTÃO DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">BOLSAS E DESCONTOS</span>
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
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-award text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Ativas</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_ativas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-pause text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Suspensas</p>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $estatisticas['total_suspensas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-times-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Encerradas</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas['total_encerradas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-percentage text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Média %</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo number_format($estatisticas['media_percentual'] ?? 0, 1); ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Gestão de Bolsas e Descontos</h2>
            <button onclick="document.getElementById('modal-nova-bolsa').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Conceder Bolsa
            </button>
        </div>

        <!-- Lista de Bolsas -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-award mr-2"></i>Bolsas Concedidas
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($bolsas) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo</th>
                                    <th class="px-4 sm:px-6 py-4">Percentual</th>
                                    <th class="px-4 sm:px-6 py-4">Período</th>
                                    <th class="px-4 sm:px-6 py-4">Motivo</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bolsas as $bolsa): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($bolsa['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($tipos_bolsa[$bolsa['tipo_bolsa']] ?? $bolsa['tipo_bolsa']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $bolsa['percentual']; ?>%</td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">
                                            <?php echo date('d/m/Y', strtotime($bolsa['data_inicio'])); ?>
                                            <?php if ($bolsa['data_fim']): ?>
                                                a <?php echo date('d/m/Y', strtotime($bolsa['data_fim'])); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($bolsa['motivo'] ?? '-', 0, 30)); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($bolsa['status']) {
                                                    'ativa' => 'bg-green-100 text-green-600',
                                                    'suspensa' => 'bg-yellow-100 text-yellow-600',
                                                    'encerrada' => 'bg-red-100 text-red-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst($bolsa['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="editarBolsa(<?php echo $bolsa['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=excluir&id=<?php echo $bolsa['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir esta bolsa?')">
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
                        <i class="fas fa-award text-4xl mb-4"></i>
                        <p>Nenhuma bolsa concedida.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Bolsa -->
    <div id="modal-nova-bolsa" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Conceder Bolsa</h3>
                <button onclick="document.getElementById('modal-nova-bolsa').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="conceder_bolsa">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Aluno</label>
                    <select name="aluno_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Selecione o aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Bolsa</label>
                    <select name="tipo_bolsa" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php foreach ($tipos_bolsa as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Percentual de Desconto (%)</label>
                    <input type="number" name="percentual" step="0.1" min="0" max="100" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data Início</label>
                        <input type="date" name="data_inicio" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data Fim (opcional)</label>
                        <input type="date" name="data_fim" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                    <textarea name="motivo" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-nova-bolsa').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Conceder Bolsa
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

        function editarBolsa(id) {
            alert('Funcionalidade de edição em desenvolvimento. ID: ' + id);
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
