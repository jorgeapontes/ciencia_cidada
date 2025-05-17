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

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $atropelamento_id = $_GET['id'];

    try {
        // Inicia transação para garantir a integridade dos dados
        $conn->begin_transaction();

        // 1. Excluir comentários relacionados
        $stmt_comentarios = $conn->prepare("DELETE FROM comentarios_atropelamentos WHERE atropelamento_id = ?");
        $stmt_comentarios->bind_param("i", $atropelamento_id);
        $stmt_comentarios->execute();

        // 2. Excluir interações (likes/dislikes) relacionadas
        $stmt_interacoes = $conn->prepare("DELETE FROM interacoes_atropelamentos WHERE atropelamento_id = ?");
        $stmt_interacoes->bind_param("i", $atropelamento_id);
        $stmt_interacoes->execute();

        // 3. Buscar o caminho da foto para excluir do servidor
        $stmt_select = $conn->prepare("SELECT caminho_foto FROM atropelamentos WHERE id = ?");
        $stmt_select->bind_param("i", $atropelamento_id);
        $stmt_select->execute();
        $resultado = $stmt_select->get_result();

        if ($atropelamento = $resultado->fetch_assoc()) {
            if (!empty($atropelamento['caminho_foto']) && file_exists($atropelamento['caminho_foto'])) {
                unlink($atropelamento['caminho_foto']);
            }
        }

        // 4. Excluir o registro de atropelamento
        $stmt_delete = $conn->prepare("DELETE FROM atropelamentos WHERE id = ?");
        $stmt_delete->bind_param("i", $atropelamento_id);
        $stmt_delete->execute();

        // Confirma a transação
        $conn->commit();

        $_SESSION['mensagem'] = "Caso de atropelamento excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
        header("Location: feed_atropelamentos.php");
        exit;

    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir caso de atropelamento: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
        header("Location: feed_atropelamentos.php");
        exit;
    }

    $stmt_comentarios->close();
    $stmt_interacoes->close();
    $stmt_select->close();
    $stmt_delete->close();
    $conn->close();

} else {
    $_SESSION['mensagem'] = "ID de atropelamento inválido.";
    $_SESSION['tipo_mensagem'] = "warning";
    header("Location: feed_atropelamentos.php");
    exit;
}
?>