<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $html = <<<HTML
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
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

// Verificar o cargo do usuário para determinar para qual painel redirecionar
$painel_voltar = 'painel_usuario.php'; // Padrão para usuários normais
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

// Processamento do formulário de publicação
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST["titulo"];
    $descricao = $_POST["descricao"];
    $atropelamento = $_POST["atropelamento"]; // Recebe '1' ou '0'
    $categoria = $_POST["categoria"] ?? null; // Nova variável para a categoria

    // Processamento do upload da foto
    $caminho_foto = '';
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $pasta_destino = "fotos/";
        $nome_arquivo = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $caminho_foto = $pasta_destino . $nome_arquivo;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $caminho_foto);
    }

    if ($atropelamento == '1') {
        // Preparar variáveis para atropelamento
        $localizacao = $_POST['localizacao'] ?? '';
        $especie = $_POST['especie'] ?? '';
        $data_ocorrencia = $_POST['data_ocorrencia'] ?? null;

        // Inserir na tabela de atropelamentos
        $stmt = $conn->prepare("INSERT INTO atropelamentos (usuario_id, data_ocorrencia, localizacao, especie, descricao, caminho_foto, data_postagem, categoria) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("issssss", $_SESSION['usuario_id'], $data_ocorrencia, $localizacao, $especie, $descricao, $caminho_foto, $categoria);
        $tabela_destino = 'feed_atropelamentos.php';
    } else {
        // Preparar variáveis para publicação normal
        $caminho_foto_value = $caminho_foto ?? '';

        // Inserir na tabela de publicações (feed geral)
        $stmt = $conn->prepare("INSERT INTO publicacoes (usuario_id, titulo, descricao, caminho_foto, data_publicacao, atropelamento, categoria) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param("isssis", $_SESSION['usuario_id'], $titulo, $descricao, $caminho_foto_value, $atropelamento, $categoria);
        $tabela_destino = 'feed_user.php';
    }

    if ($stmt->execute()) {
        header("Location: " . $tabela_destino);
        exit;
    } else {
        echo "Erro ao publicar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav id="japi-navbar" class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">JapiWiki</a>
            <div class="navbar-nav">
                <a class="nav-link" href="home.html">Home</a>
                <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                <a class="nav-link" href="feed_user.php">Feed</a>
                <a class="nav-link" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link active" href="publicar.php">Publicar</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Nova Publicação</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" class="form-control" id="foto" name="foto">
            </div>
            <div class="mb-3">
                <label class="form-label">Este é um caso de atropelamento?</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="atropelamento" id="atropelamento_sim" value="1" required>
                    <label class="form-check-label" for="atropelamento_sim">Sim</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="atropelamento" id="atropelamento_nao" value="0" required checked>
                    <label class="form-check-label" for="atropelamento_nao">Não</label>
                </div>
            </div>
            <div id="campos-atropelamento" style="display: none;">
                <div class="mb-3">
                    <label for="localizacao" class="form-label">Localização (opcional)</label>
                    <input type="text" class="form-control" id="localizacao" name="localizacao">
                </div>
                <div class="mb-3">
                    <label for="especie" class="form-label">Espécie (opcional)</label>
                    <input type="text" class="form-control" id="especie" name="especie">
                </div>
                <div class="mb-3">
                    <label for="data_ocorrencia" class="form-label">Data e Hora da Ocorrência</label>
                    <input type="datetime-local" class="form-control" id="data_ocorrencia" name="data_ocorrencia">
                </div>
            </div>
            <div class="mb-3" id="campos-categoria">
                <label class="form-label">Categoria da Publicação</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="categoria" id="categoria_animal" value="animal" required>
                    <label class="form-check-label" for="categoria_animal">Animal</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="categoria" id="categoria_planta" value="planta" required>
                    <label class="form-check-label" for="categoria_planta">Planta</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Publicar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar campos extras quando selecionar atropelamento
        document.querySelectorAll('input[name="atropelamento"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const camposAtropelamento = document.getElementById('campos-atropelamento');
                const camposEspecieAtropelamento = document.getElementById('campos-especie-atropelamento');

                if (this.value === '1') {
                    camposAtropelamento.style.display = 'block';
                    camposEspecieAtropelamento.style.display = 'block';
                } else {
                    camposAtropelamento.style.display = 'none';
                    camposEspecieAtropelamento.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>