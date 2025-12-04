<?php
// Inclui config primeiro para ter acesso às funções
require 'config.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

header('Content-Type: application/json');

// Recebe dados do formulário ou usa do arquivo de configuração
$db_host = $_POST['db_host'] ?? DB_HOST;
$db_name = $_POST['db_name'] ?? DB_NAME;
$db_user = $_POST['db_user'] ?? DB_USER;
$db_pass = $_POST['db_pass'] ?? DB_PASS;

try {
    $test_pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8", 
        $db_user, 
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Testa algumas consultas básicas
    $test_pdo->query("SELECT 1");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Conexão com banco de dados bem sucedida!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro na conexão: ' . $e->getMessage()
    ]);
}
?>