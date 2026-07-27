<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

// Obter turma_id do aluno
$turma_id = null;
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1");
    $stmt->execute([$turma, $serie]);
    $turma_data = $stmt->fetch();
    if ($turma_data) {
        $turma_id = $turma_data['id'];
    }
} catch (PDOException $e) {
    error_log("Erro ao obter turma_id: " . $e->getMessage());
}

// Obter notas do aluno
$notas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome_completo as professor_nome 
        FROM notas n 
        JOIN usuarios u ON n.professor_id = u.id 
        WHERE n.aluno_id = ?
        ORDER BY n.bimestre, n.disciplina
    ");
    $stmt->execute([$aluno_id]);
    $notas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notas: " . $e->getMessage());
}

// Obter trabalhos e correções disponíveis
$trabalhos = [];
try {
    $pdo = getDBConnection();
    if ($turma_id) {
        $stmt = $pdo->prepare("
            SELECT tc.*, u.nome_completo as professor_nome 
            FROM trabalhos_correcoes tc 
            JOIN usuarios u ON tc.professor_id = u.id 
            WHERE (tc.turma_id = ? OR tc.turma_id IS NULL)
            AND tc.ativo = TRUE
            ORDER BY tc.data_upload DESC
        ");
        $stmt->execute([$turma_id]);
    } else {
        $stmt = $pdo->query("
            SELECT tc.*, u.nome_completo as professor_nome 
            FROM trabalhos_correcoes tc 
            JOIN usuarios u ON tc.professor_id = u.id 
            WHERE tc.turma_id IS NULL
            AND tc.ativo = TRUE
            ORDER BY tc.data_upload DESC
        ");
    }
    $trabalhos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos: " . $e->getMessage());
}

// Obter grade de aulas do aluno
$grade_aulas = [];
try {
    $pdo = getDBConnection();
    if ($turma_id) {
        $stmt = $pdo->prepare("
            SELECT ga.*, u.nome_completo as professor_nome 
            FROM grade_aulas ga 
            JOIN usuarios u ON ga.professor_id = u.id 
            WHERE ga.turma_id = ?
            ORDER BY FIELD(ga.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), ga.horario_inicio
        ");
        $stmt->execute([$turma_id]);
        $grade_aulas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Erro ao obter grade de aulas: " . $e->getMessage());
}

// Obter avisos
$avisos = [];
try {
    $pdo = getDBConnection();
    if ($turma_id) {
        $stmt = $pdo->prepare("
            SELECT a.*, u.nome_completo as professor_nome 
            FROM avisos a 
            JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.ativo = TRUE 
            AND (a.tipo_aviso = 'geral' 
                OR (a.tipo_aviso = 'turma' AND a.turma_id = ?)
                OR (a.tipo_aviso = 'serie' AND a.serie = ?))
            AND (a.data_expiracao IS NULL OR a.data_expiracao > CURDATE())
            ORDER BY a.data_publicacao DESC
            LIMIT 5
        ");
        $stmt->execute([$turma_id, $serie]);
    } else {
        $stmt = $pdo->prepare("
            SELECT a.*, u.nome_completo as professor_nome 
            FROM avisos a 
            JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.ativo = TRUE 
            AND (a.tipo_aviso = 'geral' OR (a.tipo_aviso = 'serie' AND a.serie = ?))
            AND (a.data_expiracao IS NULL OR a.data_expiracao > CURDATE())
            ORDER BY a.data_publicacao DESC
            LIMIT 5
        ");
        $stmt->execute([$serie]);
    }
    $avisos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avisos: " . $e->getMessage());
}

// Obter eventos
$eventos = [];
try {
    $pdo = getDBConnection();
    if ($turma_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM eventos_calendario 
            WHERE (turma_id = ? OR serie = ? OR turma_id IS NULL)
            AND data_inicio >= CURDATE()
            ORDER BY data_inicio ASC
            LIMIT 5
        ");
        $stmt->execute([$turma_id, $serie]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM eventos_calendario 
            WHERE (serie = ? OR turma_id IS NULL)
            AND data_inicio >= CURDATE()
            ORDER BY data_inicio ASC
            LIMIT 5
        ");
        $stmt->execute([$serie]);
    }
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}

// Calcular média geral
$media_geral = 0;
if (count($notas) > 0) {
    $soma_notas = array_sum(array_column($notas, 'nota'));
    $media_geral = $soma_notas / count($notas);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno | Portal CEAA</title>
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
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">ALUNO</span>
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
                                <span class="text-white/70 text-xs"><?php echo htmlspecialchars(substr($turma, 0, 10)); ?> - <?php echo htmlspecialchars($serie); ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Aluno</p>
                                <p class="text-xs text-white/60"><?php echo htmlspecialchars($turma); ?> - <?php echo htmlspecialchars($serie); ?></p>
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
        <!-- Bem-vindo -->
        <div class="mb-8 sm:mb-10">
            <div class="flex items-center gap-3 sm:gap-4 mb-2">
                <div class="w-2 h-10 sm:h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-display font-bold text-azul-principal">
                        Painel do Aluno
                    </h1>
                    <p class="text-gray-600 mt-1 text-sm sm:text-base md:text-lg">Acompanhe suas notas, trabalhos e horários</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-star text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Média Geral</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo number_format($media_geral, 1); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-chart-line text-green-500"></i>
                        <span>Desempenho acadêmico</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Notas Lançadas</p>
                        <p class="text-4xl font-bold text-verde-complementar"><?php echo count($notas); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Total de avaliações</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-download text-azul-escuro text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Trabalhos</p>
                        <p class="text-4xl font-bold text-amarelo-destaque"><?php echo count($trabalhos); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-folder text-green-500"></i>
                        <span>Materiais disponíveis</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Aulas/Semana</p>
                        <p class="text-4xl font-bold text-purple-600"><?php echo count($grade_aulas); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-clock text-purple-500"></i>
                        <span>Grade de horários</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <a href="boletim.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Boletim</h3>
                    <p class="text-sm text-gray-500">Ver suas notas</p>
                </div>
            </a>
            
            <a href="historico_notas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Histórico</h3>
                    <p class="text-sm text-gray-500">Evolução de notas</p>
                </div>
            </a>
            
            <a href="diario_classe.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-600 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-book text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Diário de Classe</h3>
                    <p class="text-sm text-gray-500">Aulas digitais</p>
                </div>
            </a>
            
            <a href="entrega_trabalhos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-upload text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Entregar Trabalhos</h3>
                    <p class="text-sm text-gray-500">Upload de arquivos</p>
                </div>
            </a>
        </div>

        <!-- Mais Ações -->
        <div class="mb-8 sm:mb-10">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">Todos os Módulos</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="provas_resultados.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-signature text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Provas</h3>
                </a>
                
                <a href="calendario.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-indigo-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Calendário</h3>
                </a>
                
                <a href="notificacoes.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-bell text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Notificações</h3>
                </a>
                
                <a href="materiais.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-folder text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Materiais</h3>
                </a>
                
                <a href="forum.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-comments text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Fórum</h3>
                </a>
                
                <a href="frequencia.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-check text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Frequência</h3>
                </a>
                
                <a href="perfil.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-violet-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Perfil</h3>
                </a>
                
                <a href="solicitacoes_documentos.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-rose-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-alt text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Documentos</h3>
                </a>
                
                <a href="horarios.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-clock text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Horários</h3>
                </a>
                
                <a href="chat_professores.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-sky-500 to-sky-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-comment-dots text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Chat</h3>
                </a>
                
                <a href="biblioteca.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-lime-500 to-lime-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-book-open text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Biblioteca</h3>
                </a>
                
                <a href="mensalidades.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 to-fuchsia-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-dollar-sign text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Mensalidades</h3>
                </a>
                
                <a href="feedback.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-star text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Feedback</h3>
                </a>
                
                <a href="eventos.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Eventos</h3>
                </a>
                
                <a href="avisos.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-slate-500 to-slate-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-bullhorn text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Avisos</h3>
                </a>
                
                <a href="trabalhos_grupo.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-zinc-500 to-zinc-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Grupos</h3>
                </a>
                
                <a href="objetivos.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-stone-500 to-stone-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-bullseye text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Objetivos</h3>
                </a>
                
                <a href="progresso_anual.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-neutral-500 to-neutral-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Progresso</h3>
                </a>
                
                <a href="recursos_aprendizagem.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-teal-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-lightbulb text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Recursos</h3>
                </a>
                
                <a href="suporte.php" class="glass-card rounded-2xl p-4 transition-all duration-300 hover:shadow-xl group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-gray-500 to-gray-400 rounded-xl flex items-center justify-center shadow-lg mb-3 mx-auto group-hover:scale-110 transition-transform">
                        <i class="fas fa-headset text-white text-lg"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">Suporte</h3>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-8 sm:mb-10">
            <!-- Boletim de Notas -->
            <div id="boletim" class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-chart-bar mr-2"></i>Boletim de Notas
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($notas) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($notas as $nota): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                        <?php echo $nota['bimestre']; ?>º
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($nota['disciplina']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo ucfirst($nota['tipo_avaliacao']); ?> | <?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold <?php echo $nota['nota'] >= 7 ? 'text-green-600' : ($nota['nota'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                            <?php echo number_format($nota['nota'], 1); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">Por: <?php echo htmlspecialchars($nota['professor_nome']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-bar text-4xl mb-4"></i>
                            <p>Nenhuma nota lançada ainda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Trabalhos e Correções -->
            <div id="trabalhos" class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-verde-complementar to-verde-claro flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-folder-open mr-2"></i>Trabalhos e Correções
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($trabalhos) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($trabalhos as $trabalho): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-file text-verde-complementar text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($trabalho['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo ucfirst($trabalho['tipo']); ?> | <?php echo htmlspecialchars($trabalho['disciplina'] ?? ''); ?></p>
                                        <p class="text-xs text-gray-400">Por: <?php echo htmlspecialchars($trabalho['professor_nome']); ?> | <?php echo date('d/m/Y', strtotime($trabalho['data_upload'])); ?></p>
                                    </div>
                                    <?php if ($trabalho['arquivo_path']): ?>
                                        <a href="../uploads/professor_<?php echo $trabalho['professor_id']; ?>/<?php echo htmlspecialchars($trabalho['arquivo_path']); ?>" target="_blank" class="px-4 py-2 bg-verde-complementar text-white rounded-lg hover:bg-verde-claro transition-colors text-sm font-semibold">
                                            <i class="fas fa-download mr-1"></i>Baixar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-4"></i>
                            <p>Nenhum trabalho disponível.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Grade de Aulas -->
            <div id="grade" class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-purple-600 to-purple-400 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-calendar-alt mr-2"></i>Grade de Aulas
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($grade_aulas) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($grade_aulas as $aula): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-purple-400 rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                        <?php echo ucfirst(substr($aula['dia_semana'], 0, 3)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aula['disciplina']); ?></h3>
                                        <p class="text-sm text-gray-500">Prof: <?php echo htmlspecialchars($aula['professor_nome']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-purple-600"><?php echo date('H:i', strtotime($aula['horario_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['horario_fim'])); ?></p>
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

            <!-- Avisos e Eventos -->
            <div id="avisos" class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-orange-500 to-orange-400 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-bell mr-2"></i>Avisos e Eventos
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($avisos) > 0 || count($eventos) > 0): ?>
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
                                            <p class="text-xs text-gray-400 mt-2">Por: <?php echo htmlspecialchars($aviso['professor_nome']); ?> | <?php echo date('d/m/Y', strtotime($aviso['data_publicacao'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php foreach ($eventos as $evento): ?>
                                <div class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                            <i class="fas fa-calendar text-azul-principal"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo date('d/m/Y H:i', strtotime($evento['data_inicio'])); ?></p>
                                            <span class="inline-block mt-2 px-2 py-1 bg-<?php echo $evento['tipo_evento'] === 'prova' ? 'red' : ($evento['tipo_evento'] === 'trabalho' ? 'orange' : 'blue'); ?>-100 text-<?php echo $evento['tipo_evento'] === 'prova' ? 'red' : ($evento['tipo_evento'] === 'trabalho' ? 'orange' : 'blue'); ?>-600 rounded-full text-xs font-semibold">
                                                <?php echo ucfirst($evento['tipo_evento']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-info-circle text-4xl mb-4"></i>
                            <p>Nenhum aviso ou evento.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
