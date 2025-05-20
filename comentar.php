<?php
session_start();
include 'conexao.php'; 

if (!isset($_SESSION['usuario_id'])) {

    $_SESSION['mensagem'] = "Você precisa estar logado para comentar.";
    $_SESSION['tipo_mensagem'] = "danger";
    header('Location: login.php');
    exit();
}


$cargo_usuario = $_SESSION['cargo'] ?? 'user';
if (!($cargo_usuario === 'especialista' || $cargo_usuario === 'admin')) {
    $_SESSION['mensagem'] = "Seu cargo não permite adicionar comentários.";
    $_SESSION['tipo_mensagem'] = "warning";

    $origem = $_SERVER['HTTP_REFERER'] ?? 'feed_user.php';
    header('Location: ' . $origem);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $publicacao_id = $_POST['publicacao_id'] ?? null;
    $comentario_texto = $_POST['comentario'] ?? null;
    $usuario_id = $_SESSION['usuario_id'];

    if ($publicacao_id && $comentario_texto) {
        $stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $publicacao_id, $usuario_id, $comentario_texto);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Comentário adicionado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar comentário: " . $conn->error;
            $_SESSION['tipo_mensagem'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "Dados inválidos para o comentário.";
        $_SESSION['tipo_mensagem'] = "danger";
    }

    $conn->close();

    $origem = $_SERVER['HTTP_REFERER'] ?? 'feed_user.php';
    header('Location: ' . $origem);
    exit();
} else {
 
    header('Location: feed_user.php'); 
    exit();
}
?>