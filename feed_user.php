    <?php
    session_start();
    include 'conexao.php'; 

    if (!isset($_SESSION['usuario_id'])) {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <title>Acesso Negado</title>
            <style>
                body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .container { background-color: white; padding: 0px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
                h1 { color: #d9534f; margin-bottom: 0px; }
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
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
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

    $cargo_usuario = $_SESSION['cargo'] ?? 'user';
    $pode_interagir = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin' || $cargo_usuario === 'user');
    $pode_comentar = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin');

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
        SELECT p.*, u.nome, u.id as usuario_id,
        (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'like') AS likes,
        (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'dislike') AS dislikes,
        (SELECT tipo FROM interacoes WHERE publicacao_id = p.id AND usuario_id = ?) AS minha_interacao
        FROM publicacoes p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE 1 $where_filtro
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
            .like-active { 
                color: blue !important; 
            }
            .dislike-active { 
                color: red !important; 
            }
            .like-button:hover, .dislike-button:hover { c
                ursor: pointer; 
            }
            .order-filter-container { 
                display: flex; justify-content: space-between; align-items: center; 
            }
            .order-select { 
                padding: 0.5rem 0.75rem; border-radius: 0.25rem; border: 1px solid #ced4da; font-size: 0.8rem;
            }
            .filter-button { 
                padding: 0.5rem 0.75rem; border-radius: 0.25rem;  background-color: black; color: white; font-size: 0.8rem; cursor: pointer; margin-left: 0.5rem; 
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
            .disabled-interact { 
                opacity: 0.5; cursor: not-allowed !important; 
            }
        </style>
    </head>
    <body>
        <nav id="japi-navbar" class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">JapiWiki</a>
                <div class="navbar-nav">
                    <a class="nav-link" href="home.php">Home</a>
                    <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                    <a class="nav-link active" href="feed_user.php">Feed</a>
                    <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                    <a class="nav-link" href="publicar.php">Publicar</a>
                    <a class="nav-link" href="logout.php">Sair</a>
                </div>
            </div>
        </nav>

        <div class="feed-container">
            <?php if (isset($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                    <?= $_SESSION['mensagem'] ?>
                </div>
                <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
            <?php endif; ?>

            <div class="order-filter-container">
                <div class="order-select-container">
                    <select class="order-select" onchange="window.location.href='feed_user.php?ordem=' + this.value + '&filtro=<?= $filtro ?>'">
                        <option value="DESC" <?= ($ordem === 'DESC') ? 'selected' : '' ?>>Mais Recentes Primeiro</option>
                        <option value="ASC" <?= ($ordem === 'ASC') ? 'selected' : '' ?>>Mais Antigas Primeiro</option>
                    </select>
                </div>
                <div class="filter-buttons-container">
                    <button class="filter-button <?= ($filtro === 'tudo' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=tudo&ordem=<?= $ordem ?>'">Tudo</button>
                    <button class="filter-button <?= ($filtro === 'animal' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=animal&ordem=<?= $ordem ?>'">Animais</button>
                    <button class="filter-button <?= ($filtro === 'planta' ? 'active' : '') ?>" onclick="window.location.href='feed_user.php?filtro=planta&ordem=<?= $ordem ?>'">Plantas</button>
                </div>
            </div>

            <?php while ($pub = $resultado->fetch_assoc()): ?>
                <div class="card">
                    <?php
                    $nome_arquivo = basename($pub['caminho_foto']);
                    $caminho_imagem = "fotos/" . $nome_arquivo;
                    ?>

                    <div class="card-img-container">
                        <?php if (file_exists(__DIR__ . "/" . $caminho_imagem)): ?>
                            <img src="<?= $caminho_imagem ?>" class="card-img-top" alt="<?= htmlspecialchars($pub['titulo'] ?? '') ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white p-5 text-center">
                                Imagem não encontrada
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($pub['titulo'] ?? '') ?></h5>
                        
                        <div class="post-info">
                            <strong>Por:</strong> <?= htmlspecialchars($pub['nome'] ?? 'Desconhecido') ?>
                            <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pub['data_publicacao'] ?? '')) ?>
                        </div>
                        
                        <div class="description-container">
                            <p class="card-text"><strong>Descrição:</strong><br>
                            <?= nl2br(htmlspecialchars($pub['descricao'] ?? '')) ?></p>
                        </div>

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
                                <a href="delete.php?id=<?= $pub['id'] ?>&tipo=publicacao" class="btn btn-danger btn-sm ms-auto"
                                onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">
                                    Excluir
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="comment-section">
                            <h6>Comentários</h6>
                            <?php
                            $stmt_comentarios = $conn->prepare("
                                SELECT c.*, u.nome, u.cargo
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
                                                <?= htmlspecialchars($comentario['nome']) ?>
                                                <?php if ($comentario['cargo'] === 'especialista'): ?>
                                                    <span class="badge bg-info">Especialista</span>
                                                <?php elseif ($comentario['cargo'] === 'admin'): ?>
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
                                        <?php if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $comentario['usuario_id']): ?>
                                            <div class="comment-actions mt-2">
                                                <a href="editar_comentario.php?comentario_id=<?= $comentario['id'] ?>&tipo_publicacao=publicacao" class="btn btn-edit btn-sm">Editar</a>
                                                <a href="delete.php?comentario_id=<?= $comentario['id'] ?>&tipo=publicacao_comentario" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este comentário?')">Excluir</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <div class="no-comments">Nenhum comentário ainda.</div>
                            <?php endif; ?>
                        </div>

                        <?php if ($pode_comentar): ?>
                            <div class="comment-form mt-3">
                                <h6>Adicionar Comentário</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="acao" value="adicionar_comentario">
                                    <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                    <textarea name="comentario" rows="3" class="form-control mb-2" required></textarea>
                                    <button type="submit" class="btn btn-primary">Comentar</button>
                                </form>
                            </div>
                        <?php endif; ?>
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

                                if (tipo === 'like') {
                                    likeButton.classList.add('like-active');
                                    dislikeButton.classList.remove('dislike-active');
                                } else if (tipo === 'dislike') {
                                    dislikeButton.classList.add('dislike-active');
                                    likeButton.classList.remove('like-active');
                                }
                            } else {
                                alert('Erro ao processar interação: ' + (data.erro || ''));
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