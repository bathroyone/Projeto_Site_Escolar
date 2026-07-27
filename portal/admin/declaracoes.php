<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin ou secretaria
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'secretaria')) {
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

// Obter alunos
$alunos = [];
try {
    $stmt = $conn->query("SELECT id, nome_completo, turma, serie FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Gerar declaração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gerar') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($tipo)) {
        $error = 'Por favor, selecione o aluno e o tipo de declaração.';
    } else {
        try {
            // Obter dados do aluno
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$aluno_id]);
            $aluno = $stmt->get_result()->fetch_assoc();
            
            if ($aluno) {
                // Gerar HTML da declaração
                $html = gerarDeclaracao($aluno, $tipo, $observacoes);
                
                // Salvar como arquivo
                $arquivo = '../uploads/declaracoes/declaracao_' . $aluno_id . '_' . time() . '.html';
                if (!is_dir('../uploads/declaracoes/')) {
                    mkdir('../uploads/declaracoes/', 0777, true);
                }
                file_put_contents($arquivo, $html);
                
                $success = 'Declaração gerada com sucesso! <a href="' . $arquivo . '" target="_blank" class="underline">Clique aqui para visualizar</a>';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao gerar declaração.';
        }
    }
}

function gerarDeclaracao($aluno, $tipo, $observacoes) {
    $data = date('d/m/Y');
    $nome_escola = "[Inserir nome da escola aqui]";
    
    $conteudo = match($tipo) {
        'matricula' => "DECLARAÇÃO DE MATRÍCULA\n\nDeclaro para os devidos fins que " . $aluno['nome_completo'] . ", está regularmente matriculado(a) nesta instituição no ano letivo de " . date('Y') . ".",
        'frequencia' => "DECLARAÇÃO DE FREQUÊNCIA\n\nDeclaro para os devidos fins que " . $aluno['nome_completo'] . ", aluno(a) desta instituição, tem apresentado frequência satisfatória às atividades escolares.",
        'conclusao' => "DECLARAÇÃO DE CONCLUSÃO\n\nDeclaro para os devidos fins que " . $aluno['nome_completo'] . ", concluiu com aproveitamento o curso referente à série " . $aluno['serie'] . " nesta instituição.",
        default => "DECLARAÇÃO\n\nDeclaro para os devidos fins que " . $aluno['nome_completo'] . " é aluno(a) desta instituição."
    };
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Declaração</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
            .cabecalho { text-align: center; margin-bottom: 40px; }
            .conteudo { text-align: justify; line-height: 1.8; margin-bottom: 40px; }
            .assinatura { margin-top: 60px; text-align: right; }
            .data { text-align: right; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='cabecalho'>
            <h1>$nome_escola</h1>
            <p>Declaração Escolar</p>
        </div>
        <div class='conteudo'>
            <p>$conteudo</p>
            " . ($observacoes ? "<p><strong>Observações:</strong> $observacoes</p>" : "") . "
        </div>
        <div class='assinatura'>
            <p>_______________________________________</p>
            <p>Assinatura do Diretor(a)</p>
        </div>
        <div class='data'>
            <p>$nome_escola, $data</p>
        </div>
    </body>
    </html>
    ";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emissão de Declarações | Portal de Gestão Escolar</title>
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
                    <a href="../dashboard_secretaria.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
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
                                <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['tipo_usuario']); ?></p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Emissão de Declarações</h1>
                <p class="text-gray-600 mt-2">Gerar declarações para alunos</p>
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

        <!-- Formulário de Emissão -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <form method="POST" action="">
                <input type="hidden" name="action" value="gerar">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno *</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo'] . ' - ' . $aluno['turma'] . ' - ' . $aluno['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Declaração *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="matricula">Declaração de Matrícula</option>
                            <option value="frequencia">Declaração de Frequência</option>
                            <option value="conclusao">Declaração de Conclusão</option>
                            <option value="geral">Declaração Geral</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                        placeholder="Observações adicionais para a declaração"></textarea>
                </div>
                
                <button type="submit"
                    class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                    <i class="fas fa-file-alt mr-2"></i>
                    Gerar Declaração
                </button>
            </form>
        </div>

        <!-- Histórico de Declarações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Declarações Recentes</h2>
            </div>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-file-alt text-4xl mb-2"></i>
                <p>As declarações são geradas sob demanda e salvas temporariamente.</p>
            </div>
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
