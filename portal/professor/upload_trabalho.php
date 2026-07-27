<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Obter turmas do professor
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.* 
        FROM turmas t 
        JOIN grade_aulas ga ON t.id = ga.turma_id 
        WHERE ga.professor_id = ?
    ");
    $stmt->execute([$professor_id]);
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

$success = '';
$error = '';

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'trabalho');
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $turma_id = !empty($_POST['turma_id']) ? intval($_POST['turma_id']) : null;
    $data_entrega = !empty($_POST['data_entrega']) ? $_POST['data_entrega'] : null;
    
    if (empty($titulo) || empty($tipo)) {
        $error = 'Por favor, preencha o título e o tipo.';
    } elseif (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
        $file_type = $_FILES['arquivo']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/professor_' . $professor_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $upload_dir . $new_filename)) {
                try {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("
                        INSERT INTO trabalhos_correcoes (professor_id, turma_id, titulo, descricao, tipo, disciplina, data_entrega, arquivo_path, data_upload, ativo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), TRUE)
                    ");
                    $stmt->execute([$professor_id, $turma_id, $titulo, $descricao, $tipo, $disciplina, $data_entrega, $new_filename]);
                    $success = 'Arquivo enviado com sucesso!';
                } catch (PDOException $e) {
                    error_log("Erro ao salvar no banco: " . $e->getMessage());
                    $error = 'Erro ao salvar no banco de dados.';
                }
            } else {
                $error = 'Erro ao fazer upload do arquivo.';
            }
        } else {
            $error = 'Tipo de arquivo não permitido. Use PDF, DOC, DOCX, XLS, XLSX, PPT ou PPTX.';
        }
    } else {
        $error = 'Erro no upload do arquivo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Trabalho | Portal CEAA</title>
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
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #063b7a 0%, #0b4a8c 50%, #13843b 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-sm tracking-wide">UPLOAD DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">TRABALHOS</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-3 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-11 h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden md:block text-left">
                                <span class="text-white text-sm font-medium block"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                                <span class="text-white/70 text-xs">Professor</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-3 w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-white/80">Professor</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Professor</span>
                                </a>
                                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Bem-vindo -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-2">
                <div class="w-2 h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-4xl font-display font-bold text-azul-principal">
                        Upload de Trabalhos e Correções
                    </h1>
                    <p class="text-gray-600 mt-1 text-lg">Envie materiais, correções e trabalhos para seus alunos</p>
                </div>
            </div>
        </div>

        <!-- Formulário de Upload -->
        <div class="glass-card rounded-3xl shadow-xl p-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-cloud-upload-alt text-white text-3xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-display font-bold text-azul-principal">Novo Upload</h2>
                    <p class="text-gray-600">Preencha os dados e selecione o arquivo</p>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Título do trabalho/correção">
                    </div>
                    
                    <div>
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="trabalho">Trabalho</option>
                            <option value="correcao">Correção</option>
                            <option value="material">Material de Aula</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                        placeholder="Descrição detalhada do conteúdo"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina</label>
                        <input type="text" id="disciplina" name="disciplina"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: Matemática, Português">
                    </div>
                    
                    <div>
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma (opcional)</label>
                        <select id="turma_id" name="turma_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Todas as turmas</option>
                            <?php foreach ($turmas as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?> - <?php echo htmlspecialchars($t['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="data_entrega" class="block text-sm font-semibold text-gray-700 mb-2">Data de Entrega (opcional)</label>
                        <input type="date" id="data_entrega" name="data_entrega"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                    </div>
                    
                    <div>
                        <label for="arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Arquivo *</label>
                        <input type="file" id="arquivo" name="arquivo" required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                        <p class="text-xs text-gray-500 mt-1">Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX</p>
                    </div>
                </div>
                
                <button type="submit"
                   class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-4 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg text-lg">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>Enviar Arquivo
                </button>
            </form>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
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
