<?php
// Padrão: fornecedores_cadastrados.php
$pagina_atual = 'fornecedores_cadastrados.php';
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
        case 'numero':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

// Processamento de GET/POST
$id_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$fornecedor = null;

// Buscar dados para edição
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

// Processar formulário POST
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
                // Atualização
                $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, endereco=?, telefone=?, email=?, observacoes=? WHERE id=?");
                $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes, $id]);
                $sucesso = "Fornecedor atualizado com sucesso!";
                $id_edit = 0;
            } else {
                // Inserção
                $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, endereco, telefone, email, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes]);
                $sucesso = "Fornecedor cadastrado com sucesso!";
            }
            
            // Limpar dados do formulário após sucesso
            if (empty($erro)) {
                $fornecedor = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar fornecedor: " . $e->getMessage();
        }
    }
}

// Exclusão
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

// Buscar todos os registros
try {
    $fornecedores = $pdo->query("SELECT * FROM fornecedores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar fornecedores: " . $e->getMessage();
    $fornecedores = [];
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Fornecedores Cadastrados</h2>
    <?php if ($id_edit == 0): ?>
    <a href="fornecedores.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Novo Fornecedor
    </a>
    <?php endif; ?>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?= $erro ?>
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= $sucesso ?>
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<?php if ($id_edit > 0 && $fornecedor): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Editar Fornecedor</h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $fornecedor['id'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nome" 
                           name="nome" 
                           required
                           value="<?= htmlspecialchars($fornecedor['nome']) ?>"
                           placeholder="Nome do fornecedor"
                           maxlength="100">
                    <div class="invalid-feedback">
                        Por favor, informe o nome do fornecedor.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="cnpj" class="form-label">CNPJ</label>
                    <input type="text" 
                           class="form-control cnpj-mask" 
                           id="cnpj" 
                           name="cnpj"
                           value="<?= htmlspecialchars($fornecedor['cnpj']) ?>"
                           placeholder="00.000.000/0000-00"
                           maxlength="18">
                </div>
                
                <div class="col-12">
                    <label for="endereco" class="form-label">Endereço</label>
                    <input type="text" 
                           class="form-control" 
                           id="endereco" 
                           name="endereco"
                           value="<?= htmlspecialchars($fornecedor['endereco']) ?>"
                           placeholder="Rua, número, bairro, cidade"
                           maxlength="200">
                </div>
                
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" 
                           class="form-control phone-mask" 
                           id="telefone" 
                           name="telefone"
                           value="<?= htmlspecialchars($fornecedor['telefone']) ?>"
                           placeholder="(00) 00000-0000"
                           maxlength="15">
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email"
                           value="<?= htmlspecialchars($fornecedor['email']) ?>"
                           placeholder="email@exemplo.com"
                           maxlength="100">
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="3"
                              placeholder="Observações sobre o fornecedor"
                              maxlength="500"><?= htmlspecialchars($fornecedor['observacoes']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar
                        </button>
                        <a href="fornecedores_cadastrados.php" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Fornecedores</h5>
            <span class="badge bg-primary">
                <?= count($fornecedores) ?> registros
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($fornecedores)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Contato</th>
                            <th>Endereço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($f['nome']) ?>
                                    <?php if (!empty($f['observacoes'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-chat-left-text"></i>
                                            <?= htmlspecialchars(substr($f['observacoes'], 0, 50)) ?>...
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($f['cnpj'])): ?>
                                        <?= preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $f['cnpj']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($f['telefone'])): ?>
                                        <div class="mb-1">
                                            <i class="bi bi-telephone text-primary"></i> 
                                            <small><?= preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $f['telefone']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($f['email'])): ?>
                                        <div>
                                            <i class="bi bi-envelope text-primary"></i> 
                                            <small><?= htmlspecialchars($f['email']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($f['endereco'])): ?>
                                        <small><?= htmlspecialchars(substr($f['endereco'], 0, 50)) ?>...</small>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?edit=<?= $f['id'] ?>" 
                                           class="btn btn-warning"
                                           title="Editar fornecedor">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?del=<?= $f['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir este fornecedor?')"
                                           title="Excluir fornecedor">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-building display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Nenhum fornecedor cadastrado</h5>
                <p class="text-muted mb-4">Adicione seu primeiro fornecedor para começar</p>
                <a href="fornecedores.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Cadastrar Fornecedor
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Máscaras para os campos
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CNPJ
    const cnpjInputs = document.querySelectorAll('.cnpj-mask');
    cnpjInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    });

    // Máscara para telefone
    const phoneInputs = document.querySelectorAll('.phone-mask');
    phoneInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
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
    });

    // Validação do formulário
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Fornecedores Cadastrados - Sistema Almoxarifado";
$pagina_atual = 'fornecedores_cadastrados.php';

include '../includes/template.php';