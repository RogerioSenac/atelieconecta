<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: loginProf.php");
    exit();
}

$email = $_SESSION['email'];

require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();

// Busca os dados do usuário no Firebase Realtime Database
try {
    // Consulta o nó "userProf" para encontrar o usuário pelo email no campo "acesso/email"
    $userData = $database->getReference('userProf')
        ->orderByChild('acesso/email')
        ->equalTo($email)
        ->getValue();

    if (empty($userData)) {
        throw new Exception("Nenhum dado encontrado para o usuário logado.");
    }

    // Obtém o primeiro resultado encontrado (deve ser único)
    $userKey = array_key_first($userData);
    $user = $userData[$userKey];

} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Profissional</title>
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="dash">
    <div class="container mt-5">
        <div class="profile-container">
            <div class="banner"></div>
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($user['fotoPerfil'] ?? '../assets/img/perfil_cliente10.png', ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Foto de Perfil" class="profile-img">
            </div>

            <div class="data-section">
                <div class="row">
                    <div class="col-md-6 dadosPessoais">
                        <div class="info-row-nome">
                            <div class="info-item"><i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row-end">
                            <div class="info-item"><i class="fas fa-house"></i>
                            <?php echo htmlspecialchars($user['endereco']['rua'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="info-row-complEnd">
                            <div class="info-item">
                                
                                <?php echo htmlspecialchars($user['endereco']['bairro'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="info-item">
                                
                                <?php echo htmlspecialchars($user['endereco']['cidade'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="info-item">
                                
                                <?php echo htmlspecialchars($user['endereco']['estado'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php echo htmlspecialchars($user['endereco']['cep'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="info-row-contato">
                            <div class="info-item"><i class="fas fa-mobile-alt"></i>
                                <?php echo htmlspecialchars($user['cel'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="info-item"><i class="fas fa-envelope"></i>
                                
                                <?php echo htmlspecialchars($user['acesso']['email'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <div class="col-md-6 dadosSociais text-start">
                        <p class="tagService mt-3">Redes Sociais:</p>
                        <div class="info-item"><i class="fab fa-whatsapp"></i>
                            <?php echo htmlspecialchars($user['redes_sociais']['whatsapp'] ?? 'Não disponível', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="info-item"><i class="fab fa-instagram"></i>
                            <?php echo htmlspecialchars($user['redes_sociais']['instagram'] ?? 'Não disponível', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="info-item"><i class="fab fa-facebook"></i>
                            <?php echo htmlspecialchars($user['redes_sociais']['facebook'] ?? 'Não disponível', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>

                <p class="tagService">Serviços Principais:</p>
                <div class="services-container">
                    <?php foreach ($user['servicos']['principais'] ?? [] as $servico): ?>
                    <div class="service-card">
                        <img src="../assets/img/icon_<?php echo strtolower(str_replace(' ', '', $servico)); ?>.png"
                            alt="<?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <p class="tagService mt-3">Outros Serviços:</p>
                <div class="services-container">
                    <?php foreach ($user['servicos']['outros'] ?? [] as $servico): ?>
                    <div class="service-card">
                        <img src="../assets/img/icon_outros.png" alt="Outros Serviços">
                        <span><?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="logout text-center mt-3">
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>
    </div>
</body>
</html>
