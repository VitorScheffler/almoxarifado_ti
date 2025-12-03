<?php
// Padrão: computadores.php
$pagina_atual = 'computadores.php';
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'patrimonio':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

// Buscar setores para o dropdown
$setores = [];
try {
    $stmt = $pdo->query("SELECT id, setor FROM setor ORDER BY setor");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar setores: " . $e->getMessage();
}

// Processamento de GET/POST
$id_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$computador = null;

// Buscar dados para edição
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

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor_id = intval($_POST['setor_id'] ?? 0);
    $patrimonio_computador = limparDados($_POST['patrimonio_computador'] ?? '', 'patrimonio');
    $patrimonio_monitor1 = limparDados($_POST['patrimonio_monitor1'] ?? '', 'patrimonio');
    $patrimonio_monitor2 = limparDados($_POST['patrimonio_monitor2'] ?? '', 'patrimonio');
    $usuario = limparDados($_POST['usuario'] ?? '');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Buscar nome do setor
    $setor_nome = '';
    if ($setor_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT setor FROM setor WHERE id = ?");
            $stmt->execute([$setor_id]);
            $setor_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($setor_data) {
                $setor_nome = $setor_data['setor'];
            }
        } catch (PDOException $e) {
            $erro = "Erro ao buscar setor: " . $e->getMessage();
        }
    }

    // Validações
    if ($setor_id <= 0) {
        $erro = "O campo setor é obrigatório.";
    } elseif (empty($patrimonio_computador)) {
        $erro = "O campo patrimônio do computador é obrigatório.";
    } elseif (strlen($patrimonio_computador) > 100) {
        $erro = "O patrimônio do computador deve ter no máximo 100 caracteres.";
    } else {
        try {
            if ($id > 0) {
                // Verificar duplicação de patrimônio
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ? AND id != ?");
                $stmt->execute([$patrimonio_computador, $id]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    // Atualização
                    $stmt = $pdo->prepare("UPDATE computadores SET setor=?, patrimonio_computador=?, patrimonio_monitor1=?, patrimonio_monitor2=?, usuario=?, observacoes=? WHERE id=?");
                    $stmt->execute([$setor_nome, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes, $id]);
                    $sucesso = "Computador atualizado com sucesso!";
                    
                    // Redirecionar após sucesso
                    header("Location: computadores.php?success=1");
                    exit();
                }
            } else {
                // Verificar duplicação de patrimônio
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ?");
                $stmt->execute([$patrimonio_computador]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    // Inserção
                    $stmt = $pdo->prepare("INSERT INTO computadores (setor, patrimonio_computador, patrimonio_monitor1, patrimonio_monitor2, usuario, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$setor_nome, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes]);
                    $sucesso = "Computador cadastrado com sucesso!";
                    
                    // Redirecionar após sucesso
                    header("Location: computadores.php?success=1");
                    exit();
                }
            }
            
            // Limpar dados do formulário após sucesso (se não redirecionou)
            if (empty($erro)) {
                $computador = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar computador: " . $e->getMessage();
        }
    }
}

// Verificar se veio sucesso do redirecionamento
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $sucesso = "Computador cadastrado com sucesso!";
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><?= $id_edit ? 'Editar Computador' : 'Cadastrar Computador' ?></h2>
    <?php if (!$id_edit): ?>
    <a href="computadores_cadastrados.php" class="btn btn-outline-primary">
        <i class="bi bi-list-ul"></i> Ver Computadores
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
            <?= $id_edit ? 'Editar Computador' : 'Cadastrar Novo Computador' ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $computador['id'] ?? '' ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="setor_id" class="form-label">Setor *</label>
                    <select class="form-select" 
                            id="setor_id" 
                            name="setor_id" 
                            required>
                        <option value="">-- Selecione o Setor --</option>
                        <?php foreach ($setores as $setor): ?>
                            <?php 
                            // Determinar qual setor está selecionado
                            $isSelected = isset($computador['setor']) && $computador['setor'] === $setor['setor'];
                            ?>
                            <option value="<?= $setor['id'] ?>" 
                                <?= $isSelected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor['setor']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Por favor, selecione um setor.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="patrimonio_computador" class="form-label">Patrimônio Computador *</label>
                    <input type="text" 
                           class="form-control patrimonio-mask" 
                           id="patrimonio_computador" 
                           name="patrimonio_computador" 
                           required
                           value="<?= htmlspecialchars($computador['patrimonio_computador'] ?? '') ?>"
                           placeholder="000000"
                           maxlength="20"
                           title="Digite apenas números">
                    <div class="invalid-feedback">
                        Por favor, informe o patrimônio do computador.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="patrimonio_monitor1" class="form-label">Patrimônio Monitor 1</label>
                    <input type="text" 
                           class="form-control patrimonio-mask" 
                           id="patrimonio_monitor1" 
                           name="patrimonio_monitor1"
                           value="<?= htmlspecialchars($computador['patrimonio_monitor1'] ?? '') ?>"
                           placeholder="000000"
                           maxlength="20"
                           title="Digite apenas números">
                    <div class="form-text">
                        Opcional - para computador com monitor dedicado
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="patrimonio_monitor2" class="form-label">Patrimônio Monitor 2</label>
                    <input type="text" 
                           class="form-control patrimonio-mask" 
                           id="patrimonio_monitor2" 
                           name="patrimonio_monitor2"
                           value="<?= htmlspecialchars($computador['patrimonio_monitor2'] ?? '') ?>"
                           placeholder="000000"
                           maxlength="20"
                           title="Digite apenas números">
                    <div class="form-text">
                        Opcional - para configuração com dois monitores
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" 
                           class="form-control" 
                           id="usuario" 
                           name="usuario"
                           value="<?= htmlspecialchars($computador['usuario'] ?? '') ?>"
                           placeholder="Nome do usuário"
                           maxlength="100">
                    <div class="form-text">
                        Opcional - responsável pelo computador
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="3"
                              placeholder="Observações sobre o computador (opcional)"
                              maxlength="500"><?= htmlspecialchars($computador['observacoes'] ?? '') ?></textarea>
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
                            <a href="computadores.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i> Cancelar
                            </a>
                        <?php endif; ?>
                        <a href="computadores_cadastrados.php" class="btn btn-outline-info ms-auto">
                            <i class="bi bi-list-ul"></i> Ver Todos
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focar no primeiro campo
    const primeiroCampo = document.getElementById('setor_id');
    if (primeiroCampo) primeiroCampo.focus();
    
    // Máscara para campos de patrimônio (apenas números)
    const patrimonioInputs = document.querySelectorAll('.patrimonio-mask');
    patrimonioInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Permite apenas números
            e.target.value = e.target.value.replace(/\D/g, '');
        });
        
        // Prevenir colar conteúdo não numérico
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pastedText.replace(/\D/g, '');
            document.execCommand('insertText', false, numbersOnly);
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
    
    // Verificar duplicação de patrimônio em tempo real
    const patrimonioInput = document.getElementById('patrimonio_computador');
    if (patrimonioInput) {
        patrimonioInput.addEventListener('blur', function() {
            const patrimonio = this.value;
            const id = document.querySelector('input[name="id"]')?.value || 0;
            
            if (patrimonio.length > 0) {
                // Esta função precisaria de um endpoint específico
                // fetch(`../api/verificar_patrimonio.php?patrimonio=${patrimonio}&id=${id}`)
                //     .then(response => response.json())
                //     .then(data => {
                //         if (data.existe) {
                //             this.setCustomValidity('Este patrimônio já está em uso.');
                //             this.classList.add('is-invalid');
                //         } else {
                //             this.setCustomValidity('');
                //             this.classList.remove('is-invalid');
                //         }
                //     })
                //     .catch(error => console.error('Erro:', error));
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = $id_edit ? "Editar Computador - Sistema Almoxarifado" : "Cadastrar Computador - Sistema Almoxarifado";
$pagina_atual = 'computadores.php';

include '../includes/template.php';