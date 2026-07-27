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

// Criar contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_contrato') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $responsavel_id = intval($_POST['responsavel_id'] ?? 0);
    $tipo_contrato = sanitizeInput($_POST['tipo_contrato'] ?? '');
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-d'));
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $valor_mensalidade = floatval($_POST['valor_mensalidade'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($responsavel_id) || empty($tipo_contrato)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO contratos_responsaveis (aluno_id, responsavel_id, tipo_contrato, data_inicio, data_fim, valor_mensalidade, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $responsavel_id, $tipo_contrato, $data_inicio, $data_fim ?: null, $valor_mensalidade, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('CONTRATO_CREATE', 'contratos_responsaveis', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'responsavel_id' => $responsavel_id]);
            
            $success = 'Contrato criado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar contrato: " . $e->getMessage());
            $error = 'Erro ao criar contrato.';
        }
    }
}

// Atualizar contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_contrato') {
    $contrato_id = intval($_POST['contrato_id'] ?? 0);
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $valor_mensalidade = floatval($_POST['valor_mensalidade'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'ativo');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($contrato_id)) {
        $error = 'ID do contrato é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE contratos_responsaveis SET data_fim = ?, valor_mensalidade = ?, status = ?, observacoes = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$data_fim ?: null, $valor_mensalidade, $status, $observacoes, $_SESSION['usuario_id'], $contrato_id]);
            
            logAudit('CONTRATO_UPDATE', 'contratos_responsaveis', $contrato_id, null, ['status' => $status]);
            
            $success = 'Contrato atualizado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar contrato: " . $e->getMessage());
            $error = 'Erro ao atualizar contrato.';
        }
    }
}

// Cancelar contrato
if (isset($_GET['action']) && $_GET['action'] === 'cancelar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE contratos_responsaveis SET status = 'cancelado', atualizado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        
        logAudit('CONTRATO_CANCEL', 'contratos_responsaveis', intval($_GET['id']), null, ['status' => 'cancelado']);
        
        header('Location: contratos.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao cancelar contrato: " . $e->getMessage());
        $error = 'Erro ao cancelar contrato.';
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

// Obter responsáveis
$responsaveis = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'responsavel' AND ativo = 1 ORDER BY nome_completo");
    $responsaveis = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter responsáveis: " . $e->getMessage());
}

// Obter contratos
$contratos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT c.*, 
               a.nome_completo as aluno_nome,
               r.nome_completo as responsavel_nome
        FROM contratos_responsaveis c
        JOIN usuarios a ON c.aluno_id = a.id
        JOIN usuarios r ON c.responsavel_id = r.id
        ORDER BY c.data_inicio DESC
    ");
    $contratos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter contratos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Contratos | Portal da Secretaria</title>
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
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">CONTRATOS</span>
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

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Gestão de Contratos de Responsáveis</h2>
            <button onclick="document.getElementById('modal-novo-contrato').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Novo Contrato
            </button>
        </div>

        <!-- Lista de Contratos -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Contratos Cadastrados
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($contratos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Responsável</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo Contrato</th>
                                    <th class="px-4 sm:px-6 py-4">Data Início</th>
                                    <th class="px-4 sm:px-6 py-4">Data Fim</th>
                                    <th class="px-4 sm:px-6 py-4">Mensalidade</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contratos as $contrato): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($contrato['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($contrato['responsavel_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($contrato['tipo_contrato']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($contrato['data_inicio'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $contrato['data_fim'] ? date('d/m/Y', strtotime($contrato['data_fim'])) : 'Indeterminado'; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">R$ <?php echo number_format($contrato['valor_mensalidade'], 2, ',', '.'); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($contrato['status']) {
                                                    'ativo' => 'bg-green-100 text-green-600',
                                                    'cancelado' => 'bg-red-100 text-red-600',
                                                    'suspenso' => 'bg-yellow-100 text-yellow-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst($contrato['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="editarContrato(<?php echo $contrato['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($contrato['status'] === 'ativo'): ?>
                                                    <a href="?action=cancelar&id=<?php echo $contrato['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente cancelar este contrato?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-file-contract text-4xl mb-4"></i>
                        <p>Nenhum contrato cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Contrato -->
    <div id="modal-novo-contrato" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Novo Contrato</h3>
                <button onclick="document.getElementById('modal-novo-contrato').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_contrato">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Aluno</label>
                        <select name="aluno_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Selecione o aluno</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Responsável</label>
                        <select name="responsavel_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($responsaveis as $responsavel): ?>
                                <option value="<?php echo $responsavel['id']; ?>"><?php echo htmlspecialchars($responsavel['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Contrato</label>
                        <select name="tipo_contrato" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                            <option value="semestral">Semestral</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Valor Mensalidade</label>
                        <input type="number" name="valor_mensalidade" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-novo-contrato').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Criar Contrato
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

        function editarContrato(id) {
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
