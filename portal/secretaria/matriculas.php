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

// Criar matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_matricula') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $ano_letivo = sanitizeInput($_POST['ano_letivo'] ?? date('Y'));
    $data_matricula = sanitizeInput($_POST['data_matricula'] ?? date('Y-m-d'));
    $status = sanitizeInput($_POST['status'] ?? 'ativa');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se já existe matrícula para este aluno neste ano
            $stmt = $pdo->prepare("SELECT id FROM matriculas WHERE aluno_id = ? AND ano_letivo = ?");
            $stmt->execute([$aluno_id, $ano_letivo]);
            if ($stmt->fetch()) {
                $error = 'Este aluno já possui matrícula para este ano letivo.';
            } else {
                // Verificar vagas disponíveis na turma
                $stmt = $pdo->prepare("SELECT vagas FROM turmas WHERE id = ?");
                $stmt->execute([$turma_id]);
                $turma = $stmt->fetch();
                
                if ($turma && $turma['vagas'] > 0) {
                    $stmt = $pdo->prepare("INSERT INTO matriculas (aluno_id, turma_id, ano_letivo, data_matricula, status, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$aluno_id, $turma_id, $ano_letivo, $data_matricula, $status, $observacoes, $_SESSION['usuario_id']]);
                    
                    // Atualizar vagas da turma
                    $stmt = $pdo->prepare("UPDATE turmas SET vagas = vagas - 1 WHERE id = ?");
                    $stmt->execute([$turma_id]);
                    
                    logAudit('MATRICULA_CREATE', 'matriculas', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'turma_id' => $turma_id]);
                    
                    $success = 'Matrícula criada com sucesso!';
                } else {
                    $error = 'Não há vagas disponíveis nesta turma.';
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao criar matrícula: " . $e->getMessage());
            $error = 'Erro ao criar matrícula.';
        }
    }
}

// Atualizar matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_matricula') {
    $matricula_id = intval($_POST['matricula_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'ativa');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($matricula_id)) {
        $error = 'ID da matrícula é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE matriculas SET turma_id = ?, status = ?, observacoes = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$turma_id, $status, $observacoes, $_SESSION['usuario_id'], $matricula_id]);
            
            logAudit('MATRICULA_UPDATE', 'matriculas', $matricula_id, null, ['turma_id' => $turma_id, 'status' => $status]);
            
            $success = 'Matrícula atualizada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar matrícula: " . $e->getMessage());
            $error = 'Erro ao atualizar matrícula.';
        }
    }
}

// Cancelar matrícula
if (isset($_GET['action']) && $_GET['action'] === 'cancelar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        
        // Obter turma_id antes de cancelar
        $stmt = $pdo->prepare("SELECT turma_id FROM matriculas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $matricula = $stmt->fetch();
        
        if ($matricula) {
            // Devolver vaga à turma
            $stmt = $pdo->prepare("UPDATE turmas SET vagas = vagas + 1 WHERE id = ?");
            $stmt->execute([$matricula['turma_id']]);
            
            // Cancelar matrícula
            $stmt = $pdo->prepare("UPDATE matriculas SET status = 'cancelada', atualizado_por = ? WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
            
            logAudit('MATRICULA_CANCEL', 'matriculas', intval($_GET['id']), null, ['status' => 'cancelada']);
            
            header('Location: matriculas.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro ao cancelar matrícula: " . $e->getMessage());
        $error = 'Erro ao cancelar matrícula.';
    }
}

// Obter alunos sem matrícula no ano atual
$alunos_sem_matricula = [];
try {
    $pdo = getDBConnection();
    $ano_atual = date('Y');
    $stmt = $pdo->query("
        SELECT u.* 
        FROM usuarios u 
        WHERE u.tipo_usuario = 'aluno' 
        AND u.ativo = 1
        AND u.id NOT IN (
            SELECT m.aluno_id 
            FROM matriculas m 
            WHERE m.ano_letivo = $ano_atual
            AND m.status != 'cancelada'
        )
        ORDER BY u.nome_completo
    ");
    $alunos_sem_matricula = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Obter turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome, serie, vagas, ano_letivo FROM turmas WHERE ano_letivo = YEAR(CURDATE()) AND vagas > 0 ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter matrículas
$matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, u.cpf, t.nome as turma_nome, t.serie
        FROM matriculas m
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        ORDER BY m.data_matricula DESC
    ");
    $matriculas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Matrículas | Portal da Secretaria</title>
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
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">MATRÍCULAS</span>
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
            <h2 class="text-2xl font-bold text-azul-principal">Gestão de Matrículas</h2>
            <button onclick="document.getElementById('modal-nova-matricula').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Nova Matrícula
            </button>
        </div>

        <!-- Lista de Matrículas -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Matrículas Cadastradas
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($matriculas) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">CPF</th>
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Série</th>
                                    <th class="px-4 sm:px-6 py-4">Ano Letivo</th>
                                    <th class="px-4 sm:px-6 py-4">Data Matrícula</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matriculas as $matricula): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['cpf']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['turma_nome'] ?? '-'); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['serie'] ?? '-'); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['ano_letivo']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($matricula['status']) {
                                                    'ativa' => 'bg-green-100 text-green-600',
                                                    'cancelada' => 'bg-red-100 text-red-600',
                                                    'pendente' => 'bg-yellow-100 text-yellow-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst($matricula['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="editarMatricula(<?php echo $matricula['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($matricula['status'] === 'ativa'): ?>
                                                    <a href="?action=cancelar&id=<?php echo $matricula['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente cancelar esta matrícula?')">
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
                        <i class="fas fa-user-graduate text-4xl mb-4"></i>
                        <p>Nenhuma matrícula cadastrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Matrícula -->
    <div id="modal-nova-matricula" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Nova Matrícula</h3>
                <button onclick="document.getElementById('modal-nova-matricula').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_matricula">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Aluno</label>
                        <select name="aluno_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Selecione o aluno</option>
                            <?php foreach ($alunos_sem_matricula as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                        <select name="turma_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Selecione a turma</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?> - <?php echo htmlspecialchars($turma['serie']); ?> (Vagas: <?php echo $turma['vagas']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo</label>
                        <input type="number" name="ano_letivo" value="<?php echo date('Y'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data Matrícula</label>
                        <input type="date" name="data_matricula" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="ativa">Ativa</option>
                        <option value="pendente">Pendente</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-nova-matricula').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Criar Matrícula
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

        function editarMatricula(id) {
            // Implementar edição de matrícula
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
