<?php
require_once '../config.php';

requireLogin();

if (!isSecretaria()) {
    header('Location: ../dashboard.php');
    exit();
}

$secretaria_id = $_SESSION['usuario_id'];

// Obter estatísticas de matrículas
$estatisticas_matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_alunos,
            COUNT(DISTINCT CASE WHEN u.ativo = 1 THEN u.id END) as alunos_ativos,
            COUNT(DISTINCT CASE WHEN u.ativo = 0 THEN u.id END) as alunos_inativos,
            COUNT(DISTINCT t.id) as total_turmas
        FROM usuarios u
        LEFT JOIN matriculas m ON u.id = m.aluno_id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE u.tipo_usuario = 'aluno'
    ");
    $estatisticas_matriculas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter matrículas recentes
$matriculas_recentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome, t.serie
        FROM matriculas m
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        ORDER BY m.data_matricula DESC
        LIMIT 10
    ");
    $matriculas_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas recentes: " . $e->getMessage());
}

// Obter pré-matrículas pendentes
$pre_matriculas_pendentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT * FROM pre_matriculas
        WHERE status = 'pendente'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $pre_matriculas_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter pré-matrículas: " . $e->getMessage());
}

// Obter mensalidades em atraso
$mensalidades_atraso = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        LEFT JOIN turmas t ON mat.turma_id = t.id
        WHERE m.status = 'pendente' AND m.data_vencimento < CURDATE()
        ORDER BY m.data_vencimento ASC
        LIMIT 10
    ");
    $mensalidades_atraso = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades em atraso: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Secretaria | Portal CEAA</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">PAINEL DA</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">SECRETARIA</span>
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

    <main class="px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <!-- Bem-vindo -->
        <div class="mb-8 sm:mb-10">
            <div class="flex items-center gap-3 sm:gap-4 mb-2">
                <div class="w-2 h-10 sm:h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-display font-bold text-azul-principal">
                        Painel da Secretaria
                    </h1>
                    <p class="text-gray-600 mt-1 text-sm sm:text-base md:text-lg">Gestão de matrículas, financeiro e documentação</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Alunos</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo $estatisticas_matriculas['total_alunos'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo $estatisticas_matriculas['alunos_ativos'] ?? 0; ?> ativos</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Turmas</p>
                        <p class="text-4xl font-bold text-verde-complementar"><?php echo $estatisticas_matriculas['total_turmas'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-school text-green-500"></i>
                        <span>Classes ativas</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-signature text-azul-escuro text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Pré-Matrículas</p>
                        <p class="text-4xl font-bold text-amarelo-destaque"><?php echo count($pre_matriculas_pendentes); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-clock text-orange-500"></i>
                        <span>Pendentes</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Mensalidades</p>
                        <p class="text-4xl font-bold text-red-500"><?php echo count($mensalidades_atraso); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-calendar-times text-red-500"></i>
                        <span>Em atraso</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <a href="matriculas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-plus text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Matrículas</h3>
                    <p class="text-sm text-gray-500">Gestão de matrículas</p>
                </div>
            </a>
            
            <a href="rematriculas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-sync-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Rematrículas</h3>
                    <p class="text-sm text-gray-500">Renovação anual</p>
                </div>
            </a>
            
            <a href="vagas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-door-open text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Vagas</h3>
                    <p class="text-sm text-gray-500">Controle de vagas</p>
                </div>
            </a>
            
            <a href="contratos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-contract text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Contratos</h3>
                    <p class="text-sm text-gray-500">Gestão de contratos</p>
                </div>
            </a>
            
            <a href="historico_escolar.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Histórico</h3>
                    <p class="text-sm text-gray-500">Histórico escolar</p>
                </div>
            </a>
            
            <a href="declaracoes.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Declarações</h3>
                    <p class="text-sm text-gray-500">Emissão de documentos</p>
                </div>
            </a>
            
            <a href="fichas_cadastrais.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-id-card text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Fichas</h3>
                    <p class="text-sm text-gray-500">Fichas cadastrais</p>
                </div>
            </a>
            
            <a href="documentacao.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-cyan-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-folder-open text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Documentação</h3>
                    <p class="text-sm text-gray-500">Controle de documentos</p>
                </div>
            </a>
            
            <a href="mensalidades.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Mensalidades</h3>
                    <p class="text-sm text-gray-500">Controle financeiro</p>
                </div>
            </a>
            
            <a href="cobrancas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Cobranças</h3>
                    <p class="text-sm text-gray-500">Notificações</p>
                </div>
            </a>
            
            <a href="relatorios_financeiros.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-amber-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Relatórios</h3>
                    <p class="text-sm text-gray-500">Relatórios financeiros</p>
                </div>
            </a>
            
            <a href="frequencia.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-lime-500 to-lime-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-clipboard-check text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Frequência</h3>
                    <p class="text-sm text-gray-500">Controle de frequência</p>
                </div>
            </a>
            
            <a href="atestados.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-violet-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-certificate text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Atestados</h3>
                    <p class="text-sm text-gray-500">Certificados e atestados</p>
                </div>
            </a>
            
            <a href="irmaos_descontos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Irmãos</h3>
                    <p class="text-sm text-gray-500">Controle de irmãos e descontos</p>
                </div>
            </a>
            
            <a href="transferencias.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-exchange-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Transferências</h3>
                    <p class="text-sm text-gray-500">Entrada e saída</p>
                </div>
            </a>
            
            <a href="renovacoes.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-cyan-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-sync-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Renovações</h3>
                    <p class="text-sm text-gray-500">Controle de renovações</p>
                </div>
            </a>
            
            <a href="autorizacoes.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-signature text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Autorizações</h3>
                    <p class="text-sm text-gray-500">Termos e autorizações</p>
                </div>
            </a>
            
            <a href="calendario_pagamentos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-amber-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Calendário</h3>
                    <p class="text-sm text-gray-500">Calendário de pagamentos</p>
                </div>
            </a>
            
            <a href="bolsas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-award text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Bolsas</h3>
                    <p class="text-sm text-gray-500">Bolsas e descontos</p>
                </div>
            </a>
            
            <a href="comunicados.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-envelope text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Comunicados</h3>
                    <p class="text-sm text-gray-500">Comunicados aos responsáveis</p>
                </div>
            </a>
            
            <a href="agenda_atendimentos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Agenda</h3>
                    <p class="text-sm text-gray-500">Agenda de atendimentos</p>
                </div>
            </a>
            
            <a href="estatisticas_matricula.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Estatísticas</h3>
                    <p class="text-sm text-gray-500">Estatísticas de matrícula</p>
                </div>
            </a>
            
            <a href="estoque_formularios.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-slate-500 to-slate-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Estoque</h3>
                    <p class="text-sm text-gray-500">Estoque de formulários</p>
                </div>
            </a>
            
            <a href="controle_visitas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-lime-500 to-lime-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Visitas</h3>
                    <p class="text-sm text-gray-500">Controle de visitas</p>
                </div>
            </a>
            
            <a href="integracao_bancos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-sky-500 to-sky-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-university text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Bancos</h3>
                    <p class="text-sm text-gray-500">Integração com bancos</p>
                </div>
            </a>
        </div>

        <!-- Listas Recentes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-8 sm:mb-10">
            <!-- Matrículas Recentes -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-user-graduate mr-2"></i>Matrículas Recentes
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($matriculas_recentes) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($matriculas_recentes as $matricula): ?>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center text-white font-bold shadow-md">
                                        <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Sem turma'); ?> - <?php echo htmlspecialchars($matricula['serie'] ?? ''); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-azul-principal"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($matricula['status']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-user-graduate text-4xl mb-4"></i>
                            <p>Nenhuma matrícula recente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mensalidades em Atraso -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-400 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Mensalidades em Atraso
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (count($mensalidades_atraso) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($mensalidades_atraso as $mensalidade): ?>
                                <div class="flex items-center gap-4 p-4 bg-red-50 rounded-xl hover:bg-red-100 transition-colors">
                                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-dollar-sign text-red-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($mensalidade['turma_nome'] ?? 'Sem turma'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-red-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                            <p>Nenhuma mensalidade em atraso.</p>
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

        // Fechar menu ao clicar fora
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
