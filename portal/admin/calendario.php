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

// Criar tabela de calendário acadêmico se não existir
$conn->query("CREATE TABLE IF NOT EXISTS calendario_academico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('feriado', 'evento', 'prova', 'reuniao', 'inicio_ano', 'fim_ano', 'recesso') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    ano_letivo INT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Adicionar evento ao calendário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? null;
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    
    if (empty($titulo) || empty($tipo) || empty($data_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO calendario_academico (titulo, descricao, tipo, data_inicio, data_fim, ano_letivo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $tipo, $data_inicio, $data_fim, $ano_letivo]);
            $success = 'Evento adicionado ao calendário com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar evento.';
        }
    }
}

// Excluir evento
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM calendario_academico WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: calendario.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir evento.';
    }
}

// Obter eventos do calendário
$eventos = [];
$ano_atual = date('Y');
$query_eventos = "SELECT * FROM calendario_academico WHERE ano_letivo = ? AND ativo = 1 ORDER BY data_inicio ASC";
$stmt = $conn->prepare($query_eventos);
$stmt->execute([$ano_atual]);
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Acadêmico | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Calendário Acadêmico</h1>
                <p class="text-gray-600 mt-2">Gerenciar eventos, feriados e datas importantes</p>
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

        <!-- Calendário -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-xl font-display font-bold text-azul-principal">Eventos de <?php echo $ano_atual; ?></h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Filtrar por tipo:</span>
                    <select id="filtro_tipo" onchange="filtrarEventos()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">Todos</option>
                        <option value="feriado">Feriado</option>
                        <option value="evento">Evento</option>
                        <option value="prova">Prova</option>
                        <option value="reuniao">Reunião</option>
                        <option value="inicio_ano">Início do Ano</option>
                        <option value="fim_ano">Fim do Ano</option>
                        <option value="recesso">Recesso</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Data</th>
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Descrição</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="eventos_tbody">
                        <?php foreach ($eventos as $evento): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 evento-row" data-tipo="<?php echo $evento['tipo']; ?>">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                            <?php 
                                            $cor_fundo = match($evento['tipo']) {
                                                'feriado' => 'bg-red-100',
                                                'evento' => 'bg-blue-100',
                                                'prova' => 'bg-purple-100',
                                                'reuniao' => 'bg-green-100',
                                                'inicio_ano' => 'bg-yellow-100',
                                                'fim_ano' => 'bg-orange-100',
                                                'recesso' => 'bg-gray-100',
                                                default => 'bg-gray-100'
                                            };
                                            echo $cor_fundo;
                                            ?>">
                                            <i class="fas 
                                                <?php 
                                                $icone = match($evento['tipo']) {
                                                    'feriado' => 'fa-calendar-times',
                                                    'evento' => 'fa-calendar-check',
                                                    'prova' => 'fa-clipboard-list',
                                                    'reuniao' => 'fa-users',
                                                    'inicio_ano' => 'fa-play-circle',
                                                    'fim_ano' => 'fa-stop-circle',
                                                    'recesso' => 'fa-pause-circle',
                                                    default => 'fa-calendar'
                                                };
                                                echo $icone;
                                                ?> 
                                                text-gray-600"></i>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-800 text-sm"><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></span>
                                            <?php if ($evento['data_fim']): ?>
                                                <span class="text-gray-500 text-xs"> - <?php echo date('d/m/Y', strtotime($evento['data_fim'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($evento['titulo']); ?></span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_texto = match($evento['tipo']) {
                                            'feriado' => 'bg-red-100 text-red-600',
                                            'evento' => 'bg-blue-100 text-blue-600',
                                            'prova' => 'bg-purple-100 text-purple-600',
                                            'reuniao' => 'bg-green-100 text-green-600',
                                            'inicio_ano' => 'bg-yellow-100 text-yellow-600',
                                            'fim_ano' => 'bg-orange-100 text-orange-600',
                                            'recesso' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_texto;
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $evento['tipo'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($evento['descricao'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <a href="?action=excluir&id=<?php echo $evento['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este evento?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Evento -->
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
                    <input type="hidden" name="action" value="adicionar">
                    
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
                            <option value="feriado">Feriado</option>
                            <option value="evento">Evento</option>
                            <option value="prova">Prova</option>
                            <option value="reuniao">Reunião</option>
                            <option value="inicio_ano">Início do Ano Letivo</option>
                            <option value="fim_ano">Fim do Ano Letivo</option>
                            <option value="recesso">Recesso</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início *</label>
                            <input type="date" id="data_inicio" name="data_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim (opcional)</label>
                            <input type="date" id="data_fim" name="data_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="ano_letivo" class="block text-sm font-semibold text-gray-700 mb-2">Ano Letivo *</label>
                        <input type="number" id="ano_letivo" name="ano_letivo" required value="<?php echo date('Y'); ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do evento"></textarea>
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

        function filtrarEventos() {
            const filtro = document.getElementById('filtro_tipo').value;
            const linhas = document.querySelectorAll('.evento-row');
            
            linhas.forEach(linha => {
                if (filtro === '' || linha.dataset.tipo === filtro) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
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
