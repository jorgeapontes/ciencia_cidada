<?php
$senha = '12345';
$hash = password_hash($senha, PASSWORD_BCRYPT);
echo "Senha: ".$senha."<br>";
echo "Hash: ".$hash."<br>";
echo "Verificação: ".(password_verify($senha, $hash) ? "OK" : "Falhou");
?>