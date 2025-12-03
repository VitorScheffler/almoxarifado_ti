<?php
require '../includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Consulta para exportação
$sql = "SELECT 
            nome, 
            categoria, 
            descricao, 
            quantidade_atual, 
            quantidade_minima,
            valor_unitario,
            localizacao,
            codigo_barras,
            data_cadastro
        FROM itens 
        ORDER BY nome ASC";

$stmt = $pdo->query($sql);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir cabeçalhos para download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=estoque_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Cabeçalho do CSV
fputcsv($output, [
    'Nome', 
    'Categoria', 
    'Descrição', 
    'Quantidade Atual', 
    'Quantidade Mínima',
    'Valor Unitário',
    'Localização',
    'Código de Barras',
    'Data de Cadastro'
], ';');

// Dados
foreach ($itens as $item) {
    fputcsv($output, [
        $item['nome'],
        $item['categoria'],
        $item['descricao'],
        $item['quantidade_atual'],
        $item['quantidade_minima'],
        $item['valor_unitario'] ? 'R$ ' . number_format($item['valor_unitario'], 2, ',', '.') : '',
        $item['localizacao'],
        $item['codigo_barras'],
        date('d/m/Y', strtotime($item['data_cadastro']))
    ], ';');
}

fclose($output);
exit;