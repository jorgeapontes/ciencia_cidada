<?php
session_start();
include 'conexao.php'; 

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


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar_comentario') {
    $publicacao_id = $_POST['publicacao_id'] ?? null;
    $comentario_texto = $_POST['comentario'] ?? null;
    $usuario_id = $_SESSION['usuario_id'];
    $cargo_usuario = $_SESSION['cargo'] ?? 'user';
    $tipo_publicacao = 'atropelamento'; 

    
    if (!($cargo_usuario === 'especialista' || $cargo_usuario === 'admin')) {
        $_SESSION['mensagem'] = "Seu cargo não permite adicionar comentários.";
        $_SESSION['tipo_mensagem'] = "warning";
    } elseif ($publicacao_id && $comentario_texto) {
        $stmt_insert_comentario = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario, tipo_publicacao) VALUES (?, ?, ?, NOW(), ?)");
        $stmt_insert_comentario->bind_param("iiss", $publicacao_id, $usuario_id, $comentario_texto, $tipo_publicacao);

        if ($stmt_insert_comentario->execute()) {
            $_SESSION['mensagem'] = "Comentário adicionado com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar comentário: " . $conn->error;
            $_SESSION['tipo_mensagem'] = "danger";
        }
        $stmt_insert_comentario->close();
    } else {
        $_SESSION['mensagem'] = "Dados inválidos para o comentário.";
        $_SESSION['tipo_mensagem'] = "danger";
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
    exit();
}



$painel_voltar = 'painel_usuario.php';
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

$cargo_usuario = $_SESSION['cargo'] ?? 'user';
$pode_interagir = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin' || $cargo_usuario === 'user');
$pode_comentar = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin');

$ordem = $_GET['ordem'] ?? 'DESC';
$ordem_sql = ($ordem === 'ASC') ? 'ASC' : 'DESC';

$filtro = $_GET['filtro'] ?? 'tudo';
$where_filtro = '';

