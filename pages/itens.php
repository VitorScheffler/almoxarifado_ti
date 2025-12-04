<?php
// Padrão: itens.php
$pagina_atual = 'itens.php';
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'numero':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        case 'patrimonio':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

// Buscar próximo código para novos itens
$proximo_codigo = 1;
if (!isset($_GET['edit'])) {
    try {
        $stmt = $pdo->query("SELECT MAX(CAST(codigo_interno AS UNSIGNED)) as max_codigo FROM itens");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $proximo_codigo = ($resultado['max_codigo'] ?? 0) + 1;
    } catch (PDOException $e) {
        // Se houver erro, mantém o valor padrão
    }
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
                    
                    // Redirecionar após sucesso
                    header("Location: itens.php?success=1");
                    exit();
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
                    
                    // Redirecionar após sucesso
                    header("Location: itens.php?success=1");
                    exit();
                }
            }
            
            // Limpar dados do formulário após sucesso (se não redirecionou)
            if (empty($erro)) {
                $item = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar item: " . $e->getMessage();
        }
    }
}

// Verificar se veio sucesso do redirecionamento
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $sucesso = "Item cadastrado com sucesso!";
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
    <h2 class="mb-0"><?= $id_edit ? 'Editar Item' : 'Cadastrar Novo Item' ?></h2>
    <?php if (!$id_edit): ?>
    <div class="d-flex align-items-center">
        <a href="itens_cadastrados.php" class="btn btn-outline-primary">
            <i class="bi bi-list-ul"></i> Ver Itens
        </a>
    </div>
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
            <?= $id_edit ? 'Editar Item' : 'Cadastrar Novo Item' ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nome" 
                           name="nome" 
                           required
                           value="<?= htmlspecialchars($item['nome'] ?? '') ?>" 
                           placeholder="Nome do item"
                           maxlength="100">
                    <div class="invalid-feedback">
                        Por favor, informe o nome do item.
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="codigo_interno" class="form-label">Código Interno</label>
                    <input type="text" 
                           class="form-control codigo-mask" 
                           id="codigo_interno" 
                           name="codigo_interno" 
                           value="<?= isset($item['codigo_interno']) ? htmlspecialchars($item['codigo_interno']) : $proximo_codigo ?>"
                           placeholder="Código interno do item"
                           maxlength="20">
                    <div class="form-text">
                        <?= !$id_edit ? 'Código sugerido automaticamente' : 'Edite se necessário' ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                    <input type="number" 
                           class="form-control" 
                           id="quantidade_minima" 
                           name="quantidade_minima" 
                           min="0" 
                           value="<?= $item['quantidade_minima'] ?? 0 ?>"
                           placeholder="0"
                           step="1">
                    <div class="form-text">
                        Quantidade mínima para alerta de estoque
                    </div>
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
                    <div class="form-text">
                        Categorize para facilitar a organização
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" 
                              id="descricao" 
                              name="descricao" 
                              rows="3"
                              placeholder="Descrição detalhada do item (opcional)"
                              maxlength="500"><?= htmlspecialchars($item['descricao'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" 
                              id="observacoes" 
                              name="observacoes" 
                              rows="2"
                              placeholder="Observações adicionais (opcional)"
                              maxlength="300"><?= htmlspecialchars($item['observacoes'] ?? '') ?></textarea>
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
                            <a href="itens.php" class="btn btn-outline-secondary">
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
    
    // Máscara para código (apenas números)
    const codigoInputs = document.querySelectorAll('.codigo-mask');
    codigoInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Permite apenas números
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    });
    
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
    
    // Auto-preenchimento de código para novos itens
    const codigoInput = document.getElementById('codigo_interno');
    if (codigoInput && !<?= $id_edit > 0 ? 'true' : 'false' ?>) {
        codigoInput.addEventListener('focus', function() {
            if (!this.value.trim()) {
                this.value = <?= $proximo_codigo ?>;
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = $id_edit ? "Editar Item - Sistema Almoxarifado" : "Cadastrar Item - Sistema Almoxarifado";
$pagina_atual = 'itens.php';

include '../includes/template.php';