<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

$factory = (new Factory())
->withServiceAccount(__DIR__.'/assets/config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    try {
        $auth = $factory->createAuth();
        $auth->sendPasswordResetLink($email); // Correção aqui
        $msg = "Um link para redefinição de senha foi enviado para o seu email.";
    } catch (AuthException | FirebaseException $e) {
        $msg = "Erro ao enviar email de redefinição de senha. Verifique se o email está correto.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="interface">
        <form class="acesso" method="post" action="recuperaSenhaProf.php">
            <h2>Recuperação de Senha</h2>
            
            <?php if (!empty($msg)) { ?>
                <div class="message"> <?php echo $msg; ?> </div>
            <?php } ?>
            
            <label for="email">Digite seu email cadastrado:</label>
            <input type="email" name="email" id="email" placeholder="Seu Email" required>
            
            <div class="btn-enviar">
                <input type="submit" value="Enviar Link de Recuperação">
            </div>
            
            <a href="loginProf.php" class="btn btn-secondary">Voltar ao Login</a>
        </form>
    </div>
</body>
</html>
