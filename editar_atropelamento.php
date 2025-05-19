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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem'] = "ID de atropelamento inválido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: painel_usuario.php"); 
    exit;
}

$atropelamento_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM atropelamentos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $atropelamento_id, $_SESSION['usuario_id']);
$stmt->execute();
$resultado = $stmt->get_result();
$atropelamento = $resultado->fetch_assoc();

if (!$atropelamento) {
    $_SESSION['mensagem'] = "Caso de atropelamento não encontrado ou você não tem permissão para editar.";
    $_SESSION['tipo_mensagem'] = "warning";
    header("Location: painel_usuario.php"); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $especie = $_POST["especie"];
    $descricao = $_POST["descricao"];
    $localizacao = $_POST["localizacao"];
    $data_ocorrencia = $_POST["data_ocorrencia"];

    // Processamento do upload da nova foto (opcional)
    $caminho_foto = $atropelamento['caminho_foto']; 
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        if (!empty($caminho_foto) && file_exists($caminho_foto)) {
            unlink($caminho_foto);
        }
        $pasta_destino = "fotos/";
        $nome_arquivo = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $caminho_foto = $pasta_destino . $nome_arquivo;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $caminho_foto);
    }

    $stmt_update = $conn->prepare("UPDATE atropelamentos SET especie = ?, descricao = ?, localizacao = ?, data_ocorrencia = ?, caminho_foto = ? WHERE id = ?");
    $stmt_update->bind_param("sssssi", $especie, $descricao, $localizacao, $data_ocorrencia, $caminho_foto, $atropelamento_id);

    if ($stmt_update->execute()) {
        $_SESSION['mensagem'] = "Caso de atropelamento atualizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
        header("Location: painel_usuario.php"); 
        exit;
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar o caso de atropelamento: " . $stmt_update->error;
        $_SESSION['tipo_mensagem'] = "danger";
    }

    $stmt_update->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Atropelamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/editar_atropelamento.css">
    
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Ciência Cidadã</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link" href="home.html">Home</a>
                    <a class="nav-link" href="painel_usuario.php">Painel</a>
                    <a class="nav-link" href="feed_user.php">Feed</a>
                    <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                    <a class="nav-link" href="publicar.php">Nova Publicação</a>
                    <a class="nav-link" href="logout.php">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Editar Caso de Atropelamento</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="atropelamento_id" value="<?= htmlspecialchars($atropelamento['id']) ?>">
            <div class="mb-3">
                <label for="especie" class="form-label">Espécie</label>
                <input type="text" class="form-control" id="especie" name="especie" value="<?= htmlspecialchars($atropelamento['especie']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($atropelamento['descricao']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="localizacao" class="form-label">Localização</label>
                <input type="text" class="form-control" id="localizacao" name="localizacao" value="<?= htmlspecialchars($atropelamento['localizacao']) ?>">
            </div>
            <div class="mb-3">
                <label for="data_ocorrencia" class="form-label">Data da Ocorrência</label>
                <input type="datetime-local" class="form-control" id="data_ocorrencia" name="data_ocorrencia" value="<?= str_replace(' ', 'T', htmlspecialchars($atropelamento['data_ocorrencia'])) ?>">
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto (deixe em branco para manter a atual)</label>
                <input type="file" class="form-control" id="foto" name="foto">
                <?php if (!empty($atropelamento['caminho_foto']) && file_exists($atropelamento['caminho_foto'])): ?>
                    <img src="<?= htmlspecialchars($atropelamento['caminho_foto']) ?>" alt="Foto atual" class="img-thumbnail">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="painel_usuario.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>