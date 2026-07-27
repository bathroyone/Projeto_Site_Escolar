<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'] ?? '';
$serie = $_SESSION['serie'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno | Portal CEAA</title>
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
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">Portal CEAA</h1>
                        <p class="text-xs text-gray-500">Aluno</p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="#" onclick="loadContent('dashboard')" class="nav-link flex items-center gap-3 px-4 py-3 bg-primary-50 text-primary-700 rounded-lg font-medium" data-page="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" onclick="loadContent('boletim')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="boletim">
                        <i class="fas fa-file-alt"></i>
                        <span>Boletim</span>
                    </a>
                    <a href="#" onclick="loadContent('horarios')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="horarios">
                        <i class="fas fa-calendar"></i>
                        <span>Horários</span>
                    </a>
                    <a href="#" onclick="loadContent('materiais')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="materiais">
                        <i class="fas fa-book"></i>
                        <span>Materiais</span>
                    </a>
                    <a href="#" onclick="loadContent('trabalhos')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="trabalhos">
                        <i class="fas fa-tasks"></i>
                        <span>Trabalhos</span>
                    </a>
                    <a href="#" onclick="loadContent('frequencia')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="frequencia">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Frequência</span>
                    </a>
                    <a href="#" onclick="loadContent('mensalidades')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="mensalidades">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Mensalidades</span>
                    </a>
                    <a href="#" onclick="loadContent('notificacoes')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="notificacoes">
                        <i class="fas fa-bell"></i>
                        <span>Notificações</span>
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
                            <h1 class="text-2xl font-bold text-gray-800" id="page-title">Painel do Aluno</h1>
                            <p class="text-sm text-gray-500">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?></p>
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
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($turma); ?> - <?php echo htmlspecialchars($serie); ?></p>
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
                'dashboard': 'Painel do Aluno',
                'boletim': 'Boletim Escolar',
                'horarios': 'Horário de Aulas',
                'materiais': 'Materiais Didáticos',
                'trabalhos': 'Trabalhos e Provas',
                'frequencia': 'Controle de Frequência',
                'mensalidades': 'Mensalidades',
                'notificacoes': 'Notificações'
            };
            document.getElementById('page-title').textContent = titles[page] || 'Painel do Aluno';

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
