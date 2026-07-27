<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
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

// Criar tabela de notícias se não existir
$conn->query("CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    imagem VARCHAR(255),
    categoria ENUM('geral', 'eventos', 'academico', 'esportes', 'cultura') DEFAULT 'geral',
    destaque TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Adicionar notícia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $conteudo = $_POST['conteudo'] ?? '';
    $categoria = sanitizeInput($_POST['categoria'] ?? 'geral');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    
    if (empty($titulo) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            // Upload de imagem
            $imagem = '';
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/noticias/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file = $_FILES['imagem'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed_exts)) {
                    $nome_arquivo = uniqid() . '_' . time() . '.' . $ext;
                    $caminho_arquivo = $upload_dir . $nome_arquivo;
                    
                    if (move_uploaded_file($file['tmp_name'], $caminho_arquivo)) {
                        $imagem = 'uploads/noticias/' . $nome_arquivo;
                    }
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO noticias (titulo, conteudo, imagem, categoria, destaque) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $conteudo, $imagem, $categoria, $destaque]);
            $success = 'Notícia publicada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao publicar notícia.';
        }
    }
}

// Excluir notícia
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: noticias.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir notícia.';
    }
}

// Alternar destaque
if (isset($_GET['action']) && $_GET['action'] === 'destaque' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("UPDATE noticias SET destaque = NOT destaque WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: noticias.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao alterar destaque.';
    }
}

// Obter notícias
$noticias = [];
try {
    $stmt = $conn->query("SELECT * FROM noticias ORDER BY data_publicacao DESC");
    $noticias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter notícias: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Notícias | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gerenciar Notícias</h1>
                <p class="text-gray-600 mt-2">Publicar notícias no site</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Notícia
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

        <!-- Lista de Notícias -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Categoria</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Publicação</th>
                            <th class="px-4 sm:px-6 py-4">Destaque</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $noticia): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($noticia['imagem']): ?>
                                            <img src="../<?php echo htmlspecialchars($noticia['imagem']); ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-newspaper text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($noticia['titulo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_categoria = match($noticia['categoria']) {
                                            'geral' => 'bg-gray-100 text-gray-600',
                                            'eventos' => 'bg-blue-100 text-blue-600',
                                            'academico' => 'bg-green-100 text-green-600',
                                            'esportes' => 'bg-orange-100 text-orange-600',
                                            'cultura' => 'bg-purple-100 text-purple-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_categoria;
                                        ?>">
                                        <?php echo ucfirst($noticia['categoria']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y H:i', strtotime($noticia['data_publicacao'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="?action=destaque&id=<?php echo $noticia['id']; ?>" class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $noticia['destaque'] ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo $noticia['destaque'] ? 'Sim' : 'Não'; ?>
                                    </a>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $noticia['ativo'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                        <?php echo $noticia['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="?action=excluir&id=<?php echo $noticia['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir esta notícia?');">
                                            <i class="fas fa-trash"></i>
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

    <!-- Modal Nova Notícia -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Notícia</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" value="adicionar">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título da notícia">
                    </div>
                    
                    <div class="mb-4">
                        <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="geral">Geral</option>
                            <option value="eventos">Eventos</option>
                            <option value="academico">Acadêmico</option>
                            <option value="esportes">Esportes</option>
                            <option value="cultura">Cultura</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="conteudo" class="block text-sm font-semibold text-gray-700 mb-2">Conteúdo *</label>
                        <textarea id="conteudo" name="conteudo" rows="6" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Conteúdo da notícia"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="imagem" class="block text-sm font-semibold text-gray-700 mb-2">Imagem</label>
                        <input type="file" id="imagem" name="imagem" accept="image/*"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="destaque" class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Marcar como destaque</span>
                        </label>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Publicar Notícia
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
