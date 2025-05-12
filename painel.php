<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Aves Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">Aves Brasil</a>
            <div class="d-flex">
                <span class="navbar-text me-3 text-white">
                    Olá, <?= $_SESSION['nome']; ?> (Admin)
                </span>
                <a href="logout.php" class="btn btn-outline-light">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Painel Administrativo</h2>
        
        <div class="d-flex gap-2 mt-3">
            <a href="criar_admin.php" class="btn btn-warning">Criar Novo Admin</a>
            <a href="gerenciar_usuarios.php" class="btn btn-secondary">Gerenciar Usuários</a>
            <a href="feed.php" class="btn btn-primary">Ver Feed</a>
        </div>
    </div>
</body>
</html>