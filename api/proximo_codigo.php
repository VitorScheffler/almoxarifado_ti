<?php
require '../includes/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT MAX(CAST(codigo_interno AS UNSIGNED)) as max_codigo FROM itens WHERE codigo_interno REGEXP '^[0-9]+$'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $proximo_codigo = $result['max_codigo'] ? intval($result['max_codigo']) + 1 : 1000;
    
    echo json_encode(['codigo' => $proximo_codigo]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar próximo código']);
}