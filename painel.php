<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Aves Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="painel.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Aves Brasil</a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">
                Olá, <?= $_SESSION['nome']; ?> (<?= $_SESSION['cargo']; ?>)
            </span>
            <a href="logout.php" class="btn btn-outline-light">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Bem-vindo ao sistema de fotos de aves!</h2>

    <a href="publicar.php" class="btn btn-primary mt-3">Nova Publicação</a>
    <a href="feed.php">Ver publicações</a>

    <?php if ($_SESSION['cargo'] === 'admin'): ?>
        <a href="admin.php" class="btn btn-warning mt-3 ms-2">Painel do Admin</a>
    <?php endif; ?>
</div>

</body>
</html>
