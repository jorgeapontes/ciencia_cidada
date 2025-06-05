<?php
session_start();
include 'conexao.php'; // Certifique-se que este arquivo contém a conexão mysqli $conn

if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <title>Acesso Negado</title>
        <style>
            body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_comentario') {
    $publicacao_id = $_POST['publicacao_id'] ?? null;
    $comentario_texto = $_POST['comentario'] ?? null;
    $usuario_id = $_SESSION['usuario_id'];
    $cargo_usuario = $_SESSION['cargo'] ?? 'user';
    $tipo_publicacao = 'publicacao';

    if (!($cargo_usuario === 'especialista' || $cargo_usuario === 'admin')) {
        $_SESSION['mensagem'] = "Seu cargo não permite adicionar comentários.";
        $_SESSION['tipo_mensagem'] = "warning";
    } elseif ($publicacao_id && $comentario_texto) {
        $stmt_insert_comentario = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario, tipo_publicacao) VALUES (?, ?, ?, NOW(), ?)");
        $stmt_insert_comentario->bind_param("iiss", $publicacao_id, $usuario_id, $comentario_texto, $tipo_publicacao);

        if ($stmt_insert_comentario->execute()) {
            $_SESSION['mensagem'] = "Comentário adicionado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar comentário: " . $conn->error;
            $_SESSION['tipo_mensagem'] = "danger";
        }
        $stmt_insert_comentario->close();
    } else {
        $_SESSION['mensagem'] = "Dados inválidos para o comentário.";
        $_SESSION['tipo_mensagem'] = "danger";
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); // Mantém filtros e ordem
    exit();
}

$painel_voltar = 'painel_usuario.php';
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

$cargo_usuario_sessao = $_SESSION['cargo'] ?? 'user';
$pode_interagir = true;
$pode_comentar = ($cargo_usuario_sessao === 'especialista' || $cargo_usuario_sessao === 'admin');

$ordem = $_GET['ordem'] ?? 'DESC';
$ordem_sql = ($ordem === 'ASC') ? 'ASC' : 'DESC';

$filtro = $_GET['filtro'] ?? 'tudo';
$where_filtro = '';
if ($filtro === 'animal') {
    $where_filtro = "AND p.categoria = 'animal'";
} elseif ($filtro === 'planta') {
    $where_filtro = "AND p.categoria = 'planta'";
}

