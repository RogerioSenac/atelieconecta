<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Exception\Auth\WrongPassword;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    try {
        $auth = $factory->createAuth();
        $signInResult = $auth->signInWithEmailAndPassword($email, $senha);

        $_SESSION['logado'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['uid'] = $signInResult->firebaseUserId();

        header('Location: dashAcessoCli.php');
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Plataforma de Corte e Costura</title>
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css" />
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .error-message {
            display: none;
            background-color: #ffe5e5;
            color: #b30000;
            padding: 12px 16px;
            border-left: 5px solid #b30000;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            position: relative;
            animation: fadeIn 0.5s ease-in-out;
        }

        .error-message i {
            margin-right: 8px;
            color: #b30000;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="interfaceLogin">
        <form class="acesso" method="post" action="loginCli.php" onsubmit="return validateForm()">
            <h2>Login de Acesso</h2>

            <?php if (!empty($msg)): ?>
            <div id="error-box" class="error-message" style="display: block;">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="error-text"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php else: ?>
            <div id="error-box" class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="error-text"></span>
            </div>
            <?php endif; ?>

            <input type="email" name="email" id="email" placeholder="Seu Email" required
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" />

            <input type="password" name="senha" id="senha" placeholder="Senha Acesso" required />

            <div class="btn-enviar">
                <input type="submit" value="Logar" />
            </div>

            <div class="text-center mt-3">
                <a href="./cadLoginCli.php">Não tem uma conta? Cadastre-se</a>
            </div>
            <div class="text-center mt-3">
                <a href="./recuperaSenhaCli.php">Esqueceu a senha?</a>
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

            // Validação de formato de email
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorText.textContent = "Por favor, insira um email válido.";
                errorBox.style.display = "block";
                return false;
            }

            return true;
        }

        // Mensagem via URL (caso usada futuramente)
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
