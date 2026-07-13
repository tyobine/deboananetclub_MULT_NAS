<?php
// views/publicidade.php
// ==========================================================================
// LÓGICA DO CRM DE ANUNCIANTES (COM BLINDAGEM MÁXIMA E LEITURA DE CONFIGS)
// ==========================================================================
require_once __DIR__ . '/../models/banco.php';
$db_pub = new Banco();

// Recupera as configurações globais cadastradas na tabela chave-valor
$config_dados = $db_pub->getAll("SELECT * FROM configuracoes");
$sys_config = [];
if (!empty($config_dados)) {
    foreach ($config_dados as $row) {
        $sys_config[$row['chave']] = $row['valor'];
    }
}

// Define o tempo do anúncio vindo dinamicamente do banco de dados (Fallback: 15s)
$tempo_anuncio = isset($sys_config['tempo_anuncio']) ? (int)$sys_config['tempo_anuncio'] : 15;

// 1. Captura a torre (se aceder pelo link direto, isto fica vazio)
$roteador_atual = isset($_GET['router']) ? strtolower(trim($_GET['router'])) : '';

// 2. Tenta buscar anúncios da torre atual OU anúncios globais ('todos')
$anuncios_ativos = $db_pub->getAll("SELECT id, tipo, caminho_arquivo, link_destino FROM crm_anuncios WHERE exibir = 'sim' AND (FIND_IN_SET(?, localizacao) > 0 OR localizacao = 'todos')", [$roteador_atual]);

// 3. FALLBACK SUPREMO: Se der vazio, ignora o filtro de local e puxa QUALQUER anúncio ligado!
// Isso evita o erro quando se testa o site sem o link completo do MikroTik
if (empty($anuncios_ativos)) {
    $anuncios_ativos = $db_pub->getAll("SELECT id, tipo, caminho_arquivo, link_destino FROM crm_anuncios WHERE exibir = 'sim'");
}

$anuncio_id = 0; 

