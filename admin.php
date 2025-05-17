<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
session_start();
include 'conexao.php';
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

// Verifica se é admin
if ($_SESSION['cargo'] !== 'admin') {
    if ($_SESSION['cargo'] === 'especialista') {
        header("Location: painel_especialista.php");
    } elseif ($_SESSION['cargo'] === 'user') {
        header("Location: painel_usuario.php");
    } else {
        // Caso o cargo não seja reconhecido, redireciona para uma página padrão
        header("Location: feed_user.php");
    }
    exit;
}

// Alterar cargo
if (isset($_POST['alterar_cargo'])) {
    $novoCargo = $_POST['cargo'];
    $usuarioId = $_POST['usuario_id'];
    $stmt = $conn->prepare("UPDATE usuarios SET cargo = ? WHERE id = ?");
    $stmt->bind_param("si", $novoCargo, $usuarioId);
    $stmt->execute();
}

// Excluir usuário
if (isset($_POST['excluir_usuario'])) {
    $usuarioIdExcluir = $_POST['usuario_id_excluir'];

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("DELETE FROM comentarios WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuarioIdExcluir);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM interacoes WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuarioIdExcluir);
        $stmt->execute();

        $publicacoes_usuario = $conn->prepare("SELECT id, foto FROM publicacoes WHERE usuario_id = ?");
        $publicacoes_usuario->bind_param("i", $usuarioIdExcluir);
        $publicacoes_usuario->execute();
        $resultado_publicacoes = $publicacoes_usuario->get_result();

        while ($pub = $resultado_publicacoes->fetch_assoc()) {
            $stmt = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
            $stmt->bind_param("i", $pub['id']);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
            $stmt->bind_param("i", $pub['id']);
            $stmt->execute();

            if (!empty($pub['foto']) && file_exists($pub['foto'])) {
                unlink($pub['foto']);
            }

            $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
            $stmt->bind_param("i", $pub['id']);
            $stmt->execute();
        }

        $atropelamentos_usuario = $conn->prepare("SELECT id, caminho_foto FROM atropelamentos WHERE usuario_id = ?");
        $atropelamentos_usuario->bind_param("i", $usuarioIdExcluir);
        $atropelamentos_usuario->execute();
        $resultado_atropelamentos = $atropelamentos_usuario->get_result();

        while ($atr = $resultado_atropelamentos->fetch_assoc()) {
            if (!empty($atr['caminho_foto']) && file_exists($atr['caminho_foto'])) {
                unlink($atr['caminho_foto']);
            }

            $stmt = $conn->prepare("DELETE FROM atropelamentos WHERE id = ?");
            $stmt->bind_param("i", $atr['id']);
            $stmt->execute();
        }

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuarioIdExcluir);
        $stmt->execute();

        $conn->commit();
        $_SESSION['mensagem'] = "Usuário excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir usuário: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }

    header("Location: admin.php");
    exit;
}

// Excluir publicação
if (isset($_POST['excluir_publicacao'])) {
    $pubId = $_POST['pub_id'];

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        $busca = $conn->prepare("SELECT foto FROM publicacoes WHERE id = ?");
        $busca->bind_param("i", $pubId);
        $busca->execute();
        $resultado = $busca->get_result();
        if ($foto = $resultado->fetch_assoc()) {
            if (file_exists($foto['foto'])) {
                unlink($foto['foto']);
            }
        }
        $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();
        $conn->commit();
        $_SESSION['mensagem'] = "Publicação excluída com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir publicação: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
    header("Location: admin.php");
    exit;
}

// Excluir atropelamento
if (isset($_POST['excluir_atropelamento'])) {
    $atrId = $_POST['atr_id'];

    try {
        $conn->begin_transaction();
        $busca = $conn->prepare("SELECT caminho_foto FROM atropelamentos WHERE id = ?");
        $busca->bind_param("i", $atrId);
        $busca->execute();
        $resultado = $busca->get_result();
        if ($foto = $resultado->fetch_assoc()) {
            if (!empty($foto['caminho_foto']) && file_exists($foto['caminho_foto'])) {
                unlink($foto['caminho_foto']);
            }
        }
        $stmt = $conn->prepare("DELETE FROM atropelamentos WHERE id = ?");
        $stmt->bind_param("i", $atrId);
        $stmt->execute();
        $conn->commit();
        $_SESSION['mensagem'] = "Caso de atropelamento excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir atropelamento: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "danger";
    }
    header("Location: admin.php");
    exit;
}

