<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Solicitar transferência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'solicitar_transferencia') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_origem_id = intval($_POST['turma_origem_id'] ?? 0);
    $turma_destino_id = intval($_POST['turma_destino_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'interna');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($aluno_id) || empty($turma_origem_id) || empty($turma_destino_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($turma_origem_id == $turma_destino_id) {
        $error = 'A turma de destino deve ser diferente da turma de origem.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO transferencias (aluno_id, turma_origem_id, turma_destino_id, tipo, motivo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $turma_origem_id, $turma_destino_id, $tipo, $motivo]);
            
            logAudit('TRANSFERENCIA_SOLICITAR', 'transferencias', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'tipo' => $tipo]);
            
            $success = 'Solicitação de transferência criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar solicitação de transferência.';
        }
    }
}

// Aprovar transferência
if (isset($_GET['action']) && $_GET['action'] === 'aprovar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE transferencias SET status = 'aprovada', data_aprovacao = NOW(), aprovado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        
        // Atualizar turma do aluno
        $stmt = $pdo->prepare("UPDATE transferencias SET status = 'concluida' WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        logAudit('TRANSFERENCIA_APROVAR', 'transferencias', intval($_GET['id']), null, ['status' => 'aprovada']);
        
        header('Location: transferencias.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao aprovar transferência.';
    }
}

// Rejeitar transferência
if (isset($_GET['action']) && $_GET['action'] === 'rejeitar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE transferencias SET status = 'rejeitada', data_aprovacao = NOW(), aprovado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        
        logAudit('TRANSFERENCIA_REJEITAR', 'transferencias', intval($_GET['id']), null, ['status' => 'rejeitada']);
        
        header('Location: transferencias.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao rejeitar transferência.';
    }
}

// Obter transferências
$transferencias = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.*, u.nome_completo as aluno_nome, t1.nome as turma_origem_nome, t2.nome as turma_destino_nome, u2.nome_completo as aprovador_nome
        FROM transferencias t
        JOIN usuarios u ON t.aluno_id = u.id
        JOIN turmas t1 ON t.turma_origem_id = t1.id
        JOIN turmas t2 ON t.turma_destino_id = t2.id
        LEFT JOIN usuarios u2 ON t.aprovado_por = u2.id
        ORDER BY t.data_solicitacao DESC
    ");
    $transferencias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter transferências: " . $e->getMessage());
}

// Obter alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo, turma FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Obter turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM turmas WHERE ano_letivo = YEAR(CURDATE()) ORDER BY nome");
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
    <title>Gestão de Transferências | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Transferências</h1>
                <p class="text-gray-600 mt-2">Transferências entre turmas</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Transferência
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

        <!-- Transferências -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Turma Origem</th>
                            <th class="px-4 sm:px-6 py-4">Turma Destino</th>
                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Solicitação</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transferencias as $transferencia): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($transferencia['aluno_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($transferencia['turma_origem_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($transferencia['turma_destino_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_tipo = match($transferencia['tipo']) {
                                            'interna' => 'bg-blue-100 text-blue-600',
                                            'externa_entrada' => 'bg-green-100 text-green-600',
                                            'externa_saida' => 'bg-orange-100 text-orange-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_tipo;
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transferencia['tipo'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y', strtotime($transferencia['data_solicitacao'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($transferencia['status']) {
                                            'pendente' => 'bg-yellow-100 text-yellow-600',
                                            'aprovada' => 'bg-green-100 text-green-600',
                                            'rejeitada' => 'bg-red-100 text-red-600',
                                            'concluida' => 'bg-blue-100 text-blue-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($transferencia['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <?php if ($transferencia['status'] === 'pendente'): ?>
                                        <div class="flex gap-2">
                                            <a href="?action=aprovar&id=<?php echo $transferencia['id']; ?>" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Aprovar">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?action=rejeitar&id=<?php echo $transferencia['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Rejeitar">
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
            
            <?php if (empty($transferencias)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-exchange-alt text-4xl mb-2"></i>
                    <p>Nenhuma transferência solicitada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nova Transferência -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Transferência</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="solicitar_transferencia">
                    
                    <div class="mb-4">
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?> (<?php echo htmlspecialchars($aluno['turma']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="turma_origem_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma de Origem *</label>
                        <select id="turma_origem_id" name="turma_origem_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="turma_destino_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma de Destino *</label>
                        <select id="turma_destino_id" name="turma_destino_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="interna">Interna</option>
                            <option value="externa_entrada">Externa (Entrada)</option>
                            <option value="externa_saida">Externa (Saída)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                        <textarea id="motivo" name="motivo" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Motivo da transferência"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Solicitar Transferência
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
