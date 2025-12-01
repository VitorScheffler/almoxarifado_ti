<?php
require '../includes/config.php';

$item = [];
$erro = '';
$sucesso = '';

if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    if ($id_edit > 0) {
        $stmt = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
        $stmt->execute([$id_edit]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            $erro = "Item não encontrado.";
            $id_edit = 0;
        }
    }
} else {
    $id_edit = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dados = [
        'id' => isset($_POST['id']) ? (int)$_POST['id'] : 0,
        'nome' => trim($_POST['nome'] ?? ''),
        'codigo_interno' => trim($_POST['codigo_interno'] ?? ''),
        'quantidade_minima' => (int)($_POST['quantidade_minima'] ?? 0),
        'unidade' => trim($_POST['unidade'] ?? 'unidade')
    ];

    if (empty($dados['nome'])) {
        $erro = "O campo nome é obrigatório.";
    }

    if (!$erro) {
        try {
            if ($dados['id'] > 0) {
                $stmt = $pdo->prepare("UPDATE itens SET nome = ?, codigo_interno = ?, quantidade_minima = ?, unidade = ? WHERE id = ?");
                $stmt->execute([
                    $dados['nome'],
                    $dados['codigo_interno'],
                    $dados['quantidade_minima'],
                    $dados['unidade'],
                    $dados['id']
                ]);
                $sucesso = "Item atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO itens (nome, codigo_interno, quantidade_minima, unidade, quantidade_atual) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([
                    $dados['nome'],
                    $dados['codigo_interno'],
                    $dados['quantidade_minima'],
                    $dados['unidade']
                ]);
                $sucesso = "Item cadastrado com sucesso!";
            }
            
            if ($sucesso) {
                $item = [];
                $id_edit = 0;
                header("Location: estoque.php");
                exit();
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar item: " . $e->getMessage();
        }
    }
}

if (isset($_GET['del'])) {
    $id_del = (int)$_GET['del'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
        $stmt->execute([$id_del]);
        
        if ($stmt->rowCount() > 0) {
            $sucesso = "Item excluído com sucesso!";
            $pdo->commit();
            header("Location: estoque.php?success=1");
            exit();
        } else {
            $erro = "Nenhum item foi excluído (ID não encontrado).";
            $pdo->rollBack();
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro = "Erro ao excluir: " . $e->getMessage();
    }
}

try {
    $itens = $pdo->query("
        SELECT i.*, 
               (i.quantidade_atual <= i.quantidade_minima) AS alerta
        FROM itens i 
        ORDER BY i.nome
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar itens: " . $e->getMessage();
    $itens = [];
}
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="d-flex">
        <?php include '../includes/menu.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Estoque</h2>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $erro ?>
                    <button type="button" 
                            class="btn-close" 
                            data-bs-dismiss="alert" 
                            aria-label="Close">
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Item excluído com sucesso!
                    <button type="button" 
                            class="btn-close" 
                            data-bs-dismiss="alert" 
                            aria-label="Close">
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($id_edit > 0 || isset($_GET['new'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?= $id_edit > 0 ? 'Editar Item' : 'Cadastrar Novo Item' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="estoque.php">
                            <input type="hidden" name="id" value="<?= $item['id'] ?? 0 ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome do Item *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nome" 
                                           name="nome" 
                                           value="<?= htmlspecialchars($item['nome'] ?? '') ?>" 
                                           required
                                           placeholder="Nome do item">
                                </div>
                                <div class="col-md-6">
                                    <label for="codigo_interno" class="form-label">Código Interno</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="codigo_interno" 
                                           name="codigo_interno" 
                                           value="<?= htmlspecialchars($item['codigo_interno'] ?? '') ?>"
                                           placeholder="Código interno (opcional)">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="quantidade_minima" 
                                           name="quantidade_minima" 
                                           value="<?= $item['quantidade_minima'] ?? 1 ?>" 
                                           min="1"
                                           inputmode="numeric">
                                </div>

                                <div class="col-md-4">
                                    <label for="unidade" class="form-label">Unidade</label>
                                    <select class="form-select" id="unidade" name="unidade">
                                        <option value="unidade" <?= ($item['unidade'] ?? 'unidade') === 'unidade' ? 'selected' : '' ?>>Unidade</option>
                                        <option value="caixa" <?= ($item['unidade'] ?? '') === 'caixa' ? 'selected' : '' ?>>Caixa</option>
                                        <option value="par" <?= ($item['unidade'] ?? '') === 'par' ? 'selected' : '' ?>>Par</option>
                                        <option value="litro" <?= ($item['unidade'] ?? '') === 'litro' ? 'selected' : '' ?>>Litro</option>
                                        <option value="kg" <?= ($item['unidade'] ?? '') === 'kg' ? 'selected' : '' ?>>Quilograma</option>
                                        <option value="metro" <?= ($item['unidade'] ?? '') === 'metro' ? 'selected' : '' ?>>Metro</option>
                                        <option value="rolo" <?= ($item['unidade'] ?? '') === 'rolo' ? 'selected' : '' ?>>Rolo</option>
                                        <option value="pacote" <?= ($item['unidade'] ?? '') === 'pacote' ? 'selected' : '' ?>>Pacote</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <a href="estoque.php" class="btn btn-secondary me-2">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?= $id_edit > 0 ? 'Atualizar' : 'Cadastrar' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Itens em estoque</h5>
                    <div>
                        <span class="badge bg-primary me-2"><?= count($itens) ?> itens</span>
                        <a href="?new=1" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-lg"></i> Novo Item
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($itens)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Nenhum item cadastrado.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="tabela-itens" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Código</th>
                                        <th>Qtd. Atual</th>
                                        <th>Qtd. Mínima</th>
                                        <th>Unidade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $i): ?>
                                        <tr class="<?= $i['alerta'] ? 'table-warning' : '' ?>">
                                            <td><?= htmlspecialchars($i['nome']) ?></td>
                                            <td><?= !empty($i['codigo_interno']) ? htmlspecialchars($i['codigo_interno']) : '<span class="text-muted">-</span>' ?></td>
                                            <td>
                                                <span class="badge <?= $i['quantidade_atual'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $i['quantidade_atual'] ?>
                                                </span>
                                            </td>
                                            <td><?= $i['quantidade_minima'] ?></td>
                                            <td><?= htmlspecialchars($i['unidade']) ?></td>
                                            <td>
                                                <?php if ($i['alerta']): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-exclamation-triangle"></i> Baixo estoque
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Normal
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?edit=<?= $i['id'] ?>" class="btn btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?del=<?= $i['id'] ?>" class="btn btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir este item?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function () {
        $('#tabela-itens').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [5, 6] }
            ],
            pageLength: 10,
            responsive: true
        });
    });

    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    </script>
</body>
</html>