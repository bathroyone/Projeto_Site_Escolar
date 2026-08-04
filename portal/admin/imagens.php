<?php
require_once '../config.php';

requireAdmin();

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Processar upload de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_imagem') {
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    $subcategoria = sanitizeInput($_POST['subcategoria'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK && !empty($categoria)) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['imagem']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Definir diretório baseado na categoria
            $upload_dir = '../img/';
            if ($categoria === 'banner_matricula') {
                $upload_dir = '../img/carousel/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            } elseif ($categoria === 'carousel') {
                $upload_dir = '../img/carousel/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            } elseif ($categoria === 'projetos') {
                $upload_dir = '../img/projetos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            } elseif ($categoria === 'fundo') {
                $upload_dir = '../img/fundo/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            } elseif ($categoria === 'sobre') {
                $upload_dir = '../img/sobre/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
            }
            
            $file_extension = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $caminho_completo = str_replace('../', '', $upload_dir) . $new_filename;
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $new_filename)) {
                // Inserir no banco de dados
                $stmt = $conn->prepare("INSERT INTO site_imagens (categoria, subcategoria, nome_arquivo, caminho_completo, descricao) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $categoria, $subcategoria, $new_filename, $caminho_completo, $descricao);
                $stmt->execute();
                $success = "Imagem adicionada com sucesso!";
            } else {
                $error = "Erro ao fazer upload da imagem.";
            }
        } else {
            $error = "Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.";
        }
    } else {
        $error = "Erro no upload do arquivo ou categoria não selecionada.";
    }
}

// Processar exclusão de imagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_imagem') {
    $imagem_id = intval($_POST['imagem_id'] ?? 0);
    
    if ($imagem_id > 0) {
        // Obter informações da imagem
        $stmt = $conn->prepare("SELECT caminho_completo FROM site_imagens WHERE id = ?");
        $stmt->bind_param("i", $imagem_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $imagem = $result->fetch_assoc();
        
        if ($imagem) {
            // Deletar arquivo físico
            if (file_exists('../' . $imagem['caminho_completo'])) {
                unlink('../' . $imagem['caminho_completo']);
            }
            
            // Deletar registro
            $stmt = $conn->prepare("DELETE FROM site_imagens WHERE id = ?");
            $stmt->bind_param("i", $imagem_id);
            $stmt->execute();
            $success = "Imagem deletada com sucesso!";
        }
    }
}

// Processar upload de logo (mantido para compatibilidade)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_logo') {
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['logo']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../img/';
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo.' . $file_extension;
            
            // Backup do logo atual
            if (file_exists($upload_dir . 'logo.jpg')) {
                copy($upload_dir . 'logo.jpg', $upload_dir . 'logo_backup.jpg');
            }
            if (file_exists($upload_dir . 'logo.png')) {
                copy($upload_dir . 'logo.png', $upload_dir . 'logo_backup.png');
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_filename)) {
                // Atualizar no banco de dados
                $stmt = $conn->prepare("UPDATE site_imagens SET nome_arquivo = ?, caminho_completo = ? WHERE categoria = 'logo'");
                $caminho_completo = 'img/' . $new_filename;
                $stmt->bind_param("ss", $new_filename, $caminho_completo);
                $stmt->execute();
                $success = "Logo atualizado com sucesso!";
            } else {
                $error = "Erro ao fazer upload do logo.";
            }
        } else {
            $error = "Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.";
        }
    } else {
        $error = "Erro no upload do arquivo.";
    }
}

// Obter imagens por categoria
$categoria_filter = isset($_GET['categoria']) ? sanitizeInput($_GET['categoria']) : '';

$query = "SELECT * FROM site_imagens WHERE 1=1";
$params = [];
$types = "";

if ($categoria_filter) {
    $query .= " AND categoria = ?";
    $params[] = $categoria_filter;
    $types .= "s";
}

$query .= " ORDER BY categoria, subcategoria, ordem";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $imagens = $stmt->get_result();
} else {
    $imagens = $conn->query($query);
}

// Obter logo atual
$logo_files = glob('../img/logo.{jpg,png,gif,webp}', GLOB_BRACE);
$current_logo = !empty($logo_files) ? basename($logo_files[0]) : null;

