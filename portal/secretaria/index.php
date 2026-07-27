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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 min-h-screen fixed left-0 top-0 z-30 hidden lg:block">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-school text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">Portal CEAA</h1>
                        <p class="text-xs text-gray-500">Secretaria</p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="#" onclick="loadContent('dashboard')" class="nav-link flex items-center gap-3 px-4 py-3 bg-primary-50 text-primary-700 rounded-lg font-medium" data-page="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" onclick="loadContent('matriculas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="matriculas">
                        <i class="fas fa-user-plus"></i>
                        <span>Matrículas</span>
                    </a>
                    <a href="#" onclick="loadContent('renovacoes')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="renovacoes">
                        <i class="fas fa-sync-alt"></i>
                        <span>Renovações</span>
                    </a>
                    <a href="#" onclick="loadContent('pre_matriculas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="pre_matriculas">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Pré-Matrículas</span>
                    </a>
                    <a href="#" onclick="loadContent('mensalidades')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="mensalidades">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Mensalidades</span>
                    </a>
                    <a href="#" onclick="loadContent('cobrancas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="cobrancas">
                        <i class="fas fa-bell"></i>
                        <span>Cobranças</span>
                    </a>
                    <a href="#" onclick="loadContent('declaracoes')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="declaracoes">
                        <i class="fas fa-file-alt"></i>
                        <span>Declarações</span>
                    </a>
                    <a href="#" onclick="loadContent('atestados')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="atestados">
                        <i class="fas fa-certificate"></i>
                        <span>Atestados</span>
                    </a>
                    <a href="#" onclick="loadContent('frequencia')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="frequencia">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Frequência</span>
                    </a>
                    <a href="#" onclick="loadContent('historico_escolar')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="historico_escolar">
                        <i class="fas fa-history"></i>
                        <span>Histórico</span>
                    </a>
                </nav>
            </div>
            
            <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-gray-200">
                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800" id="page-title">Painel da Secretaria</h1>
                            <p class="text-sm text-gray-500">Gestão de matrículas e financeiro</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-bell"></i>
                            </button>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                                </div>
                                <div class="hidden sm:block">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 20)); ?></p>
                                    <p class="text-xs text-gray-500">Secretaria</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Content Container -->
                <div id="content-container">
                    <!-- Loading State -->
                    <div id="loading" class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentPage = 'dashboard';

        function loadContent(page) {
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-primary-50', 'text-primary-700', 'font-medium');
                link.classList.add('text-gray-600');
            });
            
            const activeLink = document.querySelector(`.nav-link[data-page="${page}"]`);
            if (activeLink) {
                activeLink.classList.add('bg-primary-50', 'text-primary-700', 'font-medium');
                activeLink.classList.remove('text-gray-600');
            }

            // Show loading
            document.getElementById('content-container').innerHTML = `
                <div id="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
                </div>
            `;

            // Update page title
            const titles = {
                'dashboard': 'Painel da Secretaria',
                'matriculas': 'Gestão de Matrículas',
                'renovacoes': 'Renovações',
                'pre_matriculas': 'Pré-Matrículas',
                'mensalidades': 'Mensalidades',
                'cobrancas': 'Cobranças',
                'declaracoes': 'Declarações',
                'atestados': 'Atestados',
                'frequencia': 'Frequência',
                'historico_escolar': 'Histórico Escolar'
            };
            document.getElementById('page-title').textContent = titles[page] || 'Painel da Secretaria';

            // Load content via AJAX
            fetch(`pages/${page}.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Page not found');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('content-container').innerHTML = html;
                    currentPage = page;
                })
                .catch(error => {
                    document.getElementById('content-container').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-700">Erro ao carregar conteúdo: ${error.message}</p>
                        </div>
                    `;
                });
        }

        // Load dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadContent('dashboard');
        });
    </script>
</body>
</html>
