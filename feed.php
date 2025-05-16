<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

// Verificar o cargo do usuário para determinar para qual painel redirecionar
$painel_voltar = 'painel_usuario.php'; // Padrão para usuários normais
if (isset($_SESSION['cargo'])) {
    if ($_SESSION['cargo'] === 'admin') {
        $painel_voltar = 'admin.php';
    } elseif ($_SESSION['cargo'] === 'especialista') {
        $painel_voltar = 'painel_especialista.php';
    }
}

// Verificar o cargo do usuário atual
$cargo_usuario = $_SESSION['cargo'] ?? 'user';
$pode_interagir = ($cargo_usuario === 'especialista' || $cargo_usuario === 'admin');

// Processar novo comentário se for enviado por um especialista ou admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comentar']) && $pode_interagir) {
        $publicacao_id = filter_input(INPUT_POST, 'publicacao_id', FILTER_VALIDATE_INT);
        $comentario = filter_input(INPUT_POST, 'comentario', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($publicacao_id && $comentario) {
            $stmt = $conn->prepare("INSERT INTO comentarios (publicacao_id, usuario_id, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $publicacao_id, $_SESSION['usuario_id'], $comentario);}}}