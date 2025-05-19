<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
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

    $conn->begin_transaction();

    $stmt_delete = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ? AND usuario_id = ?");
    $stmt_delete->bind_param("ii", $publicacao_id, $_SESSION['usuario_id']);
    $stmt_delete->execute();

    $stmt_insert = $conn->prepare("INSERT INTO interacoes (publicacao_id, usuario_id, tipo) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("iis", $publicacao_id, $_SESSION['usuario_id'], $tipo);
    $stmt_insert->execute();

    $conn->commit();

    $stmt_likes = $conn->prepare("SELECT COUNT(*) FROM interacoes WHERE publicacao_id = ? AND tipo = 'like'");
    $stmt_likes->bind_param("i", $publicacao_id);
    $stmt_likes->execute();
    $likes = $stmt_likes->get_result()->fetch_row()[0];
    $stmt_likes->close();

    $stmt_dislikes = $conn->prepare("SELECT COUNT(*) FROM interacoes WHERE publicacao_id = ? AND tipo = 'dislike'");
    $stmt_dislikes->bind_param("i", $publicacao_id);
    $stmt_dislikes->execute();
    $dislikes = $stmt_dislikes->get_result()->fetch_row()[0];
    $stmt_dislikes->close();

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