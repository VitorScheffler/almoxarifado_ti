<?php
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado)
{
    $dado = trim($dado);
    $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    return $dado;
}

$id_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$computador = null;

if ($id_edit > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM computadores WHERE id = ?");
        $stmt->execute([$id_edit]);
        $computador = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$computador) {
            $erro = "Computador não encontrado.";
            $id_edit = 0;
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar computador: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor = limparDados($_POST['setor'] ?? '');
    $patrimonio_computador = limparDados($_POST['patrimonio_computador'] ?? '');
    $patrimonio_monitor1 = limparDados($_POST['patrimonio_monitor1'] ?? '');
    $patrimonio_monitor2 = limparDados($_POST['patrimonio_monitor2'] ?? '');
    $usuario = limparDados($_POST['usuario'] ?? '');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (empty($setor)) {
        $erro = "O campo setor é obrigatório.";
    } elseif (empty($patrimonio_computador)) {
        $erro = "O campo patrimônio do computador é obrigatório.";
    } elseif (strlen($setor) > 100) {
        $erro = "O setor deve ter no máximo 100 caracteres.";
    } elseif (strlen($patrimonio_computador) > 100) {
        $erro = "O patrimônio do computador deve ter no máximo 100 caracteres.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ? AND id != ?");
                $stmt->execute([$patrimonio_computador, $id]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    $stmt = $pdo->prepare("UPDATE computadores SET setor=?, patrimonio_computador=?, patrimonio_monitor1=?, patrimonio_monitor2=?, usuario=?, observacoes=? WHERE id=?");
                    $stmt->execute([$setor, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes, $id]);
                    $sucesso = "Computador atualizado com sucesso!";
                    $id_edit = 0;
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ?");
                $stmt->execute([$patrimonio_computador]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO computadores (setor, patrimonio_computador, patrimonio_monitor1, patrimonio_monitor2, usuario, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$setor, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes]);
                    $sucesso = "Computador cadastrado com sucesso!";
                }
            }

            if (empty($erro)) {
                $computador = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar computador: " . $e->getMessage();
        }
    }
}

if (isset($_GET['del'])) {
    $id_del = intval($_GET['del']);
    try {
        $stmt = $pdo->prepare("DELETE FROM computadores WHERE id = ?");
        $stmt->execute([$id_del]);
        if ($stmt->rowCount() > 0) {
            $sucesso = "Computador excluído com sucesso!";
        } else {
            $erro = "Computador não encontrado ou já foi excluído.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao excluir computador: " . $e->getMessage();
    }
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
                    <a class="nav-link active dropdown-toggle" href="#" id="itensDropdown" role="button"
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
                <h2 class="mb-0"><?= $id_edit ? 'Editar Computador' : 'Cadastrar Computadores' ?></h2>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $erro ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $sucesso ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $id_edit ? 'Editar Computador' : 'Cadastrar Novo Computador' ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $computador['id'] ?? '' ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="setor" class="form-label">Setor *</label>
                                <input type="text" class="form-control" id="setor" name="setor" required
                                    value="<?= htmlspecialchars($computador['setor'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="patrimonio_computador" class="form-label">Patrimônio Computador *</label>
                                <input type="text" class="form-control" id="patrimonio_computador" name="patrimonio_computador" required
                                    value="<?= htmlspecialchars($computador['patrimonio_computador'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="patrimonio_monitor1" class="form-label">Patrimônio Monitor 1</label>
                                <input type="text" class="form-control" id="patrimonio_monitor1" name="patrimonio_monitor1"
                                    value="<?= htmlspecialchars($computador['patrimonio_monitor1'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="patrimonio_monitor2" class="form-label">Patrimônio Monitor 2</label>
                                <input type="text" class="form-control" id="patrimonio_monitor2" name="patrimonio_monitor2"
                                    value="<?= htmlspecialchars($computador['patrimonio_monitor2'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="usuario" class="form-label">Usuário</label>
                                <input type="text" class="form-control" id="usuario" name="usuario"
                                    value="<?= htmlspecialchars($computador['usuario'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($computador['observacoes'] ?? '') ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?= $id_edit ? 'Atualizar' : 'Cadastrar' ?>
                                </button>
                                <?php if ($id_edit): ?>
                                    <a href="computadores.php" class="btn btn-secondary">
                                        <i class="bi bi-x"></i> Cancelar
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
        document.querySelector('.navbar-toggler').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>

</html>