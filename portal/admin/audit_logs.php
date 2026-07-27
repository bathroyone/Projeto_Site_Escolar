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

// Filtros
$filtros = [
    'usuario_id' => $_GET['usuario_id'] ?? null,
    'acao' => $_GET['acao'] ?? null,
    'tabela' => $_GET['tabela'] ?? null,
    'data_inicio' => $_GET['data_inicio'] ?? null,
    'data_fim' => $_GET['data_fim'] ?? null
];

// Paginação
$pagina = intval($_GET['pagina'] ?? 1);
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Obter logs
$logs = getAuditLogs($por_pagina, $offset, $filtros);

// Obter total
$total = getAuditLogsCount($filtros);
$total_paginas = ceil($total / $por_pagina);

// Obter usuários para filtro
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT usuario_id, usuario_nome FROM audit_logs WHERE usuario_id IS NOT NULL ORDER BY usuario_nome");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Obter ações para filtro
$acoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT acao FROM audit_logs ORDER BY acao");
    $acoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter ações: " . $e->getMessage());
}

// Obter tabelas para filtro
$tabelas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT tabela FROM audit_logs WHERE tabela IS NOT NULL ORDER BY tabela");
    $tabelas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter tabelas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Auditoria | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Logs de Auditoria</h1>
                <p class="text-gray-600 mt-2">Histórico completo de ações no sistema</p>
            </div>
            <div class="text-sm text-gray-500">
                Total: <span class="font-bold text-azul-principal"><?php echo $total; ?></span> registros
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-2">Usuário</label>
                    <select id="usuario_id" name="usuario_id" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['usuario_id']; ?>" <?php echo $filtros['usuario_id'] == $usuario['usuario_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['usuario_nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="acao" class="block text-sm font-semibold text-gray-700 mb-2">Ação</label>
                    <select id="acao" name="acao" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="">Todas</option>
                        <?php foreach ($acoes as $acao): ?>
                            <option value="<?php echo $acao['acao']; ?>" <?php echo $filtros['acao'] == $acao['acao'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($acao['acao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="tabela" class="block text-sm font-semibold text-gray-700 mb-2">Tabela</label>
                    <select id="tabela" name="tabela" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                        <option value="">Todas</option>
                        <?php foreach ($tabelas as $tabela): ?>
                            <option value="<?php echo $tabela['tabela']; ?>" <?php echo $filtros['tabela'] == $tabela['tabela'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tabela['tabela']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $filtros['data_inicio'] ?? ''; ?>" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div>
                    <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo $filtros['data_fim'] ?? ''; ?>" onchange="this.form.submit()"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
            </form>
            
            <div class="mt-4 flex gap-2">
                <a href="audit_logs.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                    <i class="fas fa-times mr-2"></i>Limpar Filtros
                </a>
            </div>
        </div>

        <!-- Tabela de Logs -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Data/Hora</th>
                            <th class="px-4 sm:px-6 py-4">Usuário</th>
                            <th class="px-4 sm:px-6 py-4">Ação</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Tabela</th>
                            <th class="px-4 sm:px-6 py-4 hidden lg:table-cell">IP</th>
                            <th class="px-4 sm:px-6 py-4">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                            <?php echo strtoupper(substr($log['usuario_nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($log['usuario_nome']); ?></span>
                                            <span class="block text-xs text-gray-500"><?php echo ucfirst($log['usuario_tipo']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_acao = match(substr($log['acao'], 0, 3)) {
                                            'CRE' => 'bg-green-100 text-green-600',
                                            'UPD' => 'bg-blue-100 text-blue-600',
                                            'DEL' => 'bg-red-100 text-red-600',
                                            'LOG' => 'bg-purple-100 text-purple-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_acao;
                                        ?>">
                                        <?php echo htmlspecialchars($log['acao']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                    <?php echo htmlspecialchars($log['tabela'] ?? '-'); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden lg:table-cell">
                                    <?php echo htmlspecialchars($log['ip'] ?? '-'); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <button onclick="verDetalhes(<?php echo htmlspecialchars(json_encode($log)); ?>)" class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                        <i class="fas fa-eye mr-1"></i>Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($logs)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                    <p>Nenhum registro encontrado.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div class="mt-6 flex items-center justify-center gap-2">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?><?php echo http_build_query($filtros); ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="px-4 py-2 bg-azul-principal text-white rounded-lg"><?php echo $i; ?></span>
                    <?php elseif ($i == 1 || $i == $total_paginas || ($i >= $pagina - 2 && $i <= $pagina + 2)): ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo http_build_query($filtros); ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif ($i == $pagina - 3 || $i == $pagina + 3): ?>
                        <span class="px-4 py-2">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?><?php echo http_build_query($filtros); ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Detalhes -->
    <div id="modal" class="modal fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Detalhes do Log</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <div class="p-6" id="modal-content">
                    <!-- Conteúdo dinâmico -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function verDetalhes(log) {
            const content = document.getElementById('modal-content');
            
            let html = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Data/Hora</label>
                            <p class="text-gray-800">${new Date(log.created_at).toLocaleString('pt-BR')}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Ação</label>
                            <p class="text-gray-800 font-medium">${log.acao}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Usuário</label>
                            <p class="text-gray-800">${log.usuario_nome}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Tipo</label>
                            <p class="text-gray-800 capitalize">${log.usuario_tipo}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Tabela</label>
                            <p class="text-gray-800">${log.tabela || '-'}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">Registro ID</label>
                            <p class="text-gray-800">${log.registro_id || '-'}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">IP</label>
                            <p class="text-gray-800">${log.ip || '-'}</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-500">User Agent</label>
                            <p class="text-gray-800 text-xs">${log.user_agent || '-'}</p>
                        </div>
                    </div>
            `;
            
            if (log.dados_antigos) {
                html += `
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">Dados Antigos</label>
                        <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-xs">${JSON.stringify(JSON.parse(log.dados_antigos), null, 2)}</pre>
                    </div>
                `;
            }
            
            if (log.dados_novos) {
                html += `
                    <div>
                        <label class="text-sm font-semibold text-gray-500 block mb-2">Dados Novos</label>
                        <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto text-xs">${JSON.stringify(JSON.parse(log.dados_novos), null, 2)}</pre>
                    </div>
                `;
            }
            
            html += '</div>';
            
            content.innerHTML = html;
            document.getElementById('modal').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modal').classList.add('hidden');
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
