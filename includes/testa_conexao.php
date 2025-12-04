<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$db_host = $_POST['db_host'] ?? 'localhost';
$db_name = $_POST['db_name'] ?? 'estoque_ti';
$db_user = $_POST['db_user'] ?? 'root';
$db_pass = $_POST['db_pass'] ?? '';

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