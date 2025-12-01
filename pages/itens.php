<?php
require '../includes/config.php';

$item = [];
$erro = '';
$sucesso = '';

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $sucesso = "Item cadastrado com sucesso!";
}

if (!isset($_GET['edit'])) {
    try {
        $stmt = $pdo->query("SELECT MAX(CAST(codigo_interno AS UNSIGNED)) as max_codigo FROM itens");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $proximo_codigo = ($resultado['max_codigo'] ?? 0) + 1;
    } catch (PDOException $e) {
        $proximo_codigo = 1;
    }
}

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
        'descricao' => trim($_POST['descricao'] ?? ''),
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
                $stmt = $pdo->prepare("UPDATE itens SET nome = ?, descricao = ?, codigo_interno = ?, quantidade_minima = ?, unidade = ? WHERE id = ?");
                $stmt->execute([
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['codigo_interno'],
                    $dados['quantidade_minima'],
                    $dados['unidade'],
                    $dados['id']
                ]);
                $sucesso = "Item atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO itens (nome, descricao, codigo_interno, quantidade_minima, unidade, quantidade_atual) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->execute([
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['codigo_interno'],
                    $dados['quantidade_minima'],
                    $dados['unidade']
                ]);
                $sucesso = "Item cadastrado com sucesso!";

                header("Location: itens.php?success=1");
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
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM entradas WHERE item_id = ?");
        $stmt->execute([$id_del]);
        $total_entradas = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM saidas WHERE item_id = ?");
        $stmt->execute([$id_del]);
        $total_saidas = $stmt->fetchColumn();

        $estoque = $total_entradas - $total_saidas;

        if ($estoque > 0) {
            $erro = "Não é possível excluir o item. Ainda há $estoque unidades em estoque.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
            $stmt->execute([$id_del]);

            $sucesso = "Item excluído com sucesso!";
        }

    } catch (PDOException $e) {
        $erro = "Erro ao excluir item: " . $e->getMessage();
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
                    <a class="nav-link active dropdown-toggle" 
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

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Cadastrar novos itens</h2>
                <?php if (!$id_edit): ?>
                    <span class="badge bg-info">
                        <i class="bi bi-info-circle"></i> Próximo código: <?= $proximo_codigo ?? '1' ?>
                    </span>
                <?php endif; ?>
            </div>

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
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($sucesso) ?>
                    <button type="button" 
                            class="btn-close" 
                            data-bs-dismiss="alert" 
                            aria-label="Close">
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $id_edit ? 'Editar Item' : 'Cadastrar novo item' ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       value="<?= htmlspecialchars($item['nome'] ?? '') ?>" 
                                       required
                                       placeholder="Nome do item">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="codigo_interno" class="form-label">Código do Item</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="codigo_interno" 
                                       name="codigo_interno" 
                                       value="<?= isset($item['codigo_interno']) ? htmlspecialchars($item['codigo_interno']) : ($proximo_codigo ?? '') ?>"
                                       placeholder="Código interno (opcional)"
                                       inputmode="numeric"
                                       pattern="[0-9]*">
                            </div>
                            
                            <div class="col-12">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" 
                                          id="descricao" 
                                          name="descricao" 
                                          rows="2"
                                          placeholder="Descrição detalhada do item (opcional)"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="quantidade_minima" 
                                       name="quantidade_minima" 
                                       min="0" 
                                       value="<?= $item['quantidade_minima'] ?? 0 ?>"
                                       inputmode="numeric">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="unidade" class="form-label">Unidade de Medida</label>
                                <select class="form-select" id="unidade" name="unidade">
                                    <option value="unidade" <?= ($item['unidade'] ?? 'unidade') == 'unidade' ? 'selected' : '' ?>>Unidade</option>
                                    <option value="caixa" <?= ($item['unidade'] ?? '') == 'caixa' ? 'selected' : '' ?>>Caixa</option>
                                    <option value="litro" <?= ($item['unidade'] ?? '') == 'litro' ? 'selected' : '' ?>>Litro</option>
                                    <option value="kg" <?= ($item['unidade'] ?? '') == 'kg' ? 'selected' : '' ?>>Quilograma</option>
                                    <option value="metro" <?= ($item['unidade'] ?? '') == 'metro' ? 'selected' : '' ?>>Metro</option>
                                    <option value="rolo" <?= ($item['unidade'] ?? '') == 'rolo' ? 'selected' : '' ?>>Rolo</option>
                                    <option value="pacote" <?= ($item['unidade'] ?? '') == 'pacote' ? 'selected' : '' ?>>Pacote</option>
                                    <option value="par" <?= ($item['unidade'] ?? '') == 'par' ? 'selected' : '' ?>>Par</option>
                                    <option value="conjunto" <?= ($item['unidade'] ?? '') == 'conjunto' ? 'selected' : '' ?>>Conjunto</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-save"></i> <?= $id_edit ? 'Atualizar' : 'Cadastrar' ?>
                                </button>
                                <button type="reset" class="btn btn-danger me-2">
                                    <i class="bi bi-x-circle"></i> Limpar
                                </button>
                                <?php if ($id_edit): ?>
                                    <a href="itens.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Voltar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nome').focus();
        });

        document.getElementById('codigo_interno').addEventListener('blur', function(e) {
            if (!e.target.value.trim() && <?= !$id_edit ? 'true' : 'false' ?>) {
                e.target.value = <?= $proximo_codigo ?? 1 ?>;
            }
        });
    </script>
</body>
</html>