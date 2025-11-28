<?php
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';
$sucesso = isset($_GET['success']);

$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$itens = $pdo->query("SELECT id, nome FROM itens ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $quantidade = intval($_POST['quantidade']);
    $fornecedor_id = intval($_POST['fornecedor_id']);
    $nota_fiscal = trim($_POST['nota_fiscal']);
    $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');

    if ($item_id <= 0 || $quantidade <= 0 || $fornecedor_id <= 0 || $nota_fiscal === '') {
        $erro = 'Preencha todos os campos obrigatórios corretamente.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO entradas (item_id, quantidade, fornecedor_id, nota_fiscal, data_hora) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item_id, $quantidade, $fornecedor_id, $nota_fiscal, $data_hora]);

            $stmt = $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual + ? WHERE id = ?");
            $stmt->execute([$quantidade, $item_id]);

            header('Location: entrada.php?success=1');
            exit;
        } catch (PDOException $e) {
            $erro = 'Erro ao registrar entrada: ' . $e->getMessage();
        }
    }
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$total_stmt = $pdo->query("SELECT COUNT(*) FROM entradas");
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$stmt = $pdo->prepare("
    SELECT e.*, i.nome as item_nome, f.nome as fornecedor 
    FROM entradas e 
    JOIN itens i ON e.item_id = i.id 
    JOIN fornecedores f ON e.fornecedor_id = f.id
    ORDER BY e.data_hora DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almoxarifado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="shortcut icon" href="../assets/img/Coopershoes.png" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <div class="sidebar p-3">
            <h4 class="text-center mb-4">Almoxarifado TI</h4>
            <hr class="bg-light">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="itensDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Fornecedores
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="itensDropdown">
                        <li>
                            <a class="dropdown-item" href="fornecedores_cadastrados.php">
                                <i class="bi bi-truck"></i> Fornecedores Cadastrados
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="fornecedores.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Fornecedor
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="itensDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-laptop"></i> Computadores
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="itensDropdown">
                        <li>
                            <a class="dropdown-item" href="computadores_cadastrados.php">
                                <i class="bi bi-laptop"></i> Computadores
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="computadores.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Computadores
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="itensDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Itens
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="itensDropdown">
                        <li>
                            <a class="dropdown-item" href="estoque.php">
                                <i class="bi bi-archive"></i> Estoque
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="itens.php">
                                <i class="bi bi-plus-square"></i> Cadastrar Itens
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="entrada.php">
                        <i class="bi bi-box-arrow-in-down"></i> Entradas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="saida.php">
                        <i class="bi bi-box-arrow-up"></i> Saídas
                    </a>
                </li>

                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </li>

            </ul>
        </div>

        <div class="main-content p-4" style="flex-grow:1;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Registrar Entrada de Itens</h2>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Entrada registrada com sucesso!
                </div>
            <?php endif; ?>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dados da Entrada</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="item_id" class="form-label">Item</label>
                                <select name="item_id" id="item_id" class="form-select" required>
                                    <option value="">-- Selecione o item --</option>
                                    <?php foreach ($itens as $i): ?>
                                        <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" name="quantidade" id="quantidade" min="1" class="form-control" required />
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fornecedor_id" class="form-label">Fornecedor</label>
                                <select name="fornecedor_id" id="fornecedor_id" class="form-select" required>
                                    <option value="">-- Selecione o fornecedor --</option>
                                    <?php foreach ($fornecedores as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="data_hora" class="form-label">Data e Hora</label>
                                <input type="datetime-local" name="data_hora" id="data_hora" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" />
                            </div>
                            
                            <div class="col-md-6">
                                <label for="nota_fiscal" class="form-label">Nota Fiscal</label>
                                <input type="text" name="nota_fiscal" id="nota_fiscal" class="form-control" required />
                            </div>
                            
                            <div class="col-md-6">
                                <label for="observacoes" class="form-label">Observações (opcional)</label>
                                <textarea name="observacoes" id="observacoes" class="form-control" rows="1"></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-down"></i> Registrar Entrada
                                </button>
                                <button type="reset" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Últimas Entradas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantidade</th>
                                    <th>Fornecedor</th>
                                    <th>Nota Fiscal</th>
                                    <th>Data/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($entradas): ?>
                                    <?php foreach ($entradas as $entrada): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($entrada['item_nome']) ?></td>
                                            <td><?= htmlspecialchars($entrada['quantidade']) ?></td>
                                            <td><?= htmlspecialchars($entrada['fornecedor']) ?></td>
                                            <td><?= htmlspecialchars($entrada['nota_fiscal']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($entrada['data_hora'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Nenhuma entrada registrada recentemente
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" tabindex="-1">Anterior</a>
                            </li>

                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Próximo</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>