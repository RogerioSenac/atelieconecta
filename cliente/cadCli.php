<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

// Gera token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

try {
    // Conectar ao Firebase
    $factory = (new Factory())
        ->withServiceAccount('../config/chave.json')
        ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

    $database = $factory->createDatabase();

    // Cache simples para evitar múltiplas chamadas desnecessárias
    if (empty($_SESSION['estados_cache'])) {
        $_SESSION['estados_cache'] = $database->getReference('estados')->getValue();
    }
    if (empty($_SESSION['codigos_ddi_cache'])) {
        $_SESSION['codigos_ddi_cache'] = $database->getReference('codigos_ddi')->getValue();
    }
    if (empty($_SESSION['servicos_cache'])) {
        $_SESSION['servicos_cache'] = $database->getReference('servicos')->getValue();
    }

    $estados = $_SESSION['estados_cache'];
    $codigosDDI = $_SESSION['codigos_ddi_cache'];
    $servicos = $_SESSION['servicos_cache'];

} catch (FirebaseException $e) {
    $error = "Erro ao conectar com o banco de dados: " . $e->getMessage();
    error_log("Firebase Error: " . $e->getMessage());
}

// Função para validar CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    // Validação do CPF
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
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    
    // Validação do CNPJ
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

// Função para sanitizar e validar entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de segurança inválido. Por favor, recarregue a página e tente novamente.";
    } else {
        // Coletar e sanitizar os dados do formulário
        $nome = cleanInput(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $cpf_cnpj = cleanInput(filter_input(INPUT_POST, 'cpf_cnpj', FILTER_SANITIZE_STRING));
        $tipo_pessoa = isset($_POST['tipo_pessoa']) ? cleanInput($_POST['tipo_pessoa']) : '';
        $cel = cleanInput(filter_input(INPUT_POST, 'cel', FILTER_SANITIZE_STRING));
        $rua = cleanInput(filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING));
        $bairro = cleanInput(filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING));
        $cidade = cleanInput(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));
        $estado = isset($_POST['estado']) ? cleanInput($_POST['estado']) : '';
        $cep = cleanInput(filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING));

        // Redes sociais
        $whatsapp_url = isset($_POST['whatsapp_url']) ? cleanInput($_POST['whatsapp_url']) : '';
        $instagram_url = isset($_POST['instagram_url']) ? cleanInput($_POST['instagram_url']) : '';
        $facebook_url = isset($_POST['facebook_url']) ? cleanInput($_POST['facebook_url']) : '';

        // Serviços selecionados
        $servicos_selecionados = isset($_POST['servicos']) ? $_POST['servicos'] : [];
        $principais_servicos = [];
        $outros_servicos = [];

        // Validar campos obrigatórios
        if (empty($nome)) {
            $invalidFields['nome'] = "Nome é obrigatório.";
        }
        if (empty($tipo_pessoa)) {
            $invalidFields['tipo_pessoa'] = "Tipo de pessoa é obrigatório.";
        }
        if (empty($cpf_cnpj)) {
            $invalidFields['cpf_cnpj'] = "CPF/CNPJ é obrigatório.";
        }
        if (empty($cel)) {
            $invalidFields['cel'] = "Celular é obrigatório.";
        }
        if (empty($rua)) {
            $invalidFields['rua'] = "Endereço é obrigatório.";
        }
        if (empty($bairro)) {
            $invalidFields['bairro'] = "Bairro é obrigatório.";
        }
        if (empty($cidade)) {
            $invalidFields['cidade'] = "Cidade é obrigatória.";
        }
        if (empty($estado)) {
            $invalidFields['estado'] = "Estado é obrigatório.";
        }
        if (empty($cep)) {
            $invalidFields['cep'] = "CEP é obrigatório.";
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

        // Separando os serviços principais e os "Outros"
        foreach ($servicos_selecionados as $servico) {
            if ($servico === 'outros') {
                $outros_servicos_text = isset($_POST['outros_servicos']) ? cleanInput($_POST['outros_servicos']) : '';
                if (!empty($outros_servicos_text)) {
                    $outros_servicos = array_map('trim', explode(',', $outros_servicos_text));
                    $outros_servicos = array_filter($outros_servicos); // Remove valores vazios
                }
            } else {
                $principais_servicos[] = cleanInput($servico);
            }
        }

        // Se não houver campos inválidos, salvar os dados
        if (empty($invalidFields)) {
            try {
                // Salvar dados do usuário no Firebase
                $userRef = $database->getReference('userCli')->push([
                    'nome' => $nome,
                    'acesso' => [
                        'email' => $email,
                        'senha' => $senhaCriptografada,
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
                    ],
                    'data_cadastro' => date('Y-m-d H:i:s')
                ]);

                // Salvar serviços principais
                if (!empty($principais_servicos)) {
                    $userRef->getChild('servicos/principais')->set($principais_servicos);
                }

                // Salvar serviços "Outros"
                if (!empty($outros_servicos)) {
                    foreach ($outros_servicos as $servico) {
                        if (!empty($servico)) {
                            $userRef->getChild('servicos/outros')->push($servico);
                        }
                    }
                }

                // Mensagem de sucesso na sessão para exibir após redirecionamento
                $_SESSION['success_message'] = "Cadastro realizado com sucesso!";
                header('Location: loginCli.php');
                exit();

            } catch (FirebaseException $e) {
                $error = "Erro ao salvar os dados: " . $e->getMessage();
                error_log("Firebase Save Error: " . $e->getMessage());
            } catch (Exception $e) {
                $error = "Ocorreu um erro inesperado: " . $e->getMessage();
                error_log("General Error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Clientes</title>
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

    /* .servicos-opcoes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    } */

    .checkbox-container {
        display: flex;
        align-items: center;
    }

    .checkbox-container input[type="checkbox"] {
        margin-right: 8px;
    }

    /* .servicos-container {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 20px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    } */

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
/* 
    .botoes-container {
        display: flex;
        justify-content: space-between;
    } */

    .txt-labelService {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
    }

    #outros_servicos {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .rede h2 {
        margin-top: 20px;
        margin-bottom: 15px;
        font-size: 1.2em;
        color: #555;
    }

    .inputbox input[type="text"],
    .inputbox input[type="password"],
    .inputbox select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    </style>
    <script>
    function generateWhatsappURL() {
        const ddi = document.getElementById('whatsapp_ddi').value;
        const ddd = document.getElementById('whatsapp_ddd').value;
        const numero = document.getElementById('whatsapp_numero').value;

        if (ddi && ddd && numero) {
            const url = `https://wa.me/${ddi}${ddd}${numero}`;
            document.getElementById('whatsapp_url').value = url;
        }
    }

    function generateInstagramURL() {
        const user = document.getElementById('insta_user').value.trim();
        if (user) {
            const url = `https://instagram.com/${user.replace(/^@/, '')}`;
            document.getElementById('instagram_url').value = url;
        }
    }

    function generateFacebookURL() {
        const user = document.getElementById('face_user').value.trim();
        if (user) {
            const url = `https://facebook.com/${user}`;
            document.getElementById('facebook_url').value = url;
        }
    }

    function toggleOutrosServicos() {
        const outrosCheckbox = document.getElementById('outros');
        const outrosServicosInput = document.getElementById('outros_servicos');
        outrosServicosInput.style.display = outrosCheckbox.checked ? 'block' : 'none';
        if (outrosCheckbox.checked) {
            outrosServicosInput.focus();
        }
    }

    function applyMask() {
        const tipoPessoa = document.querySelector('input[name="tipo_pessoa"]:checked').value;
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        const celInput = document.getElementById('cel');
        const cepInput = document.getElementById('cep');

        // Remove máscaras anteriores
        Inputmask.remove(cpfCnpjInput);
        Inputmask.remove(celInput);
        Inputmask.remove(cepInput);

        if (tipoPessoa === 'pf') {
            Inputmask("999.999.999-99", {
                placeholder: "___.___.___-__",
                clearIncomplete: true
            }).mask(cpfCnpjInput);
        } else if (tipoPessoa === 'pj') {
            Inputmask("99.999.999/9999-99", {
                placeholder: "__.___.___/____-__",
                clearIncomplete: true
            }).mask(cpfCnpjInput);
        }

        Inputmask("(99) 99999-9999", {
            placeholder: "(__) _____-____",
            clearIncomplete: true
        }).mask(celInput);

        Inputmask("99999-999", {
            placeholder: "_____-___",
            clearIncomplete: true
        }).mask(cepInput);
    }

    function validateForm() {
        let isValid = true;
        const requiredFields = document.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('invalid-field');
                isValid = false;
            } else {
                field.classList.remove('invalid-field');
            }
        });

        return isValid;
    }

    document.addEventListener('DOMContentLoaded', function() {
        applyMask();

        // Adiciona validação em tempo real para campos obrigatórios
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('invalid-field');
                } else {
                    this.classList.remove('invalid-field');
                }
            });
        });

        // Foca no primeiro campo inválido se houver
        const firstInvalidField = document.querySelector('.invalid-field');
        if (firstInvalidField) {
            firstInvalidField.focus();
        }
    });

    function tipoPessoaChanged() {
        applyMask();
        // Limpa o campo CPF/CNPJ ao mudar o tipo
        document.getElementById('cpf_cnpj').value = '';
    }
    </script>
