<?php
require_once 'portal/config.php';

$success = '';
$error = '';
$documentos_enviados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do aluno
    $nome_aluno = sanitizeInput($_POST['nome_aluno'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $cpf_aluno = sanitizeInput($_POST['cpf_aluno'] ?? '');
    $rg_aluno = sanitizeInput($_POST['rg_aluno'] ?? '');
    $sexo = sanitizeInput($_POST['sexo'] ?? '');
    
    // Dados do responsável
    $nome_responsavel = sanitizeInput($_POST['nome_responsavel'] ?? '');
    $cpf_responsavel = sanitizeInput($_POST['cpf_responsavel'] ?? '');
    $rg_responsavel = sanitizeInput($_POST['rg_responsavel'] ?? '');
    $email_responsavel = sanitizeInput($_POST['email_responsavel'] ?? '');
    $telefone_responsavel = sanitizeInput($_POST['telefone_responsavel'] ?? '');
    
    // Endereço
    $endereco = sanitizeInput($_POST['endereco'] ?? '');
    $bairro = sanitizeInput($_POST['bairro'] ?? '');
    $cidade = sanitizeInput($_POST['cidade'] ?? '');
    $estado = sanitizeInput($_POST['estado'] ?? '');
    $cep = sanitizeInput($_POST['cep'] ?? '');
    
    // Dados escolares
    $turma_desejada = sanitizeInput($_POST['turma_desejada'] ?? '');
    $serie_desejada = sanitizeInput($_POST['serie_desejada'] ?? '');
    $escola_origem = sanitizeInput($_POST['escola_origem'] ?? '');
    $ano_letivo = 2026;
    
    // Validações
    if (empty($nome_aluno) || empty($data_nascimento) || empty($nome_responsavel) || empty($cpf_responsavel) || 
        empty($email_responsavel) || empty($telefone_responsavel) || empty($endereco) || empty($bairro) || 
        empty($cidade) || empty($estado) || empty($cep)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isValidEmail($email_responsavel)) {
        $error = 'Email do responsável inválido.';
    } else {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            
            // Inserir pré-matrícula
            $stmt = $pdo->prepare("
                INSERT INTO pre_matriculas (ano_letivo, nome_aluno, data_nascimento, cpf, rg, sexo, nome_responsavel, cpf_responsavel, rg_responsavel, email_responsavel, telefone_responsavel, endereco, bairro, cidade, estado, cep, turma_desejada, serie_desejada, escola_origem, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
            ");
            $stmt->execute([
                $ano_letivo, $nome_aluno, $data_nascimento, $cpf_aluno, $rg_aluno, $sexo,
                $nome_responsavel, $cpf_responsavel, $rg_responsavel, $email_responsavel, $telefone_responsavel,
                $endereco, $bairro, $cidade, $estado, $cep, $turma_desejada, $serie_desejada, $escola_origem
            ]);
            
            $pre_matricula_id = $pdo->lastInsertId();
            
            // Processar uploads de documentos
            $tipos_documento = [
                'certidao_nascimento' => 'Certidão de Nascimento',
                'rg_aluno' => 'RG do Aluno',
                'cpf_aluno' => 'CPF do Aluno',
                'rg_responsavel' => 'RG do Responsável',
                'cpf_responsavel' => 'CPF do Responsável',
                'comprovante_residencia' => 'Comprovante de Residência',
                'historico_escolar' => 'Histórico Escolar',
                'declaracao_matricula' => 'Declaração de Matrícula',
                'carteira_vacinacao' => 'Carteira de Vacinação',
                'exame_sangue' => 'Exame de Sangue',
                'atestado_saude' => 'Atestado de Saúde',
                'foto_3x4' => 'Foto 3x4',
                'comprovante_renda' => 'Comprovante de Renda',
                'contrato_prestacao' => 'Contrato de Prestação',
                'autorizacao_imagem' => 'Autorização de Uso de Imagem',
                'ficha_medica' => 'Ficha Médica',
                'declaracao_responsabilidade' => 'Declaração de Responsabilidade'
            ];
            
            $upload_dir = 'uploads/pre_matriculas/' . $pre_matricula_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            foreach ($tipos_documento as $tipo => $nome) {
                if (isset($_FILES[$tipo]) && $_FILES[$tipo]['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                    $file_type = $_FILES[$tipo]['type'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        $file_extension = pathinfo($_FILES[$tipo]['name'], PATHINFO_EXTENSION);
                        $new_filename = $tipo . '_' . uniqid() . '.' . $file_extension;
                        
                        if (move_uploaded_file($_FILES[$tipo]['tmp_name'], $upload_dir . $new_filename)) {
                            $stmt = $pdo->prepare("
                                INSERT INTO documentos_pre_matricula (pre_matricula_id, tipo_documento, nome_arquivo, caminho_arquivo)
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->execute([$pre_matricula_id, $tipo, $_FILES[$tipo]['name'], $new_filename]);
                            $documentos_enviados[] = $nome;
                        }
                    }
                }
            }
            
            $pdo->commit();
            $success = 'Pré-matrícula enviada com sucesso! Você será notificado sobre o status da sua solicitação.';
            
            // Limpar formulário
            $_POST = [];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro na pré-matrícula: " . $e->getMessage());
            $error = 'Erro ao enviar pré-matrícula. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-Matrícula | Centro Educacional</title>
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
    <header class="gradient-bg shadow-lg">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">VOLTAR PARA</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">SITE PRINCIPAL</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="hidden sm:block text-white">
                        <span class="text-xs sm:text-sm">Ano Letivo 2026</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <div>
                        <p class="font-semibold"><?php echo $success; ?></p>
                        <?php if (count($documentos_enviados) > 0): ?>
                            <p class="text-sm mt-2">Documentos enviados: <?php echo implode(', ', $documentos_enviados); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-semibold"><?php echo $error; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="glass-card rounded-3xl shadow-2xl overflow-hidden">
                <div class="h-24 sm:h-32 gradient-bg flex items-center justify-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10"></div>
                    <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute bottom-10 right-10 w-24 h-24 bg-amarelo-destaque/20 rounded-full blur-2xl"></div>
                    <div class="relative z-10 text-center">
                        <i class="fas fa-user-graduate text-white text-3xl sm:text-4xl mb-2"></i>
                        <h1 class="font-display font-bold text-white text-xl sm:text-2xl">Pré-Matrícula 2026</h1>
                    </div>
                </div>
                
                <div class="p-4 sm:p-8">
                    <div class="text-center mb-6 sm:mb-8">
                        <h2 class="font-display font-bold text-azul-principal text-xl sm:text-2xl mb-2">Formulário de Pré-Matrícula</h2>
                        <p class="text-gray-600 text-xs sm:text-sm">Preencha todos os dados e anexe os documentos necessários</p>
                    </div>
                    
                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6 sm:space-y-8">
                        <!-- Dados do Aluno -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-user"></i>Dados do Aluno
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                                    <input type="text" name="nome_aluno" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Nome completo do aluno">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Nascimento *</label>
                                    <input type="date" name="data_nascimento" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CPF</label>
                                    <input type="text" name="cpf_aluno"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="000.000.000-00">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">RG</label>
                                    <input type="text" name="rg_aluno"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="RG do aluno">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sexo *</label>
                                    <select name="sexo" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                        <option value="">Selecione</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Feminino</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dados do Responsável -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-user-tie"></i>Dados do Responsável
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                                    <input type="text" name="nome_responsavel" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Nome do responsável">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CPF *</label>
                                    <input type="text" name="cpf_responsavel" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="000.000.000-00">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">RG</label>
                                    <input type="text" name="rg_responsavel"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="RG do responsável">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                    <input type="email" name="email_responsavel" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="email@exemplo.com">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone *</label>
                                    <input type="text" name="telefone_responsavel" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="(00) 00000-0000">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Endereço -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-map-marker-alt"></i>Endereço
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Endereço Completo *</label>
                                    <input type="text" name="endereco" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Rua, número, complemento">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bairro *</label>
                                    <input type="text" name="bairro" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Bairro">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CEP *</label>
                                    <input type="text" name="cep" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="00000-000">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Cidade *</label>
                                    <input type="text" name="cidade" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Cidade">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estado *</label>
                                    <select name="estado" required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                                        <option value="">Selecione</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE">Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dados Escolares -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-school"></i>Dados Escolares
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Série Desejada</label>
                                    <input type="text" name="serie_desejada"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Ex: 1º Ano, 2º Ano">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Turma Desejada</label>
                                    <input type="text" name="turma_desejada"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Ex: Turma A, Turma B">
                                </div>
                                
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Escola de Origem</label>
                                    <input type="text" name="escola_origem"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                        placeholder="Nome da escola anterior">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Documentos -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-file-upload"></i>Documentos Obrigatórios
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-600 mb-4">Envie os documentos em formato PDF ou imagem (JPG, PNG). Tamanho máximo: 5MB por arquivo.</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Certidão de Nascimento *</label>
                                    <input type="file" name="certidao_nascimento" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">RG do Aluno</label>
                                    <input type="file" name="rg_aluno" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CPF do Aluno</label>
                                    <input type="file" name="cpf_aluno" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">RG do Responsável *</label>
                                    <input type="file" name="rg_responsavel" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CPF do Responsável *</label>
                                    <input type="file" name="cpf_responsavel" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Comprovante de Residência *</label>
                                    <input type="file" name="comprovante_residencia" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Documentos Adicionais -->
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-azul-principal mb-3 sm:mb-4 flex items-center gap-2">
                                <i class="fas fa-folder-open"></i>Documentos Adicionais
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-600 mb-4">Documentos complementares (quando aplicável)</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Histórico Escolar</label>
                                    <input type="file" name="historico_escolar" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Declaração de Matrícula</label>
                                    <input type="file" name="declaracao_matricula" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Carteira de Vacinação *</label>
                                    <input type="file" name="carteira_vacinacao" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exame de Sangue</label>
                                    <input type="file" name="exame_sangue" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Atestado de Saúde</label>
                                    <input type="file" name="atestado_saude" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Foto 3x4</label>
                                    <input type="file" name="foto_3x4" accept=".jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Comprovante de Renda</label>
                                    <input type="file" name="comprovante_renda" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Contrato de Prestação</label>
                                    <input type="file" name="contrato_prestacao" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Autorização de Uso de Imagem</label>
                                    <input type="file" name="autorizacao_imagem" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ficha Médica</label>
                                    <input type="file" name="ficha_medica" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Declaração de Responsabilidade</label>
                                    <input type="file" name="declaracao_responsabilidade" accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-yellow-600 mt-1"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-semibold mb-1">Informações Importantes:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Documentos marcados com * são obrigatórios</li>
                                        <li>A pré-matrícula será analisada pela coordenação</li>
                                        <li>Você será notificado sobre o status por email</li>
                                        <li>Após aprovação, você deverá comparecer à escola para finalizar a matrícula</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-4 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg text-lg">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Pré-Matrícula
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">© 2026 Centro Educacional. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
