<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: loginProf.php");
    exit();
}

// Gera token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtém o email da sessão
$email = $_SESSION['email'];

// Verifica se o email está definido e não está vazio
if (empty($email)) {
    die("Erro: Email do usuário não encontrado na sessão.");
}

// Inclui o autoload do Composer
require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

// Verifica se a extensão GD está habilitada
if (!extension_loaded('gd')) {
    die("Erro: A extensão GD não está habilitada no PHP. Habilite-a no php.ini e reinicie o servidor.");
}

// Configura a biblioteca Intervention Image com o driver GD
$manager = new ImageManager(new Driver());

$factory = (new Factory())
    ->withServiceAccount('../config/chave.json')
    ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

$database = $factory->createDatabase();

// Busca os dados do usuário no Firebase Realtime Database
try {
    $userData = $database->getReference('userProf')
        ->orderByChild('acesso/email')
        ->equalTo($email)
        ->getValue();

    if (empty($userData)) {
        throw new Exception("Nenhum dado encontrado para o usuário logado.");
    }

    $userKey = array_key_first($userData);
    $user = $userData[$userKey];

    // Garante que redes_sociais é um array e está com a estrutura correta
    if (!isset($user['redes_sociais']) || !is_array($user['redes_sociais'])) {
        $user['redes_sociais'] = [
            'whatsapp' => '',
            'instagram' => '',
            'facebook' => ''
        ];
    } else {
        // Garante que todos os campos existem
        $user['redes_sociais'] = array_merge([
            'whatsapp' => '',
            'instagram' => '',
            'facebook' => ''
        ], $user['redes_sociais']);
    }

} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Função para validar e redimensionar imagem
function processarImagem($arquivo, $pastaDestino, $largura, $altura) {
    global $manager;
    
    // Verifica tipo de arquivo
    $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($arquivo['type'], $permitidos)) {
        throw new Exception("Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WEBP.");
    }
    
    // Verifica tamanho do arquivo (máximo 5MB)
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new Exception("O arquivo é muito grande. Tamanho máximo: 5MB.");
    }
    
    $nomeArquivo = uniqid() . '.' . pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $caminhoCompleto = $pastaDestino . $nomeArquivo;

    try {
        $imagem = $manager->read($arquivo['tmp_name'])
            ->resize($largura, $altura)
            ->save($caminhoCompleto, 75);
            
        return $caminhoCompleto;
    } catch (Exception $e) {
        throw new Exception("Erro ao processar imagem: " . $e->getMessage());
    }
}

// Funções para tratamento de redes sociais
function formatSocialLink($value, $type) {
    if (empty($value)) return 'Não disponível';
    
    $value = (string)$value; // Garante que é string
    
    switch ($type) {
        case 'whatsapp':
            // Remove tudo que não é número
            $numero = preg_replace('/[^0-9]/', '', $value);
            
            // Verifica se tem código do país (padrão: 55 para Brasil)
            if (strlen($numero) > 11) {
                $codigoPais = substr($numero, 0, 2);
                $restante = substr($numero, 2);
            } else {
                $codigoPais = '55'; // Assume Brasil se não tiver código
                $restante = $numero;
            }
            
            // Formata o número (XX) XXXXX-XXXX
            if (strlen($restante) === 11) {
                return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $restante);
            }
            return $restante; // Retorna sem formatação se não tiver tamanho esperado
            
        case 'instagram':
            $perfil = str_replace(
                ['https://instagram.com/', 'https://www.instagram.com/', '@'], 
                '', 
                $value
            );
            return '@' . $perfil;
            
        case 'facebook':
            // Extrai apenas o nome do perfil (remove URLs)
            $perfil = str_replace(
                ['https://facebook.com/', 'https://www.facebook.com/', 'facebook.com/'], 
                '', 
                $value
            );
            return $perfil; // Mostra apenas o nome sem o domínio
            
        default:
            return $value;
    }
}

