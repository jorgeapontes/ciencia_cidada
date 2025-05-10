<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Feed de Aves</title>
    <style>
        img {
            max-width: 300px;
            height: auto;
        }
        .post {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<h2>Feed de Publicações</h2>
<a href="painel.php">← Voltar para o painel</a> | 
<a href="publicar.php">Nova Publicação</a> | 
<a href="logout.php">Sair</a>
<hr>

<?php
$sql = "SELECT publicacoes.*, usuarios.nome 
        FROM publicacoes 
        JOIN usuarios ON publicacoes.usuario_id = usuarios.id 
        ORDER BY data_publicacao DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($post = $result->fetch_assoc()) {
        echo "<div class='post'>";
        echo "<strong>".htmlspecialchars($post['especific'])."</strong><br>";
        echo "<strong>".htmlspecialchars($post['noem'])." - ".htmlspecialchars($post['data_publicacao'])."</strong><br>";
        echo "<img src='posts_fotos/".htmlspecialchars($post['foto'])."' alt='Foto de ".htmlspecialchars($post['especific'])."'>";
        echo "</div>";
    }
} else {
    echo "nenhuma publicação ainda.";
}
</body>
</html>
