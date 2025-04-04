<?php
session_start();
require '../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

if (!isset($_SESSION['email'])) {
    header('Location: loginProf.php');
    exit();
}

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
    $factory = (new Factory())
        ->withServiceAccount('../config/chave.json')
        ->withDatabaseUri('https://atelieconecta-d9030-default-rtdb.firebaseio.com/');

    $database = $factory->createDatabase();

    $profissionaisRef = $database->getReference('userProf');
    $todosProfissionais = $profissionaisRef->getValue();

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
        $cidades = [];
        $bairros = [];

        foreach ($todosProfissionais as $profissional) {
            if (isset($profissional['endereco']['cidade'])) {
                $cidadeLower = mb_strtolower($profissional['endereco']['cidade'], 'UTF-8');
                $cidades[$cidadeLower] = $profissional['endereco']['cidade'];
            }
            if (isset($profissional['endereco']['bairro'])) {
                $bairroLower = mb_strtolower($profissional['endereco']['bairro'], 'UTF-8');
                $bairros[$bairroLower] = $profissional['endereco']['bairro'];
            }
        }

        $cidadesUnicas = array_values($cidades);
        $bairrosUnicos = array_values($bairros);

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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $cidadeFiltro = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
        $bairroFiltro = isset($_POST['bairro']) ? trim($_POST['bairro']) : '';
        $servicoBusca = isset($_POST['servico_busca']) ? trim($_POST['servico_busca']) : '';
        $servicoSelecionado = isset($_POST['servico']) ? trim($_POST['servico']) : '';

        // Converter filtros para lowercase para comparação case-insensitive
        $cidadeFiltroLower = !empty($cidadeFiltro) ? mb_strtolower($cidadeFiltro, 'UTF-8') : null;
        $bairroFiltroLower = !empty($bairroFiltro) ? mb_strtolower($bairroFiltro, 'UTF-8') : null;
        $servicoBuscaLower = !empty($servicoBusca) ? mb_strtolower($servicoBusca, 'UTF-8') : null;

        $profissionais = [];
        foreach ($todosProfissionais as $key => $profissional) {
            $passouFiltros = true;

            // Filtro por cidade (case-insensitive)
            if ($cidadeFiltroLower !== null) {
                if (
                    !isset($profissional['endereco']['cidade']) ||
                    mb_strtolower($profissional['endereco']['cidade'], 'UTF-8') !== $cidadeFiltroLower
                ) {
                    $passouFiltros = false;
                }
            }

            // Filtro por bairro (case-insensitive)
            if ($passouFiltros && $bairroFiltroLower !== null) {
                if (
                    !isset($profissional['endereco']['bairro']) ||
                    mb_strtolower($profissional['endereco']['bairro'], 'UTF-8') !== $bairroFiltroLower
                ) {
                    $passouFiltros = false;
                }
            }

            // Filtro por serviço
            if ($passouFiltros && ($servicoBuscaLower !== null || !empty($servicoSelecionado))) {
                $temServico = false;

                if ($servicoBuscaLower !== null) {
                    if (isset($profissional['servicos']['principais'])) {
                        foreach ($profissional['servicos']['principais'] as $servico) {
                            if (stripos($servico, $servicoBusca) !== false) {
                                $temServico = true;
                                break;
                            }
                        }
                    }

                    if (!$temServico && isset($profissional['servicos']['outros'])) {
                        foreach ($profissional['servicos']['outros'] as $outroServico) {
                            if (stripos($outroServico, $servicoBusca) !== false) {
                                $temServico = true;
                                break;
                            }
                        }
                    }
                }

                if (!$temServico && !empty($servicoSelecionado)) {
                    if (isset($profissional['servicos']['principais'])) {
                        if (in_array($servicoSelecionado, $profissional['servicos']['principais'])) {
                            $temServico = true;
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .container h1 {
            color: #fff;
            text-align: center;
            padding: 10px;
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

        .btn-voltar {
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 10px;
        }

        .btn-voltar:hover {
            background-color: #6c755d;
        }

        /* Estilos do carrossel */
        .carrossel-container {
            position: relative;
            max-width: 800px;
            margin: 30px auto;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .carrossel {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .profissional-card {
            min-width: 100%;
            box-sizing: border-box;
            padding: 25px;
            background-color: white;
        }

        .carrossel-controles {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .carrossel-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            margin: 0 10px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }

        .carrossel-btn:hover {
            background-color: #45a049;
        }

        .carrossel-indicadores {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }

        .indicador {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ccc;
            margin: 0 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .indicador.ativo {
            background-color: #4CAF50;
        }

        .profissional-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .profissional-foto {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #4CAF50;
        }

        .profissional-nome {
            color: #2c3e50;
            margin: 0;
            font-size: 1.5em;
        }

        .profissional-info {
            margin-bottom: 15px;
        }

        .profissional-info p {
            margin: 8px 0;
            color: #555;
        }

        .profissional-info strong {
            color: #2c3e50;
        }

        .servicos-container {
            margin: 15px 0;
        }

        .servicos-container h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .servicos-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .servico-tag {
            background-color: grey;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .servico-tag.destaque {
            background-color: #4CAF50;
            color: white;
        }

        .servico-tag.outro {
            background-color: #6c757d;
            color: white;
        }

        .redes-sociais {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }

        .rede-social {
            color: #4CAF50;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rede-social:hover {
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

        .sem-resultados {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 1.1em;
            color: #fff;
        }

        /* Estilos para os selects */
        .select-cidade-bairro {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .select-cidade-bairro:focus {
            outline: none;
            border-color: #4CAF50;
        }

        /* Estilos para o select de serviços */
        .servico-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }

        .servico-select:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .servico-busca-container {
            display: flex;
            gap: 10px;
        }

        .servico-busca-container input {
            flex: 1;
        }

        .servico-busca-container select {
            width: 200px;
        }

        .info-busca {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }

        .foto-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: #777;
            font-size: 2em;
            border: 2px solid #ccc;
        }

        .botoes {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        @media (min-width: 10px) and (max-width: 320px) {
            .filtros .servico-busca-container {
                flex-direction: column;
            }

            #servico_busca::placeholder {
                font-size: 12.8px;
            }

            .botoes{
                flex-direction: column;
            }
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
                    <select name="cidade" id="cidade" class="select-cidade-bairro">
                        <option value="">Selecione uma cidade</option>
                        <?php foreach ($cidadesUnicas as $cidade): ?>
                            <option value="<?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>"
                                <?= (isset($_POST['cidade']) && $_POST['cidade'] === $cidade) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-group">
                    <label for="bairro">Bairro:</label>
                    <select name="bairro" id="bairro" class="select-cidade-bairro">
                        <option value="">Selecione um bairro</option>
                        <?php foreach ($bairrosUnicos as $bairro): ?>
                            <option value="<?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>"
                                <?= (isset($_POST['bairro']) && $_POST['bairro'] === $bairro) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bairro, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-group">
                    <label for="servico_busca">Buscar por Serviço:</label>
                    <div class="servico-busca-container">
                        <input type="text" name="servico_busca" id="servico_busca"
                            placeholder="Digite o serviço desejado (ex: costura)"
                            value="<?php echo isset($_POST['servico_busca']) ? htmlspecialchars($_POST['servico_busca'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <select name="servico" id="servico" class="servico-select">
                            <option value="">Ou selecione um serviço</option>
                            <?php foreach ($servicosDisponiveis as $servico): ?>
                                <option value="<?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo (isset($_POST['servico']) && $_POST['servico'] === $servico) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($servico, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="info-busca">
                        Digite parte do nome do serviço ou selecione na lista. A busca inclui todos os serviços cadastrados.
                    </div>
                </div>
                <div class="botoes">
                    <a href="dashAcessoCli.php" class="btn-voltar">
                        <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Voltar
                    </a>
                    <button type="submit" class="btn-buscar">Buscar Profissionais</button>
                </div>
            </form>
        </div>

        <?php if (!empty($profissionais)): ?>
            <div class="carrossel-container">
                <div class="carrossel" id="carrossel">
                    <?php foreach ($profissionais as $id => $profissional): ?>
                        <div class="profissional-card" data-index="<?= array_search($id, array_keys($profissionais)) ?>">
                            <div class="profissional-header">
                                <?php if (!empty($profissional['fotoPerfil'])): ?>
                                    <img src="<?= htmlspecialchars($profissional['fotoPerfil'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto do profissional" class="profissional-foto">
                                <?php else: ?>
                                    <div class="foto-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <h3 class="profissional-nome"><?= htmlspecialchars($profissional['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>

                            <div class="profissional-info">
                                <p><strong>Endereço:</strong>
                                    <?= htmlspecialchars($profissional['endereco']['rua'] ?? '', ENT_QUOTES, 'UTF-8') ?>,
                                    <?= htmlspecialchars($profissional['endereco']['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                    <?= htmlspecialchars($profissional['endereco']['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?> -
                                    <?= htmlspecialchars($profissional['endereco']['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <p><strong>Telefone:</strong> <?= htmlspecialchars($profissional['cel'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>

                            <?php if (isset($profissional['servicos'])): ?>
                                <div class="servicos-container">
                                    <h4>Serviços oferecidos:</h4>
                                    <div class="servicos-lista">
                                        <?php
                                        $servicoBusca = isset($_POST['servico_busca']) ? strtolower($_POST['servico_busca']) : '';
                                        $servicoSelecionado = isset($_POST['servico']) ? $_POST['servico'] : '';

                                        // Mostrar serviços principais
                                        if (isset($profissional['servicos']['principais'])): ?>
                                            <?php foreach ($profissional['servicos']['principais'] as $servico): ?>
                                                <?php
                                                $destaque = (!empty($servicoBusca) && stripos($servico, $servicoBusca) !== false) ||
                                                    (!empty($servicoSelecionado) && $servicoSelecionado === $servico);
                                                ?>
                                                <span class="servico-tag <?= $destaque ? 'destaque' : '' ?>">
                                                    <?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <?php // Mostrar serviços "outros"
                                        if (isset($profissional['servicos']['outros'])): ?>
                                            <?php foreach ($profissional['servicos']['outros'] as $servico): ?>
                                                <?php $destaque = !empty($servicoBusca) && stripos($servico, $servicoBusca) !== false; ?>
                                                <span class="servico-tag <?= $destaque ? 'destaque' : 'outro' ?>">
                                                    <?= htmlspecialchars($servico, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($profissional['redes_sociais'])): ?>
                                <div class="redes-sociais">
                                    <?php if (!empty($profissional['redes_sociais']['whatsapp'])): ?>
                                        <a href="<?= htmlspecialchars($profissional['redes_sociais']['whatsapp'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rede-social">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($profissional['redes_sociais']['instagram'])): ?>
                                        <a href="<?= htmlspecialchars($profissional['redes_sociais']['instagram'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rede-social">
                                            <i class="fab fa-instagram"></i> Instagram
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($profissional['redes_sociais']['facebook'])): ?>
                                        <a href="<?= htmlspecialchars($profissional['redes_sociais']['facebook'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rede-social">
                                            <i class="fab fa-facebook"></i> Facebook
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="carrossel-controles">
                    <button class="carrossel-btn" id="btnAnterior"><i class="fas fa-chevron-left"></i></button>
                    <button class="carrossel-btn" id="btnProximo"><i class="fas fa-chevron-right"></i></button>
                </div>

                <div class="carrossel-indicadores" id="indicadores">
                    <?php for ($i = 0; $i < count($profissionais); $i++): ?>
                        <div class="indicador <?= $i === 0 ? 'ativo' : '' ?>" data-index="<?= $i ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="sem-resultados">
                Nenhum profissional encontrado com os critérios selecionados.
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Script do carrossel
        document.addEventListener('DOMContentLoaded', function() {
            const carrossel = document.getElementById('carrossel');
            const btnAnterior = document.getElementById('btnAnterior');
            const btnProximo = document.getElementById('btnProximo');
            const indicadores = document.querySelectorAll('.indicador');
            let currentIndex = 0;
            const totalItems = <?= count($profissionais) ?>;

            function updateCarrossel() {
                carrossel.style.transform = `translateX(-${currentIndex * 100}%)`;

                // Atualizar indicadores
                indicadores.forEach((indicador, index) => {
                    if (index === currentIndex) {
                        indicador.classList.add('ativo');
                    } else {
                        indicador.classList.remove('ativo');
                    }
                });
            }

            function nextItem() {
                currentIndex = (currentIndex + 1) % totalItems;
                updateCarrossel();
            }

            function prevItem() {
                currentIndex = (currentIndex - 1 + totalItems) % totalItems;
                updateCarrossel();
            }

            // Event listeners
            btnProximo.addEventListener('click', nextItem);
            btnAnterior.addEventListener('click', prevItem);

            // Navegação pelos indicadores
            indicadores.forEach(indicador => {
                indicador.addEventListener('click', function() {
                    currentIndex = parseInt(this.getAttribute('data-index'));
                    updateCarrossel();
                });
            });

            // Navegação por teclado
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowRight') {
                    nextItem();
                } else if (e.key === 'ArrowLeft') {
                    prevItem();
                }
            });
        });
    </script>
</body>

</html>