function getSocialLink($value, $type) {
    if (empty($value)) return '#';
    
    $value = (string)$value;
    $value = trim($value);
    
    switch ($type) {
        case 'whatsapp':
            // Remove tudo que não é número
            $numero = preg_replace('/[^0-9]/', '', $value);
            
            // Verifica se já tem código do país
            if (strlen($numero) <= 11) {
                $numero = '55' . $numero; // Adiciona código do Brasil se não tiver
            }
            return 'https://wa.me/' . $numero;
            
        case 'instagram':
            $usuario = str_replace(['@', 'https://instagram.com/', 'https://www.instagram.com/'], '', $value);
            return 'https://instagram.com/' . $usuario;
            
        case 'facebook':
            // Remove todos os prefixos possíveis
            $perfil = str_replace(
                ['https://facebook.com/', 'https://www.facebook.com/', 'facebook.com/'], 
                '', 
                $value
            );
            return 'https://www.facebook.com/' . $perfil;
            
        default:
            return '#';
    }
}

// Processa o upload da nova foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }
    
    $uploadDir = '../assets/uploads/';
    
    try {
        $caminhoImagem = processarImagem($_FILES['fotoPerfil'], $uploadDir, 200, 200);
        $database->getReference('userProf/' . $userKey . '/fotoPerfil')->set($caminhoImagem);
        $user['fotoPerfil'] = $caminhoImagem;
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Foto de perfil atualizada com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao processar a imagem: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

// Processa o upload da nova foto do banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoBanner']) && $_FILES['fotoBanner']['error'] === UPLOAD_ERR_OK) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }
    
    $uploadDir = '../assets/uploads/';
    
    try {
        $caminhoImagem = processarImagem($_FILES['fotoBanner'], $uploadDir, 1200, 300);
        $database->getReference('userProf/' . $userKey . '/fotoBanner')->set($caminhoImagem);
        $user['fotoBanner'] = $caminhoImagem;
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Foto do banner atualizada com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao processar a imagem: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

// Processa as alterações de dados do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editNome']) && isset($_POST['editCel'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }
    
    // Validação dos dados
    $novoNome = filter_var($_POST['editNome'], FILTER_SANITIZE_STRING);
    $novoCel = filter_var($_POST['editCel'], FILTER_SANITIZE_STRING);
    $novoEmail = filter_var($_POST['editEmail'], FILTER_VALIDATE_EMAIL);
    $novoCep = filter_var($_POST['editCep'], FILTER_SANITIZE_STRING);
    $novoRua = filter_var($_POST['editRua'], FILTER_SANITIZE_STRING);
    $novoBairro = filter_var($_POST['editBairro'], FILTER_SANITIZE_STRING);
    $novoCidade = filter_var($_POST['editCidade'], FILTER_SANITIZE_STRING);
    $novoEstado = filter_var($_POST['editEstado'], FILTER_SANITIZE_STRING);
    
    if (!$novoEmail) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'E-mail inválido!'];
        header("Location: dashAcessoProf.php");
        exit();
    }
    
    try {
        $updates = [
            'nome' => $novoNome,
            'cel' => $novoCel,
            'acesso/email' => $novoEmail,
            'endereco/cep' => $novoCep,
            'endereco/rua' => $novoRua,
            'endereco/bairro' => $novoBairro,
            'endereco/cidade' => $novoCidade,
            'endereco/estado' => $novoEstado
        ];
        
        $database->getReference('userProf/' . $userKey)->update($updates);
        
        // Atualiza a sessão
        $_SESSION['email'] = $novoEmail;
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Dados atualizados com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao atualizar dados: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

// Processa edição das redes sociais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['whatsapp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }

    try {
        // Formata os dados antes de salvar
        $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp']);
        $instagram = str_replace('@', '', $_POST['instagram']);
        
        $redesSociais = [
            'whatsapp' => $whatsapp,
            'instagram' => $instagram,
            'facebook' => $_POST['facebook']
        ];

        $database->getReference('userProf/' . $userKey . '/redes_sociais')->set($redesSociais);
        
        // Atualiza localmente para não precisar recarregar
        $user['redes_sociais'] = $redesSociais;
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Redes sociais atualizadas com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao atualizar redes sociais: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

// Processa alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['currentPassword'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }

    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Verifica se a senha atual está correta
    if (!password_verify($currentPassword, $user['acesso']['senha'])) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Senha atual incorreta!'];
        header("Location: dashAcessoProf.php");
        exit();
    }

    // Verifica se as novas senhas coincidem
    if ($newPassword !== $confirmPassword) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'As novas senhas não coincidem!'];
        header("Location: dashAcessoProf.php");
        exit();
    }

    // Verifica força da senha (opcional)
    if (strlen($newPassword) < 8) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'A senha deve ter pelo menos 8 caracteres!'];
        header("Location: dashAcessoProf.php");
        exit();
    }

    try {
        // Atualiza a senha
        $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $database->getReference('userProf/' . $userKey . '/acesso/senha')->set($newHashedPassword);
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Senha alterada com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao atualizar senha: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

/// Processa alteração de serviços principais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['servicosPrincipais'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }

    try {
        // Processa os serviços principais
        $servicosPrincipais = [];
        
        // Verifica se foi enviado algum serviço (pode ser array vazio)
        if (isset($_POST['servicosPrincipais']) && is_array($_POST['servicosPrincipais'])) {
            $servicosPrincipais = array_unique(array_filter(array_map('trim', $_POST['servicosPrincipais'])));
        }
        
        // Prepara os dados para atualização
        $updates = [
            'servicos/principais' => !empty($servicosPrincipais) ? array_values($servicosPrincipais) : null
        ];
        
        // Atualiza no Firebase
        $database->getReference('userProf/' . $userKey)->update($updates);
        
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Serviços principais atualizados com sucesso!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Erro ao atualizar serviços principais: ' . $e->getMessage()
        ];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}

