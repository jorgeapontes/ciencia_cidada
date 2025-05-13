<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

// Verificar o cargo do usuário para determinar para qual painel redirecionar
$painel_voltar = 'painel_usuario.php'; // Padrão para usuários normais
if (isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'admin') {
    $painel_voltar = 'admin.php';
}

// Verificar se o usuário atual é especialista
$is_especialista = (isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'especialista');

// Processar novo comentário se for enviado por um especialista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentar']) && $is_especialista) {
    $publicacao_id = filter_input(INPUT_POST, 'publicacao_id', FILTER_VALIDATE_INT);
    $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if ($publicacao_id && $comentario) {
        // Verifique se as colunas existem antes de inserir
$stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
if (!$stmt) {
    die("Erro ao preparar a query: " . $conn->error);
}
        $stmt->bind_param("iis", $publicacao_id, $_SESSION['usuario_id'], $comentario);
        $stmt->execute();
        
        // Redirecionar para evitar reenvio do formulário
        header("Location: feed.php");
        exit;
    }
}

// Buscar publicações com informações do usuário, contagem de likes/dislikes e comentários
$stmt = $conn->prepare("
    SELECT p.*, u.nome,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'like') AS likes,
    (SELECT COUNT(*) FROM interacoes WHERE publicacao_id = p.id AND tipo = 'dislike') AS dislikes
    FROM publicacoes p
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.id DESC
");
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; }
        .feed-container { max-width: 800px; margin: 40px auto; }
        .card { margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .descricao { white-space: pre-wrap; }
        .topo { display: flex; justify-content: space-between; align-items: center; }
        .card-img-top { max-height: 500px; object-fit: cover; }
        .post-info { display: flex; align-items: center; margin-bottom: 10px; font-size: 0.9em; color: #6c757d; }
        .post-info strong { margin-right: 5px; color: #343a40; }
        .card-actions { display: flex; gap: 10px; margin-top: 10px; align-items: center; }
        .btn-like { background-color: #28a745; color: white; }
        .btn-dislike { background-color: #dc3545; color: white; }
        .disabled-btn { opacity: 0.5; cursor: not-allowed; }
        .btn-group { margin-right: 10px; }
        .badge { margin-left: 5px; }
        .comentarios-container { margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        .comentario { padding: 10px; background-color: #f8f9fa; border-radius: 5px; margin-bottom: 10px; }
        .comentario-info { font-size: 0.8em; color: #6c757d; margin-bottom: 5px; }
        .form-comentario { margin-top: 15px; }
        .comentario-autor { font-weight: bold; color: #495057; }
    </style>
</head>
<body>
    <div class="container feed-container">
        <div class="topo mb-4">
            <h2>Feed de Publicações</h2>
            <a href="<?= $painel_voltar ?>" class="btn btn-secondary">Voltar para o painel</a>
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
                        <strong>Em:</strong> <?= date('d/m/Y H:i', strtotime($pub['data_publicacao'] ?? '')) ?>
                    </div>
                    <p class="card-text descricao"><?= nl2br(htmlspecialchars($pub['descricao'] ?? '')) ?></p>

                    <div class="card-actions">
                        <?php if ($is_especialista): ?>
                            <div class="btn-group" role="group">
                                <button class="btn btn-like" onclick="interagir(<?= $pub['id'] ?>, 'like')">
                                    Like <span class="badge bg-light text-dark"><?= $pub['likes'] ?></span>
                                </button>
                                <button class="btn btn-dislike" onclick="interagir(<?= $pub['id'] ?>, 'dislike')">
                                    Dislike <span class="badge bg-light text-dark"><?= $pub['dislikes'] ?></span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="btn-group" role="group">
                                <button class="btn btn-like disabled-btn" disabled>
                                    Like <span class="badge bg-light text-dark"><?= $pub['likes'] ?></span>
                                </button>
                                <button class="btn btn-dislike disabled-btn" disabled>
                                    Dislike <span class="badge bg-light text-dark"><?= $pub['dislikes'] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <a href="delete.php?id=<?= $pub['id'] ?>" class="btn btn-danger btn-sm ms-auto"
                           onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">
                            Excluir
                        </a>
                    </div>

                    <!-- Seção de Comentários -->
                    <div class="comentarios-container">
                        <h6>Comentários</h6>
                        
                        <?php
                        // Buscar comentários para esta publicação
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
                                <div class="comentario">
                                    <div class="comentario-info">
                                        <span class="comentario-autor">
                                            <?= htmlspecialchars($comentario['nome']) ?>
                                            <?= ($comentario['cargo'] === 'especialista') ? '<span class="badge bg-info">Especialista</span>' : '' ?>
                                        </span>
                                        em <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="alert alert-secondary py-2">Nenhum comentário ainda.</div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function interagir(publicacaoId, tipo) {
            fetch('interacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `publicacao_id=${publicacaoId}&tipo=${tipo}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualiza os contadores
                    document.querySelectorAll(`button[onclick*="interagir(${publicacaoId}, 'like'"] span.badge`).forEach(span => {
                        span.textContent = data.likes;
                    });
                    document.querySelectorAll(`button[onclick*="interagir(${publicacaoId}, 'dislike'"] span.badge`).forEach(span => {
                        span.textContent = data.dislikes;
                    });
                } else {
                    alert(data.erro || 'Erro ao processar interação');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao conectar com o servidor');
            });
        }
    </script>
</body>
</html>