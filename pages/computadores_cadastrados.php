<?php
$pagina_atual = 'computadores_cadastrados.php';
require '../includes/config.php';

$erro = '';
$sucesso = '';

function limparDados($dado)
{
    $dado = trim($dado);
    $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    return $dado;
}

$setores = [];
try {
    $stmt = $pdo->query("SELECT id, setor FROM setor ORDER BY setor");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar setores: " . $e->getMessage();
}

$id_edit = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$computador = null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor_id = intval($_POST['setor_id'] ?? 0);
    $patrimonio_computador = limparDados($_POST['patrimonio_computador'] ?? '');
    $patrimonio_monitor1 = limparDados($_POST['patrimonio_monitor1'] ?? '');
    $patrimonio_monitor2 = limparDados($_POST['patrimonio_monitor2'] ?? '');
    $usuario = limparDados($_POST['usuario'] ?? '');
    $observacoes = limparDados($_POST['observacoes'] ?? '');
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

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

    if ($setor_id <= 0) {
        $erro = "O campo setor é obrigatório.";
    } elseif (empty($patrimonio_computador)) {
        $erro = "O campo patrimônio do computador é obrigatório.";
    } elseif (strlen($patrimonio_computador) > 100) {
        $erro = "O patrimônio do computador deve ter no máximo 100 caracteres.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ? AND id != ?");
                $stmt->execute([$patrimonio_computador, $id]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    $stmt = $pdo->prepare("UPDATE computadores SET setor=?, patrimonio_computador=?, patrimonio_monitor1=?, patrimonio_monitor2=?, usuario=?, observacoes=? WHERE id=?");
                    $stmt->execute([$setor_nome, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes, $id]);
                    $sucesso = "Computador atualizado com sucesso!";
                    $id_edit = 0;
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM computadores WHERE patrimonio_computador = ?");
                $stmt->execute([$patrimonio_computador]);
                if ($stmt->fetch()) {
                    $erro = "Já existe um computador cadastrado com este patrimônio.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO computadores (setor, patrimonio_computador, patrimonio_monitor1, patrimonio_monitor2, usuario, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$setor_nome, $patrimonio_computador, $patrimonio_monitor1, $patrimonio_monitor2, $usuario, $observacoes]);
                    $sucesso = "Computador cadastrado com sucesso!";
                }
            }

            if (empty($erro)) {
                $computador = null;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao salvar computador: " . $e->getMessage();
        }
    }
}

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

try {
    $computadores = $pdo->query("SELECT * FROM computadores ORDER BY setor, usuario")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar computadores: " . $e->getMessage();
    $computadores = [];
}

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Computadores Cadastrados</h2>
    <span class="text-muted">
        <?= count($computadores) ?> registro(s)
    </span>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $erro ?>
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $sucesso ?>
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="alert" 
                aria-label="Close">
        </button>
    </div>
<?php endif; ?>

<?php if ($id_edit > 0 && $computador): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Editar Computador</h5>
        </div>
        <div class="card-body">
            <form method="post">
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
                                <option value="<?= $setor['id'] ?>" 
                                    <?= ($computador['setor'] === $setor['setor']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($setor['setor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="patrimonio_computador" class="form-label">Patrimônio Computador *</label>
                        <input type="text" 
                               class="form-control" 
                               id="patrimonio_computador" 
                               name="patrimonio_computador" 
                               required
                               inputmode="numeric"
                               pattern="[0-9]*"
                               value="<?= htmlspecialchars($computador['patrimonio_computador']) ?>"
                               placeholder="000000"
                               title="Digite apenas números">
                    </div>

                    <div class="col-md-6">
                        <label for="patrimonio_monitor1" class="form-label">Patrimônio Monitor 1</label>
                        <input type="text" 
                               class="form-control" 
                               id="patrimonio_monitor1" 
                               name="patrimonio_monitor1"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               value="<?= htmlspecialchars($computador['patrimonio_monitor1']) ?>"
                               placeholder="000000"
                               title="Digite apenas números">
                    </div>

                    <div class="col-md-6">
                        <label for="patrimonio_monitor2" class="form-label">Patrimônio Monitor 2</label>
                        <input type="text" 
                               class="form-control" 
                               id="patrimonio_monitor2" 
                               name="patrimonio_monitor2"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               value="<?= htmlspecialchars($computador['patrimonio_monitor2']) ?>"
                               placeholder="000000"
                               title="Digite apenas números">
                    </div>

                    <div class="col-md-6">
                        <label for="usuario" class="form-label">Usuário</label>
                        <input type="text" 
                               class="form-control" 
                               id="usuario" 
                               name="usuario"
                               value="<?= htmlspecialchars($computador['usuario']) ?>"
                               placeholder="Nome do usuário">
                    </div>

                    <div class="col-12">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" 
                                  id="observacoes" 
                                  name="observacoes" 
                                  rows="3"
                                  placeholder="Observações sobre o computador"><?= htmlspecialchars($computador['observacoes']) ?></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar
                        </button>
                        <a href="computadores_cadastrados.php" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Cancelar
                        </a>
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
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($computadores)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
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
                                    <?= !empty($c['usuario']) ? htmlspecialchars($c['usuario']) : '<span class="text-muted">Não informado</span>' ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($c['patrimonio_computador']) ?>
                                </td>
                                <td>
                                    <?= !empty($c['patrimonio_monitor1']) ? htmlspecialchars($c['patrimonio_monitor1']) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <?= !empty($c['patrimonio_monitor2']) ? htmlspecialchars($c['patrimonio_monitor2']) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['observacoes'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars(substr($c['observacoes'], 0, 50)) ?>...</small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?edit=<?= $c['id'] ?>" 
                                        class="btn btn-warning"
                                        title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?del=<?= $c['id'] ?>" 
                                        class="btn btn-danger" 
                                        onclick="return confirm('Tem certeza que deseja excluir este computador?')"
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
            <div class="alert alert-info mb-0">
                Nenhum computador cadastrado. <a href="computadores.php">Cadastre seu primeiro computador</a>.
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$conteudo = ob_get_clean();

$titulo = "Computadores Cadastrados - Almoxarifado TI";

include '../includes/template.php';