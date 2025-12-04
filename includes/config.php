<?php
session_start();

// Caminho do arquivo de configurações
$config_file = __DIR__ . '/../config_sistema.json';

// Se o arquivo de configuração existir, carrega dele
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    
    define('DB_HOST', $config['db_host'] ?? 'localhost');
    define('DB_NAME', $config['db_name'] ?? 'estoque_ti');
    define('DB_USER', $config['db_user'] ?? 'root');
    define('DB_PASS', $config['db_pass'] ?? 'Cooper123@');
    
    // Configurações LDAP (usadas no login)
    define('LDAP_SERVER', $config['ldap_server'] ?? 'ldap://192.168.0.6');
    define('LDAP_PORT', $config['ldap_port'] ?? 389);
    define('LDAP_DOMAIN', $config['ldap_domain'] ?? 'coopershoes.com.br');
    define('LDAP_BASE_DN', $config['ldap_base_dn'] ?? 'DC=coopershoes,DC=com,DC=br');
    define('LDAP_GRUPO', $config['ldap_grupo'] ?? 'CN=Estoque,OU=Grupos,OU=Matriz,OU=RS,OU=Internos,OU=Coopershoes,OU=Grupo Coopershoes,DC=coopershoes,DC=com,DC=br');
    
    // Configurações gerais
    define('EMPRESA_NOME', $config['empresa_nome'] ?? 'Coopershoes');
    define('SISTEMA_NOME', $config['sistema_nome'] ?? 'Almoxarifado TI');
    define('EMPRESA_LOGO', $config['empresa_logo'] ?? '../assets/img/Coopershoes.png');
    define('TIMEZONE', $config['timezone'] ?? 'America/Sao_Paulo');
} else {
    // Valores padrão
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'estoque_ti');
    define('DB_USER', 'root');
    define('DB_PASS', 'Cooper123@');
    define('LDAP_SERVER', 'ldap://192.168.0.6');
    define('LDAP_PORT', 389);
    define('LDAP_DOMAIN', 'coopershoes.com.br');
    define('LDAP_BASE_DN', 'DC=coopershoes,DC=com,DC=br');
    define('LDAP_GRUPO', 'CN=Estoque,OU=Grupos,OU=Matriz,OU=RS,OU=Internos,OU=Coopershoes,OU=Grupo Coopershoes,DC=coopershoes,DC=com,DC=br');
    define('EMPRESA_NOME', 'Coopershoes');
    define('SISTEMA_NOME', 'Almoxarifado TI');
    define('EMPRESA_LOGO', '../assets/img/Coopershoes.png');
    define('TIMEZONE', 'America/Sao_Paulo');
}

// Define timezone
date_default_timezone_set(TIMEZONE);

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
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Redireciona para login se não estiver autenticado
if (!isset($_SESSION['usuario']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: ../auth/login.php");
    exit();
}
?>