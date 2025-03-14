<?php
session_start(); // Inicia a sessão
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

// Recupera o e-mail da sessão ou do campo oculto do formulário
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
if (empty($email)) {
    $email = isset($_POST['email_hidden']) ? $_POST['email_hidden'] : '';
}

// Recupera a senha da sessão
$senha = isset($_SESSION['senha']) ? $_SESSION['senha'] : '';

// Criptografa a senha antes de salvar no Firebase
$senhaCriptografada = password_hash($senha, PASSWORD_BCRYPT);

$msg = "";
$error = "";
$invalidFields = [];

// Conectar ao Firebase
$factory = (new Factory())
->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();

// Carregar os estados, códigos DDI e serviços do Firebase
$estados = $database->getReference('estados')->getValue();
$codigosDDI = $database->getReference('codigos_ddi')->getValue();
$servicos = $database->getReference('servicos')->getValue();

// Função para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// Função para validar CNPJ
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar e sanitizar os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf_cnpj = filter_input(INPUT_POST, 'cpf_cnpj', FILTER_SANITIZE_STRING);
    $tipo_pessoa = $_POST['tipo_pessoa'];
    $cel = filter_input(INPUT_POST, 'cel', FILTER_SANITIZE_STRING);
    $rua = filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING);
    $bairro = filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING);
    $cidade = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING);
    $estado = $_POST['estado'];
    $cep = filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING);

    // Redes sociais
    $whatsapp_url = $_POST['whatsapp_url'];
    $instagram_url = $_POST['instagram_url'];
    $facebook_url = $_POST['facebook_url'];

    // Serviços selecionados
    $servicos_selecionados = isset($_POST['servicos']) ? $_POST['servicos'] : [];
    $principais_servicos = [];
    $outros_servicos = [];

    // Separando os serviços principais e os "Outros"
    foreach ($servicos_selecionados as $servico) {
        if ($servico === 'outros') {
            $outros_servicos = array_map('trim', explode(',', $_POST['outros_servicos']));
        } else {
            $principais_servicos[] = $servico;
        }
    }

    // Validar CPF/CNPJ
    if ($tipo_pessoa === 'pf' && !validarCPF($cpf_cnpj)) {
        $invalidFields['cpf_cnpj'] = "CPF inválido.";
    } elseif ($tipo_pessoa === 'pj' && !validarCNPJ($cpf_cnpj)) {
        $invalidFields['cpf_cnpj'] = "CNPJ inválido.";
    }

    // Validar outros campos
    if (strlen($cel) < 14) {
        $invalidFields['cel'] = "Celular inválido.";
    }
    if (strlen($cep) < 9) {
        $invalidFields['cep'] = "CEP inválido.";
    }

    // Se não houver campos inválidos, salvar os dados
    try {
        // Salvar dados do usuário no Firebase
        $userRef = $database->getReference('userProf')->push([
            'nome' => $nome,
            'acesso' => [
                'email' => $email, // Certifique-se de que o e-mail está aqui
                'senha' => $senhaCriptografada, // Senha criptografada
            ],
            'tipo_pessoa' => $tipo_pessoa,
            'cpf_cnpj' => $cpf_cnpj,
            'cel' => $cel,
            'endereco' => [
                'rua' => $rua,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
                'cep' => $cep
            ],
            'redes_sociais' => [
                'whatsapp' => $whatsapp_url,
                'instagram' => $instagram_url,
                'facebook' => $facebook_url
            ]
        ]);
    

            // Salvar serviços principais
            if (!empty($principais_servicos)) {
                $userRef->getChild('servicos/principais')->set($principais_servicos);
            }

            // Salvar serviços "Outros"
            if (!empty($outros_servicos)) {
                foreach ($outros_servicos as $servico) {
                    $userRef->getChild('servicos/outros')->push($servico);
                }
            }

            // Redirecionar para o painel de acesso
            header('Location: DashAcessoProf.php');
            exit();
        } catch (Exception $e) {
            $error = "Erro ao salvar os dados: " . $e->getMessage();
        }
    }
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Profissional</title>
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6-beta.9/dist/inputmask.min.js"></script>
    <style>
    .invalid-field {
        border: 1px solid red !important;
    }

    .error-msg {
        color: red;
        font-size: 14px;
        margin-top: 5px;
    }

    .servicos-opcoes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
    }

    .checkbox-container input[type="checkbox"] {
        margin-right: 8px;
    }

    .servicos-container {
        max-height: 300px;
        overflow-y: auto;
    }

    .tipo-pessoa-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
    }

    .tipo-pessoa-container label {
        margin-right: 15px;
        font-size: 18px;
    }

    .linha {
        display: flex;
        gap: 10px;
    }

    .inputbox {
        margin-bottom: 10px;
    }

    .rede .inputbox input,
    .rede select {
        width: 100%;
    }

    .rede .inputbox {
        margin-bottom: 10px;
    }

    .success-msg {
        color: green;
        font-size: 18px;
        text-align: center;
        margin-bottom: 20px;
    }

    .botoes-container {
        display: flex;
        justify-content: space-between;
    }
    </style>
    <script>
    function generateWhatsappURL() {
        const ddi = document.getElementById('whatsapp_ddi').value;
        const ddd = document.getElementById('whatsapp_ddd').value;
        const numero = document.getElementById('whatsapp_numero').value;
        const url = `https://wa.me/${ddi}${ddd}${numero}`;
        document.getElementById('whatsapp_url').value = url;
    }

    function generateInstagramURL() {
        const user = document.getElementById('insta_user').value;
        const url = `https://instagram.com/${user}`;
        document.getElementById('instagram_url').value = url;
    }

    function generateFacebookURL() {
        const user = document.getElementById('face_user').value;
        const url = `https://facebook.com/${user}`;
        document.getElementById('facebook_url').value = url;
    }

    function toggleOutrosServicos() {
        const outrosCheckbox = document.getElementById('outros');
        const outrosServicosInput = document.getElementById('outros_servicos');
        outrosServicosInput.style.display = outrosCheckbox.checked ? 'block' : 'none';
    }

    function applyMask() {
        const tipoPessoa = document.querySelector('input[name="tipo_pessoa"]:checked').value;
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        const celInput = document.getElementById('cel');
        const cepInput = document.getElementById('cep');

        if (tipoPessoa === 'pf') {
            Inputmask("999.999.999-99").mask(cpfCnpjInput);
        } else if (tipoPessoa === 'pj') {
            Inputmask("99.999.999/9999-99").mask(cpfCnpjInput);
        }

        Inputmask("(99) 99999-9999").mask(celInput);
        Inputmask("99999-999").mask(cepInput);
    }

    window.onload = applyMask;

    function tipoPessoaChanged() {
        applyMask();
    }
    </script>
