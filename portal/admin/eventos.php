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

// Criar evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_evento') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'evento');
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? '');
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $local = sanitizeInput($_POST['local'] ?? '');
    $vagas = intval($_POST['vagas'] ?? 0);
    $responsavel = sanitizeInput($_POST['responsavel'] ?? '');
    $telefone_contato = sanitizeInput($_POST['telefone_contato'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'planejado');
    
    if (empty($titulo) || empty($tipo) || empty($data_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descricao, tipo, data_inicio, data_fim, local, vagas, responsavel, telefone_contato, valor, status, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $tipo, $data_inicio, $data_fim, $local, $vagas, $responsavel, $telefone_contato, $valor, $status, $_SESSION['usuario_id']]);
            
            logAudit('EVENTO_CREATE', 'eventos', $pdo->lastInsertId(), null, ['titulo' => $titulo, 'tipo' => $tipo]);
            
            $success = 'Evento criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar evento.';
        }
    }
}

// Inscrever aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscrever_aluno') {
    $evento_id = intval($_POST['evento_id'] ?? 0);
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    
    if (empty($evento_id) || empty($aluno_id)) {
        $error = 'Por favor, selecione o evento e o aluno.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO evento_inscricoes (evento_id, aluno_id, status) VALUES (?, ?, 'confirmada') ON DUPLICATE KEY UPDATE status = 'confirmada'");
            $stmt->execute([$evento_id, $aluno_id]);
            
            // Atualizar vagas preenchidas
            $stmt = $pdo->prepare("UPDATE eventos SET vagas_preenchidas = vagas_preenchidas + 1 WHERE id = ?");
            $stmt->execute([$evento_id]);
            
            logAudit('EVENTO_INSCRICAO', 'evento_inscricoes', null, null, ['evento_id' => $evento_id, 'aluno_id' => $aluno_id]);
            
            $success = 'Aluno inscrito com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao inscrever aluno.';
        }
    }
}

// Excluir evento
if (isset($_GET['action']) && $_GET['action'] === 'excluir_evento' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: eventos.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir evento.';
    }
}

// Obter eventos
$eventos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM eventos ORDER BY data_inicio DESC");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}

// Obter inscrições
$inscricoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ei.*, e.titulo as evento_titulo, u.nome_completo as aluno_nome 
        FROM evento_inscricoes ei 
        JOIN eventos e ON ei.evento_id = e.id 
        JOIN usuarios u ON ei.aluno_id = u.id 
        ORDER BY ei.data_inscricao DESC
    ");
    $inscricoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter inscrições: " . $e->getMessage());
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
    <title>Gestão de Eventos | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Eventos</h1>
                <p class="text-gray-600 mt-2">Eventos e atividades extracurriculares</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Evento
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
            <button onclick="showTab('eventos')" id="tab-eventos" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Eventos</button>
            <button onclick="showTab('inscricoes')" id="tab-inscricoes" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Inscrições</button>
        </div>

        <!-- Tab Eventos -->
        <div id="content-eventos" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Início</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Local</th>
                                <th class="px-4 sm:px-6 py-4">Vagas</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $evento): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($evento['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $evento['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y H:i', strtotime($evento['data_inicio'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($evento['local'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $evento['vagas_preenchidas'] . '/' . $evento['vagas']; ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($evento['status']) {
                                                'planejado' => 'bg-gray-100 text-gray-600',
                                                'aberto_inscricoes' => 'bg-green-100 text-green-600',
                                                'em_andamento' => 'bg-blue-100 text-blue-600',
                                                'concluido' => 'bg-purple-100 text-purple-600',
                                                'cancelado' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $evento['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_evento&id=<?php echo $evento['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este evento?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($eventos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-2"></i>
                        <p>Nenhum evento cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Inscrições -->
        <div id="content-inscricoes" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Nova Inscrição</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="inscrever_aluno">
                    
                    <div>
                        <label for="evento_id" class="block text-sm font-semibold text-gray-700 mb-2">Evento</label>
                        <select id="evento_id" name="evento_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($eventos as $evento): ?>
                                <option value="<?php echo $evento['id']; ?>"><?php echo htmlspecialchars($evento['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-plus mr-2"></i>Inscrever
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Histórico de Inscrições</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Evento</th>
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Data Inscrição</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $inscricao): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($inscricao['evento_titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($inscricao['aluno_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($inscricao['data_inscricao'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $inscricao['status'] === 'confirmada' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                            <?php echo ucfirst($inscricao['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($inscricoes)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-user-check text-4xl mb-2"></i>
                        <p>Nenhuma inscrição registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Evento -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Evento</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_evento">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título do evento">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="evento">Evento</option>
                            <option value="atividade_extracurricular">Atividade Extracurricular</option>
                            <option value="passeio">Passeio</option>
                            <option value="palestra">Palestra</option>
                            <option value="esporte">Esporte</option>
                            <option value="cultural">Cultural</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do evento"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início *</label>
                            <input type="datetime-local" id="data_inicio" name="data_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim</label>
                            <input type="datetime-local" id="data_fim" name="data_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="local" class="block text-sm font-semibold text-gray-700 mb-2">Local</label>
                            <input type="text" id="local" name="local"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Local do evento">
                        </div>
                        
                        <div>
                            <label for="vagas" class="block text-sm font-semibold text-gray-700 mb-2">Vagas</label>
                            <input type="number" id="vagas" name="vagas"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="0 = ilimitado">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="responsavel" class="block text-sm font-semibold text-gray-700 mb-2">Responsável</label>
                            <input type="text" id="responsavel" name="responsavel"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Nome do responsável">
                        </div>
                        
                        <div>
                            <label for="telefone_contato" class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
                            <input type="text" id="telefone_contato" name="telefone_contato"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="valor" class="block text-sm font-semibold text-gray-700 mb-2">Valor (R$)</label>
                            <input type="number" id="valor" name="valor" step="0.01" value="0"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select id="status" name="status"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="planejado">Planejado</option>
                                <option value="aberto_inscricoes">Aberto Inscrições</option>
                                <option value="em_andamento">Em Andamento</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Evento
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
