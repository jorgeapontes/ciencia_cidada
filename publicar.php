<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $especie = $_POST["especie"];
    $usuario_id = $_SESSION["usuario_id"];

    // Verifica se foi enviada uma foto
    if ($_FILES["foto"]["error"] == 0) {
        $nome_foto = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $caminho = "fotos/" . $nome_foto;
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $caminho)) {
            $stmt = $conn->prepare("INSERT INTO publicacoes (especie, foto, usuario_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $especie, $caminho, $usuario_id);
            if ($stmt->execute()) {
                $mensagem = "Foto publicada com sucesso!";
            } else {
                $mensagem = "Erro ao salvar no banco.";
            }
        } else {
            $mensagem = "Erro ao enviar foto.";
        }
    } else {
        $mensagem = "Envie uma foto válida.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Publicar Foto</title>
</head>
<body>
<h2>Nova Publicação</h2>
<form method="POST" enctype="multipart/form-data">
    Espécie: <input type="text" name="especie" required><br>
    Foto: <input type="file" name="foto" accept="image/*" required><br>
    <button type="submit">Publicar</button>
</form>
<p><?= $mensagem ?></p>
<a href="painel.php">← Voltar</a>
<script>
    console.log ("<?php echo $_FILES?>")
</script>
</body>
</html>
