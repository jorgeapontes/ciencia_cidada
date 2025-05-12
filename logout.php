<?php
// Inicia a sessão (se já não estiver iniciada)
session_start();

// Destroi todas as variáveis de sessão
$_SESSION = array();

// Se desejar destruir o cookie de sessão também
// Nota: Isso destruirá a sessão, e não apenas os dados da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login (ou outra página de sua escolha)
header("Location: login.php");
exit();
?>