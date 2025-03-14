<?php
require '../vendor/autoload.php'; // Certifique-se de ter o autoload do Composer configurado corretamente

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// A lista de estados do Brasil
$estadosBrasil = [
    "AC" => "Acre",
    "AL" => "Alagoas",
    "AP" => "Amapá",
    "AM" => "Amazonas",
    "BA" => "Bahia",
    "CE" => "Ceará",
    "DF" => "Distrito Federal",
    "ES" => "Espírito Santo",
    "GO" => "Goiás",
    "MA" => "Maranhão",
    "MT" => "Mato Grosso",
    "MS" => "Mato Grosso do Sul",
    "MG" => "Minas Gerais",
    "PA" => "Pará",
    "PB" => "Paraíba",
    "PR" => "Paraná",
    "PE" => "Pernambuco",
    "PI" => "Piauí",
    "RJ" => "Rio de Janeiro",
    "RN" => "Rio Grande do Norte",
    "RS" => "Rio Grande do Sul",
    "RO" => "Rondônia",
    "RR" => "Roraima",
    "SC" => "Santa Catarina",
    "SP" => "São Paulo",
    "SE" => "Sergipe",
    "TO" => "Tocantins"
];

// URL do seu banco de dados Firebase
$databaseUrl = 'https://atelieconecta-d9030-default-rtdb.firebaseio.com/'; // Substitua com a URL do seu banco de dados

// Autenticação do Firebase
$firebase = (new Factory)
    ->withServiceAccount('../chave.json') // Caminho para o arquivo JSON da chave do Firebase
    ->withDatabaseUri($databaseUrl) // Especifica o URL correto do banco de dados
    ->createDatabase();

// Inserindo os estados no Firebase
try {
    $database = $firebase->getReference('estados');
    $database->set($estadosBrasil); // Inserindo todos os estados

    echo "Estados do Brasil inseridos com sucesso!";
} catch (Exception $e) {
    echo "Erro ao inserir estados: " . $e->getMessage();
}
?>
