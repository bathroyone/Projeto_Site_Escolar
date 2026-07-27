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

// Criar tabela de notificações se não existir
$conn->query("CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo_destino ENUM('todos', 'alunos', 'professores', 'secretaria', 'turma') DEFAULT 'todos',
    turma_id INT,
    enviada TINYINT(1) DEFAULT 0,
    data_envio DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Criar tabela de notificações recebidas
$conn->query("CREATE TABLE IF NOT EXISTS notificacoes_recebidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificacao_id INT NOT NULL,
    usuario_id INT NOT NULL,
    lida TINYINT(1) DEFAULT 0,
    data_leitura DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notificacao_id) REFERENCES notificacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (notificacao_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Enviar notificação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $tipo_destino = sanitizeInput($_POST['tipo_destino'] ?? 'todos');
    $turma_id = intval($_POST['turma_id'] ?? 0);
    
    if (empty($titulo) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            // Criar notificação
            $stmt = $conn->prepare("INSERT INTO notificacoes (titulo, mensagem, tipo_destino, turma_id, enviada, data_envio) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$titulo, $mensagem, $tipo_destino, $turma_id ?: null]);
            $notificacao_id = $conn->insert_id;
            
            // Determinar destinatários
            $where_clause = "ativo = 1";
            if ($tipo_destino === 'alunos') {
                $where_clause .= " AND tipo_usuario = 'aluno'";
            } elseif ($tipo_destino === 'professores') {
                $where_clause .= " AND tipo_usuario = 'professor'";
            } elseif ($tipo_destino === 'secretaria') {
                $where_clause .= " AND tipo_usuario = 'secretaria'";
            } elseif ($tipo_destino === 'turma' && $turma_id > 0) {
                $where_clause .= " AND turma_id = $turma_id";
            }
            
            // Obter usuários destinatários
            $stmt = $conn->query("SELECT id FROM usuarios WHERE $where_clause");
            $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Criar notificações para cada usuário
            foreach ($usuarios as $usuario) {
                $stmt = $conn->prepare("INSERT INTO notificacoes_recebidas (notificacao_id, usuario_id) VALUES (?, ?)");
                $stmt->execute([$notificacao_id, $usuario['id']]);
            }
            
            $success = 'Notificação enviada para ' . count($usuarios) . ' usuários!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar notificação.';
        }
    }
}

// Obter notificações enviadas
$notificacoes = [];
try {
    $stmt = $conn->query("SELECT n.*, COUNT(nr.id) as total_recebidas FROM notificacoes n LEFT JOIN notificacoes_recebidas nr ON n.id = nr.notificacao_id GROUP BY n.id ORDER BY n.created_at DESC");
    $notificacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter notificações: " . $e->getMessage());
}

// Obter turmas
$turmas = [];
try {
    $stmt = $conn->query("SELECT * FROM turmas WHERE ano_letivo = " . date('Y') . " ORDER BY nome, serie");
    $turmas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações em Massa | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Notificações em Massa</h1>
                <p class="text-gray-600 mt-2">Enviar comunicados para usuários</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-bell mr-2"></i>Nova Notificação
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

        <!-- Histórico de Notificações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Notificações</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Destino</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Envio</th>
                            <th class="px-4 sm:px-6 py-4">Enviada Para</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificacoes as $notificacao): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-azul-principal/10 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-bell text-azul-principal"></i>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($notificacao['titulo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                        <?php echo ucfirst(str_replace('_', ' ', $notificacao['tipo_destino'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_envio'] ?? $notificacao['created_at'])); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $notificacao['total_recebidas']; ?> usuários</td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $notificacao['enviada'] ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                        <?php echo $notificacao['enviada'] ? 'Enviada' : 'Pendente'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Nova Notificação -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Notificação</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="enviar">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título da notificação">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_destino" class="block text-sm font-semibold text-gray-700 mb-2">Enviar Para</label>
                        <select id="tipo_destino" name="tipo_destino" onchange="mostrarTurma()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="todos">Todos os Usuários</option>
                            <option value="alunos">Apenas Alunos</option>
                            <option value="professores">Apenas Professores</option>
                            <option value="secretaria">Apenas Secretaria</option>
                            <option value="turma">Turma Específica</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 hidden" id="turma_container">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                        <select id="turma_id" name="turma_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" rows="5" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Conteúdo da mensagem"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Notificação
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

        function mostrarTurma() {
            const tipoDestino = document.getElementById('tipo_destino').value;
            const turmaContainer = document.getElementById('turma_container');
            
            if (tipoDestino === 'turma') {
                turmaContainer.classList.remove('hidden');
            } else {
                turmaContainer.classList.add('hidden');
            }
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
