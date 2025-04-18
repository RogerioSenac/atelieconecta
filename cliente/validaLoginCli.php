<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

if (isset($_POST['email']) && isset($_POST['senha'])) {
    $email = htmlspecialchars($_POST['email']);
    $senha = htmlspecialchars($_POST['senha']);

    $factory = (new Factory)
    ->withServiceAccount('../config/chave.json')
        ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

    try {
        $auth = $factory->createAuth();
        $usuarioAuth = $auth->signInWithEmailAndPassword($email, $senha);

        $_SESSION['logado'] = true;
        $userData = $usuarioAuth->data();
        $_SESSION['email'] = $userData['email'];
        $_SESSION['userId'] = $userData['localId'];

        header("Location: DashAcessoCli.php");
        exit;
    } catch (Exception $e) {
        error_log($e->getMessage()); // Registrar erro para debug
        $msg = "Usuário ou senha incorretos!";
        header("Location: loginCli.php?erro=" . urlencode($msg));
        exit;
    }
}
?>
