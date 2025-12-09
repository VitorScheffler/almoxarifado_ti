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
        <!-- Formulário de Categoria -->
        <div class="card border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-tag me-2"></i>
                    <?php echo $categoria_editar ? 'Editar Categoria' : 'Nova Categoria'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao_categoria" value="<?php echo $categoria_editar ? 'editar' : 'adicionar'; ?>">
                    <input type="hidden" name="categoria_id" value="<?php echo isset($categoria_editar['id']) ? $categoria_editar['id'] : ''; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label">Nome da Categoria *</label>
                        <input type="text" 
                               class="form-control" 
                               name="categoria_nome" 
                               value="<?php echo isset($categoria_editar['categoria']) ? htmlspecialchars($categoria_editar['categoria']) : ''; ?>" 
                               placeholder="Ex: Eletrônicos, Móveis, Informática" 
                               required
                               maxlength="100">
                        <small class="form-text text-muted mt-1">
                            A categoria ficará disponível para uso nos itens.
                        </small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> 
                            <?php echo $categoria_editar ? 'Atualizar Categoria' : 'Cadastrar Categoria'; ?>
                        </button>
                        <?php if ($categoria_editar): ?>
                            <a href="gerenciamento_categorias.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x me-1"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de Categorias -->
    <div class="col-lg-12">
        <div class="card border-0">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-tags me-2"></i>
                    Categorias Cadastradas
                    <span class="badge bg-primary rounded-pill ms-2"><?php echo count($categorias); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($categorias)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Categoria</th>
                                    <th>Itens Vinculados</th>
                                    <th class="pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): 
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                                        $stmt->execute([$cat['categoria']]);
                                        $count_itens = $stmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        $count_itens = 0;
                                    }
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-tag-fill text-info me-2"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($cat['categoria']); ?></strong>
                                                    <?php if ($count_itens > 0): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="bi bi-box me-1"></i>
                                                            <?php echo $count_itens; ?> item(ns) cadastrado(s)
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($count_itens > 0): ?>
                                                <span class="badge bg-info rounded-pill">
                                                    <?php echo $count_itens; ?> itens
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark rounded-pill">
                                                    Nenhum item
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?editar_categoria=<?php echo $cat['id']; ?>" 
                                                   class="btn btn-warning"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?excluir_categoria=<?php echo $cat['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Tem certeza que deseja excluir a categoria <?php echo addslashes($cat['categoria']); ?>?\n\n<?php echo $count_itens > 0 ? 'ATENÇÃO: ' . $count_itens . ' item(ns) ficará(ão) sem categoria.' : ''; ?>')"
                                                   title="Excluir">
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
                        <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">Nenhuma categoria cadastrada</p>
                        <a href="gerenciamento_categorias.php" class="btn btn-sm btn-primary mt-3">
                            <i class="bi bi-plus me-1"></i> Cadastrar Primeira Categoria
                        </a>
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