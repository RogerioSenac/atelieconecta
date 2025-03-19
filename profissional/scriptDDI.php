<?php
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// Lista de códigos DDI (exemplo com alguns países)
$codigosDDI = [
    "93" => "Afeganistão",
"355" => "Albânia",
"49" => "Alemanha",
"376" => "Andorra",
"244" => "Angola",
"1-268" => "Antígona e Barbuda",
"54" => "Argentina",
"374" => "Armênia",
"61" => "Austrália",
"43" => "Áustria",
"994" => "Azerbaijão",
"1-242" => "Bahamas",
"973" => "Bahrein",
"880" => "Bangladesh",
"1-246" => "Barbados",
"32" => "Bélgica",
"375" => "Bielorrússia",
"591" => "Bolívia",
"387" => "Bósnia e Herzegovina",
"267" => "Botsuana",
"55" => "Brasil",
"673" => "Brunei",
"359" => "Bulgária",
"238" => "Cabo Verde",
"237" => "Camarões",
"1" => "Canadá",
"7" => "Cazaquistão",
"56" => "Chile",
"86" => "China",
"57" => "Colômbia",
"242" => "Congo (República do)",
"243" => "Congo (República Democrática do Congo)",
"82" => "Coréia do Sul",
"506" => "Costa Rica",
"225" => "Côte d'Ivoire",
"385" => "Croácia",
"45" => "Dinamarca",
"20" => "Egito",
"503" => "El Salvador",
"971" => "Emirados Árabes Unidos",
"593" => "Equador",
"44" => "Escócia",
"34" => "Espanha",
"1" => "Estados Unidos",
"372" => "Estônia",
"970" => "Faixa de Gaza",
"63" => "Filipinas",
"358" => "Finlândia",
"33" => "França",
"995" => "Geórgia",
"233" => "Gana",
"30" => "Grécia",
"502" => "Guatemala",
"592" => "Guiana",
"504" => "Honduras",
"36" => "Hungria",
"354" => "Islândia",
"91" => "Índia",
"62" => "Indonésia",
"98" => "Irã",
"353" => "Irlanda",
"354" => "Islândia",
"39" => "Itália",
"1-876" => "Jamaica",
"81" => "Japão",
"962" => "Jordânia",
"371" => "Letônia",
"961" => "Líbano",
"231" => "Libéria",
"370" => "Lituânia",
"352" => "Luxemburgo",
"60" => "Malásia",
"265" => "Malawi",
"356" => "Malta",
"212" => "Marrocos",
"230" => "Maurício",
"52" => "México",
"258" => "Moçambique",
"373" => "Moldávia",
"976" => "Mongólia",
"47" => "Noruega",
"64" => "Nova Zelândia",
"92" => "Paquistão",
"507" => "Panamá",
"595" => "Paraguai",
"51" => "Peru",
"48" => "Polônia",
"351" => "Portugal",
"254" => "Quênia",
"1-809" => "República Dominicana",
"420" => "República Tcheca",
"40" => "Romênia",
"7" => "Rússia",
"221" => "Senegal",
"232" => "Serra Leoa",
"65" => "Singapura",
"963" => "Síria",
"94" => "Sri Lanka",
"46" => "Suécia",
"41" => "Suíça",
"66" => "Tailândia",
"90" => "Turquia",
"380" => "Ucrânia",
"598" => "Uruguai",
"58" => "Venezuela",
"84" => "Vietnã",
"260" => "Zâmbia",
"263" => "Zimbábue",

];

// Conectar ao Firebase
$firebase = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/')
    ->createDatabase();

// Inserir os códigos DDI no Firebase
try {
    $database = $firebase->getReference('codigos_ddi');
    $database->set($codigosDDI); // Inserir os DDI no Firebase
    echo "Códigos DDI inseridos com sucesso!";
} catch (Exception $e) {
    echo "Erro ao inserir códigos DDI: " . $e->getMessage();
}
?>
