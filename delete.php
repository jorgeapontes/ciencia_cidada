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

// Verificar se é para deletar publicação ou comentário
if (isset($_GET['id'])) {
    // Deletar publicação
    $pubId = $_GET['id'];
    
    try {
        // Verificar se o usuário tem permissão
        $stmt = $conn->prepare("SELECT usuario_id FROM publicacoes WHERE id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            header("Location: feed_user.php");
            exit;
        }
        
        $publicacao = $result->fetch_assoc();
        
        // Só pode deletar se for admin ou dono da publicação
        if ($_SESSION['cargo'] !== 'admin' && $_SESSION['usuario_id'] !== $publicacao['usuario_id']) {
            header("Location: feed_user.php");
            exit;
        }
        
        $conn->begin_transaction();
        
        // 1. Excluir comentários relacionados
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        
        // 2. Excluir interações relacionadas
        $stmt = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        
        // 3. Excluir a imagem do servidor
        $busca = $conn->prepare("SELECT foto FROM publicacoes WHERE id = ?");
        $busca->bind_param("i", $pubId);
        $busca->execute();
        $resultado = $busca->get_result();
        
        if ($foto = $resultado->fetch_assoc()) {
            if (file_exists($foto['foto'])) {
                unlink($foto['foto']);
            }
        }
        
        // 4. Excluir a publicação
        $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        
        $conn->commit();
        header("Location: feed_user.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        die("Erro ao excluir publicação: " . $e->getMessage());
    }
    
} elseif (isset($_GET['comentario_id'])) {
    // Deletar comentário
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