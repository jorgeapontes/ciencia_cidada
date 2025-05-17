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

// Verificar o cargo do usuário para determinar para qual painel redirecionar
$painel_voltar = 'painel_usuario.php'; // Padrão para usuários normais
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

// Verificar o cargo do usuário atual
$cargo_usuario = $_SESSION['cargo'] ?? 'user';
$pode_interagir = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin' || $cargo_usuario === 'user');

// Obter a opção de ordenação da URL (se existir)
$ordem = $_GET['ordem'] ?? 'DESC'; // DESC por padrão (mais recentes primeiro)
$ordem_sql = ($ordem === 'ASC') ? 'ASC' : 'DESC';

// Buscar publicações com ordenação
$stmt = $conn->prepare("
            SELECT p.*, u.nome, u.id as usuario_id,
            (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'like') AS likes,
            (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'dislike') AS dislikes,
            (SELECT tipo FROM interacoes WHERE publicacao_id = p.id AND usuario_id = ?) AS minha_interacao
            FROM publicacoes p
            JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.data_publicacao $ordem_sql");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed de Publicações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/feed.css">
    <style>
        .like-active {
            color: blue !important; /* Cor para indicar que o usuário curtiu */
        }
        .dislike-active {
            color: red !important; /* Cor para indicar que o usuário descurtiu */
        }
        .like-button:hover, .dislike-button:hover {
            cursor: pointer;
        }

        /* Estilos para o seletor de ordenação */
        .order-select-container {
            margin-bottom: 1rem;
            text-align: right;
        }

        .order-select {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Ciência Cidadã</a>
            <div class="navbar-nav">
                <a class="nav-link" href="home.html">Home</a>
                <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                <a class="nav-link active" href="feed_user.php">Seu Feed</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="feed-container">
        <div class="order-select-container">
            <select class="order-select" onchange="window.location.href='feed_user.php?ordem=' + this.value">
                <option value="DESC" <?= ($ordem === 'DESC') ? 'selected' : '' ?>>Mais Recentes Primeiro</option>
                <option value="ASC" <?= ($ordem === 'ASC') ? 'selected' : '' ?>>Mais Antigas Primeiro</option>
            </select>
        </div>

        <?php while ($pub = $resultado->fetch_assoc()): ?>
            <div class="card">
                <?php
                $nome_arquivo = basename($pub['caminho_foto']);
                $caminho_imagem = "fotos/" . $nome_arquivo;
                ?>

                <?php if (file_exists(__DIR__ . "/" . $caminho_imagem)): ?>
                    <img src="<?= $caminho_imagem ?>" class="card-img-top" alt="<?= htmlspecialchars($pub['titulo'] ?? '') ?>">
                <?php else: ?>
                    <div class="bg-secondary text-white p-5 text-center">
                        Imagem não encontrada
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($pub['titulo'] ?? '') ?></h5>
                    <div class="post-info">
                        <strong>Por:</strong> <?= htmlspecialchars($pub['nome'] ?? 'Desconhecido') ?>
                        |
                        <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pub['data_publicacao'] ?? '')) ?>
                    </div>
                    <p class="card-text"><?= nl2br(htmlspecialchars($pub['descricao'] ?? '')) ?></p>

                    <div class="card-actions">
                        <div class="btn-group" role="group">
                            <button class="like-button <?= ($pode_interagir ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'like' ? 'like-active' : '') ?>"
                                    data-publicacao-id="<?= $pub['id'] ?>" data-tipo="like" <?= ($pode_interagir ? '' : 'disabled') ?>>
                                <i class="bi bi-hand-thumbs-up"></i> <span class="badge bg-light text-dark like-count-<?= $pub['id'] ?>"><?= $pub['likes'] ?></span>
                            </button>
                            <button class="dislike-button <?= ($pode_interagir ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'dislike' ? 'dislike-active' : '') ?>"
                                    data-publicacao-id="<?= $pub['id'] ?>" data-tipo="dislike" <?= ($pode_interagir ? '' : 'disabled') ?>>
                                <i class="bi bi-hand-thumbs-down"></i> <span class="badge bg-light text-dark dislike-count-<?= $pub['id'] ?>"><?= $pub['dislikes'] ?></span>
                            </button>
                        </div>

                        <?php if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $pub['usuario_id']): ?>
                            <a href="delete.php?id=<?= $pub['id'] ?>" class="btn btn-danger btn-sm ms-auto"
                               onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">
                                Excluir
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="comentarios-container">
                        <h6>Comentários</h6>
                        <?php
                        $stmt_comentarios = $conn->prepare("
                                SELECT c.*, u.nome, u.cargo
                                FROM comentarios c
                                JOIN usuarios u ON c.usuario_id = u.id
                                WHERE c.publicacao_id = ?
                                ORDER BY c.data_comentario ASC
                            ");
                        $stmt_comentarios->bind_param("i", $pub['id']);
                        $stmt_comentarios->execute();
                        $comentarios = $stmt_comentarios->get_result();

                        if ($comentarios->num_rows > 0):
                            while ($comentario = $comentarios->fetch_assoc()): ?>
                                <div class="comentario visualizacao-apenas">
                                    <div class="comentario-info">
                                        <span class="comentario-autor">
                                            <?= htmlspecialchars($comentario['nome']) ?>
                                            <?= ($comentario['cargo'] === 'especialista') ? '<span class="badge bg-info">Especialista</span>' : '' ?>
                                            <?= ($comentario['cargo'] === 'admin') ? '<span class="badge bg-danger">Admin</span>' : '' ?>
                                            <?= ($comentario['cargo'] === 'user') ? '<span class="badge bg-secondary">Usuário</span>' : '' ?>
                                        </span>
                                        <span>
                                            <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                        </span>
                                    </div>
                                    <p class="comentario-text">
                                        <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                                    </p>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="alert alert-secondary">Nenhum comentário ainda.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($resultado->num_rows === 0): ?>
            <div class="alert alert-info">Nenhuma publicação encontrada.</div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botoesInteracao = document.querySelectorAll('.like-button, .dislike-button');

            botoesInteracao.forEach(botao => {
                botao.addEventListener('click', function(event) {
                    event.preventDefault();

                    const publicacaoId = this.dataset.publicacaoId;
                    const tipo = this.dataset.tipo;
                    const likeCountSpan = document.querySelector('.like-count-' + publicacaoId);
                    const dislikeCountSpan = document.querySelector('.dislike-count-' + publicacaoId);
                    const icone = this.querySelector('i');

                    fetch('interacao.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `publicacao_id=${encodeURIComponent(publicacaoId)}&tipo=${encodeURIComponent(tipo)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (likeCountSpan) likeCountSpan.textContent = data.likes;
                                if (dislikeCountSpan) dislikeCountSpan.textContent = data.dislikes;

                                const likeButton = this.parentNode.querySelector('.like-button[data-publicacao-id="' + publicacaoId + '"]');
                                const dislikeButton = this.parentNode.querySelector('.dislike-button[data-publicacao-id="' + publicacaoId + '"]');

                                if (tipo === 'like') {
                                    likeButton.classList.add('like-active');
                                    dislikeButton.classList.remove('dislike-active');
                                } else if (tipo === 'dislike') {
                                    dislikeButton.classList.add('dislike-active');
                                    likeButton.classList.remove('like-active');
                                }
                            } else {
                                alert('Erro ao processar interação.');
                                console.error(data.erro);
                            }
                        })
                        .catch(error => {
                            console.error('Erro na requisição:', error);
                            alert('Ocorreu um erro ao interagir.');
                        });
                });
            });
        });
    </script>
</body>
</html>