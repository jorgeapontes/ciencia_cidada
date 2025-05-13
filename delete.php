<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

// Verificar se é admin ou especialista
if (!isset($_SESSION['cargo']) || ($_SESSION['cargo'] !== 'admin' && $_SESSION['cargo'] !== 'especialista')) {
    header("Location: painel_usuario.php");
    exit;
}

// Verificar se é para deletar publicação ou comentário
if (isset($_GET['id'])) {
    // Deletar publicação (apenas admin)
    if ($_SESSION['cargo'] === 'admin') {
        $id = $_GET['id'];
        
        // Primeiro deletar os comentários relacionados
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Depois deletar a publicação
        $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: feed.php");
    exit;
    
} elseif (isset($_GET['comentario_id'])) {
    // Deletar comentário
    $comentario_id = $_GET['comentario_id'];
    
    // Verificar se o usuário é dono do comentário ou admin
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
    
    // Voltar para a página anterior
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Se não for nenhum dos casos acima, redirecionar
header("Location: feed.php");
exit;
?>