<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(403); 
    echo json_encode(['erro' => 'Usuário não autenticado.']);
    exit;
}

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atropelamento_id']) && isset($_POST['tipo'])) {
    $atropelamento_id = filter_input(INPUT_POST, 'atropelamento_id', FILTER_VALIDATE_INT);
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario_id = $_SESSION['usuario_id'];

    if ($atropelamento_id !== false && ($tipo === 'like' || $tipo === 'dislike')) {
        // Verifica se o usuário já interagiu com o post
        $stmt_check = $conn->prepare("SELECT id, tipo FROM interacoes_atropelamentos WHERE usuario_id = ? AND atropelamento_id = ?");
        $stmt_check->bind_param("ii", $usuario_id, $atropelamento_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();

        if ($resultado_check->num_rows > 0) {
            $interacao_existente = $resultado_check->fetch_assoc();
            if ($interacao_existente['tipo'] === $tipo) {
                $stmt_delete = $conn->prepare("DELETE FROM interacoes_atropelamentos WHERE id = ?");
                $stmt_delete->bind_param("i", $interacao_existente['id']);
                if (!$stmt_delete->execute()) {
                    http_response_code(500); 
                    echo json_encode(['erro' => 'Erro ao remover interação: ' . $stmt_delete->error]);
                    $stmt_delete->close();
                    $conn->close();
                    exit;
                }
                $stmt_delete->close();
            } else {
                $stmt_update = $conn->prepare("UPDATE interacoes_atropelamentos SET tipo = ? WHERE id = ?");
                $stmt_update->bind_param("si", $tipo, $interacao_existente['id']);
                if (!$stmt_update->execute()) {
                    http_response_code(500); 
                    echo json_encode(['erro' => 'Erro ao atualizar interação: ' . $stmt_update->error]);
                    $stmt_update->close();
                    $conn->close();
                    exit;
                }
                $stmt_update->close();
            }
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO interacoes_atropelamentos (usuario_id, atropelamento_id, tipo, data_interacao) VALUES (?, ?, ?, NOW())");
            $stmt_insert->bind_param("iis", $usuario_id, $atropelamento_id, $tipo);
            if (!$stmt_insert->execute()) {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao inserir interação: ' . $stmt_insert->error]);
                $stmt_insert->close();
                $conn->close();
                exit;
            }
            $stmt_insert->close();
        }

        $stmt_counts = $conn->prepare("
            SELECT
                (SELECT COUNT(*) FROM interacoes_atropelamentos WHERE atropelamento_id = ? AND tipo = 'like') AS likes,
                (SELECT COUNT(*) FROM interacoes_atropelamentos WHERE atropelamento_id = ? AND tipo = 'dislike') AS dislikes
            FROM atropelamentos
            WHERE id = ?
        ");
        $stmt_counts->bind_param("iii", $atropelamento_id, $atropelamento_id, $atropelamento_id);
        $stmt_counts->execute();
        $resultado_counts = $stmt_counts->get_result();
        $counts = $resultado_counts->fetch_assoc();

        echo json_encode(['success' => true, 'likes' => $counts['likes'] ?? 0, 'dislikes' => $counts['dislikes'] ?? 0]);

        $stmt_check->close();
        $stmt_counts->close();

    } else {
        http_response_code(400);
        echo json_encode(['erro' => 'ID de atropelamento ou tipo de interação inválido.']);
    }

    $conn->close();
} else {
    http_response_code(400); 
    echo json_encode(['erro' => 'Requisição inválida.']);
}
?>