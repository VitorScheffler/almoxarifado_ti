<?php
$pagina_atual = 'entrada.php';
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

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Registrar Entrada de Itens</h2>
</div>

<?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> Entrada registrada com sucesso!
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

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

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Dados da Entrada</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="item_id" class="form-label">Item *</label>
                    <select name="item_id" 
                            id="item_id" 
                            class="form-select" 
                            required>
                        <option value="">-- Selecione o item --</option>
                        <?php foreach ($itens as $i): ?>
                            <option value="<?= $i['id'] ?>">
                                <?= htmlspecialchars($i['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="quantidade" class="form-label">Quantidade *</label>
                    <input type="number" 
                           name="quantidade" 
                           id="quantidade" 
                           min="1" 
                           class="form-control" 
                           required
                           inputmode="numeric"
                           placeholder="Quantidade">
                </div>
                
                <div class="col-md-6">
                    <label for="fornecedor_id" class="form-label">Fornecedor *</label>
                    <select name="fornecedor_id" 
                            id="fornecedor_id" 
                            class="form-select" 
                            required>
                        <option value="">-- Selecione o fornecedor --</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= $f['id'] ?>">
                                <?= htmlspecialchars($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
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
                    <label for="nota_fiscal" class="form-label">Nota Fiscal *</label>
                    <input type="text" 
                           name="nota_fiscal" 
                           id="nota_fiscal" 
                           class="form-control" 
                           required
                           placeholder="Número da nota fiscal">
                </div>
                
                <div class="col-md-6">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" 
                              id="observacoes" 
                              class="form-control" 
                              rows="1"
                              placeholder="Observações sobre a entrada (opcional)"></textarea>
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
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Últimas Entradas</h5>
            <span class="text-muted">Total: <?= $total_items ?> registros</span>
        </div>
    </div>
    <div class="card-body">
        <?php if ($entradas): ?>
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
                        <?php foreach ($entradas as $entrada): ?>
                            <tr>
                                <td><?= htmlspecialchars($entrada['item_nome']) ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?= htmlspecialchars($entrada['quantidade']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($entrada['fornecedor']) ?></td>
                                <td><?= htmlspecialchars($entrada['nota_fiscal']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($entrada['data_hora'])) ?></td>
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
                <i class="bi bi-info-circle"></i> Nenhuma entrada registrada ainda.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dataHoraInput = document.getElementById('data_hora');
    if (dataHoraInput && !dataHoraInput.value) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dataHoraInput.value = now.toISOString().slice(0, 16);
    }
});
</script>
<?php
$conteudo = ob_get_clean();
$titulo = "Registrar Entrada - Almoxarifado TI";

include '../includes/template.php';