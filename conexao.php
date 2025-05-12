<?php
$servidor = "localhost";
$usuario = "root";
$senha = ""; // Deixe vazio se não tiver senha
$banco = "aves"; // Substitua pelo nome real do seu banco

// Cria a conexão
$conn = new mysqli($servidor, $usuario, $senha, $banco);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Define o charset para utf8mb4
$conn->set_charset("utf8mb4");
?>