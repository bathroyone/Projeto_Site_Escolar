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

// Registrar visita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_visita') {
    $visitante_nome = sanitizeInput($_POST['visitante_nome'] ?? '');
    $visitante_documento = sanitizeInput($_POST['visitante_documento'] ?? '');
    $visitante_telefone = sanitizeInput($_POST['visitante_telefone'] ?? '');
    $tipo_visita = sanitizeInput($_POST['tipo_visita'] ?? 'pais');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    $data_visita = sanitizeInput($_POST['data_visita'] ?? date('Y-m-d'));
    $hora_entrada = sanitizeInput($_POST['hora_entrada'] ?? '');
    $setor = sanitizeInput($_POST['setor'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($visitante_nome) || empty($tipo_visita) || empty($data_visita)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO visitas (visitante_nome, visitante_documento, visitante_telefone, tipo_visita, motivo, data_visita, hora_entrada, setor, autorizado_por, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$visitante_nome, $visitante_documento, $visitante_telefone, $tipo_visita, $motivo, $data_visita, $hora_entrada, $setor, $_SESSION['usuario_id'], $observacoes]);
            
            logAudit('VISITA_CREATE', 'visitas', $pdo->lastInsertId(), null, ['visitante_nome' => $visitante_nome, 'tipo' => $tipo_visita]);
            
            $success = 'Visita registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar visita.';
        }
    }
}

// Aprovar autorização
if (isset($_GET['action']) && $_GET['action'] === 'aprovar_autorizacao' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE autorizacoes SET status = 'aprovada', aprovado_por = ?, data_aprovacao = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        header('Location: visitas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao aprovar autorização.';
    }
}

// Rejeitar autorização
if (isset($_GET['action']) && $_GET['action'] === 'rejeitar_autorizacao' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE autorizacoes SET status = 'rejeitada', aprovado_por = ?, data_aprovacao = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id'], intval($_GET['id'])]);
        header('Location: visitas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao rejeitar autorização.';
    }
}

// Obter visitas
$visitas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM visitas ORDER BY data_visita DESC, created_at DESC");
    $visitas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter visitas: " . $e->getMessage());
}

// Obter autorizações
$autorizacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.nome_completo as aluno_nome 
        FROM autorizacoes a 
        JOIN usuarios u ON a.aluno_id = u.id 
        ORDER BY a.data_solicitacao DESC
    ");
    $autorizacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter autorizações: " . $e->getMessage());
}

// Obter alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitas e Autorizações | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Visitas e Autorizações</h1>
                <p class="text-gray-600 mt-2">Controle de visitas e autorizações</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Visita
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
            <button onclick="showTab('visitas')" id="tab-visitas" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Visitas</button>
            <button onclick="showTab('autorizacoes')" id="tab-autorizacoes" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Autorizações</button>
        </div>

        <!-- Tab Visitas -->
        <div id="content-visitas" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Visitante</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Setor</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitas as $visita): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($visita['visitante_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-600">
                                            <?php echo ucfirst($visita['tipo_visita']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y', strtotime($visita['data_visita'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($visita['setor'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($visita['status']) {
                                                'agendada' => 'bg-blue-100 text-blue-600',
                                                'em_andamento' => 'bg-green-100 text-green-600',
                                                'concluida' => 'bg-gray-100 text-gray-600',
                                                'cancelada' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $visita['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($visitas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-user-clock text-4xl mb-2"></i>
                        <p>Nenhuma visita registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Autorizações -->
        <div id="content-autorizacoes" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Descrição</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Responsável</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autorizacoes as $aut): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($aut['aluno_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $aut['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars(substr($aut['descricao'], 0, 50)) . '...'; ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($aut['responsavel_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($aut['status']) {
                                                'pendente' => 'bg-yellow-100 text-yellow-600',
                                                'aprovada' => 'bg-green-100 text-green-600',
                                                'rejeitada' => 'bg-red-100 text-red-600',
                                                'expirada' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($aut['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <?php if ($aut['status'] === 'pendente'): ?>
                                            <div class="flex gap-2">
                                                <a href="?action=aprovar_autorizacao&id=<?php echo $aut['id']; ?>" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Aprovar">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="?action=rejeitar_autorizacao&id=<?php echo $aut['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Rejeitar">
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
                
                <?php if (empty($autorizacoes)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-file-signature text-4xl mb-2"></i>
                        <p>Nenhuma autorização solicitada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Visita -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Visita</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="registrar_visita">
                    
                    <div class="mb-4">
                        <label for="visitante_nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome do Visitante *</label>
                        <input type="text" id="visitante_nome" name="visitante_nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome completo">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="visitante_documento" class="block text-sm font-semibold text-gray-700 mb-2">Documento</label>
                            <input type="text" id="visitante_documento" name="visitante_documento"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="CPF/RG">
                        </div>
                        
                        <div>
                            <label for="visitante_telefone" class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
                            <input type="text" id="visitante_telefone" name="visitante_telefone"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_visita" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Visita *</label>
                        <select id="tipo_visita" name="tipo_visita" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="pais">Pais/Responsáveis</option>
                            <option value="autoridade">Autoridade</option>
                            <option value="fornecedor">Fornecedor</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                        <textarea id="motivo" name="motivo" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Motivo da visita"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_visita" class="block text-sm font-semibold text-gray-700 mb-2">Data *</label>
                            <input type="date" id="data_visita" name="data_visita" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="hora_entrada" class="block text-sm font-semibold text-gray-700 mb-2">Hora Entrada</label>
                            <input type="time" id="hora_entrada" name="hora_entrada"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="setor" class="block text-sm font-semibold text-gray-700 mb-2">Setor</label>
                        <input type="text" id="setor" name="setor"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Setor a visitar">
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações adicionais"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Registrar Visita
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