$stmt = $conn->prepare("
            SELECT a.*, u.nome, u.id as usuario_id
            
            FROM atropelamentos a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE 1 $where_filtro
            ORDER BY a.data_postagem $ordem_sql");

$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed de Atropelamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/feed_atropelamento.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .btn-edit {
            background-color:rgb(7, 102, 255); 
            color: white;
            border: none;
        }
        .btn-edit:hover {
            background-color:rgb(0, 97, 224);
        }

        
    
        .order-filter-container {
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; 
        }

        .order-select-container {
            text-align: left;
            margin-right: 1rem; 
        }

        .filter-buttons-container {
            text-align: right;
            display: flex; 
            gap: 0.5rem; 
        }

        .order-select {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
            font-size: 0.8rem;
        }

        .filter-button {
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #6c757d;
            background-color: #6c757d;
            color: white;
            font-size: 0.8rem;
            cursor: pointer;
           
        }

        .filter-button.active {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <nav id="japi-navbar" class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">JapiWiki</a>
            <div class="navbar-nav">
                <a class="nav-link" href="home.html">Home</a>
                <a class="nav-link" href="<?= $painel_voltar ?>">Painel</a>
                <a class="nav-link" href="feed_user.php">Feed</a>
                <a class="nav-link active" href="feed_atropelamentos.php">Atropelamentos</a>
                <a class="nav-link" href="publicar.php">Publicar</a>
                <a class="nav-link" href="logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <div class="feed-container">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>">
                <?= $_SESSION['mensagem'] ?>
            </div>
            <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
        <?php endif; ?>

        <div class="order-filter-container">
            <div class="order-select-container">
                <select class="order-select" onchange="window.location.href='feed_atropelamentos.php?ordem=' + this.value + '&filtro=<?= $filtro ?>'">
                    <option value="DESC" <?= ($ordem === 'DESC') ? 'selected' : '' ?>>Mais Recentes Primeiro</option>
                    <option value="ASC" <?= ($ordem === 'ASC') ? 'selected' : '' ?>>Mais Antigas Primeiro</option>
                </select>
            </div>
            <div class="filter-buttons-container">
                <button class="filter-button <?= ($filtro === 'tudo' ? 'active' : '') ?>" onclick="window.location.href='feed_atropelamentos.php?filtro=tudo&ordem=<?= $ordem ?>'">Tudo</button>
                </div>
        </div>

        <?php while ($atropelamento = $resultado->fetch_assoc()): ?>
            <div class="card">
                <?php
                $nome_arquivo = basename($atropelamento['caminho_foto']);
                $caminho_imagem = "fotos/" . $nome_arquivo;
                ?>

                <?php if (file_exists(__DIR__ . "/" . $caminho_imagem)): ?>
                    <img src="<?= $caminho_imagem ?>" class="card-img-top" alt="<?= htmlspecialchars($atropelamento['titulo'] ?? '') ?>">
                <?php else: ?>
                    <div class="bg-secondary text-white p-5 text-center">
                        Imagem não encontrada
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($atropelamento['titulo'] ?? '') ?></h5>
                    <div class="post-info">
                        <strong>Por:</strong> <?= htmlspecialchars($atropelamento['nome'] ?? 'Desconhecido') ?>
                        <br>
                        <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($atropelamento['data_postagem'] ?? '')) ?>                       
                        <br>
                        <strong>Localização:</strong> <?= htmlspecialchars($atropelamento['local_ocorencia'] ?? '') ?>
                    </div>
                    <p class="card-text"><?= nl2br(htmlspecialchars($atropelamento['descricao'] ?? '')) ?></p>

                   

                    <div class="comentarios-container">
                        <h6>Comentários</h6>
                        <?php
                        $stmt_comentarios = $conn->prepare("
                                                        SELECT c.*, u.nome, u.cargo
                                                        FROM comentarios c
                                                        JOIN usuarios u ON c.usuario_id = u.id
                                                        WHERE c.publicacao_id = ? AND c.tipo_publicacao = 'atropelamento'
                                                        ORDER BY c.data_comentario ASC
                                                     ");
                        $stmt_comentarios->bind_param("i", $atropelamento['id']);
                        $stmt_comentarios->execute();
                        $comentarios = $stmt_comentarios->get_result();

                        if ($comentarios->num_rows > 0):
                            while ($comentario = $comentarios->fetch_assoc()): ?>
                                <div class="comentario visualizacao-apenas">
                                    <div class="comentario-info">
                                        <span class="comentario-autor">
                                            <?= htmlspecialchars($comentario['nome']) ?>
                                            <?= ($comentario['cargo'] === 'especialista') ? '<span class="badge bg-info">Especialista</span>' : '' ?>
                                            <?= ($comentario['cargo'] === 'admin') ? '<span class="badge bg-danger">Admin</span>' : '' ?>
                                            <?= ($comentario['cargo'] === 'user') ? '<span class="badge bg-secondary">Usuário</span>' : '' ?>
                                        </span>
                                        <span>
                                            <?= date('d/m/Y H:i', strtotime($comentario['data_comentario'])) ?>
                                        </span>
                                    </div>
                                    <p class="comentario-text">
                                        <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                                    </p>
                                    <?php if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $comentario['usuario_id']): ?>
                                        <a href="editar_comentario.php?comentario_id=<?= $comentario['id'] ?>&tipo_publicacao=atropelamento" class="btn btn-edit btn-sm mt-2">Editar Comentário</a>
                                        <a href="delete.php?comentario_id=<?= $comentario['id'] ?>&tipo=atropelamento_comentario" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Tem certeza que deseja excluir este comentário?')">Excluir Comentário</a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <div class="alert alert-secondary">Nenhum comentário ainda.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($pode_comentar): ?>
                        <div class="comentario-form">
                            <h6>Adicionar Comentário</h6>
                            <form method="POST" action="">
                                <input type="hidden" name="acao" value="adicionar_comentario">
                                <input type="hidden" name="publicacao_id" value="<?= $atropelamento['id'] ?>">
                                <textarea name="comentario" rows="3" class="form-control" required></textarea>
                                <button type="submit" class="btn btn-primary">Comentar</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($_SESSION['cargo'] === 'admin' || $_SESSION['usuario_id'] === $atropelamento['usuario_id']): ?>
                        <a href="delete.php?id=<?= $atropelamento['id'] ?>&tipo=atropelamento" class="btn btn-danger btn-sm ms-auto"
                           onclick="return confirm('Tem certeza que deseja excluir este registro de atropelamento?')">
                            Excluir
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($resultado->num_rows === 0): ?>
            <div class="alert alert-info">Nenhum registro de atropelamento encontrado.</div>
        <?php endif; ?>
    </div>

   
</body>
</html>