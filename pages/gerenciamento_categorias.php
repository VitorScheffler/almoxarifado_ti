<?php
// Padrão: configuracoes.php
$pagina_atual = 'gerenciamento.php';
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Buscar categorias únicas
$stmt = $pdo->query("SELECT DISTINCT categoria FROM itens WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_categoria'])) {
    $acao = $_POST['acao_categoria'];
    
    try {
        switch ($acao) {
            case 'adicionar':
                $novaCategoria = trim($_POST['categoria_nome']);
                if (!empty($novaCategoria)) {
                    // Verificar se já existe
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                    $stmt->execute([$novaCategoria]);
                    if ($stmt->fetchColumn() == 0) {
                        // Adicionar um item temporário para criar a categoria
                        $stmt = $pdo->prepare("INSERT INTO itens (nome, categoria, data_cadastro) VALUES (?, ?, NOW())");
                        $stmt->execute(['Categoria Temp', $novaCategoria]);
                        
                        // Remover o item temporário
                        $pdo->prepare("DELETE FROM itens WHERE nome = 'Categoria Temp' AND categoria = ?")->execute([$novaCategoria]);
                        
                        $_SESSION['sucesso'] = "Categoria '{$novaCategoria}' criada com sucesso!";
                    } else {
                        $_SESSION['erro'] = "Esta categoria já existe!";
                    }
                }
                break;
                
            case 'renomear':
                $antiga = trim($_POST['categoria_antiga']);
                $nova = trim($_POST['categoria_nome']);
                
                if (!empty($antiga) && !empty($nova) && $antiga !== $nova) {
                    $stmt = $pdo->prepare("UPDATE itens SET categoria = ? WHERE categoria = ?");
                    $stmt->execute([$nova, $antiga]);
                    $_SESSION['sucesso'] = "Categoria renomeada de '{$antiga}' para '{$nova}'!";
                }
                break;
                
            case 'remover':
                $categoria = trim($_POST['categoria_antiga']);
                if (!empty($categoria)) {
                    $stmt = $pdo->prepare("UPDATE itens SET categoria = NULL WHERE categoria = ?");
                    $stmt->execute([$categoria]);
                    $_SESSION['sucesso'] = "Categoria '{$categoria}' removida!";
                }
                break;
        }
        
        header('Location: gerenciamento_categorias.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar: " . $e->getMessage();
        header('Location: gerenciamento_categorias.php');
        exit;
    }
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Gerenciamento de Categorias</h2>
    <a href="gerenciamento.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-12">

        <!-- Adicionar Nova Categoria -->
        <div class="card border mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Adicionar Nova Categoria
                </h5>
            </div>
            <div class="card-body">
                <form method="post" class="mb-0">
                    <input type="hidden" name="acao_categoria" value="adicionar">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               name="categoria_nome" 
                               placeholder="Digite o nome da nova categoria" 
                               required
                               maxlength="100">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus me-1"></i> Adicionar
                        </button>
                    </div>
                    <small class="form-text text-muted mt-2 d-block">
                        A categoria ficará disponível para uso nos itens.
                    </small>
                </form>
            </div>
        </div>

        <div class="col-lg-12">
        <!-- Renomear Categoria -->
        <div class="card border mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-pencil me-2"></i>
                    Renomear Categoria
                </h5>
            </div>
            <div class="card-body">
                <form method="post" class="mb-0">
                    <input type="hidden" name="acao_categoria" value="renomear">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Categoria Atual</label>
                            <select class="form-select" name="categoria_antiga" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['categoria']) ?>">
                                        <?= htmlspecialchars($cat['categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Novo Nome</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="categoria_nome" 
                                   placeholder="Digite o novo nome" 
                                   required
                                   maxlength="100">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-pencil me-1"></i> Renomear
                    </button>
                    <small class="form-text text-muted mt-2 d-block">
                        Todos os itens com esta categoria serão atualizados automaticamente.
                    </small>
                </form>
            </div>
        </div>
    </div>

    <!-- Remover Categoria -->
    <div class="col-lg-12">
        <div class="card border mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-trash me-2"></i>
                    Remover Categoria
                </h5>
            </div>
            <div class="card-body">
                <form method="post" id="formRemoverCategoria">
                    <input type="hidden" name="acao_categoria" value="remover">
                    <div class="mb-3">
                        <label class="form-label">Selecione a Categoria</label>
                        <select class="form-select" name="categoria_antiga" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['categoria']) ?>">
                                    <?= htmlspecialchars($cat['categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-trash me-1"></i> Remover Categoria
                    </button>
                    <small class="form-text text-danger mt-2 d-block">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Os itens ficarão sem categoria após esta operação.
                    </small>
                </form>
            </div>
        </div>

        <!-- Categorias Existentes -->
        <div class="card border">
            <div class="card-header bg-white border">
                <h5 class="mb-0">
                    <i class="bi bi-tags text me-2"></i>
                    Categorias Existentes
                    <span class="badge bg-primary rounded-pill ms-2"><?= count($categorias) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($categorias)): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($categorias as $cat): 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                            $stmt->execute([$cat['categoria']]);
                            $count = $stmt->fetchColumn();
                        ?>
                            <div class="badge bg-light text-dark border p-2 d-flex align-items-center">
                                <i class="bi bi-tag-fill text-info me-1"></i>
                                <span class="me-2"><?= htmlspecialchars($cat['categoria']) ?></span>
                                <span class="badge bg-info rounded-pill"><?= $count ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Nenhuma categoria cadastrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmação para remover categoria
    document.getElementById('formRemoverCategoria').addEventListener('submit', function(e) {
        const categoria = this.querySelector('select[name="categoria_antiga"]').value;
        if (!categoria) {
            e.preventDefault();
            return;
        }
        
        if (!confirm(`Tem certeza que deseja remover a categoria "${categoria}"?\n\nOs itens associados ficarão sem categoria.`)) {
            e.preventDefault();
        }
    });
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Gerenciamento de Categorias - Sistema Almoxarifado";
include '../includes/template.php';
?>