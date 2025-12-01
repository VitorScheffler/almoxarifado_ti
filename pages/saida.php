<?php
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $quantidade = intval($_POST['quantidade']);
    $usuario_destino = trim($_POST['usuario_destino']);
    $motivo = trim($_POST['motivo']);
    $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');

    if ($item_id <= 0 || $quantidade <= 0 || $usuario_destino === '' || $motivo === '') {
        $erro = 'Preencha todos os campos obrigatórios corretamente.';
    } else {
        $stmt = $pdo->prepare("SELECT quantidade_atual FROM itens WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item || $item['quantidade_atual'] < $quantidade) {
            $erro = 'Estoque insuficiente para essa saída.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO saidas (item_id, quantidade, usuario_destino, motivo, data_hora) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$item_id, $quantidade, $usuario_destino, $motivo, $data_hora]);

            $stmt = $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual - ? WHERE id = ?");
            $stmt->execute([$quantidade, $item_id]);

            header('Location: saida.php?success=1');
            exit;
        }
    }
}

$success = isset($_GET['success']);

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$total_stmt = $pdo->query("SELECT COUNT(*) FROM saidas");
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$stmt = $pdo->prepare("
    SELECT s.*, i.nome as item_nome 
    FROM saidas s 
    JOIN itens i ON s.item_id = i.id 
    ORDER BY s.data_hora DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$saidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <a class="nav-link dropdown-toggle" 
                       href="#" 
                       id="itensDropdown" 
                       role="button"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
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
                    <a class="nav-link dropdown-toggle" 
                       href="#" 
                       id="itensDropdown" 
                       role="button"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
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
                    <a class="nav-link dropdown-toggle" 
                       href="#" 
                       id="itensDropdown" 
                       role="button"
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
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
                    <a class="nav-link" href="entrada.php">
                        <i class="bi bi-box-arrow-in-down"></i> Entradas
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link active" href="saida.php">
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
                <h2 class="mb-0">Registrar Saída de Itens</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> Saída registrada com sucesso!
                    <button type="button" 
                            class="btn-close" 
                            data-bs-dismiss="alert" 
                            aria-label="Close">
                    </button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registrar Saída</h5>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
                            <button type="button" 
                                    class="btn-close" 
                                    data-bs-dismiss="alert" 
                                    aria-label="Close">
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="item_id" class="form-label">Item *</label>
                                <select name="item_id" 
                                        id="item_id" 
                                        class="form-select" 
                                        required>
                                    <option value="">-- Selecione o item --</option>
                                    <?php
                                    $itens = $pdo->query("SELECT id, nome, quantidade_atual FROM itens ORDER BY nome")->fetchAll();
                                    foreach ($itens as $item) {
                                        echo '<option value="' . (int)$item['id'] . '">' 
                                            . htmlspecialchars($item['nome']) 
                                            . ' (Estoque: ' . (int)$item['quantidade_atual'] . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="quantidade" class="form-label">Quantidade *</label>
                                <input type="number"
                                       name="quantidade"
                                       id="quantidade"
                                       class="form-control"
                                       required
                                       min="1"
                                       inputmode="numeric"
                                       placeholder="Quantidade">
                            </div>
                            <div class="col-md-6">
                                <label for="usuario_destino" class="form-label">Usuário Destino *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text"
                                           name="usuario_destino"
                                           id="usuario_destino"
                                           class="form-control"
                                           required
                                           placeholder="Nome do usuário destino">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="motivo" class="form-label">Motivo *</label>
                                <select name="motivo" 
                                        id="motivo" 
                                        class="form-select" 
                                        required>
                                    <option value="">-- Selecione o motivo --</option>
                                    <option value="Substituição">Substituição</option>
                                    <option value="Lixo Eletrônico">Lixo Eletrônico</option>
                                    <option value="Manutenção">Manutenção</option>
                                    <option value="Instalação">Instalação</option>
                                    <option value="Projeto">Projeto</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="data_hora" class="form-label">Data e Hora</label>
                                <input type="datetime-local"
                                       name="data_hora"
                                       id="data_hora"
                                       class="form-control"
                                       value="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea name="observacoes"
                                          id="observacoes"
                                          class="form-control"
                                          rows="2"
                                          placeholder="Observações adicionais (opcional)"></textarea>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-up"></i> Registrar Saída
                            </button>
                            <button type="reset" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> Limpar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Últimas Saídas</h5>
                        <span class="badge bg-primary">
                            Total: <?= $total_items ?> registros
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($saidas): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantidade</th>
                                        <th>Destino</th>
                                        <th>Motivo</th>
                                        <th>Data/Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($saidas as $saida): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($saida['item_nome']) ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= htmlspecialchars($saida['quantidade']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($saida['usuario_destino']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($saida['motivo']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($saida['data_hora'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="bi bi-chevron-left"></i> Anterior
                                    </a>
                                </li>

                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        Próximo <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> 
                            Nenhuma saída registrada ainda.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.getElementById('item_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const text = selectedOption.text;
                const estoqueMatch = text.match(/Estoque: (\d+)/);
                if (estoqueMatch) {
                    const estoque = parseInt(estoqueMatch[1]);
                    document.getElementById('quantidade').max = estoque;
                    document.getElementById('quantidade').setAttribute('title', `Máximo: ${estoque} unidades`);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const dataHoraInput = document.getElementById('data_hora');
            if (dataHoraInput && !dataHoraInput.value) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                dataHoraInput.value = now.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>