<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
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

// Verificar se o usuário atual é especialista
$is_especialista = (isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'especialista');

// Processar novo comentário se for enviado por um especialista
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comentar']) && $is_especialista) {
        $publicacao_id = filter_input(INPUT_POST, 'publicacao_id', FILTER_VALIDATE_INT);
        $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($publicacao_id && $comentario) {
            $stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $publicacao_id, $_SESSION['usuario_id'], $comentario);
            $stmt->execute();
            header("Location: feed.php");
            exit;
        }
    }

    // Processar edição de comentário
    if (isset($_POST['editar_comentario']) && $is_especialista) {
        $comentario_id = filter_input(INPUT_POST, 'comentario_id', FILTER_VALIDATE_INT);
        $novo_comentario = filter_input(INPUT_POST, 'novo_comentario', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($comentario_id && $novo_comentario) {
            $stmt = $conn->prepare("UPDATE comentarios SET comentario = ? WHERE id = ?");
            $stmt->bind_param("si", $novo_comentario, $comentario_id);
            $stmt->execute();
            header("Location: feed.php");
            exit;
        }
    }

    // Processar interação (like/dislike)
    if (isset($_POST['interagir']) && $is_especialista) {
        $publicacao_id = filter_input(INPUT_POST, 'publicacao_id', FILTER_VALIDATE_INT);
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($publicacao_id && ($tipo === 'like' || $tipo === 'dislike')) {
            // Verificar se o usuário já interagiu com esta publicação
            $stmt_check = $conn->prepare("SELECT id FROM interacoes WHERE usuario_id = ? AND publicacao_id = ?");
            $stmt_check->bind_param("ii", $_SESSION['usuario_id'], $publicacao_id);
            $stmt_check->execute();
            $resultado_check = $stmt_check->get_result();

            if ($resultado_check->num_rows > 0) {
                // Usuário já interagiu, pode atualizar a interação
                $stmt_update = $conn->prepare("UPDATE interacoes SET tipo = ? WHERE usuario_id = ? AND publicacao_id = ?");
                $stmt_update->bind_param("sii", $tipo, $_SESSION['usuario_id'], $publicacao_id);
                $stmt_update->execute();
            } else {
                // Usuário não interagiu, inserir nova interação
                $stmt_insert = $conn->prepare("INSERT INTO interacoes (usuario_id, publicacao_id, tipo) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iis", $_SESSION['usuario_id'], $publicacao_id, $tipo);
                $stmt_insert->execute();
            }
            header("Location: feed.php");
            exit;
        }
    }
}

// Buscar publicações
$stmt = $conn->prepare("
    SELECT p.*, u.nome, u.id as usuario_id,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'like') AS likes,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'dislike') AS dislikes,
    (SELECT tipo FROM interacoes WHERE publicacao_id = p.id AND usuario_id = ?) AS minha_interacao
    FROM publicacoes p
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.id DESC
");
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
    
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Ciência Cidadã</a>
            <div class="navbar-nav">
                <a class="nav-link" href="home.html">Home</a>
                <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="feed-container">
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
                            <form method="POST">
                                <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                <input type="hidden" name="tipo" value="like">
                                <button type="submit" name="interagir" class="like-button <?= ($is_especialista ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'like' ? 'like-active' : '') ?>" <?= ($is_especialista ? '' : 'disabled') ?>>
                                    <i class="bi bi-hand-thumbs-up"></i> <span class="badge bg-light text-dark"><?= $pub['likes'] ?></span>
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                <input type="hidden" name="tipo" value="dislike">
                                <button type="submit" name="interagir" class="dislike-button <?= ($is_especialista ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'dislike' ? 'dislike-active' : '') ?>" <?= ($is_especialista ? '' : 'disabled') ?>>
                                    <i class="bi bi-hand-thumbs-down"></i> <span class="badge bg-light text-dark"><?= $pub['dislikes'] ?></span>
                                </button>
                            </form>
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
                            SELECT c.*, u.nome, u.cargo, u.id as usuario_id
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
                                <div class="comentario">
                                    <div class="comentario-info">
                                        <span class="comentario-autor">
                                            <?= htmlspecialchars($comentario['nome']) ?>
                                            <?= ($comentario['cargo'] === 'especialista') ? '<span class="badge bg-info">Especialista</span>' : '' ?>
                                        </span>
                                        <span>
                                            <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                            <?php if ($is_especialista || $_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $comentario['usuario_id']): ?>
                                                <button class="btn-edit" onclick="toggleEditForm(<?= $comentario['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="delete.php?comentario_id=<?= $comentario['id'] ?>"
                                                   onclick="return confirm('Tem certeza que deseja excluir este comentário?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <p id="comentario-text-<?= $comentario['id'] ?>">
                                        <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                                    </p>

                                    <div id="edit-form-<?= $comentario['id'] ?>" class="edit-form">
                                        <form method="POST">
                                            <input type="hidden" name="comentario_id" value="<?= $comentario['id'] ?>">
                                            <textarea class="form-control mb-2" name="novo_comentario" rows="2"><?= htmlspecialchars($comentario['comentario']) ?></textarea>
                                            <button type="submit" name="editar_comentario" class="btn btn-primary btn-sm">Salvar</button>
                                            <button type="button" onclick="toggleEditForm(<?= $comentario['id'] ?>)" class="btn btn-secondary btn-sm">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="alert alert-secondary">Nenhum comentário ainda.</div>
                        <?php endif; ?>

                        <?php if ($is_especialista): ?>
                            <form method="POST" class="form-comentario">
                                <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" name="comentario" rows="2"
                                              placeholder="Adicione um comentário como especialista..." required></textarea>
                                </div>
                                <button type="submit" name="comentar" class="btn btn-primary btn-sm">Enviar Comentário</button>
                            </form>
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
        function toggleEditForm(commentId) {
            const textElement = document.getElementById(`comentario-text-${commentId}`);
            const formElement = document.getElementById(`edit-form-${commentId}`);

            if (textElement.style.display === 'none') {
                textElement.style.display = 'block';
                formElement.style.display = 'none';
            } else {
                textElement.style.display = 'none';
                formElement.style.display = 'block';
            }
        }
    </script>
</body>
</html>