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

// Verificar o cargo do usuário atual
$cargo_usuario = $_SESSION['cargo'] ?? 'user';
$pode_interagir = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin');

// Processar interação (like/dislike)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interagir']) && $pode_interagir) {
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
                                <button type="submit" name="interagir" class="like-button <?= ($pode_interagir ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'like' ? 'like-active' : '') ?>" <?= ($pode_interagir ? '' : 'disabled') ?>>
                                    <i class="bi bi-hand-thumbs-up"></i> <span class="badge bg-light text-dark"><?= $pub['likes'] ?></span>
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="publicacao_id" value="<?= $pub['id'] ?>">
                                <input type="hidden" name="tipo" value="dislike">
                                <button type="submit" name="interagir" class="dislike-button <?= ($pode_interagir ? '' : 'disabled-interact') ?> <?= ($pub['minha_interacao'] === 'dislike' ? 'dislike-active' : '') ?>" <?= ($pode_interagir ? '' : 'disabled') ?>>
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
</body>
</html>