<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

$success = '';
$error = '';

// Solicitar documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'solicitar_documento') {
    $tipo_documento = sanitizeInput($_POST['tipo_documento'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($tipo_documento)) {
        $error = 'Por favor, selecione o tipo de documento.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO solicitacoes_documentos (aluno_id, tipo_documento, motivo, data_solicitacao, status) VALUES (?, ?, ?, NOW(), 'pendente')");
            $stmt->execute([$aluno_id, $tipo_documento, $motivo]);
            
            logAudit('DOCUMENTO_SOLICITAR', 'solicitacoes_documentos', $pdo->lastInsertId(), null, ['tipo_documento' => $tipo_documento]);
            
            $success = 'Solicitação enviada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar solicitação.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter solicitações do aluno
$solicitacoes = [];
try {
    $stmt = $pdo->query("
        SELECT 
            sd.*,
            u.nome_completo as aluno_nome
        FROM solicitacoes_documentos sd
        JOIN usuarios u ON sd.aluno_id = u.id
        WHERE sd.aluno_id = ?
        ORDER BY sd.data_solicitacao DESC
    ");
    $stmt->execute([$aluno_id]);
    $solicitacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter solicitações: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação de Documentos | Portal de Gestão Escolar</title>
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
                                <p class="text-sm text-gray-500">Aluno</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Solicitação de Documentos</h1>
                <p class="text-gray-600 mt-2">Solicite declarações e documentos escolares</p>
            </div>
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

        <!-- Formulário de Solicitação -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h2 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-file-alt mr-2"></i>Nova Solicitação
                </h2>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="solicitar_documento">
                
                <div class="mb-6">
                    <label for="tipo_documento" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Documento *</label>
                    <select id="tipo_documento" name="tipo_documento" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Selecione o documento</option>
                        <option value="declaracao_matricula">Declaração de Matrícula</option>
                        <option value="declaracao_conclusao">Declaração de Conclusão</option>
                        <option value="historico_escolar">Histórico Escolar</option>
                        <option value="boletim">Boletim Escolar</option>
                        <option value="atestado_frequencia">Atestado de Frequência</option>
                        <option value="declaracao_vinculo">Declaração de Vínculo</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo da Solicitação</label>
                    <textarea id="motivo" name="motivo" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                        placeholder="Descreva o motivo da solicitação (opcional)"></textarea>
                </div>
                
                <button type="submit"
                    class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Enviar Solicitação
                </button>
            </form>
        </div>

        <!-- Histórico de Solicitações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Solicitações</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Documento</th>
                            <th class="px-4 sm:px-6 py-4">Motivo</th>
                            <th class="px-4 sm:px-6 py-4">Data</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitacoes as $solicitacao): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800">
                                    <?php 
                                    $documentos = [
                                        'declaracao_matricula' => 'Declaração de Matrícula',
                                        'declaracao_conclusao' => 'Declaração de Conclusão',
                                        'historico_escolar' => 'Histórico Escolar',
                                        'boletim' => 'Boletim Escolar',
                                        'atestado_frequencia' => 'Atestado de Frequência',
                                        'declaracao_vinculo' => 'Declaração de Vínculo',
                                        'outro' => 'Outro'
                                    ];
                                    echo htmlspecialchars($documentos[$solicitacao['tipo_documento']] ?? $solicitacao['tipo_documento']);
                                    ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($solicitacao['motivo'] ?? '', 0, 50)) . '...'; ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                    <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($solicitacao['status']) {
                                            'pendente' => 'bg-yellow-100 text-yellow-600',
                                            'aprovado' => 'bg-green-100 text-green-600',
                                            'rejeitado' => 'bg-red-100 text-red-600',
                                            'concluido' => 'bg-blue-100 text-blue-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo ucfirst($solicitacao['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($solicitacoes)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-2"></i>
                    <p>Nenhuma solicitação encontrada.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
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
