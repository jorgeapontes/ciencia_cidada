<?php
session_start();
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

include 'conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['tipo'])) {
    $id_para_excluir = $_GET['id'];
    $tipo_post = $_GET['tipo'];

    try {
        if ($tipo_post === 'publicacao') {
            // Deletar publicação da tabela 'publicacoes'
            $stmt_check = $conn->prepare("SELECT usuario_id, caminho_foto FROM publicacoes WHERE id = ?");
            $stmt_check->bind_param("i", $id_para_excluir);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 1) {
                $publicacao = $result_check->fetch_assoc();
                if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $publicacao['usuario_id']) {
                    $conn->begin_transaction();

                    // Excluir comentários relacionados
                    $stmt_delete_comentarios = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
                    $stmt_delete_comentarios->bind_param("i", $id_para_excluir);
                    $stmt_delete_comentarios->execute();

                    // Excluir interações relacionadas
                    $stmt_delete_interacoes = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
                    $stmt_delete_interacoes->bind_param("i", $id_para_excluir);
                    $stmt_delete_interacoes->execute();

                    // Excluir a imagem do servidor
                    if (!empty($publicacao['caminho_foto']) && file_exists($publicacao['caminho_foto'])) {
                        unlink($publicacao['caminho_foto']);
                    }

                    // Excluir a publicação
                    $stmt_delete_publicacao = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
                    $stmt_delete_publicacao->bind_param("i", $id_para_excluir);
                    $stmt_delete_publicacao->execute();

                    $conn->commit();
                    header("Location: painel_usuario.php");
                    exit;
                } else {
                    echo "Você não tem permissão para excluir esta publicação.";
                    exit;
                }
            } else {
                echo "Publicação não encontrada.";
                exit;
            }
        } elseif ($tipo_post === 'atropelamento') {
            // Deletar caso de atropelamento da tabela 'atropelamentos'
            $stmt_check_atropelamento = $conn->prepare("SELECT usuario_id, caminho_foto FROM atropelamentos WHERE id = ?");
            $stmt_check_atropelamento->bind_param("i", $id_para_excluir);
            $stmt_check_atropelamento->execute();
            $result_check_atropelamento = $stmt_check_atropelamento->get_result();

            if ($result_check_atropelamento->num_rows === 1) {
                $atropelamento = $result_check_atropelamento->fetch_assoc();
                if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $atropelamento['usuario_id']) {
                    // Excluir a imagem do servidor (se houver)
                    if (!empty($atropelamento['caminho_foto']) && file_exists($atropelamento['caminho_foto'])) {
                        unlink($atropelamento['caminho_foto']);
                    }

                    $stmt_delete_atropelamento = $conn->prepare("DELETE FROM atropelamentos WHERE id = ?");
                    $stmt_delete_atropelamento->bind_param("i", $id_para_excluir);
                    $stmt_delete_atropelamento->execute();

                    header("Location: painel_usuario.php");
                    exit;
                } else {
                    echo "Você não tem permissão para excluir este caso de atropelamento.";
                    exit;
                }
            } else {
                echo "Caso de atropelamento não encontrado.";
                exit;
            }
        } else {
            echo "Tipo de post para exclusão inválido.";
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        die("Erro ao excluir: " . $e->getMessage());
    }
} elseif (isset($_GET['comentario_id'])) {
    // Lógica para deletar comentários (como estava antes)
    $comentario_id = $_GET['comentario_id'];

    $stmt = $conn->prepare("SELECT usuario_id FROM comentarios WHERE id = ?");
    $stmt->bind_param("i", $comentario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $comentario = $result->fetch_assoc();

        // Só pode deletar se for admin ou dono do comentário
        if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $comentario['usuario_id']) {
            $stmt = $conn->prepare("DELETE FROM comentarios WHERE id = ?");
            $stmt->bind_param("i", $comentario_id);
            $stmt->execute();
        }
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

header("Location: feed_user.php");
exit;
?>