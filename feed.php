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
        echo "<strong>{$post['especie']}</strong><br>";
        echo "<em>por {$post['nome']} em {$post['data_publicacao']}</em><br><br>";
        echo "<img src='{$post['foto']}' alt='Foto de {$post['especie']}'><br>";
        echo "</div>";
    }
} else {
    echo "Nenhuma publicação ainda.";
}
?>
</body>
</html>
