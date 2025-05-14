<?php
session_start();
if (!isset($_SESSION["usuario_id"]) || $_SESSION["cargo"] !== 'user') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="painel.css">
</head>
<body>
    <div class="container mt-5 text-center">
        <h1>Painel do usuário</h1>
        <p>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION["nome"]); ?>!</p>

        <div class="d-grid gap-2 col-6 mx-auto mt-4">
            <a href="publicar.php" class="btn btn-success btn-lg">Nova Publicação</a>
            <a href="feed_user.php" class="btn btn-primary btn-lg">Ver Feed</a>
            <a href="logout.php" class="btn btn-danger mt-3">Sair</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
