<?php
require_once '../../config.php';

requireAdmin();

$error = '';
$success = '';

// Obter todos os usuários
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Adicionar novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $trocar_senha_proximo_login = isset($_POST['trocar_senha_proximo_login']) ? 1 : 0;
    
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                $senha_hash = hashPassword($senha);
                
                $matricula = null;
                if ($tipo_usuario === 'professor' || $tipo_usuario === 'secretaria') {
                    $matricula = strtoupper(substr($tipo_usuario, 0, 3)) . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                }
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, turma, serie, matricula, trocar_senha_proximo_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash, $tipo_usuario, $turma, $serie, $matricula, $trocar_senha_proximo_login]);
                
                $success = 'Usuário adicionado com sucesso!';
                
                $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
                $usuarios = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao adicionar usuário: " . $e->getMessage());
            $error = 'Erro ao adicionar usuário.';
        }
    }
}

// Editar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    if (empty($nome) || empty($email) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado para outro usuário.';
            } else {
                $matricula = null;
                if ($tipo_usuario === 'professor' || $tipo_usuario === 'secretaria') {
                    $stmt = $pdo->prepare("SELECT matricula FROM usuarios WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario_atual = $stmt->fetch();
                    
                    if (!$usuario_atual['matricula']) {
                        $matricula = strtoupper(substr($tipo_usuario, 0, 3)) . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    }
                }
                
                if (!empty($nova_senha)) {
                    if (strlen($nova_senha) < 6) {
                        $error = 'A nova senha deve ter no mínimo 6 caracteres.';
                    } else {
                        $senha_hash = hashPassword($nova_senha);
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, senha = ?, tipo_usuario = ?, turma = ?, serie = ?, matricula = COALESCE(?, matricula) WHERE id = ?");
                        $stmt->execute([$nome, $email, $senha_hash, $tipo_usuario, $turma, $serie, $matricula, $usuario_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, tipo_usuario = ?, turma = ?, serie = ?, matricula = COALESCE(?, matricula) WHERE id = ?");
                    $stmt->execute([$nome, $email, $tipo_usuario, $turma, $serie, $matricula, $usuario_id]);
                }
                
                $success = 'Usuário atualizado com sucesso!';
                
                $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
                $usuarios = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao editar usuário: " . $e->getMessage());
            $error = 'Erro ao editar usuário.';
        }
    }
}

// Atualizar status do usuário
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: ../index.php?page=usuarios');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status: " . $e->getMessage());
    }
}

// Excluir usuário
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND id != ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: ../index.php?page=usuarios');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir usuário: " . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gerenciar Usuários</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Usuário
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

<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <input type="text" placeholder="Buscar usuário..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">Todos os tipos</option>
                <option value="admin">Admin</option>
                <option value="professor">Professor</option>
                <option value="aluno">Aluno</option>
                <option value="secretaria">Secretaria</option>
            </select>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email/Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Criação</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($usuarios as $usuario): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm mr-3">
                                    <?php echo strtoupper(substr($usuario['nome_completo'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($usuario['nome_completo']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($usuario['matricula'] ?? $usuario['cpf'] ?? ''); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $usuario['tipo_usuario'] === 'admin' ? 'bg-purple-100 text-purple-800' : ($usuario['tipo_usuario'] === 'professor' ? 'bg-blue-100 text-blue-800' : ($usuario['tipo_usuario'] === 'aluno' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800')); ?>">
                                <?php echo ucfirst($usuario['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($usuario['usuario_login'] ?? $usuario['email'] ?? ''); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $usuario['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="editUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome_completo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES); ?>', '<?php echo $usuario['tipo_usuario']; ?>', '<?php echo htmlspecialchars($usuario['turma'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['serie'] ?? '', ENT_QUOTES); ?>')" class="text-primary-600 hover:text-primary-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?action=toggle_status&id=<?php echo $usuario['id']; ?>" class="text-gray-600 hover:text-gray-800 mr-3">
                                <i class="fas fa-<?php echo $usuario['ativo'] ? 'ban' : 'check'; ?>"></i>
                            </a>
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                <a href="?action=excluir&id=<?php echo $usuario['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Adicionar Usuário -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Adicionar Novo Usuário</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar">
                
                <div class="mb-4">
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                    <input type="text" id="nome" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Nome completo do usuário">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="email@exemplo.com">
                </div>
                
                <div class="mb-4">
                    <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">Senha *</label>
                    <input type="password" id="senha" name="senha" required minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="trocar_senha_proximo_login" id="trocar_senha_proximo_login" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="text-sm text-gray-700">Usuário deve alterar senha no primeiro acesso</span>
                    </label>
                </div>
                
                <div class="mb-4">
                    <label for="tipo_usuario" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                    <select id="tipo_usuario" name="tipo_usuario" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="aluno">Aluno</option>
                        <option value="professor">Professor</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="turma" class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                        <input type="text" id="turma" name="turma" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Turma A">
                    </div>
                    
                    <div>
                        <label for="serie" class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                        <input type="text" id="serie" name="serie" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: 1º Ano">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Usuário
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuário -->
<div id="modal-editar" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalEditar()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Editar Usuário</h3>
                <button onclick="toggleModalEditar()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="usuario_id" id="edit_usuario_id">
                
                <div class="mb-4">
                    <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                    <input type="text" id="edit_nome" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Nome completo do usuário">
                </div>
                
                <div class="mb-4">
                    <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" id="edit_email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="email@exemplo.com">
                </div>
                
                <div class="mb-4">
                    <label for="edit_nova_senha" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (deixe em branco para manter atual)</label>
                    <input type="password" id="edit_nova_senha" name="nova_senha" minlength="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="mb-4">
                    <label for="edit_tipo_usuario" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                    <select id="edit_tipo_usuario" name="tipo_usuario" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="aluno">Aluno</option>
                        <option value="professor">Professor</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="edit_turma" class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                        <input type="text" id="edit_turma" name="turma" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Turma A">
                    </div>
                    
                    <div>
                        <label for="edit_serie" class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                        <input type="text" id="edit_serie" name="serie" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: 1º Ano">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Atualizar Usuário
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

    function toggleModalEditar() {
        const modal = document.getElementById('modal-editar');
        modal.classList.toggle('hidden');
    }

    function editUsuario(id, nome, email, tipo, turma, serie) {
        document.getElementById('edit_usuario_id').value = id;
        document.getElementById('edit_nome').value = nome;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_tipo_usuario').value = tipo;
        document.getElementById('edit_turma').value = turma;
        document.getElementById('edit_serie').value = serie;
        document.getElementById('edit_nova_senha').value = '';
        toggleModalEditar();
    }
</script>
