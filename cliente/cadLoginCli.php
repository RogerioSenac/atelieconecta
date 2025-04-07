<?php
session_start(); // Inicia a sessão
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

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

            // Verifica se o e-mail já está cadastrado
            try {
                $existingUser = $auth->getUserByEmail($email);
                // Se o e-mail já estiver cadastrado, mostra mensagem e redireciona
                echo "<script>
                    alert('Este e-mail já tem cadastro. Por favor, forneça outro valido ou efetue o login com o já cadastrado.');
                    window.location.href = 'loginCli.php';
                </script>";
                exit();
            } catch (Exception $e) {
                // Se o erro for "user not found", o usuário não existe e podemos continuar
                if (strpos($e->getMessage(), 'No user with email') !== false) {
                    // Continuar com a criação do usuário
                } else {
                    // Se o erro for diferente de "user not found", mostra o erro
                    throw $e;
                }
            }

            // Cria o usuário com a SDK Admin corretamente
            $userProperties = [
                'email' => $email,
                'emailVerified' => false,
                'password' => $senha,
            ];
            $newUser = $auth->createUser($userProperties);

            // Depuração: verificar os dados do usuário criado
            echo '<pre>';
            var_dump($newUser);
            echo '</pre>';

            // Armazena o e-mail na sessão
            $_SESSION['email'] = $email;

            // Redireciona para cadCli.php
            header('Location: cadCli.php');
            exit();
        } catch (Exception $e) {
            $msg = "Erro ao cadastrar usuário: " . $e->getMessage();
            echo "<pre>";
            var_dump($e); // Exibe mais detalhes sobre o erro para depuração
            echo "</pre>";
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

    // Retorna mensagem de erro única
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
    <div class="interfaceCadLogin">
        <div class="box">
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

                <input type="submit" value="Enviar" class="sub">
                <button class="back" type="button" onclick="window.location.href='loginCli.php'">Voltar</button>
            </form>
        </div>
    </div>

    <script>
        // Scripts JavaScript para exibir/esconder senha
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

        function validarForcaSenha(senha) {
            // Função de validação de força de senha
            let erros = [];

            if (senha.length < 8) {
                erros.push("A senha deve ter no mínimo 8 caracteres.");
            }
            if (!/[A-Z]/.test(senha)) {
                erros.push("A senha deve conter pelo menos uma letra maiúscula.");
            }
            if (!/[a-z]/.test(senha)) {
                erros.push("A senha deve conter pelo menos uma letra minúscula.");
            }
            if (!/[0-9]/.test(senha)) {
                erros.push("A senha deve conter pelo menos um número.");
            }
            if (!/[!@#$%^&*()]/.test(senha)) {
                erros.push("A senha deve conter pelo menos um caractere especial (!@#$%^&*()).");
            }

            if (erros.length > 0) {
                return "A senha não atende aos seguintes requisitos:\n- " + erros.join("\n- ");
            }

            return ""; // Senha válida
        }
    </script>
</body>
</html>
