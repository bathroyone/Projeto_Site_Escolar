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

// Obter configurações de frequência
$configuracoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM frequencia_configuracoes WHERE ativo = 1");
    $configuracoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter configurações: " . $e->getMessage());
}

// Atualizar configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_config') {
    $tipo_alerto = sanitizeInput($_POST['tipo_alerto'] ?? '');
    $limite_percentual = floatval($_POST['limite_percentual'] ?? 0);
    $notificar_responsavel = isset($_POST['notificar_responsavel']) ? 1 : 0;
    $notificar_professor = isset($_POST['notificar_professor']) ? 1 : 0;
    $notificar_secretaria = isset($_POST['notificar_secretaria']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE frequencia_configuracoes 
            SET limite_percentual = ?, notificar_responsavel = ?, notificar_professor = ?, notificar_secretaria = ?
            WHERE tipo_alerto = ?
        ");
        $stmt->execute([$limite_percentual, $notificar_responsavel, $notificar_professor, $notificar_secretaria, $tipo_alerto]);
        
        logAudit('FREQUENCIA_CONFIG_UPDATE', 'frequencia_configuracoes', null, null, ['tipo_alerto' => $tipo_alerto]);
        
        $success = 'Configuração atualizada com sucesso!';
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar configuração.';
    }
}

// Obter alertas não resolvidos
$alertas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT fa.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM frequencia_alertas fa 
        JOIN usuarios u ON fa.aluno_id = u.id 
        JOIN turmas t ON fa.turma_id = t.id 
        WHERE fa.resolvido = 0 
        ORDER BY fa.percentual_atual ASC
    ");
    $alertas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alertas: " . $e->getMessage());
}

// Resolver alerta
if (isset($_GET['action']) && $_GET['action'] === 'resolver_alerta' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE frequencia_alertas SET resolvido = 1, data_resolucao = NOW() WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: frequencia_global.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao resolver alerta.';
    }
}

// Obter resumo de frequência por turma
$frequencia_turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.nome as turma_nome, t.serie, 
               COUNT(DISTINCT fr.aluno_id) as total_alunos,
               AVG(fr.percentual_frequencia) as media_frequencia
        FROM turmas t
        LEFT JOIN frequencia_resumo fr ON t.id = fr.turma_id AND fr.ano_letivo = YEAR(CURDATE())
        WHERE t.ano_letivo = YEAR(CURDATE())
        GROUP BY t.id, t.nome, t.serie
        ORDER BY media_frequencia ASC
    ");
    $frequencia_turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter frequência por turma: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Frequência Global | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Controle de Frequência Global</h1>
                <p class="text-gray-600 mt-2">Monitoramento e alertas de frequência</p>
            </div>
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

        <!-- Alertas Ativos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-xl font-display font-bold text-azul-principal">Alertas de Frequência</h2>
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo count($alertas) > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                    <?php echo count($alertas); ?> alertas ativos
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Tipo Alerta</th>
                            <th class="px-4 sm:px-6 py-4">Frequência</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertas as $alerta): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($alerta['aluno_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($alerta['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_alerta = match($alerta['tipo_alerto']) {
                                            'baixa_frequencia' => 'bg-yellow-100 text-yellow-600',
                                            'falta_excessiva' => 'bg-orange-100 text-orange-600',
                                            'risco_reprovacao' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_alerta;
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $alerta['tipo_alerto'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="text-2xl font-bold <?php echo $alerta['percentual_atual'] < 50 ? 'text-red-600' : ($alerta['percentual_atual'] < 75 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo $alerta['percentual_atual']; ?>%
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $alerta['notificado'] ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo $alerta['notificado'] ? 'Notificado' : 'Pendente'; ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="?action=resolver_alerta&id=<?php echo $alerta['id']; ?>" class="px-3 py-1 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors text-sm">
                                        <i class="fas fa-check mr-1"></i>Resolver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($alertas)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>Nenhum alerta ativo no momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Frequência por Turma -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Frequência por Turma</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Série</th>
                            <th class="px-4 sm:px-6 py-4">Total Alunos</th>
                            <th class="px-4 sm:px-6 py-4">Média Frequência</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($frequencia_turmas as $turma): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($turma['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($turma['serie']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $turma['total_alunos']; ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="text-2xl font-bold <?php echo $turma['media_frequencia'] < 50 ? 'text-red-600' : ($turma['media_frequencia'] < 75 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo number_format($turma['media_frequencia'], 1); ?>%
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match(true) {
                                            $turma['media_frequencia'] < 50 => 'bg-red-100 text-red-600',
                                            $turma['media_frequencia'] < 75 => 'bg-yellow-100 text-yellow-600',
                                            default => 'bg-green-100 text-green-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo $turma['media_frequencia'] < 50 ? 'Crítico' : ($turma['media_frequencia'] < 75 ? 'Atenção' : 'Normal'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configurações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">Configurações de Alertas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($configuracoes as $config): ?>
                    <form method="POST" action="" class="border border-gray-200 rounded-xl p-4">
                        <input type="hidden" name="action" value="atualizar_config">
                        <input type="hidden" name="tipo_alerto" value="<?php echo $config['tipo_alerto']; ?>">
                        
                        <h3 class="font-bold text-gray-800 mb-4"><?php echo ucfirst(str_replace('_', ' ', $config['tipo_alerto'])); ?></h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Limite (%)</label>
                            <input type="number" name="limite_percentual" step="0.01" value="<?php echo $config['limite_percentual']; ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="notificar_responsavel" <?php echo $config['notificar_responsavel'] ? 'checked' : ''; ?> class="w-5 h-5 text-azul-principal">
                                <span class="text-sm text-gray-700">Notificar Responsável</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="notificar_professor" <?php echo $config['notificar_professor'] ? 'checked' : ''; ?> class="w-5 h-5 text-azul-principal">
                                <span class="text-sm text-gray-700">Notificar Professor</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="notificar_secretaria" <?php echo $config['notificar_secretaria'] ? 'checked' : ''; ?> class="w-5 h-5 text-azul-principal">
                                <span class="text-sm text-gray-700">Notificar Secretaria</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            Atualizar
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
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
