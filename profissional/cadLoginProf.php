<?php
session_start(); // Inicia a sessão
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://pi-a24-default-rtdb.firebaseio.com/');

$msg = "";

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmaSenha = $_POST['confirma_senha'];

    // Valida a força da senha
    $mensagemErro = validarForcaSenha($senha);
    if ($mensagemErro) {
        $msg = $mensagemErro;
    } elseif ($senha !== $confirmaSenha) {
        $msg = "As senhas não coincidem!";
    } else {
        try {
            $auth = $factory->createAuth();
            $newUser = $auth->createUserWithEmailAndPassword($email, $senha);

            // Armazena o e-mail e a senha na sessão
            $_SESSION['email'] = $email;
            $_SESSION['senha'] = $senha;

            // Redireciona para cadProf.php
            header('Location: cadProf.php');
            exit();
        } catch (Exception $e) {
            $msg = "Erro ao cadastrar usuário: " . $e->getMessage();
        }
    }
}

function validarForcaSenha($senha)
{
    $erros = [];

    // Mínimo de 8 caracteres
    if (strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    }

    // Pelo menos uma letra maiúscula
    if (!preg_match('/[A-Z]/', $senha)) {
        $erros[] = "A senha deve conter pelo menos uma letra maiúscula.";
    }

    // Pelo menos uma letra minúscula
    if (!preg_match('/[a-z]/', $senha)) {
        $erros[] = "A senha deve conter pelo menos uma letra minúscula.";
    }

    // Pelo menos um número
    if (!preg_match('/[0-9]/', $senha)) {
        $erros[] = "A senha deve conter pelo menos um número.";
    }

    // Pelo menos um caractere especial
    if (!preg_match('/[!@#$%^&*()]/', $senha)) {
        $erros[] = "A senha deve conter pelo menos um caractere especial (!@#$%^&*()).";
    }

    // Se houver erros, retorna uma mensagem única com todos os requisitos
    if (!empty($erros)) {
        return "A senha não atende aos seguintes requisitos:\n- " . implode("\n- ", $erros);
    }

    return ""; // Senha válida
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Acesso</title>
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                    <input type="password" name="confirma_senha" id="confirma_senha" placeholder="Confirme sua senha"
                        required>
                    <i class="fa fa-eye toggle-password" onclick="toggleSenha('confirma_senha')"></i>
                </div>

                <!-- Campo oculto para passar o e-mail -->
                <input type="hidden" name="email_hidden"
                    value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

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

    function validarForcaSenha(senha) {
        let erros = [];

        // Mínimo de 8 caracteres
        if (senha.length < 8) {
            erros.push("A senha deve ter no mínimo 8 caracteres.");
        }

        // Pelo menos uma letra maiúscula
        if (!/[A-Z]/.test(senha)) {
            erros.push("A senha deve conter pelo menos uma letra maiúscula.");
        }

        // Pelo menos uma letra minúscula
        if (!/[a-z]/.test(senha)) {
            erros.push("A senha deve conter pelo menos uma letra minúscula.");
        }

        // Pelo menos um número
        if (!/[0-9]/.test(senha)) {
            erros.push("A senha deve conter pelo menos um número.");
        }

        // Pelo menos um caractere especial
        if (!/[!@#$%^&*()]/.test(senha)) {
            erros.push("A senha deve conter pelo menos um caractere especial (!@#$%^&*()).");
        }

        // Se houver erros, retorna uma mensagem única com todos os requisitos
        if (erros.length > 0) {
            return "A senha não atende aos seguintes requisitos:\n- " + erros.join("\n- ");
        }

        return ""; // Senha válida
    }

    function validarSenha() {
        let senha = document.getElementById("senha").value;
        let confirmaSenha = document.getElementById("confirma_senha").value;

        // Valida a força da senha
        let mensagemErro = validarForcaSenha(senha);
        if (mensagemErro) {
            alert(mensagemErro);
            return false;
        }

        // Verifica se as senhas coincidem
        if (senha !== confirmaSenha) {
            alert("As senhas não coincidem!");
            return false;
        }

        return true;
    }
    </script>
</body>

</html>