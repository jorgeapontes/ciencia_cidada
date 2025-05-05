<?php
include 'conexao.php';
$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $nome, $email, $senha);
        if ($stmt->execute()) {
            $sucesso = "Cadastro realizado com sucesso! <a href='login.php'>Faça login</a>";
        } else {
            $erro = "Erro: " . $stmt->error;
        }
    } else {
        $erro = "Erro na preparação da query.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Aves</title>
</head>
<body>
<h2>Cadastro</h2>
<form method="POST">
    Nome: <input type="text" name="nome" required><br>
    Email: <input type="email" name="email" required><br>
    Senha: <input type="password" name="senha" required><br>
    <button type="submit">Cadastrar</button>
</form>
<p style="color: red;"><?= $erro ?></p>
<p style="color: green;"><?= $sucesso ?></p>
<a href="login.php">Voltar para login</a>
</body>
</html>
