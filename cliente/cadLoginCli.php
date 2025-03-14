<?php
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

$factory = (new Factory())
    ->withServiceAccount('../chave.json')
    ->withDatabaseUri('https://pi-a24-default-rtdb.firebaseio.com/');

$msg = "";

// Executa quando o formulário for submetido
if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmaSenha = $_POST['confirma_senha'];

    if ($senha !== $confirmaSenha) {
        $msg = "As senhas não coincidem!";
    } else {
        try {
            $auth = $factory->createAuth();
            $newUser = $auth->createUserWithEmailAndPassword($email, $senha);
            header('Location: cadCli.php');
            exit();
        } catch (Exception $e) {
            $msg = "Erro ao cadastrar usuário: " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .error-message {
            color: red;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            width: 100%;
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="box">
        <div class="formulario">
            <h2>Cadastro de Acesso</h2>

            <?php if (!empty($msg)) : ?>
                <p class="error-message"><?php echo $msg; ?></p>
            <?php endif; ?>

            <form method="post" onsubmit="return validarSenha()">
                <div class="inputbox">
                    <input type="email" name="email" id="email" placeholder="Informe seu Email" required>
                </div>

                <div class="inputbox password-container">
                    <input type="password" name="senha" id="senha" placeholder="Informe sua senha de acesso" required>
                    <i class="fa fa-eye toggle-password" onclick="toggleSenha('senha')"></i>
                </div>

                <div class="inputbox password-container">
                    <input type="password" name="confirma_senha" id="confirma_senha" placeholder="Confirme sua senha" required>
                    <i class="fa fa-eye toggle-password" onclick="toggleSenha('confirma_senha')"></i>
                </div>

                <input type="submit" value="Enviar" class="sub">
                <a href="./index.php" class="back">Voltar</a>
            </form>
        </div>
    </div>

    <script>
        function toggleSenha(id) {
            let input = document.getElementById(id);
            let icon = input.nextElementSibling;
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        function validarSenha() {
            let senha = document.getElementById("senha").value;
            let confirmaSenha = document.getElementById("confirma_senha").value;

            if (senha !== confirmaSenha) {
                alert("As senhas não coincidem!");
                return false;
            }
            return true;
        }
    </script>

</body>

</html>
