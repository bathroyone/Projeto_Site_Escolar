<?php
require_once '../config.php';

requireAdmin();

$admin_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Portal CEAA</title>
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
        <aside class="w-64 bg-white border-r border-gray-200 h-screen fixed left-0 top-0 z-30 hidden lg:block flex flex-col">
            <div class="p-6 flex-shrink-0">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-800">Portal CEAA</h1>
                        <p class="text-xs text-gray-500">Administração</p>
                    </div>
                </div>
            </div>
            
            <nav class="space-y-1 flex-1 overflow-y-auto px-6">
                <a href="?page=dashboard" class="nav-link flex items-center gap-3 px-4 py-3 bg-primary-50 text-primary-700 rounded-lg font-medium" data-page="dashboard">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="?page=usuarios" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="usuarios">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
                <a href="?page=turmas" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="turmas">
                    <i class="fas fa-chalkboard"></i>
                    <span>Turmas</span>
                </a>
                <a href="?page=arquivos" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="arquivos">
                    <i class="fas fa-folder"></i>
                    <span>Arquivos</span>
                </a>
                <a href="?page=backup" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="backup">
                    <i class="fas fa-database"></i>
                    <span>Backup/Restore</span>
                </a>
                <a href="?page=audit_logs" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="audit_logs">
                    <i class="fas fa-history"></i>
                    <span>Audit Logs</span>
                </a>
                <a href="?page=permissoes" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="permissoes">
                    <i class="fas fa-lock"></i>
                    <span>Permissões</span>
                </a>
                <a href="?page=notificacoes" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="notificacoes">
                    <i class="fas fa-bell"></i>
                    <span>Notificações</span>
                </a>
                <a href="?page=relatorios" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="relatorios">
                    <i class="fas fa-chart-bar"></i>
                    <span>Relatórios</span>
                </a>
                <a href="?page=analytics" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="analytics">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="?page=integracoes" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="integracoes">
                    <i class="fas fa-plug"></i>
                    <span>Integrações</span>
                </a>
                <a href="?page=suporte" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="suporte">
                    <i class="fas fa-headset"></i>
                    <span>Suporte</span>
                </a>
                <a href="?page=horarios" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="horarios">
                    <i class="fas fa-clock"></i>
                    <span>Horários</span>
                </a>
                <a href="?page=patrimonio" class="nav-link flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" data-page="patrimonio">
                    <i class="fas fa-box"></i>
                    <span>Patrimônio</span>
                </a>
            </nav>
            
            <div class="p-6 border-t border-gray-200 flex-shrink-0">
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
                            <h1 class="text-2xl font-bold text-gray-800" id="page-title">Painel Administrativo</h1>
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
                                    <p class="text-xs text-gray-500">Administrador</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Content Container -->
                <div id="content-container">
                    <?php
                    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                    $allowed_pages = ['dashboard', 'usuarios', 'turmas', 'arquivos', 'backup', 'audit_logs', 'permissoes', 'notificacoes', 'relatorios', 'analytics', 'integracoes', 'suporte', 'horarios', 'patrimonio'];
                    
                    if (in_array($page, $allowed_pages)) {
                        include "pages/$page.php";
                    } else {
                        echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-700">Página não encontrada</p>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update active nav link based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'dashboard'; ?>';
            const titles = {
                'dashboard': 'Painel Administrativo',
                'usuarios': 'Gerenciar Usuários',
                'turmas': 'Gerenciar Turmas',
                'arquivos': 'Gerenciar Arquivos',
                'backup': 'Backup e Restore',
                'audit_logs': 'Logs de Auditoria',
                'permissoes': 'Gestão de Permissões',
                'notificacoes': 'Notificações',
                'relatorios': 'Relatórios',
                'analytics': 'Analytics',
                'integracoes': 'Integrações',
                'suporte': 'Suporte',
                'horarios': 'Horários',
                'patrimonio': 'Patrimônio'
            };
            document.getElementById('page-title').textContent = titles[currentPage] || 'Painel Administrativo';
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-primary-50', 'text-primary-700', 'font-medium');
                link.classList.add('text-gray-600');
            });
            
            const activeLink = document.querySelector(`.nav-link[data-page="${currentPage}"]`);
            if (activeLink) {
                activeLink.classList.add('bg-primary-50', 'text-primary-700', 'font-medium');
                activeLink.classList.remove('text-gray-600');
            }
        });
    </script>
</body>
</html>
