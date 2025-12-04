<?php
session_start();
require 'config.php';

if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$acao = $_POST['acao'] ?? '';

switch ($acao) {
    case 'salvar_geral':
        salvarConfiguracoesGeral();
        break;
    case 'salvar_ldap':
        salvarConfiguracoesLDAP();
        break;
    case 'salvar_banco':
        salvarConfiguracoesBanco();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
        exit;
}

function salvarConfiguracoesGeral() {
    global $pdo;
    
    $configuracoes = [
        'empresa_nome' => $_POST['empresa_nome'] ?? '',
        'sistema_nome' => $_POST['sistema_nome'] ?? '',
        'empresa_logo' => $_POST['empresa_logo'] ?? '',
        'timezone' => $_POST['timezone'] ?? ''
    ];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($configuracoes as $chave => $valor) {
            $stmt = $pdo->prepare("
                INSERT INTO config_sistema (chave, valor, descricao, categoria) 
                VALUES (:chave, :valor, :descricao, 'geral')
                ON DUPLICATE KEY UPDATE 
                valor = :valor, 
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $descricao = match($chave) {
                'empresa_nome' => 'Nome da empresa',
                'sistema_nome' => 'Nome do sistema',
                'empresa_logo' => 'Logo da empresa',
                'timezone' => 'Fuso horário',
                default => ''
            };
            
            $stmt->execute([
                ':chave' => $chave,
                ':valor' => $valor,
                ':descricao' => $descricao
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Configurações gerais salvas com sucesso!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}

function salvarConfiguracoesLDAP() {
    global $pdo;
    
    $configuracoes = [
        'ldap_server' => $_POST['ldap_server'] ?? '',
        'ldap_port' => $_POST['ldap_port'] ?? '',
        'ldap_domain' => $_POST['ldap_domain'] ?? '',
        'ldap_base_dn' => $_POST['ldap_base_dn'] ?? '',
        'ldap_grupo' => $_POST['ldap_grupo'] ?? ''
    ];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($configuracoes as $chave => $valor) {
            $stmt = $pdo->prepare("
                INSERT INTO config_sistema (chave, valor, descricao, categoria) 
                VALUES (:chave, :valor, :descricao, 'ldap')
                ON DUPLICATE KEY UPDATE 
                valor = :valor, 
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $descricao = match($chave) {
                'ldap_server' => 'Servidor LDAP',
                'ldap_port' => 'Porta LDAP',
                'ldap_domain' => 'Domínio LDAP',
                'ldap_base_dn' => 'Base DN LDAP',
                'ldap_grupo' => 'Grupo LDAP autorizado',
                default => ''
            };
            
            $stmt->execute([
                ':chave' => $chave,
                ':valor' => $valor,
                ':descricao' => $descricao
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Configurações LDAP salvas com sucesso!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}

function salvarConfiguracoesBanco() {
    $config_file = __DIR__ . '/../config_sistema.json';
    
    $configuracoes = [
        'db_host' => $_POST['db_host'] ?? '',
        'db_name' => $_POST['db_name'] ?? '',
        'db_user' => $_POST['db_user'] ?? '',
        'db_pass' => $_POST['db_pass'] ?? ''
    ];
    
    try {
        // Salva no arquivo JSON
        if (file_put_contents($config_file, json_encode($configuracoes, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => 'Configurações do banco salvas com sucesso!']);
        } else {
            throw new Exception('Não foi possível salvar o arquivo de configuração');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}
?>