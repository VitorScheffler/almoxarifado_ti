<?php
require 'includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: auth/login.php");
    exit();
}

function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erro na consulta: " . $e->getMessage());
        return false;
    }
}

$itens_alerta = [];
$itens_zerados = [];
$saidas = [];

$stmt = executeQuery($pdo, "
    SELECT id, nome, quantidade_atual, quantidade_minima 
    FROM itens 
    WHERE quantidade_atual <= quantidade_minima AND quantidade_minima > 0
    ORDER BY quantidade_atual ASC
");

if ($stmt) {
    $itens_alerta = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = executeQuery($pdo, "
    SELECT id, nome 
    FROM itens 
    WHERE quantidade_atual = 0
    ORDER BY nome
");

if ($stmt) {
    $itens_zerados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stmt = executeQuery($pdo, "
    SELECT s.id, i.nome as item_nome, s.quantidade, s.usuario_destino, s.data_hora
    FROM saidas s
    JOIN itens i ON s.item_id = i.id
    WHERE s.data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY s.data_hora DESC
");

if ($stmt) {
    $saidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

usort($itens_alerta, function ($a, $b) {
    $prioridade = function ($item) {
        if ($item['quantidade_atual'] == 0) return 0;
        if ($item['quantidade_atual'] < $item['quantidade_minima']) return 1;
        if ($item['quantidade_atual'] == $item['quantidade_minima']) return 2;
        return 3;
    };
    return $prioridade($a) <=> $prioridade($b);
});
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="shortcut icon" href="assets/img/Coopershoes.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <div class="sidebar p-3">
            <h4 class="text-center mb-4">Almoxarifado TI</h4>
            <hr class="bg-light">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" 
                    href="../index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['pages/fornecedores_cadastrados.php', 'pages/fornecedores.php'])) ? 'active' : '' ?>" 
                    href="#" 
                    id="fornecedoresDropdown" 
                    role="button"
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Fornecedores
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="fornecedoresDropdown">
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/fornecedores_cadastrados.php') ? 'active' : '' ?>" 
                            href="pages/fornecedores_cadastrados.php">
                                <i class="bi bi-truck"></i> Fornecedores Cadastrados
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/fornecedores.php') ? 'active' : '' ?>" 
                            href="pages/fornecedores.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Fornecedor
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['pages/computadores_cadastrados.php', 'pages/computadores.php'])) ? 'active' : '' ?>" 
                    href="#" 
                    id="computadoresDropdown" 
                    role="button"
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                        <i class="bi bi-laptop"></i> Computadores
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="computadoresDropdown">
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/computadores_cadastrados.php') ? 'active' : '' ?>" 
                            href="pages/computadores_cadastrados.php">
                                <i class="bi bi-laptop"></i> Computadores
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/computadores.php') ? 'active' : '' ?>" 
                            href="pages/computadores.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Computadores
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['pages/itens_cadastrados.php', 'pages/itens.php'])) ? 'active' : '' ?>" 
                    href="#" 
                    id="itensDropdown" 
                    role="button"
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Itens
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="itensDropdown">
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/itens_cadastrados.php') ? 'active' : '' ?>" 
                            href="pages/itens_cadastrados.php">
                                <i class="bi bi-archive"></i> Itens Cadastrados
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'pages/itens.php') ? 'active' : '' ?>" 
                            href="pages/itens.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Itens
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'pages/estoque.php') ? 'active' : '' ?>" 
                    href="pages/estoque.php">
                        <i class="bi bi-archive"></i> Estoque
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'pages/entrada.php') ? 'active' : '' ?>" 
                    href="pages/entrada.php">
                        <i class="bi bi-box-arrow-in-down"></i> Entradas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'pages/saida.php') ? 'active' : '' ?>" 
                    href="pages/saida.php">
                        <i class="bi bi-box-arrow-up"></i> Saídas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_atual ?? '') == 'pages/configuracoes.php' ? 'active' : '' ?>" 
                    href="pages/configuracoes.php">
                        <i class="bi bi-gear"></i> Configurações
                    </a>
                </li>

                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </li>
            </ul>
        </div>

        <div class="main-content">
            <div class="container-fluid p-4">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Itens no Estoque</h6>
                                        <h4 class="mb-0"><?= $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn(); ?></h4>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box-seam text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Itens com Alerta</h6>
                                        <h4 class="mb-0"><?= count($itens_alerta) ?></h4>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Itens Zerados</h6>
                                        <h4 class="mb-0"><?= count($itens_zerados) ?></h4>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box text-danger" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Saídas (7 dias)</h6>
                                        <h4 class="mb-0"><?= count($saidas) ?></h4>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box-arrow-up text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-exclamation-triangle text-warning"></i> 
                                    Itens com Alerta de Estoque
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($itens_alerta) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-warning">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qtd Atual</th>
                                                    <th>Qtd Mínima</th>
                                                    <th>Status</th>
                                                    <th>Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($itens_alerta as $item): ?>
                                                    <tr class="<?= $item['quantidade_atual'] == 0 ? 'table-danger' : '' ?>">
                                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                                        <td>
                                                            <span class="badge <?= $item['quantidade_atual'] == 0 ? 'bg-danger' : 'bg-warning' ?>">
                                                                <?= $item['quantidade_atual'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= $item['quantidade_minima'] ?></td>
                                                        <td>
                                                            <?php if ($item['quantidade_atual'] == 0): ?>
                                                                <span class="badge bg-danger">Zerado</span>
                                                            <?php elseif ($item['quantidade_atual'] < $item['quantidade_minima']): ?>
                                                                <span class="badge bg-danger">Abaixo do mínimo</span>
                                                            <?php elseif ($item['quantidade_atual'] == $item['quantidade_minima']): ?>
                                                                <span class="badge bg-warning text-dark">Mínimo</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Estoque OK</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="pages/entrada.php" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-box-arrow-in-down"></i> Repor
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="bi bi-check-circle"></i> 
                                        Nenhum item em alerta. Estoque está em bom estado!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history text-info"></i> 
                                    Últimas Saídas (7 dias)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($saidas) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-info">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Quantidade</th>
                                                    <th>Destino</th>
                                                    <th>Data/Hora</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($saidas as $saida): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($saida['item_nome']) ?></td>
                                                        <td>
                                                            <span class="badge bg-danger">
                                                                <?= $saida['quantidade'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($saida['usuario_destino']) ?></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($saida['data_hora'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> 
                                        Nenhuma saída registrada nos últimos 7 dias.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const navbarToggler = document.querySelector('.navbar-toggler');
        if (navbarToggler) {
            navbarToggler.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
        }
    </script>
</body>
</html>