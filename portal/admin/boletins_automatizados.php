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

// Obter configuração de boletins
$configuracao = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM boletins_configuracoes WHERE ativo = 1 ORDER BY ano_letivo DESC LIMIT 1");
    $configuracao = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter configuração: " . $e->getMessage());
}

// Atualizar configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_config') {
    $media_minima = floatval($_POST['media_minima'] ?? 7.00);
    $frequencia_minima = floatval($_POST['frequencia_minima'] ?? 75.00);
    $numero_bimestres = intval($_POST['numero_bimestres'] ?? 4);
    
    try {
        if ($configuracao) {
            $stmt = $pdo->prepare("UPDATE boletins_configuracoes SET media_minima_aprovacao = ?, frequencia_minima_aprovacao = ?, numero_bimestres = ? WHERE id = ?");
            $stmt->execute([$media_minima, $frequencia_minima, $numero_bimestres, $configuracao['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO boletins_configuracoes (ano_letivo, media_minima_aprovacao, frequencia_minima_aprovacao, numero_bimestres) VALUES (?, ?, ?, ?)");
            $stmt->execute([date('Y'), $media_minima, $frequencia_minima, $numero_bimestres]);
        }
        
        logAudit('BOLETIM_CONFIG_UPDATE', 'boletins_configuracoes', $configuracao['id'] ?? null, null, ['media_minima' => $media_minima]);
        
        $success = 'Configuração atualizada com sucesso!';
        
        // Recarregar configuração
        $stmt = $pdo->query("SELECT * FROM boletins_configuracoes WHERE ativo = 1 ORDER BY ano_letivo DESC LIMIT 1");
        $configuracao = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar configuração.';
    }
}

// Gerar boletins em lote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gerar_boletins') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $bimestre = intval($_POST['bimestre'] ?? 1);
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    
    if (empty($turma_id)) {
        $error = 'Por favor, selecione a turma.';
    } else {
        try {
            // Obter alunos da turma
            $stmt = $pdo->prepare("SELECT id, nome_completo FROM usuarios WHERE turma = ? AND tipo_usuario = 'aluno' AND ativo = 1");
            $stmt->execute([$turma_id]);
            $alunos = $stmt->fetchAll();
            
            $gerados = 0;
            foreach ($alunos as $aluno) {
                // Verificar se boletim já existe
                $stmt_check = $pdo->prepare("SELECT id FROM boletins_gerados WHERE aluno_id = ? AND turma_id = ? AND ano_letivo = ? AND bimestre = ?");
                $stmt_check->execute([$aluno['id'], $turma_id, $ano_letivo, $bimestre]);
                
                if (!$stmt_check->fetch()) {
                    // Criar boletim
                    $stmt_insert = $pdo->prepare("INSERT INTO boletins_gerados (aluno_id, turma_id, ano_letivo, bimestre, gerado_por) VALUES (?, ?, ?, ?, ?)");
                    $stmt_insert->execute([$aluno['id'], $turma_id, $ano_letivo, $bimestre, $_SESSION['usuario_id']]);
                    $gerados++;
                }
            }
            
            logAudit('BOLETIM_GERAR_LOTE', 'boletins_gerados', null, null, ['turma_id' => $turma_id, 'bimestre' => $bimestre, 'gerados' => $gerados]);
            
            $success = "$gerados boletins gerados com sucesso!";
        } catch (PDOException $e) {
            $error = 'Erro ao gerar boletins.';
        }
    }
}

// Obter boletins gerados
$boletins = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT bg.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM boletins_gerados bg 
        JOIN usuarios u ON bg.aluno_id = u.id 
        JOIN turmas t ON bg.turma_id = t.id 
        ORDER BY bg.created_at DESC
    ");
    $boletins = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter boletins: " . $e->getMessage());
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
    <title>Boletins Automatizados | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Boletins Automatizados</h1>
                <p class="text-gray-600 mt-2">Geração e gestão de boletins escolares</p>
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

        <!-- Configuração -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">Configuração de Boletins</h2>
            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <input type="hidden" name="action" value="atualizar_config">
                
                <div>
                    <label for="media_minima" class="block text-sm font-semibold text-gray-700 mb-2">Média Mínima Aprovação</label>
                    <input type="number" id="media_minima" name="media_minima" step="0.01" value="<?php echo $configuracao['media_minima_aprovacao'] ?? 7.00; ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div>
                    <label for="frequencia_minima" class="block text-sm font-semibold text-gray-700 mb-2">Frequência Mínima (%)</label>
                    <input type="number" id="frequencia_minima" name="frequencia_minima" step="0.01" value="<?php echo $configuracao['frequencia_minima_aprovacao'] ?? 75.00; ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div>
                    <label for="numero_bimestres" class="block text-sm font-semibold text-gray-700 mb-2">Número de Bimestres</label>
                    <input type="number" id="numero_bimestres" name="numero_bimestres" value="<?php echo $configuracao['numero_bimestres'] ?? 4; ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="md:col-span-3">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>Salvar Configuração
                    </button>
                </div>
            </form>
        </div>

        <!-- Gerar Boletins -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">Gerar Boletins em Lote</h2>
            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <input type="hidden" name="action" value="gerar_boletins">
                
                <div>
                    <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                    <select id="turma_id" name="turma_id" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="">Selecione</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="bimestre" class="block text-sm font-semibold text-gray-700 mb-2">Bimestre</label>
                    <select id="bimestre" name="bimestre" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="1">1º Bimestre</option>
                        <option value="2">2º Bimestre</option>
                        <option value="3">3º Bimestre</option>
                        <option value="4">4º Bimestre</option>
                    </select>
                </div>
                
                <div>
                    <label for="ano_letivo" class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo</label>
                    <input type="number" id="ano_letivo" name="ano_letivo" value="<?php echo date('Y'); ?>"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-file-alt mr-2"></i>Gerar Boletins
                    </button>
                </div>
            </form>
        </div>

        <!-- Boletins Gerados -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Boletins Gerados</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Ano</th>
                            <th class="px-4 sm:px-6 py-4">Bimestre</th>
                            <th class="px-4 sm:px-6 py-4">Média</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletins as $boletim): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($boletim['aluno_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($boletim['turma_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $boletim['ano_letivo']; ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $boletim['bimestre']; ?>º</td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="text-2xl font-bold <?php echo $boletim['media_geral'] >= 7 ? 'text-green-600' : ($boletim['media_geral'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo number_format($boletim['media_geral'], 1); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($boletim['status']) {
                                            'aprovado' => 'bg-green-100 text-green-600',
                                            'reprovado' => 'bg-red-100 text-red-600',
                                            'recuperacao' => 'bg-yellow-100 text-yellow-600',
                                            'pendente' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($boletim['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($boletim['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($boletins)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-file-alt text-4xl mb-2"></i>
                    <p>Nenhum boletim gerado ainda.</p>
                </div>
            <?php endif; ?>
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
