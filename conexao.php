<?php
$servidor = "localhost";
$usuario = "root";
$senha = ""; 
$banco = "aves"; 

$conn = new mysqli($servidor, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>