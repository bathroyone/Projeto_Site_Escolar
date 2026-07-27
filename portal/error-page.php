<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Sistema de Gestão Escolar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Ocorreu um Erro</h1>
        <p class="text-gray-600 mb-6">
            Desculpe, ocorreu um erro inesperado. Nossa equipe foi notificada e estamos trabalhando para resolver o problema.
        </p>
        <div class="space-y-3">
            <a href="dashboard.php" class="block w-full py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <i class="fas fa-home mr-2"></i>Voltar ao Dashboard
            </a>
            <a href="login.php" class="block w-full py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                <i class="fas fa-sign-in-alt mr-2"></i>Fazer Login Novamente
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-6">
            Se o problema persistir, entre em contato com o suporte técnico.
        </p>
    </div>
</body>
</html>
