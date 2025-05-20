<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$comentario_id = $_GET['comentario_id'] ?? null;
$tipo_publicacao = $_GET['tipo_publicacao'] ?? null; 
$usuario_logado_id = $_SESSION['usuario_id'];
$cargo_usuario_logado = $_SESSION['cargo'];

$comentario_atual = null;
$mensagem = '';
$tipo_mensagem = '';

if ($comentario_id && $tipo_publicacao) {
    // Buscar o comentário no banco de dados
    $stmt = $conn->prepare("SELECT * FROM comentarios WHERE id = ? AND tipo_publicacao = ?");
    $stmt->bind_param("is", $comentario_id, $tipo_publicacao);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $comentario_atual = $resultado->fetch_assoc();
    $stmt->close();


    if (!$comentario_atual) {
        $mensagem = "Comentário não encontrado.";
        $tipo_mensagem = "danger";
    } elseif ($comentario_atual['usuario_id'] != $usuario_logado_id && $cargo_usuario_logado !== 'admin') {
        $mensagem = "Você não tem permissão para editar este comentário.";
        $tipo_mensagem = "danger";
        $comentario_atual = null; 
    }
} else {
    $mensagem = "ID do comentário ou tipo de publicação não fornecido.";
    $tipo_mensagem = "danger";
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_comentario') {
    $novo_comentario_texto = $_POST['comentario'] ?? '';
    $id_do_comentario = $_POST['comentario_id_form'] ?? null;
    $tipo_da_publicacao_form = $_POST['tipo_publicacao_form'] ?? null;

    if ($id_do_comentario && $tipo_da_publicacao_form && $novo_comentario_texto && $comentario_atual && $comentario_atual['id'] == $id_do_comentario && $comentario_atual['tipo_publicacao'] == $tipo_da_publicacao_form) {
      
        if ($comentario_atual['usuario_id'] == $usuario_logado_id || $cargo_usuario_logado === 'admin') {
            $stmt_update = $conn->prepare("UPDATE comentarios SET comentario = ? WHERE id = ? AND tipo_publicacao = ?");
            $stmt_update->bind_param("sis", $novo_comentario_texto, $id_do_comentario, $tipo_da_publicacao_form);

            if ($stmt_update->execute()) {
                $_SESSION['mensagem'] = "Comentário atualizado com sucesso!";
                $_SESSION['tipo_mensagem'] = "success";
             
                if ($tipo_da_publicacao_form === 'publicacao') {
                    header('Location: feed_user.php');
                } else {
                    header('Location: feed_atropelamentos.php');
                }
                exit();
            } else {
                $mensagem = "Erro ao atualizar comentário: " . $conn->error;
                $tipo_mensagem = "danger";
            }
            $stmt_update->close();
        } else {
            $mensagem = "Você não tem permissão para realizar esta ação.";
            $tipo_mensagem = "danger";
        }
    } else {
        $mensagem = "Dados inválidos para a atualização.";
        $tipo_mensagem = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Comentário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/feed.css"> <style>
        .container {
            max-width: 600px;
            margin-top: 50px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Editar Comentário</h2>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <?php if ($comentario_atual): ?>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="editar_comentario">
                <input type="hidden" name="comentario_id_form" value="<?= htmlspecialchars($comentario_atual['id']) ?>">
                <input type="hidden" name="tipo_publicacao_form" value="<?= htmlspecialchars($comentario_atual['tipo_publicacao']) ?>">

                <div class="mb-3">
                    <label for="comentario" class="form-label">Seu Comentário:</label>
                    <textarea class="form-control" id="comentario" name="comentario" rows="5" required><?= htmlspecialchars($comentario_atual['comentario']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Edição</button>
                <a href="<?= ($comentario_atual['tipo_publicacao'] === 'publicacao' ? 'feed_user.php' : 'feed_atropelamentos.php') ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>