<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Processar formulário de upload/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $titulo = trim($_POST['titulo'] ?? '');
        $autor = trim($_POST['autor'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $subcategoria_id = intval($_POST['subcategoria_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $livro_id = intval($_POST['livro_id'] ?? 0);
        
        // Obter nomes da categoria e subcategoria
        $cat_query = $conn->prepare("SELECT nome FROM biblioteca_categorias WHERE id = ?");
        $cat_query->bind_param("i", $categoria_id);
        $cat_query->execute();
        $cat_result = $cat_query->get_result();
        $categoria_nome = $cat_result->fetch_assoc()['nome'] ?? '';
        
        $subcat_query = $conn->prepare("SELECT nome FROM biblioteca_subcategorias WHERE id = ?");
        $subcat_query->bind_param("i", $subcategoria_id);
        $subcat_query->execute();
        $subcat_result = $subcat_query->get_result();
        $subcategoria_nome = $subcat_result->fetch_assoc()['nome'] ?? '';
        
        // Upload do PDF
        $arquivo_pdf = '';
        if (isset($_FILES['arquivo_pdf']) && $_FILES['arquivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/biblioteca/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['arquivo_pdf']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('livro_') . '.' . $ext;
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['arquivo_pdf']['tmp_name'], $target)) {
                $arquivo_pdf = 'uploads/biblioteca/' . $filename;
            }
        }
        
        // Upload da capa
        $capa_imagem = '';
        if (isset($_FILES['capa_imagem']) && $_FILES['capa_imagem']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/biblioteca/capas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['capa_imagem']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('capa_') . '.' . $ext;
            $target = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['capa_imagem']['tmp_name'], $target)) {
                $capa_imagem = 'uploads/biblioteca/capas/' . $filename;
            }
        }
        
        if ($action === 'add') {
            // Inserir novo livro
            $stmt = $conn->prepare("INSERT INTO biblioteca_livros (titulo, autor, categoria, subcategoria, arquivo_pdf, capa_imagem, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $titulo, $autor, $categoria_nome, $subcategoria_nome, $arquivo_pdf, $capa_imagem, $descricao);
            $stmt->execute();
            $success = "Livro adicionado com sucesso!";
        } else {
            // Atualizar livro existente
            if ($arquivo_pdf) {
                $stmt = $conn->prepare("UPDATE biblioteca_livros SET titulo = ?, autor = ?, categoria = ?, subcategoria = ?, arquivo_pdf = ?, capa_imagem = COALESCE(?, capa_imagem), descricao = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $titulo, $autor, $categoria_nome, $subcategoria_nome, $arquivo_pdf, $capa_imagem, $descricao, $livro_id);
            } else {
                $stmt = $conn->prepare("UPDATE biblioteca_livros SET titulo = ?, autor = ?, categoria = ?, subcategoria = ?, capa_imagem = COALESCE(?, capa_imagem), descricao = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $titulo, $autor, $categoria_nome, $subcategoria_nome, $capa_imagem, $descricao, $livro_id);
            }
            $stmt->execute();
            $success = "Livro atualizado com sucesso!";
        }
    } elseif ($action === 'delete') {
        $livro_id = intval($_POST['livro_id'] ?? 0);
        
        // Obter informações do livro para deletar arquivos
        $stmt = $conn->prepare("SELECT arquivo_pdf, capa_imagem FROM biblioteca_livros WHERE id = ?");
        $stmt->bind_param("i", $livro_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $livro = $result->fetch_assoc();
        
        if ($livro) {
            // Deletar arquivos
            if ($livro['arquivo_pdf'] && file_exists('../' . $livro['arquivo_pdf'])) {
                unlink('../' . $livro['arquivo_pdf']);
            }
            if ($livro['capa_imagem'] && file_exists('../' . $livro['capa_imagem'])) {
                unlink('../' . $livro['capa_imagem']);
            }
            
            // Deletar registro
            $stmt = $conn->prepare("DELETE FROM biblioteca_livros WHERE id = ?");
            $stmt->bind_param("i", $livro_id);
            $stmt->execute();
            $success = "Livro deletado com sucesso!";
        }
    } elseif ($action === 'toggle_status') {
        $livro_id = intval($_POST['livro_id'] ?? 0);
        $ativo = intval($_POST['ativo'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE biblioteca_livros SET ativo = ? WHERE id = ?");
        $stmt->bind_param("ii", $ativo, $livro_id);
        $stmt->execute();
        $success = "Status do livro atualizado!";
    }
}

// Buscar categorias e subcategorias
$categorias = $conn->query("SELECT * FROM biblioteca_categorias WHERE ativo = 1 ORDER BY ordem");
$subcategorias = $conn->query("SELECT * FROM biblioteca_subcategorias WHERE ativo = 1 ORDER BY categoria_id, ordem");

// Buscar livros com paginação
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria_filter = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (titulo LIKE ? OR autor LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($categoria_filter > 0) {
    $cat_query = $conn->prepare("SELECT nome FROM biblioteca_categorias WHERE id = ?");
    $cat_query->bind_param("i", $categoria_filter);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    $categoria_nome = $cat_result->fetch_assoc()['nome'] ?? '';
    
    $where .= " AND categoria = ?";
    $params[] = $categoria_nome;
    $types .= "s";
}

$count_query = "SELECT COUNT(*) as total FROM biblioteca_livros $where";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_result = $stmt->get_result();
} else {
    $total_result = $conn->query($count_query);
}
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

$query = "SELECT * FROM biblioteca_livros $where ORDER BY data_upload DESC LIMIT $per_page OFFSET $offset";
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $livros = $stmt->get_result();
} else {
    $livros = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Biblioteca - Painel Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif']
                    },
                    colors: {
                        azul: {
                            principal: '#0a2463',
                            claro: '#1e4d8c',
                            escuro: '#051635'
                        },
                        verde: {
                            complementar: '#13843b',
                            claro: '#15a048'
                        },
                        amarelo: {
                            destaque: '#ffd700',
                            claro: '#ffed4a'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 text-white hover:text-white/80 transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                        <span class="font-semibold">Voltar</span>
                    </a>
                    <div class="h-8 w-px bg-white/20"></div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-book text-white text-2xl"></i>
                        <h1 class="text-white font-bold text-xl sm:text-2xl">Gerenciar Biblioteca</h1>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <button onclick="openModal()" class="px-4 py-2 bg-gradient-to-r from-verde-complementar to-verde-claro text-white rounded-lg font-semibold hover:shadow-lg transition-all text-sm">
                        <i class="fas fa-plus mr-2"></i>Novo Livro
                    </button>
                    <div class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-xl">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div class="hidden sm:block text-left">
                            <span class="text-white text-xs font-medium block"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 15)); ?></span>
                            <span class="text-white/70 text-xs">Admin</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-4 sm:p-6 lg:p-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Buscar por título ou autor..." 
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="sm:w-64">
                    <select name="categoria" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="0">Todas as categorias</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="px-6 py-3 bg-azul-principal text-white rounded-xl font-semibold hover:bg-azul-claro transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
            </div>
        </div>

        <!-- Tabela de Livros -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs sm:text-sm text-gray-500">
                            <th class="pb-3 sm:pb-4 font-semibold px-4">Capa</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4">Título</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4 hidden sm:table-cell">Autor</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4 hidden md:table-cell">Categoria</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4 hidden md:table-cell">Subcategoria</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4 hidden lg:table-cell">Data Upload</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4">Status</th>
                            <th class="pb-3 sm:pb-4 font-semibold px-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($livros->num_rows > 0): ?>
                            <?php while ($livro = $livros->fetch_assoc()): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <?php if ($livro['capa_imagem']): ?>
                                            <img src="../<?php echo $livro['capa_imagem']; ?>" alt="Capa" class="w-12 h-16 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-12 h-16 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-book text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($livro['titulo']); ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-600 hidden sm:table-cell text-sm"><?php echo htmlspecialchars($livro['autor']); ?></td>
                                    <td class="py-4 px-4 text-gray-600 hidden md:table-cell text-sm"><?php echo htmlspecialchars($livro['categoria']); ?></td>
                                    <td class="py-4 px-4 text-gray-600 hidden md:table-cell text-sm"><?php echo htmlspecialchars($livro['subcategoria']); ?></td>
                                    <td class="py-4 px-4 text-gray-600 text-sm hidden lg:table-cell">
                                        <?php echo date('d/m/Y H:i', strtotime($livro['data_upload'])); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="livro_id" value="<?php echo $livro['id']; ?>">
                                            <input type="hidden" name="ativo" value="<?php echo $livro['ativo'] ? 0 : 1; ?>">
                                            <button type="submit" class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $livro['ativo'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                                <?php echo $livro['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-1 sm:gap-2">
                                            <button onclick="editLivro(<?php echo $livro['id']; ?>, '<?php echo htmlspecialchars($livro['titulo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($livro['autor'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($livro['descricao'], ENT_QUOTES); ?>')" 
                                                class="p-1.5 sm:p-2 text-azul-principal hover:bg-azul-principal/10 rounded-lg transition-colors" title="Editar">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <a href="../<?php echo $livro['arquivo_pdf']; ?>" target="_blank" 
                                                class="p-1.5 sm:p-2 text-verde-complementar hover:bg-verde-complementar/10 rounded-lg transition-colors" title="Visualizar PDF">
                                                <i class="fas fa-file-pdf text-sm"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja deletar este livro?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="livro_id" value="<?php echo $livro['id']; ?>">
                                                <button type="submit" class="p-1.5 sm:p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Deletar">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-gray-500">
                                    <i class="fas fa-book text-4xl mb-2"></i>
                                    <p>Nenhum livro encontrado</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
                <div class="px-4 py-4 border-t border-gray-200 flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        Mostrando <?php echo ($offset + 1); ?> a <?php echo min($offset + $per_page, $total); ?> de <?php echo $total; ?> livros
                    </span>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria_filter; ?>" 
                                class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-2 bg-azul-principal text-white rounded-lg"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria_filter; ?>" 
                                    class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&categoria=<?php echo $categoria_filter; ?>" 
                                class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Adicionar/Editar Livro -->
    <div id="livroModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-azul-principal" id="modalTitle">Adicionar Novo Livro</h2>
                        <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-times text-gray-500"></i>
                        </button>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add" id="formAction">
                    <input type="hidden" name="livro_id" value="" id="livroId">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Título do Livro *</label>
                        <input type="text" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Digite o título do livro" id="tituloInput">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Autor *</label>
                        <input type="text" name="autor" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome do autor" id="autorInput">
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Categoria *</label>
                            <select name="categoria_id" required id="categoriaSelect"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                onchange="loadSubcategorias(this.value)">
                                <option value="">Selecione uma categoria</option>
                                <?php 
                                $categorias->data_seek(0);
                                while ($cat = $categorias->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-icon="<?php echo $cat['icone']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Subcategoria *</label>
                            <select name="subcategoria_id" required id="subcategoriaSelect"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                                <option value="">Selecione uma categoria primeiro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do livro (opcional)" id="descricaoInput"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Arquivo PDF *</label>
                        <input type="file" name="arquivo_pdf" accept=".pdf" required id="pdfInput"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Imagem da Capa</label>
                        <input type="file" name="capa_imagem" accept="image/*"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeModal()" 
                            class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                            <i class="fas fa-save mr-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const subcategoriasData = <?php 
            $subcategorias->data_seek(0);
            $data = [];
            while ($sub = $subcategorias->fetch_assoc()) {
                $data[] = $sub;
            }
            echo json_encode($data);
        ?>;

        function openModal() {
            document.getElementById('livroModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Adicionar Novo Livro';
            document.getElementById('formAction').value = 'add';
            document.getElementById('livroId').value = '';
            document.getElementById('pdfInput').required = true;
        }

        function closeModal() {
            document.getElementById('livroModal').classList.add('hidden');
            document.querySelector('form').reset();
        }

        function editLivro(id, titulo, autor, descricao) {
            document.getElementById('livroModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Editar Livro';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('livroId').value = id;
            document.getElementById('tituloInput').value = titulo;
            document.getElementById('autorInput').value = autor;
            document.getElementById('descricaoInput').value = descricao;
            document.getElementById('pdfInput').required = false;
        }

        function loadSubcategorias(categoriaId) {
            const select = document.getElementById('subcategoriaSelect');
            select.innerHTML = '<option value="">Selecione uma subcategoria</option>';
            
            if (!categoriaId) return;
            
            const filtered = subcategoriasData.filter(sub => sub.categoria_id == categoriaId);
            filtered.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.nome;
                select.appendChild(option);
            });
        }
    </script>
</body>
</html>
