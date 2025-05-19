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

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $cargo = "admin"; 

    $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $erro = "Este e-mail já está cadastrado!";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        h2 {
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control {
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ced4da;
        }
        .btn-success, .btn-secondary {
            border-radius: 4px;
            padding: 0.75rem 1.5rem;
            font-weight: bold;
        }
        .mt-3 {
            margin-top: 1.5rem;
        }
        .row {
            display: flex;
            justify-content: center;
        }
        .col-md-6 {
            max-width: 500px;
            width: 100%;
        }

        @media (max-width: 576px) {
            .container {
                margin-top: 20px;
                padding-left: 15px;
                padding-right: 15px;
            }
            h2 {
                font-size: 1.5rem;
                margin-bottom: 15px;
            }
            .form-label {
                font-size: 0.9rem;
            }
            .form-control, .btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
            .mt-3 {
                margin-top: 1rem;
            }
        }

        @media (min-width: 577px) and (max-width: 992px) {
            .container {
                margin-top: 25px;
                padding-left: 20px;
                padding-right: 20px;
            }
            h2 {
                font-size: 1.8rem;
            }
            .col-md-6 {
                max-width: 70%;
            }
        }
    </style>
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

        <div class="row">
            <div class="col-md-6">
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
        </div>
    </div>
</body>
</html>