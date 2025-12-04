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

// Buscar estatísticas
try {
    $total_categorias = $pdo->query("SELECT COUNT(DISTINCT categoria) FROM itens WHERE categoria IS NOT NULL AND categoria != ''")->fetchColumn();
    $total_setores = $pdo->query("SELECT COUNT(*) FROM setores")->fetchColumn();
    $total_itens = $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn();
    $total_computadores = $pdo->query("SELECT COUNT(*) FROM computadores")->fetchColumn();
    $itens_sem_categoria = $pdo->query("SELECT COUNT(*) FROM itens WHERE categoria IS NULL OR categoria = ''")->fetchColumn();
} catch (PDOException $e) {
    // Se houver erro, define valores padrão
    $total_categorias = 0;
    $total_setores = 0;
    $total_itens = 0;
    $total_computadores = 0;
    $itens_sem_categoria = 0;
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}

// Início do conteúdo
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Configurações do Sistema</h2>
</div>

<?php if (isset($_SESSION['erro']) && $_SESSION['erro']): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['erro']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['erro']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['sucesso']) && $_SESSION['sucesso']): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['sucesso']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<div class="row">
    <!-- Painel de Estatísticas -->
    <div class="col-md-12 mb-4">
        <div class="card border">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i> Estatísticas do Sistema
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card border border-2 text-center h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <h1 class="display-6 text mb-2"><?php echo $total_categorias; ?></h1>
                                <p class="mb-0 text-muted">Categorias de Itens</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-2 text-center h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <h1 class="display-6 text mb-2"><?php echo $total_setores; ?></h1>
                                <p class="mb-0 text-muted">Setores Cadastrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-2 text-center h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <h1 class="display-6 text mb-2"><?php echo $total_itens; ?></h1>
                                <p class="mb-0 text-muted">Itens Cadastrados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border border-2 text-center h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <h1 class="display-6 text mb-2"><?php echo $total_computadores; ?></h1>
                                <p class="mb-0 text-muted">Computadores</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Ação -->
    <div class="col-md-12 mb-4">
        <div class="card border">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-gear me-2"></i> Gerenciamento do Sistema
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <a href="gerenciamento_categorias.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 card-hover">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-tags" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Categorias</h5>
                                    <p class="card-text text-muted mb-0">Gerencie as categorias dos itens</p>
                                    <div class="mt-3">
                                        <span class="badge bg-primary rounded-pill"><?php echo $total_categorias; ?> ativas</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-4 mb-4">
                        <a href="gerenciamento_setores.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 card-hover">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-building" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Setores</h5>
                                    <p class="card-text text-muted mb-0">Administre os setores da empresa</p>
                                    <div class="mt-3">
                                        <span class="badge bg-primary rounded-pill"><?php echo $total_setores; ?> cadastrados</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-4 mb-4">
                        <a href="gerenciamento_sistema.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm h-100 card-hover">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-gear" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h5 class="card-title mb-2">Sistema</h5>
                                    <p class="card-text text-muted mb-0">Configure parâmetros do sistema</p>
                                    <div class="mt-3">
                                        <span class="badge bg-primary rounded-pill">Configurações</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-hover {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
}
</style>

<?php
$conteudo = ob_get_clean();
$titulo = "Gerenciamento - Sistema Almoxarifado";
include '../includes/template.php';
?>