// 4. SE ACHOU QUALQUER ANÚNCIO DE CLIENTE, ELE MOSTRA:
if (!empty($anuncios_ativos)) {
    $chave_sorteada = array_rand($anuncios_ativos);
    $anuncio_escolhido = $anuncios_ativos[$chave_sorteada];

    $anuncio_id = $anuncio_escolhido['id'];
    $link_midia = htmlspecialchars($anuncio_escolhido['caminho_arquivo']);
    $tipo_anuncio = $anuncio_escolhido['tipo'];
    $link_destino = (!empty($anuncio_escolhido['link_destino'])) ? trim($anuncio_escolhido['link_destino']) : '#';
} 
// 5. CORINGA: SÓ MOSTRA SE O PAINEL ESTIVER 100% VAZIO OU TUDO DESLIGADO
else {
    $tipo_anuncio = 'imagem';
    $link_midia = '/src/anuncie_aqui.jpg'; 
    $link_destino = 'https://wa.me/5588996567485?text=Ol%C3%A1%2C+tenho+interesse+em+anunciar+no+Wi-Fi!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Patrocinado - Wi-Fi</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>

<body class="pagina-pub">
    <div class="ad-container px-3 text-center py-4">
        <div class="text-white mb-3">
            <h3 class="fw-bold">Acesso Patrocinado</h3>
            <p class="small">Wi-Fi gratuito oferecido pelo comércio local.</p>
        </div>

        <div class="card p-3">
            <div class="card-body p-1">

                <div class="mb-3 midia-box text-center position-relative" style="min-height: 250px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 8px; overflow: hidden;">
                    
                    <!-- Spinner centralizado de forma absoluta e sem texto -->
                    <div id="loading-spinner" class="position-absolute top-50 start-50 translate-middle" style="z-index: 10;">
                        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>

                    <!-- Interceptação de clique via JavaScript para reter o fluxo sem abrir nova aba imediatamente -->
                    <a href="#" onclick="marcarInteresseAnuncio(event, '<?= $anuncio_id ?>', '<?= htmlspecialchars($link_destino) ?>')" style="display: block; width: 100%; text-decoration: none; position: relative; z-index: 20;">
                        <?php if ($tipo_anuncio === 'video'): ?>
                            <video id="ad-media" autoplay muted playsinline loop style="display: none; cursor: pointer; width: 100%; border-radius: 8px;">
                                <source src="<?= $link_midia ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img id="ad-media" src="<?= $link_midia ?>" alt="Patrocinador" style="display: none; cursor: pointer; width: 100%; border-radius: 8px;">
                        <?php endif; ?>
                    </a>

                    <?php if ($tipo_anuncio === 'video'): ?>
                        <input type="range" id="video-progress" class="video-progress" value="0" min="0" max="100" step="0.1" style="display: none; position: relative; z-index: 30;">
                        <button id="btn-som" class="btn-som" style="display: none; position: absolute; z-index: 30; bottom: 10px; right: 10px;">🔇 Ligar Som</button>
                    <?php endif; ?>
                </div>

                <div class="alert alert-info py-2 mb-2" id="status-box">
                    A carregar os detalhes do parceiro...
                </div>

                <form id="form-liberacao-gratis" method="POST" action="/liberar-gratis-confirmado">
                    <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plano_id ?? ''); ?>">
                    <input type="hidden" name="mac" value="<?php echo htmlspecialchars($mac ?? $_GET['mac'] ?? ''); ?>">
                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip ?? $_GET['ip'] ?? ''); ?>">
                    <input type="hidden" name="router" value="<?php echo htmlspecialchars($roteador_atual); ?>">
                    
                    <!-- Inputs ocultos para rastreamento de clique retido -->
                    <input type="hidden" name="anuncio_id_clicado" id="anuncio_id_clicado" value="0">
                    <input type="hidden" name="url_redirecionamento_final" id="url_redirecionamento_final" value="">

                    <div class="text-start mb-3" id="whatsapp-box" style="display: none;">
                        <label class="form-label small fw-bold text-secondary mb-1">Para liberar a internet, informe o seu WhatsApp:</label>
                        <input type="tel" name="whatsapp" id="whatsapp-input" class="form-control form-control-lg text-center fw-bold mb-2" placeholder="(88) 99999-9999" required autocomplete="tel">

                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="lgpd-check" required>
                            <label class="form-check-label small text-muted" for="lgpd-check">
                                Concordo com os <a href="/politicas" target="_blank" class="text-primary text-decoration-none fw-bold">Termos de Uso e Privacidade (LGPD)</a>.
                            </label>
                        </div>
                    </div>

                    <button type="submit" id="btn-liberar" class="btn btn-secondary btn-lg w-100 fw-bold" disabled>
                        ⏳ Aguarde...
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.tempoAnuncioGlobal = <?= $tempo_anuncio ?>;

        function marcarInteresseAnuncio(event, id, urlDestino) {
            event.preventDefault();
            if(!id || id === '0' || urlDestino === '#') return;

            // Armazena a intenção de redirecionamento nos inputs ocultos seguros do formulário
            document.getElementById('anuncio_id_clicado').value = id;
            document.getElementById('url_redirecionamento_final').value = urlDestino;

            // Altera visualmente a caixa de status notificando o usuário sem alterar o layout CSS
            let statusBox = document.getElementById('status-box');
            statusBox.className = "alert alert-success py-2 mb-2 fw-bold";
            statusBox.innerText = "Você será redirecionado para o anúncio ao liberar o Wi-Fi.";
        }
    </script>
    <script src="/src/midia.js?v=<?php echo time(); ?>"></script>

    <?php if ($anuncio_id > 0): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let elementoMidia = document.getElementById('ad-media');
            let viewsJaContadas = false;

            function registrarVisualizacao() {
                if (!viewsJaContadas) {
                    viewsJaContadas = true;
                    fetch('/visualizacao', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ anuncio_id: <?= $anuncio_id ?> })
                    });
                }
            }

            if (elementoMidia.tagName === 'VIDEO') {
                elementoMidia.addEventListener('playing', registrarVisualizacao);
            } else {
                if (elementoMidia.complete) {
                    registrarVisualizacao();
                } else {
                    elementoMidia.addEventListener('load', registrarVisualizacao);
                }
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>