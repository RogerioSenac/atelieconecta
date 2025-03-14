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
    <style>
        .error-message {
            display: none;
            color: red;
            /* background-color: #ff4d4d; */
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="interface">
        <form class="acesso" method="post" action="validaLoginCli.php">
            <h2>Login de Acesso</h2>

            <!-- Mensagem de erro -->
            <div id="error-box" class="error-message">
                <i class="fas fa-exclamation-circle"></i> <span id="error-text"></span>
            </div>

            <input type="email" name="email" id="email" placeholder="Seu Email" required>
            <input type="password" name="senha" id="senha" placeholder="Senha Acesso" required>

            <div class="btn-enviar">
                <input type="submit" value="Logar">
            </div>

            <div class="text-center mt-3">
                <a href="./cadLoginCli.php">Não tem uma conta? Cadastre-se</a>
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
    </script>
</body>

</html>
