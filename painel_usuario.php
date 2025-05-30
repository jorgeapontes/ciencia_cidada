<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Acesso Negado</title>
        <style>
            body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
            h1 { color: #d9534f; margin-bottom: 20px; }
            p { margin-bottom: 15px; }
            .login-link { color: #007bff; text-decoration: none; font-weight: bold; }
            .login-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Acesso Negado</h1>
            <p>Você precisa estar logado para acessar esta página.</p>
            <p><a href='login.php' class='login-link'>Fazer Login</a></p>
        </div>
    </body>
    </html>
    HTML;

    echo $html;
    exit;
}

include 'conexao.php';

$usuario_id = $_SESSION['usuario_id'];

// Contagem total de posts (publicações normais + atropelamentos)
$stmt_total_posts = $conn->prepare("
    SELECT COUNT(*) AS total FROM (
        SELECT id FROM publicacoes WHERE usuario_id = ?
        UNION ALL
        SELECT id FROM atropelamentos WHERE usuario_id = ?
    ) AS total_posts
");
$stmt_total_posts->bind_param("ii", $usuario_id, $usuario_id);
$stmt_total_posts->execute();
$resultado_total_posts = $stmt_total_posts->get_result();
$total_posts = $resultado_total_posts->fetch_assoc()['total'];
$stmt_total_posts->close();

// Contagem de espécies distintas nas publicações normais
$stmt_especies = $conn->prepare("
    SELECT COUNT(DISTINCT especie) AS total_especies
    FROM publicacoes
    WHERE usuario_id = ? AND especie IS NOT NULL AND especie != ''
");
$stmt_especies->bind_param("i", $usuario_id);
$stmt_especies->execute();
$resultado_especies = $stmt_especies->get_result();
$total_especies = $resultado_especies->fetch_assoc()['total_especies'];
$stmt_especies->close();

// Contagem de posts de animais 
$stmt_animais = $conn->prepare("
    SELECT COUNT(*) AS total_animais
    FROM publicacoes
    WHERE usuario_id = ? AND categoria = 'animal'
");
$stmt_animais->bind_param("i", $usuario_id);
$stmt_animais->execute();
$resultado_animais = $stmt_animais->get_result();
$total_animais = $resultado_animais->fetch_assoc()['total_animais'];
$stmt_animais->close();

// Contagem de posts de plantas 
$stmt_plantas = $conn->prepare("
    SELECT COUNT(*) AS total_plantas
    FROM publicacoes
    WHERE usuario_id = ? AND categoria = 'planta'
");
$stmt_plantas->bind_param("i", $usuario_id);
$stmt_plantas->execute();
$resultado_plantas = $stmt_plantas->get_result();
$total_plantas = $resultado_plantas->fetch_assoc()['total_plantas'];
$stmt_plantas->close();

// Contagem de casos de atropelamento
$stmt_atropelamentos_count = $conn->prepare("
    SELECT COUNT(*) AS total_atropelamentos
    FROM atropelamentos
    WHERE usuario_id = ?
");
$stmt_atropelamentos_count->bind_param("i", $usuario_id);
$stmt_atropelamentos_count->execute();
$resultado_atropelamentos_count = $stmt_atropelamentos_count->get_result();
$total_atropelamentos = $resultado_atropelamentos_count->fetch_assoc()['total_atropelamentos'];
$stmt_atropelamentos_count->close();

// Buscar as publicações normais 
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

// Buscar os casos de atropelamento 
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
        .user-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }
        .user-stats > div {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .stat-icon {
            font-size: 1.5em; 
        }
    </style>
</head>
<body>
    <nav id="navbar" class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="brand" href="home.php">JapiWiki</a> <div id="japi-navbar"class="navbar-nav">
                <a class="nav-link" href="home.php">Home</a> <a class="nav-link active" href="painel_usuario.php">Painel</a>
                <a class="nav-link" href="feed_user.php">Feed</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link" href="publicar.php">Publicar</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Painel do usuário</h1>
        <?php if (isset($_SESSION['nome'])): ?>
            <p>Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>!</p>
        <?php endif; ?>

        <div class="user-stats">
            <div><span class="stat-icon">📝</span> <strong>Posts:</strong> <?= $total_posts ?></div>
            <div><span class="stat-icon">🐾</span> <strong>Animais:</strong> <?= $total_animais ?></div>
            <div><span class="stat-icon">🌳</span> <strong>Plantas:</strong> <?= $total_plantas ?></div>
            <div><span class="stat-icon">⚠️</span> <strong>Atropelamentos:</strong> <?= $total_atropelamentos ?></div>
        </div>

        <div class="mb-3">
            <a href="publicar.php" class="btn btn-success">Nova Publicação</a>
            <a href="feed_user.php" class="btn btn-primary">Ver Feed</a>
            <a href="feed_atropelamentos.php" class="btn btn-primary">Atropelamentos</a>
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
                            <a href="delete.php?id=<?= $post['id'] ?>&tipo=publicacao" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">Excluir</a>
                        <?php elseif (isset($post['especie'])): ?>
                            <a href="editar_atropelamento.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="delete.php?id=<?= $post['id'] ?>&tipo=atropelamento" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este caso de atropelamento?')">Excluir</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>