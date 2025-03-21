<?php
session_start();
if (!isset($_SESSION['logado'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$email = $_SESSION['email'];

require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();

// Verifica se uma imagem foi enviada
if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/uploads/'; // Pasta onde as imagens serão salvas
    $uploadFile = $uploadDir . basename($_FILES['fotoPerfil']['name']);

    // Move a imagem para a pasta de uploads
    if (move_uploaded_file($_FILES['fotoPerfil']['tmp_name'], $uploadFile)) {
        // Atualiza o banco de dados com o novo caminho da imagem
        $userData = $database->getReference('userProf')
            ->orderByChild('acesso/email')
            ->equalTo($email)
            ->getValue();

        if (!empty($userData)) {
            $userKey = array_key_first($userData);
            $database->getReference('userProf/' . $userKey . '/fotoPerfil')->set($uploadFile);

            echo json_encode(['success' => true, 'message' => 'Foto de perfil atualizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao mover o arquivo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada.']);
}
?>