</head>

<body>
    <div class="boxCad">
        <h2>Cadastro de Clientes</h2>

        <?php if ($msg): ?>
        <div class="success-msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="" onsubmit="return validateForm()">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="email_hidden" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">

            <div class="inputbox">
                <input type="text" name="nome" id="nome" placeholder="Nome completo"
                    value="<?= isset($nome) ? htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') : '' ?>" required
                    class="<?= isset($invalidFields['nome']) ? 'invalid-field' : '' ?>">
                <?php if (isset($invalidFields['nome'])): ?>
                <div class="error-msg"><?= htmlspecialchars($invalidFields['nome'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="tipo-pessoa-container">
                <label>
                    <input type="radio" name="tipo_pessoa" value="pf"
                        <?= (!isset($tipo_pessoa) || $tipo_pessoa === 'pf') ? 'checked' : '' ?>
                        onchange="tipoPessoaChanged()" required> Pessoa Física
                </label>
                <label>
                    <input type="radio" name="tipo_pessoa" value="pj"
                        <?= (isset($tipo_pessoa) && $tipo_pessoa === 'pj') ? 'checked' : '' ?>
                        onchange="tipoPessoaChanged()"> Pessoa Jurídica
                </label>
                <?php if (isset($invalidFields['tipo_pessoa'])): ?>
                <div class="error-msg"><?= htmlspecialchars($invalidFields['tipo_pessoa'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" placeholder="CPF / CNPJ"
                        value="<?= isset($cpf_cnpj) ? htmlspecialchars($cpf_cnpj, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['cpf_cnpj']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cpf_cnpj'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['cpf_cnpj'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="inputbox">
                    <input type="text" name="cel" id="cel" placeholder="Celular com DDD"
                        value="<?= isset($cel) ? htmlspecialchars($cel, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['cel']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cel'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['cel'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="rua" id="rua" placeholder="Endereço completo"
                        value="<?= isset($rua) ? htmlspecialchars($rua, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['rua']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['rua'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['rua'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="bairro" id="bairro" placeholder="Bairro"
                        value="<?= isset($bairro) ? htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['bairro']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['bairro'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['bairro'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="inputbox">
                    <input type="text" name="cidade" id="cidade" placeholder="Cidade"
                        value="<?= isset($cidade) ? htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['cidade']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cidade'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['cidade'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="inputbox">
                    <select name="estado" id="estado" required
                        class="<?= isset($invalidFields['estado']) ? 'invalid-field' : '' ?>">
                        <option value="" disabled selected>Selecione o Estado</option>
                        <?php foreach ($estados as $sigla => $nome): ?>
                        <option value="<?= htmlspecialchars($sigla, ENT_QUOTES, 'UTF-8') ?>"
                            <?= (isset($estado) && $estado === $sigla) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($invalidFields['estado'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['estado'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="inputbox">
                    <input type="text" name="cep" id="cep" placeholder="CEP"
                        value="<?= isset($cep) ? htmlspecialchars($cep, ENT_QUOTES, 'UTF-8') : '' ?>"
                        class="<?= isset($invalidFields['cep']) ? 'invalid-field' : '' ?>" required>
                    <?php if (isset($invalidFields['cep'])): ?>
                    <div class="error-msg"><?= htmlspecialchars($invalidFields['cep'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rede">
                <h2>Redes Sociais (Opcionais)</h2>
                <div class="inputbox">
                    <select name="whatsapp_ddi" id="whatsapp_ddi" onchange="generateWhatsappURL()">
                        <option value="" disabled selected>Escolha o DDI</option>
                        <?php foreach ($codigosDDI as $ddi => $pais): ?>
                        <option value="<?= htmlspecialchars($ddi, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($pais, ENT_QUOTES, 'UTF-8') ?>
                            (+<?= htmlspecialchars($ddi, ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="whatsapp_ddd" id="whatsapp_ddd" placeholder="DDD"
                        oninput="generateWhatsappURL()" maxlength="2" >
                    <input type="text" name="whatsapp_numero" id="whatsapp_numero" placeholder="Número do WhatsApp"
                        oninput="generateWhatsappURL()">
                </div>
                <input type="hidden" name="whatsapp_url" id="whatsapp_url">

                <div class="inputbox">
                    <input type="text" name="insta_user" id="insta_user"
                        placeholder="Nome de usuário do Instagram (sem @)" oninput="generateInstagramURL()">
                    <input type="text" name="face_user" id="face_user" placeholder="Nome de usuário do Facebook"
                        oninput="generateFacebookURL()">
                </div>
                <input type="hidden" name="instagram_url" id="instagram_url">
                <input type="hidden" name="facebook_url" id="facebook_url">
            </div>

           
            <div class="botoes-container">
                <input type="submit" value="Cadastrar" class="sub">
                <a href="../index.php" class="back">Voltar</a>
            </div>
        </form>
    </div>
</body>

</html>