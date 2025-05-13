<?php
session_start();
if (!isset($_SESSION["usuario_id"]) || $_SESSION["cargo"] !== 'especialista') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Especialista - Aves Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Painel do Especialista</h1>
        <p>Bem-vindo(a), <?php echo $_SESSION["nome"]; ?>!</p>
        <p><a href="logout.php" class="btn btn-danger">Sair</a></p>
        <p><a href="feed.php" class="btn btn-primary">Feed</a></p>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>