$stmt = $conn->prepare("
    SELECT p.*, u.nome AS nome_usuario_post, u.id AS id_usuario_post, u.cargo AS cargo_usuario_post,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'like') AS likes,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'dislike') AS dislikes,
    (SELECT tipo FROM interacoes WHERE publicacao_id = p.id AND usuario_id = ?) AS minha_interacao
    FROM publicacoes p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.atropelamento = 0 $where_filtro
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
    <link href="css/feed.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Estilos base para botões de interação (like/dislike) */
        .card-actions .btn-group > .like-button,
        .card-actions .btn-group > .dislike-button {
            background-color: #ffffff; /* Fundo branco */
            border: 1px solid #ced4da; /* Borda cinza clara (neutra) */
            padding: 0.30rem 0.6rem;  /* Ajuste fino no padding */
            font-size: 0.875rem;    /* Tamanho de fonte padrão para botões pequenos */
            border-radius: 0.25rem; /* Borda arredondada */
            color: #495057;         /* Cor de texto/ícone neutra (cinza escuro) */
            margin: 0 3px;          /* Pequeno espaçamento entre os botões */
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out; /* Transição suave */
            display: inline-flex;   /* Para alinhar ícone e texto corretamente */
            align-items: center;     /* Alinha ícone e texto verticalmente */
            line-height: 1.5;       /* Altura da linha padrão */
        }

        .card-actions .btn-group > .like-button:hover,
        .card-actions .btn-group > .dislike-button:hover {
            background-color: #f8f9fa; /* Cinza muito claro no hover */
            border-color: #adb5bd;      /* Borda um pouco mais escura no hover */
        }

        /* Estilo para like ATIVO (cores neutras) */
        .like-button.like-active {
            background-color: #e9ecef;   /* Fundo cinza claro para ativo */
            border-color: #adb5bd;       /* Borda cinza para ativo */
            color: #28a745;             /* Ícone/texto verde para 'like' ativo (sutil) */
            /* Se preferir totalmente neutro para o ícone/texto: */
            /* color: #212529; */      /* Preto/cinza escuro */
        }

        /* Estilo para dislike ATIVO (cores neutras) */
        .dislike-button.dislike-active {
            background-color: #e9ecef; /* Fundo cinza claro para ativo */
            border-color: #adb5bd;     /* Borda cinza para ativo */
            color: #dc3545;            /* Ícone/texto vermelho para 'dislike' ativo (sutil) */
            /* Se preferir totalmente neutro para o ícone/texto: */
            /* color: #212529; */     /* Preto/cinza escuro */
        }

        .card-actions .btn-group > button .badge {
            background-color: #f8f9fa !important; /* Fundo claro para o contador */
            color: #212529 !important;           /* Texto escuro para o contador */
            border: 1px solid #dee2e6;           /* Borda sutil no contador */
            padding: 0.2em 0.4em;                 /* Padding menor para o badge */
            font-size: 0.75em;                   /* Fonte menor para o badge */
            margin-left: 5px;
        }

        .order-filter-container {
            display: flex; justify-content: space-between; align-items: center; 
        }
        .order-select {
            padding: 0.5rem 0.75rem; border-radius: 0.25rem; border: 1px solid #ced4da; font-size: 0.9rem;
        }
        .filter-button {
            padding: 0.5rem 0.75rem; border-radius: 0.25rem; background-color: #6c757d; color: white; font-size: 0.9rem; cursor: pointer; margin-left: 0.5rem; border: none;
        }
        .filter-button.active {
            background-color: #007bff; border-color: #007bff;
        }
        .btn-edit {
            background-color: rgb(7, 143, 255); color: white; border: none !important;
        }
        .btn-edit:hover {
            background-color: rgb(0, 146, 224);
        }
        .disabled-interact, button[disabled] { /* Estilo para botões desabilitados */
            opacity: 0.65; cursor: not-allowed !important;
        }
        .post-info {
            font-size: 0.85em; color: #555; margin-bottom: 10px;
        }
        .description-container {
            margin-bottom: 5px;
        }
        .card-actions {
            display: flex; justify-content: space-between; align-items: center;
        }
        .comment {
            background-color: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 10px;
        }
        .comment-header {
            display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 5px;
        }
        .comment-author {
            font-weight: bold;
        }
        .comment-date {
            color: #777;
        }
        .comment-content {
            font-size: 0.95em;
        }
        .no-comments {
            color: #777; font-style: italic;
        }
        .comment-form textarea {
            resize: vertical;
        }
        .badge.bg-info { background-color: #17a2b8 !important; }
        .badge.bg-danger { background-color: #dc3545 !important; }

        .post-category-info {
            font-size: 0.9em; color: #333; margin-top: 8px;
        }
        .post-category-info strong {
            color: #000;
        }
        .post-category-info p {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <nav id="japi-navbar" class="navbar navbar-expand-lg navbar-dark bg-dark justify-content-end">
        <div class="container-fluid d-flex justify-content-between">
            <a class="navbar-brand" href="#">JapiWiki</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                    <a class="nav-link active" aria-current="page" href="feed_user.php">Feed</a>
                    <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                    <a class="nav-link" href="publicar.php">Publicar</a>
                    <a class="nav-link" href="logout.php">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="feed-container container mt-4">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
        <?php endif; ?>

        <div class="order-filter-container">
            <div class="order-select-container">
                <select class="order-select form-select-sm" onchange="window.location.href='feed_user.php?ordem=' + this.value + '&filtro=<?= $filtro ?>'">
                    <option value="DESC" <?= ($ordem === 'DESC') ? 'selected' : '' ?>>Mais Recentes Primeiro</option>
                    <option value="ASC" <?= ($ordem === 'ASC') ? 'selected' : '' ?>>Mais Antigos Primeiro</option>
                </select>
            </div>
            <div class="filter-buttons-container">
                <button class="filter-button <?= ($filtro === 'tudo' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=tudo&ordem=<?= $ordem ?>'">Tudo</button>
                <button class="filter-button <?= ($filtro === 'animal' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=animal&ordem=<?= $ordem ?>'">Animais</button>
                <button class="filter-button <?= ($filtro === 'planta' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=planta&ordem=<?= $ordem ?>'">Plantas</button>
            </div>
        </div>

        <?php while ($pub = $resultado->fetch_assoc()): ?>
            <div class="card mb-3">
                <?php
                $nome_arquivo = basename($pub['caminho_foto']);
                $caminho_imagem = "fotos/" . $nome_arquivo;
                ?>

                <div class="card-img-container">
                    <?php if (file_exists(__DIR__ . "/" . $caminho_imagem) && !is_dir(__DIR__ . "/" . $caminho_imagem)): ?>
                        <img src="<?= htmlspecialchars($caminho_imagem) ?>" class="card-img-top" alt="<?= htmlspecialchars($pub['titulo'] ?? '') ?>">
                    <?php else: ?>
                        <div class="bg-secondary text-white p-5 text-center">
                            Imagem não encontrada (<?= htmlspecialchars($caminho_imagem)?>)
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($pub['titulo'] ?? '') ?></h5>

                    <div class="post-info">
                        <strong>Por:</strong> <?= htmlspecialchars($pub['nome_usuario_post'] ?? 'Desconhecido') ?>
                        <?php if ($pub['cargo_usuario_post'] === 'especialista'): ?>
                            <span class="badge bg-info text-dark">Especialista</span>
                        <?php elseif ($pub['cargo_usuario_post'] === 'admin'): ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php endif; ?>
                        <br>
                        <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pub['data_publicacao'] ?? '')) ?>
                    </div>

                    <div class="description-container">
                        <p class="card-text"><strong>Descrição:</strong><br>
                        <?= nl2br(htmlspecialchars($pub['descricao'] ?? '')) ?></p>

                        <div class="post-category-info">
                            <?php if (!empty($pub['categoria'])): ?>
                                <p class="card-text"><strong>Categoria:</strong> <?= ucfirst(htmlspecialchars($pub['categoria'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($pub['sub_categoria'])): ?>
                                <p class="card-text"><strong>Tipo:</strong> <?= ucfirst(htmlspecialchars($pub['sub_categoria'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($pub['nome_cientifico'])): ?>
                                <p class="card-text"><strong>Nome Científico:</strong> <em class="text-muted"><?= htmlspecialchars($pub['nome_cientifico']) ?></em></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-actions">
                        <div class="btn-group" role="group">
                            <button class="like-button <?= ($pub['minha_interacao'] === 'like' ? 'like-active' : '') ?>"
                                    data-publicacao-id="<?= $pub['id'] ?>" data-tipo="like" <?= (!$pode_interagir ? 'disabled' : '') ?>>
                                <i class="bi bi-hand-thumbs-up"></i> <span class="badge like-count-<?= $pub['id'] ?>"><?= $pub['likes'] ?></span>
                            </button>
                            <button class="dislike-button <?= ($pub['minha_interacao'] === 'dislike' ? 'dislike-active' : '') ?>"
                                    data-publicacao-id="<?= $pub['id'] ?>" data-tipo="dislike" <?= (!$pode_interagir ? 'disabled' : '') ?>>
                                <i class="bi bi-hand-thumbs-down"></i> <span class="badge dislike-count-<?= $pub['id'] ?>"><?= $pub['dislikes'] ?></span>
                            </button>
                        </div>

                        <?php if (isset($_SESSION['cargo']) && ($_SESSION['cargo'] === 'admin' || (isset($pub['id_usuario_post']) && $_SESSION['usuario_id'] == $pub['id_usuario_post']))): ?>
                            <a href="delete.php?id=<?= $pub['id'] ?>&tipo=publicacao" class="btn btn-danger btn-sm"
                               onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">
                                Excluir
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="comment-section">
                        <h6>Comentários</h6>
                        <?php
                        $stmt_comentarios = $conn->prepare("
                            SELECT c.*, u.nome AS nome_usuario_comentario, u.cargo AS cargo_usuario_comentario, u.id AS id_usuario_comentario
                            FROM comentarios c
                            JOIN usuarios u ON c.usuario_id = u.id
                            WHERE c.publicacao_id = ? AND c.tipo_publicacao = 'publicacao'
                            ORDER BY c.data_comentario ASC
                        ");
                        $stmt_comentarios->bind_param("i", $pub['id']);
                        $stmt_comentarios->execute();
                        $comentarios = $stmt_comentarios->get_result();

                        if ($comentarios->num_rows > 0):
                            while ($comentario = $comentarios->fetch_assoc()): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <span class="comment-author">
                                            <?= htmlspecialchars($comentario['nome_usuario_comentario']) ?>
                                            <?php if ($comentario['cargo_usuario_comentario'] === 'especialista'): ?>
                                                <span class="badge bg-info text-dark">Especialista</span>
                                            <?php elseif ($comentario['cargo_usuario_comentario'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="comment-date">
                                            <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                        </span>
                                    </div>
                                    <div class="comment-content">
                                        <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                                    </div>
                                    <?php if (isset($_SESSION['cargo']) && ($_SESSION['cargo'] === 'admin' || (isset($comentario['id_usuario_comentario']) && $_SESSION['usuario_id'] == $comentario['id_usuario_comentario']))): ?>
                                        <div class="comment-actions mt-2">
                                            <a href="editar_comentario.php?comentario_id=<?= $comentario['id'] ?>&tipo_publicacao=publicacao&publicacao_id=<?= $pub['id'] ?>" class="btn btn-edit btn-sm">Editar</a>
                                            <a href="delete.php?comentario_id=<?= $comentario['id'] ?>&tipo=publicacao_comentario&publicacao_id=<?= $pub['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este comentário?')">Excluir</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="no-comments">Nenhum comentário ainda.</div>
                        <?php endif;
                        $stmt_comentarios->close();
                        ?>
                    </div>

                    <?php if ($pode_comentar): ?>
                        <div class="comment-form mt-3">
                            <h6>Adicionar Comentário</h6>
                            <form method="POST" action="feed_user.php?<?= http_build_query($_GET) ?>">
                                <input type="hidden" name="acao" value="adicionar_comentario">
                                <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                <textarea name="comentario" rows="3" class="form-control mb-2" required placeholder="Escreva seu comentário aqui..."></textarea>
                                <button type="submit" class="btn btn-primary btn-sm">Comentar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($resultado->num_rows === 0): ?>
            <div class="alert alert-info mt-3">Nenhuma publicação encontrada.</div>
        <?php endif;
        $stmt->close();
        $conn->close();
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botoesInteracao = document.querySelectorAll('.like-button, .dislike-button');

            botoesInteracao.forEach(botao => {
                botao.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (this.hasAttribute('disabled')) {
                        return;
                    }

                    const publicacaoId = this.dataset.publicacaoId;
                    const tipo = this.dataset.tipo;
                    const likeCountSpan = document.querySelector('.like-count-' + publicacaoId);
                    const dislikeCountSpan = document.querySelector('.dislike-count-' + publicacaoId);

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

                            const likeButton = document.querySelector(`.like-button[data-publicacao-id="${publicacaoId}"]`);
                            const dislikeButton = document.querySelector(`.dislike-button[data-publicacao-id="${publicacaoId}"]`);

                            // Remove classes ativas de ambos
                            likeButton.classList.remove('like-active');
                            dislikeButton.classList.remove('dislike-active');

                            // Adiciona classe ativa ao botão clicado, se a interação foi bem sucedida
                            if (data.nova_interacao === 'like') {
                                likeButton.classList.add('like-active');
                            } else if (data.nova_interacao === 'dislike') {
                                dislikeButton.classList.add('dislike-active');
                            }

                        } else {
                            let mensagemErro = 'Erro ao processar interação.';
                            if (data.mensagem) {
                                mensagemErro = data.mensagem;
                            } else if (data.erro) {
                                mensagemErro = 'Erro: ' + data.erro;
                            }
                            alert(mensagemErro);
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        alert('Ocorreu um erro ao interagir. Verifique sua conexão ou tente novamente mais tarde.');
                    });
                });
            });
        });
    </script>
</body>
</html>