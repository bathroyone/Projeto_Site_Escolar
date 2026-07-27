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

$success = '';
$error = '';

// Criar novo objetivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_objetivo') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    $data_meta = $_POST['data_meta'] ?? null;
    
    if (empty($titulo)) {
        $error = 'Por favor, informe o título do objetivo.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO objetivos_alunos (aluno_id, titulo, descricao, categoria, data_meta, data_criacao, status) VALUES (?, ?, ?, ?, ?, NOW(), 'ativo')");
            $stmt->execute([$aluno_id, $titulo, $descricao, $categoria, $data_meta]);
            
            logAudit('OBJETIVO_CRIAR', 'objetivos_alunos', $pdo->lastInsertId(), null, ['titulo' => $titulo]);
            
            $success = 'Objetivo criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar objetivo.';
        }
    }
}

// Marcar objetivo como concluído
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'concluir_objetivo') {
    $objetivo_id = intval($_POST['objetivo_id'] ?? 0);
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE objetivos_alunos SET status = 'concluido', data_conclusao = NOW() WHERE id = ? AND aluno_id = ?");
        $stmt->execute([$objetivo_id, $aluno_id]);
        
        logAudit('OBJETIVO_CONCLUIR', 'objetivos_alunos', $objetivo_id, null);
        
        $success = 'Objetivo concluído com sucesso!';
    } catch (PDOException $e) {
        $error = 'Erro ao concluir objetivo.';
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter objetivos do aluno
$objetivos = [];
try {
    $stmt = $pdo->query("
        SELECT 
            oa.*,
            CASE 
                WHEN oa.status = 'concluido' THEN 'concluido'
                WHEN oa.data_meta < CURDATE() THEN 'atrasado'
                ELSE 'ativo'
            END as status_formatado
        FROM objetivos_alunos oa
        WHERE oa.aluno_id = ?
        ORDER BY oa.status = 'concluido' ASC, oa.data_meta ASC
    ");
    $stmt->execute([$aluno_id]);
    $objetivos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter objetivos: " . $e->getMessage());
}

// Calcular estatísticas
$estatisticas = [
    'total' => count($objetivos),
    'ativos' => 0,
    'concluidos' => 0,
    'atrasados' => 0
];

foreach ($objetivos as $objetivo) {
    if ($objetivo['status'] === 'concluido') {
        $estatisticas['concluidos']++;
    } elseif ($objetivo['status_formatado'] === 'atrasado') {
        $estatisticas['atrasados']++;
    } else {
        $estatisticas['ativos']++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objetivos e Metas | Portal de Gestão Escolar</title>
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
                                <p class="text-sm text-gray-500">Aluno</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Objetivos e Metas</h1>
                <p class="text-gray-600 mt-2">Defina e acompanhe seus objetivos acadêmicos</p>
            </div>
            <button onclick="abrirModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Objetivo
            </button>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total de Objetivos</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo $estatisticas['total']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-bullseye text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Ativos</p>
                        <p class="text-4xl font-bold text-blue-600"><?php echo $estatisticas['ativos']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-spinner text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Concluídos</p>
                        <p class="text-4xl font-bold text-green-600"><?php echo $estatisticas['concluidos']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Atrasados</p>
                        <p class="text-4xl font-bold text-red-600"><?php echo $estatisticas['atrasados']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Objetivos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Meus Objetivos</h2>
            </div>
            <div class="p-6">
                <?php if (!empty($objetivos)): ?>
                    <div class="space-y-4">
                        <?php foreach ($objetivos as $objetivo): ?>
                            <div class="p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all 
                                <?php echo $objetivo['status'] === 'concluido' ? 'bg-green-50' : ($objetivo['status_formatado'] === 'atrasado' ? 'bg-red-50' : 'bg-gray-50'); ?>">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                            <?php 
                                            $cor_icon = match($objetivo['status']) {
                                                'concluido' => 'bg-green-100',
                                                'ativo' => 'bg-blue-100',
                                                default => 'bg-gray-100'
                                            };
                                            echo $cor_icon;
                                            ?>">
                                            <i class="fas 
                                                <?php 
                                                $icone = match($objetivo['status']) {
                                                    'concluido' => 'fa-check-circle',
                                                    'ativo' => 'fa-bullseye',
                                                    default => 'fa-flag'
                                                };
                                                echo $icone;
                                                ?> 
                                                text-xl 
                                                <?php 
                                                $cor_texto = match($objetivo['status']) {
                                                    'concluido' => 'text-green-600',
                                                    'ativo' => 'text-blue-600',
                                                    default => 'text-gray-600'
                                                };
                                                echo $cor_texto;
                                                ?>">
                                            </i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <h3 class="font-semibold text-gray-800 text-lg <?php echo $objetivo['status'] === 'concluido' ? 'line-through text-gray-400' : ''; ?>">
                                                    <?php echo htmlspecialchars($objetivo['titulo']); ?>
                                                </h3>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                                    <?php 
                                                    $cor_status = match($objetivo['status']) {
                                                        'concluido' => 'bg-green-100 text-green-600',
                                                        'ativo' => ($objetivo['status_formatado'] === 'atrasado' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'),
                                                        default => 'bg-gray-100 text-gray-600'
                                                    };
                                                    echo $cor_status;
                                                    ?>">
                                                    <?php echo ucfirst($objetivo['status_formatado']); ?>
                                                </span>
                                            </div>
                                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars(substr($objetivo['descricao'] ?? '', 0, 100)) . '...'; ?></p>
                                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                                <span>
                                                    <i class="fas fa-tag mr-1"></i>
                                                    <?php echo ucfirst($objetivo['categoria'] ?? 'Geral'); ?>
                                                </span>
                                                <?php if ($objetivo['data_meta']): ?>
                                                    <span class="<?php echo $objetivo['status_formatado'] === 'atrasado' ? 'text-red-600 font-semibold' : ''; ?>">
                                                        <i class="fas fa-calendar-alt mr-1"></i>
                                                        Meta: <?php echo date('d/m/Y', strtotime($objetivo['data_meta'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($objetivo['status'] !== 'concluido'): ?>
                                        <form method="POST" action="" class="flex-shrink-0">
                                            <input type="hidden" name="action" value="concluir_objetivo">
                                            <input type="hidden" name="objetivo_id" value="<?php echo $objetivo['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors">
                                                <i class="fas fa-check mr-1"></i>
                                                Concluir
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-bullseye text-4xl mb-2"></i>
                        <p>Nenhum objetivo definido ainda.</p>
                        <button onclick="abrirModal()" class="mt-4 px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-plus mr-2"></i>Criar Primeiro Objetivo
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Objetivo -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Objetivo</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_objetivo">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Melhorar nota em Matemática">
                    </div>
                    
                    <div class="mb-4">
                        <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Geral</option>
                            <option value="academico">Acadêmico</option>
                            <option value="comportamental">Comportamental</option>
                            <option value="pessoal">Pessoal</option>
                            <option value="social">Social</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="data_meta" class="block text-sm font-semibold text-gray-700 mb-2">Data Meta</label>
                        <input type="date" id="data_meta" name="data_meta"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-6">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descreva seu objetivo em detalhes..."></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Criar Objetivo
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

        function abrirModal() {
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
