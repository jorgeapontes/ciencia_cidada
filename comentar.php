<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // Redirecionar para a página de login se não estiver logado
    exit;
}

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicacao_id']) && isset($_POST['comentario'])) {
    $publicacao_id = $_POST['publicacao_id'];
    $comentario = trim($_POST['comentario']);
    $usuario_id = $_SESSION['usuario_id'];

    if (!empty($comentario)) {
        try {
            $stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $publicacao_id, $usuario_id, $comentario);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['mensagem'] = "Comentário adicionado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";
            } else {
                $_SESSION['mensagem'] = "Erro ao adicionar o comentário.";
                $_SESSION['tipo_mensagem'] = "danger";
            }
        } catch (Exception $e) {
            $_SESSION['mensagem'] = "Erro ao adicionar o comentário: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    } else {
        $_SESSION['mensagem'] = "O comentário não pode estar vazio.";
        $_SESSION['tipo_mensagem'] = "warning";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']); // Redirecionar de volta para a página anterior
    exit;
} else {
    // Se a requisição não for POST ou os campos necessários não estiverem definidos
    header("Location: feed_user.php"); // Redirecionar para o feed por segurança
    exit;
}
?>