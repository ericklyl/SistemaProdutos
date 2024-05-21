<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

/*
O seguinte código retorna para o cliente os detalhes do produto, com base no id.
Códigos de erro:
0 : falha de autenticação
1 : usuário já existe
2 : falha banco de dados
3 : faltam parâmetros
4 : entrada não encontrada no BD
*/

// Conexão com o banco de dados
require_once 'conexao_db.php';
// Autenticação
require_once 'autenticacao.php';

// Função para retornar a resposta em JSON
function jsonResponse($success, $data = null, $error = null, $errorCode = null) {
    $response = ['sucesso' => $success];
    if ($success && $data) {
        $response['dados'] = $data;
    } else {
        $response['erro'] = $error;
        $response['cod_erro'] = $errorCode;
    }
    echo json_encode($response);
    exit;
}

// Verifica se o usuário conseguiu autenticar
if (!autenticar($db_con)) {
    jsonResponse(0, null, 'Usuário ou senha não confere', 0);
}

// Verifica se o parâmetro 'id' foi enviado
if (!isset($_GET['id'])) {
    jsonResponse(0, null, 'Faltam parâmetros', 3);
}

$id = intval($_GET['id']);

// Verifica se a conexão com o banco de dados está ativa
if ($db_con) {
    // Realiza uma consulta ao BD e obtem o produto
    $consulta = $db_con->prepare("SELECT * FROM produtos WHERE id = :id");
    if ($consulta) {
        $consulta->bindParam(':id', $id, PDO::PARAM_INT);
        if ($consulta->execute()) {
            if ($consulta->rowCount() > 0) {
                $produto = $consulta->fetch(PDO::FETCH_ASSOC);
                jsonResponse(1, $produto);
            } else {
                jsonResponse(0, null, 'Entrada não encontrada no BD', 4);
            }
        } else {
            jsonResponse(0, null, 'Erro na execução da consulta', 2);
        }
    } else {
        jsonResponse(0, null, 'Erro ao preparar a consulta', 2);
    }
} else {
    jsonResponse(0, null, 'Falha na conexão com o banco de dados', 2);
}

// Fecha a conexão com o banco de dados
$db_con = null;
?>
