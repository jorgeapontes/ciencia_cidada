<?php
include 'conexao.php'; // ou o nome do seu arquivo de conexão
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feed de Publicações</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Ajusta o tamanho das imagens */
        img {
            max-width: 100%;  /* Impede que as imagens ultrapassem o tamanho da tela */
            height: auto;     /* Mantém a proporção da imagem */
            display: block;   /* Remove espaços indesejados abaixo da imagem */  
            width: 500px;
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
        $imagem = !empty($post['foto']) ? $post['foto'] : $post['caminho_foto'];
        echo "<div class='post'>";
        echo "<strong>{$post['especie']}</strong><br>";
        echo "<em>por {$post['nome']} em {$post['data_publicacao']}</em><br><br>";
        echo "<img src='{$imagem}' alt='Foto de {$post['especie']}'><br>";
        echo "</div>";
    }
} else {
    echo "Nenhuma publicação ainda.";
}
?>

</body>
</html>
