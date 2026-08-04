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

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter materiais complementares
$materiais = [];
try {
    $stmt = $pdo->query("
        SELECT 
            m.*,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM materiais_didaticos m
        JOIN disciplinas d ON m.disciplina_id = d.id
        JOIN usuarios u ON m.professor_id = u.id
        WHERE m.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND m.status = 'ativo'
        ORDER BY m.data_upload DESC
    ");
    $stmt->execute([$turma, $serie]);
    $materiais = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter materiais: " . $e->getMessage());
}

// Agrupar por disciplina
$materiais_por_disciplina = [];
foreach ($materiais as $material) {
    $disciplina = $material['disciplina_nome'];
    if (!isset($materiais_por_disciplina[$disciplina])) {
        $materiais_por_disciplina[$disciplina] = [];
    }
    $materiais_por_disciplina[$disciplina][] = $material;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiais Complementares | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Materiais Complementares</h1>
                <p class="text-gray-600 mt-2">Apostilas, vídeos e recursos de estudo</p>
            </div>
        </div>

        <!-- Filtros por Tipo -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button onclick="filtrarTipo('todos')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-azul-principal text-white" data-tipo="todos">
                    <i class="fas fa-th-large mr-2"></i>Todos
                </button>
                <button onclick="filtrarTipo('pdf')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="pdf">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </button>
                <button onclick="filtrarTipo('video')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="video">
                    <i class="fas fa-video mr-2"></i>Vídeo
                </button>
                <button onclick="filtrarTipo('apresentacao')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="apresentacao">
                    <i class="fas fa-file-powerpoint mr-2"></i>Apresentação
                </button>
                <button onclick="filtrarTipo('link')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="link">
                    <i class="fas fa-link mr-2"></i>Link
                </button>
            </div>
        </div>

        <!-- Materiais por Disciplina -->
        <div class="space-y-8">
            <?php foreach ($materiais_por_disciplina as $disciplina => $materiais): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                        <h2 class="text-xl font-display font-bold text-white">
                            <i class="fas fa-book mr-2"></i>
                            <?php echo htmlspecialchars($disciplina); ?>
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($materiais as $material): ?>
                                <div class="material-card p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all" data-tipo="<?php echo $material['tipo']; ?>">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                            <?php 
                                            $cor_tipo = match($material['tipo']) {
                                                'pdf' => 'bg-red-100',
                                                'video' => 'bg-purple-100',
                                                'apresentacao' => 'bg-orange-100',
                                                'link' => 'bg-blue-100',
                                                default => 'bg-gray-100'
                                            };
                                            echo $cor_tipo;
                                            ?>">
                                            <i class="fas 
                                                <?php 
                                                $icone_tipo = match($material['tipo']) {
                                                    'pdf' => 'fa-file-pdf',
                                                    'video' => 'fa-video',
                                                    'apresentacao' => 'fa-file-powerpoint',
                                                    'link' => 'fa-link',
                                                    default => 'fa-file'
                                                };
                                                echo $icone_tipo;
                                                ?> 
                                                text-xl 
                                                <?php 
                                                $cor_texto = match($material['tipo']) {
                                                    'pdf' => 'text-red-600',
                                                    'video' => 'text-purple-600',
                                                    'apresentacao' => 'text-orange-600',
                                                    'link' => 'text-blue-600',
                                                    default => 'text-gray-600'
                                                };
                                                echo $cor_texto;
                                                ?>">
                                            </i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($material['titulo']); ?></h3>
                                            <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($material['professor_nome']); ?></p>
                                            <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($material['descricao'] ?? '', 0, 60)) . '...'; ?></p>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-400">
                                                    <?php echo date('d/m/Y', strtotime($material['data_upload'])); ?>
                                                </span>
                                                <a href="<?php echo htmlspecialchars($material['arquivo_url']); ?>" target="_blank" 
                                                    class="px-3 py-1 bg-azul-principal text-white rounded-lg text-sm font-semibold hover:bg-azul-escuro transition-colors">
                                                    <i class="fas fa-download mr-1"></i>
                                                    <?php echo $material['tipo'] === 'link' ? 'Acessar' : 'Baixar'; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($materiais_por_disciplina)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-2"></i>
                    <p>Nenhum material complementar disponível.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function filtrarTipo(tipo) {
            const cards = document.querySelectorAll('.material-card');
            const buttons = document.querySelectorAll('.filtro-btn');
            
            // Atualizar botões
            buttons.forEach(btn => {
                if (btn.dataset.tipo === tipo) {
                    btn.classList.remove('bg-gray-100', 'text-gray-700');
                    btn.classList.add('bg-azul-principal', 'text-white');
                } else {
                    btn.classList.remove('bg-azul-principal', 'text-white');
                    btn.classList.add('bg-gray-100', 'text-gray-700');
                }
            });
            
            // Filtrar cards
            cards.forEach(card => {
                if (tipo === 'todos' || card.dataset.tipo === tipo) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
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
