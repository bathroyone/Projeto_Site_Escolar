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

// Adicionar registro ao histórico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_registro') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $ano_letivo = sanitizeInput($_POST['ano_letivo'] ?? date('Y'));
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $situacao = sanitizeInput($_POST['situacao'] ?? 'aprovado');
    $media_final = floatval($_POST['media_final'] ?? 0);
    $frequencia = floatval($_POST['frequencia'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($serie)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO historico_escolar (aluno_id, ano_letivo, serie, turma, situacao, media_final, frequencia, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $ano_letivo, $serie, $turma, $situacao, $media_final, $frequencia, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('HISTORICO_CREATE', 'historico_escolar', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'ano_letivo' => $ano_letivo]);
            
            $success = 'Registro adicionado ao histórico com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao adicionar registro: " . $e->getMessage());
            $error = 'Erro ao adicionar registro.';
        }
    }
}

// Atualizar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_registro') {
    $registro_id = intval($_POST['registro_id'] ?? 0);
    $situacao = sanitizeInput($_POST['situacao'] ?? 'aprovado');
    $media_final = floatval($_POST['media_final'] ?? 0);
    $frequencia = floatval($_POST['frequencia'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($registro_id)) {
        $error = 'ID do registro é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE historico_escolar SET situacao = ?, media_final = ?, frequencia = ?, observacoes = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$situacao, $media_final, $frequencia, $observacoes, $_SESSION['usuario_id'], $registro_id]);
            
            logAudit('HISTORICO_UPDATE', 'historico_escolar', $registro_id, null, ['situacao' => $situacao]);
            
            $success = 'Registro atualizado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar registro: " . $e->getMessage());
            $error = 'Erro ao atualizar registro.';
        }
    }
}

// Excluir registro
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM historico_escolar WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        logAudit('HISTORICO_DELETE', 'historico_escolar', intval($_GET['id']), null, []);
        
        header('Location: historico_escolar.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir registro: " . $e->getMessage());
        $error = 'Erro ao excluir registro.';
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

// Obter histórico
$historico = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT h.*, u.nome_completo as aluno_nome
        FROM historico_escolar h
        JOIN usuarios u ON h.aluno_id = u.id
        ORDER BY h.ano_letivo DESC, h.serie DESC, u.nome_completo
    ");
    $historico = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter histórico: " . $e->getMessage());
}

// Obter séries disponíveis
$series = ['1º Ano', '2º Ano', '3º Ano', '4º Ano', '5º Ano', '6º Ano', '7º Ano', '8º Ano', '9º Ano', '1º Ano EM', '2º Ano EM', '3º Ano EM'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Escolar | Portal da Secretaria</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">HISTÓRICO</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">ESCOLAR</span>
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
            <h2 class="text-2xl font-bold text-azul-principal">Histórico Escolar dos Alunos</h2>
            <button onclick="document.getElementById('modal-novo-registro').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Novo Registro
            </button>
        </div>

        <!-- Lista de Histórico -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Registros de Histórico
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($historico) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Ano Letivo</th>
                                    <th class="px-4 sm:px-6 py-4">Série</th>
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Média Final</th>
                                    <th class="px-4 sm:px-6 py-4">Frequência</th>
                                    <th class="px-4 sm:px-6 py-4">Situação</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $registro): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($registro['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($registro['ano_letivo']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($registro['serie']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($registro['turma'] ?? '-'); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo number_format($registro['media_final'], 1); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo number_format($registro['frequencia'], 1); ?>%</td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_situacao = match($registro['situacao']) {
                                                    'aprovado' => 'bg-green-100 text-green-600',
                                                    'reprovado' => 'bg-red-100 text-red-600',
                                                    'recuperacao' => 'bg-yellow-100 text-yellow-600',
                                                    'transferido' => 'bg-blue-100 text-blue-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_situacao;
                                                ?>">
                                                <?php echo ucfirst($registro['situacao']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="editarRegistro(<?php echo $registro['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=excluir&id=<?php echo $registro['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir este registro?')">
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
                        <i class="fas fa-history text-4xl mb-4"></i>
                        <p>Nenhum registro de histórico cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Registro -->
    <div id="modal-novo-registro" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Novo Registro de Histórico</h3>
                <button onclick="document.getElementById('modal-novo-registro').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar_registro">
                
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo</label>
                        <input type="number" name="ano_letivo" value="<?php echo date('Y'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                        <select name="serie" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <?php foreach ($series as $serie): ?>
                                <option value="<?php echo $serie; ?>"><?php echo $serie; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                        <input type="text" name="turma" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Média Final</label>
                        <input type="number" name="media_final" step="0.1" min="0" max="10" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência (%)</label>
                        <input type="number" name="frequencia" step="0.1" min="0" max="100" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Situação</label>
                        <select name="situacao" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="aprovado">Aprovado</option>
                            <option value="reprovado">Reprovado</option>
                            <option value="recuperacao">Recuperação</option>
                            <option value="transferido">Transferido</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-novo-registro').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Adicionar Registro
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

        function editarRegistro(id) {
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
