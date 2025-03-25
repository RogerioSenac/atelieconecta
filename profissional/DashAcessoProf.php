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

} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Função para validar e redimensionar imagem
function processarImagem($arquivo, $pastaDestino, $largura, $altura) {
    global $manager;
    
    // Verifica tipo de arquivo
    $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($arquivo['type'], $permitidos)) {
        throw new Exception("Tipo de arquivo não permitido. Use JPEG, PNG ou GIF.");
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

// Processa o upload da nova foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
    // --- ADICIONE ESTA VERIFICAÇÃO NO INÍCIO ---
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
    // --- ADICIONE ESTA VERIFICAÇÃO NO INÍCIO ---
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
    // --- ADICIONE ESTA VERIFICAÇÃO NO INÍCIO ---
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
    <style>
    .invalid-feedback {
        display: none;
        color: red;
    }

    .is-invalid~.invalid-feedback {
        display: block;
    }

    .was-validated .form-control:invalid~.invalid-feedback {
        display: block;
    }
    </style>
</head>

<body class="dash">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert"
        style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <?= $alert['message'] ?>
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
                                <input type="email" class="form-control" id="editEmail" name="editEmail" required>
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
                                <input type="text" class="form-control" id="editBairro" name="editBairro" required>
                                <div class="invalid-feedback">Por favor, insira o bairro.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="editCidade" name="editCidade" required>
                                <div class="invalid-feedback">Por favor, insira a cidade.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEstado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="editEstado" name="editEstado" required>
                                <div class="invalid-feedback">Por favor, insira o estado.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
    // Máscaras para os campos
    $(document).ready(function() {
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

        // Preenche o modal com os dados atuais
        document.getElementById("editIcon").addEventListener("click", function() {
            const modal = new bootstrap.Modal(document.getElementById("editModal"));

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

        // Upload de imagens
        document.getElementById('uploadImage').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('uploadForm').submit();
            }
        });

        document.getElementById('uploadBanner').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('uploadBannerForm').submit();
            }
        });
    });
    </script>
</body>

</html>