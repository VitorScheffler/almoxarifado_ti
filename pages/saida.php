<?php
// Padrão: saida.php
$pagina_atual = 'saida.php';
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
        case 'texto':
        default:
            $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
    return $dado;
}

// Processar formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $usuario_destino = limparDados($_POST['usuario_destino'] ?? '');
    $motivo = limparDados($_POST['motivo'] ?? '');
    $data_hora = $_POST['data_hora'] ?? date('Y-m-d\TH:i');
    $observacoes = limparDados($_POST['observacoes'] ?? '');

    // Validações
    if ($item_id <= 0) {
        $erro = 'Selecione um item.';
    } elseif ($quantidade <= 0) {
        $erro = 'A quantidade deve ser maior que zero.';
    } elseif (empty($usuario_destino)) {
        $erro = 'Informe o usuário destino.';
    } elseif (empty($motivo)) {
        $erro = 'Selecione um motivo.';
    } else {
        try {
            // Verificar estoque disponível
            $stmt = $pdo->prepare("SELECT quantidade_atual, nome FROM itens WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $erro = 'Item não encontrado.';
            } elseif ($item['quantidade_atual'] < $quantidade) {
                $erro = 'Estoque insuficiente. Disponível: ' . $item['quantidade_atual'] . ' unidades.';
            } else {
                // Inserir registro de saída
                $stmt = $pdo->prepare("INSERT INTO saidas (item_id, quantidade, usuario_destino, motivo, data_hora, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_id, $quantidade, $usuario_destino, $motivo, $data_hora, $observacoes]);
                
                // Atualizar estoque
                $stmt = $pdo->prepare("UPDATE itens SET quantidade_atual = quantidade_atual - ? WHERE id = ?");
                $stmt->execute([$quantidade, $item_id]);

                // Registrar ação no log
                $acao = "Saída de {$quantidade} unidades de '{$item['nome']}' para '{$usuario_destino}'";
                $stmt = $pdo->prepare("INSERT INTO logs (usuario, acao, data_hora) VALUES (?, ?, NOW())");
                $stmt->execute([$_SESSION['usuario'], $acao]);

                // Redirecionar com sucesso
                header('Location: saida.php?success=1');
                exit;
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao registrar saída: ' . $e->getMessage();
        }
    }
}

// Buscar itens para o select
$itens = [];
try {
    $stmt = $pdo->query("SELECT id, nome, quantidade_atual FROM itens WHERE quantidade_atual > 0 ORDER BY nome");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar itens: ' . $e->getMessage();
}

// Paginação para histórico
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

$total_items = 0;
$total_pages = 0;
$saidas = [];

try {
    // Total de registros
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM saidas");
    $total_items = $total_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
    
    // Buscar registros paginados
    $stmt = $pdo->prepare("
        SELECT s.*, i.nome as item_nome 
        FROM saidas s 
        JOIN itens i ON s.item_id = i.id 
        ORDER BY s.data_hora DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $saidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar histórico: ' . $e->getMessage();
}

// Início do conteúdo
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Registrar Saída</h2>
</div>

<?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> Saída registrada com sucesso!
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-box-arrow-up"></i> Registrar Nova Saída
                </h5>
            </div>
            <div class="card-body">
                <?php if ($erro): ?>
                    <div class="alert alert-primary alert-dismissible fade show" role="alert">
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
                                <?php foreach ($itens as $item): ?>
                                    <option value="<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['nome']) ?> 
                                        (Estoque: <span class="text-success"><?= $item['quantidade_atual'] ?></span>)
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
                                       class="form-control"
                                       required
                                       min="1"
                                       value="1"
                                       inputmode="numeric"
                                       placeholder="Quantidade">
                            </div>
                            <div class="invalid-feedback">
                                Informe uma quantidade válida.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="usuario_destino" class="form-label">Destinatário *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text"
                                       name="usuario_destino"
                                       id="usuario_destino"
                                       class="form-control"
                                       required
                                       placeholder="Nome do destinatário">
                            </div>
                            <div class="invalid-feedback">
                                Informe o nome do destinatário.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="motivo" class="form-label">Motivo *</label>
                            <select name="motivo" 
                                    id="motivo" 
                                    class="form-select" 
                                    required>
                                <option value="">-- Selecione o motivo --</option>
                                <option value="Substituição">Substituição</option>
                                <option value="Manutenção">Manutenção</option>
                                <option value="Instalação">Instalação</option>
                                <option value="Projeto">Projeto</option>
                                <option value="Empréstimo">Empréstimo</option>
                                <option value="Descarte">Descarte</option>
                                <option value="Outro">Outro</option>
                            </select>
                            <div class="invalid-feedback">
                                Selecione um motivo.
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
                                      placeholder="Observações adicionais (opcional)"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-up"></i> Registrar Saída
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
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Últimas Saídas</h5>
                    <span class="badge bg-secondary">
                        <?= $total_items ?> registros
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($saidas)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Qtd</th>
                                    <th>Destino</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saidas as $saida): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($saida['item_nome']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= $saida['quantidade'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($saida['usuario_destino']) ?></small>
                                            <?php if (!empty($saida['motivo'])): ?>
                                                <br>
                                                <span class="badge bg-secondary badge-sm">
                                                    <?= htmlspecialchars($saida['motivo']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($saida['data_hora'])) ?>
                                                <br>
                                                <?= date('H:i', strtotime($saida['data_hora'])) ?>
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
                            <i class="bi bi-box-arrow-up display-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted">Nenhuma saída registrada</h5>
                        <p class="text-muted">Registre sua primeira saída acima</p>
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
    
    // Atualizar quantidade máxima quando selecionar item
    const itemSelect = document.getElementById('item_id');
    const quantidadeInput = document.getElementById('quantidade');
    
    if (itemSelect && quantidadeInput) {
        itemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const text = selectedOption.text;
                const estoqueMatch = text.match(/Estoque:\s*<span[^>]*>(\d+)<\/span>/);
                if (estoqueMatch) {
                    const estoque = parseInt(estoqueMatch[1]);
                    quantidadeInput.max = estoque;
                    quantidadeInput.setAttribute('title', `Máximo: ${estoque} unidades`);
                    
                    // Se a quantidade atual for maior que o estoque, ajustar
                    if (parseInt(quantidadeInput.value) > estoque) {
                        quantidadeInput.value = estoque;
                    }
                }
            }
        });
    }
    
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
    
    // Auto-completar para "Outro" motivo
    const motivoSelect = document.getElementById('motivo');
    const observacoesTextarea = document.getElementById('observacoes');
    
    if (motivoSelect && observacoesTextarea) {
        motivoSelect.addEventListener('change', function() {
            if (this.value === 'Outro' && !observacoesTextarea.value.trim()) {
                observacoesTextarea.placeholder = 'Por favor, especifique o motivo da saída...';
                observacoesTextarea.focus();
            }
        });
    }
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Registrar Saída - Sistema Almoxarifado";
$pagina_atual = 'saida.php';

include '../includes/template.php';