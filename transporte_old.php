<?php
require_once 'portal/config.php';

// Criar tabela de rotas de transporte se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS rotas_transporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    bairros_atendidos TEXT,
    horario_saida TIME NOT NULL,
    horario_chegada TIME,
    valor_mensal DECIMAL(10,2),
    telefone_contato VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter rotas de transporte
$rotas = [];
try {
    $stmt = $pdo->query("SELECT * FROM rotas_transporte WHERE ativo = 1 ORDER BY nome");
    $rotas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter rotas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transporte Escolar | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-[0_8px_30px_rgb(0,0,0,0.5)] sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">TRANSPORTE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">ESCOLAR</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-6 py-2.5 bg-white/5 border border-white/10 backdrop-blur-sm/20 text-white rounded-full font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/30 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Banner -->
        <div class="bg-gradient-to-r from-azul-principal to-verde-complementar rounded-3xl p-8 mb-12 text-center">
            <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">
                <i class="fas fa-bus mr-3"></i>Transporte Escolar
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Informações sobre as rotas de transporte escolar, horários e valores.
            </p>
        </div>

        <!-- Informações Gerais -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Informações Gerais
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Segurança</h3>
                    <p class="text-gray-400 text-sm">Transporte seguro com monitoramento e profissionais qualificados.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Pontualidade</h3>
                    <p class="text-gray-400 text-sm">Horários estabelecidos para garantir pontualidade nas aulas.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Acompanhamento</h3>
                    <p class="text-gray-400 text-sm">Controle de entrada e saída dos alunos em cada parada.</p>
                </div>
            </div>
        </div>

        <!-- Rotas Disponíveis -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-route mr-2 text-amarelo-destaque"></i>Rotas Disponíveis
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <?php if (count($rotas) > 0): ?>
                    <?php foreach ($rotas as $rota): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-bus text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg"><?php echo htmlspecialchars($rota['nome']); ?></h3>
                                    <?php if ($rota['descricao']): ?>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($rota['descricao']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <?php if ($rota['bairros_atendidos']): ?>
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-amarelo-destaque mt-1"></i>
                                        <div>
                                            <span class="text-gray-400 text-sm">Bairros atendidos:</span>
                                            <p class="text-white text-sm"><?php echo htmlspecialchars($rota['bairros_atendidos']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm">Saída:</span>
                                        <span class="text-white text-sm font-semibold"><?php echo date('H:i', strtotime($rota['horario_saida'])); ?></span>
                                    </div>
                                    <?php if ($rota['horario_chegada']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-flag-checkered text-amarelo-destaque"></i>
                                            <span class="text-gray-400 text-sm">Chegada:</span>
                                            <span class="text-white text-sm font-semibold"><?php echo date('H:i', strtotime($rota['horario_chegada'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($rota['valor_mensal']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-dollar-sign text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm">Valor mensal:</span>
                                        <span class="text-verde-complementar text-sm font-bold">R$ <?php echo number_format($rota['valor_mensal'], 2, ',', '.'); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($rota['telefone_contato']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm">Contato:</span>
                                        <span class="text-white text-sm"><?php echo htmlspecialchars($rota['telefone_contato']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 text-center py-12 text-gray-400">
                        <i class="fas fa-bus text-4xl mb-4"></i>
                        <p class="text-lg">Nenhuma rota cadastrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Como Contratar -->
        <div class="mt-16 bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-handshake mr-2 text-amarelo-destaque"></i>Como Contratar
            </h2>
            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-amarelo-destaque rounded-full flex items-center justify-center mx-auto mb-4 text-azul-escuro font-bold text-xl">1</div>
                    <h3 class="text-white font-semibold mb-2">Entre em Contato</h3>
                    <p class="text-gray-400 text-sm">Fale com a secretaria para verificar disponibilidade.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-amarelo-destaque rounded-full flex items-center justify-center mx-auto mb-4 text-azul-escuro font-bold text-xl">2</div>
                    <h3 class="text-white font-semibold mb-2">Escolha a Rota</h3>
                    <p class="text-gray-400 text-sm">Selecione a rota que melhor atende seu endereço.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-amarelo-destaque rounded-full flex items-center justify-center mx-auto mb-4 text-azul-escuro font-bold text-xl">3</div>
                    <h3 class="text-white font-semibold mb-2">Formalize</h3>
                    <p class="text-gray-400 text-sm">Preencha o contrato de transporte escolar.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-amarelo-destaque rounded-full flex items-center justify-center mx-auto mb-4 text-azul-escuro font-bold text-xl">4</div>
                    <h3 class="text-white font-semibold mb-2">Inicie</h3>
                    <p class="text-gray-400 text-sm">O aluno começa a utilizar o transporte.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> [Inserir nome da escola aqui]. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>

