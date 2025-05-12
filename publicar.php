<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $usuario_id = $_SESSION['usuario_id'];

    // Nome correto do campo file
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_temp = $_FILES['foto']['tmp_name'];
        $foto_nome = uniqid() . "_" . $_FILES['foto']['name'];

        $pasta = 'pasta_fotos/';

        // Cria a pasta caso não exista
        if (!is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $caminho = $pasta . $foto_nome;

        if (move_uploaded_file($foto_temp, $caminho)) {
            $stmt = $conn->prepare("INSERT INTO publicacoes (usuario_id, titulo, descricao, caminho_foto) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $usuario_id, $titulo, $descricao, $caminho);
            $stmt->execute();

            $mensagem = "Publicação realizada com sucesso!";
            $stmt->close();
        } else {
            $mensagem = "Erro ao mover a imagem.";
        }
    } else {
        $mensagem = "Nenhuma imagem foi enviada ou ocorreu um erro.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nova Publicação</title>
</head>
<body>
    <h1>Nova Publicação</h1>

    <?php if ($mensagem): ?>
        <p><?php echo $mensagem; ?></p>
        <?php if ($mensagem === "Publicação realizada com sucesso!"): ?>
            <a href="feed.php"><button>Ir para o Feed</button></a>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Título:</label><br>
        <input type="text" name="titulo" required><br><br>

        <label>Descrição:</label><br>
        <textarea name="descricao" required></textarea><br><br>

        <label>Foto da ave:</label><br>
        <input type="file" name="foto" accept="image/*" required><br><br>

        <input type="submit" value="Publicar">
    </form>
</body>
</html>
