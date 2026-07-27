<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Obter turmas do professor
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.* 
        FROM turmas t 
        JOIN grade_aulas ga ON t.id = ga.turma_id 
        WHERE ga.professor_id = ?
    ");
    $stmt->execute([$professor_id]);
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter grade de aulas do professor
$grade_aulas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT ga.*, t.nome as turma_nome, t.serie 
        FROM grade_aulas ga 
        JOIN turmas t ON ga.turma_id = t.id 
        WHERE ga.professor_id = ?
        ORDER BY FIELD(ga.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), ga.horario_inicio
    ");
    $stmt->execute([$professor_id]);
    $grade_aulas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter grade de aulas: " . $e->getMessage());
}

// Obter trabalhos e correções do professor
$trabalhos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT tc.*, t.nome as turma_nome 
        FROM trabalhos_correcoes tc 
        LEFT JOIN turmas t ON tc.turma_id = t.id 
        WHERE tc.professor_id = ? AND tc.ativo = TRUE
        ORDER BY tc.data_upload DESC
    ");
    $stmt->execute([$professor_id]);
    $trabalhos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos: " . $e->getMessage());
}

// Obter notas lançadas pelo professor
$notas_lancadas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM notas n 
        JOIN usuarios u ON n.aluno_id = u.id 
        LEFT JOIN turmas t ON n.turma_id = t.id 
        WHERE n.professor_id = ?
        ORDER BY n.data_lancamento DESC
        LIMIT 10
    ");
    $stmt->execute([$professor_id]);
    $notas_lancadas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notas: " . $e->getMessage());
}

// Obter avisos do professor
$avisos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT a.*, t.nome as turma_nome 
        FROM avisos a 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        WHERE a.professor_id = ? AND a.ativo = TRUE
        ORDER BY a.data_publicacao DESC
        LIMIT 5
    ");
    $stmt->execute([$professor_id]);
    $avisos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avisos: " . $e->getMessage());
}

// Processar formulários
$success = '';
$error = '';

// Criar aviso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_aviso') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    $tipo_aviso = sanitizeInput($_POST['tipo_aviso'] ?? 'geral');
    $turma_id = !empty($_POST['turma_id']) ? intval($_POST['turma_id']) : null;
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $data_expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;
    
    if (empty($titulo) || empty($conteudo)) {
        $error = 'Por favor, preencha o título e o conteúdo do aviso.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO avisos (professor_id, turma_id, serie, tipo_aviso, titulo, conteudo, data_publicacao, data_expiracao, ativo)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, TRUE)
            ");
            $stmt->execute([$professor_id, $turma_id, $serie, $tipo_aviso, $titulo, $conteudo, $data_expiracao]);
            $success = 'Aviso criado com sucesso!';
            
            // Recarregar avisos
            $stmt = $pdo->prepare("
                SELECT a.*, t.nome as turma_nome 
                FROM avisos a 
                LEFT JOIN turmas t ON a.turma_id = t.id 
                WHERE a.professor_id = ? AND a.ativo = TRUE
                ORDER BY a.data_publicacao DESC
                LIMIT 5
            ");
            $stmt->execute([$professor_id]);
            $avisos = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao criar aviso: " . $e->getMessage());
            $error = 'Erro ao criar aviso.';
        }
    }
}

