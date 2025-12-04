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

// Verificar se a tabela setores existe
try {
    $stmt = $pdo->query("SELECT 1 FROM setores LIMIT 1");
} catch (PDOException $e) {
    // Criar a tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS setores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setor VARCHAR(50) NOT NULL,
        descricao VARCHAR(500),
        responsavel VARCHAR(100),
        telefone VARCHAR(20),
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Buscar setores
try {
    $stmt = $pdo->query("SELECT * FROM setores ORDER BY setor");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $setores = [];
    error_log("Erro ao buscar setores: " . $e->getMessage());
}

// Variáveis para edição
$setor_editar = null;

// Verificar se está editando
if (isset($_GET['editar_setor'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM setores WHERE id = ?");
        $stmt->execute([intval($_GET['editar_setor'])]);
        $setor_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar setor para edição: " . $e->getMessage());
    }
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_setor'])) {
    $acao = $_POST['acao_setor'];
    
    try {
        switch ($acao) {
            case 'adicionar':
                if (!empty($_POST['setor_nome'])) {
                    $stmt = $pdo->prepare("INSERT INTO setores (setor, descricao, responsavel, telefone, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        trim($_POST['setor_nome']),
                        trim($_POST['setor_descricao'] ?? ''),
                        trim($_POST['responsavel'] ?? ''),
                        trim($_POST['telefone'] ?? ''),
                        trim($_POST['email'] ?? '')
                    ]);
                    $_SESSION['sucesso'] = "Setor cadastrado com sucesso!";
                }
                break;
                
            case 'editar':
                if (!empty($_POST['setor_nome']) && !empty($_POST['setor_id'])) {
                    $stmt = $pdo->prepare("UPDATE setores SET setor = ?, descricao = ?, responsavel = ?, telefone = ?, email = ? WHERE id = ?");
                    $stmt->execute([
                        trim($_POST['setor_nome']),
                        trim($_POST['setor_descricao'] ?? ''),
                        trim($_POST['responsavel'] ?? ''),
                        trim($_POST['telefone'] ?? ''),
                        trim($_POST['email'] ?? ''),
                        intval($_POST['setor_id'])
                    ]);
                    $_SESSION['sucesso'] = "Setor atualizado com sucesso!";
                }
                break;
        }
        
        header('Location: gerenciamento_setores.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar: " . $e->getMessage();
        header('Location: gerenciamento_setores.php');
        exit;
    }
}

// Processar exclusão GET
if (isset($_GET['excluir_setor'])) {
    try {
        $setor_id = intval($_GET['excluir_setor']);
        
        // Buscar o setor antes de excluir
        $stmt = $pdo->prepare("SELECT setor FROM setores WHERE id = ?");
        $stmt->execute([$setor_id]);
        $setor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($setor) {
            // Verificar se há computadores vinculados
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM computadores WHERE setor = ?");
            $stmt->execute([$setor['setor']]);
            $count_computadores = $stmt->fetchColumn();
            
            if ($count_computadores > 0) {
                $_SESSION['erro'] = "Não é possível excluir o setor. Existem $count_computadores computador(es) vinculado(s).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM setores WHERE id = ?");
                $stmt->execute([$setor_id]);
                $_SESSION['sucesso'] = "Setor excluído com sucesso!";
            }
        } else {
            $_SESSION['erro'] = "Setor não encontrado!";
        }
        
        header('Location: gerenciamento_setores.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao excluir: " . $e->getMessage();
        header('Location: gerenciamento_setores.php');
        exit;
    }
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Gerenciamento de Setores</h2>
    <a href="gerenciamento.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <!-- Formulário de Setor -->
        <div class="card border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>
                    <?php echo $setor_editar ? 'Editar Setor' : 'Novo Setor'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao_setor" value="<?php echo $setor_editar ? 'editar' : 'adicionar'; ?>">
                    <input type="hidden" name="setor_id" value="<?php echo isset($setor_editar['id']) ? $setor_editar['id'] : ''; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Setor *</label>
                        <input type="text" 
                               class="form-control" 
                               name="setor_nome" 
                               value="<?php echo isset($setor_editar['setor']) ? htmlspecialchars($setor_editar['setor']) : ''; ?>" 
                               placeholder="Ex: TI, RH, Financeiro" 
                               required
                               maxlength="50">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" 
                                  name="setor_descricao" 
                                  rows="2"
                                  placeholder="Descrição do setor"
                                  maxlength="500"><?php echo isset($setor_editar['descricao']) ? htmlspecialchars($setor_editar['descricao']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Responsável</label>
                        <input type="text" 
                               class="form-control" 
                               name="responsavel" 
                               value="<?php echo isset($setor_editar['responsavel']) ? htmlspecialchars($setor_editar['responsavel']) : ''; ?>" 
                               placeholder="Nome do responsável"
                               maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ramal</label>
                        <input type="text" 
                               class="form-control" 
                               name="telefone" 
                               value="<?php echo isset($setor_editar['telefone']) ? htmlspecialchars($setor_editar['telefone']) : ''; ?>" 
                               placeholder="100"
                               maxlength="4">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">E-mail</label>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               value="<?php echo isset($setor_editar['email']) ? htmlspecialchars($setor_editar['email']) : ''; ?>" 
                               placeholder="setor@empresa.com.br"
                               maxlength="100">
                    </div>
                    
                    <div class="gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> 
                            <?php echo $setor_editar ? 'Atualizar Setor' : 'Cadastrar Setor'; ?>
                        </button>
                        <?php if ($setor_editar): ?>
                            <a href="gerenciamento_setores.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x me-1"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de Setores -->
    <div class="col-lg-12">
        <div class="card border-0">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>
                    Setores Cadastrados
                    <span class="badge bg-primary rounded-pill ms-2"><?php echo count($setores); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($setores)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Setor</th>
                                    <th>Responsável</th>
                                    <th>Contato</th>
                                    <th class="pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($setores as $setor): 
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM computadores WHERE setor = ?");
                                        $stmt->execute([$setor['setor']]);
                                        $count_computadores = $stmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        $count_computadores = 0;
                                    }
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-building text-muted me-2"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($setor['setor']); ?></strong>
                                                    <?php if (!empty($setor['descricao'])): ?>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($setor['descricao']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($count_computadores > 0): ?>
                                                        <br>
                                                        <small>
                                                            <i class="bi bi-pc-display me-1"></i>
                                                            <?php echo $count_computadores; ?> computador(es)
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($setor['responsavel'])): ?>
                                                <i class="bi bi-person me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($setor['responsavel']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <?php if (!empty($setor['telefone'])): ?>
                                                    <small><i class="bi bi-telephone me-1 text-muted"></i> <?php echo htmlspecialchars($setor['telefone']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($setor['email'])): ?>
                                                    <small><i class="bi bi-envelope me-1 text-muted"></i> <?php echo htmlspecialchars($setor['email']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="?editar_setor=<?php echo $setor['id']; ?>" 
                                                   class="btn btn-warning"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?excluir_setor=<?php echo $setor['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Tem certeza que deseja excluir o setor <?php echo addslashes($setor['setor']); ?>?')"
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
                        <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">Nenhum setor cadastrado</p>
                        <a href="gerenciamento_setores.php" class="btn btn-sm btn-primary mt-3">
                            <i class="bi bi-plus me-1"></i> Cadastrar Primeiro Setor
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
$titulo = "Gerenciamento de Setores - Sistema Almoxarifado";
include '../includes/template.php';
?>