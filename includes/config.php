<?php
session_start();

// Caminho do arquivo de configurações do banco
$config_file = __DIR__ . '/../config/config_sistema.json';

// Se o arquivo de configuração existir, carrega dele
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    
    define('DB_HOST', $config['db_host'] ?? 'localhost');
    define('DB_NAME', $config['db_name'] ?? 'estoque_ti');
    define('DB_USER', $config['db_user'] ?? 'root');
    define('DB_PASS', $config['db_pass'] ?? 'Cooper123@');
} else {
    // Valores padrão
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'estoque_ti');
    define('DB_USER', 'root');
    define('DB_PASS', 'Cooper123@');
}

// Conexão com banco de dados
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Carrega configurações do sistema do banco de dados
    carregarConfiguracoesDoBanco($pdo);
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

/**
 * Carrega configurações do sistema da tabela config_sistema
 */
function carregarConfiguracoesDoBanco($pdo) {
    try {
        $stmt = $pdo->query("SELECT chave, valor FROM config_sistema");
        $configs = $stmt->fetchAll();
        
        foreach ($configs as $config) {
            // Verifica se a constante já não foi definida antes
            $chave_constante = strtoupper($config['chave']);
            if (!defined($chave_constante)) {
                define($chave_constante, $config['valor']);
            }
        }
        
        // Define timezone
        if (defined('TIMEZONE')) {
            date_default_timezone_set(TIMEZONE);
        } else {
            date_default_timezone_set('America/Sao_Paulo');
        }
        
    } catch (Exception $e) {
        // Se não conseguir carregar do banco, usa valores padrão
        if (!defined('LDAP_SERVER')) define('LDAP_SERVER', 'ldap://192.168.0.6');
        if (!defined('LDAP_PORT')) define('LDAP_PORT', 389);
        if (!defined('LDAP_DOMAIN')) define('LDAP_DOMAIN', 'coopershoes.com.br');
        if (!defined('LDAP_BASE_DN')) define('LDAP_BASE_DN', 'DC=coopershoes,DC=com,DC=br');
        if (!defined('LDAP_GRUPO')) define('LDAP_GRUPO', 'CN=Estoque,OU=Grupos,OU=Matriz,OU=RS,OU=Internos,OU=Coopershoes,OU=Grupo Coopershoes,DC=coopershoes,DC=com,DC=br');
        if (!defined('EMPRESA_NOME')) define('EMPRESA_NOME', 'Coopershoes');
        if (!defined('SISTEMA_NOME')) define('SISTEMA_NOME', 'Almoxarifado TI');
        if (!defined('EMPRESA_LOGO')) define('EMPRESA_LOGO', '../assets/img/Coopershoes.png');
        if (!defined('TIMEZONE')) define('TIMEZONE', 'America/Sao_Paulo');
        
        date_default_timezone_set(TIMEZONE);
    }
}

// Redireciona para login se não estiver autenticado
if (!isset($_SESSION['usuario']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: ../auth/login.php");
    exit();
}
?>