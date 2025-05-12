<?php
include 'conexao.php';

$senha_comum = '12345';
$hash_valido = password_hash($senha_comum, PASSWORD_BCRYPT);

$conn->query("UPDATE usuarios SET senha = '$hash_valido'");
echo "Todas as senhas foram atualizadas para: $senha_comum";
?>