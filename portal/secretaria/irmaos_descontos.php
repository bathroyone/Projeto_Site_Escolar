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

// Adicionar vínculo de irmãos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_irmao') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $irmao_id = intval($_POST['irmao_id'] ?? 0);
    $tipo_vinculo = sanitizeInput($_POST['tipo_vinculo'] ?? 'irmao');
    
    if (empty($aluno_id) || empty($irmao_id) || $aluno_id === $irmao_id) {
        $error = 'Por favor, selecione alunos diferentes.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se já existe vínculo
            $stmt = $pdo->prepare("SELECT id FROM vinculos_irmaos WHERE (aluno_id = ? AND irmao_id = ?) OR (aluno_id = ? AND irmao_id = ?)");
            $stmt->execute([$aluno_id, $irmao_id, $irmao_id, $aluno_id]);
            if ($stmt->fetch()) {
                $error = 'Vínculo de irmãos já existe.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO vinculos_irmaos (aluno_id, irmao_id, tipo_vinculo, criado_por) VALUES (?, ?, ?, ?)");
                $stmt->execute([$aluno_id, $irmao_id, $tipo_vinculo, $_SESSION['usuario_id']]);
                
                logAudit('IRMAO_CREATE', 'vinculos_irmaos', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'irmao_id' => $irmao_id]);
                
                $success = 'Vínculo de irmãos adicionado com sucesso!';
            }
        } catch (PDOException $e) {
            error_log("Erro ao adicionar vínculo: " . $e->getMessage());
            $error = 'Erro ao adicionar vínculo.';
        }
    }
}

// Aplicar desconto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'aplicar_desconto') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo_desconto = sanitizeInput($_POST['tipo_desconto'] ?? '');
    $valor_desconto = floatval($_POST['valor_desconto'] ?? 0);
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($aluno_id) || empty($tipo_desconto) || empty($valor_desconto)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO descontos (aluno_id, tipo_desconto, valor_desconto, motivo, criado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $tipo_desconto, $valor_desconto, $motivo, $_SESSION['usuario_id']]);
            
            logAudit('DESCONTO_CREATE', 'descontos', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'tipo' => $tipo_desconto]);
            
            $success = 'Desconto aplicado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao aplicar desconto: " . $e->getMessage());
            $error = 'Erro ao aplicar desconto.';
        }
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

// Obter vínculos de irmãos
$vinculos_irmaos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT v.*, 
               a1.nome_completo as aluno_nome,
               a2.nome_completo as irmao_nome
        FROM vinculos_irmaos v
        JOIN usuarios a1 ON v.aluno_id = a1.id
        JOIN usuarios a2 ON v.irmao_id = a2.id
        ORDER BY v.created_at DESC
    ");
    $vinculos_irmaos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter vínculos: " . $e->getMessage());
}

// Obter descontos aplicados
$descontos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT d.*, u.nome_completo as aluno_nome
        FROM descontos d
        JOIN usuarios u ON d.aluno_id = u.id
        ORDER BY d.created_at DESC
    ");
    $descontos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter descontos: " . $e->getMessage());
}

// Tipos de vínculo
$tipos_vinculo = ['irmao' => 'Irmão', 'irma' => 'Irmã', 'meio_irmao' => 'Meio-irmão', 'meio_irma' => 'Meio-irmã', 'primo' => 'Primo', 'outro' => 'Outro'];

// Tipos de desconto
$tipos_desconto = ['irmaos' => 'Desconto por irmãos', 'bolsa_estudos' => 'Bolsa de estudos', 'desconto_antecipado' => 'Desconto por pagamento antecipado', 'outro' => 'Outro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Irmãos e Descontos | Portal da Secretaria</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">IRMÃOS E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">DESCONTOS</span>
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
            <h2 class="text-2xl font-bold text-azul-principal">Controle de Irmãos e Descontos</h2>
            <div class="flex gap-3">
                <button onclick="document.getElementById('modal-irmao').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                    <i class="fas fa-users mr-2"></i>Adicionar Irmão
                </button>
                <button onclick="document.getElementById('modal-desconto').classList.remove('hidden')" class="bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-principal px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                    <i class="fas fa-percent mr-2"></i>Aplicar Desconto
                </button>
            </div>
        </div>

        <!-- Vínculos de Irmãos -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-users mr-2"></i>Vínculos de Irmãos
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($vinculos_irmaos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Irmão</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo de Vínculo</th>
                                    <th class="px-4 sm:px-6 py-4">Data Cadastro</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vinculos_irmaos as $vinculo): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($vinculo['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($vinculo['irmao_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($tipos_vinculo[$vinculo['tipo_vinculo']] ?? $vinculo['tipo_vinculo']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($vinculo['created_at'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <a href="?action=excluir_irmao&id=<?php echo $vinculo['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir este vínculo?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Nenhum vínculo de irmãos cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Descontos Aplicados -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro">
                <h3 class="text-xl font-display font-bold text-azul-escuro">
                    <i class="fas fa-percent mr-2"></i>Descontos Aplicados
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($descontos) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo Desconto</th>
                                    <th class="px-4 sm:px-6 py-4">Valor</th>
                                    <th class="px-4 sm:px-6 py-4">Motivo</th>
                                    <th class="px-4 sm:px-6 py-4">Data Aplicação</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($descontos as $desconto): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($desconto['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($tipos_desconto[$desconto['tipo_desconto']] ?? $desconto['tipo_desconto']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $desconto['valor_desconto']; ?>%</td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($desconto['motivo'] ?? '-', 0, 30)); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($desconto['created_at'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <a href="?action=excluir_desconto&id=<?php echo $desconto['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir este desconto?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-percent text-4xl mb-4"></i>
                        <p>Nenhum desconto aplicado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Irmão -->
    <div id="modal-irmao" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Adicionar Vínculo de Irmãos</h3>
                <button onclick="document.getElementById('modal-irmao').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar_irmao">
                
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Irmão</label>
                    <select name="irmao_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Selecione o irmão</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Vínculo</label>
                    <select name="tipo_vinculo" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php foreach ($tipos_vinculo as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-irmao').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Adicionar Vínculo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Aplicar Desconto -->
    <div id="modal-desconto" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-azul-escuro">Aplicar Desconto</h3>
                <button onclick="document.getElementById('modal-desconto').classList.add('hidden')" class="text-azul-escuro hover:text-azul-principal transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="aplicar_desconto">
                
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Desconto</label>
                    <select name="tipo_desconto" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php foreach ($tipos_desconto as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Valor do Desconto (%)</label>
                    <input type="number" name="valor_desconto" step="0.1" min="0" max="100" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                    <textarea name="motivo" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-desconto').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl hover:shadow-lg transition-all font-semibold">
                        Aplicar Desconto
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
