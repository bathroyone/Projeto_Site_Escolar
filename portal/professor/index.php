<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor | Portal CEAA</title>
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
        <aside class="w-64 bg-white border-r border-gray-200 h-screen fixed left-0 top-0 z-30 hidden lg:flex flex-col">
            <div class="p-6 flex-shrink-0">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chalkboard-teacher text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">Portal CEAA</h1>
                        <p class="text-xs text-gray-500">Professor</p>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto px-6 space-y-1 pb-24">
                    <a href="#" onclick="loadContent('dashboard')" class="nav-link flex items-center gap-3 px-4 py-3 bg-primary-50 text-primary-700 rounded-lg font-medium" data-page="dashboard">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" onclick="loadContent('lancar_notas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="lancar_notas">
                        <i class="fas fa-star"></i>
                        <span>Lançar Notas</span>
                    </a>
                    <a href="#" onclick="loadContent('chamada')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="chamada">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Chamada</span>
                    </a>
                    <a href="#" onclick="loadContent('diario')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="diario">
                        <i class="fas fa-book"></i>
                        <span>Diário de Classe</span>
                    </a>
                    <a href="#" onclick="loadContent('planejamento')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="planejamento">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Planejamento</span>
                    </a>
                    <a href="#" onclick="loadContent('materiais')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="materiais">
                        <i class="fas fa-folder-open"></i>
                        <span>Materiais Didáticos</span>
                    </a>
                    <a href="#" onclick="loadContent('provas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="provas">
                        <i class="fas fa-file-alt"></i>
                        <span>Provas Online</span>
                    </a>
                    <a href="#" onclick="loadContent('trabalhos_grupo')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="trabalhos_grupo">
                        <i class="fas fa-users"></i>
                        <span>Trabalhos em Grupo</span>
                    </a>
                    <a href="#" onclick="loadContent('forum')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="forum">
                        <i class="fas fa-comments"></i>
                        <span>Fórum de Discussão</span>
                    </a>
                    <a href="#" onclick="loadContent('acompanhamento')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="acompanhamento">
                        <i class="fas fa-user-graduate"></i>
                        <span>Acompanhamento Individual</span>
                    </a>
                    <a href="#" onclick="loadContent('relatorios')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="relatorios">
                        <i class="fas fa-chart-bar"></i>
                        <span>Relatórios de Desempenho</span>
                    </a>
                    <a href="#" onclick="loadContent('avisos')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="avisos">
                        <i class="fas fa-bell"></i>
                        <span>Avisos aos Responsáveis</span>
                    </a>
                    <a href="#" onclick="loadContent('projetos')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="projetos">
                        <i class="fas fa-project-diagram"></i>
                        <span>Gestão de Projetos</span>
                    </a>
                    <a href="#" onclick="loadContent('videoconferencias')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="videoconferencias">
                        <i class="fas fa-video"></i>
                        <span>Videoconferências</span>
                    </a>
                    <a href="#" onclick="loadContent('bibliografia')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="bibliografia">
                        <i class="fas fa-book-reader"></i>
                        <span>Bibliografia</span>
                    </a>
                    <a href="#" onclick="loadContent('recursos')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="recursos">
                        <i class="fas fa-share-alt"></i>
                        <span>Compartilhamento de Recursos</span>
                    </a>
                    <a href="#" onclick="loadContent('recuperacao')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="recuperacao">
                        <i class="fas fa-redo"></i>
                        <span>Recuperação de Notas</span>
                    </a>
                    <a href="#" onclick="loadContent('feedback')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="feedback">
                        <i class="fas fa-comment-dots"></i>
                        <span>Feedback para Alunos</span>
                    </a>
                    <a href="#" onclick="loadContent('atendimento')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="atendimento">
                        <i class="fas fa-clock"></i>
                        <span>Horários de Atendimento</span>
                    </a>
                    <a href="#" onclick="loadContent('entregas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="entregas">
                        <i class="fas fa-inbox"></i>
                        <span>Controle de Entregas</span>
                    </a>
                    <a href="#" onclick="loadContent('formativas')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="formativas">
                        <i class="fas fa-tasks"></i>
                        <span>Avaliações Formativas</span>
                    </a>
                    <a href="#" onclick="loadContent('gamificacao')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="gamificacao">
                        <i class="fas fa-trophy"></i>
                        <span>Gamificação da Turma</span>
                    </a>
                    <a href="#" onclick="loadContent('anotacoes')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="anotacoes">
                        <i class="fas fa-sticky-note"></i>
                        <span>Anotações sobre Alunos</span>
                    </a>
                    <a href="#" onclick="loadContent('comunicacao')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="comunicacao">
                        <i class="fas fa-envelope"></i>
                        <span>Comunicação com Professores</span>
                    </a>
                    <a href="#" onclick="loadContent('calendario')" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="calendario">
                        <i class="fas fa-calendar"></i>
                        <span>Calendário Pessoal</span>
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
                            <h1 class="text-2xl font-bold text-gray-800" id="page-title">Painel do Professor</h1>
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
                                    <p class="text-xs text-gray-500">Professor</p>
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
                'dashboard': 'Painel do Professor',
                'lancar_notas': 'Lançar Notas',
                'chamada': 'Chamada Digital',
                'diario': 'Diário de Classe',
                'planejamento': 'Planejamento de Aulas',
                'materiais': 'Materiais Didáticos',
                'provas': 'Provas Online',
                'trabalhos_grupo': 'Trabalhos em Grupo',
                'forum': 'Fórum de Discussão',
                'acompanhamento': 'Acompanhamento Individual',
                'relatorios': 'Relatórios de Desempenho',
                'avisos': 'Avisos aos Responsáveis',
                'projetos': 'Gestão de Projetos',
                'videoconferencias': 'Videoconferências',
                'bibliografia': 'Bibliografia',
                'recursos': 'Compartilhamento de Recursos',
                'recuperacao': 'Recuperação de Notas',
                'feedback': 'Feedback para Alunos',
                'atendimento': 'Horários de Atendimento',
                'entregas': 'Controle de Entregas',
                'formativas': 'Avaliações Formativas',
                'gamificacao': 'Gamificação da Turma',
                'anotacoes': 'Anotações sobre Alunos',
                'comunicacao': 'Comunicação com Professores',
                'calendario': 'Calendário Pessoal'
            };
            document.getElementById('page-title').textContent = titles[page] || 'Painel do Professor';

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
                    
                    // Execute scripts in the loaded content
                    const scripts = document.getElementById('content-container').querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.head.appendChild(newScript);
                    });
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
