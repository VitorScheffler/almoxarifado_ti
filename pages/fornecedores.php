<?php
// Padrão: fornecedores.php
$pagina_atual = 'fornecedores.php';
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
                // Verificar duplicação de CNPJ
                if (!empty($cnpj)) {
                    $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ? AND id != ?");
                    $stmt->execute([$cnpj, $id]);
                    if ($stmt->fetch()) {
                        $erro = "Já existe um fornecedor cadastrado com este CNPJ.";
                    }
                }
                
                // Atualização
                if (empty($erro)) {
                    $stmt = $pdo->prepare("UPDATE fornecedores SET nome=?, cnpj=?, endereco=?, telefone=?, email=?, observacoes=? WHERE id=?");
                    $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes, $id]);
                    $sucesso = "Fornecedor atualizado com sucesso!";
                    
                    // Redirecionar após sucesso
                    header("Location: fornecedores.php?success=1");
                    exit();
                }
            } else {
                // Verificar duplicação de CNPJ
                if (!empty($cnpj)) {
                    $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ?");
                    $stmt->execute([$cnpj]);
                    if ($stmt->fetch()) {
                        $erro = "Já existe um fornecedor cadastrado com este CNPJ.";
                    }
                }
                
                // Inserção
                if (empty($erro)) {
                    $stmt = $pdo->prepare("INSERT INTO fornecedores (nome, cnpj, endereco, telefone, email, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $cnpj, $endereco, $telefone, $email, $observacoes]);
                    $sucesso = "Fornecedor cadastrado com sucesso!";
                    
                    // Redirecionar após sucesso
                    header("Location: fornecedores.php?success=1");
                    exit();
                }
            }
            
            // Limpar dados do formulário após sucesso (se não redirecionou)
            if (empty($erro)) {
                $fornecedor = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar fornecedor: " . $e->getMessage();
        }
    }
}

// Verificar se veio sucesso do redirecionamento
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $sucesso = "Fornecedor cadastrado com sucesso!";
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><?= $id_edit ? 'Editar Fornecedor' : 'Cadastrar Fornecedor' ?></h2>
    <?php if (!$id_edit): ?>
    <a href="fornecedores_cadastrados.php" class="btn btn-outline-primary">
        <i class="bi bi-list-ul"></i> Ver Fornecedores
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

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi <?= $id_edit ? 'bi-pencil' : 'bi-plus-lg' ?>"></i> 
            <?= $id_edit ? 'Editar Fornecedor' : 'Cadastrar Novo Fornecedor' ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
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
                           placeholder="Rua, número, bairro, cidade"
                           maxlength="200">
                </div>
                
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" 
                           class="form-control phone-mask" 
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
                           placeholder="email@exemplo.com"
                           maxlength="100">
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="3"
                              placeholder="Observações sobre o fornecedor (opcional)"
                              maxlength="500"><?= htmlspecialchars($fornecedor['observacoes'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?= $id_edit ? 'Atualizar' : 'Cadastrar' ?>
                        </button>
                        <button type="reset" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle"></i> Limpar
                        </button>
                        <?php if ($id_edit): ?>
                            <a href="fornecedores.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focar no primeiro campo
    document.getElementById('nome').focus();
    
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
    
    // Validação de CNPJ em tempo real
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('blur', function() {
            const cnpj = this.value.replace(/\D/g, '');
            const id = document.querySelector('input[name="id"]')?.value || 0;
            
            if (cnpj.length === 14) {
                // Esta função precisaria de um endpoint específico
                // fetch(`../api/verificar_cnpj.php?cnpj=${cnpj}&id=${id}`)
                //     .then(response => response.json())
                //     .then(data => {
                //         if (data.existe) {
                //             this.setCustomValidity('Este CNPJ já está cadastrado.');
                //             this.classList.add('is-invalid');
                //         } else {
                //             this.setCustomValidity('');
                //             this.classList.remove('is-invalid');
                //         }
                //     })
                //     .catch(error => console.error('Erro:', error));
            } else if (cnpj.length > 0 && cnpj.length !== 14) {
                this.setCustomValidity('CNPJ deve ter 14 dígitos.');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = $id_edit ? "Editar Fornecedor - Sistema Almoxarifado" : "Cadastrar Fornecedor - Sistema Almoxarifado";
$pagina_atual = 'fornecedores.php';

include '../includes/template.php';