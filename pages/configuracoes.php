<?php
// Padrão: saida.php
$pagina_atual = 'saida.php';
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';
$sucesso = isset($_GET['success']);

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'numero':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        case 'texto':
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}


// Início do conteúdo
ob_start();
?>

<?php
$conteudo = ob_get_clean();
$titulo = "Configurações - Sistema Almoxarifado";
$pagina_atual = 'configuracoes.php';

include '../includes/template.php';