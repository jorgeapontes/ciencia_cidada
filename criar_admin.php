<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['cargo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $cargo = "admin"; // Força o cargo como admin

    // Verifica se o e-mail já existe
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $erro = "Este e-mail já está cadastrado!";
    } else {
        // Gera o hash da senha
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

        // Insere no banco
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, cargo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $email, $senha_hash, $cargo);

        if ($stmt->execute()) {
            $sucesso = "✅ Administrador criado com sucesso!";
        } else {
            $erro = "❌ Erro ao criar administrador: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Novo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Criar Novo Administrador</h2>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha:</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Criar Admin</button>
            <a href="painel.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>
</body>
</html>