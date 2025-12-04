<?php
// Padrão: computadores_cadastrados.php
$pagina_atual = 'computadores_cadastrados.php';
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'numero':
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
    $stmt = $pdo->query("SELECT id, setor FROM setores ORDER BY setor");
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
            $stmt = $pdo->prepare("SELECT setor FROM setores WHERE id = ?");
            $stmt->execute([$setor_id]);
            $setor_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($setor_data) {
                $setor_nome = $setor_data['setor'];
            } else {
                $erro = "Setor selecionado não encontrado.";
            }
        } catch (PDOException $e) {
            $erro = "Erro ao buscar setor: " . $e->getMessage();
        }
    }

    // Validações
    if (empty($erro)) {
        if ($setor_id <= 0) {
            $erro = "O campo setor é obrigatório.";
        } elseif (empty($patrimonio_computador)) {
            $erro = "O campo patrimônio do computador é obrigatório.";
        } elseif (strlen($patrimonio_computador) > 100) {
            $erro = "O patrimônio do computador deve ter no máximo 100 caracteres.";
        }
    }

    // Se não há erros, salvar
    if (empty($erro)) {
        try {
            if ($id > 0) {
                // Verificar duplicação de patrimônio (exceto o próprio registro)
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ? AND id != ?");
                $stmt->execute([$patrimonio_computador, $id]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    // Atualização
                    $stmt = $pdo->prepare("UPDATE computadores SET setor=?, patrimonio_computador=?, patrimonio_monitor1=?, patrimonio_monitor2=?, usuario=?, observacoes=? WHERE id=?");
                    $stmt->execute([$setor_nome, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes, $id]);
                    $sucesso = "Computador atualizado com sucesso!";
                    $id_edit = 0;
                    $computador = null; // Limpar dados do formulário
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
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar computador: " . $e->getMessage();
        }
    }
}

// Exclusão
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

// Buscar todos os registros
try {
    $computadores = $pdo->query("SELECT * FROM computadores ORDER BY setor, usuario")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar computadores: " . $e->getMessage();
    $computadores = [];
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Computadores Cadastrados</h2>
    <?php if ($id_edit == 0): ?>
    <a href="computadores.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Novo Computador
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

<?php if ($id_edit > 0 && $computador): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Editar Computador</h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $computador['id'] ?>">
            
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
                            $isSelected = ($computador['setor'] === $setor['setor']);
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
                           value="<?= htmlspecialchars($computador['patrimonio_computador']) ?>"
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
                           value="<?= htmlspecialchars($computador['patrimonio_monitor1']) ?>"
                           placeholder="000000"
                           maxlength="20"
                           title="Digite apenas números">
                </div>
                
                <div class="col-md-6">
                    <label for="patrimonio_monitor2" class="form-label">Patrimônio Monitor 2</label>
                    <input type="text" 
                           class="form-control patrimonio-mask" 
                           id="patrimonio_monitor2" 
                           name="patrimonio_monitor2"
                           value="<?= htmlspecialchars($computador['patrimonio_monitor2']) ?>"
                           placeholder="000000"
                           maxlength="20"
                           title="Digite apenas números">
                </div>
                
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" 
                           class="form-control" 
                           id="usuario" 
                           name="usuario"
                           value="<?= htmlspecialchars($computador['usuario']) ?>"
                           placeholder="Nome do usuário"
                           maxlength="100">
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="3"
                              placeholder="Observações sobre o computador"
                              maxlength="500"><?= htmlspecialchars($computador['observacoes']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar
                        </button>
                        <a href="computadores_cadastrados.php" class="btn btn-secondary">
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
            <h5 class="mb-0">Lista de Computadores</h5>
            <span class="badge bg-primary">
                <?= count($computadores) ?> registros
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($computadores)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Setor</th>
                            <th>Usuário</th>
                            <th>Patrimônio Computador</th>
                            <th>Monitor 1</th>
                            <th>Monitor 2</th>
                            <th>Observações</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($computadores as $c): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($c['setor']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['usuario'])): ?>
                                        <?= htmlspecialchars($c['usuario']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($c['patrimonio_computador']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['patrimonio_monitor1'])): ?>
                                            <?= htmlspecialchars($c['patrimonio_monitor1']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['patrimonio_monitor2'])): ?>
                                            <?= htmlspecialchars($c['patrimonio_monitor2']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['observacoes'])): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-chat-left-text"></i>
                                            <?= htmlspecialchars(substr($c['observacoes'], 0, 50)) ?>...
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?edit=<?= $c['id'] ?>" 
                                           class="btn btn-warning"
                                           title="Editar computador">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?del=<?= $c['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir este computador?')"
                                           title="Excluir computador">
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
                    <i class="bi bi-pc-display display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Nenhum computador cadastrado</h5>
                <p class="text-muted mb-4">Adicione seu primeiro computador para começar</p>
                <a href="computadores.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Cadastrar Computador
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Verificação de patrimônio duplicado (opcional - remover se não tiver o arquivo)
    const patrimonioInput = document.getElementById('patrimonio_computador');
    if (patrimonioInput) {
        patrimonioInput.addEventListener('blur', function() {
            const patrimonio = this.value;
            const id = document.querySelector('input[name="id"]')?.value || 0;
            
            if (patrimonio.length > 0) {
                // Esta parte requer o arquivo ../api/verificar_patrimonio.php
                // Remova se não tiver o arquivo ou implemente conforme necessário
                console.log('Verificando patrimônio:', patrimonio, 'ID:', id);
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Computadores Cadastrados - Sistema Almoxarifado";
$pagina_atual = 'computadores_cadastrados.php';

include '../includes/template.php';
?>