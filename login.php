<?php
session_start();
include 'conexao.php';

$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $stmt = $conn->prepare("SELECT id, nome, senha, cargo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $nome, $senha_hash, $cargo);
        $stmt->fetch();
        if (password_verify($senha, $senha_hash)) {
            $_SESSION["usuario_id"] = $id;
            $_SESSION["nome"] = $nome;
            $_SESSION["cargo"] = $cargo;
            header("Location: painel.php");
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Usuário não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Aves</title>
</head>
<body>
<h2>Login</h2>
<form method="POST">
    Email: <input type="email" name="email" required><br>
    Senha: <input type="password" name="senha" required><br>
    <button type="submit">Entrar</button>
</form>
<p style="color: red;"><?= $erro ?></p>
<a href="cadastro.php">Cadastre-se</a>
</body>
</html>