// Adicionar aula à grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_aula') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $dia_semana = sanitizeInput($_POST['dia_semana'] ?? '');
    $horario_inicio = $_POST['horario_inicio'] ?? '';
    $horario_fim = $_POST['horario_fim'] ?? '';
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $sala = sanitizeInput($_POST['sala'] ?? '');
    
    if (empty($turma_id) || empty($dia_semana) || empty($horario_inicio) || empty($horario_fim) || empty($disciplina)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO grade_aulas (professor_id, turma_id, dia_semana, horario_inicio, horario_fim, disciplina, sala)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$professor_id, $turma_id, $dia_semana, $horario_inicio, $horario_fim, $disciplina, $sala]);
            $success = 'Aula adicionada à grade com sucesso!';
            
            // Recarregar grade
            $stmt = $pdo->prepare("
                SELECT ga.*, t.nome as turma_nome, t.serie 
                FROM grade_aulas ga 
                JOIN turmas t ON ga.turma_id = t.id 
                WHERE ga.professor_id = ?
                ORDER BY FIELD(ga.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), ga.horario_inicio
            ");
            $stmt->execute([$professor_id]);
            $grade_aulas = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao adicionar aula: " . $e->getMessage());
            $error = 'Erro ao adicionar aula.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor | Portal CEAA</title>
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
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .action-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="../dashboard.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">PAINEL DO</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">PROFESSOR</span>
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
                                <span class="text-white/70 text-xs">Professor</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Professor</p>
                            </div>
                            <div class="p-2">
                                <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
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

    <main class="px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
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
        <div class="mb-8 sm:mb-10">
            <div class="flex items-center gap-3 sm:gap-4 mb-2">
                <div class="w-2 h-10 sm:h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-display font-bold text-azul-principal">
                        Painel do Professor
                    </h1>
                    <p class="text-gray-600 mt-1 text-sm sm:text-base md:text-lg">Gerenciamento de aulas, notas e conteúdo</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Turmas</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo count($turmas); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Turmas ativas</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Aulas/Semana</p>
                        <p class="text-4xl font-bold text-verde-complementar"><?php echo count($grade_aulas); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-clock text-green-500"></i>
                        <span>Grade de horários</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-alt text-azul-escuro text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Trabalhos</p>
                        <p class="text-4xl font-bold text-amarelo-destaque"><?php echo count($trabalhos); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-upload text-green-500"></i>
                        <span>Uploads recentes</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-star text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Notas</p>
                        <p class="text-4xl font-bold text-purple-600"><?php echo count($notas_lancadas); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-chart-line text-purple-500"></i>
                        <span>Lançamentos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <button onclick="document.getElementById('modal-aviso').classList.remove('hidden')" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bullhorn text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Criar Aviso</h3>
                    <p class="text-sm text-gray-500">Enviar avisos para alunos</p>
                </div>
            </button>
            
            <button onclick="document.getElementById('modal-aula').classList.remove('hidden')" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-plus text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Adicionar Aula</h3>
                    <p class="text-sm text-gray-500">Cadastrar horário de aula</p>
                </div>
            </button>
            
            <a href="upload_trabalho.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-upload text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Upload Trabalho</h3>
                    <p class="text-sm text-gray-500">Enviar correções e materiais</p>
                </div>
            </a>
            
            <a href="lancar_notas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-edit text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Lançar Notas</h3>
                    <p class="text-sm text-gray-500">Registrar notas no boletim</p>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-8 sm:mb-10">
            <!-- Grade de Aulas -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-calendar-alt mr-2"></i>Grade de Aulas
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($grade_aulas) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($grade_aulas as $aula): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                        <?php echo ucfirst(substr($aula['dia_semana'], 0, 3)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aula['disciplina']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($aula['turma_nome']); ?> - <?php echo htmlspecialchars($aula['serie']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-azul-principal"><?php echo date('H:i', strtotime($aula['horario_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['horario_fim'])); ?></p>
                                        <p class="text-sm text-gray-500">Sala: <?php echo htmlspecialchars($aula['sala'] ?? '-'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-4"></i>
                            <p>Nenhuma aula cadastrada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Avisos Recentes -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-escuro">
                        <i class="fas fa-bullhorn mr-2"></i>Avisos Recentes
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($avisos) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($avisos as $aviso): ?>
                                <div class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-100">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                            <i class="fas fa-bullhorn text-amarelo-destaque"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aviso['titulo']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($aviso['conteudo'], 0, 80)) . '...'; ?></p>
                                            <p class="text-xs text-gray-400 mt-2"><?php echo date('d/m/Y', strtotime($aviso['data_publicacao'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-info-circle text-4xl mb-4"></i>
                            <p>Nenhum aviso criado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Trabalhos e Notas Recentes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Trabalhos Recentes -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-purple-600 to-purple-400 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-file-alt mr-2"></i>Trabalhos e Correções
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($trabalhos) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($trabalhos as $trabalho): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-file text-purple-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($trabalho['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo ucfirst($trabalho['tipo']); ?> | <?php echo htmlspecialchars($trabalho['turma_nome'] ?? 'Geral'); ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                        <?php echo ucfirst($trabalho['tipo']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-4"></i>
                            <p>Nenhum trabalho enviado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notas Recentes -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-orange-500 to-orange-400 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-star mr-2"></i>Notas Recentes
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($notas_lancadas) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($notas_lancadas as $nota): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-star text-orange-500 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($nota['aluno_nome']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($nota['disciplina']); ?> - <?php echo $nota['bimestre']; ?>º Bimestre</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-orange-500"><?php echo number_format($nota['nota'], 1); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-bar text-4xl mb-4"></i>
                            <p>Nenhuma nota lançada.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Criar Aviso -->
    <div id="modal-aviso" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('modal-aviso').classList.add('hidden')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Criar Novo Aviso</h2>
                    <button onclick="document.getElementById('modal-aviso').classList.add('hidden')" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_aviso">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Título do aviso">
                    </div>
                    
                    <div class="mb-4">
                        <label for="conteudo" class="block text-sm font-semibold text-gray-700 mb-2">Conteúdo *</label>
                        <textarea id="conteudo" name="conteudo" required rows="4"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Conteúdo do aviso"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_aviso" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Aviso</label>
                        <select id="tipo_aviso" name="tipo_aviso"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="geral">Geral</option>
                            <option value="turma">Turma Específica</option>
                            <option value="serie">Série Específica</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma (opcional)</label>
                        <select id="turma_id" name="turma_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?> - <?php echo htmlspecialchars($t['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série (opcional)</label>
                        <input type="text" id="serie" name="serie"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: 1º Ano, 2º Ano">
                    </div>
                    
                    <div class="mb-4">
                        <label for="data_expiracao" class="block text-sm font-semibold text-gray-700 mb-2">Data de Expiração (opcional)</label>
                        <input type="date" id="data_expiracao" name="data_expiracao"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Criar Aviso
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Aula -->
    <div id="modal-aula" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('modal-aula').classList.add('hidden')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Adicionar Aula à Grade</h2>
                    <button onclick="document.getElementById('modal-aula').classList.add('hidden')" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar_aula">
                    
                    <div class="mb-4">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                        <select id="turma_id" name="turma_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?> - <?php echo htmlspecialchars($t['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="dia_semana" class="block text-sm font-semibold text-gray-700 mb-2">Dia da Semana *</label>
                        <select id="dia_semana" name="dia_semana" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione o dia</option>
                            <option value="segunda">Segunda-feira</option>
                            <option value="terca">Terça-feira</option>
                            <option value="quarta">Quarta-feira</option>
                            <option value="quinta">Quinta-feira</option>
                            <option value="sexta">Sexta-feira</option>
                            <option value="sabado">Sábado</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="horario_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Horário Início *</label>
                            <input type="time" id="horario_inicio" name="horario_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="horario_fim" class="block text-sm font-semibold text-gray-700 mb-2">Horário Fim *</label>
                            <input type="time" id="horario_fim" name="horario_fim" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina *</label>
                        <input type="text" id="disciplina" name="disciplina" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: Matemática, Português">
                    </div>
                    
                    <div class="mb-4">
                        <label for="sala" class="block text-sm font-semibold text-gray-700 mb-2">Sala (opcional)</label>
                        <input type="text" id="sala" name="sala"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: Sala 101">
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Adicionar Aula
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

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
