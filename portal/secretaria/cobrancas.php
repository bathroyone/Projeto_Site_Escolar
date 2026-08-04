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

// Enviar notificação de cobrança
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_notificacao') {
    $mensalidade_id = intval($_POST['mensalidade_id'] ?? 0);
    $tipo_notificacao = sanitizeInput($_POST['tipo_notificacao'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $data_envio = sanitizeInput($_POST['data_envio'] ?? date('Y-m-d'));
    
    if (empty($mensalidade_id) || empty($tipo_notificacao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO cobrancas (mensalidade_id, tipo_notificacao, mensagem, data_envio, status, criado_por) VALUES (?, ?, ?, ?, 'enviada', ?)");
            $stmt->execute([$mensalidade_id, $tipo_notificacao, $mensagem, $data_envio, $_SESSION['usuario_id']]);
            
            logAudit('COBRANCA_ENVIO', 'cobrancas', $pdo->lastInsertId(), null, ['mensalidade_id' => $mensalidade_id, 'tipo' => $tipo_notificacao]);
            
            $success = 'Notificação de cobrança enviada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao enviar notificação: " . $e->getMessage());
            $error = 'Erro ao enviar notificação.';
        }
    }
}

// Obter mensalidades em atraso para cobrança
$mensalidades_cobranca = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, 
               u.nome_completo as aluno_nome,
               u.email as aluno_email,
               u.telefone as aluno_telefone,
               t.nome as turma_nome,
               r.nome_completo as responsavel_nome,
               r.email as responsavel_email,
               r.telefone as responsavel_telefone
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        LEFT JOIN turmas t ON mat.turma_id = t.id
        LEFT JOIN contratos_responsaveis c ON mat.aluno_id = c.aluno_id AND c.status = 'ativo'
        LEFT JOIN usuarios r ON c.responsavel_id = r.id
        WHERE m.status = 'pendente' AND m.data_vencimento < CURDATE()
        ORDER BY m.data_vencimento ASC
    ");
    $mensalidades_cobranca = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades para cobrança: " . $e->getMessage());
}

// Obter histórico de cobranças
$cobrancas_historico = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT c.*, 
               u.nome_completo as aluno_nome,
               m.valor as mensalidade_valor,
               m.referencia as mensalidade_referencia
        FROM cobrancas c
        JOIN mensalidades m ON c.mensalidade_id = m.id
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        ORDER BY c.data_envio DESC
        LIMIT 20
    ");
    $cobrancas_historico = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter histórico de cobranças: " . $e->getMessage());
}

// Tipos de notificação
$tipos_notificacao = [
    'email' => 'E-mail',
    'sms' => 'SMS',
    'whatsapp' => 'WhatsApp',
    'carta' => 'Carta'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Cobranças | Portal da Secretaria</title>
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
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">COBRANÇAS</span>
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

        <h2 class="text-2xl font-bold text-azul-principal mb-8">Gestão de Cobranças e Notificações</h2>

        <!-- Mensalidades para Cobrança -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-400">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-bell mr-2"></i>Mensalidades em Atraso - Enviar Cobrança
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($mensalidades_cobranca) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Responsável</th>
                                    <th class="px-4 sm:px-6 py-4">Contato</th>
                                    <th class="px-4 sm:px-6 py-4">Vencimento</th>
                                    <th class="px-4 sm:px-6 py-4">Valor</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensalidades_cobranca as $mensalidade): ?>
                                    <tr class="border-b border-gray-50 hover:bg-red-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mensalidade['responsavel_nome'] ?? $mensalidade['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">
                                            <div class="text-xs">
                                                <div><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($mensalidade['responsavel_email'] ?? $mensalidade['aluno_email'] ?? '-'); ?></div>
                                                <div><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($mensalidade['responsavel_telefone'] ?? $mensalidade['aluno_telefone'] ?? '-'); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <button onclick="enviarCobranca(<?php echo $mensalidade['id']; ?>, <?php echo $mensalidade['valor']; ?>, '<?php echo htmlspecialchars($mensalidade['aluno_nome']); ?>')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all text-sm font-semibold">
                                                <i class="fas fa-paper-plane mr-1"></i>Enviar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                        <p>Nenhuma mensalidade em atraso para cobrança.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Histórico de Cobranças -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-history mr-2"></i>Histórico de Cobranças
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($cobrancas_historico) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Referência</th>
                                    <th class="px-4 sm:px-6 py-4">Tipo Notificação</th>
                                    <th class="px-4 sm:px-6 py-4">Data Envio</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cobrancas_historico as $cobranca): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($cobranca['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('m/Y', strtotime($cobranca['mensalidade_referencia'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($tipos_notificacao[$cobranca['tipo_notificacao']] ?? $cobranca['tipo_notificacao']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($cobranca['data_envio'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                                <?php echo ucfirst($cobranca['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-4xl mb-4"></i>
                        <p>Nenhuma cobrança enviada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Enviar Cobrança -->
    <div id="modal-cobranca" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Enviar Cobrança</h3>
                <button onclick="document.getElementById('modal-cobranca').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="enviar_notificacao">
                <input type="hidden" name="mensalidade_id" id="mensalidade_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Notificação</label>
                    <select name="tipo_notificacao" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php foreach ($tipos_notificacao as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data Envio</label>
                    <input type="date" name="data_envio" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mensagem</label>
                    <textarea name="mensagem" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent" placeholder="Mensagem da cobrança..."></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-cobranca').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Enviar Cobrança
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

        function enviarCobranca(mensalidadeId, valor, alunoNome) {
            document.getElementById('mensalidade_id').value = mensalidadeId;
            document.querySelector('textarea[name="mensagem"]').value = `Prezado(a),\n\nInformamos que a mensalidade no valor de R$ ${valor.toFixed(2)} está em atraso. Por favor, regularize o pagamento o mais breve possível.\n\nAtenciosamente,\nSecretaria CEAA`;
            document.getElementById('modal-cobranca').classList.remove('hidden');
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
