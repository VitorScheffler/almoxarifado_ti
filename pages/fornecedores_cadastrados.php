<?php
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'cnpj':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        case 'telefone':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        case 'email':
            $dado = filter_var($dado, FILTER_SANITIZE_EMAIL);
            break;
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

$id_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$fornecedor = null;

if ($id_edit > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
        $stmt->execute([$id_edit]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fornecedor) {
            $erro = "Fornecedor não encontrado.";
            $id_edit = 0;
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar fornecedor: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = limparDados($_POST['nome'] ?? '');
    $cnpj = limparDados($_POST['cnpj'] ?? '', 'cnpj');
    $endereco = limparDados($_POST['endereco'] ?? '');
    $telefone = limparDados($_POST['telefone'] ?? '', 'telefone');
    $email = limparDados($_POST['email'] ?? '', 'email');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validações
    if (empty($nome)) {
        $erro = "O campo nome é obrigatório.";
    } elseif (strlen($nome) > 100) {
        $erro = "O nome deve ter no máximo 100 caracteres.";
    } elseif (!empty($cnpj) && strlen($cnpj) != 14) {
        $erro = "CNPJ deve ter 14 dígitos.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, endereco=?, telefone=?, email=?, observacoes=? WHERE id=?");
                $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes, $id]);
                $sucesso = "Fornecedor atualizado com sucesso!";
                $id_edit = 0;
            } else {
                $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, endereco, telefone, email, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes]);
                $sucesso = "Fornecedor cadastrado com sucesso!";
            }
            
            if (empty($erro)) {
                $fornecedor = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar fornecedor: " . $e->getMessage();
        }
    }
}

if (isset($_GET['del'])) {
    $id_del = intval($_GET['del']);
    try {
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$id_del]);
        if ($stmt->rowCount() > 0) {
            $sucesso = "Fornecedor excluído com sucesso!";
        } else {
            $erro = "Fornecedor não encontrado ou já foi excluído.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao excluir fornecedor: " . $e->getMessage();
    }
}

try {
    $fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar fornecedores: " . $e->getMessage();
    $fornecedores = [];
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
                    <a class="nav-link active dropdown-toggle" href="#" id="itensDropdown" role="button"
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
                <h2 class="mb-0">Fornecedores Cadastrados</h2>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fornecedores Cadastrados</h5>
                        <span class="badge bg-primary">
                            <?= count($fornecedores) ?> registros
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($fornecedores)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>CNPJ</th>
                                        <th>Contato</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fornecedores as $f): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($f['nome']) ?></strong>
                                                <?php if (!empty($f['observacoes'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($f['observacoes'], 0, 50)) ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="format-cnpj">
                                                <?= !empty($f['cnpj']) ? 
                                                    preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $f['cnpj']) : 
                                                    'Não informado' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($f['telefone'])): ?>
                                                    <i class="bi bi-telephone"></i> 
                                                    <?= preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $f['telefone']) ?>
                                                    <br>
                                                <?php endif; ?>
                                                <?php if (!empty($f['email'])): ?>
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($f['email']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?= $f['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                                <a href="?del=<?= $f['id'] ?>" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Tem certeza que deseja excluir este item?')">
                                                <i class="bi bi-trash"></i> Excluir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>


                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            Nenhum fornecedor cadastrado. <a href="fornecedores.php">Cadastre seu primeiro fornecedor</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>