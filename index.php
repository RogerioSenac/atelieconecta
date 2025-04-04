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
    <!-- Alterado para Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header>
        <div class="container">
            <img class="logo" src="assets/img/new_logo3.png" alt="logo">
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
            <h1>Bem-vindo à Plataforma do Atelie Conecta</h1></br>
            <p>Conectando você aos talentos da moda.</p>
        </section>

        <section class="services" id="services">
            <div class="container">
                <h2>Serviços Oferecidos</h2>
                <div class="row">
                    <div class="service-item col-6 col-lg-4">
                        <img class="imagem" src="./assets/img/servico-1.jpeg" alt="Serviço 1">
                        <p class="tesoura">Customização</p>
                    </div>
                    <div class="col-6 col-lg-4 service-item">
                        <img class="imagem" src="./assets/img/servico-2.jpeg" alt="Serviço 2">
                        <p class="tesoura">Ateliê sob Medida</p>
                    </div>
                    <div class="col-6 col-lg-4 service-item">
                        <img class="imagem" src="./assets/img/servico-3.jpeg" alt="Serviço 3">
                        <p class="tesoura">Consertos e Ajuste</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 col-lg-4 service-item">
                        <img class="imagem" src="./assets/img/servico-4.jpeg" alt="Serviço 4">
                        <p class="tesoura">Estilista</p>
                    </div>
                    <div class="col-6 col-lg-4 service-item">
                        <img class="imagem" src="./assets/img/servico-6.jpeg" alt="Serviço 5">
                        <p class="tesoura">Roupas Personalizadas</p>
                    </div>
                    <div class="col-6 col-lg-4 service-item">
                        <img class="imagem" src="./assets/img/servico-5.jpeg" alt="Serviço 6">
                        <p class="tesoura">Modelagem</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="comentario" id="comentario">
            <h2>Depoimentos Recentes</h2>
            <div class="container">
                <div id="comentariosCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php

                        // URLs do Firebase
                        $firebase_comentarios_url = "https://atelieconecta-d9030-default-rtdb.firebaseio.com/comentarios.json";
                        $firebase_users_url = "https://atelieconecta-d9030-default-rtdb.firebaseio.com/userCli.json";

                        function getFirebaseData($url)
                        {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            $response = curl_exec($ch);
                            curl_close($ch);
                            return $response !== false ? json_decode($response, true) : [];
                        }

                        // Obter dados
                        $comentarios = getFirebaseData($firebase_comentarios_url);
                        $usuarios = getFirebaseData($firebase_users_url);

                        if (empty($comentarios)) {
                            echo '<div class="carousel-item active">';
                            echo '<div class="row justify-content-center">';
                            echo '<div class="col-12 text-center">';
                            echo '<p class="text-muted">Nenhum depoimento encontrado.</p>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            // Filtra e ordena comentários
                            $comentariosFiltrados = array_filter($comentarios, function ($comentario) {
                                return !isset($comentario['aprovado']) || $comentario['aprovado'] === true;
                            });

                            usort($comentariosFiltrados, function ($a, $b) {
                                $dateA = isset($a['data']) ? strtotime(str_replace('/', '-', $a['data'])) : 0;
                                $dateB = isset($b['data']) ? strtotime(str_replace('/', '-', $b['data'])) : 0;
                                return $dateB - $dateA;
                            });

                            // Dividir em grupos de 3
                            $comentariosChunks = array_chunk($comentariosFiltrados, 3);

                            foreach ($comentariosChunks as $index => $chunk) {
                                $activeClass = $index === 0 ? 'active' : '';
                                echo '<div class="carousel-item ' . $activeClass . '">';
                                echo '<div class="row justify-content-center">';

                                foreach ($chunk as $comentario) {
                                    $nome = htmlspecialchars($comentario['usuario_nome'] ?? 'Usuário Anônimo');
                                    $data = isset($comentario['data']) ? date('d/m/Y', strtotime(str_replace('/', '-', $comentario['data']))) : 'Data desconhecida';
                                    $texto = htmlspecialchars($comentario['texto'] ?? 'Sem comentário.');
                                    $avaliacao = (int)($comentario['avaliacao'] ?? 0);
                                    $userId = $comentario['usuario_id'] ?? null;

                                    // Obter foto do perfil - CORREÇÃO PRINCIPAL
                                    // Dentro do loop dos comentários, onde busca a foto:
                                    $fotoPerfil = 'assets/img/user-default.png'; // Foto padrão

                                    if ($userId && isset($usuarios[$userId])) {
                                        $userData = $usuarios[$userId];
                                        
                                        if (!empty($userData['fotoPerfil'])) {
                                            // Remove "../" do início do caminho se existir
                                            $fotoPath = ($userData['fotoPerfil']);
                                            $fotoPerfil = $fotoPath;
                                        }
                                    }

                                    // Exibir estrelas de avaliação
                                    $estrelas = str_repeat('★', $avaliacao) . str_repeat('☆', 5 - $avaliacao);

                                    // Exibir o card do depoimento
                                    echo '<div class="col-md-4 mb-4">';
                                    echo '<div class="depoimento-item card h-100 mx-2">';
                                    echo '<div class="card-body text-center">';
                                    echo '<div class="foto-perfil-container">';
                                    echo '<img src="' . $fotoPerfil . '" alt="Foto de ' . $nome . '" class="foto-perfil-depoimento" onerror="this.src=\'assets/img/user-default.png\'">';
                                    echo '</div>';
                                    echo '<h5 class="card-title h5_depoimento">' . $nome . '</h5>';
                                    echo '<p class="card-subtitle mb-2 text-muted depoimento-date">' . $data . '</p>';
                                    echo '<div class="avaliacao mb-2 text-warning">' . $estrelas . '</div>';
                                    echo '<p class="card-text p_depoimento">' . $texto . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }

                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>

                    <?php if (!empty($comentarios) && count($comentariosChunks) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#comentariosCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#comentariosCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Próximo</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($comentarios) && count($comentariosChunks) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#comentariosCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#comentariosCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Próximo</span>
            </button>
        <?php endif; ?>
        </div>
        </div>
        </section>

        <section class="container-quem-somos" id="sobre">
            <div class="container-quem">
                <div class="row-q">
                    <div class="col-12 text-center">
                        <img class="img-fluid" src="./assets/img/outdor2.png" alt="foto fachada">
                    </div>
                </div>
                <p class="apresentacao-quem">
                    Transforme seu estilo de forma simples e eficiente com o Ateliê Connect.
                </p>
                <div class="row">
                    <div class="p-2 col-12 col-md-6">
                        <p class="apresentacao-txtquem">
                            O Ateliê Connect é uma plataforma inovadora que conecta pessoas a profissionais
                            especializados em moda, como estilistas, consultores de imagem e personal shoppers. Nosso
                            objetivo é facilitar o acesso a serviços personalizados de moda, proporcionando consultorias
                            e sugestões de looks que atendem às necessidades e estilos de cada indivíduo.
                        </p>
                    </div>
                    <div class="p-2 col-12 col-md-6 ">
                        <p class="apresentacao-txtquem">
                            Com uma interface prática e intuitiva, o Ateliê Connect oferece uma experiência única,
                            permitindo agendar consultas, explorar portfólios e receber dicas de profissionais
                            qualificados. Acreditamos que a moda é uma poderosa ferramenta de expressão e confiança, e
                            estamos aqui para ajudar você a encontrar o visual que reflita sua verdadeira identidade.
                        </p>
                    </div>
                </div>
        </section>

        <section class="contato" id="contato">
            <!-- <video autoplay loop muted playsinline>
                <source src="./assets/video/video_Developers2.mov" type="video/mp4">
                Seu navegador não suporta o elemento de vídeo.
            </video> -->

            <!-- <div class="txt-contato"> -->
            <div class="img-contato">
                <img src="./assets/img/developersRGTsemfundo2.png">
            </div>
            <p class="somosadev">MUITO PRAZER,<br> <span>SOMOS A DEVELOPER RGT</span></p>
            <p class="txtApres">
                A <span>Developer RGT</span> é formada por talentosos alunos do curso técnico de
                Informática para Internet do <span>SENAC REGISTRO</span>, unidos pela paixão por tecnologia e
                inovação.
            </p>

            <p class="txtApres">
                Especializados em <span>DESENVOLVIMENTO WEB</span>, estamos no mercado desde
                <span>Janeiro/2024</span>,
                criando soluções digitais sob medida para empresas e empreendedores. Nossa equipe aplica as mais
                avançadas
                técnicas e <span>BOAS PRÁTICAS</span> do setor para desenvolver aplicações web
                <span>CRIATIVAS</span>,
                <span>INOVADORAS</span> e altamente funcionais.
            </p>

            <p class="txtApres">
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

    <!-- Scripts atualizados para Bootstrap 5 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicialização do carrossel
        document.addEventListener('DOMContentLoaded', function() {
            var myCarousel = document.querySelector('#comentariosCarousel');
            var carousel = new bootstrap.Carousel(myCarousel, {
                interval: 5000, // Muda a cada 5 segundos
                wrap: true
            });

            // Menu mobile (ajustado para Bootstrap 5)
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');

            navbarToggler.addEventListener('click', function() {
                navbarCollapse.classList.toggle('show');
            });

            // Fechar menu ao clicar em um item
            document.querySelectorAll('.navbar-nav>li>a').forEach(function(element) {
                element.addEventListener('click', function() {
                    navbarCollapse.classList.remove('show');
                });
            });
        });
    </script>

</body>

</html>