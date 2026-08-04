<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Backup do banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    try {
        $pdo = getDBConnection();
        $dbname = DB_NAME;
        
        // Criar arquivo de backup
        $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_dir = dirname($backup_file);
        
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Usar mysqldump do XAMPP
        $xampp_mysql = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (file_exists($xampp_mysql)) {
            $command = '"' . $xampp_mysql . '" --user=' . DB_USER . ' --password=' . DB_PASS . ' --host=' . DB_HOST . ' ' . $dbname . ' > "' . $backup_file . '"';
            system($command, $output);
        } else {
            // Fallback: backup via PHP
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $sql = '';
            
            foreach ($tables as $table) {
                $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $sql .= "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES\n";
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($val) {
                            return $val === null ? 'NULL' : "'" . addslashes($val) . "'";
                        }, $row);
                        $sql .= "(" . implode(',', $values) . "),\n";
                    }
                    $sql = rtrim($sql, ",\n") . ";\n\n";
                }
            }
            
            file_put_contents($backup_file, $sql);
        }
        
        if (file_exists($backup_file)) {
            $success = 'Backup realizado com sucesso!';
            
            // Recarregar lista de backups
            $backups = array_diff(scandir($backup_dir), array('.', '..'));
            rsort($backups);
        } else {
            $error = 'Erro ao realizar backup.';
        }
    } catch (Exception $e) {
        error_log("Erro ao fazer backup: " . $e->getMessage());
        $error = 'Erro ao realizar backup: ' . $e->getMessage();
    }
}

// Restore do banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
    try {
        $backup_file = $_FILES['backup_file']['tmp_name'];
        
        // Usar mysql do XAMPP
        $xampp_mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        if (file_exists($xampp_mysql)) {
            $command = '"' . $xampp_mysql . '" --user=' . DB_USER . ' --password=' . DB_PASS . ' --host=' . DB_HOST . ' ' . DB_NAME . ' < "' . $backup_file . '"';
            system($command, $output);
        } else {
            // Fallback: restore via PHP
            $sql = file_get_contents($backup_file);
            $pdo->exec($sql);
        }
        
        $success = 'Restore realizado com sucesso!';
    } catch (Exception $e) {
        error_log("Erro ao fazer restore: " . $e->getMessage());
        $error = 'Erro ao realizar restore: ' . $e->getMessage();
    }
}

// Listar backups existentes
$backups = [];
$backup_dir = '../backups/';
if (is_dir($backup_dir)) {
    $backups = array_diff(scandir($backup_dir), array('.', '..'));
    rsort($backups);
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Backup e Restore</h2>
</div>

<?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Backup -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">
            <i class="fas fa-download mr-2 text-primary-600"></i>Realizar Backup
        </h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="backup">
            <p class="text-gray-600 mb-4">Crie um backup completo do banco de dados.</p>
            <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Gerar Backup
            </button>
        </form>
    </div>
    
    <!-- Restore -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">
            <i class="fas fa-upload mr-2 text-primary-600"></i>Restaurar Backup
        </h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <p class="text-gray-600 mb-4">Selecione um arquivo de backup para restaurar.</p>
            <input type="file" name="backup_file" accept=".sql" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 mb-4">
            <button type="submit" class="w-full bg-orange-600 text-white font-medium py-2 rounded-lg hover:bg-orange-700 transition-colors">
                <i class="fas fa-upload mr-2"></i>Restaurar Backup
            </button>
        </form>
    </div>
</div>

<!-- Lista de Backups -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mt-6">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Backups Disponíveis</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arquivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamanho</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($backups as $backup): ?>
                    <?php 
                    $backup_path = '../backups/' . $backup;
                    $backup_size = filesize($backup_path);
                    $backup_date = date('d/m/Y H:i', filemtime($backup_path));
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($backup); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo number_format($backup_size / 1024, 2); ?> KB</td>
                        <td class="px-6 py-4 text-gray-600"><?php echo $backup_date; ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="<?php echo $backup_path; ?>" download class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?action=excluir&file=<?php echo $backup; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este backup?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($backups)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-database text-4xl mb-4"></i>
            <p>Nenhum backup encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Excluir backup
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['file'])) {
    $backup_path = '../backups/' . $_GET['file'];
    if (file_exists($backup_path)) {
        unlink($backup_path);
        echo '<script>window.location.href="?";</script>';
    }
}
?>
