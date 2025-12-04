<?php
header('Content-Type: application/json');
require '../includes/config.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['existe' => false, 'error' => 'Não autenticado']);
    exit;
}

$patrimonio = $_GET['patrimonio'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (empty($patrimonio)) {
    echo json_encode(['existe' => false]);
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ? AND id != ?");
        $stmt->execute([$patrimonio, $id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ?");
        $stmt->execute([$patrimonio]);
    }
    
    $existe = $stmt->fetch() !== false;
    echo json_encode(['existe' => $existe]);
    
} catch (PDOException $e) {
    echo json_encode(['existe' => false, 'error' => $e->getMessage()]);
}
?>