<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Exception\Auth\WrongPassword;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$auth = $factory->createAuth();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    try {
        // Verifica se o email está cadastrado
        $user = $auth->getUserByEmail($email);
        
        // Tenta fazer login
        $signInResult = $auth->signInWithEmailAndPassword($email, $senha);
        
        // Login bem-sucedido
        $_SESSION['logado'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['uid'] = $signInResult->firebaseUserId();

        header('Location: dashAcessoProf.php');
        exit();
        
    } catch (UserNotFound $e) {
        $msg = "Usuário não cadastrado. Clique em cadastrar-se";
    } catch (WrongPassword $e) {
        $msg = "Senha incorreta. Tente novamente ou recupere sua senha";
    } catch (FailedToVerifyToken $e) {
        $msg = "Erro na autenticação. Verifique o perfil de acesso e tente novamente";
    } catch (Exception $e) {
        $msg = "Ocorreu um erro. Por favor, tente novamente";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Corte e Costura</title>
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="interfaceLogin">
        <form class="acesso" method="post" action="loginProf.php" onsubmit="return validateForm()">
            <h2>Login de Acesso</h2>
            
            <?php if (!empty($msg)): ?>
            <div id="error-box" class="error-message" style="display: block;">
                <i class="fas fa-exclamation-circle"></i> 
                <span id="error-text"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php else: ?>
            <div id="error-box" class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
                <span id="error-text"></span>
            </div>
            <?php endif; ?>

            <input type="email" name="email" id="email" placeholder="Seu Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

            <input type="password" name="senha" id="senha" placeholder="Senha Acesso" required>

            <div class="btn-enviar">
                <input type="submit" value="Logar">
            </div>

            <div class="text-center mt-3">
                <a href="./cadLoginProf.php">Não tem uma conta? Cadastre-se</a>
            </div>
            <div class="text-center mt-3">
                <a href="./recuperaSenhaProf.php">Esqueceu a senha?</a>
            </div>
            <a href="../index.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>

    <script>
        // Validação do formulário
        function validateForm() {
            const email = document.getElementById("email").value.trim();
            const senha = document.getElementById("senha").value.trim();
            const errorBox = document.getElementById("error-box");
            const errorText = document.getElementById("error-text");

            if (!email || !senha) {
                errorText.textContent = "Por favor, preencha todos os campos.";
                errorBox.style.display = "block";
                return false;
            }

            // Validação simples de email
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorText.textContent = "Por favor, insira um email válido.";
                errorBox.style.display = "block";
                return false;
            }

            return true;
        }

        // Mostra mensagem de erro se existir na URL
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.has("erro")) {
                const errorText = document.getElementById("error-text");
                const errorBox = document.getElementById("error-box");
                
                errorText.textContent = decodeURIComponent(params.get("erro"));
                errorBox.style.display = "block";
            }
        });
    </script>
</body>
</html>