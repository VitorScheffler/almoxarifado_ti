<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha']    ?? '';

    if ($usuario === '' || $senha === '') {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        $dominio       = "coopershoes.com.br";
        $servidor_ldap = "ldap://192.168.0.6";
        $porta_ldap    = 389;
        $usuario_dn    = "$usuario@$dominio";

        try {
            $conexao = ldap_connect("ldap://192.168.0.6:389");
            if (!$conexao) {
                throw new Exception("Não foi possível conectar ao servidor LDAP.");
            }

            ldap_set_option($conexao, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conexao, LDAP_OPT_REFERRALS,        0);
            ldap_set_option($conexao, LDAP_OPT_NETWORK_TIMEOUT, 10);

            $bind = @ldap_bind($conexao, $usuario_dn, $senha);

            if (!$bind) {
                $error_code = ldap_errno($conexao);
                $mensagem = ($error_code == 0x31)
                    ? "Usuário ou senha inválidos."
                    : "Erro na autenticação. Código: $error_code";
            } else {
                $base_dn         = "DC=coopershoes,DC=com,DC=br";
                $grupo_permitido = "CN=Estoque,OU=Grupos,OU=Matriz,OU=RS,OU=Internos,"
                                 . "OU=Coopershoes,OU=Grupo Coopershoes,"
                                 . "DC=coopershoes,DC=com,DC=br";

                $filtro = "(sAMAccountName=$usuario)";
                $busca  = ldap_search($conexao, $base_dn, $filtro, ['memberOf']);
                $dados  = ldap_get_entries($conexao, $busca);

                $tem_acesso = false;

                if (!empty($dados[0]['memberof'])) {
                    foreach ($dados[0]['memberof'] as $dn) {
                        if (!is_string($dn)) continue;
                        if (strcasecmp($dn, $grupo_permitido) === 0) {
                            $tem_acesso = true;
                            break;
                        }
                    }
                }

                if ($tem_acesso) {
                    $_SESSION['usuario']  = $usuario;
                    $_SESSION['dominio']  = $dominio;
                    session_regenerate_id(true);
                    header("Location: ../index.php");
                    exit;
                } else {
                    $mensagem = "Acesso negado: você não pertence ao grupo autorizado.";
                }
            }

            ldap_unbind($conexao);
        } catch (Exception $e) {
            $mensagem = "Erro no sistema. Por favor, tente novamente mais tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almoxarifado TI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="shortcut icon" href="../assets/img/Coopershoes.png" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-lg border-0" style="width: 100%; max-width: 400px;">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">
                    <i class="bi bi-box-seam"></i> Almoxarifado TI
                </h4>
            </div>
            <div class="card-body p-4">
                <h5 class="card-title text-center mb-4">Acesso ao Sistema</h5>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($mensagem) ?>
                        <button type="button" 
                                class="btn-close" 
                                data-bs-dismiss="alert" 
                                aria-label="Close">
                        </button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuário</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <input type="text" 
                                   id="usuario" 
                                   name="usuario" 
                                   class="form-control" 
                                   required
                                   placeholder="Seu usuário do domínio"
                                   autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" 
                                   id="senha" 
                                   name="senha" 
                                   class="form-control" 
                                   required
                                   placeholder="Sua senha"
                                   autocomplete="current-password">
                            <button type="button" 
                                    id="toggleSenha" 
                                    class="btn btn-outline-secondary"
                                    onclick="togglePasswordVisibility()">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Acesso restrito ao grupo autorizado
                        </small>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> 
                    Sistema de almoxarifado - Coopershoes
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('usuario').focus();
        });

        function togglePasswordVisibility() {
            const senhaInput = document.getElementById('senha');
            const toggleButton = document.getElementById('toggleSenha');
            const icon = toggleButton.querySelector('i');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                toggleButton.title = 'Ocultar senha';
            } else {
                senhaInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                toggleButton.title = 'Mostrar senha';
            }
        }
    </script>
</body>
</html>