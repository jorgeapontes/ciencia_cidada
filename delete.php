<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
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
            header("Location: feed.php");
            exit;
        }
        
        $publicacao = $result->fetch_assoc();
        
        // Só pode deletar se for admin ou dono da publicação
        if ($_SESSION['cargo'] !== 'admin' && $_SESSION['usuario_id'] !== $publicacao['usuario_id']) {
            header("Location: feed.php");
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
        header("Location: feed.php");
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

header("Location: feed.php");
exit;
?>