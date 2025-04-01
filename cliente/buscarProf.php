<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

// Verifica se o usuário está logado
if (!isset($_SESSION['email'])) {
    header('Location: loginProf.php');
    exit();
}

// Gera token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = "";
$error = "";
$profissionais = [];
$cidadesUnicas = [];
$bairrosUnicos = [];
$servicosDisponiveis = [];

try {
    // Conectar ao Firebase
    $factory = (new Factory())
        ->withServiceAccount('../config/chave.json')
        ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

    $database = $factory->createDatabase();

    // Buscar todos os profissionais, serviços disponíveis e dados para filtros
    $profissionaisRef = $database->getReference('userProf');
    $todosProfissionais = $profissionaisRef->getValue();

    // Buscar serviços disponíveis (exceto "outros")
    $servicosRef = $database->getReference('servicos');
    $todosServicos = $servicosRef->getValue();

    if ($todosServicos) {
        foreach ($todosServicos as $servico) {
            if ($servico['nome'] != "outros") {
                $servicosDisponiveis[] = $servico['nome'];
            }
        }
        sort($servicosDisponiveis);
    }

    if ($todosProfissionais) {
        // Processar para obter cidades e bairros únicos (case-insensitive)
        $cidades = [];
        $bairros = [];

        foreach ($todosProfissionais as $profissional) {
            if (isset($profissional['endereco']['cidade'])) {
                $cidadeLower = mb_strtolower($profissional['endereco']['cidade'], 'UTF-8');
                $cidades[$cidadeLower] = $profissional['endereco']['cidade']; // Guarda o valor original
            }
            if (isset($profissional['endereco']['bairro'])) {
                $bairroLower = mb_strtolower($profissional['endereco']['bairro'], 'UTF-8');
                $bairros[$bairroLower] = $profissional['endereco']['bairro']; // Guarda o valor original
            }
        }

        // Manter os valores originais mas ordenados
        $cidadesUnicas = array_values($cidades);
        $bairrosUnicos = array_values($bairros);

        // Ordenar mantendo a acentuação correta
        usort($cidadesUnicas, function ($a, $b) {
            return strcmp(
                iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($a, 'UTF-8')),
                iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($b, 'UTF-8'))
            );
        });

        usort($bairrosUnicos, function ($a, $b) {
            return strcmp(
                iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($a, 'UTF-8')),
                iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower($b, 'UTF-8'))
            );
        });
    }

    // Processar busca se houver submissão do formulário
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $cidadeFiltro = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $bairroFiltro = isset($_POST['bairro']) ? trim($_POST['bairro']) : '';
        $servicosFiltro = isset($_POST['servicos']) ? $_POST['servicos'] : [];

        // Converter filtros para lowercase para comparação case-insensitive
        $cidadeFiltroLower = mb_strtolower($cidadeFiltro, 'UTF-8');
        $bairroFiltroLower = mb_strtolower($bairroFiltro, 'UTF-8');

        // Construir query - agora buscamos todos e filtramos localmente para case-insensitive
        $profissionaisSnapshot = $profissionaisRef->getValue();

        // Aplicar filtros
        $profissionais = [];
        foreach ($profissionaisSnapshot as $key => $profissional) {
            $passouFiltros = true;

            // Verificar filtro de cidade (case-insensitive)
            if (!empty($cidadeFiltro)) {
                if (
                    !isset($profissional['endereco']['cidade']) ||
                    mb_strtolower($profissional['endereco']['cidade'], 'UTF-8') !== $cidadeFiltroLower
                ) {
                    $passouFiltros = false;
                }
            }

            // Verificar filtro de bairro (case-insensitive)
            if ($passouFiltros && !empty($bairroFiltro)) {
                if (
                    !isset($profissional['endereco']['bairro']) ||
                    mb_strtolower($profissional['endereco']['bairro'], 'UTF-8') !== $bairroFiltroLower
                ) {
                    $passouFiltros = false;
                }
            }

            // Verificar filtro de serviços (mantém a mesma lógica)
            if ($passouFiltros && !empty($servicosFiltro)) {
                $temServico = false;

                if (isset($profissional['servicos']['principais'])) {
                    foreach ($servicosFiltro as $servicoDesejado) {
                        if (in_array($servicoDesejado, $profissional['servicos']['principais'])) {
                            $temServico = true;
                            break;
                        }
                    }
                }

                if (!$temServico && isset($profissional['servicos']['outros'])) {
                    foreach ($profissional['servicos']['outros'] as $outroServico) {
                        foreach ($servicosFiltro as $servicoDesejado) {
                            if (stripos($outroServico, $servicoDesejado) !== false) {
                                $temServico = true;
                                break 2;
                            }
                        }
                    }
                }

                $passouFiltros = $temServico;
            }

            if ($passouFiltros) {
                $profissionais[$key] = $profissional;
            }
        }

        if (empty($profissionais)) {
            $msg = "Nenhum profissional encontrado com os critérios de busca.";
        }
    }
} catch (FirebaseException $e) {
    $error = "Erro ao conectar com o banco de dados: " . $e->getMessage();
    error_log("Firebase Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Profissionais</title>
    <link rel="stylesheet" href="../assets/css/stylesCadLogin.css">
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* [Manter todos os estilos anteriores] */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .filtros {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filtro-group {
            margin-bottom: 15px;
        }

        .filtro-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .filtro-group select,
        .filtro-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .servicos-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .checkbox-container input {
            margin-right: 8px;
        }

        .btn-buscar {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn-buscar:hover {
            background-color: #45a049;
        }

        .resultados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .profissional-card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profissional-card h3 {
            margin-top: 0;
            color: #333;
        }

        .profissional-card p {
            margin: 5px 0;
        }

        .servicos {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }

        .servicos span {
            display: inline-block;
            background-color: #e9f7ef;
            padding: 3px 8px;
            margin: 2px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .servicos span.destaque {
            background-color: #4CAF50;
            color: white;
        }

        .redes-sociais {
            margin-top: 10px;
        }

        .redes-sociais a {
            display: inline-block;
            margin-right: 10px;
            color: #4CAF50;
            text-decoration: none;
        }

        .redes-sociais a:hover {
            text-decoration: underline;
        }

        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .mensagem.sucesso {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .mensagem.erro {
            background-color: #f2dede;
            color: #a94442;
        }


        /* Adicionar estilo para o campo de busca */
        .search-container {
            position: relative;
            margin-bottom: 15px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-container i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }

        
    </style>
</head>

<body>
    <div class="container">
        <h1>Consulta de Profissionais</h1>

        <?php if ($msg): ?>
            <div class="mensagem sucesso"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mensagem erro"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="filtros">
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="filtro-group">
                    <label for="cidade">Cidade:</label>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="cidadeInput" onkeyup="filterDropdown('cidadeInput', 'cidadeDropdown')"
                            placeholder="Buscar cidade..." autocomplete="off">
                        <input type="hidden" name="cidade" id="cidadeHidden">
                        <div id="cidadeDropdown" class="dropdown-content">
                            <a onclick="selectOption('cidadeHidden', 'cidadeInput', '')">Todas as cidades</a>
                            <?php foreach ($cidadesUnicas as $cidade): ?>
                                <a onclick="selectOption('cidadeHidden', 'cidadeInput', '<?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>')">
                                    <?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filtro-group">
                    <label for="bairro">Bairro:</label>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bairroInput" onkeyup="filterDropdown('bairroInput', 'bairroDropdown')"
                            placeholder="Buscar bairro..." autocomplete="off">
                        <input type="hidden" name="bairro" id="bairroHidden">
                        <div id="bairroDropdown" class="dropdown-content">
                            <a onclick="selectOption('bairroHidden', 'bairroInput', '')">Todos os bairros</a>
                            <?php foreach ($bairrosUnicos as $bairro): ?>
                                <a onclick="selectOption('bairroHidden', 'bairroInput', '<?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>')">
                                    <?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="filtro-group">
                    <label>Serviços:</label>
                    <div class="servicos-checkboxes">
                        <?php foreach ($servicosDisponiveis as $servico): ?>
                            <div class="checkbox-container">
                                <input type="checkbox" name="servicos[]" id="servico_<?= htmlspecialchars(str_replace(' ', '_', $servico), ENT_QUOTES, 'UTF-8') ?>"
                                    value="<?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= (isset($_POST['servicos']) && in_array($servico, $_POST['servicos'])) ? 'checked' : '' ?>>
                                <label for="servico_<?= htmlspecialchars(str_replace(' ', '_', $servico), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-buscar">Buscar Profissionais</button>
            </form>
        </div>

        <?php if (!empty($profissionais)): ?>
            <div class="resultados">
                <?php foreach ($profissionais as $id => $profissional): ?>
                    <div class="profissional-card">
                        <h3><?= htmlspecialchars($profissional['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>

                        <p><strong>Endereço:</strong>
                            <?= htmlspecialchars($profissional['endereco']['rua'] ?? '', ENT_QUOTES, 'UTF-8') ?>,
                            <?= htmlspecialchars($profissional['endereco']['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?> -
                            <?= htmlspecialchars($profissional['endereco']['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>/
                            <?= htmlspecialchars($profissional['endereco']['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p><strong>Telefone:</strong> <?= htmlspecialchars($profissional['cel'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

                        <?php if (isset($profissional['servicos'])): ?>
                            <div class="servicos">
                                <strong>Serviços:</strong><br>
                                <?php if (isset($profissional['servicos']['principais'])): ?>
                                    <?php foreach ($profissional['servicos']['principais'] as $servico): ?>
                                        <span class="<?= (isset($_POST['servicos']) && in_array($servico, $_POST['servicos'])) ? 'destaque' : '' ?>">
                                            <?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (isset($profissional['servicos']['outros'])): ?>
                                    <?php foreach ($profissional['servicos']['outros'] as $servico): ?>
                                        <span>
                                            <?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($profissional['redes_sociais'])): ?>
                            <div class="redes-sociais">
                                <?php if (!empty($profissional['redes_sociais']['whatsapp'])): ?>
                                    <a href="<?= htmlspecialchars($profissional['redes_sociais']['whatsapp'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($profissional['redes_sociais']['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($profissional['redes_sociais']['instagram'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                        <i class="fab fa-instagram"></i> Instagram
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($profissional['redes_sociais']['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($profissional['redes_sociais']['facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                        <i class="fab fa-facebook"></i> Facebook
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Função para filtrar opções no dropdown
        function filterDropdown(inputId, dropdownId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toUpperCase();
            const dropdown = document.getElementById(dropdownId);
            const options = dropdown.getElementsByTagName("a");

            // Mostrar o dropdown
            dropdown.classList.add("show");

            for (let i = 0; i < options.length; i++) {
                const txtValue = options[i].textContent || options[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    options[i].style.display = "";
                } else {
                    options[i].style.display = "none";
                }
            }
        }

        // Função para selecionar uma opção do dropdown
        function selectOption(hiddenId, inputId, value) {
            document.getElementById(hiddenId).value = value;
            document.getElementById(inputId).value = value;
            document.getElementById(inputId + 'Dropdown').classList.remove("show");
        }

        // Fechar dropdowns quando clicar fora
        window.onclick = function(event) {
            if (!event.target.matches('.search-container input')) {
                const dropdowns = document.getElementsByClassName("dropdown-content");
                for (let i = 0; i < dropdowns.length; i++) {
                    if (dropdowns[i].classList.contains('show')) {
                        dropdowns[i].classList.remove('show');
                    }
                }
            }
        }

        // Manter valores selecionados após submit (para os campos de busca)
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_POST['cidade']) && $_POST['cidade'] !== ''): ?>
                document.getElementById('cidadeInput').value = '<?= htmlspecialchars($_POST['cidade'], ENT_QUOTES, 'UTF-8') ?>';
            <?php endif; ?>

            <?php if (isset($_POST['bairro']) && $_POST['bairro'] !== ''): ?>
                document.getElementById('bairroInput').value = '<?= htmlspecialchars($_POST['bairro'], ENT_QUOTES, 'UTF-8') ?>';
            <?php endif; ?>
        });
    </script>
</body>

</html>