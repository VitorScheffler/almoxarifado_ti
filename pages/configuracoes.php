<?php
// Padrão: configuracoes.php
$pagina_atual = 'configuracoes.php';
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';
$sucesso = '';
$mensagem = '';

// Processar ações
$acao = $_GET['acao'] ?? '';
$id = $_GET['id'] ?? 0;

// Cores disponíveis para badges
$cores_disponiveis = [
    'primary' => '#0d6efd',
    'secondary' => '#6c757d',
    'success' => '#198754',
    'danger' => '#dc3545',
    'warning' => '#ffc107',
    'info' => '#0dcaf0',
    'light' => '#f8f9fa',
    'dark' => '#212529',
    'purple' => '#6f42c1',
    'pink' => '#d63384',
    'orange' => '#fd7e14',
    'teal' => '#20c997',
];

// Buscar todas as categorias existentes na tabela itens
$categorias = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM itens WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar categorias: " . $e->getMessage();
}

// Buscar todos os setores
$setores = [];
try {
    $stmt = $pdo->query("SELECT * FROM setor ORDER BY setor");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar setores: " . $e->getMessage();
}

// Processar ações para categorias
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao_categoria'])) {
        $acao_cat = $_POST['acao_categoria'];
        $categoria_nome = trim($_POST['categoria_nome'] ?? '');
        $categoria_antiga = trim($_POST['categoria_antiga'] ?? '');
        
        if (empty($categoria_nome)) {
            $erro = "O nome da categoria é obrigatório.";
        } elseif (strlen($categoria_nome) > 100) {
            $erro = "O nome da categoria deve ter no máximo 100 caracteres.";
        } else {
            try {
                if ($acao_cat === 'adicionar') {
                    // Verificar se já existe
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                    $stmt->execute([$categoria_nome]);
                    if ($stmt->fetchColumn() > 0) {
                        $erro = "Esta categoria já existe.";
                    } else {
                        $sucesso = "Categoria '$categoria_nome' está disponível para uso. Será salva quando for usada em um item.";
                    }
                } 
                elseif ($acao_cat === 'renomear') {
                    if (empty($categoria_antiga)) {
                        $erro = "Categoria antiga não especificada.";
                    } else {
                        // Atualizar todos os itens com a categoria antiga
                        $stmt = $pdo->prepare("UPDATE itens SET categoria = ? WHERE categoria = ?");
                        $stmt->execute([$categoria_nome, $categoria_antiga]);
                        $sucesso = "Categoria renomeada de '$categoria_antiga' para '$categoria_nome' em " . $stmt->rowCount() . " itens.";
                    }
                }
                elseif ($acao_cat === 'remover') {
                    if (empty($categoria_antiga)) {
                        $erro = "Categoria não especificada.";
                    } else {
                        // Verificar se há itens usando esta categoria
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                        $stmt->execute([$categoria_antiga]);
                        $count = $stmt->fetchColumn();
                        
                        if ($count > 0) {
                            // Remover categoria dos itens (definir como NULL)
                            $stmt = $pdo->prepare("UPDATE itens SET categoria = NULL WHERE categoria = ?");
                            $stmt->execute([$categoria_antiga]);
                            $sucesso = "Categoria '$categoria_antiga' removida de " . $stmt->rowCount() . " itens.";
                        } else {
                            $sucesso = "Categoria '$categoria_antiga' não estava em uso.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $erro = "Erro ao processar categoria: " . $e->getMessage();
            }
        }
    }
    
    // Processar ações para setores
    elseif (isset($_POST['acao_setor'])) {
        $acao_setor = $_POST['acao_setor'];
        $setor_id = $_POST['setor_id'] ?? 0;
        $setor_nome = trim($_POST['setor_nome'] ?? '');
        $setor_descricao = trim($_POST['setor_descricao'] ?? '');
        $responsavel = trim($_POST['responsavel'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($setor_nome)) {
            $erro = "O nome do setor é obrigatório.";
        } elseif (strlen($setor_nome) > 50) {
            $erro = "O nome do setor deve ter no máximo 50 caracteres.";
        } else {
            try {
                if ($acao_setor === 'adicionar') {
                    // Inserir novo setor
                    $stmt = $pdo->prepare("INSERT INTO setor (setor, descricao, responsavel, telefone, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$setor_nome, $setor_descricao, $responsavel, $telefone, $email]);
                    $sucesso = "Setor '$setor_nome' adicionado com sucesso!";
                    
                    // Atualizar lista de setores
                    $stmt = $pdo->query("SELECT * FROM setor ORDER BY setor");
                    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                elseif ($acao_setor === 'editar') {
                    if ($setor_id > 0) {
                        // Atualizar setor existente
                        $stmt = $pdo->prepare("UPDATE setor SET setor = ?, descricao = ?, responsavel = ?, telefone = ?, email = ? WHERE id = ?");
                        $stmt->execute([$setor_nome, $setor_descricao, $responsavel, $telefone, $email, $setor_id]);
                        $sucesso = "Setor atualizado com sucesso!";
                        
                        // Atualizar lista de setores
                        $stmt = $pdo->query("SELECT * FROM setor ORDER BY setor");
                        $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $erro = "ID do setor inválido.";
                    }
                }
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $erro = "Já existe um setor com este nome.";
                } else {
                    $erro = "Erro ao processar setor: " . $e->getMessage();
                }
            }
        }
    }
    
    // Processar configurações do sistema
    elseif (isset($_POST['acao'])) {
        $acao_config = $_POST['acao'];
        $config_file = __DIR__ . '/../config_sistema.json';
        
        // Carrega configurações atuais
        if (file_exists($config_file)) {
            $config = json_decode(file_get_contents($config_file), true);
        } else {
            $config = [];
        }
        
        try {
            if ($acao_config === 'salvar_banco') {
                // Atualiza configurações do banco
                $config['db_host'] = trim($_POST['db_host']);
                $config['db_name'] = trim($_POST['db_name']);
                $config['db_user'] = trim($_POST['db_user']);
                $config['db_pass'] = trim($_POST['db_pass']);
                
                $sucesso = "Configurações do banco salvas com sucesso! Reinicie o sistema para aplicar as mudanças.";
                
            } elseif ($acao_config === 'salvar_ldap') {
                // Atualiza configurações LDAP
                $config['ldap_server'] = trim($_POST['ldap_server']);
                $config['ldap_port'] = trim($_POST['ldap_port']);
                $config['ldap_domain'] = trim($_POST['ldap_domain']);
                $config['ldap_base_dn'] = trim($_POST['ldap_base_dn']);
                $config['ldap_grupo'] = trim($_POST['ldap_grupo']);
                
                $sucesso = "Configurações LDAP salvas com sucesso!";
                
            } elseif ($acao_config === 'salvar_geral') {
                // Atualiza configurações gerais
                $config['empresa_nome'] = trim($_POST['empresa_nome']);
                $config['sistema_nome'] = trim($_POST['sistema_nome']);
                $config['empresa_logo'] = trim($_POST['empresa_logo']);
                $config['timezone'] = trim($_POST['timezone']);
                
                $sucesso = "Configurações gerais salvas com sucesso!";
            }
            
            // Salva as configurações no arquivo JSON
            file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
        } catch (Exception $e) {
            $erro = "Erro ao salvar configurações: " . $e->getMessage();
        }
    }
}

// Processar exclusão de setor via GET
if (isset($_GET['excluir_setor']) && is_numeric($_GET['excluir_setor'])) {
    $setor_id_excluir = (int)$_GET['excluir_setor'];
    
    try {
        // Verificar se há computadores usando este setor
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM computadores WHERE setor = (SELECT setor FROM setor WHERE id = ?)");
        $stmt->execute([$setor_id_excluir]);
        $count_computadores = $stmt->fetchColumn();
        
        if ($count_computadores > 0) {
            $erro = "Não é possível excluir este setor porque existem $count_computadores computador(es) vinculados a ele.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM setor WHERE id = ?");
            $stmt->execute([$setor_id_excluir]);
            $sucesso = "Setor excluído com sucesso!";
            
            // Atualizar lista de setores
            $stmt = $pdo->query("SELECT * FROM setor ORDER BY setor");
            $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $erro = "Erro ao excluir setor: " . $e->getMessage();
    }
}

// Buscar setor para edição
$setor_editar = null;
if (isset($_GET['editar_setor']) && is_numeric($_GET['editar_setor'])) {
    $setor_id_editar = (int)$_GET['editar_setor'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM setor WHERE id = ?");
        $stmt->execute([$setor_id_editar]);
        $setor_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro = "Erro ao buscar setor: " . $e->getMessage();
    }
}

// Buscar estatísticas
$estatisticas = [];
try {
    // Contar itens por categoria
    $stmt = $pdo->query("
        SELECT 
            COALESCE(categoria, 'Sem categoria') as categoria_nome,
            COUNT(*) as total_itens,
            SUM(quantidade_atual) as estoque_total
        FROM itens 
        GROUP BY categoria 
        ORDER BY total_itens DESC
    ");
    $estatisticas['itens_por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar computadores por setor
    $stmt = $pdo->query("
        SELECT 
            c.setor,
            COUNT(*) as total_computadores
        FROM computadores c
        GROUP BY c.setor 
        ORDER BY total_computadores DESC
    ");
    $estatisticas['computadores_por_setor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total de categorias únicas
    $stmt = $pdo->query("SELECT COUNT(DISTINCT categoria) as total FROM itens WHERE categoria IS NOT NULL AND categoria != ''");
    $estatisticas['total_categorias'] = $stmt->fetchColumn();
    
    // Total de setores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM setor");
    $estatisticas['total_setores'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // Ignorar erros nas estatísticas
}

// Início do conteúdo
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Configurações do Sistema</h2>
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

<div class="row">
    <!-- Painel de Estatísticas -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Estatísticas do Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card border-primary mb-3">
                            <div class="card-body text-center">
                                <h1 class="display-6 text-primary"><?= $estatisticas['total_categorias'] ?? 0 ?></h1>
                                <p class="mb-0">Categorias de Itens</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success mb-3">
                            <div class="card-body text-center">
                                <h1 class="display-6 text-success"><?= $estatisticas['total_setores'] ?? 0 ?></h1>
                                <p class="mb-0">Setores Cadastrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Resumo das Configurações</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Total de itens cadastrados
                                        <span class="badge bg-primary rounded-pill">
                                            <?= $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn() ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Total de computadores
                                        <span class="badge bg-success rounded-pill">
                                            <?= $pdo->query("SELECT COUNT(*) FROM computadores")->fetchColumn() ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Itens sem categoria
                                        <span class="badge bg-warning rounded-pill">
                                            <?= $pdo->query("SELECT COUNT(*) FROM itens WHERE categoria IS NULL OR categoria = ''")->fetchColumn() ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de Categorias -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="bi bi-tags"></i> Gerenciamento de Categorias
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="border-bottom pb-2">Adicionar Nova Categoria</h6>
                    <form method="post" class="mb-3">
                        <input type="hidden" name="acao_categoria" value="adicionar">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="categoria_nome" 
                                   placeholder="Digite o nome da nova categoria" 
                                   required
                                   maxlength="100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Adicionar
                            </button>
                        </div>
                        <div class="form-text mt-2">
                            A categoria ficará disponível para uso nos itens. Não será salva até ser utilizada.
                        </div>
                    </form>
                </div>

                <div class="mb-4">
                    <h6 class="border-bottom pb-2">Renomear Categoria</h6>
                    <form method="post" class="mb-3">
                        <input type="hidden" name="acao_categoria" value="renomear">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <select class="form-select" name="categoria_antiga" required>
                                    <option value="">Selecione a categoria</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['categoria']) ?>">
                                            <?= htmlspecialchars($cat['categoria']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" 
                                       class="form-control" 
                                       name="categoria_nome" 
                                       placeholder="Novo nome" 
                                       required
                                       maxlength="100">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Renomear
                        </button>
                        <div class="form-text mt-2">
                            Todos os itens com esta categoria serão atualizados.
                        </div>
                    </form>
                </div>

                <div>
                    <h6 class="border-bottom pb-2">Remover Categoria</h6>
                    <form method="post" onsubmit="return confirm('Tem certeza que deseja remover esta categoria? Os itens ficarão sem categoria.')">
                        <input type="hidden" name="acao_categoria" value="remover">
                        <div class="input-group mb-3">
                            <select class="form-select" name="categoria_antiga" required>
                                <option value="">Selecione a categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['categoria']) ?>">
                                        <?= htmlspecialchars($cat['categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                        <div class="form-text">
                            A categoria será removida de todos os itens que a utilizam.
                        </div>
                    </form>
                </div>

                <?php if (!empty($categorias)): ?>
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">Categorias Existentes (<?= count($categorias) ?>)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($categorias as $cat): 
                                // Buscar quantos itens usam esta categoria
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE categoria = ?");
                                $stmt->execute([$cat['categoria']]);
                                $count = $stmt->fetchColumn();
                            ?>
                                <span class="badge bg-info text-dark p-2">
                                    <i class="bi bi-tag"></i> 
                                    <?= htmlspecialchars($cat['categoria']) ?>
                                    <span class="badge bg-light text-dark ms-1"><?= $count ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de Setores -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-building"></i> Gerenciamento de Setores
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="border-bottom pb-2">
                        <?= $setor_editar ? 'Editar Setor' : 'Adicionar Novo Setor' ?>
                    </h6>
                    <form method="post">
                        <input type="hidden" name="acao_setor" value="<?= $setor_editar ? 'editar' : 'adicionar' ?>">
                        <input type="hidden" name="setor_id" value="<?= $setor_editar['id'] ?? '' ?>">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <label for="setor_nome" class="form-label">Nome do Setor *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="setor_nome" 
                                       name="setor_nome" 
                                       value="<?= htmlspecialchars($setor_editar['setor'] ?? '') ?>" 
                                       placeholder="Ex: TI, RH, Financeiro" 
                                       required
                                       maxlength="50">
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <label for="setor_descricao" class="form-label">Descrição (opcional)</label>
                                <textarea class="form-control" 
                                          id="setor_descricao" 
                                          name="setor_descricao" 
                                          rows="2"
                                          placeholder="Descrição do setor"
                                          maxlength="500"><?= htmlspecialchars($setor_editar['descricao'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="responsavel" class="form-label">Responsável</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="responsavel" 
                                       name="responsavel" 
                                       value="<?= htmlspecialchars($setor_editar['responsavel'] ?? '') ?>" 
                                       placeholder="Nome do responsável"
                                       maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="telefone" 
                                       name="telefone" 
                                       value="<?= htmlspecialchars($setor_editar['telefone'] ?? '') ?>" 
                                       placeholder="(11) 99999-9999"
                                       maxlength="20">
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($setor_editar['email'] ?? '') ?>" 
                                       placeholder="setor@empresa.com.br"
                                       maxlength="100">
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> <?= $setor_editar ? 'Atualizar' : 'Salvar' ?>
                            </button>
                            <?php if ($setor_editar): ?>
                                <a href="configuracoes.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <?php if (!empty($setores)): ?>
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2">Setores Cadastrados (<?= count($setores) ?>)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Setor</th>
                                        <th>Responsável</th>
                                        <th>Telefone</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($setores as $setor): 
                                        // Buscar quantos computadores neste setor
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM computadores WHERE setor = ?");
                                        $stmt->execute([$setor['setor']]);
                                        $count_computadores = $stmt->fetchColumn();
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($setor['setor']) ?></strong>
                                                <?php if (!empty($setor['descricao'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($setor['descricao']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($setor['responsavel']) ? htmlspecialchars($setor['responsavel']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <?= !empty($setor['telefone']) ? htmlspecialchars($setor['telefone']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?editar_setor=<?= $setor['id'] ?>" 
                                                       class="btn btn-warning"
                                                       title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?excluir_setor=<?= $setor['id'] ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir o setor <?= addslashes($setor['setor']) ?>?')"
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
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Configurações do Sistema -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="bi bi-gear"></i> Configurações do Sistema
                </h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="configTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="banco-tab" data-bs-toggle="tab" data-bs-target="#banco" type="button" role="tab">
                            <i class="bi bi-database"></i> Banco de Dados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ldap-tab" data-bs-toggle="tab" data-bs-target="#ldap" type="button" role="tab">
                            <i class="bi bi-person-badge"></i> LDAP
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="geral-tab" data-bs-toggle="tab" data-bs-target="#geral" type="button" role="tab">
                            <i class="bi bi-building"></i> Empresa
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="configTabContent">
                    <!-- Banco de Dados -->
                    <div class="tab-pane fade show active" id="banco" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="acao" value="salvar_banco">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="db_host" class="form-label">Host do Banco</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" 
                                           value="<?= htmlspecialchars(DB_HOST) ?>" required>
                                    <div class="form-text">Ex: localhost, 127.0.0.1, mysql.meuservidor.com</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="db_name" class="form-label">Nome do Banco</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" 
                                           value="<?= htmlspecialchars(DB_NAME) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="db_user" class="form-label">Usuário</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" 
                                           value="<?= htmlspecialchars(DB_USER) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="db_pass" class="form-label">Senha</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                               value="<?= htmlspecialchars(DB_PASS) ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('db_pass')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Salvar Configurações do Banco
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="testarConexaoBanco()">
                                        <i class="bi bi-plug"></i> Testar Conexão
                                    </button>
                                    <div id="testeConexao" class="mt-2"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- LDAP -->
                    <div class="tab-pane fade" id="ldap" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="acao" value="salvar_ldap">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="ldap_server" class="form-label">Servidor LDAP</label>
                                    <input type="text" class="form-control" id="ldap_server" name="ldap_server" 
                                           value="<?= htmlspecialchars(LDAP_SERVER) ?>" required>
                                    <div class="form-text">Ex: ldap://192.168.0.6, ldaps://ldap.empresa.com</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ldap_port" class="form-label">Porta</label>
                                    <input type="number" class="form-control" id="ldap_port" name="ldap_port" 
                                           value="<?= htmlspecialchars(LDAP_PORT) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ldap_domain" class="form-label">Domínio</label>
                                    <input type="text" class="form-control" id="ldap_domain" name="ldap_domain" 
                                           value="<?= htmlspecialchars(LDAP_DOMAIN) ?>" required>
                                    <div class="form-text">Ex: empresa.com.br</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="ldap_base_dn" class="form-label">Base DN</label>
                                    <input type="text" class="form-control" id="ldap_base_dn" name="ldap_base_dn" 
                                           value="<?= htmlspecialchars(LDAP_BASE_DN) ?>" required>
                                    <div class="form-text">Ex: DC=empresa,DC=com,DC=br</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="ldap_grupo" class="form-label">Grupo Autorizado (DN Completo)</label>
                                    <textarea class="form-control" id="ldap_grupo" name="ldap_grupo" rows="3" 
                                              required><?= htmlspecialchars(LDAP_GRUPO) ?></textarea>
                                    <div class="form-text">Distinguished Name completo do grupo que tem acesso</div>
                                </div>
                                
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Salvar Configurações LDAP
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Empresa -->
                    <div class="tab-pane fade" id="geral" role="tabpanel">
                        <form method="post">
                            <input type="hidden" name="acao" value="salvar_geral">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="empresa_nome" class="form-label">Nome da Empresa</label>
                                    <input type="text" class="form-control" id="empresa_nome" name="empresa_nome" 
                                           value="<?= htmlspecialchars(EMPRESA_NOME) ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="sistema_nome" class="form-label">Nome do Sistema</label>
                                    <input type="text" class="form-control" id="sistema_nome" name="sistema_nome" 
                                           value="<?= htmlspecialchars(SISTEMA_NOME) ?>" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="empresa_logo" class="form-label">Caminho do Logo</label>
                                    <input type="text" class="form-control" id="empresa_logo" name="empresa_logo" 
                                           value="<?= htmlspecialchars(EMPRESA_LOGO) ?>">
                                    <div class="form-text">Caminho relativo para o arquivo de logo</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Fuso Horário</label>
                                    <select class="form-select" id="timezone" name="timezone" required>
                                        <option value="America/Sao_Paulo" <?= TIMEZONE == 'America/Sao_Paulo' ? 'selected' : '' ?>>São Paulo</option>
                                        <option value="America/Manaus" <?= TIMEZONE == 'America/Manaus' ? 'selected' : '' ?>>Manaus</option>
                                        <option value="America/Bahia" <?= TIMEZONE == 'America/Bahia' ? 'selected' : '' ?>>Bahia</option>
                                        <option value="America/Fortaleza" <?= TIMEZONE == 'America/Fortaleza' ? 'selected' : '' ?>>Fortaleza</option>
                                        <option value="America/Recife" <?= TIMEZONE == 'America/Recife' ? 'selected' : '' ?>>Recife</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Salvar Configurações Gerais
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no primeiro campo
    const firstInput = document.querySelector('input[type="text"]');
    if (firstInput) firstInput.focus();
    
    // Máscara para telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/^(\d*)$/, '($1');
            }
            e.target.value = value;
        });
    }
    
    // Confirmação para ações importantes
    const formsRemover = document.querySelectorAll('form[onsubmit*="confirm"]');
    formsRemover.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Esta ação não pode ser desfeita. Tem certeza que deseja continuar?')) {
                e.preventDefault();
            }
        });
    });
    
    // Ativar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Funções para configurações do sistema
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function testarConexaoBanco() {
    const db_host = document.getElementById('db_host').value;
    const db_name = document.getElementById('db_name').value;
    const db_user = document.getElementById('db_user').value;
    const db_pass = document.getElementById('db_pass').value;
    
    const resultado = document.getElementById('testeConexao');
    resultado.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Testando conexão...</div>';
    
    // Cria um formulário dinâmico para enviar via AJAX
    const formData = new FormData();
    formData.append('db_host', db_host);
    formData.append('db_name', db_name);
    formData.append('db_user', db_user);
    formData.append('db_pass', db_pass);
    
    // Faz uma requisição AJAX para testar a conexão
    fetch('../includes/testa_conexao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultado.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle"></i> ${data.message}</div>`;
        } else {
            resultado.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultado.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Erro ao testar conexão: ${error}</div>`;
    });
}
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Configurações - Sistema Almoxarifado";
$pagina_atual = 'configuracoes.php';

include '../includes/template.php';