// Buscar todos os usuários
$usuarios = $conn->query("SELECT * FROM usuarios");

// Buscar todas as publicações com o nome do usuário
$publicacoes_query = "
    SELECT publicacoes.id, publicacoes.especie, publicacoes.foto, usuarios.nome, publicacoes.data_publicacao, NULL as localizacao, 'publicacao' as tipo
    FROM publicacoes
    JOIN usuarios ON publicacoes.usuario_id = usuarios.id
";

// Buscar todos os atropelamentos com o nome do usuário
$atropelamentos_query = "
    SELECT atropelamentos.id, atropelamentos.especie, atropelamentos.caminho_foto as foto, usuarios.nome, atropelamentos.data_postagem as data_publicacao, atropelamentos.localizacao, 'atropelamento' as tipo
    FROM atropelamentos
    JOIN usuarios ON atropelamentos.usuario_id = usuarios.id
";

// Unir as duas queries
$posts_geral_query = "({$publicacoes_query}) UNION ({$atropelamentos_query}) ORDER BY data_publicacao DESC";
$posts_geral = $conn->query($posts_geral_query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: sans-serif;
        }
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .nav-buttons {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .nav-buttons a {
            flex-grow: 1;
            text-align: center;
        }
        .section-title {
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }
        .table-container {
            margin-bottom: 30px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .table img {
            max-height: 60px;
            border-radius: 4px;
            max-width: 80px;
            height: auto;
        }
        .action-form {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-form button {
            flex-grow: 1;
            margin-bottom: 5px;
        }
        .alert {
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .admin-container {
                margin: 15px;
                padding: 10px;
            }
            .table th, .table td {
                padding: 6px;
                font-size: 0.9em;
            }
            .table img {
                max-height: 40px;
                max-width: 60px;
            }
        }

        @media (max-width: 576px) {
            .nav-buttons a {
                font-size: 0.9em;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 class="text-center mb-4">Painel de Administração</h2>

        <div class="nav-buttons">
            <a href="feed_user.php" class="btn btn-primary">Feed Geral</a>
            <a href="feed_atropelamentos.php" class="btn btn-info">Atropelamentos</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?>" role="alert">
                <?= $_SESSION['mensagem'] ?>
            </div>
            <?php unset($_SESSION['mensagem']); unset($_SESSION['tipo_mensagem']); ?>
        <?php endif; ?>

        <div class="table-container">
            <h4 class="section-title">Gerenciar Usuários</h4>
            <?php include 'tabela_usuarios_admin.php'; ?>
        </div>

        <div class="table-container">
            <h4 class="section-title">Gerenciar Publicações</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Espécie</th>
                            <th>Foto</th>
                            <th>Autor</th>
                            <th>Data</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($post = $posts_geral->fetch_assoc()): ?>
                            <tr>
                                <td><?= $post['id'] ?></td>
                                <td><?= ucfirst($post['tipo']) ?></td>
                                <td><?= $post['especie'] ?></td>
                                <td>
                                    <?php if (!empty($post['foto']) && file_exists($post['foto'])): ?>
                                        <img src="<?= $post['foto'] ?>" alt="foto" class="img-thumbnail">
                                    <?php else: ?>
                                        Sem foto
                                    <?php endif; ?>
                                </td>
                                <td><?= $post['nome'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($post['data_publicacao'])) ?></td>
                                <td>
                                    <div class="action-form">
                                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este item?')">
                                            <input type="hidden" name="<?php if ($post['tipo'] === 'publicacao'): echo 'pub_id'; else: echo 'atr_id'; endif; ?>" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="<?php if ($post['tipo'] === 'publicacao'): echo 'excluir_publicacao'; else: echo 'excluir_atropelamento'; endif; ?>" value="true">
                                            <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>