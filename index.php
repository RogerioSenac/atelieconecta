<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Corte e Costura</title>
    <link rel="stylesheet" href="../atelieconecta/assets/css/styles.css">
    <link rel="icon" href="../atelieconecta/assets/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <header>
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="#banner">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#services">Serviços</a></li>
                        <li class="nav-item"><a class="nav-link" href="#comentario">Comentarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="#sobre">Sobre Nós</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contato">Contato</a></li>
                    </ul>
                </div>
            </nav>
            <a href="perfil.php"><img src="./assets/img/login_3cinza.png" class="btn-login"></a>
        </div>
    </header>

    <main>
        <section class="banner" id="banner">
            <h1>Bem-vindo à Plataforma do Atelie Connect</h1></br>
            <p>Conectando você aos talentos da moda.</p>
        </section>

        <section class="services" id="services">
            <div class="container">
                <h2>Serviços Oferecidos</h2>
                <div class="row">
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-1.jpeg" alt="Serviço 1">
                        <p class="tesoura">Customização</p>
                    </div>
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-2.jpeg" alt="Serviço 2">
                        <p class="tesoura">Ateliê sob Medida</p>
                    </div>
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-3.jpeg" alt="Serviço 3">
                        <p class="tesoura">Consertos e Ajuste</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-4.jpeg" alt="Serviço 4">
                        <p class="tesoura">Estilista</p>
                    </div>
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-6.jpeg" alt="Serviço 5">
                        <p class="tesoura">Roupas Personalizadas</p>
                    </div>
                    <div class="col-6 col-md-4 service-item">
                        <img src="./assets/img/servico-5.jpeg" alt="Serviço 6">
                        <p class="tesoura">Modelagem</p>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <section class="comentario" id="comentario" class="comentario d-flex flex-column align-items-center">
            <h2>Depoimentos Recentes</h2>
            <div class="container">
                <div class="row">
                    <!-- Card de Depoimentos -->
                    <div class="col-md-6">
                        <div class="list-group">
                            <?php
                            // URL do Realtime Database Firebase
                            $firebase_url = "https://SEU_PROJETO.firebaseio.com/comentarios.json";

                            // Fazendo a requisição para obter os comentários
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $firebase_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            $response = curl_exec($ch);
                            curl_close($ch);

                            // Verifica se a resposta não é vazia e decodifica JSON
                            if ($response) {
                                $comentarios = json_decode($response, true);
                            } else {
                                $comentarios = [];
                            }

                            // Verifica se o JSON foi decodificado corretamente
                            if (!is_array($comentarios)) {
                                echo "<p>Erro ao carregar os comentários.</p>";
                                exit;
                            }

                            // Ordenar os comentários por data (do mais recente para o mais antigo)
                            usort($comentarios, function ($a, $b) {
                                return strtotime($b['data']) - strtotime($a['data']);
                            });

                            // Selecionar os 5 últimos
                            $comentarios = array_slice($comentarios, 0, 5);

                            // Loop para exibir os comentários
                            if (!empty($comentarios)) {
                                foreach ($comentarios as $comentario) {
                                    // Verifica se os campos existem antes de acessar
                                    $nome = isset($comentario['nome']) ? htmlspecialchars($comentario['nome']) : 'Usuário Anônimo';
                                    $foto = isset($comentario['foto']) && !empty($comentario['foto']) ? htmlspecialchars($comentario['foto']) : 'Assets/img/default-user.png';
                                    $data = isset($comentario['data']) ? date('d/m/Y', strtotime($comentario['data'])) : 'Data desconhecida';
                                    $texto = isset($comentario['texto']) ? htmlspecialchars($comentario['texto']) : 'Sem comentário.';

                                    echo '<div class="depoimento-item d-flex flex-column align-items-center">';
                                    echo '<img src="' . $foto . '" alt="Foto de ' . $nome . '" class="user-photo">';
                                    echo '<div class="depoimento-content text-center">';
                                    echo '<h5 class="h5_depoimento">' . $nome . '</h5>';
                                    echo '<p class="depoimento-date">' . $data . '</p>';
                                    echo '<p class="p_depoimento">' . $texto . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>Nenhum depoimento encontrado.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
        </section>

        <section class="container-quem-somos" id="sobre">
            <div class="container-quem">
                <div class="row-q">
                    <div class="col-12 text-center">
                        <img class="icones img-fluid" src="./assets/img/outdor2.png" alt="foto fachada">
                    </div>
                </div>
                <p class="p1">
                    Transforme seu estilo de forma simples e eficiente com o Ateliê Connect.
                </p>
                <div class="row">
                    <div class="p-2 col-12 col-md-6">
                        <p>
                            O Ateliê Connect é uma plataforma inovadora que conecta pessoas a profissionais
                            especializados em moda, como estilistas, consultores de imagem e personal shoppers. Nosso
                            objetivo é facilitar o acesso a serviços personalizados de moda, proporcionando consultorias
                            e sugestões de looks que atendem às necessidades e estilos de cada indivíduo.
                        </p>
                    </div>
                    <div class="col-12 col-md-6 p-2">
                        <p>
                            Com uma interface prática e intuitiva, o Ateliê Connect oferece uma experiência única,
                            permitindo agendar consultas, explorar portfólios e receber dicas de profissionais
                            qualificados. Acreditamos que a moda é uma poderosa ferramenta de expressão e confiança, e
                            estamos aqui para ajudar você a encontrar o visual que reflita sua verdadeira identidade.
                        </p>
                    </div>
                </div>
        </section>

        <section class="contato" id="contato">
            <video autoplay loop muted playsinline>
                <source src="./assets/video/video_Developers2.mov" type="video/mp4">
                Seu navegador não suporta o elemento de vídeo.
            </video>

            <div class="txt-contato">
                <div class="img-contato">
                    <img src="./assets/img/developersRGTsemfundo2.png">
                </div>
                <h2>MUITO PRAZER, <span>SOMOS A DEVELOPER RGT</span></h2>
                <p>
                    A <span>Developer RGT</span> é formada por talentosos alunos do curso técnico de
                    Informática para Internet do <span>SENAC REGISTRO</span>, unidos pela paixão por tecnologia e
                    inovação.
                </p>

                <p>
                    Especializados em <span>DESENVOLVIMENTO WEB</span>, estamos no mercado desde
                    <span>Janeiro/2024</span>,
                    criando soluções digitais sob medida para empresas e empreendedores. Nossa equipe aplica as mais
                    avançadas
                    técnicas e <span>BOAS PRÁTICAS</span> do setor para desenvolver aplicações web
                    <span>CRIATIVAS</span>,
                    <span>INOVADORAS</span> e altamente funcionais.
                </p>

                <p>
                    Se você busca um site profissional, moderno e totalmente personalizado, a <span>Developer RGT</span>
                    está pronta
                    para transformar sua ideia em realidade. <strong>Entre em contato e leve sua presença digital para o
                        próximo nível!</strong>
                </p>

                <div class="btn-social">
                    <a href="https://www.instagram.com/developers.rgt?igsh=MXF1bml6OHAyeXcwNA=="
                        target="_blank"><button><i class="rede fa-brands fa-instagram"></i></button></a>

                    <a href="https://www.linkedin.com/in/developers-rgt-862402309/" target="_blank"><button><i
                                class="fa-brands fa-linkedin"></i></button></a>

                    <a href="https://www.youtube.com/@developers_rgt?si=oQlWOmaRf2SRPzlD&fbclid=PAZXh0bgNhZW0CMTEAAaYqnLMgVcRIadkyQ5bQ6MNxTCTzn54hLfiPhnE_JIKYDPlky-PEubYzu0g_aem_AZ5WecxAIc8cxdrGFj8cjiGjndHYazGJ6A-xIEf5Gyn1Et8ZO3SSA65_nPesYYSksfh2gltMmyF2FESUFtbq0KQ2"
                        target="_blank"><button><i class="fa-brands fa-youtube"></i></button></a>

                    <a href="https://github.com/DevelopersRGT" target="_blank"><button><i
                                class="fa-brands fa-github"></i></button></a>

                    <a href="falecomdevelopersrgt@gmail.com" target="_blank"><button><i
                                class="fas fa-envelope"></i></button></a>
                </div> <!-- Fim btn-social -->
            </div><!-- Fim Texto contato -->
        </section>
    </main>

    <footer>
        <div class="rodape">
            <div>
                <img class="logo_dev" src="./assets/img/logo_devergt10.jpeg" alt="logo astronauta">
            </div>
            <p>&copy; 2025 Plataforma de Corte e Costura. <br>Todos os direitos reservados.</p>
            <div class="txt_logo">
                <p class="p_footer">Design By <span>DevelopersRGT</span></p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Alternar o menu sanduíche ao clicar no ícone
    $(document).ready(function() {
        $('.navbar-toggler').on('click', function() {
            $('.navbar-collapse').toggleClass('show');
        });

        // Fechar o menu sanduíche ao clicar fora dele
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.navbar').length) {
                $('.navbar-collapse').removeClass('show');
            }
        });

        // Fechar o menu sanduíche ao clicar em um item
        $('.navbar-nav>li>a').on('click', function() {
            $('.navbar-collapse').removeClass('show');
        });
    });
    </script>

</body>

</html>