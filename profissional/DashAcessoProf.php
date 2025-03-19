<?php
// Inicia a sessão e verifica se o usuário está logado
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: loginProf.php");
    exit();
}

require '../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory())
->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();
$auth = $factory->createAuth();

// Recupera o e-mail da sessão
$email = $_SESSION['email'];

// Busca os dados do usuário no Firebase
$userData = $database->getReference('userProf')
    ->orderByChild('email')
    ->equalTo($email)
    ->getValue();

// Lógica para atualização de dados (se necessário)
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
                <img src="<?php echo htmlspecialchars($fotoPerfil ?? '../assets/img/perfil_cliente10.png', ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Foto de Perfil" class="profile-img">
            </div>

            <div class="data-section">
                <?php if ($userData): ?>
                <?php foreach ($userData as $user): ?>
                <div class="row">
                    <div class="col-md-6 dadosPessoais">
                        <div class="info-row-nome">
                            <div class="info-item"><i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row-end">
                            <div class="info-item"><i class="fas fa-house"></i>
                                <?php echo htmlspecialchars($user['rua'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row-complEnd">
                            <div class="info-item">
                                <?php echo htmlspecialchars($user['bairro'], ENT_QUOTES, 'UTF-8'); echo ",&nbsp"; ?>
                            </div>
                            <div class="info-item">
                                <?php echo htmlspecialchars($user['cidade'], ENT_QUOTES, 'UTF-8'); echo ",&nbsp"; ?>
                            </div>
                            <div class="info-item">
                                <?php echo htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8'); echo ",&nbsp"; ?>
                            </div>
                            <div class="info-item"><?php echo htmlspecialchars($user['cep'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="info-row-contato">
                            <div class="info-item"><i class="fas fa-mobile-alt"></i>
                                <?php echo htmlspecialchars($user['cel'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="info-item"><i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6 dadosSociais text-start">
                        <!-- Seção para exibir as redes sociais -->
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
                <?php endforeach; ?>
                <?php else: ?>
                <p>Nenhum dado encontrado.</p>
                <?php endif; ?>
            </div>

            <div class="logout text-center mt-3">
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>
    </div>
</body>

</html>