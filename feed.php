<?php
<<<<<<< HEAD
include 'conexao.php'; // ou o nome do seu arquivo de conexão
=======
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';
>>>>>>> eb2d9fe5768b03a0a46ef6510797ea07ad4e1121
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feed de Publicações</title>
<<<<<<< HEAD
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
=======
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .post {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            width: 500px;
            margin: 10px 0;
        }
        .descricao {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Feed de Publicações</h2>
    <a href="painel.php" class="btn btn-secondary">← Voltar para o painel</a>
    <a href="publicar.php" class="btn btn-primary">Nova Publicação</a>
    <a href="logout.php" class="btn btn-danger">Sair</a>
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
            echo "<h3>{$post['especie']}</h3>";
            echo "<p><em>por {$post['nome']} em {$post['data_publicacao']}</em></p>";
            echo "<img src='{$imagem}' alt='Foto de {$post['especie']}' class='img-fluid'>";
            
            // Adicionando a descrição (se existir)
            if (!empty($post['descricao'])) {
                echo "<div class='descricao'><strong>Descrição:</strong> {$post['descricao']}</div>";
            }
            
            // Botão de deletar
            if ($_SESSION['usuario_id'] == $post['usuario_id'] || $_SESSION['cargo'] === 'admin') {
                echo "<a href='delete.php?id={$post['id']}' class='btn btn-danger mt-2' 
                      onclick='return confirm(\"Tem certeza que deseja excluir esta publicação?\")'>Excluir</a>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Nenhuma publicação ainda.</div>";
    }
    ?>
</div>
</body>
</html>
>>>>>>> eb2d9fe5768b03a0a46ef6510797ea07ad4e1121
