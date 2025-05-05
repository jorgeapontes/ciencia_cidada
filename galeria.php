<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Comentar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comentario'])) {
    $texto = $_POST['comentario'];
    $pub_id = $_POST['pub_id'];
    $user_id = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, texto) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $pub_id, $user_id, $texto);
    $stmt->execute();
}

// Curtir
if (isset($_POST['curtir'])) {
    $pub_id = $_POST['pub_id'];
    $user_id = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO curtidas (publicacao_id, usuario_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $pub_id, $user_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Galeria de Aves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Galeria de Aves</h2>
    <a href="painel.php" class="btn btn-secondary mb-4">← Voltar</a>

    <?php
    $publicacoes = $conn->query("
        SELECT publicacoes.*, usuarios.nome FROM publicacoes
        JOIN usuarios ON publicacoes.usuario_id = usuarios.id
        ORDER BY publicacoes.data_publicacao DESC
    ");

    while ($pub = $publicacoes->fetch_assoc()):
        $pub_id = $pub['id'];

        // Conta curtidas
        $curtidas = $conn->query("SELECT COUNT(*) AS total FROM curtidas WHERE publicacao_id = $pub_id");
        $totalCurtidas = $curtidas->fetch_assoc()['total'];

        // Comentários
        $comentarios = $conn->query("
            SELECT c.texto, u.nome, c.data_comentario FROM comentarios c
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.publicacao_id = $pub_id
            ORDER BY c.data_comentario DESC
        ");
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <strong><?= $pub['especie'] ?></strong> por <?= $pub['nome'] ?> em <?= date('d/m/Y', strtotime($pub['data_publicacao'])) ?>
        </div>
        <img src="<?= $pub['foto'] ?>" class="card-img-top" style="max-height: 400px; object-fit: cover;">
        <div class="card-body">
            <form method="POST" class="d-inline">
                <input type="hidden" name="pub_id" value="<?= $pub_id ?>">
                <button type="submit" name="curtir" class="btn btn-outline-danger btn-sm">❤️ Curtir (<?= $totalCurtidas ?>)</button>
            </form>
        </div>
        <div class="card-footer">
            <form method="POST" class="mb-3">
                <input type="hidden" name="pub_id" value="<?= $pub_id ?>">
                <div class="input-group">
                    <input type="text" name="comentario" class="form-control" placeholder="Escreva um comentário..." required>
                    <button class="btn btn-primary" type="submit">Comentar</button>
                </div>
            </form>
            <div>
                <?php while ($c = $comentarios->fetch_assoc()): ?>
                    <div class="mb-1">
                        <strong><?= $c['nome'] ?></strong> (<?= date('d/m H:i', strtotime($c['data_comentario'])) ?>):<br>
                        <?= htmlspecialchars($c['texto']) ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php endwhile; ?>
</div>
</body>
</html>
