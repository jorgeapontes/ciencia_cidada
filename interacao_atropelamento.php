<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(403); // Forbidden
    echo json_encode(['erro' => 'Usuário não autenticado.']);
    exit;
}

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atropelamento_id']) && isset($_POST['tipo'])) {
    $atropelamento_id = filter_input(INPUT_POST, 'atropelamento_id', FILTER_VALIDATE_INT);
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario_id = $_SESSION['usuario_id'];

    if ($atropelamento_id && ($tipo === 'like' || $tipo === 'dislike')) {
        // Verificar se o usuário já interagiu com este atropelamento
        $stmt_check = $conn->prepare("SELECT id, tipo FROM interacoes_atropelamentos WHERE usuario_id = ? AND atropelamento_id = ?");
        $stmt_check->bind_param("ii", $usuario_id, $atropelamento_id);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();

        if ($resultado_check->num_rows > 0) {
            $interacao_existente = $resultado_check->fetch_assoc();
            if ($interacao_existente['tipo'] === $tipo) {
                // Remover a interação se o usuário clicar no mesmo botão novamente
                $stmt_delete = $conn->prepare("DELETE FROM interacoes_atropelamentos WHERE id = ?");
                $stmt_delete->bind_param("i", $interacao_existente['id']);
                $stmt_delete->execute();
            } else {
                // Atualizar a interação se o tipo for diferente
                $stmt_update = $conn->prepare("UPDATE interacoes_atropelamentos SET tipo = ? WHERE id = ?");
                $stmt_update->bind_param("si", $tipo, $interacao_existente['id']);
                $stmt_update->execute();
            }
        } else {
            // Inserir nova interação
            $stmt_insert = $conn->prepare("INSERT INTO interacoes_atropelamentos (usuario_id, atropelamento_id, tipo, data_interacao) VALUES (?, ?, ?, NOW())");
            $stmt_insert->bind_param("iis", $usuario_id, $atropelamento_id, $tipo);
            $stmt_insert->execute();
        }

        // Recalcular e retornar os counts de likes e dislikes
        $stmt_counts = $conn->prepare("
            SELECT
                (SELECT COUNT(*) FROM interacoes_atropelamentos WHERE atropelamento_id = ? AND tipo = 'like') AS likes,
                (SELECT COUNT(*) FROM interacoes_atropelamentos WHERE atropelamento_id = ? AND tipo = 'dislike') AS dislikes
        ");
        $stmt_counts->bind_param("ii", $atropelamento_id, $atropelamento_id);
        $stmt_counts->execute();
        $resultado_counts = $stmt_counts->get_result();
        $counts = $resultado_counts->fetch_assoc();

        echo json_encode(['success' => true, 'likes' => $counts['likes'], 'dislikes' => $counts['dislikes']]);

        $stmt_check->close();
        $stmt_update->close();
        $stmt_insert->close();
        $stmt_counts->close();

    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['erro' => 'ID de atropelamento ou tipo de interação inválido.']);
    }

    $conn->close();
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['erro' => 'Requisição inválida.']);
}
?>