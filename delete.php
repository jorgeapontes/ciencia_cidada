<?php
session_start();
include 'conexao.php'; 

if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Acesso Negado</title>
        <style>
            body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
            h1 { color: #d9534f; margin-bottom: 20px; }
            p { margin-bottom: 15px; }
            .login-link { color: #007bff; text-decoration: none; font-weight: bold; }
            .login-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Acesso Negado</h1>
            <p>Você precisa estar logado para acessar esta página.</p>
            <p><a href='login.php' class='login-link'>Fazer Login</a></p>
        </div>
    </body>
    </html>
    HTML;

    echo $html;
    exit;
}

$id_para_excluir = $_GET['id'] ?? null; 
$comentario_id = $_GET['comentario_id'] ?? null; 
$tipo = $_GET['tipo'] ?? null; 

$usuario_logado_id = $_SESSION['usuario_id'];
$cargo_usuario_logado = $_SESSION['cargo'];

$redirecionar_para = 'feed_user.php'; 

try {
    if ($tipo) {
        switch ($tipo) {
            case 'publicacao':
                if ($id_para_excluir) {
                    $stmt_check = $conn->prepare("SELECT usuario_id, caminho_foto FROM publicacoes WHERE id = ?");
                    $stmt_check->bind_param("i", $id_para_excluir);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    if ($result_check->num_rows === 1) {
                        $publicacao = $result_check->fetch_assoc();
                        if ($cargo_usuario_logado === 'admin' || $usuario_logado_id === $publicacao['usuario_id']) {
                            $conn->begin_transaction();

                            $stmt_delete_comentarios = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ? AND tipo_publicacao = 'publicacao'");
                            $stmt_delete_comentarios->bind_param("i", $id_para_excluir);
                            $stmt_delete_comentarios->execute();
                            $stmt_delete_comentarios->close();

                            $stmt_delete_interacoes = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
                            $stmt_delete_interacoes->bind_param("i", $id_para_excluir);
                            $stmt_delete_interacoes->execute();
                            $stmt_delete_interacoes->close();

                          
                            $caminho_foto_completo = !empty($publicacao['caminho_foto']) ? $publicacao['caminho_foto'] : null;
                            if ($caminho_foto_completo && file_exists($caminho_foto_completo)) {
                                if (!unlink($caminho_foto_completo)) {
                                    throw new Exception("Não foi possível excluir o arquivo de imagem: " . $caminho_foto_completo);
                                }
                            }

                            // Excluir a publicação
                            $stmt_delete_publicacao = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
                            $stmt_delete_publicacao->bind_param("i", $id_para_excluir);
                            if ($stmt_delete_publicacao->execute()) {
                                $conn->commit();
                                $_SESSION['mensagem'] = "Publicação excluída com sucesso!";
                                $_SESSION['tipo_mensagem'] = "success";
                                $redirecionar_para = 'feed_user.php'; 
                            } else {
                                throw new Exception("Erro ao excluir publicação: " . $conn->error);
                            }
                            $stmt_delete_publicacao->close();
                        } else {
                            $_SESSION['mensagem'] = "Você não tem permissão para excluir esta publicação.";
                            $_SESSION['tipo_mensagem'] = "danger";
                            $redirecionar_para = 'feed_user.php';
                        }
                    } else {
                        $_SESSION['mensagem'] = "Publicação não encontrada.";
                        $_SESSION['tipo_mensagem'] = "danger";
                        $redirecionar_para = 'feed_user.php'; 
                    }
                    $stmt_check->close();
                } else {
                    $_SESSION['mensagem'] = "ID da publicação não fornecido para exclusão.";
                    $_SESSION['tipo_mensagem'] = "danger";
                    $redirecionar_para = 'feed_user.php';
                }
                break;

            case 'atropelamento':
                if ($id_para_excluir) {
                    $stmt_check_atropelamento = $conn->prepare("SELECT usuario_id, caminho_foto FROM atropelamentos WHERE id = ?");
                    $stmt_check_atropelamento->bind_param("i", $id_para_excluir);
                    $stmt_check_atropelamento->execute();
                    $result_check_atropelamento = $stmt_check_atropelamento->get_result();

                    if ($result_check_atropelamento->num_rows === 1) {
                        $atropelamento = $result_check_atropelamento->fetch_assoc();
                        if ($cargo_usuario_logado === 'admin' || $usuario_logado_id === $atropelamento['usuario_id']) {
                            $conn->begin_transaction();

                            $stmt_delete_comentarios_atropelamento = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ? AND tipo_publicacao = 'atropelamento'");
                            $stmt_delete_comentarios_atropelamento->bind_param("i", $id_para_excluir);
                            $stmt_delete_comentarios_atropelamento->execute();
                            $stmt_delete_comentarios_atropelamento->close();

                            $stmt_delete_interacoes_atropelamento = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
                            $stmt_delete_interacoes_atropelamento->bind_param("i", $id_para_excluir);
                            $stmt_delete_interacoes_atropelamento->execute();
                            $stmt_delete_interacoes_atropelamento->close();

                            $caminho_foto_completo_atrop = !empty($atropelamento['caminho_foto']) ? $atropelamento['caminho_foto'] : null;
                            if ($caminho_foto_completo_atrop && file_exists($caminho_foto_completo_atrop)) {
                                if (!unlink($caminho_foto_completo_atrop)) {
                                    throw new Exception("Não foi possível excluir o arquivo de imagem do atropelamento: " . $caminho_foto_completo_atrop);
                                }
                            }

                            // Excluir o atropelamento
                            $stmt_delete_atropelamento = $conn->prepare("DELETE FROM atropelamentos WHERE id = ?");
                            $stmt_delete_atropelamento->bind_param("i", $id_para_excluir);
                            if ($stmt_delete_atropelamento->execute()) {
                                $conn->commit();
                                $_SESSION['mensagem'] = "Registro de atropelamento excluído com sucesso!";
                                $_SESSION['tipo_mensagem'] = "success";
                                $redirecionar_para = 'feed_atropelamentos.php'; 
                            } else {
                                throw new Exception("Erro ao excluir registro de atropelamento: " . $conn->error);
                            }
                            $stmt_delete_atropelamento->close();
                        } else {
                            $_SESSION['mensagem'] = "Você não tem permissão para excluir este caso de atropelamento.";
                            $_SESSION['tipo_mensagem'] = "danger";
                            $redirecionar_para = 'feed_atropelamentos.php'; 
                        }
                    } else {
                        $_SESSION['mensagem'] = "Caso de atropelamento não encontrado.";
                        $_SESSION['tipo_mensagem'] = "danger";
                        $redirecionar_para = 'feed_atropelamentos.php'; 
                    }
                    $stmt_check_atropelamento->close();
                } else {
                    $_SESSION['mensagem'] = "ID do caso de atropelamento não fornecido para exclusão.";
                    $_SESSION['tipo_mensagem'] = "danger";
                    $redirecionar_para = 'feed_atropelamentos.php'; 
                }
                break;

            case 'publicacao_comentario':
            case 'atropelamento_comentario':
                if ($comentario_id) {
                    // Determine o tipo de publicação do comentário para a exclusão certa
                    $tipo_comentario_db = ($tipo === 'publicacao_comentario') ? 'publicacao' : 'atropelamento';

                    $stmt_check_comentario = $conn->prepare("SELECT usuario_id, publicacao_id FROM comentarios WHERE id = ? AND tipo_publicacao = ?");
                    $stmt_check_comentario->bind_param("is", $comentario_id, $tipo_comentario_db);
                    $stmt_check_comentario->execute();
                    $resultado_comentario = $stmt_check_comentario->get_result();
                    $comentario = $resultado_comentario->fetch_assoc();
                    $stmt_check_comentario->close();

                    if ($comentario && ($comentario['usuario_id'] == $usuario_logado_id || $cargo_usuario_logado === 'admin')) {
                        $stmt_delete_comentario = $conn->prepare("DELETE FROM comentarios WHERE id = ? AND tipo_publicacao = ?");
                        $stmt_delete_comentario->bind_param("is", $comentario_id, $tipo_comentario_db);
                        if ($stmt_delete_comentario->execute()) {
                            $_SESSION['mensagem'] = "Comentário excluído com sucesso!";
                            $_SESSION['tipo_mensagem'] = "success";
                        } else {
                            throw new Exception("Erro ao excluir comentário: " . $conn->error);
                        }
                        $stmt_delete_comentario->close();

                      
                        if ($tipo_comentario_db === 'publicacao') {
                            $redirecionar_para = 'feed_user.php';
                        } else { 
                            $redirecionar_para = 'feed_atropelamentos.php';
                        }
                    } else {
                        $_SESSION['mensagem'] = "Você não tem permissão para excluir este comentário.";
                        $_SESSION['tipo_mensagem'] = "danger";
                        $redirecionar_para = ($tipo_comentario_db === 'publicacao') ? 'feed_user.php' : 'feed_atropelamentos.php';
                    }
                } else {
                    $_SESSION['mensagem'] = "ID do comentário não fornecido para exclusão.";
                    $_SESSION['tipo_mensagem'] = "danger";
                    
                    if (isset($_SERVER['HTTP_REFERER'])) {
                        $redirecionar_para = $_SERVER['HTTP_REFERER'];
                    } else {
                        $redirecionar_para = 'feed_user.php';
                    }
                }
                break;

            default:
                $_SESSION['mensagem'] = "Tipo de exclusão inválido.";
                $_SESSION['tipo_mensagem'] = "danger";
                $redirecionar_para = 'feed_user.php'; 
                break;
        }
    } else {
        $_SESSION['mensagem'] = "Ação de exclusão não especificada.";
        $_SESSION['tipo_mensagem'] = "danger";
        $redirecionar_para = 'feed_user.php'; 
    }
} catch (Exception $e) {
    if ($conn->in_transaction) { 
        $conn->rollback();
    }
    $_SESSION['mensagem'] = "Ocorreu um erro: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
    
    if ($tipo === 'publicacao' || $tipo === 'publicacao_comentario') {
        $redirecionar_para = 'feed_user.php';
    } elseif ($tipo === 'atropelamento' || $tipo === 'atropelamento_comentario') {
        $redirecionar_para = 'feed_atropelamentos.php';
    } else {
        $redirecionar_para = 'feed_user.php'; 
    }
}

header('Location: ' . $redirecionar_para);
exit();
?>