// Obter álbuns existentes (mantido para compatibilidade)
$albuns = [];
$album_dirs = glob('../album/img/*', GLOB_ONLYDIR);
foreach ($album_dirs as $dir) {
    $album_name = basename($dir);
    $photos = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    $albuns[] = [
        'id' => $album_name,
        'name' => $album_name,
        'photo_count' => count($photos),
        'photos' => array_slice($photos, 0, 4)
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Imagens | Portal CEAA</title>
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
                        <i class="fas fa-arrow-left text-azul-principal"></i>
                        <span class="text-gray-600">Voltar</span>
                    </a>
                    <div class="h-6 w-px bg-gray-200"></div>
                    <h1 class="text-xl font-display font-bold text-azul-principal">Gestão de Imagens</h1>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
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
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros por Categoria -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-wrap items-center gap-4">
                <span class="font-semibold text-gray-700">Filtrar por categoria:</span>
                <div class="flex flex-wrap gap-2">
                    <a href="imagens.php" class="px-4 py-2 rounded-full text-sm font-medium <?php echo empty($categoria_filter) ? 'bg-azul-principal text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Todas
                    </a>
                    <a href="imagens.php?categoria=carousel" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $categoria_filter === 'carousel' ? 'bg-azul-principal text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Carousel
                    </a>
                    <a href="imagens.php?categoria=projetos" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $categoria_filter === 'projetos' ? 'bg-azul-principal text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Projetos
                    </a>
                    <a href="imagens.php?categoria=banner_matricula" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $categoria_filter === 'banner_matricula' ? 'bg-azul-principal text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'; ?>">
                        <i class="fas fa-images mr-1"></i> Banner Matrículas
                    </a>
                    <a href="imagens.php?categoria=fundo" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $categoria_filter === 'fundo' ? 'bg-azul-principal text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Fundo
                    </a>
                    <a href="imagens.php?categoria=sobre" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $categoria_filter === 'sobre' ? 'bg-azul-principal text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Sobre
                    </a>
                </div>
            </div>
        </div>

        <!-- Logo do Site -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">
                <i class="fas fa-image mr-2"></i>Logo do Site
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center">
                        <?php if ($current_logo): ?>
                            <img src="../img/<?php echo $current_logo; ?>" alt="Logo Atual" class="h-32 mx-auto mb-4 object-contain">
                        <?php else: ?>
                            <i class="fas fa-image text-gray-300 text-5xl mb-4"></i>
                        <?php endif; ?>
                        <p class="text-gray-500 text-sm">Logo atual</p>
                    </div>
                </div>
                
                <div>
                    <form method="POST" enctype="multipart/form-data" class="h-full">
                        <input type="hidden" name="action" value="upload_logo">
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 h-full flex flex-col justify-center">
                            <label class="cursor-pointer">
                                <div class="text-center">
                                    <i class="fas fa-cloud-upload-alt text-azul-principal text-4xl mb-4"></i>
                                    <p class="text-gray-600 mb-2">Clique para selecionar nova logo</p>
                                    <p class="text-gray-400 text-xs">JPG, PNG, GIF ou WebP (máx 5MB)</p>
                                </div>
                                <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            </label>
                            <button type="submit" class="mt-4 w-full bg-azul-principal text-white py-2 rounded-lg hover:bg-azul-escuro transition-colors">
                                <i class="fas fa-upload mr-2"></i>Atualizar Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Upload de Imagens por Categoria -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-azul-principal">
                    <i class="fas fa-upload mr-2"></i>Upload de Imagens
                </h2>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload_imagem">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Categoria *</label>
                        <select name="categoria" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Selecione uma categoria</option>
                            <option value="banner_matricula">Banner - Matrículas (Hero do Site)</option>
                            <option value="carousel">Carousel (Slides)</option>
                            <option value="projetos">Projetos</option>
                            <option value="fundo">Fundo (Background)</option>
                            <option value="sobre">Seção Sobre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Subcategoria</label>
                        <input type="text" name="subcategoria" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent" placeholder="Ex: slide1, projeto1, etc">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent" placeholder="Descrição da imagem (opcional)"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Imagem *</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center">
                        <label class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-azul-principal text-3xl mb-2"></i>
                            <p class="text-gray-600 text-sm">Clique para selecionar a imagem</p>
                            <p class="text-gray-400 text-xs">JPG, PNG, GIF ou WebP (máx 5MB)</p>
                            <input type="file" name="imagem" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" required>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-verde-complementar text-white py-3 rounded-xl hover:bg-verde-claro transition-colors">
                    <i class="fas fa-upload mr-2"></i>Fazer Upload
                </button>
            </form>
        </div>

        <!-- Lista de Imagens -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-6">
                <i class="fas fa-images mr-2"></i>Imagens Cadastradas
            </h2>
            
            <?php if ($imagens->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($imagem = $imagens->fetch_assoc()): ?>
                        <div class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="w-20 h-20 flex-shrink-0">
                                <img src="../<?php echo $imagem['caminho_completo']; ?>" alt="<?php echo htmlspecialchars($imagem['descricao'] ?? $imagem['nome_arquivo']); ?>" class="w-full h-full object-cover rounded-lg">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($imagem['nome_arquivo']); ?></p>
                                <p class="text-sm text-gray-500">
                                    <span class="inline-block px-2 py-1 bg-azul-principal/10 text-azul-principal rounded text-xs mr-2"><?php echo htmlspecialchars($imagem['categoria']); ?></span>
                                    <?php if ($imagem['subcategoria']): ?>
                                        <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"><?php echo htmlspecialchars($imagem['subcategoria']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($imagem['descricao']): ?>
                                    <p class="text-sm text-gray-400 truncate"><?php echo htmlspecialchars($imagem['descricao']); ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja deletar esta imagem?');">
                                <input type="hidden" name="action" value="delete_imagem">
                                <input type="hidden" name="imagem_id" value="<?php echo $imagem['id']; ?>">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-images text-4xl mb-4"></i>
                    <p>Nenhuma imagem cadastrada nesta categoria.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Álbuns de Fotos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-azul-principal">
                    <i class="fas fa-images mr-2"></i>Álbuns de Fotos
                </h2>
                <button onclick="document.getElementById('create-album-modal').classList.remove('hidden')" class="bg-verde-complementar text-white px-4 py-2 rounded-lg hover:bg-verde-claro transition-colors">
                    <i class="fas fa-plus mr-2"></i>Novo Álbum
                </button>
            </div>
            
            <?php if (count($albuns) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($albuns as $album): ?>
                        <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                            <div class="h-40 bg-gray-100 relative">
                                <?php if (!empty($album['photos'])): ?>
                                    <div class="grid grid-cols-2 grid-rows-2 h-full">
                                        <?php foreach ($album['photos'] as $photo): ?>
                                            <img src="<?php echo $photo; ?>" alt="Foto do álbum" class="w-full h-full object-cover">
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="h-full flex items-center justify-center">
                                        <i class="fas fa-images text-gray-300 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($album['name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo $album['photo_count']; ?> foto(s)</p>
                                <div class="flex gap-2 mt-3">
                                    <button onclick="openUploadModal('<?php echo $album['id']; ?>')" class="flex-1 bg-azul-principal text-white py-2 rounded-lg text-sm hover:bg-azul-escuro transition-colors">
                                        <i class="fas fa-plus mr-1"></i>Adicionar
                                    </button>
                                    <button onclick="deleteAlbum('<?php echo $album['id']; ?>')" class="bg-red-500 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-600 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-images text-4xl mb-4"></i>
                    <p>Nenhum álbum criado ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Upload de Foto -->
    <div id="upload-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeUploadModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-display font-bold text-azul-principal">Adicionar Foto ao Álbum</h3>
                        <button onclick="closeUploadModal()" class="p-2 rounded-full hover:bg-gray-100">
                            <i class="fas fa-times text-gray-500"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_album">
                        <input type="hidden" name="album_id" id="upload-album-id">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Selecione a foto</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center">
                                <label class="cursor-pointer">
                                    <i class="fas fa-cloud-upload-alt text-azul-principal text-3xl mb-2"></i>
                                    <p class="text-gray-600 text-sm">Clique para selecionar</p>
                                    <input type="file" name="album_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" required>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-azul-principal text-white py-3 rounded-xl hover:bg-azul-escuro transition-colors">
                            <i class="fas fa-upload mr-2"></i>Upload Foto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Criação de Álbum -->
    <div id="create-album-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="document.getElementById('create-album-modal').classList.add('hidden')"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-display font-bold text-azul-principal">Criar Novo Álbum</h3>
                        <button onclick="document.getElementById('create-album-modal').classList.add('hidden')" class="p-2 rounded-full hover:bg-gray-100">
                            <i class="fas fa-times text-gray-500"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form method="POST" action="create_album.php">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome do Álbum</label>
                            <input type="text" name="album_name" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent" placeholder="Ex: A1, A2, Eventos, etc.">
                        </div>
                        <button type="submit" class="w-full bg-verde-complementar text-white py-3 rounded-xl hover:bg-verde-claro transition-colors">
                            <i class="fas fa-plus mr-2"></i>Criar Álbum
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        function openUploadModal(albumId) {
            document.getElementById('upload-album-id').value = albumId;
            document.getElementById('upload-modal').classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
        }

        function deleteAlbum(albumId) {
            if (confirm('Tem certeza que deseja excluir este álbum e todas as suas fotos?')) {
                window.location.href = 'delete_album.php?id=' + albumId;
            }
        }
    </script>
</body>
</html>
