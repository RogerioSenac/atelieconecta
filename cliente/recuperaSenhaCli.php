<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    try {
        $auth = $factory->createAuth();
        $auth->sendPasswordResetLink($email);
        $msg = "Um link para redefinição de senha foi enviado para o seu email.";
        $msgType = "success";
    } catch (AuthException | FirebaseException $e) {
        $msg = "Erro ao enviar email de redefinição de senha. Verifique se o email está correto.";
        $msgType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha | Ateliê Conecta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/stylesSenahas.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">

</head>
<body>
    <div class="password-recovery-container">
        <div class="logo">
            <img src="../assets/img/new_logo3.png" alt="Ateliê Conecta">
        </div>
        
        <h2>Recuperação de Senha</h2>
        
        <?php if (!empty($msg)) { ?>
            <div class="message <?php echo $msgType; ?>">
                <i class="fas <?php echo $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php } ?>
        
        <div class="illustration">
            <img src="https://cdn-icons-png.flaticon.com/512/6195/6195699.png" alt="Esqueci minha senha">
        </div>
        
        <form method="post" action="recuperaSenhaCli.php">
            <div class="form-group">
                <label for="email">Email cadastrado</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="digite.seu@email.com" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Enviar Link de Recuperação
            </button>
            
            <a href="loginCli.php" class="back-to-login">
                <i class="fas fa-arrow-left"></i> Voltar ao Login
            </a>
        </form>
    </div>

    <script>
        // Adiciona foco automático no campo de email
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>