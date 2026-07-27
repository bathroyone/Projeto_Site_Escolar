<?php
require_once '../config.php';

requireAdmin();

$error = '';
$success = '';

// Obter todos os arquivos
$arquivos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.nome_completo as professor_nome, t.nome as turma_nome 
        FROM arquivos a 
        JOIN usuarios u ON a.professor_id = u.id 
        LEFT JOIN turmas t ON a.turma_id = t.id 
        ORDER BY a.data_upload DESC
    ");
    $arquivos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter arquivos: " . $e->getMessage());
}

// Excluir arquivo
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        
        // Obter caminho do arquivo antes de excluir
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM arquivos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $arquivo = $stmt->fetch();
        
        if ($arquivo) {
            // Excluir arquivo do sistema
            $caminho_completo = UPLOAD_DIR . $arquivo['caminho_arquivo'];
            if (file_exists($caminho_completo)) {
                unlink($caminho_completo);
            }
            
            // Excluir do banco
            $stmt = $pdo->prepare("DELETE FROM arquivos WHERE id = ?");
            $stmt->execute([intval($_GET['id'])]);
        }
        
        header('Location: arquivos.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir arquivo: " . $e->getMessage());
    }
}

// Toggle status do arquivo
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE arquivos SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: arquivos.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Arquivos | Portal CEAA</title>
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
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">NOME DA ESCOLA</span>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gerenciar Arquivos</h1>
                <p class="text-gray-600 mt-2">Visualizar e gerenciar todos os arquivos do sistema</p>
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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-4">Título</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Disciplina</th>
                            <th class="px-6 py-4">Turma/Série</th>
                            <th class="px-6 py-4">Professor</th>
                            <th class="px-6 py-4">Data Upload</th>
                            <th class="px-6 py-4">Visibilidade</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivos as $arquivo): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-file text-azul-principal"></i>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-800 block"><?php echo htmlspecialchars($arquivo['titulo']); ?></span>
                                            <?php if ($arquivo['descricao']): ?>
                                                <span class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($arquivo['descricao'], 0, 50)) . '...'; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $arquivo['tipo_arquivo'] === 'trabalho' ? 'bg-orange-100 text-orange-600' : ($arquivo['tipo_arquivo'] === 'correcao' ? 'bg-red-100 text-red-600' : ($arquivo['tipo_arquivo'] === 'material' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600')); ?>">
                                        <?php echo ucfirst($arquivo['tipo_arquivo']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($arquivo['disciplina'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php echo htmlspecialchars($arquivo['turma_nome'] ?? '-'); ?> / <?php echo htmlspecialchars($arquivo['serie'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($arquivo['professor_nome']); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($arquivo['data_upload'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $arquivo['visibilidade'] === 'publico' ? 'bg-green-100 text-green-600' : ($arquivo['visibilidade'] === 'turma' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'); ?>">
                                        <?php echo ucfirst($arquivo['visibilidade']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $arquivo['ativo'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                        <?php echo $arquivo['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="../uploads/<?php echo htmlspecialchars($arquivo['caminho_arquivo']); ?>" target="_blank" class="p-2 rounded-lg hover:bg-blue-100 text-azul-principal" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=toggle_status&id=<?php echo $arquivo['id']; ?>" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600" title="<?php echo $arquivo['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                            <i class="fas fa-<?php echo $arquivo['ativo'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <a href="?action=excluir&id=<?php echo $arquivo['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este arquivo?');">
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
