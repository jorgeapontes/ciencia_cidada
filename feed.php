<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

// Buscar publicações com informações do usuário
$stmt = $conn->prepare("
    SELECT p.*, u.nome
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
        body {
            background-color: #f4f4f4;
        }
        .feed-container {
            max-width: 800px;
            margin: 40px auto;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .descricao {
            white-space: pre-wrap;
        }
        .topo {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-img-top {
            max-height: 500px;
            object-fit: cover;
        }
        .post-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #6c757d;
        }
        .post-info strong {
            margin-right: 5px;
            color: #343a40;
        }
        .card-actions {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container feed-container">
        <div class="topo mb-4">
            <h2>Feed de Publicações</h2>
            <a href="painel_usuario.php" class="btn btn-secondary">Voltar para o painel</a>
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
                        <a href="delete.php?id=<?= $pub['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Tem certeza que deseja excluir esta publicação?')">
                            Excluir
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($resultado->num_rows === 0): ?>
            <div class="alert alert-info">Nenhuma publicação encontrada.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>