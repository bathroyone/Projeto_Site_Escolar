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

// Criar disciplina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_disciplina') {
    $codigo = sanitizeInput($_POST['codigo'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $area_conhecimento = sanitizeInput($_POST['area_conhecimento'] ?? '');
    $carga_horaria_anual = intval($_POST['carga_horaria_anual'] ?? 100);
    $carga_horaria_semanal = intval($_POST['carga_horaria_semanal'] ?? 4);
    
    if (empty($codigo) || empty($nome) || empty($area_conhecimento)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO disciplinas (codigo, nome, descricao, area_conhecimento, carga_horaria_anual, carga_horaria_semanal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nome, $descricao, $area_conhecimento, $carga_horaria_anual, $carga_horaria_semanal]);
            
            logAudit('DISCIPLINA_CREATE', 'disciplinas', $pdo->lastInsertId(), null, ['codigo' => $codigo, 'nome' => $nome]);
            
            $success = 'Disciplina criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar disciplina.';
        }
    }
}

// Excluir disciplina
if (isset($_GET['action']) && $_GET['action'] === 'excluir_disciplina' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: disciplinas.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir disciplina.';
    }
}

// Obter disciplinas
$disciplinas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM disciplinas ORDER BY area_conhecimento, nome");
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter disciplinas: " . $e->getMessage());
}

// Obter currículos
$curriculos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM curriculos WHERE ativo = 1 ORDER BY ano_letivo DESC, serie");
    $curriculos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter currículos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Disciplinas e Currículo | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Disciplinas e Currículo</h1>
                <p class="text-gray-600 mt-2">Gerenciar disciplinas e currículos escolares</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Disciplina
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
            <button onclick="showTab('disciplinas')" id="tab-disciplinas" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Disciplinas</button>
            <button onclick="showTab('curriculos')" id="tab-curriculos" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Currículos</button>
        </div>

        <!-- Tab Disciplinas -->
        <div id="content-disciplinas" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Código</th>
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Área</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Carga Anual</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Carga Semanal</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($disciplina['codigo']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($disciplina['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_area = match($disciplina['area_conhecimento']) {
                                                'linguagens' => 'bg-blue-100 text-blue-600',
                                                'matematica' => 'bg-purple-100 text-purple-600',
                                                'ciencias_natureza' => 'bg-green-100 text-green-600',
                                                'ciencias_humanas' => 'bg-orange-100 text-orange-600',
                                                'artes' => 'bg-pink-100 text-pink-600',
                                                'educacao_fisica' => 'bg-red-100 text-red-600',
                                                'tecnologia' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_area;
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $disciplina['area_conhecimento'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $disciplina['carga_horaria_anual']; ?>h</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $disciplina['carga_horaria_semanal']; ?>h</td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_disciplina&id=<?php echo $disciplina['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir esta disciplina?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Currículos -->
        <div id="content-curriculos" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Ano Letivo</th>
                                <th class="px-4 sm:px-6 py-4">Série</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($curriculos as $curriculo): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($curriculo['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $curriculo['ano_letivo']; ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($curriculo['serie']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $curriculo['ativo'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo $curriculo['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($curriculos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-book text-4xl mb-2"></i>
                        <p>Nenhum currículo cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Disciplina -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Disciplina</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_disciplina">
                    
                    <div class="mb-4">
                        <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-2">Código *</label>
                        <input type="text" id="codigo" name="codigo" required maxlength="20"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: PORT, MAT, CIE">
                    </div>
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome da disciplina">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição da disciplina"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="area_conhecimento" class="block text-sm font-semibold text-gray-700 mb-2">Área de Conhecimento *</label>
                        <select id="area_conhecimento" name="area_conhecimento" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="linguagens">Linguagens</option>
                            <option value="matematica">Matemática</option>
                            <option value="ciencias_natureza">Ciências da Natureza</option>
                            <option value="ciencias_humanas">Ciências Humanas</option>
                            <option value="artes">Artes</option>
                            <option value="educacao_fisica">Educação Física</option>
                            <option value="tecnologia">Tecnologia</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="carga_horaria_anual" class="block text-sm font-semibold text-gray-700 mb-2">Carga Anual (h)</label>
                            <input type="number" id="carga_horaria_anual" name="carga_horaria_anual" value="100"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="carga_horaria_semanal" class="block text-sm font-semibold text-gray-700 mb-2">Carga Semanal (h)</label>
                            <input type="number" id="carga_horaria_semanal" name="carga_horaria_semanal" value="4"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Disciplina
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
