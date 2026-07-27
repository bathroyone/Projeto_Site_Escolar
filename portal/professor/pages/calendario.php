<?php
require_once '../../config.php';
requireLogin();

if (!isProfessor()) {
    header('Location: ../../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Calendário Pessoal</h2>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
    <i class="fas fa-calendar text-gray-300 text-6xl mb-4"></i>
    <h3 class="text-lg font-medium text-gray-600 mb-2">Integração com Calendário Pessoal</h3>
    <p class="text-gray-500">Esta funcionalidade está em desenvolvimento.</p>
</div>
