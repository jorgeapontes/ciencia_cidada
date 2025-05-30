<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "aves";


$totalUsuarios = "N/A";
$totalPublicacoes = "N/A";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {

} else {

    $conn->set_charset("utf8");


    $sqlUsuarios = "SELECT COUNT(*) AS total FROM usuarios";
    $resultUsuarios = $conn->query($sqlUsuarios);
    if ($resultUsuarios && $resultUsuarios->num_rows > 0) {
        $row = $resultUsuarios->fetch_assoc();
        $totalUsuarios = $row['total'];
    } else {
        $totalUsuarios = 0;
    }


    $sqlPublicacoesNormais = "SELECT COUNT(*) AS total FROM publicacoes";
    $resultPublicacoesNormais = $conn->query($sqlPublicacoesNormais);
    $countPublicacoesNormais = 0;
    if ($resultPublicacoesNormais && $resultPublicacoesNormais->num_rows > 0) {
        $row = $resultPublicacoesNormais->fetch_assoc();
        $countPublicacoesNormais = (int)$row['total'];
    }


    $sqlPublicacoesAtropelamentos = "SELECT COUNT(*) AS total FROM atropelamentos";
    $resultPublicacoesAtropelamentos = $conn->query($sqlPublicacoesAtropelamentos);
    $countPublicacoesAtropelamentos = 0;
    if ($resultPublicacoesAtropelamentos && $resultPublicacoesAtropelamentos->num_rows > 0) {
        $row = $resultPublicacoesAtropelamentos->fetch_assoc();
        $countPublicacoesAtropelamentos = (int)$row['total'];
    }


    if ($totalUsuarios !== "N/A") {
        $totalPublicacoes = $countPublicacoesNormais + $countPublicacoesAtropelamentos;
    }


    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ciência Cidadã - JapiWiki</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .welcome-text {
            
            text-align: center;
            color: #ddd;
        }

        .welcome-text h1 {
            font-size: 3em;
            color: #4CAF50;
            
        }

        .welcome-text p {
            font-size: 1.15em;
            line-height: 1.8;
            margin-bottom: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .welcome-text .btn {
            margin-top: 25px;
            margin-bottom: 5px;
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .welcome-text .btn:hover {
            background-color: #45a049;
        }

        .stats-section {
            text-align: center;
            padding: 30px 10px;
            background-color: transparent;
            
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            flex-direction: column;
            padding: 25px 25px;
            background-color: transparent;
            border-radius: 10px;
            box-shadow: none;
            min-width: 180px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            cursor: default;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .stat-item h3 {
            font-size: 3.0em;
            color: #ffffff;
            margin-bottom: 5px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        .stat-item p {
            font-size: 1.2em;
            color: #dddddd;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.6);
        }

        .view-publications-btn-container {
            text-align: center;
         
        }
        .view-publications-btn {
            background-color: #2c5d85; 
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        .view-publications-btn:hover {
            background-color: #1e4566; 
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="#" class="logo">JapiWiki</a>
        <div class="nav-links">
            <span class="nav-link" id="verPublicacoes">Ver publicações</span>
            <a href="login.php" class="nav-link">Entrar</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="welcome-text">
            <h1>Bem-vindo ao JapiWiki</h1>
            <p>Uma plataforma colaborativa onde cidadãos e cientistas se unem para compartilhar descobertas e observações sobre a natureza ao nosso redor.</p>
            <p>Participe de um movimento global de compartilhamento de conhecimento científico sobre espécies, acessível a todos.</p>
            <p>Registre suas observações, compartilhe fotos de espécies interessantes e colabore com especialistas.</p>
            <a href="login.php" class="btn">Junte-se a nós</a>
        </div>

        <section class="stats-section">
            <div class="stat-item">
                <h3 id="totalPublicacoes"><?php echo htmlspecialchars($totalPublicacoes); ?></h3>
                <p>Total de Publicações</p>
            </div>
            <div class="stat-item">
                <h3 id="totalUsuarios"><?php echo htmlspecialchars($totalUsuarios); ?></h3>
                <p>Usuários</p>
            </div>
        </section>

        <div class="view-publications-btn-container">
            <a href="feed_user.php" class="view-publications-btn">Ver Publicações</a>
        </div>
    </main>

    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Acesso Restrito</h2>
            <p>Você precisa estar logado para acessar as publicações.</p>
            <p>Por favor, faça login ou cadastre-se para continuar.</p>
            <a href="login.php" class="btn">Entrar</a>
        </div>
    </div>

    <script>
        document.getElementById('verPublicacoes').addEventListener('click', function() {
            const isLoggedIn = false;
            
            if(isLoggedIn) {
                window.location.href = 'feed.php';
            } else {
                document.getElementById('loginModal').style.display = 'block';
            }
        });

        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('loginModal').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('loginModal')) {
                document.getElementById('loginModal').style.display = 'none';
            }
        });
    </script>
</body>
</html>