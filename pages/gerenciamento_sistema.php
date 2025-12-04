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

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Configurações do Sistema</h2>
    <a href="gerenciamento.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <!-- Configurações da Empresa -->
        <div class="card border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2"></i>
                    Configurações da Empresa
                </h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="acao" value="salvar_geral">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome da Empresa *</label>
                        <input type="text" 
                               class="form-control" 
                               name="empresa_nome" 
                               value="<?= htmlspecialchars(EMPRESA_NOME) ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Sistema *</label>
                        <input type="text" 
                               class="form-control" 
                               name="sistema_nome" 
                               value="<?= htmlspecialchars(SISTEMA_NOME) ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Caminho do Logo</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="empresa_logo" 
                                   value="<?= htmlspecialchars(EMPRESA_LOGO) ?>"
                                   placeholder="Ex: assets/img/logo.png">
                            <button type="button" class="btn btn-outline-secondary" onclick="previewLogo()">
                                <i class="bi bi-eye me-1"></i> Visualizar
                            </button>
                        </div>
                        <small class="form-text text-muted">Caminho relativo para o arquivo de logo</small>
                        <div id="logoPreview" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Fuso Horário *</label>
                        <select class="form-select" name="timezone" required>
                            <?php
                            $timezones = [
                                'America/Sao_Paulo' => 'São Paulo',
                                'America/Manaus' => 'Manaus',
                                'America/Bahia' => 'Bahia',
                                'America/Fortaleza' => 'Fortaleza',
                                'America/Recife' => 'Recife',
                                'America/Rio_Branco' => 'Rio Branco'
                            ];
                            
                            foreach ($timezones as $tz => $label):
                            ?>
                                <option value="<?= $tz ?>" <?= TIMEZONE == $tz ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Salvar Configurações da Empresa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Configurações do Banco de Dados -->
        <div class="card border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-database me-2"></i>
                    Configurações do Banco de Dados
                </h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="acao" value="salvar_banco">
                    
                    <div class="mb-3">
                        <label class="form-label">Host do Banco *</label>
                        <input type="text" 
                               class="form-control" 
                               name="db_host" 
                               value="<?= htmlspecialchars(DB_HOST) ?>" 
                               required
                               placeholder="localhost">
                        <small class="form-text text-muted">Ex: localhost, 127.0.0.1, mysql.meuservidor.com</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Banco *</label>
                        <input type="text" 
                               class="form-control" 
                               name="db_name" 
                               value="<?= htmlspecialchars(DB_NAME) ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Usuário *</label>
                        <input type="text" 
                               class="form-control" 
                               name="db_user" 
                               value="<?= htmlspecialchars(DB_USER) ?>" 
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   name="db_pass" 
                                   value="<?= htmlspecialchars(DB_PASS) ?>"
                                   id="db_pass">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('db_pass')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Salvar Configurações do Banco
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="testarConexaoBanco()">
                            <i class="bi bi-plug me-1"></i> Testar Conexão
                        </button>
                    </div>
                    <div id="testeConexao" class="mt-3"></div>
                </form>
            </div>
        </div>

        <!-- Configurações LDAP -->
        <div class="card border-0">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i>
                    Configurações LDAP
                </h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="acao" value="salvar_ldap">
                    
                    <div class="mb-3">
                        <label class="form-label">Servidor LDAP *</label>
                        <input type="text" 
                               class="form-control" 
                               name="ldap_server" 
                               value="<?= htmlspecialchars(LDAP_SERVER) ?>" 
                               required
                               placeholder="ldap://192.168.0.6">
                        <small class="form-text text-muted">Ex: ldap://192.168.0.6, ldaps://ldap.empresa.com</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Porta *</label>
                        <input type="number" 
                               class="form-control" 
                               name="ldap_port" 
                               value="<?= htmlspecialchars(LDAP_PORT) ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Domínio *</label>
                        <input type="text" 
                               class="form-control" 
                               name="ldap_domain" 
                               value="<?= htmlspecialchars(LDAP_DOMAIN) ?>" 
                               required
                               placeholder="empresa.com.br">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Base DN *</label>
                        <input type="text" 
                               class="form-control" 
                               name="ldap_base_dn" 
                               value="<?= htmlspecialchars(LDAP_BASE_DN) ?>" 
                               required
                               placeholder="DC=empresa,DC=com,DC=br">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Grupo Autorizado (DN Completo) *</label>
                        <textarea class="form-control" 
                                  name="ldap_grupo" 
                                  rows="3" 
                                  required><?= htmlspecialchars(LDAP_GRUPO) ?></textarea>
                        <small class="form-text text-muted">Distinguished Name completo do grupo que tem acesso</small>
                    </div>
                    
                    <div class="gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Salvar Configurações LDAP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função para salvar configurações via AJAX
function salvarConfiguracoes(formElement) {
    const formData = new FormData(formElement);
    const acao = formData.get('acao');
    const submitBtn = formElement.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Salvando...';
    
    fetch('../includes/salvar_configuracoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'Erro ao salvar: ' + error);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
    
    return false; // Impede o envio tradicional do formulário
}

// Função para mostrar alertas
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Remove alertas anteriores
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Adiciona no topo da página
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Remove automaticamente após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Adicione event listeners aos formulários
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarConfiguracoes(this);
        });
    });
});

// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Toggle password visibility
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

// Test database connection
function testarConexaoBanco() {
    const db_host = document.querySelector('input[name="db_host"]').value;
    const db_name = document.querySelector('input[name="db_name"]').value;
    const db_user = document.querySelector('input[name="db_user"]').value;
    const db_pass = document.querySelector('input[name="db_pass"]').value;
    
    const resultado = document.getElementById('testeConexao');
    resultado.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split me-2"></i> Testando conexão...</div>';
    
    const formData = new FormData();
    formData.append('db_host', db_host);
    formData.append('db_name', db_name);
    formData.append('db_user', db_user);
    formData.append('db_pass', db_pass);
    
    fetch('../includes/testa_conexao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultado.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i> ${data.message}</div>`;
        } else {
            resultado.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        resultado.innerHTML = `<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i> Erro ao testar conexão: ${error}</div>`;
    });
}

// Logo preview
function previewLogo() {
    const logoPath = document.querySelector('input[name="empresa_logo"]').value;
    const previewDiv = document.getElementById('logoPreview');
    
    if (!logoPath) {
        previewDiv.innerHTML = '<div class="alert alert-warning">Digite um caminho para visualizar</div>';
        return;
    }
    
    previewDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="bi bi-image me-2"></i> Visualização do Logo
            <hr>
            <div class="text-center mt-3">
                <img src="${logoPath}" alt="Logo Preview" class="img-thumbnail" style="max-height: 100px;" onerror="this.style.display='none';">
                <div class="mt-2">
                    <small class="text-muted">Caminho: ${logoPath}</small>
                </div>
            </div>
        </div>
    `;
}

// Auto-focus no primeiro campo
document.addEventListener('DOMContentLoaded', function() {
    const firstInput = document.querySelector('.tab-pane.active input[type="text"]');
    if (firstInput) firstInput.focus();
});
</script>

<?php
$conteudo = ob_get_clean();
$titulo = "Configurações do Sistema - Sistema Almoxarifado";
include '../includes/template.php';
?>