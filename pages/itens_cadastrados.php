<?php
// Padrão: itens_cadastrados.php
$pagina_atual = 'itens_cadastrados.php';
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'numero':
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
$item = null;

// Buscar dados para edição
if ($id_edit > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
        $stmt->execute([$id_edit]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $erro = "Item não encontrado.";
            $id_edit = 0;
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar item: " . $e->getMessage();
    }
}

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = limparDados($_POST['nome'] ?? '');
    $codigo_interno = limparDados($_POST['codigo_interno'] ?? '', 'numero');
    $descricao = limparDados($_POST['descricao'] ?? '');
    $quantidade_minima = intval($_POST['quantidade_minima'] ?? 0);
    $unidade = limparDados($_POST['unidade'] ?? 'unidade');
    $categoria = limparDados($_POST['categoria'] ?? '');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validações
    if (empty($nome)) {
        $erro = "O campo nome é obrigatório.";
    } elseif (strlen($nome) > 100) {
        $erro = "O nome deve ter no máximo 100 caracteres.";
    } elseif ($quantidade_minima < 0) {
        $erro = "A quantidade mínima não pode ser negativa.";
    } else {
        try {
            if ($id > 0) {
                // Verificar duplicação de código interno
                if (!empty($codigo_interno)) {
                    $stmt = $pdo->prepare("SELECT id FROM itens WHERE codigo_interno = ? AND id != ?");
                    $stmt->execute([$codigo_interno, $id]);
                    if ($stmt->fetch()) {
                        $erro = "Já existe um item com este código interno.";
                    }
                }
                
                // Atualização
                if (empty($erro)) {
                    $stmt = $pdo->prepare("UPDATE itens SET nome=?, codigo_interno=?, descricao=?, quantidade_minima=?, unidade=?, categoria=?, observacoes=? WHERE id=?");
                    $stmt->execute([$nome, $codigo_interno, $descricao, $quantidade_minima, $unidade, $categoria, $observacoes, $id]);
                    $sucesso = "Item atualizado com sucesso!";
                    $id_edit = 0;
                }
            } else {
                // Verificar duplicação de código interno
                if (!empty($codigo_interno)) {
                    $stmt = $pdo->prepare("SELECT id FROM itens WHERE codigo_interno = ?");
                    $stmt->execute([$codigo_interno]);
                    if ($stmt->fetch()) {
                        $erro = "Já existe um item com este código interno.";
                    }
                }
                
                // Inserção
                if (empty($erro)) {
                    $stmt = $pdo->prepare("INSERT INTO itens (nome, codigo_interno, descricao, quantidade_minima, unidade, categoria, observacoes, quantidade_atual) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                    $stmt->execute([$nome, $codigo_interno, $descricao, $quantidade_minima, $unidade, $categoria, $observacoes]);
                    $sucesso = "Item cadastrado com sucesso!";
                }
            }
            
            // Limpar dados do formulário após sucesso
            if (empty($erro)) {
                $item = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar item: " . $e->getMessage();
        }
    }
}

// Exclusão
if (isset($_GET['del'])) {
    $id_del = intval($_GET['del']);
    try {
        // Verificar se há estoque
        $stmt = $pdo->prepare("SELECT quantidade_atual FROM itens WHERE id = ?");
        $stmt->execute([$id_del]);
        $item_check = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item_check && $item_check['quantidade_atual'] > 0) {
            $erro = "Não é possível excluir o item. Ainda há {$item_check['quantidade_atual']} unidades em estoque.";
        } else {
            // Verificar se há movimentações
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM entradas WHERE item_id = ?");
            $stmt->execute([$id_del]);
            $entradas_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM saidas WHERE item_id = ?");
            $stmt->execute([$id_del]);
            $saidas_count = $stmt->fetchColumn();
            
            if ($entradas_count > 0 || $saidas_count > 0) {
                $erro = "Não é possível excluir o item. Existem movimentações associadas.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
                $stmt->execute([$id_del]);
                if ($stmt->rowCount() > 0) {
                    $sucesso = "Item excluído com sucesso!";
                } else {
                    $erro = "Item não encontrado ou já foi excluído.";
                }
            }
        }
    } catch (PDOException $e) {
        $erro = "Erro ao excluir item: " . $e->getMessage();
    }
}

