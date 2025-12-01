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
        <?php include '../includes/menu.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Cadastrar novos Fornecedores</h2>
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

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $sucesso ?>
                    <button type="button" 
                            class="btn-close" 
                            data-bs-dismiss="alert" 
                            aria-label="Close">
                    </button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $id_edit ? 'Editar Fornecedor' : 'Cadastrar Novo Fornecedor' ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $fornecedor['id'] ?? '' ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       required
                                       value="<?= htmlspecialchars($fornecedor['nome'] ?? '') ?>"
                                       placeholder="Nome do fornecedor">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="cnpj" 
                                       name="cnpj"
                                       value="<?= htmlspecialchars($fornecedor['cnpj'] ?? '') ?>"
                                       placeholder="00.000.000/0000-00"
                                       maxlength="18">
                            </div>
                            
                            <div class="col-12">
                                <label for="endereco" class="form-label">Endereço</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="endereco" 
                                       name="endereco"
                                       value="<?= htmlspecialchars($fornecedor['endereco'] ?? '') ?>"
                                       placeholder="Rua, número, bairro, cidade">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="telefone" 
                                       name="telefone"
                                       value="<?= htmlspecialchars($fornecedor['telefone'] ?? '') ?>"
                                       placeholder="(00) 00000-0000"
                                       maxlength="15">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email"
                                       value="<?= htmlspecialchars($fornecedor['email'] ?? '') ?>"
                                       placeholder="email@exemplo.com">
                            </div>
                            
                            <div class="col-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" 
                                          id="observacoes" 
                                          name="observacoes" 
                                          rows="3"
                                          placeholder="Observações sobre o fornecedor"><?= htmlspecialchars($fornecedor['observacoes'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?= $id_edit ? 'Atualizar' : 'Cadastrar' ?>
                                </button>
                                <button type="reset" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Limpar
                                </button>
                                <?php if ($id_edit): ?>
                                    <a href="fornecedores.php" class="btn btn-secondary">
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
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            
            e.target.value = value;
        });

        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>