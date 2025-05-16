<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

// Busca todas as postagens com informações do usuário e descrição
$sql = "SELECT p.*, u.nome
        FROM postagens p
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.data_postagem DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Aves Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .postagem {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }
        .postagem img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .postagem-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .postagem-usuario {
            font-weight: bold;
            margin-left: 10px;
        }
        .postagem-data {
            color: #6c757d;
            font-size: 0.9em;
        }
        .postagem-descricao {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .navbar {
            margin-bottom: 20px; /* Adiciona espaço abaixo da navbar */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Aves Brasil</a>
            <div class="navbar-nav">
                <a class="nav-link active" href="painel.php">Feed Geral</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($postagem = $result->fetch_assoc()): ?>
                        <div class="postagem">
                            <div class="postagem-header">
                                <img src="https://via.placeholder.com/40" alt="Foto do usuário" class="rounded-circle">
                                <div>
                                    <div class="postagem-usuario"><?= htmlspecialchars($postagem['nome']) ?></div>
                                    <div class="postagem-data"><?= date('d/m/Y H:i', strtotime($postagem['data_postagem'])) ?></div>
                                </div>
                            </div>

                            <img src="<?= htmlspecialchars($postagem['imagem_path']) ?>" alt="Postagem de ave">

                            <?php if (!empty($postagem['descricao'])): ?>
                                <div class="postagem-descricao">
                                    <?= nl2br(htmlspecialchars($postagem['descricao'])) ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary">Curtir</button>
                                <button class="btn btn-sm btn-outline-secondary">Comentar</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhuma postagem encontrada.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>