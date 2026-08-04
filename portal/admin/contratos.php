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

// Criar contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_contrato') {
    $numero = sanitizeInput($_POST['numero'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'trabalho');
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $parte_interessada = sanitizeInput($_POST['parte_interessada'] ?? '');
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? '');
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($numero) || empty($tipo) || empty($titulo) || empty($parte_interessada) || empty($data_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO contratos (numero, tipo, titulo, descricao, parte_interessada, data_inicio, data_fim, valor, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$numero, $tipo, $titulo, $descricao, $parte_interessada, $data_inicio, $data_fim, $valor, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('CONTRATO_CREATE', 'contratos', $pdo->lastInsertId(), null, ['numero' => $numero, 'tipo' => $tipo]);
            
            $success = 'Contrato criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar contrato.';
        }
    }
}

// Criar documento legal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_documento') {
    $tipo = sanitizeInput($_POST['tipo'] ?? 'alvara');
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $numero_registro = sanitizeInput($_POST['numero_registro'] ?? '');
    $orgao_emissor = sanitizeInput($_POST['orgao_emissor'] ?? '');
    $data_emissao = sanitizeInput($_POST['data_emissao'] ?? '');
    $data_validade = sanitizeInput($_POST['data_validade'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($tipo) || empty($titulo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO documentos_legais (tipo, titulo, numero_registro, orgao_emissor, data_emissao, data_validade, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $titulo, $numero_registro, $orgao_emissor, $data_emissao, $data_validade, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('DOCUMENTO_LEGAL_CREATE', 'documentos_legais', $pdo->lastInsertId(), null, ['tipo' => $tipo, 'titulo' => $titulo]);
            
            $success = 'Documento criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar documento.';
        }
    }
}

// Excluir contrato
if (isset($_GET['action']) && $_GET['action'] === 'excluir_contrato' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: contratos.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir contrato.';
    }
}

// Excluir documento
if (isset($_GET['action']) && $_GET['action'] === 'excluir_documento' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM documentos_legais WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: contratos.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir documento.';
    }
}

// Obter contratos
$contratos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM contratos ORDER BY data_inicio DESC");
    $contratos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter contratos: " . $e->getMessage());
}

// Obter documentos legais
$documentos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM documentos_legais ORDER BY data_emissao DESC");
    $documentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter documentos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratos e Documentos | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Contratos e Documentos Legais</h1>
                <p class="text-gray-600 mt-2">Gestão de contratos e documentos</p>
            </div>
            <div class="flex gap-2">
                <button onclick="toggleModalContrato()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Novo Contrato
                </button>
                <button onclick="toggleModalDocumento()" class="px-6 py-3 bg-white border-2 border-azul-principal text-azul-principal font-bold rounded-xl hover:bg-azul-principal hover:text-white transition-all">
                    <i class="fas fa-file-alt mr-2"></i>Novo Documento
                </button>
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

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200">
            <button onclick="showTab('contratos')" id="tab-contratos" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Contratos</button>
            <button onclick="showTab('documentos')" id="tab-documentos" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Documentos Legais</button>
        </div>

        <!-- Tab Contratos -->
        <div id="content-contratos" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Número</th>
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Parte Interessada</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Vigência</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contratos as $contrato): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-mono font-bold text-azul-principal"><?php echo htmlspecialchars($contrato['numero']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($contrato['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst($contrato['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($contrato['parte_interessada']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                        <?php echo date('d/m/Y', strtotime($contrato['data_inicio'])); ?> 
                                        <?php echo $contrato['data_fim'] ? ' a ' . date('d/m/Y', strtotime($contrato['data_fim'])) : ''; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $contrato['status'] === 'ativo' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($contrato['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_contrato&id=<?php echo $contrato['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este contrato?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($contratos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-file-contract text-4xl mb-2"></i>
                        <p>Nenhum contrato cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Documentos -->
        <div id="content-documentos" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Número Registro</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Órgão Emissor</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Validade</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos as $doc): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($doc['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo ucfirst($doc['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($doc['numero_registro'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($doc['orgao_emissor'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $doc['data_validade'] ? date('d/m/Y', strtotime($doc['data_validade'])) : '-'; ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $doc['status'] === 'ativo' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($doc['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_documento&id=<?php echo $doc['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este documento?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($documentos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-file-alt text-4xl mb-2"></i>
                        <p>Nenhum documento cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Contrato -->
    <div id="modal-contrato" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalContrato()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Contrato</h2>
                    <button onclick="toggleModalContrato()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_contrato">
                    
                    <div class="mb-4">
                        <label for="numero" class="block text-sm font-semibold text-gray-700 mb-2">Número *</label>
                        <input type="text" id="numero" name="numero" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: CT-2024-001">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="trabalho">Trabalho</option>
                            <option value="prestacao_servicos">Prestação de Serviços</option>
                            <option value="fornecedor">Fornecedor</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="seguro">Seguro</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título do contrato">
                    </div>
                    
                    <div class="mb-4">
                        <label for="parte_interessada" class="block text-sm font-semibold text-gray-700 mb-2">Parte Interessada *</label>
                        <input type="text" id="parte_interessada" name="parte_interessada" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome da parte interessada">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início *</label>
                            <input type="date" id="data_inicio" name="data_inicio" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="valor" class="block text-sm font-semibold text-gray-700 mb-2">Valor (R$)</label>
                        <input type="number" id="valor" name="valor" step="0.01"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="0.00">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do contrato"></textarea>
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
                        Salvar Contrato
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Novo Documento -->
    <div id="modal-documento" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalDocumento()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Documento Legal</h2>
                    <button onclick="toggleModalDocumento()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_documento">
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="alvara">Alvará</option>
                            <option value="licenca">Licença</option>
                            <option value="registro">Registro</option>
                            <option value="certificado">Certificado</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título do documento">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="numero_registro" class="block text-sm font-semibold text-gray-700 mb-2">Número Registro</label>
                            <input type="text" id="numero_registro" name="numero_registro"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Número do registro">
                        </div>
                        
                        <div>
                            <label for="orgao_emissor" class="block text-sm font-semibold text-gray-700 mb-2">Órgão Emissor</label>
                            <input type="text" id="orgao_emissor" name="orgao_emissor"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Órgão emissor">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_emissao" class="block text-sm font-semibold text-gray-700 mb-2">Data Emissão</label>
                            <input type="date" id="data_emissao" name="data_emissao"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_validade" class="block text-sm font-semibold text-gray-700 mb-2">Data Validade</label>
                            <input type="date" id="data_validade" name="data_validade"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
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
                        Salvar Documento
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

        function toggleModalContrato() {
            const modal = document.getElementById('modal-contrato');
            modal.classList.toggle('hidden');
        }

        function toggleModalDocumento() {
            const modal = document.getElementById('modal-documento');
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
