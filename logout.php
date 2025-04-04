<?php
// logout.php
session_start();

// Verifica token CSRF para segurança
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
}

// Limpeza completa da sessão
$_SESSION = [];

// Destrói o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redirecionamento seguro
header("Location: index.php");
exit();
?>