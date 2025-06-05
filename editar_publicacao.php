<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Negado</title>
        <style>
            body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
            h1 { color: #d9534f; margin-bottom: 20px; }
            p { margin-bottom: 15px; }
            .login-link { color: #007bff; text-decoration: none; font-weight: bold; }
            .login-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Acesso Negado</h1>
            <p>Você precisa estar logado para acessar esta página.</p>
            <p><a href='login.php' class='login-link'>Fazer Login</a></p>
        </div>
    </body>
    </html>
    HTML;

    echo $html;
    exit;
}

include 'conexao.php';

$mensagem = "";
$publicacao = null;

// pega o ID da publicação pela URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $publicacao_id = $_GET['id'];

    // Ve se a publicação pertence ao usuário logado
    $stmt = $conn->prepare("SELECT * FROM publicacoes WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $publicacao_id, $_SESSION['usuario_id']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $publicacao = $resultado->fetch_assoc();
    $stmt->close();

    if (!$publicacao) {
        $mensagem = "Você não tem permissão para editar esta publicação ou ela não existe.";
    }
} else {
    $mensagem = "ID da publicação inválido.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_publicacao'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $publicacao_id = $_POST['publicacao_id'];
    $atualizar_foto = false;
    $caminho_foto = $publicacao['caminho_foto']; 

    // checar dnv se a publicação pertence ao usuário 
    $stmt_check = $conn->prepare("SELECT id, caminho_foto FROM publicacoes WHERE id = ? AND usuario_id = ?");
    $stmt_check->bind_param("ii", $publicacao_id, $_SESSION['usuario_id']);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $publicacao_check = $resultado_check->fetch_assoc();
    $stmt_check->close();

    if ($publicacao_check) {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_temp = $_FILES['foto']['tmp_name'];
            $foto_nome = uniqid() . "_" . $_FILES['foto']['name'];
            $pasta = 'fotos/';
            $caminho_nova_foto = $pasta . $foto_nome;

            if (move_uploaded_file($foto_temp, __DIR__ . '/' . $caminho_nova_foto)) {
                if (!empty($publicacao_check['caminho_foto']) && file_exists(__DIR__ . '/' . $publicacao_check['caminho_foto'])) {
                    unlink(__DIR__ . '/' . $publicacao_check['caminho_foto']);
                }
                $caminho_foto = $caminho_nova_foto;
                $atualizar_foto = true;
            } else {
                $mensagem = "Erro ao mover a nova imagem.";
            }
        }

        // Att os dados da publicação 
        $sql_atualizar = "UPDATE publicacoes SET titulo = ?, descricao = ?";
        if ($atualizar_foto) {
            $sql_atualizar .= ", caminho_foto = ?";
        }
        $sql_atualizar .= " WHERE id = ?";

        $stmt_atualizar = $conn->prepare($sql_atualizar);
        if ($atualizar_foto) {
            $stmt_atualizar->bind_param("sssi", $titulo, $descricao, $caminho_foto, $publicacao_id);
        } else {
            $stmt_atualizar->bind_param("ssi", $titulo, $descricao, $publicacao_id);
        }

        if ($stmt_atualizar->execute()) {
            $mensagem = "Publicação atualizada com sucesso!";
            $stmt_reload = $conn->prepare("SELECT * FROM publicacoes WHERE id = ?");
            $stmt_reload->bind_param("i", $publicacao_id);
            $stmt_reload->execute();
            $resultado_reload = $stmt_reload->get_result();
            $publicacao = $resultado_reload->fetch_assoc();
            $stmt_reload->close();
        } else {
            $mensagem = "Erro ao atualizar a publicação.";
        }
        $stmt_atualizar->close();

    } else {
        $mensagem = "Você não tem permissão para editar esta publicação.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Publicação</title>
    <link rel="stylesheet" href="css/editar_publicacao.css">
   
</head>
<body>
    <div class="container">
        <h1>Editar Publicação</h1>

        <?php if ($mensagem): ?>
            <p><?php echo $mensagem; ?></p>
            <?php if ($mensagem === "Publicação atualizada com sucesso!"): ?>
                <a href="feed_user.php"><button>Voltar para o Feed</button></a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($publicacao)): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="publicacao_id" value="<?php echo htmlspecialchars($publicacao['id']); ?>">

                <label for="titulo">Título:</label><br>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($publicacao['titulo']); ?>" required><br><br>

                <label for="descricao">Descrição:</label><br>
                <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($publicacao['descricao']); ?></textarea><br><br>

                <div>
                    <label>Foto atual:</label><br>
                    <?php if (!empty($publicacao['caminho_foto'])): ?>
                        <img src="<?php echo htmlspecialchars($publicacao['caminho_foto']); ?>" alt="Foto atual" class="current-image">
                    <?php else: ?>
                        <p>Nenhuma foto cadastrada.</p>
                    <?php endif; ?>
                </div>

                <label for="foto">Alterar foto (opcional):</label><br>
                <input type="file" id="foto" name="foto" accept="image/*"><br><br>

                <button type="submit" name="atualizar_publicacao">Salvar Alterações</button>
                <a href="feed_user.php"><button type="button">Cancelar</button></a>
            </form>
        <?php endif; ?>

        <?php if (isset($mensagem) && strpos($mensagem, 'permissão') !== false): ?>
            <p><a href="feed_user.php"><button>Voltar para o Feed</button></a></p>
        <?php endif; ?>
    </div>
</body>
</html>