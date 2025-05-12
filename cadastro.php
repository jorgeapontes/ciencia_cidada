<?php
include 'conexao.php';
$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = password_hash($_POST["senha"], PASSWORD_DEFAULT);
    
    // Define cargo padrão como 'user' (não permite selecionar admin no formulário público)
    $cargo = ($_POST["cargo"] == 'especialista') ? 'especialista' : 'user';

    // Verifica email duplicado
    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $erro = "Este email já está cadastrado.";
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, cargo) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $nome, $email, $senha, $cargo);
            if ($stmt->execute()) {
                $sucesso = "Cadastro realizado com sucesso! <a href='login.php'>Faça login</a>";
            } else {
                $erro = "Erro: " . $stmt->error;
            }
        } else {
            $erro = "Erro na preparação da query.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Aves</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h2 class="text-center">Cadastro</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome:</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha:</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                        <div class="mb-3">
                            <label for="cargo" class="form-label">Tipo de Usuário:</label>
                            <select class="form-select" id="cargo" name="cargo" required>
                                <option value="">Selecione...</option>
                                <option value="user">Usuário Comum</option>
                                <option value="especialista">Especialista</option>
                                <!-- Removida opção de admin -->
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Cadastrar</button>
                    </form>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger mt-3"><?= $erro ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success mt-3"><?= $sucesso ?></div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">Voltar para login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>