// Buscar todos os registros com status de estoque
try {
    $itens = $pdo->query("
        SELECT i.*, 
               CASE 
                   WHEN i.quantidade_atual <= i.quantidade_minima THEN 'alert'
                   WHEN i.quantidade_atual = 0 THEN 'danger'
                   ELSE 'normal'
               END as status_estoque
        FROM itens i 
        ORDER BY i.nome
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar itens: " . $e->getMessage();
    $itens = [];
}

// Buscar categorias existentes
$categorias_existentes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM itens WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    // Ignorar erro, a lista será vazia
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Itens Cadastrados</h2>
    <?php if ($id_edit == 0): ?>
    <a href="itens.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Novo Item
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

<?php if ($id_edit > 0 && $item): ?>
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Editar Item</h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nome" 
                           name="nome" 
                           required
                           value="<?= htmlspecialchars($item['nome']) ?>"
                           placeholder="Nome do item"
                           maxlength="100">
                    <div class="invalid-feedback">
                        Por favor, informe o nome do item.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="codigo_interno" class="form-label">Código Interno</label>
                    <input type="text" 
                           class="form-control" 
                           id="codigo_interno" 
                           name="codigo_interno"
                           value="<?= htmlspecialchars($item['codigo_interno']) ?>"
                           placeholder="Código único do item"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           title="Digite apenas números">
                </div>
                
                <div class="col-md-6">
                    <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                    <input type="number" 
                           class="form-control" 
                           id="quantidade_minima" 
                           name="quantidade_minima"
                           value="<?= htmlspecialchars($item['quantidade_minima']) ?>"
                           min="0"
                           placeholder="Estoque mínimo">
                </div>
                
                <div class="col-md-6">
                    <label for="unidade" class="form-label">Unidade de Medida *</label>
                    <select class="form-select" id="unidade" name="unidade" required>
                        <option value="unidade" <?= ($item['unidade'] ?? 'unidade') == 'unidade' ? 'selected' : '' ?>>Unidade</option>
                        <option value="caixa" <?= ($item['unidade'] ?? '') == 'caixa' ? 'selected' : '' ?>>Caixa</option>
                        <option value="litro" <?= ($item['unidade'] ?? '') == 'litro' ? 'selected' : '' ?>>Litro</option>
                        <option value="kg" <?= ($item['unidade'] ?? '') == 'kg' ? 'selected' : '' ?>>Quilograma</option>
                        <option value="metro" <?= ($item['unidade'] ?? '') == 'metro' ? 'selected' : '' ?>>Metro</option>
                        <option value="rolo" <?= ($item['unidade'] ?? '') == 'rolo' ? 'selected' : '' ?>>Rolo</option>
                        <option value="pacote" <?= ($item['unidade'] ?? '') == 'pacote' ? 'selected' : '' ?>>Pacote</option>
                        <option value="par" <?= ($item['unidade'] ?? '') == 'par' ? 'selected' : '' ?>>Par</option>
                        <option value="conjunto" <?= ($item['unidade'] ?? '') == 'conjunto' ? 'selected' : '' ?>>Conjunto</option>
                        <option value="fardo" <?= ($item['unidade'] ?? '') == 'fardo' ? 'selected' : '' ?>>Fardo</option>
                    </select>
                    <div class="invalid-feedback">
                        Por favor, selecione uma unidade de medida.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">-- Selecione ou digite --</option>
                        <?php foreach ($categorias_existentes as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                <?= ($item['categoria'] ?? '') == $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" 
                              id="descricao" 
                              name="descricao" 
                              rows="3"
                              placeholder="Descrição detalhada do item"
                              maxlength="500"><?= htmlspecialchars($item['descricao']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="2"
                              placeholder="Observações adicionais"
                              maxlength="300"><?= htmlspecialchars($item['observacoes']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar
                        </button>
                        <a href="itens_cadastrados.php" class="btn btn-secondary">
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
            <h5 class="mb-0">Lista de Itens</h5>
            <span class="badge bg-primary">
                <?= count($itens) ?> registros
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($itens)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Estoque</th>
                            <th>Mínimo</th>
                            <th>Unidade</th>
                            <th>Categoria</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $i): ?>
                            <tr class="<?= $i['status_estoque'] == 'danger' ? 'table-danger' : ($i['status_estoque'] == 'alert' ? 'table-warning' : '') ?>">
                                <td>
                                    <strong><?= htmlspecialchars($i['nome']) ?></strong>
                                    <?php if (!empty($i['descricao'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-card-text"></i>
                                            <?= htmlspecialchars(substr($i['descricao'], 0, 50)) ?>...
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($i['codigo_interno'])): ?>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($i['codigo_interno']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $i['quantidade_atual'] == 0 ? 'bg-danger' : ($i['quantidade_atual'] <= $i['quantidade_minima'] ? 'bg-warning' : 'bg-success') ?>">
                                        <?= $i['quantidade_atual'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= $i['quantidade_minima'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($i['unidade']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($i['categoria'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($i['categoria']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?edit=<?= $i['id'] ?>" 
                                           class="btn btn-warning"
                                           title="Editar item">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?del=<?= $i['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Tem certeza que deseja excluir este item?')"
                                           title="Excluir item">
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
                    <i class="bi bi-box display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Nenhum item cadastrado</h5>
                <p class="text-muted mb-4">Adicione seu primeiro item para começar</p>
                <a href="itens.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Cadastrar Item
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Comportamento para categoria com criação dinâmica
    const categoriaSelect = document.getElementById('categoria');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            if (this.value === '' && this.dataset.listenerAdded !== 'true') {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control mt-2';
                input.placeholder = 'Digite a nova categoria';
                input.name = 'categoria_nova';
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '') {
                        categoriaSelect.value = this.value.trim();
                        // Adicionar a nova opção ao select
                        const option = document.createElement('option');
                        option.value = this.value.trim();
                        option.textContent = this.value.trim();
                        option.selected = true;
                        categoriaSelect.appendChild(option);
                    }
                    this.remove();
                    categoriaSelect.dataset.listenerAdded = 'false';
                });
                this.parentNode.appendChild(input);
                input.focus();
                this.dataset.listenerAdded = 'true';
            }
        });
    }

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

    // Gerar código interno automaticamente para novos itens
    const codigoInput = document.getElementById('codigo_interno');
    if (codigoInput && !codigoInput.value && <?= $id_edit > 0 ? 'false' : 'true' ?>) {
        fetch('../api/proximo_codigo.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                return response.json();
            })
            .then(data => {
                if (data.codigo) {
                    codigoInput.value = data.codigo;
                }
            })
            .catch(error => {
                console.error('Erro ao buscar próximo código:', error);
            });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Itens Cadastrados - Sistema Almoxarifado";
$pagina_atual = 'itens_cadastrados.php';

include '../includes/template.php';