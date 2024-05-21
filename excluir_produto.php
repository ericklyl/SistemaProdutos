<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

/*
 * Este script exclui um produto com base no ID fornecido.
 * 
 * Códigos de erro:
 * 0 : Falha na autenticação
 * 1 : Usuário já existe
 * 2 : Erro no banco de dados
 * 3 : Parâmetros ausentes
 * 4 : Produto não encontrado no BD
 * 5 : Usuário autenticado não é o criador do produto
 */

// Inclui arquivos de conexão com o banco de dados e autenticação
require_once 'conexao_db.php';
require_once 'autenticacao.php';

// Função para enviar resposta em JSON
function enviarRespostaJson($sucesso, $dados = null, $erro = null, $codigoErro = null) {
    $resposta = ['sucesso' => $sucesso];
    if ($sucesso && $dados) {
        $resposta = array_merge($resposta, $dados);
    } elseif (!$sucesso) {
        $resposta['erro'] = $erro;
        $resposta['codigo_erro'] = $codigoErro;
    }
    echo json_encode($resposta);
    exit;
}

// Verifica se o usuário está autenticado
if (!autenticar($db_con)) {
    enviarRespostaJson(0, null, 'Usuário ou senha inválidos', 0);
}

// Verifica se o ID foi passado como parâmetro
if (empty($_POST['id'])) {
    enviarRespostaJson(0, null, 'Parâmetros ausentes', 3);
}

$idProduto = intval($_POST['id']);

// Verifica se o usuário autenticado é o criador do produto
$query = $db_con->prepare("SELECT usuarios_login FROM produtos WHERE id = :id");
if ($query) {
    $query->bindParam(':id', $idProduto, PDO::PARAM_INT);
    if ($query->execute()) {
        if ($query->rowCount() > 0) {
            $produto = $query->fetch(PDO::FETCH_ASSOC);
            $usuarioCriador = $produto['usuarios_login'];
            
            if ($usuarioCriador != $login) {
                enviarRespostaJson(0, null, 'Usuário autenticado não é o criador do produto', 5);
            }
        } else {
            enviarRespostaJson(0, null, 'Produto não encontrado no banco de dados', 4);
        }
    } else {
        enviarRespostaJson(0, null, 'Erro ao executar a consulta', 2);
    }
} else {
    enviarRespostaJson(0, null, 'Erro ao preparar a consulta', 2);
}

// Exclui o produto do banco de dados
$query = $db_con->prepare("DELETE FROM produtos WHERE id = :id");
if ($query) {
    $query->bindParam(':id', $idProduto, PDO::PARAM_INT);
    if ($query->execute()) {
        enviarRespostaJson(1, ['mensagem' => 'Produto excluído com sucesso']);
    } else {
        enviarRespostaJson(0, null, 'Erro ao excluir o produto do banco de dados', 2);
    }
} else {
    enviarRespostaJson(0, null, 'Erro ao preparar a exclusão', 2);
}

// Fecha a conexão com o banco de dados
$db_con = null;
?>
