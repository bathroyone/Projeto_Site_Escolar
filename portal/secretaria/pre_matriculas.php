<?php
require_once '../config.php';

requireLogin();

if (!isSecretaria() && !isAdmin()) {
    header('Location: ../dashboard.php');
    exit();
}

$success = '';
$error = '';

// Obter pré-matrículas
$pre_matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT pm.*, 
               (SELECT COUNT(*) FROM documentos_pre_matricula WHERE pre_matricula_id = pm.id) as qtd_documentos
        FROM pre_matriculas pm 
        ORDER BY pm.data_solicitacao DESC
    ");
    $pre_matriculas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter pré-matrículas: " . $e->getMessage());
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');
    $pre_matricula_id = intval($_POST['pre_matricula_id'] ?? 0);
    
    if ($action === 'aprovar') {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // Atualizar status
            $stmt = $pdo->prepare("
                UPDATE pre_matriculas 
                SET status = 'aprovada', data_analise = CURDATE(), usuario_aprovacao_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $pre_matricula_id]);
            
            // Criar usuário a partir da pré-matrícula
            $stmt = $pdo->prepare("SELECT * FROM pre_matriculas WHERE id = ?");
            $stmt->execute([$pre_matricula_id]);
            $pre_matricula = $stmt->fetch();
            
            if ($pre_matricula) {
                // Gerar matrícula
                $matricula = 'ALU' . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $senha = 'aluno123';
                $senha_hash = hashPassword($senha);
                
                // Obter turma_id
                $turma_id = null;
                if ($pre_matricula['turma_desejada'] && $pre_matricula['serie_desejada']) {
                    $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1");
                    $stmt->execute([$pre_matricula['turma_desejada'], $pre_matricula['serie_desejada']]);
                    $turma = $stmt->fetch();
                    if ($turma) {
                        $turma_id = $turma['id'];
                    }
                }
                
                // Inserir usuário
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, turma, serie, matricula, turma_id, cpf, data_nascimento, ativo)
                    VALUES (?, ?, ?, 'aluno', ?, ?, ?, ?, ?, ?, TRUE)
                ");
                $stmt->execute([
                    $pre_matricula['nome_aluno'],
                    $pre_matricula['email_responsavel'],
                    $senha_hash,
                    $pre_matricula['turma_desejada'] ?? '',
                    $pre_matricula['serie_desejada'] ?? '',
                    $matricula,
                    $turma_id,
                    $pre_matricula['cpf'],
                    $pre_matricula['data_nascimento']
                ]);
                
                // Atualizar status para concluída
                $stmt = $pdo->prepare("UPDATE pre_matriculas SET status = 'concluida' WHERE id = ?");
                $stmt->execute([$pre_matricula_id]);
                
                $success = 'Pré-matrícula aprovada e matrícula realizada com sucesso! Matrícula: ' . $matricula . ' | Senha: ' . $senha;
            }
            
            $pdo->commit();
            
            // Recarregar pré-matrículas
            $stmt = $pdo->query("
                SELECT pm.*, 
                       (SELECT COUNT(*) FROM documentos_pre_matricula WHERE pre_matricula_id = pm.id) as qtd_documentos
                FROM pre_matriculas pm 
                ORDER BY pm.data_solicitacao DESC
            ");
            $pre_matriculas = $stmt->fetchAll();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro ao aprovar pré-matrícula: " . $e->getMessage());
            $error = 'Erro ao aprovar pré-matrícula.';
        }
    } elseif ($action === 'rejeitar') {
        $motivo = sanitizeInput($_POST['motivo'] ?? '');
        
        if (empty($motivo)) {
            $error = 'Por favor, informe o motivo da rejeição.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    UPDATE pre_matriculas 
                    SET status = 'rejeitada', data_analise = CURDATE(), usuario_aprovacao_id = ?, motivo_rejeicao = ?
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['usuario_id'], $motivo, $pre_matricula_id]);
                $success = 'Pré-matrícula rejeitada com sucesso!';
                
                // Recarregar pré-matrículas
                $stmt = $pdo->query("
                    SELECT pm.*, 
                           (SELECT COUNT(*) FROM documentos_pre_matricula WHERE pre_matricula_id = pm.id) as qtd_documentos
                    FROM pre_matriculas pm 
                    ORDER BY pm.data_solicitacao DESC
                ");
                $pre_matriculas = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Erro ao rejeitar pré-matrícula: " . $e->getMessage());
                $error = 'Erro ao rejeitar pré-matrícula.';
            }
        }
    } elseif ($action === 'em_analise') {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE pre_matriculas 
                SET status = 'em_analise', data_analise = CURDATE(), usuario_aprovacao_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['usuario_id'], $pre_matricula_id]);
            $success = 'Pré-matrícula movida para análise!';
            
            // Recarregar pré-matrículas
            $stmt = $pdo->query("
                SELECT pm.*, 
                       (SELECT COUNT(*) FROM documentos_pre_matricula WHERE pre_matricula_id = pm.id) as qtd_documentos
                FROM pre_matriculas pm 
                ORDER BY pm.data_solicitacao DESC
            ");
            $pre_matriculas = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao mover para análise: " . $e->getMessage());
            $error = 'Erro ao mover para análise.';
        }
    }
}

