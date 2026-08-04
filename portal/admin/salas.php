<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Criar sala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_sala') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'sala_aula');
    $capacidade = intval($_POST['capacidade'] ?? 0);
    $andar = sanitizeInput($_POST['andar'] ?? '');
    $bloco = sanitizeInput($_POST['bloco'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $recursos = sanitizeInput($_POST['recursos'] ?? '');
    
    if (empty($nome) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO salas_espacos (nome, tipo, capacidade, andar, bloco, descricao, recursos, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $tipo, $capacidade, $andar, $bloco, $descricao, $recursos, $_SESSION['usuario_id']]);
            
            logAudit('SALA_CREATE', 'salas_espacos', $pdo->lastInsertId(), null, ['nome' => $nome, 'tipo' => $tipo]);
            
            $success = 'Sala criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar sala.';
        }
    }
}

// Aprovar reserva
if (isset($_GET['action']) && $_GET['action'] === 'aprovar_reserva' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE salas_reservas SET status = 'confirmada', aprovado_por = ? WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        header('Location: salas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao aprovar reserva.';
    }
}

// Cancelar reserva
if (isset($_GET['action']) && $_GET['action'] === 'cancelar_reserva' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE salas_reservas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: salas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao cancelar reserva.';
    }
}

// Excluir sala
if (isset($_GET['action']) && $_GET['action'] === 'excluir_sala' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM salas_espacos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: salas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir sala.';
    }
}

// Obter salas
$salas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM salas_espacos ORDER BY nome");
    $salas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter salas: " . $e->getMessage());
}

// Obter reservas
$reservas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT sr.*, s.nome as sala_nome, u.nome_completo as usuario_nome 
        FROM salas_reservas sr 
        JOIN salas_espacos s ON sr.sala_id = s.id 
        JOIN usuarios u ON sr.usuario_id = u.id 
        ORDER BY sr.data_reserva DESC, sr.hora_inicio
    ");
    $reservas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter reservas: " . $e->getMessage());
}

// Obter usuários
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE ativo = 1 ORDER BY nome_completo");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Salas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Salas e Espaços</h1>
                <p class="text-gray-600 mt-2">Controle de salas e reservas</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Sala
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

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200">
            <button onclick="showTab('salas')" id="tab-salas" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Salas</button>
            <button onclick="showTab('reservas')" id="tab-reservas" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Reservas</button>
        </div>

        <!-- Tab Salas -->
        <div id="content-salas" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Capacidade</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Localização</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salas as $sala): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($sala['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $sala['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $sala['capacidade'] ?: '-'; ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                        <?php echo $sala['bloco'] ? 'Bloco ' . htmlspecialchars($sala['bloco']) : ''; ?>
                                        <?php echo $sala['andar'] ? ' - ' . htmlspecialchars($sala['andar']) . 'º andar' : ''; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($sala['status']) {
                                                'disponivel' => 'bg-green-100 text-green-600',
                                                'ocupada' => 'bg-red-100 text-red-600',
                                                'manutencao' => 'bg-yellow-100 text-yellow-600',
                                                'indisponivel' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($sala['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_sala&id=<?php echo $sala['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir esta sala?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($salas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-door-open text-4xl mb-2"></i>
                        <p>Nenhuma sala cadastrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Reservas -->
        <div id="content-reservas" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Sala</th>
                                <th class="px-4 sm:px-6 py-4">Usuário</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Horário</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $reserva): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($reserva['sala_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($reserva['usuario_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($reserva['data_reserva'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $reserva['hora_inicio']; ?> - <?php echo $reserva['hora_fim']; ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($reserva['status']) {
                                                'pendente' => 'bg-yellow-100 text-yellow-600',
                                                'confirmada' => 'bg-green-100 text-green-600',
                                                'cancelada' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($reserva['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <?php if ($reserva['status'] === 'pendente'): ?>
                                            <div class="flex gap-1">
                                                <a href="?action=aprovar_reserva&id=<?php echo $reserva['id']; ?>" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Aprovar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="?action=cancelar_reserva&id=<?php echo $reserva['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($reservas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-calendar-check text-4xl mb-2"></i>
                        <p>Nenhuma reserva registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Sala -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Sala</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_sala">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome da sala">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="sala_aula">Sala de Aula</option>
                            <option value="laboratorio">Laboratório</option>
                            <option value="biblioteca">Biblioteca</option>
                            <option value="auditorio">Auditório</option>
                            <option value="quadra">Quadra</option>
                            <option value="recreio">Recreio</option>
                            <option value="administracao">Administração</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="capacidade" class="block text-sm font-semibold text-gray-700 mb-2">Capacidade</label>
                            <input type="number" id="capacidade" name="capacidade"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="0">
                        </div>
                        
                        <div>
                            <label for="andar" class="block text-sm font-semibold text-gray-700 mb-2">Andar</label>
                            <input type="text" id="andar" name="andar"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Ex: 1º">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="bloco" class="block text-sm font-semibold text-gray-700 mb-2">Bloco</label>
                        <input type="text" id="bloco" name="bloco"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: A">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição da sala"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="recursos" class="block text-sm font-semibold text-gray-700 mb-2">Recursos</label>
                        <textarea id="recursos" name="recursos" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Projetor, Ar condicionado, Quadra branca"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Sala
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

        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
                el.classList.add('text-gray-500');
            });
            
            document.getElementById('content-' + tab).classList.remove('hidden');
            
            const tabElement = document.getElementById('tab-' + tab);
            tabElement.classList.add('text-azul-principal', 'border-b-2', 'border-azul-principal');
            tabElement.classList.remove('text-gray-500');
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
