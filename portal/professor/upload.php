<?php
require_once '../config.php';

requireProfessor();

$error = '';
$success = '';

// Obter turmas disponíveis
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT id, nome, serie FROM turmas WHERE ano_letivo = 2026 ORDER BY serie, nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo_arquivo = sanitizeInput($_POST['tipo_arquivo'] ?? '');
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $visibilidade = sanitizeInput($_POST['visibilidade'] ?? 'turma');
    
    if (empty($titulo) || empty($tipo_arquivo) || empty($disciplina)) {
        $error = 'Por favor, preencha os campos obrigatórios.';
    } elseif (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Por favor, selecione um arquivo.';
    } else {
        $upload_result = uploadFile($_FILES['arquivo'], 'arquivos/');
        
        if ($upload_result['success']) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO arquivos (titulo, descricao, tipo_arquivo, caminho_arquivo, turma_id, serie, disciplina, professor_id, visibilidade)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $titulo,
                    $descricao,
                    $tipo_arquivo,
                    $upload_result['path'],
                    $turma_id > 0 ? $turma_id : null,
                    $serie,
                    $disciplina,
                    $_SESSION['usuario_id'],
                    $visibilidade
                ]);
                
                // Criar notificações para os alunos da turma/série
                if ($turma_id > 0) {
                    $stmt = $pdo->prepare("
                        SELECT u.id FROM usuarios u 
                        WHERE u.tipo_usuario = 'aluno' 
                        AND u.turma_id = ?
                    ");
                    $stmt->execute([$turma_id]);
                    $alunos = $stmt->fetchAll();
                    
                    foreach ($alunos as $aluno) {
                        createNotification(
                            $aluno['id'],
                            'Novo arquivo disponível',
                            "O professor adicionou um novo arquivo: $titulo",
                            'arquivo',
                            'dashboard.php'
                        );
                    }
                }
                
                $success = 'Arquivo enviado com sucesso!';
                $_POST = [];
            } catch (PDOException $e) {
                error_log("Erro ao salvar arquivo: " . $e->getMessage());
                $error = 'Erro ao salvar arquivo no banco de dados.';
            }
        } else {
            $error = $upload_result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Arquivos | Portal CEAA</title>
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
                    <a href="../dashboard.php" class="flex items-center gap-2">
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">NOME DA ESCOLA</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="../dashboard.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
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
                                <p class="text-sm text-gray-500">Professor</p>
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

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="h-32 bg-gradient-to-br from-azul-principal to-purple-600 flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-white/10"></div>
                <div class="relative z-10 text-center">
                    <i class="fas fa-cloud-upload-alt text-white text-4xl mb-2"></i>
                    <h1 class="font-display font-bold text-white text-2xl">Upload de Arquivos</h1>
                </div>
            </div>
            
            <div class="p-8">
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
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título do Arquivo *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Ex: Trabalho de Matemática - Capítulo 3">
                    </div>
                    
                    <div class="mb-6">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Descrição do conteúdo do arquivo..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="tipo_arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Arquivo *</label>
                            <select id="tipo_arquivo" name="tipo_arquivo" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                <option value="">Selecione</option>
                                <option value="trabalho">Trabalho</option>
                                <option value="correcao">Correção</option>
                                <option value="material">Material Didático</option>
                                <option value="video">Vídeo</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="disciplina" class="block text-sm font-semibold text-gray-700 mb-2">Disciplina *</label>
                            <input type="text" id="disciplina" name="disciplina" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: Matemática">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                            <select id="turma_id" name="turma_id"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                <option value="">Todas as turmas</option>
                                <?php foreach ($turmas as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" data-serie="<?php echo $t['serie']; ?>">
                                        <?php echo $t['nome']; ?> - <?php echo $t['serie']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                            <select id="serie" name="serie"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                <option value="">Todas as séries</option>
                                <?php
                                $series = array_unique(array_column($turmas, 'serie'));
                                foreach ($series as $s): ?>
                                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="visibilidade" class="block text-sm font-semibold text-gray-700 mb-2">Visibilidade</label>
                        <select id="visibilidade" name="visibilidade"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="turma">Apenas a turma selecionada</option>
                            <option value="serie">Todos da série selecionada</option>
                            <option value="publico">Público (todos os alunos)</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label for="arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Arquivo *</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-azul-principal transition-colors">
                            <input type="file" id="arquivo" name="arquivo" required
                                class="hidden" onchange="updateFileName(this)">
                            <label for="arquivo" class="cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600">Clique para selecionar ou arraste o arquivo aqui</p>
                                <p class="text-sm text-gray-400 mt-2">Máximo 10MB (PDF, DOC, XLS, PPT, JPG, PNG, MP4)</p>
                            </label>
                            <p id="file-name" class="mt-4 text-sm font-semibold text-azul-principal hidden"></p>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-purple-600 text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                        <i class="fas fa-upload mr-2"></i>
                        Enviar Arquivo
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function updateFileName(input) {
            const fileName = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileName.textContent = input.files[0].name;
                fileName.classList.remove('hidden');
            }
        }

        // Atualizar série quando turma é selecionada
        document.getElementById('turma_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const serie = selectedOption.getAttribute('data-serie');
            if (serie) {
                document.getElementById('serie').value = serie;
            }
        });

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
