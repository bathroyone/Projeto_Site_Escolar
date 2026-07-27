<?php
session_start();
require_once 'portal/config.php';

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['usuario_id']);
$userName = $_SESSION['nome'] ?? '';
$userType = $_SESSION['tipo_usuario'] ?? '';

// Obter informações do aluno se estiver logado
$aluno_info = null;
if ($isLoggedIn && $userType === 'aluno') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $aluno_info = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao obter informações do aluno: " . $e->getMessage());
    }
}

// Obter comunicados recentes
$comunicados = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM comunicados WHERE ativo = 1 ORDER BY data_envio DESC LIMIT 5");
    $comunicados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter comunicados: " . $e->getMessage());
}

// Obter eventos próximos
$eventos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM eventos WHERE data_evento >= CURDATE() ORDER BY data_evento ASC LIMIT 5");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Pais e Responsáveis | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">PORTAL DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">PAIS E RESPONSÁVEIS</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ($isLoggedIn): ?>
                        <div class="relative">
                            <button onclick="toggleMenu()" class="flex items-center gap-2 px-4 py-2 bg-white/20 rounded-xl hover:bg-white/30 transition-all">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <span class="text-white text-sm font-medium"><?php echo htmlspecialchars(substr($userName, 0, 15)); ?></span>
                                <i class="fas fa-chevron-down text-white/70 text-xs"></i>
                            </button>

                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 z-50">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-xs text-gray-500">Logado como</p>
                                    <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                                    <p class="text-xs text-azul-principal font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                                </div>
                                <a href="portal/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                                <a href="portal/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="portal/login.php" class="px-6 py-2.5 bg-white text-azul-principal rounded-full font-semibold hover:shadow-lg transition-all">
                            <i class="fas fa-sign-in-alt mr-2"></i>Acesso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Banner de Boas-vindas -->
        <div class="bg-gradient-to-r from-azul-principal to-verde-complementar rounded-3xl p-8 mb-12 text-center">
            <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">
                <i class="fas fa-users mr-3"></i>Portal de Pais e Responsáveis
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Acompanhe o desenvolvimento dos alunos, acesse comunicados, calendário escolar e muito mais.
            </p>
        </div>

        <!-- Informações do Aluno (se logado) -->
        <?php if ($isLoggedIn && $aluno_info): ?>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-8 border border-white/20">
                <h2 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-user-graduate mr-2 text-amarelo-destaque"></i>Informações do Aluno
                </h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">Nome</p>
                        <p class="text-white font-semibold"><?php echo htmlspecialchars($aluno_info['nome'] ?? 'Não disponível'); ?></p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">Turma</p>
                        <p class="text-white font-semibold"><?php echo htmlspecialchars($aluno_info['turma'] ?? 'Não disponível'); ?></p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">Status</p>
                        <p class="text-verde-complementar font-semibold"><?php echo ucfirst($aluno_info['status'] ?? 'Ativo'); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Acesso Rápido -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">Acesso Rápido</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="noticias.php" class="bg-white10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-newspaper text-white text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Notícias</h3>
                    <p class="text-gray-400 text-sm">Últimas notícias da escola</p>
                </a>

                <a href="eventos/eventos.html" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-alt text-white text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Eventos</h3>
                    <p class="text-gray-400 text-sm">Calendário de eventos</p>
                </a>

                <a href="biblioteca_vrtual/biblioteca.html" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Biblioteca</h3>
                    <p class="text-gray-400 text-sm">Biblioteca virtual</p>
                </a>

                <a href="agendar_visita.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-amarelo-destaque to-orange-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check text-azul-escuro text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Agendar Visita</h3>
                    <p class="text-gray-400 text-sm">Marque uma visita</p>
                </a>
            </div>
        </div>

        <!-- Comunicados Recentes -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-bullhorn mr-2 text-amarelo-destaque"></i>Comunicados Recentes
            </h2>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20 overflow-hidden">
                <?php if (count($comunicados) > 0): ?>
                    <?php foreach ($comunicados as $comunicado): ?>
                        <div class="p-6 border-b border-white/10 last:border-b-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars(substr($comunicado['mensagem'], 0, 150)) . '...'; ?></p>
                                </div>
                                <span class="text-gray-500 text-xs whitespace-nowrap ml-4"><?php echo date('d/m/Y', strtotime($comunicado['data_envio'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-400">
                        <i class="fas fa-bullhorn text-4xl mb-4"></i>
                        <p>Nenhum comunicado recente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Próximos Eventos -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-calendar-alt mr-2 text-amarelo-destaque"></i>Próximos Eventos
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <?php if (count($eventos) > 0): ?>
                    <?php foreach ($eventos as $evento): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-center gap-4">
                                <div class="bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl p-4 text-center min-w-[70px]">
                                    <span class="text-white font-bold text-2xl block"><?php echo date('d', strtotime($evento['data_evento'])); ?></span>
                                    <span class="text-white/80 text-xs uppercase"><?php echo date('M', strtotime($evento['data_evento'])); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo date('H:i', strtotime($evento['data_evento'])); ?> - <?php echo htmlspecialchars($evento['local'] ?? 'Local não informado'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 text-center text-gray-400">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p>Nenhum evento próximo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Links Úteis -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-link mr-2 text-amarelo-destaque"></i>Links Úteis
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="index.php#contact" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-phone text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Contato</h3>
                    <p class="text-gray-400 text-sm">Entre em contato conosco</p>
                </a>

                <a href="pre_matricula.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-user-graduate text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Pré-Matrícula</h3>
                    <p class="text-gray-400 text-sm">Faça a pré-matrícula online</p>
                </a>

                <a href="index.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-home text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Site Principal</h3>
                    <p class="text-gray-400 text-sm">Voltar ao site da escola</p>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> [Inserir nome da escola aqui]. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

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
