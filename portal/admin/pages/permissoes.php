<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Obter usuários e permissões
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo, tipo_usuario FROM usuarios ORDER BY nome_completo");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Atualizar permissão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_permissao') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    
    if (empty($usuario_id) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE usuarios SET tipo_usuario = ? WHERE id = ?");
            $stmt->execute([$tipo_usuario, $usuario_id]);
            
            $success = 'Permissão atualizada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar permissão: " . $e->getMessage());
            $error = 'Erro ao atualizar permissão.';
        }
    }
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Gestão de Permissões</h2>
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

<!-- Lista de Usuários e Permissões -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Usuários e Permissões</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Atual</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($usuario['nome_completo']); ?></td>
                        <td class="px-6 py-4">
                            <?php 
                            $tipo_cor = match($usuario['tipo_usuario']) {
                                'admin' => 'bg-red-100 text-red-600',
                                'professor' => 'bg-blue-100 text-blue-600',
                                'secretaria' => 'bg-green-100 text-green-600',
                                'aluno' => 'bg-yellow-100 text-yellow-600',
                                default => 'bg-gray-100 text-gray-600'
                            };
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?>">
                                <?php echo ucfirst($usuario['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="toggleModal(<?php echo $usuario['id']; ?>, '<?php echo $usuario['tipo_usuario']; ?>')" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Editar Permissão -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Editar Permissão</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="atualizar_permissao">
                <input type="hidden" name="usuario_id" id="usuario_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                    <select name="tipo_usuario" id="tipo_usuario" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="admin">Administrador</option>
                        <option value="professor">Professor</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="aluno">Aluno</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Permissão
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(usuarioId = null, tipoUsuario = null) {
        const modal = document.getElementById('modal');
        modal.classList.toggle('hidden');
        
        if (usuarioId && tipoUsuario) {
            document.getElementById('usuario_id').value = usuarioId;
            document.getElementById('tipo_usuario').value = tipoUsuario;
        }
    }
</script>
