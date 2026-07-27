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

// Diretório de backup
$backup_dir = '../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Criar backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Obter todas as tabelas
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $sql = '';
        foreach ($tables as $table) {
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $result = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_array();
            $sql .= $row[1] . ";\n\n";
            
            $result = $conn->query("SELECT * FROM `$table`");
            $columns = $result->fetch_fields();
            $num_columns = count($columns);
            
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $sql .= "INSERT INTO `$table` VALUES (";
                for ($i = 0; $i < $num_columns; $i++) {
                    $row[$i] = addslashes($row[$i]);
                    $row[$i] = preg_replace("/\n/", "\\n", $row[$i]);
                    if (isset($row[$i])) {
                        $sql .= '"' . $row[$i] . '"';
                    } else {
                        $sql .= '""';
                    }
                    if ($i < $num_columns - 1) {
                        $sql .= ',';
                    }
                }
                $sql .= ");\n";
            }
            $sql .= "\n\n";
        }
        
        file_put_contents($backup_file, $sql);
        $success = 'Backup criado com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao criar backup: ' . $e->getMessage();
    }
}

// Restaurar backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore' && isset($_FILES['backup_file'])) {
    try {
        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Erro ao fazer upload do arquivo.';
        } else {
            $sql = file_get_contents($file['tmp_name']);
            
            // Dividir em queries individuais
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!$conn->query($query)) {
                        throw new Exception("Erro ao executar query: " . $conn->error);
                    }
                }
            }
            
            $success = 'Backup restaurado com sucesso!';
        }
    } catch (Exception $e) {
        $error = 'Erro ao restaurar backup: ' . $e->getMessage();
    }
}

// Excluir backup
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $file_path = $backup_dir . $file;
    
    if (file_exists($file_path) && strpos($file, 'backup_') === 0 && strpos($file, '.sql') !== false) {
        if (unlink($file_path)) {
            $success = 'Backup excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir backup.';
        }
    } else {
        $error = 'Arquivo inválido.';
    }
}

// Obter lista de backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (strpos($file, 'backup_') === 0 && strpos($file, '.sql') !== false) {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup e Restore | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Backup e Restore</h1>
                <p class="text-gray-600 mt-2">Gerenciar backups do banco de dados</p>
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

        <!-- Ações -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Criar Backup -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-download text-azul-principal text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Criar Backup</h3>
                        <p class="text-sm text-gray-500">Gerar backup completo do banco de dados</p>
                    </div>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="backup">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                        <i class="fas fa-download mr-2"></i>Criar Backup
                    </button>
                </form>
            </div>
            
            <!-- Restaurar Backup -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-upload text-orange-600 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Restaurar Backup</h3>
                        <p class="text-sm text-gray-500">Restaurar banco de dados de arquivo SQL</p>
                    </div>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="restore">
                    <input type="file" name="backup_file" accept=".sql" required class="w-full mb-3 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all">
                        <i class="fas fa-upload mr-2"></i>Restaurar Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de Backups -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Backups Existentes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-4">Nome do Arquivo</th>
                            <th class="px-6 py-4">Tamanho</th>
                            <th class="px-6 py-4">Data de Criação</th>
                            <th class="px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-database text-4xl mb-2"></i>
                                    <p>Nenhum backup encontrado</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-file-code text-azul-principal"></i>
                                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($backup['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', $backup['date']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="../backups/<?php echo $backup['name']; ?>" download class="px-3 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="?action=delete&file=<?php echo $backup['name']; ?>" class="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este backup?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
