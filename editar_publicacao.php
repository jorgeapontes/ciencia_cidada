<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

$mensagem = "";

// Obter o ID da publicação da URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $publicacao_id = $_GET['id'];

    // Verificar se a publicação pertence ao usuário logado
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

// Processar a atualização da publicação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_publicacao'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $publicacao_id = $_POST['publicacao_id'];
    $atualizar_foto = false;
    $caminho_foto = $publicacao['caminho_foto']; // Mantém o caminho da foto antiga por padrão

    // Verificar novamente se a publicação pertence ao usuário logado (segurança extra)
    $stmt_check = $conn->prepare("SELECT id, caminho_foto FROM publicacoes WHERE id = ? AND usuario_id = ?");
    $stmt_check->bind_param("ii", $publicacao_id, $_SESSION['usuario_id']);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $publicacao_check = $resultado_check->fetch_assoc();
    $stmt_check->close();

    if ($publicacao_check) {
        // Processar nova foto, se enviada
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_temp = $_FILES['foto']['tmp_name'];
            $foto_nome = uniqid() . "_" . $_FILES['foto']['name'];
            $pasta = 'fotos/';
            $caminho_nova_foto = $pasta . $foto_nome;

            if (move_uploaded_file($foto_temp, __DIR__ . '/' . $caminho_nova_foto)) {
                // Excluir a foto antiga, se existir
                if (!empty($publicacao_check['caminho_foto']) && file_exists(__DIR__ . '/' . $publicacao_check['caminho_foto'])) {
                    unlink(__DIR__ . '/' . $publicacao_check['caminho_foto']);
                }
                $caminho_foto = $caminho_nova_foto;
                $atualizar_foto = true;
            } else {
                $mensagem = "Erro ao mover a nova imagem.";
            }
        }

        // Atualizar os dados da publicação (incluindo o caminho da foto se uma nova foi enviada)
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
            // Recarregar os dados da publicação para exibir a nova imagem, se houver
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
<html>
<head>
    <title>Editar Publicação</title>
    <link rel="stylesheet" href="css/publicar.css">
    <style>
        .current-image {
            max-width: 300px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
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

            <label>Título:</label><br>
            <input type="text" name="titulo" value="<?php echo htmlspecialchars($publicacao['titulo']); ?>" required><br><br>

            <label>Descrição:</label><br>
            <textarea name="descricao" required><?php echo htmlspecialchars($publicacao['descricao']); ?></textarea><br><br>

            <div>
                <label>Foto atual:</label><br>
                <?php if (!empty($publicacao['caminho_foto'])): ?>
                    <img src="<?php echo htmlspecialchars($publicacao['caminho_foto']); ?>" alt="Foto atual" class="current-image">
                <?php else: ?>
                    <p>Nenhuma foto cadastrada.</p>
                <?php endif; ?>
            </div>

            <label>Alterar foto (opcional):</label><br>
            <input type="file" name="foto" accept="image/*"><br><br>

            <button type="submit" name="atualizar_publicacao">Salvar Alterações</button>
            <a href="feed_user.php"><button type="button">Cancelar</button></a>
        </form>
    <?php endif; ?>

    <?php if (isset($mensagem) && strpos($mensagem, 'permissão') !== false): ?>
        <p><a href="feed_user.php"><button>Voltar para o Feed</button></a></p>
    <?php endif; ?>
</body>
</html>