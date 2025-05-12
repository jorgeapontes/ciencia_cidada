<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

if (isset($_GET['id'])) {
    $publicacao_id = $_GET['id'];
    
    // Primeiro verifica se a publicação existe e se o usuário tem permissão
    $sql = "SELECT usuario_id FROM publicacoes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $publicacao_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $publicacao = $result->fetch_assoc();
        
        // Verifica se é o dono da publicação ou admin
        if ($_SESSION['usuario_id'] == $publicacao['usuario_id'] || $_SESSION['cargo'] === 'admin') {
            // Deleta a publicação
            $sql_delete = "DELETE FROM publicacoes WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $publicacao_id);
            
            if ($stmt_delete->execute()) {
                $_SESSION['mensagem'] = "Publicação excluída com sucesso!";
            } else {
                $_SESSION['erro'] = "Erro ao excluir publicação.";
            }
        } else {
            $_SESSION['erro'] = "Você não tem permissão para excluir esta publicação.";
        }
    } else {
        $_SESSION['erro'] = "Publicação não encontrada.";
    }
    
    header("Location: feed.php");
    exit;
} else {
    header("Location: feed.php");
    exit;
}
?>