<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

/*
 * Código para atualizar informações dos produtos
 * 
 * Códigos de erro:
 * 0 : falha de autenticação
 * 1 : usuário já existe
 * 2 : falha banco de dados
 * 3 : faltam parâmetros
 * 4 : entrada não encontrada no BD
 * 5 : usuário autenticado não criou o produto
 */

// Conexão com o banco de dados
require_once('conexao_db.php');

// Autenticação
require_once('autenticacao.php');

// Função para retornar a resposta em JSON
function enviarRespostaJson($sucesso, $dados = null, $erro = null, $codigoErro = null) {
    $resposta = ['sucesso' => $sucesso];
    if ($sucesso) {
        if (is_array($dados)) {
            $resposta = array_merge($resposta, $dados);
        }
    } else {
        $resposta['erro'] = $erro;
        $resposta['codigo_erro'] = $codigoErro;
    }
    echo json_encode($resposta);
    exit;
}

// Verifica se o usuário conseguiu autenticar
if (!autenticar($db_con)) {
    enviarRespostaJson(0, null, 'usuário ou senha não conferem', 0);
}

// Verifica se todos os parâmetros foram enviados pelo cliente
if (!isset($_POST['id'], $_POST['novo_nome'], $_POST['novo_preco'], $_POST['nova_descricao'], $_FILES['nova_img'])) {
    enviarRespostaJson(0, null, 'faltam parâmetros', 3);
}

$id = intval($_POST['id']);
$novoNome = trim($_POST['novo_nome']);
$novoPreco = floatval($_POST['novo_preco']);
$novaDescricao = trim($_POST['nova_descricao']);

// Consulta para verificar se o produto existe
$consulta = $db_con->prepare("SELECT * FROM produtos WHERE id = :id");

if ($consulta) {
    $consulta->bindParam(':id', $id, PDO::PARAM_INT);
    if ($consulta->execute()) {
        if ($consulta->rowCount() === 0) {
            enviarRespostaJson(0, null, 'produto não encontrado', 4);
        } else {
            $produto = $consulta->fetch(PDO::FETCH_ASSOC);
            $criadoPor = $produto['usuarios_login'];

            if ($criadoPor !== $login) {
                enviarRespostaJson(0, null, 'você não é o criador do produto', 5);
            } else {
                // Conexão com o servidor Imgur para subir a nova imagem
                $nomeArquivo = $_FILES['nova_img']['tmp_name'];
                $clientID = "************";
                $handle = fopen($nomeArquivo, "r");
                $dados = fread($handle, filesize($nomeArquivo));
                fclose($handle);
                $parametros = ['image' => base64_encode($dados)];
                $timeout = 30;
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
                curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Client-ID ' . $clientID]);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $parametros);
                $out = curl_exec($curl);
                curl_close($curl);
                $resultadoImgur = json_decode($out, true);
                
                if (isset($resultadoImgur['data']['link'])) {
                    $urlImagem = $resultadoImgur['data']['link'];
                    
                    // Consulta para atualizar o banco de dados com os novos dados
                    $atualiza = $db_con->prepare("UPDATE produtos SET nome = :nome, preco = :preco, descricao = :descricao, img = :img WHERE id = :id");
                    $atualiza->bindParam(':nome', $novoNome, PDO::PARAM_STR);
                    $atualiza->bindParam(':preco', $novoPreco, PDO::PARAM_STR);
                    $atualiza->bindParam(':descricao', $novaDescricao, PDO::PARAM_STR);
                    $atualiza->bindParam(':img', $urlImagem, PDO::PARAM_STR);
                    $atualiza->bindParam(':id', $id, PDO::PARAM_INT);
                    
                    if ($atualiza->execute()) {
                        enviarRespostaJson(1);
                    } else {
                        enviarRespostaJson(0, null, 'Erro ao atualizar produto no BD', 2);
                    }
                } else {
                    enviarRespostaJson(0, null, 'Erro ao fazer upload da imagem', 2);
                }
            }
        }
    } else {
        enviarRespostaJson(0, null, 'Erro ao executar a consulta', 2);
    }
} else {
    enviarRespostaJson(0, null, 'Erro ao preparar a consulta', 2);
}

// Fecha a conexão com o banco de dados
$db_con = null;

?>
