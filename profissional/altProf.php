<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

if (!isset($_SESSION['logado'])) {
    header("Location: perfil.php");
    exit;
}

$email = htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8');

$factory = (new Factory())
    ->withServiceAccount('../chave.json')
    ->withDatabaseUri('https://pi-a24-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();
$auth = $factory->createAuth();

$userData = $database->getReference('userProf')
    ->orderByChild('email')
    ->equalTo($email)
    ->getValue();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
    $senha = $_POST['senha'];
    try {
        $user = $auth->getUserByEmail($email);
        $signInResult = $auth->signInWithEmailAndPassword($email, $senha);

        $updatedData = [
            'nome' => $_POST['nome'],
            'cel' => $_POST['cel']
        ];

        $userKey = array_key_first($userData);
        $database->getReference("userProf/$userKey")->update($updatedData);

        echo "<script>alert('Dados atualizados com sucesso!'); window.location.href = window.location.href;</script>";
    } catch (Exception $e) {
        echo "<script>alert('Senha incorreta!');</script>";
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
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.6-beta.9/dist/inputmask.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const cpfCnpjInput = document.getElementById("cpf");
            const celularInput = document.getElementById("cel");
            const cepInput = document.getElementById("cep");
            const tipoPessoa = document.querySelectorAll("input[name='tipo']");
            const outrosCheckbox = document.getElementById("outros");
            const outrosServicosInput = document.getElementById("outros_servicos");
            const whatsappNumeroInput = document.getElementById("whatsapp_numero");
           

            tipoPessoa.forEach(radio => radio.addEventListener("change", function() {
                let mask = this.value === 'pf' ? "999.999.999-99" : "99.999.999/9999-99";
                Inputmask({
                    "mask": mask
                }).mask(cpfCnpjInput);
            }));

            cpfCnpjInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, '');
            });

            Inputmask({
                "mask": "(99) 99999-9999"
            }).mask(celularInput);
            Inputmask({
                "mask": "99999-999"
            }).mask(cepInput);
            Inputmask({
                "mask": "99999999999",
                "placeholder": "",
                "showMaskOnHover": false
            }).mask(whatsappNumeroInput);

            outrosCheckbox.addEventListener("change", function() {
                if (this.checked) {
                    outrosServicosInput.style.display = "block";
                    outrosServicosInput.focus();
                } else {
                    outrosServicosInput.style.display = "none";
                    outrosServicosInput.value = "";
                }
            });
        });

        function generateWhatsappURL() {
            const ddi = document.getElementById("whatsapp_ddi").value;
            const ddd = document.getElementById("whatsapp_ddd").value;
            const numero = document.getElementById("whatsapp_numero").value;
            const whatsappUrl = `https://api.whatsapp.com/send?phone=${ddi}${ddd}${numero}`;
            document.getElementById("whatsapp_url").value = whatsappUrl;
        }

        function generateInstagramURL() {
            const userInsta = document.getElementById("insta_user").value;
            const instaUrl = `https://instagram.com/${userInsta}`;
            document.getElementById("instagram_url").value = instaUrl;
        }

        function generateFacebookURL() {
            const userFace = document.getElementById("face_user").value;
            const faceUrl = `https://www.facebook.com/${userFace}`;
            document.getElementById("facebook_url").value = faceUrl;
        }

    </script>
</head>

