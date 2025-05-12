<?php
$senha = '12345';
$hash = password_hash($senha, PASSWORD_BCRYPT);
echo "Hash correto para '12345':<br><strong>$hash</strong>";
?>