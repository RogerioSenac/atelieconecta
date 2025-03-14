<?php
session_start();

if (!isset($_SESSION['logado'])) {
    header("Location: perfil.php");
    exit;
}

echo "<h3>Usuário logado: " . $_SESSION['email'] . "</h3>";
?>
