<?php
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

$msg = "";

if (isset($_POST['cpf'])) {
    $factory = (new Factory())
        ->withServiceAccount('../chave.json')
        ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');
    $database = $factory->createDatabase();

    $novoUser = [
        'cpf' => $_POST['cpf'],
        'nome' => $_POST['nome'],
        'rua' => $_POST['rua'],
        'bairro' => $_POST['bairro'],
        'cidade' => $_POST['cidade'],
        'estado' => $_POST['estado'],
        'cep' => $_POST['cep'],
        'cel' => $_POST['cel']
    ];
    
    
    try {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $_POST['cpf']); // Remove pontos e traços
        $database->getReference('userCli/' . $cpfLimpo)->set($novoUser);
        $msg = "Cadastro realizado com sucesso!";
    } catch (Exception $e) {
        $msg = "Cadastro não realizado.";
    }
    header('Location:loginCli.php');
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
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6-beta.9/dist/inputmask.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Inicializa o Inputmask para CPF
            Inputmask({
                "mask": "999.999.999-99"
            }).mask(document.getElementById("cpf"));

            // Inicializa o Inputmask para Celular
            Inputmask({
                "mask": "(99) 99999-9999"
            }).mask(document.getElementById("cel"));

            // Inicializa o Inputmask para CEP
            Inputmask({
                "mask": "99999-999"
            }).mask(document.getElementById("cep"));
        });
    </script>
</head>

<body>
    <div class="box">
        <div class="formulario">
            <h2>Cadastro de Usuário</h2>

            <form method="post">
                <div class="linha">
                    <div class="inputbox">
                        <input type="text" name="cpf" id="cpf" placeholder="CPF" required>
                    </div>
                    <div class="inputbox">
                        <input type="text" name="nome" id="nome" placeholder="Nome" required>
                    </div>
                    <div class="inputbox">
                        <input type="text" name="cel" id="cel" placeholder="Celular" required>
                    </div>
                </div>
                <div class="linha">
                    <div class="inputbox">
                        <input type="text" name="rua" id="rua" placeholder="Endereço" required>
                    </div>
                    <div class="inputbox">
                        <input type="text" name="bairro" id="bairro" placeholder="Bairro" required>
                    </div>
                </div>
                <div class="linha">
                    <div class="inputbox">
                        <input type="text" name="cidade" id="cidade" placeholder="Cidade" required>
                    </div>
                    <div class="inputbox">
                        <select name="estado" id="estado" required>
                            <option value="" disabled selected>Selecione o Estado</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                    <div class="inputbox">
                        <input type="text" name="cep" id="cep" placeholder="CEP" required>
                    </div>
                </div>
                <div class="botoes-container">
                    <input type="submit" value="Cadastrar" class="sub">
                </div>
                <a href="./index.php" class="back">Voltar</a>
            </form>
        </div>
    </div>
</body>

</html>