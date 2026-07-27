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

// Enviar trabalho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_trabalho') {
    $entrega_id = intval($_POST['entrega_id'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($entrega_id)) {
        $error = 'Por favor, selecione um trabalho.';
    } elseif (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Por favor, selecione um arquivo.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se já existe entrega
            $stmt = $pdo->prepare("SELECT * FROM controle_entregas_alunos WHERE entrega_id = ? AND aluno_id = ?");
            $stmt->execute([$entrega_id, $aluno_id]);
            $entrega_existente = $stmt->fetch();
            
            if ($entrega_existente) {
                $error = 'Você já enviou este trabalho.';
            } else {
                // Processar upload
                $arquivo = $_FILES['arquivo'];
                $nome_arquivo = time() . '_' . $arquivo['name'];
                $caminho = '../uploads/trabalhos/' . $nome_arquivo;
                
                if (!is_dir('../uploads/trabalhos')) {
                    mkdir('../uploads/trabalhos', 0777, true);
                }
                
                if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
                    $stmt = $pdo->prepare("INSERT INTO controle_entregas_alunos (entrega_id, aluno_id, status, data_entrega, observacoes) VALUES (?, ?, 'entregue', NOW(), ?)");
                    $stmt->execute([$entrega_id, $aluno_id, $observacoes]);
                    
                    logAudit('TRABALHO_ENVIAR', 'controle_entregas_alunos', $pdo->lastInsertId(), null, ['entrega_id' => $entrega_id]);
                    
                    $success = 'Trabalho enviado com sucesso!';
                } else {
                    $error = 'Erro ao fazer upload do arquivo.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao enviar trabalho.';
        }
    }
}

// Obter trabalhos pendentes
$trabalhos_pendentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ce.*, d.nome as disciplina_nome, u.nome_completo as professor_nome,
               (SELECT COUNT(*) FROM controle_entregas_alunos WHERE entrega_id = ce.id AND aluno_id = ?) as ja_enviado
        FROM controle_entregas ce
        JOIN disciplinas d ON ce.disciplina_id = d.id
        JOIN usuarios u ON ce.professor_id = u.id
        WHERE ce.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND ce.data_limite >= CURDATE()
        AND ce.status = 'ativo'
        ORDER BY ce.data_limite ASC
    ");
    $stmt->execute([$aluno_id, $turma, $serie]);
    $trabalhos_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos pendentes: " . $e->getMessage());
}

// Obter trabalhos entregues
$trabalhos_entregues = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT cea.*, ce.titulo, ce.data_limite, d.nome as disciplina_nome, u.nome_completo as professor_nome
        FROM controle_entregas_alunos cea
        JOIN controle_entregas ce ON cea.entrega_id = ce.id
        JOIN disciplinas d ON ce.disciplina_id = d.id
        JOIN usuarios u ON ce.professor_id = u.id
        WHERE cea.aluno_id = ?
        ORDER BY cea.data_entrega DESC
    ");
    $stmt->execute([$aluno_id]);
    $trabalhos_entregues = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos entregues: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrega de Trabalhos | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Entrega de Trabalhos</h1>
                <p class="text-gray-600 mt-2">Gerencie suas entregas online</p>
            </div>
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

        <!-- Trabalhos Pendentes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h2 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-tasks mr-2"></i>Trabalhos Pendentes
                </h2>
            </div>
            <div class="p-6">
                <?php if (count($trabalhos_pendentes) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($trabalhos_pendentes as $trabalho): ?>
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($trabalho['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($trabalho['disciplina_nome']); ?> | 
                                            <?php echo htmlspecialchars($trabalho['professor_nome']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Prazo: <?php echo date('d/m/Y H:i', strtotime($trabalho['data_limite'])); ?>
                                        </p>
                                    </div>
                                    <?php if ($trabalho['ja_enviado'] > 0): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                            <i class="fas fa-check mr-1"></i>Enviado
                                        </span>
                                    <?php else: ?>
                                        <button onclick="abrirModal(<?php echo $trabalho['id']; ?>, '<?php echo htmlspecialchars($trabalho['titulo']); ?>')" class="px-4 py-2 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all text-sm">
                                            <i class="fas fa-upload mr-1"></i>Enviar
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-600 text-sm mt-2"><?php echo htmlspecialchars(substr($trabalho['descricao'] ?? '', 0, 100)) . '...'; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-tasks text-4xl mb-2"></i>
                        <p>Nenhum trabalho pendente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Trabalhos Entregues -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">
                    <i class="fas fa-check-circle mr-2"></i>Trabalhos Entregues
                </h2>
            </div>
            <div class="p-6">
                <?php if (count($trabalhos_entregues) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($trabalhos_entregues as $trabalho): ?>
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($trabalho['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($trabalho['disciplina_nome']); ?> | 
                                            <?php echo htmlspecialchars($trabalho['professor_nome']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            Enviado: <?php echo date('d/m/Y H:i', strtotime($trabalho['data_entrega'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($trabalho['status']) {
                                            'entregue' => 'bg-green-100 text-green-600',
                                            'atrasado' => 'bg-yellow-100 text-yellow-600',
                                            'nao_entregue' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($trabalho['status']); ?>
                                    </span>
                                </div>
                                <?php if ($trabalho['nota']): ?>
                                    <p class="text-sm text-gray-600 mt-2">
                                        <i class="fas fa-star mr-1"></i>
                                        Nota: <span class="font-bold"><?php echo $trabalho['nota']; ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Nenhum trabalho entregue ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Enviar Trabalho -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Enviar Trabalho</h2>
                    <button onclick="fecharModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="enviar_trabalho">
                    <input type="hidden" id="entrega_id" name="entrega_id">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Trabalho: <span id="trabalho_titulo" class="font-semibold"></span></p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Arquivo *</label>
                        <input type="file" id="arquivo" name="arquivo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações sobre o trabalho"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-upload mr-2"></i>
                        Enviar Trabalho
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

        function abrirModal(entregaId, titulo) {
            document.getElementById('entrega_id').value = entregaId;
            document.getElementById('trabalho_titulo').textContent = titulo;
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
