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

    try {
        // Inicia transação
        $conn->begin_transaction();

        // 1. Primeiro exclui os comentários relacionados
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();

        // 2. Exclui as interações (likes/dislikes) relacionadas
        $stmt = $conn->prepare("DELETE FROM interacoes WHERE publicacao_id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();

        // 3. Busca a foto para excluir do servidor
        $busca = $conn->prepare("SELECT foto FROM publicacoes WHERE id = ?");
        $busca->bind_param("i", $pubId);
        $busca->execute();
        $resultado = $busca->get_result();

        if ($foto = $resultado->fetch_assoc()) {
            if (file_exists($foto['foto'])) {
                unlink($foto['foto']);
            }
        }

        // 4. Agora exclui a publicação
        $stmt = $conn->prepare("DELETE FROM publicacoes WHERE id = ?");
        $stmt->bind_param("i", $pubId);
        $stmt->execute();

        // Confirma a transação
        $conn->commit();

    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação
        $conn->rollback();
        die("Erro ao excluir publicação: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .table-container {
            margin-bottom: 40px;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table img {
            max-height: 60px;
            border-radius: 4px;
        }
        .action-form {
            display: flex;
            justify-content: center;
        }
        .nav-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 class="text-center mb-4">Painel de Administração</h2>

        <div class="nav-buttons">
            <a href="feed.php" class="btn btn-primary">Feed Geral</a>
            <a href="feed_atropelamentos.php" class="btn btn-info">Atropelamentos</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <div class="table-container">
            <h4 class="section-title">Gerenciar Usuários</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
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
                                <td><?= ucfirst($user['cargo']) ?></td>
                                <td>
                                    <form method="POST" class="action-form">
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
            </div>
        </div>

        <div class="table-container">
            <h4 class="section-title">Gerenciar Publicações</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
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
                                    <img src="<?= $pub['foto'] ?>" alt="foto" class="img-thumbnail">
                                </td>
                                <td><?= $pub['nome'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pub['data_publicacao'])) ?></td>
                                <td>
                                    <form method="POST" class="action-form" onsubmit="return confirm('Tem certeza que deseja excluir esta publicação e todos os dados relacionados?')">
                                        <input type="hidden" name="pub_id" value="<?= $pub['id'] ?>">
                                        <button type="submit" name="excluir_publicacao" class="btn btn-danger btn-sm">Excluir</button>
                                    </form>
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