</head>

<body>
    <div class="box">
        <h2>Cadastro de Profissional</h2>

        <?php if ($msg): ?>
        <div class="success-msg"><?= $msg ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="inputbox">
                <input type="text" name="nome" id="nome" placeholder="Nome" value="<?= isset($nome) ? $nome : '' ?>"
                    required>
            </div>

            <div class="tipo-pessoa-container">
                <label>
                    <input type="radio" name="tipo_pessoa" value="pf"
                        <?= (!isset($tipo_pessoa) || $tipo_pessoa === 'pf') ? 'checked' : '' ?>
                        onchange="tipoPessoaChanged()"> Pessoa Física
                </label>
                <label>
                    <input type="radio" name="tipo_pessoa" value="pj"
                        <?= (isset($tipo_pessoa) && $tipo_pessoa === 'pj') ? 'checked' : '' ?>
                        onchange="tipoPessoaChanged()"> Pessoa Jurídica
                </label>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" placeholder="CPF / CNPJ"
                        value="<?= isset($cpf_cnpj) ? $cpf_cnpj : '' ?>"
                        class="<?= isset($invalidFields['cpf_cnpj']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cpf_cnpj'])): ?>
                    <div class="error-msg"><?= $invalidFields['cpf_cnpj'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="inputbox">
                    <input type="text" name="cel" id="cel" placeholder="Celular" value="<?= isset($cel) ? $cel : '' ?>"
                        class="<?= isset($invalidFields['cel']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cel'])): ?>
                    <div class="error-msg"><?= $invalidFields['cel'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="rua" id="rua" placeholder="Endereço" value="<?= isset($rua) ? $rua : '' ?>"
                        required>
                </div>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="bairro" id="bairro" placeholder="Bairro"
                        value="<?= isset($bairro) ? $bairro : '' ?>" required>
                </div>
                <div class="inputbox">
                    <input type="text" name="cidade" id="cidade" placeholder="Cidade"
                        value="<?= isset($cidade) ? $cidade : '' ?>" required>
                </div>
                <div class="inputbox">
                    <select name="estado" id="estado" required>
                        <option value="" disabled selected>Selecione o Estado</option>
                        <?php foreach ($estados as $sigla => $nome): ?>
                        <option value="<?= $sigla ?>" <?= (isset($estado) && $estado === $sigla) ? 'selected' : '' ?>>
                            <?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="inputbox">
                    <input type="text" name="cep" id="cep" placeholder="CEP" value="<?= isset($cep) ? $cep : '' ?>"
                        class="<?= isset($invalidFields['cep']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cep'])): ?>
                    <div class="error-msg"><?= $invalidFields['cep'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rede">
                <h2>Redes Sociais</h2>
                <div class="inputbox">
                    <select name="whatsapp_ddi" id="whatsapp_ddi" onchange="generateWhatsappURL()">
                        <option value="" disabled selected>Escolha o DDI</option>
                        <?php foreach ($codigosDDI as $ddi => $pais): ?>
                        <option value="<?= $ddi ?>"><?= $pais ?> (+<?= $ddi ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="whatsapp_ddd" id="whatsapp_ddd" placeholder="DDD"
                        oninput="generateWhatsappURL()">
                    <input type="text" name="whatsapp_numero" id="whatsapp_numero" placeholder="Número do WhatsApp"
                        oninput="generateWhatsappURL()">
                </div>
                <input type="hidden" name="whatsapp_url" id="whatsapp_url">

                <div class="inputbox">
                    <input type="text" name="insta_user" id="insta_user" placeholder="Link do Instagram"
                        oninput="generateInstagramURL()">
                    <input type="text" name="face_user" id="face_user" placeholder="Link do Facebook"
                        oninput="generateFacebookURL()">
                </div>
                <input type="hidden" name="instagram_url" id="instagram_url">
                <input type="hidden" name="facebook_url" id="facebook_url">
            </div>

            <div class="servicos-container">
                <label class="txt-labelService">Serviços oferecidos</label>
                <div class="servicos-opcoes">
                    <?php if (!empty($servicos)): ?>
                    <?php foreach ($servicos as $servico): ?>
                    <?php if ($servico['nome'] != "outros"): ?>
                    <div class="checkbox-container">
                        <input type="checkbox" name="servicos[]" value="<?= $servico['nome'] ?>">
                        <span><?= $servico['nome'] ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p>Nenhum serviço cadastrado no momento.</p>
                    <?php endif; ?>

                    <div class="checkbox-container">
                        <input type="checkbox" id="outros" name="servicos[]" value="outros"
                            onchange="toggleOutrosServicos()">
                        <span>Outros</span>
                        <input type="text" id="outros_servicos" name="outros_servicos"
                            placeholder="Descreva os serviços adicionais" autocomplete="off" style="display:none;">
                    </div>
                </div>
            </div>

            <div class="botoes-container">
                <input type="submit" value="Cadastrar" class="sub">
                <a href="../index.php" class="back">Voltar</a>
            </div>
        </form>
    </div>
</body>

</html>