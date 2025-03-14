<?php
require '../vendor/autoload.php'; // Caminho para o autoload do Composer

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// Conectar ao Firebase
$factory = (new Factory())
    ->withServiceAccount('../chave.json')  // Caminho para o seu arquivo de chave JSON
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');  // URL do seu banco de dados Firebase

$database = $factory->createDatabase();

// Dados de serviços
$servicos = [
    ['nome' => 'Customizacao'],
    ['nome' => 'Atelie sob medida'],
    ['nome' => 'Consertos e ajustes'],
    ['nome' => 'Estilista'],
    ['nome' => 'Modelagem'],
    ['nome' => 'Outros'],
    ['nome' => 'Teste'],
];

// Referência onde os serviços serão inseridos
$servicosRef = $database->getReference('servicos');

// Inserindo os serviços no Firebase
foreach ($servicos as $servico) {
    $servicosRef->push([
        'nome' => $servico['nome']
    ]);
}

echo "Serviços inseridos com sucesso!";
?>
