<?php
require_once '../config.php';

requireAdmin();

$admin_id = $_SESSION['usuario_id'];

$success = '';
$error = '';

// Obter arquivos
$arquivos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM arquivos ORDER BY data_upload DESC");
    $arquivos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter arquivos: " . $e->getMessage());
}

// Upload de arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_arquivo') {
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $nome_original = $_FILES['arquivo']['name'];
        $tipo = $_FILES['arquivo']['type'];
        $tamanho = $_FILES['arquivo']['size'];
        $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
        
        // Gerar nome único
        $nome_unico = uniqid() . '.' . $extensao;
        $caminho = '../uploads/' . $nome_unico;
        
        // Mover arquivo
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO arquivos (nome_original, nome_unico, tipo, tamanho, caminho, usuario_id, data_upload) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$nome_original, $nome_unico, $tipo, $tamanho, $caminho, $admin_id]);
                
                $success = 'Arquivo enviado com sucesso!';
            } catch (PDOException $e) {
                error_log("Erro ao salvar arquivo no banco: " . $e->getMessage());
                $error = 'Erro ao salvar arquivo no banco de dados.';
            }
        } else {
            $error = 'Erro ao mover o arquivo.';
        }
    } else {
        $error = 'Erro no upload do arquivo.';
    }
}

// Excluir arquivo
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT caminho FROM arquivos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        $arquivo = $stmt->fetch();
        
        if ($arquivo && file_exists($arquivo['caminho'])) {
            unlink($arquivo['caminho']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM arquivos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Arquivo excluído com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir arquivo: " . $e->getMessage());
        $error = 'Erro ao excluir arquivo.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gerenciar Arquivos</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-upload mr-2"></i>Upload Arquivo
        </button>
    </div>
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

<!-- Lista de Arquivos -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Arquivos do Sistema</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamanho</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($arquivos as $arquivo): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($arquivo['nome_original']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($arquivo['tipo']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo number_format($arquivo['tamanho'] / 1024, 2); ?> KB</td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($arquivo['data_upload'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="<?php echo htmlspecialchars($arquivo['caminho']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-download"></i>
                            </a>
                            <a href="?action=excluir&id=<?php echo $arquivo['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este arquivo?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($arquivos)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-folder-open text-4xl mb-4"></i>
            <p>Nenhum arquivo encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Upload -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Upload de Arquivo</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" value="upload_arquivo">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo *</label>
                    <input type="file" name="arquivo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-upload mr-2"></i>Enviar Arquivo
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('modal');
        modal.classList.toggle('hidden');
    }
</script>
