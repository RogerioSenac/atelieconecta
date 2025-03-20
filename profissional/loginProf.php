<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    try {
        $auth = $factory->createAuth();
        $signInResult = $auth->signInWithEmailAndPassword($email, $senha);

        // Armazena o e-mail na sessão
        $_SESSION['logado'] = true;
        $_SESSION['email'] = $email;

        // Redireciona para o painel de acesso
        header('Location: DashAcessoProf.php');
        exit();
    } catch (Exception $e) {
        $msg = "E-mail ou senha incorretos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Corte e Costura</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="interface">
        <form class="acesso" method="post" action="validaLoginProf.php" onsubmit="return validateForm()">
            <h2>Login de Acesso</h2>
            <!-- <img src="../assets/img/testeUsuario-removebg.png" class="iconeUsuario"> -->
            <div id="error-box" class="error-message">
                <i class="fas fa-exclamation-circle"></i> <span id="error-text"></span>
            </div>

            <!-- <label for="email">Email:</label> -->
            <input type="email" name="email" id="email" placeholder="Seu Email" required>

            <!-- <label for="senha">Senha:</label> -->
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
    // Verifica se há mensagem de erro na URL
    const params = new URLSearchParams(window.location.search);
    if (params.has("erro")) {
        const errorMessage = decodeURIComponent(params.get("erro"));
        document.getElementById("error-text").textContent = errorMessage;
        document.getElementById("error-box").style.display = "block";
    }

    // Validação do formulário
    function validateForm() {
        const email = document.getElementById("email").value;
        const senha = document.getElementById("senha").value;

        if (!email || !senha) {
            document.getElementById("error-text").textContent = "Por favor, preencha todos os campos.";
            document.getElementById("error-box").style.display = "block";
            return false;
        }

        return true;
    }
    </script>
</body>

</html>