// Busca os serviços disponíveis no Firebase
try {
    $servicosRef = $database->getReference('servicos');
    $servicosDisponiveis = $servicosRef->getValue();
    
    // Verifica se é array e não está vazio
    if (!is_array($servicosDisponiveis)) {
        $servicosDisponiveis = [];
    }
    
} catch (Exception $e) {
    $servicosDisponiveis = [];
    // Você pode adicionar um log de erro se necessário
}

// Inicio Logica Outros Serviços
// Processa alteração de outros serviços
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['outrosServicos'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Token de segurança inválido. Tente novamente.'
        ];
        header("Location: dashAcessoProf.php");
        exit();
    }

    try {
        $outrosServicos = array_values(array_filter(array_map('trim', $_POST['outrosServicos']), 'strlen'));
        $updates = [
            'servicos/outros' => !empty($outrosServicos) ? $outrosServicos : null
        ];
        $database->getReference('userProf/' . $userKey)->update($updates);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Outros serviços atualizados!'];
    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao atualizar outros serviços: ' . $e->getMessage()];
    }
    
    header("Location: dashAcessoProf.php");
    exit();
}
// Fim Logica Outros Serviços



// Exibe alertas se existirem
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
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
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert"
        style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <?= htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
    setTimeout(() => document.querySelector('.alert').remove(), 5000);
    </script>
    <?php endif; ?>
    <div class="container mt-5">
        <div class="profile-container">
            <!-- Banner com ícone de câmera para upload -->
            <div class="banner-container">
                <div class="banner"
                    style="background-image: url('<?php echo htmlspecialchars($user['fotoBanner'] ?? '../assets/img/loginUsuario10.jpeg', ENT_QUOTES, 'UTF-8'); ?>');">
                </div>
                <label for="uploadBanner" class="camera-icon-banner">
                    <i class="fas fa-camera"></i>
                </label>
                <form id="uploadBannerForm" action="dashAcessoProf.php" method="POST" enctype="multipart/form-data"
                    style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="file" id="uploadBanner" name="fotoBanner" accept="image/*">
                </form>
                <img id="bannerPreview" class="preview-image" alt="Pré-visualização do banner">
            </div>

            <!-- Foto de perfil com ícone de câmera para upload -->
            <div class="profile-header">
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($user['fotoPerfil'] ?? '../assets/img/perfil_cliente10.png', ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Foto de Perfil" class="profile-img" id="profileImage">
                    <label for="uploadImage" class="camera-icon">
                        <i class="fas fa-camera"></i>
                    </label>
                    <form id="uploadForm" action="dashAcessoProf.php" method="POST" enctype="multipart/form-data"
                        style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="file" id="uploadImage" name="fotoPerfil" accept="image/*">
                    </form>
                    <img id="profilePreview" class="preview-image" alt="Pré-visualização da foto de perfil">
                </div>
            </div>

            <div class="data-section">
                <div class="row">
                    <div class="col-md-6 dadosPessoais">
                        <h4>Dados Pessoais
                            <!-- Ícone para editar os dados pessoais -->
                            <i class="fas fa-edit" id="editIcon" style="cursor: pointer;"></i>
                        </h4>
                        <div class="info-row-nome">
                            <div class="info-item"><i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row-end">
                            <div class="info-item"><i class="fas fa-house"></i>
                                <?php echo htmlspecialchars($user['endereco']['rua'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
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
                        <div class="info-row-contato">
                            <div class="info-item"><i class="fas fa-mobile-alt"></i>
                                <?php echo htmlspecialchars($user['cel'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="info-row-contato">
                            <div class="info-item"><i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($user['acesso']['email'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 dadosSociais text-start">
                        <p class="tagService mt-3">Redes Sociais:
                            <i class="fas fa-edit ms-2" id="editRedesIcon" style="cursor: pointer;"></i>
                        </p>

                        <div class="info-item">
                            <i class="fab fa-whatsapp"></i>
                            <span><?= htmlspecialchars(formatSocialLink($user['redes_sociais']['whatsapp'], 'whatsapp'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($user['redes_sociais']['whatsapp'])): ?>
                            <a href="<?= htmlspecialchars(getSocialLink($user['redes_sociais']['whatsapp'], 'whatsapp'), ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank">
                                <i class="fas fa-external-link-alt ms-2" style="color: #6c757d; cursor: pointer;"></i>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="info-item">
                            <i class="fab fa-instagram"></i>
                            <span><?= htmlspecialchars(formatSocialLink($user['redes_sociais']['instagram'], 'instagram'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($user['redes_sociais']['instagram'])): ?>
                            <a href="<?= htmlspecialchars(getSocialLink($user['redes_sociais']['instagram'], 'instagram'), ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank">
                                <i class="fas fa-external-link-alt ms-2" style="color: #6c757d; cursor: pointer;"></i>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="info-item">
                            <i class="fab fa-facebook"></i>
                            <span><?= htmlspecialchars(formatSocialLink($user['redes_sociais']['facebook'], 'facebook'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($user['redes_sociais']['facebook'])): ?>
                            <a href="<?= htmlspecialchars(getSocialLink($user['redes_sociais']['facebook'], 'facebook'), ENT_QUOTES, 'UTF-8') ?>"
                                target="_blank">
                                <i class="fas fa-external-link-alt ms-2" style="color: #6c757d; cursor: pointer;"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Inicio dos Serviços Principais -->
                    <p class="tagService">Serviços Principais:
                        <i class="fas fa-edit ms-2" id="editServicosIcon" style="cursor: pointer;"></i>
                    </p>
                    <div class="services-container">
                        <?php if (!empty($user['servicos']['principais'])): ?>
                        <?php foreach ($user['servicos']['principais'] as $servico): ?>
                        <div class="service-card">
                            <img src="../assets/img/icon_<?php echo strtolower(str_replace(' ', '', $servico)); ?>.png"
                                alt="<?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?>">
                            <span><?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="alert alert-info">Até o momento nenhum serviço principal selecionado.</div>
                        <?php endif; ?>
                    </div>
                    <!-- Fim dos Serviços Principais -->

                    <!-- Inicio dos Outros Serviços -->
                    <!-- Seção Outros Serviços -->
                    <p class="tagService mt-4">Outros Serviços:
                        <i class="fas fa-edit ms-2" id="editOutrosServicosIcon" style="cursor: pointer;"></i>
                    </p>
                    <div class="services-container">
                        <?php if (!empty($user['servicos']['outros'])): ?>
                        <?php foreach ($user['servicos']['outros'] as $servico): ?>
                        <div class="service-card">
                            <img src="../assets/img/icon_outros.png" alt="Outros Serviços">
                            <span><?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="alert alert-info">Nenhum outro serviço cadastrado.</div>
                        <?php endif; ?>
                    </div>
                    <!-- Fim dos Outros Serviços -->

                    <!-- inicio do Sair -->
                    <div class="logout text-center mt-3">
                        <button id="logoutBtn" class="btn btn-danger">Sair</button>
                    </div>
                    <!-- fim do Sair -->
                </div>
            </div>
            <!-- Modal de Edição de Dados Pessoais -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content custom-modal">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Editar Dados Pessoais</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editForm" method="POST" action="dashAcessoProf.php" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editNome" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="editNome" name="editNome" required>
                                        <div class="invalid-feedback">Por favor, insira seu nome.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editCel" class="form-label">Celular</label>
                                        <input type="text" class="form-control" id="editCel" name="editCel" required>
                                        <div class="invalid-feedback">Por favor, insira um celular válido.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editEmail" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="editEmail" name="editEmail"
                                            required>
                                        <div class="invalid-feedback">Por favor, insira um e-mail válido.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editCep" class="form-label">CEP</label>
                                        <input type="text" class="form-control" id="editCep" name="editCep" required>
                                        <div class="invalid-feedback">Por favor, insira um CEP válido.</div>
                                    </div>
                                </div>
                                <h6 class="section-title">Endereço</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editRua" class="form-label">Rua</label>
                                        <input type="text" class="form-control" id="editRua" name="editRua" required>
                                        <div class="invalid-feedback">Por favor, insira a rua.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editBairro" class="form-label">Bairro</label>
                                        <input type="text" class="form-control" id="editBairro" name="editBairro"
                                            required>
                                        <div class="invalid-feedback">Por favor, insira o bairro.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editCidade" class="form-label">Cidade</label>
                                        <input type="text" class="form-control" id="editCidade" name="editCidade"
                                            required>
                                        <div class="invalid-feedback">Por favor, insira a cidade.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editEstado" class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="editEstado" name="editEstado"
                                            required>
                                        <div class="invalid-feedback">Por favor, insira o estado.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Redes Sociais -->
            <div class="modal fade" id="redesModal" tabindex="-1" aria-labelledby="redesModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content custom-modal">
                        <div class="modal-header">
                            <h5 class="modal-title" id="redesModalLabel">Editar Redes Sociais</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="redesForm" method="POST" action="dashAcessoProf.php">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-whatsapp me-2"></i>WhatsApp</label>
                                    <input type="text" class="form-control" name="whatsapp"
                                        value="<?= htmlspecialchars($user['redes_sociais']['whatsapp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-instagram me-2"></i>Instagram</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control" name="instagram"
                                            value="<?= htmlspecialchars(str_replace('@', '', $user['redes_sociais']['instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-facebook me-2"></i>Facebook</label>
                                    <input type="text" class="form-control" name="facebook"
                                        value="<?= htmlspecialchars($user['redes_sociais']['facebook'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Alterar Senha -->
            <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content custom-modal">
                        <div class="modal-header">
                            <h5 class="modal-title" id="passwordModalLabel">Alterar Senha</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="passwordForm" method="POST" action="dashAcessoProf.php">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" id="currentPassword"
                                        name="currentPassword" required>
                                    <div class="invalid-feedback">Por favor, insira sua senha atual.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword"
                                        required>
                                    <div class="invalid-feedback">Por favor, insira uma nova senha.</div>
                                    <div class="password-strength">
                                        <span></span>
                                    </div>
                                    <small class="text-muted">A senha deve ter pelo menos 8 caracteres.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmPassword"
                                        name="confirmPassword" required>
                                    <div class="invalid-feedback">As senhas não coincidem.</div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Alterar Senha</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Inicio Modal Editar Serviços -->
            <div class="modal fade" id="servicosModal" tabindex="-1" aria-labelledby="servicosModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content custom-modal">
                        <div class="modal-header">
                            <h5 class="modal-title" id="servicosModalLabel">Selecionar Serviços Principais</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="servicosForm" method="POST" action="dashAcessoProf.php">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                                <div class="mb-3">
                                    <p class="mb-3">Marque os serviços que você oferece:</p>
                                    <?php if (!empty($servicosDisponiveis)): ?>
                                    <div class="row" id="servicosDisponiveis">
                                        <?php
                                $servicosUsuario = $user['servicos']['principais'] ?? [];
                                foreach ($servicosDisponiveis as $servicoId => $servicoData) {
                                    $nomeServico = $servicoData['nome'] ?? $servicoId;
                                    $checked = in_array($nomeServico, $servicosUsuario) ? 'checked' : '';
                                    echo '<div class="col-md-4 mb-3">';
                                    echo '<div class="form-check d-flex align-items-center">';
                                    echo '<input class="form-check-input me-2" type="checkbox" name="servicosPrincipais[]" style="width: 18px; height: 18px;" ';
                                    echo 'value="'.htmlspecialchars($nomeServico, ENT_QUOTES, 'UTF-8').'" id="servico_'.$servicoId.'" '.$checked.'>';
                                    echo '<label class="form-check-label" for="servico_'.$servicoId.'">';
                                    echo htmlspecialchars($nomeServico, ENT_QUOTES, 'UTF-8');
                                    echo '</label>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning">Nenhum serviço disponível encontrado</div>
                                    <?php endif; ?>

                                    <?php if (empty($user['servicos']['principais'])): ?>
                                    <div class="alert alert-info mt-3">Até o momento nenhum serviço principal
                                        selecionado.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Salvar Seleção</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            </form>
        </div>
        <!-- Inicio Modal Outros Serviços -->
        <!-- Modal Outros Serviços -->
        <div class="modal fade" id="outrosServicosModal" tabindex="-1" aria-labelledby="outrosServicosModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content custom-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="outrosServicosModalLabel">Editar Outros Serviços</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="outrosServicosForm" method="POST" action="dashAcessoProf.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="mb-3">
                                <p class="mb-3">Adicione outros serviços que você oferece:</p>
                                <div id="outrosServicosContainer">
                                    <?php if (!empty($user['servicos']['outros'])): ?>
                                    <?php foreach ($user['servicos']['outros'] as $index => $servico): ?>
                                    <div class="input-group mb-2 servico-input">
                                        <input type="text" class="form-control" name="outrosServicos[]"
                                            value="<?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="button" class="btn btn-danger remover-servico">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <div class="input-group mb-2 servico-input">
                                        <input type="text" class="form-control" name="outrosServicos[]">
                                        <button type="button" class="btn btn-danger remover-servico">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <button type="button" id="adicionarOutroServico" class="btn btn-sm btn-secondary mt-2">
                                    <i class="fas fa-plus me-1"></i> Adicionar outro serviço
                                </button>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fim Modal Outros Serviços -->


        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
        // Substitua todo o código JavaScript por este:

        <script>
        $(document).ready(function() {
            // Máscaras para os campos
            $('#editCel').mask('(00) 00000-0000');
            $('#editCep').mask('00000-000');

            // Validação do formulário
            document.getElementById('editForm').addEventListener('submit', function(event) {
                const form = event.target;
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add('was-validated');
                }
            });

            // Modal Dados Pessoais - CÓDIGO CORRIGIDO
            document.getElementById("editIcon").addEventListener("click", function() {
                const modal = new bootstrap.Modal(document.getElementById("editModal"));

                // Preenche os campos do formulário
                document.getElementById("editNome").value =
                    "<?= htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editCel").value =
                    "<?= htmlspecialchars($user['cel'], ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editEmail").value =
                    "<?= htmlspecialchars($user['acesso']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editCep").value =
                    "<?= htmlspecialchars($user['endereco']['cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editRua").value =
                    "<?= htmlspecialchars($user['endereco']['rua'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editBairro").value =
                    "<?= htmlspecialchars($user['endereco']['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editCidade").value =
                    "<?= htmlspecialchars($user['endereco']['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
                document.getElementById("editEstado").value =
                    "<?= htmlspecialchars($user['endereco']['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>";

                modal.show();
            });

            // Modal redes sociais
            document.getElementById("editRedesIcon").addEventListener("click", function() {
                new bootstrap.Modal(document.getElementById("redesModal")).show();
            });

            // Modal serviços principais
            document.getElementById("editServicosIcon").addEventListener("click", function() {
                new bootstrap.Modal(document.getElementById("servicosModal")).show();
            });

            // Modal outros serviços
            document.getElementById("editOutrosServicosIcon").addEventListener("click", function() {
                new bootstrap.Modal(document.getElementById("outrosServicosModal")).show();
            });

            // Upload de imagens
            document.getElementById('uploadImage').addEventListener('change', function() {
                if (this.files && this.files[0]) document.getElementById('uploadForm').submit();
            });

            document.getElementById('uploadBanner').addEventListener('change', function() {
                if (this.files && this.files[0]) document.getElementById('uploadBannerForm').submit();
            });

            // Envio do formulário de serviços principais
            document.getElementById("servicosForm").addEventListener("submit", function(e) {
                // Cria um campo hidden se nenhum checkbox estiver selecionado
                if (document.querySelectorAll('#servicosDisponiveis input[type="checkbox"]:checked')
                    .length === 0) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'servicosPrincipais[]';
                    hiddenInput.value = '';
                    this.appendChild(hiddenInput);
                }

                // Continua com o envio normal
                e.preventDefault();
                const formData = new FormData(this);

                fetch("dashAcessoProf.php", {
                    method: "POST",
                    body: formData
                }).then(response => {
                    if (response.ok) location.reload();
                    else alert("Erro ao salvar serviços principais");
                }).catch(error => console.error("Error:", error));
            });

            // Envio do formulário de outros serviços
            document.getElementById("outrosServicosForm").addEventListener("submit", function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch("dashAcessoProf.php", {
                    method: "POST",
                    body: formData
                }).then(response => {
                    if (response.ok) location.reload();
                    else alert("Erro ao salvar outros serviços");
                }).catch(error => console.error("Error:", error));
            });

            // Adicionar outro serviço
            document.getElementById("adicionarOutroServico").addEventListener("click", function() {
                const container = document.getElementById("outrosServicosContainer");
                const novoInput = document.createElement("div");
                novoInput.className = "input-group mb-2 servico-input";
                novoInput.innerHTML = `
            <input type="text" class="form-control" name="outrosServicos[]">
            <button type="button" class="btn btn-danger remover-servico">
                <i class="fas fa-trash"></i>
            </button>
        `;
                container.appendChild(novoInput);
            });

            // Remover serviço
            document.addEventListener("click", function(e) {
                if (e.target.closest(".remover-servico")) {
                    const inputGroup = e.target.closest(".servico-input");
                    if (inputGroup) {
                        const container = inputGroup.parentElement;
                        if (container.querySelectorAll('.servico-input').length > 1) {
                            inputGroup.remove();
                        } else {
                            inputGroup.querySelector('input').value = '';
                        }
                    }
                }
            });

            // Logout
            // Logout seguro com CSRF
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();

                if (confirm('Tem certeza que deseja sair do sistema?')) {
                    // Cria um formulário temporário para enviar o token CSRF
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../logout.php';

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'csrf_token';
                    tokenInput.value = '<?= $_SESSION['csrf_token'] ?>';

                    form.appendChild(tokenInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }); // Esta chave fecha o $(document).ready que estava faltando
        </script>


</body>

</html>