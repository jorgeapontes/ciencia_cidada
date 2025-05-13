<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'especialista') {
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

include 'conexao.php';

$publicacao_id = $_POST['publicacao_id'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$publicacao_id || !in_array($tipo, ['like', 'dislike'])) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

try {
    // Remove qualquer interação existente do usuário nesta publicação
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $publicacao_id, $_SESSION['usuario_id']);
    $stmt->execute();
    
    // Adiciona a nova interação
    $stmt = $conn->prepare("INSERT INTO interacoes (publicacao_id, usuario_id, tipo) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $publicacao_id, $_SESSION['usuario_id'], $tipo);
    $stmt->execute();
    
    $conn->commit();
    
    // Obtém as contagens atualizadas
    $likes = $conn->query("SELECT COUNT(*) FROM interacoes WHERE publicacao_id = $publicacao_id AND tipo = 'like'")->fetch_row()[0];
    $dislikes = $conn->query("SELECT COUNT(*) FROM interacoes WHERE publicacao_id = $publicacao_id AND tipo = 'dislike'")->fetch_row()[0];
    
    echo json_encode([
        'success' => true,
        'likes' => $likes,
        'dislikes' => $dislikes
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['erro' => 'Erro ao processar interação']);
}
?>