// Obter documentos de uma pré-matrícula específica
$documentos = [];
if (isset($_GET['ver_documentos'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM documentos_pre_matricula 
            WHERE pre_matricula_id = ?
            ORDER BY tipo_documento
        ");
        $stmt->execute([intval($_GET['ver_documentos'])]);
        $documentos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter documentos: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pré-Matrículas | Portal Admin</title>
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-sm tracking-wide">VOLTAR PARA</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">PAINEL ADMIN</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-11 h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden md:block text-left">
                                <span class="text-white text-sm font-medium block"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                                <span class="text-white/70 text-xs">Administrador</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-3 w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-white/80">Administrador</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Admin</span>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
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

        <!-- Bem-vindo -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-2 h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-4xl font-display font-bold text-azul-principal">
                        Gerenciar Pré-Matrículas
                    </h1>
                    <p class="text-gray-600 mt-1 text-lg">Aprovar, rejeitar e analisar solicitações de matrícula</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="glass-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-gray-500 to-gray-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Total</p>
                        <p class="text-4xl font-bold text-gray-600"><?php echo count($pre_matriculas); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Pendentes</p>
                        <p class="text-4xl font-bold text-yellow-500"><?php echo count(array_filter($pre_matriculas, fn($p) => $p['status'] === 'pendente')); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-search text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Em Análise</p>
                        <p class="text-4xl font-bold text-blue-500"><?php echo count(array_filter($pre_matriculas, fn($p) => $p['status'] === 'em_analise')); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Aprovadas</p>
                        <p class="text-4xl font-bold text-green-500"><?php echo count(array_filter($pre_matriculas, fn($p) => $p['status'] === 'concluida')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Pré-Matrículas -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h2 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Solicitações de Pré-Matrícula
                </h2>
            </div>
            <div class="p-6">
                <?php if (count($pre_matriculas) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($pre_matriculas as $pm): ?>
                            <div class="p-6 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-purple-400 rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                            <?php echo strtoupper(substr($pm['nome_aluno'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($pm['nome_aluno']); ?></h3>
                                            <p class="text-sm text-gray-500">
                                                Responsável: <?php echo htmlspecialchars($pm['nome_responsavel']); ?> | 
                                                Email: <?php echo htmlspecialchars($pm['email_responsavel']); ?> |
                                                Tel: <?php echo htmlspecialchars($pm['telefone_responsavel']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500 mt-1">
                                                Série: <?php echo htmlspecialchars($pm['serie_desejada'] ?? '-'); ?> | 
                                                Turma: <?php echo htmlspecialchars($pm['turma_desejada'] ?? '-'); ?> |
                                                Documentos: <?php echo $pm['qtd_documentos']; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-4 py-2 rounded-full text-xs font-bold
                                            <?php 
                                            echo match($pm['status']) {
                                                'pendente' => 'bg-yellow-100 text-yellow-700',
                                                'em_analise' => 'bg-blue-100 text-blue-700',
                                                'aprovada' => 'bg-green-100 text-green-700',
                                                'rejeitada' => 'bg-red-100 text-red-700',
                                                'concluida' => 'bg-purple-100 text-purple-700',
                                                default => 'bg-gray-100 text-gray-700'
                                            };
                                            ?>">
                                            <?php echo ucfirst($pm['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <a href="?ver_documentos=<?php echo $pm['id']; ?>" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-sm font-semibold">
                                        <i class="fas fa-file-alt mr-1"></i>Ver Documentos
                                    </a>
                                    
                                    <?php if ($pm['status'] === 'pendente'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="em_analise">
                                            <input type="hidden" name="pre_matricula_id" value="<?php echo $pm['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-semibold">
                                                <i class="fas fa-search mr-1"></i>Mover para Análise
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($pm['status'] === 'em_analise'): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="aprovar">
                                            <input type="hidden" name="pre_matricula_id" value="<?php echo $pm['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors text-sm font-semibold">
                                                <i class="fas fa-check mr-1"></i>Aprovar
                                            </button>
                                        </form>
                                        
                                        <button onclick="document.getElementById('rejeitar-<?php echo $pm['id']; ?>').classList.remove('hidden')" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-semibold">
                                            <i class="fas fa-times mr-1"></i>Rejeitar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($pm['status'] === 'rejeitada' && $pm['motivo_rejeicao']): ?>
                                        <p class="text-sm text-red-600"><strong>Motivo:</strong> <?php echo htmlspecialchars($pm['motivo_rejeicao']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Formulário de Rejeição -->
                                <div id="rejeitar-<?php echo $pm['id']; ?>" class="hidden mt-4 p-4 bg-red-50 rounded-xl">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="rejeitar">
                                        <input type="hidden" name="pre_matricula_id" value="<?php echo $pm['id']; ?>">
                                        <div class="mb-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo da Rejeição *</label>
                                            <textarea name="motivo" required rows="3"
                                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                                                placeholder="Informe o motivo da rejeição"></textarea>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-semibold">
                                                <i class="fas fa-times mr-1"></i>Confirmar Rejeição
                                            </button>
                                            <button type="button" onclick="document.getElementById('rejeitar-<?php echo $pm['id']; ?>').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm font-semibold">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Nenhuma pré-matrícula recebida.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal de Documentos -->
        <?php if (isset($_GET['ver_documentos']) && count($documentos) > 0): ?>
            <div id="modal-documentos" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="window.location.href='pre_matriculas.php'"></div>
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-600 to-purple-400">
                            <h2 class="text-xl font-display font-bold text-white">
                                <i class="fas fa-file-alt mr-2"></i>Documentos Enviados
                            </h2>
                            <a href="pre_matriculas.php" class="p-2 rounded-lg hover:bg-white/20 transition-all">
                                <i class="fas fa-times text-white text-xl"></i>
                            </a>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <?php foreach ($documentos as $doc): ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-file text-purple-600 text-xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($doc['nome_arquivo']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $doc['tipo_documento'])); ?></p>
                                        </div>
                                        <a href="uploads/pre_matriculas/<?php echo $_GET['ver_documentos']; ?>/<?php echo htmlspecialchars($doc['caminho_arquivo']); ?>" target="_blank" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-semibold">
                                            <i class="fas fa-download mr-1"></i>Baixar
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
