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

// Criar tabela de trabalhos entregues se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS trabalhos_entregues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    trabalho_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    observacao TEXT,
    data_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
    nota DECIMAL(5, 2),
    feedback TEXT,
    status ENUM('pendente', 'corrigido', 'revisar') DEFAULT 'pendente',
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$success = '';
$error = '';

// Obter trabalhos disponíveis
$trabalhos = [];
try {
    $stmt = $conn->prepare("
        SELECT t.*, u.nome_completo as professor_nome,
        (SELECT id FROM trabalhos_entregues WHERE aluno_id = ? AND trabalho_id = t.id) as entrega_id
        FROM trabalhos t 
        JOIN usuarios u ON t.professor_id = u.id 
        WHERE (t.visibilidade = 'publico' 
            OR (t.visibilidade = 'turma' AND t.turma = ?)
            OR (t.visibilidade = 'serie' AND t.serie = ?))
        AND t.data_limite >= CURDATE()
        ORDER BY t.data_limite ASC
    ");
    $stmt->execute([$aluno_id, $turma, $serie]);
    $trabalhos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos: " . $e->getMessage());
}

// Entregar trabalho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'entregar') {
    $trabalho_id = intval($_POST['trabalho_id'] ?? 0);
    $observacao = sanitizeInput($_POST['observacao'] ?? '');
    
    if (empty($trabalho_id)) {
        $error = 'Por favor, selecione o trabalho.';
    } elseif (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Por favor, selecione um arquivo.';
    } else {
        try {
            $upload_dir = '../uploads/trabalhos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['arquivo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'doc', 'docx', 'zip', 'rar'];
            
            if (!in_array($ext, $allowed_exts)) {
                $error = 'Tipo de arquivo não permitido. Use PDF, DOC, DOCX, ZIP ou RAR.';
            } else {
                $nome_arquivo = uniqid() . '_' . time() . '.' . $ext;
                $caminho_arquivo = $upload_dir . $nome_arquivo;
                
                if (move_uploaded_file($file['tmp_name'], $caminho_arquivo)) {
                    $stmt = $conn->prepare("INSERT INTO trabalhos_entregues (aluno_id, trabalho_id, arquivo, observacao) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$aluno_id, $trabalho_id, 'uploads/trabalhos/' . $nome_arquivo, $observacao]);
                    $success = 'Trabalho entregue com sucesso!';
                } else {
                    $error = 'Erro ao fazer upload do arquivo.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao entregar trabalho.';
        }
    }
}

// Obter trabalhos entregues
$trabalhos_entregues = [];
try {
    $stmt = $conn->prepare("
        SELECT te.*, t.titulo as trabalho_titulo, t.disciplina, u.nome_completo as professor_nome
        FROM trabalhos_entregues te
        JOIN trabalhos t ON te.trabalho_id = t.id
        JOIN usuarios u ON t.professor_id = u.id
        WHERE te.aluno_id = ?
        ORDER BY te.data_entrega DESC
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
    <title>Trabalhos | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Trabalhos</h1>
                <p class="text-gray-600 mt-2">Entregar e acompanhar trabalhos</p>
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

        <!-- Trabalhos Disponíveis -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Trabalhos Disponíveis</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Professor</th>
                            <th class="px-4 sm:px-6 py-4">Data Limite</th>
                            <th class="px-4 sm:px-6 py-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trabalhos as $trabalho): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-file-alt text-purple-600"></i>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($trabalho['titulo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                        <?php echo htmlspecialchars($trabalho['disciplina']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($trabalho['professor_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($trabalho['data_limite'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <?php if ($trabalho['entrega_id']): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">Entregue</span>
                                    <?php else: ?>
                                        <button onclick="entregarTrabalho(<?php echo $trabalho['id']; ?>, '<?php echo htmlspecialchars($trabalho['titulo']); ?>')" class="px-4 py-2 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-lg hover:from-azul-escuro hover:to-verde-claro transition-all text-sm">
                                            Entregar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($trabalhos)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                    <p>Nenhum trabalho disponível.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trabalhos Entregues -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Meus Trabalhos Entregues</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Trabalho</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Entrega</th>
                            <th class="px-4 sm:px-6 py-4">Nota</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trabalhos_entregues as $entrega): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-check text-green-600"></i>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($entrega['trabalho_titulo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($entrega['disciplina']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y H:i', strtotime($entrega['data_entrega'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <?php if ($entrega['nota']): ?>
                                        <span class="text-2xl font-bold <?php echo $entrega['nota'] >= 7 ? 'text-green-600' : ($entrega['nota'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                            <?php echo $entrega['nota']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($entrega['status']) {
                                            'pendente' => 'bg-yellow-100 text-yellow-600',
                                            'corrigido' => 'bg-green-100 text-green-600',
                                            'revisar' => 'bg-red-100 text-red-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($entrega['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($trabalhos_entregues)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>Nenhum trabalho entregue ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Entregar Trabalho -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Entregar Trabalho</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="entregar">
                    <input type="hidden" id="trabalho_id" name="trabalho_id">
                    
                    <div class="mb-4">
                        <label for="arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Arquivo *</label>
                        <input type="file" id="arquivo" name="arquivo" required accept=".pdf,.doc,.docx,.zip,.rar"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Formatos aceitos: PDF, DOC, DOCX, ZIP, RAR (máx 10MB)</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacao" class="block text-sm font-semibold text-gray-700 mb-2">Observação</label>
                        <textarea id="observacao" name="observacao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações sobre o trabalho"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-upload mr-2"></i>
                        Entregar Trabalho
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

        function toggleModal() {
            const modal = document.getElementById('modal');
            modal.classList.toggle('hidden');
        }

        function entregarTrabalho(id, titulo) {
            document.getElementById('trabalho_id').value = id;
            toggleModal();
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