<body>
    <div class="box">
        <h2>Cadastro de Usuário</h2>
        <form method="post" action="">
        <!-- <?php if ($userData): ?>
                    <?php foreach ($userData as $user): ?>
                        <div class="row">
                            <div class="col-md-6 dadosPessoais">
                                <div class="info-row-nome">
                                    <div class="info-item"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum dado encontrado.</p>
                <?php endif; ?> -->

            <div class="inputbox">
                <input type="text" name="nome" id="nome" value=<?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?>>
            </div>
            <div class="radio-group">
                <label><input type="radio" name="tipo" value="pf" id="pf" required> Pessoa Física</label>
                <label><input type="radio" name="tipo" value="pj" id="pj" required> Pessoa Jurídica</label>
            </div>
            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="cpf" id="cpf" placeholder="CPF / CNPJ" required>
                </div>
                <div class="inputbox">
                    <input type="text" name="cel" id="cel" placeholder="Celular" required>
                </div>
            </div>
            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="rua" id="rua" placeholder="Endereço" required>
                </div>
            </div>
            <div class="linha">
                <div class="inputbox">
                    <input type="text" name="bairro" id="bairro" placeholder="Bairro" required>
                </div>
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

            <div class="rede">
                <h2>Cadastro de Rede Social</h2>
                <div class="inputbox">
                    <select name="whatsapp_ddi" id="whatsapp_ddi" onchange="generateWhatsappURL()">
                        <option value="" disabled selected>Escolha o DDI</option>
                        <option value="93">Afeganistão (+93)</option>
                        <option value="355">Albânia (+355)</option>
                        <option value="49">Alemanha (+49)</option>
                        <option value="376">Andorra (+376)</option>
                        <option value="244">Angola (+244)</option>
                        <option value="1-268">Antígona e Barbuda (+1-268)</option>
                        <option value="54">Argentina (+54)</option>
                        <option value="374">Armênia (+374)</option>
                        <option value="61">Austrália (+61)</option>
                        <option value="43">Áustria (+43)</option>
                        <option value="994">Azerbaijão (+994)</option>
                        <option value="1-242">Bahamas (+1-242)</option>
                        <option value="973">Bahrein (+973)</option>
                        <option value="880">Bangladesh (+880)</option>
                        <option value="1-246">Barbados (+1-246)</option>
                        <option value="32">Bélgica (+32)</option>
                        <option value="375">Bielorrússia (+375)</option>
                        <option value="591">Bolívia (+591)</option>
                        <option value="387">Bósnia e Herzegovina (+387)</option>
                        <option value="267">Botsuana (+267)</option>
                        <option value="55">Brasil (+55)</option>
                        <option value="673">Brunei (+673)</option>
                        <option value="359">Bulgária (+359)</option>
                        <option value="238">Cabo Verde (+238)</option>
                        <option value="237">Camarões (+237)</option>
                        <option value="1">Canadá (+1)</option>
                        <option value="7">Cazaquistão (+7)</option>
                        <option value="56">Chile (+56)</option>
                        <option value="86">China (+86)</option>
                        <option value="57">Colômbia (+57)</option>
                        <option value="242">Congo (República do) (+242)</option>
                        <option value="243">Congo (República Democrática do Congo) (+243)</option>
                        <option value="82">Coréia do Sul (+82)</option>
                        <option value="506">Costa Rica (+506)</option>
                        <option value="225">Côte d'Ivoire (+225)</option>
                        <option value="385">Croácia (+385)</option>
                        <option value="45">Dinamarca (+45)</option>
                        <option value="20">Egito (+20)</option>
                        <option value="503">El Salvador (+503)</option>
                        <option value="971">Emirados Árabes Unidos (+971)</option>
                        <option value="593">Equador (+593)</option>
                        <option value="44">Escócia (+44)</option>
                        <option value="34">Espanha (+34)</option>
                        <option value="1">Estados Unidos (+1)</option>
                        <option value="372">Estônia (+372)</option>
                        <option value="970">Faixa de Gaza (+970)</option>
                        <option value="63">Filipinas (+63)</option>
                        <option value="358">Finlândia (+358)</option>
                        <option value="33">França (+33)</option>
                        <option value="995">Geórgia (+995)</option>
                        <option value="233">Gana (+233)</option>
                        <option value="30">Grécia (+30)</option>
                        <option value="502">Guatemala (+502)</option>
                        <option value="592">Guiana (+592)</option>
                        <option value="504">Honduras (+504)</option>
                        <option value="36">Hungria (+36)</option>
                        <option value="354">Islândia (+354)</option>
                        <option value="91">Índia (+91)</option>
                        <option value="62">Indonésia (+62)</option>
                        <option value="98">Irã (+98)</option>
                        <option value="353">Irlanda (+353)</option>
                        <option value="354">Islândia (+354)</option>
                        <option value="39">Itália (+39)</option>
                        <option value="1-876">Jamaica (+1-876)</option>
                        <option value="81">Japão (+81)</option>
                        <option value="962">Jordânia (+962)</option>
                        <option value="371">Letônia (+371)</option>
                        <option value="961">Líbano (+961)</option>
                        <option value="231">Libéria (+231)</option>
                        <option value="370">Lituânia (+370)</option>
                        <option value="352">Luxemburgo (+352)</option>
                        <option value="60">Malásia (+60)</option>
                        <option value="265">Malawi (+265)</option>
                        <option value="356">Malta (+356)</option>
                        <option value="212">Marrocos (+212)</option>
                        <option value="230">Maurício (+230)</option>
                        <option value="52">México (+52)</option>
                        <option value="258">Moçambique (+258)</option>
                        <option value="373">Moldávia (+373)</option>
                        <option value="976">Mongólia (+976)</option>
                        <option value="47">Noruega (+47)</option>
                        <option value="64">Nova Zelândia (+64)</option>
                        <option value="92">Paquistão (+92)</option>
                        <option value="507">Panamá (+507)</option>
                        <option value="595">Paraguai (+595)</option>
                        <option value="51">Peru (+51)</option>
                        <option value="48">Polônia (+48)</option>
                        <option value="351">Portugal (+351)</option>
                        <option value="254">Quênia (+254)</option>
                        <option value="1-809">República Dominicana (+1-809)</option>
                        <option value="420">República Tcheca (+420)</option>
                        <option value="40">Romênia (+40)</option>
                        <option value="7">Rússia (+7)</option>
                        <option value="221">Senegal (+221)</option>
                        <option value="232">Serra Leoa (+232)</option>
                        <option value="65">Singapura (+65)</option>
                        <option value="963">Síria (+963)</option>
                        <option value="94">Sri Lanka (+94)</option>
                        <option value="46">Suécia (+46)</option>
                        <option value="41">Suíça (+41)</option>
                        <option value="66">Tailândia (+66)</option>
                        <option value="90">Turquia (+90)</option>
                        <option value="380">Ucrânia (+380)</option>
                        <option value="598">Uruguai (+598)</option>
                        <option value="58">Venezuela (+58)</option>
                        <option value="84">Vietnã (+84)</option>
                        <option value="260">Zâmbia (+260)</option>
                        <option value="263">Zimbábue (+263)</option>
                    </select>
                    <input type="text" name="whatsapp_ddd" id="whatsapp_ddd" placeholder="DDD"  oninput="generateWhatsappURL()">
                    <input type="text" name="whatsapp_numero" id="whatsapp_numero" placeholder="Número do WhatsApp" oninput="generateWhatsappURL()">
                </div>
                <input type="hidden" name="whatsapp_url" id="whatsapp_url">
                
                <div class="inputbox">
                    <input type="text" name="insta_user" id="insta_user" placeholder="Link do Instagram" oninput="generateInstagramURL()">
                    <input type="text" name="face_user" id="face_user" placeholder="Link do Facebook" oninput="generateFacebookURL()">
                </div>
                <input type="hidden" name="instagram_url" id="instagram_url">
                <input type="hidden" name="facebook_url" id="facebook_url">
            </div>

            <div class="servicos-container">
                <label class="txt-labelService">Serviços oferecidos</label>
                <div class="servicos-opcoes">
                    <div class="coluna">
                        <div class="checkbox-container">
                            <input type="checkbox" name="servicos[]" value="customizacao">
                            <span>Customização</span>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" name="servicos[]" value="atelie sob medida">
                            <span>Ateliê Sob Medida</span>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" name="servicos[]" value="consertos e ajustes">
                            <span>Consertos e Ajustes</span>
                        </div>
                    </div>
                    <div class="coluna">
                        <div class="checkbox-container">
                            <input type="checkbox" name="servicos[]" value="estilista">
                            <span>Estilista</span>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" name="servicos[]" value="modelagem">
                            <span>Modelagem</span>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="outros" name="servicos[]" value="outros">
                            <span>Outros</span>
                            <input type="text" id="outros_servicos" name="outros_servicos" placeholder="Descreva os serviços adicionais" autocomplete="off" style="display:none;">
                        </div>
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