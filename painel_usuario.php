<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

// Buscar as publicações normais do usuário
$stmt_publicacoes = $conn->prepare("
    SELECT p.*
    FROM publicacoes p
    WHERE p.usuario_id = ?
    ORDER BY p.data_publicacao DESC
");
$stmt_publicacoes->bind_param("i", $_SESSION['usuario_id']);
$stmt_publicacoes->execute();
$resultado_publicacoes = $stmt_publicacoes->get_result();
$publicacoes = $resultado_publicacoes->fetch_all(MYSQLI_ASSOC);

// Buscar os casos de atropelamento do usuário
$stmt_atropelamentos = $conn->prepare("
    SELECT a.*
    FROM atropelamentos a
    WHERE a.usuario_id = ?
    ORDER BY a.data_postagem DESC
");
$stmt_atropelamentos->bind_param("i", $_SESSION['usuario_id']);
$stmt_atropelamentos->execute();
$resultado_atropelamentos = $stmt_atropelamentos->get_result();
$atropelamentos = $resultado_atropelamentos->fetch_all(MYSQLI_ASSOC);

// Unir os dois arrays de resultados
$posts = array_merge($publicacoes, $atropelamentos);

// Ordenar os posts por data de publicação
usort($posts, function ($a, $b) {
    $data_a = $a['data_publicacao'] ?? $a['data_postagem'] ?? null;
    $data_b = $b['data_publicacao'] ?? $b['data_postagem'] ?? null;

    if ($data_a === null && $data_b === null) return 0;
    if ($data_a === null) return 1;
    if ($data_b === null) return -1;

    return strtotime($data_b) - strtotime($data_a);
});
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/painel_usuario.css">
    <style>
        .post-container {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }
        .post-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
        }
        .post-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Ciência Cidadã</a>
            <div class="navbar-nav">
                <a class="nav-link active" href="painel_usuario.php">Painel</a>
                <a class="nav-link" href="feed_user.php">Ver Feed</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link" href="publicar.php">Nova Publicação</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Painel do usuário</h1>
        <?php if (isset($_SESSION['nome'])): ?>
            <p>Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>!</p>
        <?php endif; ?>

        <div class="mb-3">
            <a href="publicar.php" class="btn btn-success">Nova Publicação</a>
            <a href="feed_user.php" class="btn btn-primary">Ver Feed</a>
            <a href="feed_atropelamentos.php" class="btn btn-info">Atropelamentos</a>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>

        <h2>Suas Publicações</h2>
        <?php if (empty($posts)): ?>
            <p>Você ainda não fez nenhuma publicação.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-container">
                    <?php if (isset($post['caminho_foto']) && file_exists($post['caminho_foto'])): ?>
                        <img src="<?= htmlspecialchars($post['caminho_foto']) ?>" alt="Imagem da publicação" class="post-image">
                    <?php endif; ?>

                    <div>
                        <?php if (isset($post['titulo'])): ?>
                            <h3><?= htmlspecialchars($post['titulo']) ?></h3>
                            <p><?= nl2br(htmlspecialchars($post['descricao'])) ?></p>
                            <p>Data: <?= date('d/m/Y H:i', strtotime($post['data_publicacao'])) ?></p>
                        <?php elseif (isset($post['especie'])): ?>
                            <h3>Caso de Atropelamento</h3>
                            <?php if (!empty($post['especie'])): ?>
                                <p>Espécie: <?= htmlspecialchars($post['especie']) ?></p>
                            <?php endif; ?>
                            <p><?= nl2br(htmlspecialchars($post['descricao'])) ?></p>
                            <?php if (!empty($post['data_ocorrencia'])): ?>
                                <p>Ocorrência: <?= date('d/m/Y H:i', strtotime($post['data_ocorrencia'])) ?></p>
                            <?php endif; ?>
                            <p>Postado em: <?= date('d/m/Y H:i', strtotime($post['data_postagem'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="post-actions">
                        <?php if (isset($post['titulo'])): ?>
                            <a href="editar_publicacao.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="delete_publicacao.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">Excluir</a>
                        <?php elseif (isset($post['especie'])): ?>
                            <a href="editar_atropelamento.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="delete_atropelamento.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este caso de atropelamento?')">Excluir</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>