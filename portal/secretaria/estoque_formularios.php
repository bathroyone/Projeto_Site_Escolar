<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é secretaria
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'secretaria') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Adicionar formulário ao estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_formulario') {
    $nome_formulario = sanitizeInput($_POST['nome_formulario'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($nome_formulario) || empty($quantidade)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO estoque_formularios (nome_formulario, quantidade, descricao, criado_por) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome_formulario, $quantidade, $descricao, $_SESSION['usuario_id']]);
            
            logAudit('FORMULARIO_CREATE', 'estoque_formularios', $pdo->lastInsertId(), null, ['nome' => $nome_formulario, 'quantidade' => $quantidade]);
            
            $success = 'Formulário adicionado ao estoque com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao adicionar formulário: " . $e->getMessage());
            $error = 'Erro ao adicionar formulário.';
        }
    }
}

// Atualizar quantidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_quantidade') {
    $formulario_id = intval($_POST['formulario_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $tipo_movimentacao = sanitizeInput($_POST['tipo_movimentacao'] ?? 'entrada');
    
    if (empty($formulario_id)) {
        $error = 'ID do formulário é obrigatório.';
    } else {
        try {
            $pdo = getDBConnection();
            
            if ($tipo_movimentacao === 'entrada') {
                $stmt = $pdo->prepare("UPDATE estoque_formularios SET quantidade = quantidade + ?, atualizado_por = ? WHERE id = ?");
                $stmt->execute([$quantidade, $_SESSION['usuario_id'], $formulario_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE estoque_formularios SET quantidade = GREATEST(0, quantidade - ?), atualizado_por = ? WHERE id = ?");
                $stmt->execute([$quantidade, $_SESSION['usuario_id'], $formulario_id]);
            }
            
            logAudit('FORMULARIO_UPDATE', 'estoque_formularios', $formulario_id, null, ['quantidade' => $quantidade, 'tipo' => $tipo_movimentacao]);
            
            $success = 'Quantidade atualizada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao atualizar quantidade: " . $e->getMessage());
            $error = 'Erro ao atualizar quantidade.';
        }
    }
}

// Excluir formulário
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM estoque_formularios WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        logAudit('FORMULARIO_DELETE', 'estoque_formularios', intval($_GET['id']), null, []);
        
        header('Location: estoque_formularios.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir formulário: " . $e->getMessage());
        $error = 'Erro ao excluir formulário.';
    }
}

// Obter formulários
$formularios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM estoque_formularios ORDER BY nome_formulario");
    $formularios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter formulários: " . $e->getMessage());
}

// Obter estatísticas
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_formularios,
            SUM(quantidade) as total_quantidade,
            COUNT(CASE WHEN quantidade <= 10 THEN 1 END) as estoque_baixo
        FROM estoque_formularios
    ");
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque de Formulários | Portal da Secretaria</title>
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
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #063b7a 0%, #0b4a8c 50%, #13843b 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">CONTROLE DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">ESTOQUE DE FORMULÁRIOS</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="text-white text-xs sm:text-sm font-medium block"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 15)); ?></span>
                                <span class="text-white/70 text-xs">Secretaria</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>

                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Secretaria</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Secretaria</span>
                                </a>
                                <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-alt text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Tipos</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $estatisticas['total_formularios'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Total Unidades</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_quantidade'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Estoque Baixo</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas['estoque_baixo'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-azul-principal">Controle de Estoque de Formulários</h2>
            <button onclick="document.getElementById('modal-novo-formulario').classList.remove('hidden')" class="bg-gradient-to-r from-azul-principal to-verde-complementar text-white px-6 py-3 rounded-xl hover:shadow-lg transition-all font-semibold">
                <i class="fas fa-plus mr-2"></i>Novo Formulário
            </button>
        </div>

        <!-- Lista de Formulários -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-list mr-2"></i>Formulários em Estoque
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($formularios) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Nome</th>
                                    <th class="px-4 sm:px-6 py-4">Quantidade</th>
                                    <th class="px-4 sm:px-6 py-4">Descrição</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formularios as $formulario): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($formulario['nome_formulario']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $formulario['quantidade']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($formulario['descricao'] ?? '-', 0, 40)); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = $formulario['quantidade'] <= 10 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600';
                                                echo $cor_status;
                                                ?>">
                                                <?php echo $formulario['quantidade'] <= 10 ? 'Estoque Baixo' : 'OK'; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex gap-2">
                                                <button onclick="atualizarQuantidade(<?php echo $formulario['id']; ?>)" class="text-azul-principal hover:text-azul-claro transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=excluir&id=<?php echo $formulario['id']; ?>" class="text-red-600 hover:text-red-700 transition-colors" onclick="return confirm('Deseja realmente excluir este formulário?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-file-alt text-4xl mb-4"></i>
                        <p>Nenhum formulário cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Formulário -->
    <div id="modal-novo-formulario" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Novo Formulário</h3>
                <button onclick="document.getElementById('modal-novo-formulario').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar_formulario">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome do Formulário</label>
                    <input type="text" name="nome_formulario" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Quantidade Inicial</label>
                    <input type="number" name="quantidade" required min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-novo-formulario').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function atualizarQuantidade(id) {
            alert('Funcionalidade de atualização de quantidade em desenvolvimento. ID: ' + id);
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
