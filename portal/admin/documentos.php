<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin ou secretaria
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'secretaria')) {
    header('Location: ../login.php');
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$success = '';
$error = '';

// Processar upload de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_documento') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo_documento = sanitizeInput($_POST['tipo_documento'] ?? '');
    $data_validade = $_POST['data_validade'] ?? null;
    
    if (empty($aluno_id) || empty($tipo_documento)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Por favor, selecione um arquivo.';
    } else {
        try {
            $upload_dir = '../uploads/documentos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['arquivo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            
            if (!in_array($ext, $allowed_exts)) {
                $error = 'Tipo de arquivo não permitido. Use PDF, JPG, PNG ou DOC.';
            } else {
                $nome_arquivo = uniqid() . '_' . time() . '.' . $ext;
                $caminho_arquivo = $upload_dir . $nome_arquivo;
                
                if (move_uploaded_file($file['tmp_name'], $caminho_arquivo)) {
                    $stmt = $conn->prepare("INSERT INTO aluno_documentos (aluno_id, tipo_documento, nome_arquivo, caminho_arquivo, data_validade) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$aluno_id, $tipo_documento, $file['name'], $caminho_arquivo, $data_validade]);
                    $success = 'Documento enviado com sucesso!';
                } else {
                    $error = 'Erro ao fazer upload do arquivo.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao salvar documento.';
        }
    }
}

// Processar atualização de status do documento
if (isset($_GET['action']) && $_GET['action'] === 'atualizar_status' && isset($_GET['id']) && isset($_GET['status'])) {
    try {
        $stmt = $conn->prepare("UPDATE aluno_documentos SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['status'], intval($_GET['id'])]);
        header('Location: documentos.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar status.';
    }
}

// Obter lista de documentos
$documentos = [];
$query_documentos = "SELECT ad.*, u.nome_completo as aluno_nome FROM aluno_documentos ad JOIN usuarios u ON ad.aluno_id = u.id ORDER BY ad.created_at DESC";
$result_documentos = $conn->query($query_documentos);
while ($row = $result_documentos->fetch_assoc()) {
    $documentos[] = $row;
}

// Obter lista de alunos
$alunos = [];
$query_alunos = "SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo";
$result_alunos = $conn->query($query_alunos);
while ($row = $result_alunos->fetch_assoc()) {
    $alunos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação de Alunos | Portal de Gestão Escolar</title>
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
                    <a href="../dashboard_secretaria.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
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
                                <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['tipo']); ?></p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Documentação de Alunos</h1>
                <p class="text-gray-600 mt-2">Gerenciar documentos dos alunos</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-upload mr-2"></i>Novo Documento
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

        <!-- Lista de Documentos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Tipo Documento</th>
                            <th class="px-4 sm:px-6 py-4">Arquivo</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Validade</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentos as $documento): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                            <?php echo strtoupper(substr($documento['aluno_nome'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm truncate max-w-[150px] sm:max-w-none"><?php echo htmlspecialchars($documento['aluno_nome']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($documento['tipo_documento']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                    <a href="../<?php echo $documento['caminho_arquivo']; ?>" target="_blank" class="text-azul-principal hover:underline">
                                        <i class="fas fa-file-alt mr-1"></i><?php echo htmlspecialchars($documento['nome_arquivo']); ?>
                                    </a>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                    <?php echo $documento['data_validade'] ? date('d/m/Y', strtotime($documento['data_validade'])) : '-'; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $status_class = match($documento['status']) {
                                            'aprovado' => 'bg-green-100 text-green-600',
                                            'reprovado' => 'bg-red-100 text-red-600',
                                            'vencido' => 'bg-orange-100 text-orange-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $status_class;
                                        ?>">
                                        <?php echo ucfirst($documento['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-1 sm:gap-2">
                                        <a href="?action=atualizar_status&id=<?php echo $documento['id']; ?>&status=aprovado" class="p-1.5 sm:p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Aprovar">
                                            <i class="fas fa-check text-sm"></i>
                                        </a>
                                        <a href="?action=atualizar_status&id=<?php echo $documento['id']; ?>&status=reprovado" class="p-1.5 sm:p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Reprovar">
                                            <i class="fas fa-times text-sm"></i>
                                        </a>
                                        <a href="../<?php echo $documento['caminho_arquivo']; ?>" target="_blank" class="p-1.5 sm:p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors" title="Visualizar">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Upload Documento -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Enviar Documento</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="upload_documento">
                    
                    <div class="mb-4">
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_documento" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Documento *</label>
                        <select id="tipo_documento" name="tipo_documento" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="certificado_nascimento">Certificado de Nascimento</option>
                            <option value="rg">RG</option>
                            <option value="cpf">CPF</option>
                            <option value="historico_escolar">Histórico Escolar</option>
                            <option value="declaracao_matricula">Declaração de Matrícula</option>
                            <option value="comprovante_residencia">Comprovante de Residência</option>
                            <option value="foto_aluno">Foto do Aluno</option>
                            <option value="carteira_vacinacao">Carteira de Vacinação</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="arquivo" class="block text-sm font-semibold text-gray-700 mb-2">Arquivo *</label>
                        <input type="file" id="arquivo" name="arquivo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <p class="text-xs text-gray-500 mt-1">Formatos aceitos: PDF, JPG, PNG, DOC (máx 10MB)</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="data_validade" class="block text-sm font-semibold text-gray-700 mb-2">Data de Validade (opcional)</label>
                        <input type="date" id="data_validade" name="data_validade"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-upload mr-2"></i>
                        Enviar Documento
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

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
