<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: loginProf.php");
    exit();
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
        ->equalTo($email) // Certifique-se de que $email é uma string válida
        ->getValue();

    if (empty($userData)) {
        throw new Exception("Nenhum dado encontrado para o usuário logado.");
    }

    $userKey = array_key_first($userData);
    $user = $userData[$userKey];

} catch (Exception $e) {
    die("Erro ao buscar dados do usuário: " . $e->getMessage());
}

// Função para redimensionar e salvar a imagem
function processarImagem($arquivo, $pastaDestino, $largura, $altura) {
    global $manager;

    $nomeArquivo = uniqid() . '.' . pathinfo($arquivo['name'], PATHINFO_EXTENSION); // Gera um nome único para o arquivo
    $caminhoCompleto = $pastaDestino . $nomeArquivo;

    // Redimensiona e salva a imagem
    $imagem = $manager->read($arquivo['tmp_name'])
        ->resize($largura, $altura) // Redimensiona a imagem para o tamanho desejado
        ->save($caminhoCompleto, 75); // Salva a imagem com 75% de qualidade (ajuste conforme necessário)

    return $caminhoCompleto;
}

// Processa o upload da nova foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/uploads/'; // Pasta onde as imagens serão salvas

    // Redimensiona e salva a imagem do perfil (exemplo: 200x200 pixels)
    try {
        $caminhoImagem = processarImagem($_FILES['fotoPerfil'], $uploadDir, 200, 200);

        // Atualiza o banco de dados com o novo caminho da imagem
        $database->getReference('userProf/' . $userKey . '/fotoPerfil')->set($caminhoImagem);

        // Atualiza a variável $user para refletir a nova foto
        $user['fotoPerfil'] = $caminhoImagem;

        echo "<script>alert('Foto de perfil atualizada com sucesso!');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Erro ao processar a imagem: " . $e->getMessage() . "');</script>";
    }
}

// Processa o upload da nova foto do banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoBanner']) && $_FILES['fotoBanner']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/uploads/'; // Pasta onde as imagens serão salvas

    // Redimensiona e salva a imagem do banner (exemplo: 1200x300 pixels)
    try {
        $caminhoImagem = processarImagem($_FILES['fotoBanner'], $uploadDir, 1200, 300);

        // Atualiza o banco de dados com o novo caminho da imagem
        $database->getReference('userProf/' . $userKey . '/fotoBanner')->set($caminhoImagem);

        // Atualiza a variável $user para refletir a nova foto
        $user['fotoBanner'] = $caminhoImagem;

        echo "<script>alert('Foto do banner atualizada com sucesso!');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Erro ao processar a imagem: " . $e->getMessage() . "');</script>";
    }
}

// Processa as alterações de dados do usuário (nome, celular, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editNome']) && isset($_POST['editCel'])) {
    // Captura os dados do formulário
    $novoNome = $_POST['editNome'];
    $novoCel = $_POST['editCel'];
    $novoEmail = $_POST['editEmail'];
    $novoCep = $_POST['editCep'];
    $novoRua = $_POST['editRua'];
    $novoBairro = $_POST['editBairro'];
    $novoCidade = $_POST['editCidade'];
    $novoEstado = $_POST['editEstado'];

    // Atualiza os dados no banco de dados
    try {
        $database->getReference('userProf/' . $userKey . '/nome')->set($novoNome);
        $database->getReference('userProf/' . $userKey . '/cel')->set($novoCel);
        $database->getReference('userProf/' . $userKey . '/acesso/email')->set($novoEmail);
        $database->getReference('userProf/' . $userKey . '/endereco/cep')->set($novoCep);
        $database->getReference('userProf/' . $userKey . '/endereco/rua')->set($novoRua);
        $database->getReference('userProf/' . $userKey . '/endereco/bairro')->set($novoBairro);
        $database->getReference('userProf/' . $userKey . '/endereco/cidade')->set($novoCidade);
        $database->getReference('userProf/' . $userKey . '/endereco/estado')->set($novoEstado);

        echo "<script>alert('Dados atualizados com sucesso!');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Erro ao atualizar dados: " . $e->getMessage() . "');</script>";
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
                        <input type="file" id="uploadImage" name="fotoPerfil" accept="image/*">
                    </form>
                </div>
            </div>

            <div class="data-section">
                <div class="row">
                    <div class="col-md-6 dadosPessoais">                    
                        <h4>Dados Pessoais <!-- Ícone para editar os dados pessoais -->
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
                <form id="editForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="editNome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="editNome">
                        </div>
                        <div class="col-md-6">
                            <label for="editCel" class="form-label">Celular</label>
                            <input type="text" class="form-control" id="editCel">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="editEmail">
                        </div>
                        <div class="col-md-6">
                            <label for="editCep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="editCep">
                        </div>
                    </div>

                    <h6 class="section-title">Endereço</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="editRua" class="form-label">Rua</label>
                            <input type="text" class="form-control" id="editRua">
                        </div>
                        <div class="col-md-6">
                            <label for="editBairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="editBairro">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label for="editCidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="editCidade">
                        </div>
                        <div class="col-md-6">
                            <label for="editEstado" class="form-label">Estado</label>
                            <input type="text" class="form-control" id="editEstado">
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
<script>
  // Esse código deve ser executado quando o modal for aberto
  $('#modalID').on('shown.bs.modal', function () {
      // Reaplica as máscaras aos campos do modal
      $('#campoCPF').mask('000.000.000-00');
      $('#campoTelefone').mask('(00) 00000-0000');
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
   document.getElementById("editIcon").addEventListener("click", function() {
    // Abre o modal com os dados preenchidos
    let modal = new bootstrap.Modal(document.getElementById("editModal"));
    
    // Preenche os campos do modal com os dados atuais
    document.getElementById("editNome").value = "<?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editCel").value = "<?php echo htmlspecialchars($user['cel'], ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editEmail").value = "<?php echo htmlspecialchars($user['acesso']['email'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editCep").value = "<?php echo htmlspecialchars($user['endereco']['cep'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editRua").value = "<?php echo htmlspecialchars($user['endereco']['rua'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editBairro").value = "<?php echo htmlspecialchars($user['endereco']['bairro'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editCidade").value = "<?php echo htmlspecialchars($user['endereco']['cidade'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    document.getElementById("editEstado").value = "<?php echo htmlspecialchars($user['endereco']['estado'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?>";
    
    // Exibe o modal
    modal.show();
});

    // Evento de submissão do formulário de edição
    document.getElementById("editForm").addEventListener("submit", function(event) {
        event.preventDefault(); // Evita o recarregamento da página
        
        // Captura os valores editados
        let formData = new FormData(this);
        
        fetch("backend/atualizar_dados.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Dados atualizados com sucesso!");
                location.reload(); // Atualiza a página para mostrar os dados atualizados
            } else {
                alert("Erro ao atualizar: " + data.message);
            }
        })
        .catch(error => console.error("Erro na requisição:", error));
    });
</script>


</body>

</html>