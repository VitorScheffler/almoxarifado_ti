<?php
// Padrão: entrada.php
$pagina_atual = 'entrada.php';
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

// Verificar autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';
$sucesso = isset($_GET['success']);

function limparDados($dado, $tipo = 'texto') {
    $dado = trim($dado);
    switch ($tipo) {
        case 'numero':
            $dado = preg_replace('/[^0-9]/', '', $dado);
            break;
        case 'nota_fiscal':
            $dado = preg_replace('/[^0-9\-]/', '', $dado);
            break;
        case 'texto':
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

// Buscar dados para selects
$fornecedores = [];
$itens = [];

try {
    $stmt = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome");
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id, nome FROM itens ORDER BY nome");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar dados: ' . $e->getMessage();
}

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $fornecedor_id = intval($_POST['fornecedor_id'] ?? 0);
    $nota_fiscal = limparDados($_POST['nota_fiscal'] ?? '', 'nota_fiscal');
    $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');
    $observacoes = limparDados($_POST['observacoes'] ?? '');

    // Validações
    if ($item_id <= 0) {
        $erro = 'Selecione um item.';
    } elseif ($quantidade <= 0) {
        $erro = 'A quantidade deve ser maior que zero.';
    } elseif ($fornecedor_id <= 0) {
        $erro = 'Selecione um fornecedor.';
    } elseif (empty($nota_fiscal)) {
        $erro = 'Informe o número da nota fiscal.';
    } else {
        try {
            // Buscar nome do item
            $stmt = $pdo->prepare("SELECT nome FROM itens WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                $erro = 'Item não encontrado.';
            } else {
                // Inserir registro de entrada
                $stmt = $pdo->prepare("INSERT INTO entradas (item_id, quantidade, fornecedor_id, nota_fiscal, data_hora, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_id, $quantidade, $fornecedor_id, $nota_fiscal, $data_hora, $observacoes]);
                
                // Atualizar estoque
                $stmt = $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual + ? WHERE id = ?");
                $stmt->execute([$quantidade, $item_id]);

                // Registrar ação no log
                $acao = "Entrada de {$quantidade} unidades de '{$item['nome']}' - NF: {$nota_fiscal}";
                $stmt = $pdo->prepare("INSERT INTO logs (usuario, acao, data_hora) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['usuario'], $acao]);

                // Redirecionar com sucesso
                header('Location: entrada.php?success=1');
                exit;
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao registrar entrada: ' . $e->getMessage();
        }
    }
}

// Paginação para histórico
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$total_items = 0;
$total_pages = 0;
$entradas = [];

try {
    // Total de registros
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM entradas");
    $total_items = $total_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Buscar registros paginados
    $stmt = $pdo->prepare("
        SELECT e.*, i.nome as item_nome, f.nome as fornecedor 
        FROM entradas e 
        JOIN itens i ON e.item_id = i.id 
        JOIN fornecedores f ON e.fornecedor_id = f.id
        ORDER BY e.data_hora DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (empty($erro)) $erro = 'Erro ao carregar histórico: ' . $e->getMessage();
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Registrar Entrada</h2>
</div>

<?php if ($sucesso): ?>
    <div class="alert alert-primary alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> Entrada registrada com sucesso!
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-box-arrow-in-down"></i> Registrar Nova Entrada
                </h5>
            </div>
            <div class="card-body">
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

                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="item_id" class="form-label">Item *</label>
                            <select name="item_id" 
                                    id="item_id" 
                                    class="form-select" 
                                    required>
                                <option value="">-- Selecione o item --</option>
                                <?php foreach ($itens as $i): ?>
                                    <option value="<?= $i['id'] ?>">
                                        <?= htmlspecialchars($i['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um item.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="quantidade" class="form-label">Quantidade *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-123"></i>
                                </span>
                                <input type="number" 
                                       name="quantidade" 
                                       id="quantidade" 
                                       min="1" 
                                       class="form-control" 
                                       required
                                       value="1"
                                       inputmode="numeric"
                                       placeholder="Quantidade">
                            </div>
                            <div class="invalid-feedback">
                                Informe uma quantidade válida.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fornecedor_id" class="form-label">Fornecedor *</label>
                            <select name="fornecedor_id" 
                                    id="fornecedor_id" 
                                    class="form-select" 
                                    required>
                                <option value="">-- Selecione o fornecedor --</option>
                                <?php foreach ($fornecedores as $f): ?>
                                    <option value="<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione um fornecedor.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="nota_fiscal" class="form-label">Nota Fiscal *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-receipt"></i>
                                </span>
                                <input type="text" 
                                       name="nota_fiscal" 
                                       id="nota_fiscal" 
                                       class="form-control nota-fiscal-mask" 
                                       required
                                       placeholder="Número da nota fiscal"
                                       maxlength="20">
                            </div>
                            <div class="invalid-feedback">
                                Informe o número da nota fiscal.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="data_hora" class="form-label">Data e Hora</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="datetime-local" 
                                       name="data_hora" 
                                       id="data_hora" 
                                       class="form-control" 
                                       value="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea name="observacoes" 
                                      id="observacoes" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Observações sobre a entrada (opcional)"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-down"></i> Registrar Entrada
                                </button>
                                <button type="reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Últimas Entradas</h5>
                    <span class="badge bg-secondary">
                        <?= $total_items ?> registros
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($entradas)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Qtd</th>
                                    <th>Fornecedor</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entradas as $entrada): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($entrada['item_nome']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= $entrada['quantidade'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($entrada['fornecedor']) ?></small>
                                            <?php if (!empty($entrada['nota_fiscal'])): ?>
                                                <br>
                                                <span class="badge bg-info text-dark badge-sm">
                                                    NF: <?= htmlspecialchars($entrada['nota_fiscal']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($entrada['data_hora'])) ?>
                                                <br>
                                                <?= date('H:i', strtotime($entrada['data_hora'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($p = $start_page; $p <= $end_page; $p++): ?>
                                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-box-arrow-in-down display-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted">Nenhuma entrada registrada</h5>
                        <p class="text-muted">Registre sua primeira entrada acima</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focar no primeiro campo
    document.getElementById('item_id').focus();
    
    // Máscara para nota fiscal
    const notaFiscalInputs = document.querySelectorAll('.nota-fiscal-mask');
    notaFiscalInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            // Permite apenas números e hífen
            e.target.value = e.target.value.replace(/[^0-9\-]/g, '');
        });
    });
    
    // Configurar data/hora atual
    const dataHoraInput = document.getElementById('data_hora');
    if (dataHoraInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        if (!dataHoraInput.value) {
            dataHoraInput.value = now.toISOString().slice(0, 16);
        }
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
    
    // Sugerir data de hoje para nota fiscal
    const notaFiscalInput = document.getElementById('nota_fiscal');
    if (notaFiscalInput) {
        notaFiscalInput.addEventListener('focus', function() {
            if (!this.value.trim()) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                // Sugerir um formato: ANO-MÊS-XXXX
                this.placeholder = `Ex: ${year}${month}-XXXX`;
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Registrar Entrada - Sistema Almoxarifado";
$pagina_atual = 'entrada.php';

include '../includes/template.php';