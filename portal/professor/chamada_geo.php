<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'professor') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Criar chamada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_chamada') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $data_chamada = sanitizeInput($_POST['data_chamada'] ?? date('Y-m-d'));
    $hora_inicio = sanitizeInput($_POST['hora_inicio'] ?? date('H:i'));
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($turma_id) || empty($data_chamada) || empty($hora_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO chamadas_digitais (turma_id, professor_id, data_chamada, hora_inicio, observacoes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$turma_id, $_SESSION['usuario_id'], $data_chamada, $hora_inicio, $observacoes]);
            
            logAudit('CHAMADA_CREATE', 'chamadas_digitais', $pdo->lastInsertId(), null, ['turma_id' => $turma_id, 'data' => $data_chamada]);
            
            $success = 'Chamada iniciada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar chamada.';
        }
    }
}

// Fechar chamada
if (isset($_GET['action']) && $_GET['action'] === 'fechar_chamada' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE chamadas_digitais SET status = 'fechada', hora_fim = NOW() WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: chamada_geo.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao fechar chamada.';
    }
}

// Registrar presença (via API para alunos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_presenca') {
    $chamada_id = intval($_POST['chamada_id'] ?? 0);
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $localizacao_texto = sanitizeInput($_POST['localizacao_texto'] ?? '');
    
    if (empty($chamada_id)) {
        $error = 'Chamada não especificada.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO chamada_presenca (chamada_id, aluno_id, latitude, longitude, localizacao_texto, ip_registro) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$chamada_id, $_SESSION['usuario_id'], $latitude, $longitude, $localizacao_texto, $_SERVER['REMOTE_ADDR']]);
            
            $success = 'Presença registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar presença.';
        }
    }
}

// Obter chamadas do professor
$chamadas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT cd.*, t.nome as turma_nome 
        FROM chamadas_digitais cd 
        JOIN turmas t ON cd.turma_id = t.id 
        WHERE cd.professor_id = ? 
        ORDER BY cd.data_chamada DESC, cd.hora_inicio DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $chamadas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter chamadas: " . $e->getMessage());
}

// Obter turmas do professor
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome FROM turmas WHERE ano_letivo = YEAR(CURDATE()) ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter presenças
$presencas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT cp.*, u.nome_completo as aluno_nome, cd.data_chamada 
        FROM chamada_presenca cp 
        JOIN usuarios u ON cp.aluno_id = u.id 
        JOIN chamadas_digitais cd ON cp.chamada_id = cd.id 
        WHERE cd.professor_id = ? 
        ORDER BY cp.data_registro DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $presencas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter presenças: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada Digital | Portal do Professor</title>
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
                                <p class="text-sm text-gray-500">Professor</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Chamada Digital</h1>
                <p class="text-gray-600 mt-2">Sistema de chamada com geolocalização</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Chamada
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
            <button onclick="showTab('chamadas')" id="tab-chamadas" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Chamadas</button>
            <button onclick="showTab('presencas')" id="tab-presencas" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Presenças</button>
        </div>

        <!-- Tab Chamadas -->
        <div id="content-chamadas" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Turma</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4">Horário</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chamadas as $chamada): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($chamada['turma_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($chamada['data_chamada'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                        <?php echo $chamada['hora_inicio']; ?> 
                                        <?php echo $chamada['hora_fim'] ? ' - ' . $chamada['hora_fim'] : ''; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $chamada['status'] === 'aberta' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($chamada['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <?php if ($chamada['status'] === 'aberta'): ?>
                                            <a href="?action=fechar_chamada&id=<?php echo $chamada['id']; ?>" class="p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors" title="Fechar Chamada">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($chamadas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                        <p>Nenhuma chamada registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Presenças -->
        <div id="content-presencas" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Histórico de Presenças</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Localização</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presencas as $presenca): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($presenca['aluno_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($presenca['data_registro'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                            Presente
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                        <?php if ($presenca['latitude'] && $presenca['longitude']): ?>
                                            <a href="https://maps.google.com/?q=<?php echo $presenca['latitude']; ?>,<?php echo $presenca['longitude']; ?>" target="_blank" class="text-blue-600 hover:underline">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <?php echo number_format($presenca['latitude'], 6); ?>, <?php echo number_format($presenca['longitude'], 6); ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                            <?php endif; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($presenca['ip_registro'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($presencas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-user-check text-4xl mb-2"></i>
                        <p>Nenhuma presença registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Chamada -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Chamada</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_chamada">
                    
                    <div class="mb-4">
                        <label for="turma_id" class="block text-sm font-semibold text-gray-700 mb-2">Turma *</label>
                        <select id="turma_id" name="turma_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_chamada" class="block text-sm font-semibold text-gray-700 mb-2">Data *</label>
                            <input type="date" id="data_chamada" name="data_chamada" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="hora_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Hora Início *</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" required value="<?php echo date('H:i'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações sobre a chamada"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-play mr-2"></i>
                        Iniciar Chamada
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
