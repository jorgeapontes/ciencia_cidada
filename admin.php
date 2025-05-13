<?php
session_start();
include 'conexao.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'admin') {
    header("Location: painel.php");
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

// Excluir publicação
if (isset($_POST['excluir_publicacao'])) {
    $pubId = $_POST['pub_id'];
    // Primeiro exclui a imagem do servidor
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
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Painel de Administração</h2>
    <div class="mb-4">
        <a href="feed.php" class="btn btn-primary">Feed</a>
        <a href="delete.php" class="btn btn-danger">Logout</a>
    </div>

    <!-- Usuários -->
    <h4>Gerenciar Usuários</h4>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $usuarios = $conn->query("SELECT * FROM usuarios");
            while ($user = $usuarios->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['nome'] ?></td>
                    <td><?= $user['email'] ?></td>
                    <td><?= $user['cargo'] ?></td>
                    <td>
                        <form method="POST" class="d-flex">
                            <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                            <select name="cargo" class="form-select form-select-sm me-2" style="width: auto;">
                                <option value="user" <?= $user['cargo'] === 'user' ? 'selected' : '' ?>>Usuário</option>
                                <option value="especialista" <?= $user['cargo'] === 'especialista' ? 'selected' : '' ?>>Especialista</option>
                                <option value="admin" <?= $user['cargo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" name="alterar_cargo" class="btn btn-primary btn-sm">Alterar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Publicações -->
    <h4 class="mt-5">Gerenciar Publicações</h4>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Espécie</th>
                <th>Foto</th>
                <th>Autor</th>
                <th>Data</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $publicacoes = $conn->query("
                SELECT publicacoes.*, usuarios.nome FROM publicacoes 
                JOIN usuarios ON publicacoes.usuario_id = usuarios.id 
                ORDER BY publicacoes.data_publicacao DESC
            ");
            while ($pub = $publicacoes->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $pub['id'] ?></td>
                    <td><?= $pub['especie'] ?></td>
                    <td>
                        <img src="<?= $pub['foto'] ?>" alt="foto" width="60">
                    </td>
                    <td><?= $pub['nome'] ?></td>
                    <td><?= $pub['data_publicacao'] ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                            <input type="hidden" name="pub_id" value="<?= $pub['id'] ?>">
                            <button type="submit" name="excluir_publicacao" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html><?php
session_start();
include 'conexao.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'admin') {
    header("Location: painel.php");
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

// Excluir publicação
if (isset($_POST['excluir_publicacao'])) {
    $pubId = $_POST['pub_id'];
    // Primeiro exclui a imagem do servidor
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
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
   
</body>
</html>