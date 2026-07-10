<a href="#" class="btn-float-suporte" data-bs-toggle="modal" data-bs-target="#modalSuporte">
    <i class="fa-solid fa-headset"></i>
</a>

<div class="modal fade" id="modalSuporte" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #1e3a8a;">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-headset me-2"></i> Ajuda e Suporte</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="this.blur()"></button>
            </div>

            <div class="modal-body p-0">
                <div id="chatBox" class="chat-box m-2">

                    <div class="msg-bot">
                        <strong>Assistente Virtual 🤖</strong><br>
                        Olá! Vi que precisa de ajuda. Sobre qual assunto quer falar?
                    </div>

                    <div id="opcoesContainer" class="d-flex flex-column align-items-start mt-2">
                        <button class="btn btn-pergunta w-100" onclick="enviarPergunta('Fiz o PIX, mas a internet não liberou!', 'O sistema pode levar até 30 segundos para confirmar o pagamento através do banco. Por favor, aguarde ou tente conectar e desconectar do Wi-Fi. O seu pagamento está salvo!')">
                            Fiz o PIX, mas a internet não liberou.
                        </button>
                        <button class="btn btn-pergunta w-100" onclick="enviarPergunta('Onde acho o PIX copia e cola?', 'Ao escolher um plano pago e prosseguir, você verá o QR Code e um botão verde logo abaixo dele escrito \'Copiar Código PIX\'.')">
                            Onde acho o PIX Copia e Cola?
                        </button>
                        <button class="btn btn-pergunta w-100" onclick="enviarPergunta('Minha internet está lenta.', 'Se você está no plano Patrocinado (Grátis), a velocidade é reduzida para serviços básicos. Para vídeos rápidos, adquira nosso passe Premium VIP.')">
                            Minha internet está lenta ou caindo.
                        </button>

                        <button class="btn btn-pergunta w-100 mt-2 text-success border-success fw-bold" onclick="chamarHumano()">
                            <i class="fa-brands fa-whatsapp"></i> Meu problema é outro (Falar com Humano)
                        </button>
                    </div>

                    <div id="caixaMensagem" class="d-none mt-2 w-100">
                        <textarea id="textoSuporte" class="form-control mb-1" rows="3" placeholder="Descreva o problema aqui..." oninput="verificarTamanhoTexto()"></textarea>

                        <div class="text-end mb-2">
                            <small id="contadorCaracteres" class="text-muted fw-bold" style="font-size: 0.75rem;">0 / 50 caracteres mínimos</small>
                        </div>

                        <label class="form-label small text-muted mb-1"><i class="fa-solid fa-paperclip"></i> Anexar Comprovante (Opcional)</label>
                        <input type="file" id="comprovanteSuporte" class="form-control form-control-sm mb-3" accept="image/*,.pdf">

                        <button id="btnEnviarSuporte" class="btn btn-secondary w-100 fw-bold" onclick="enviarParaTelegram()" disabled>
                            <i class="fa-solid fa-paper-plane me-1"></i> Enviar Chamado
                        </button>
                    </div>

                    <div id="digitando" class="msg-bot d-none mt-3">
                        <div class="typing-indicator">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const opcoesContainer = document.getElementById('opcoesContainer');
    const digitandoIndicator = document.getElementById('digitando');

    // Configuração do Limite
    const MIN_CARACTERES = 50;

    // 1. Respostas locais da Assistente Virtual
    function enviarPergunta(pergunta, resposta) {
        opcoesContainer.classList.add('d-none');

        const userMsg = document.createElement('div');
        userMsg.className = 'msg-user';
        userMsg.innerText = pergunta;
        chatBox.insertBefore(userMsg, digitandoIndicator);

        digitandoIndicator.classList.remove('d-none');
        chatBox.scrollTop = chatBox.scrollHeight;

        setTimeout(() => {
            digitandoIndicator.classList.add('d-none');

            const botMsg = document.createElement('div');
            botMsg.className = 'msg-bot';
            botMsg.innerHTML = '<strong>Assistente Virtual 🤖</strong><br>' + resposta;
            chatBox.insertBefore(botMsg, digitandoIndicator);

            chatBox.insertBefore(opcoesContainer, digitandoIndicator);
            opcoesContainer.classList.remove('d-none');

            chatBox.scrollTop = chatBox.scrollHeight;
        }, 1200);
    }

    // 2. Abre espaço para o cliente digitar e anexar arquivos
    function chamarHumano() {
        opcoesContainer.classList.add('d-none');
        const caixa = document.getElementById('caixaMensagem');
        caixa.classList.remove('d-none');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // NOVA FUNÇÃO: Libera o botão de envio apenas quando a meta for batida
    function verificarTamanhoTexto() {
        const texto = document.getElementById('textoSuporte').value.trim();
        const qtd = texto.length;

        const contador = document.getElementById('contadorCaracteres');
        const btnEnviar = document.getElementById('btnEnviarSuporte');

        contador.innerText = `${qtd} / ${MIN_CARACTERES} caracteres mínimos`;

        if (qtd >= MIN_CARACTERES) {
            // Atingiu a meta: Botão Verde e Liberado
            btnEnviar.classList.remove('btn-secondary');
            btnEnviar.classList.add('btn-success');
            btnEnviar.disabled = false;

            contador.classList.remove('text-muted', 'text-danger');
            contador.classList.add('text-success');
        } else {
            // Não atingiu a meta: Botão Cinza e Bloqueado
            btnEnviar.classList.remove('btn-success');
            btnEnviar.classList.add('btn-secondary');
            btnEnviar.disabled = true;

            contador.classList.remove('text-success', 'text-muted');
            contador.classList.add('text-danger');
        }
    }

    // 3. O Envio Real
    function enviarParaTelegram() {
        const texto = document.getElementById('textoSuporte').value.trim();
        const arquivoInput = document.getElementById('comprovanteSuporte');
        const arquivo = arquivoInput.files.length > 0 ? arquivoInput.files[0] : null;

        // Limite de segurança de tamanho do arquivo (Máx 5MB)
        if (arquivo && arquivo.size > 5 * 1024 * 1024) {
            alert("O arquivo é muito grande. O limite máximo permitido é de 5MB.");
            return;
        }

        document.getElementById('caixaMensagem').classList.add('d-none');

        const userMsg = document.createElement('div');
        userMsg.className = 'msg-user';
        userMsg.innerHTML = texto;
        chatBox.insertBefore(userMsg, digitandoIndicator);

        digitandoIndicator.classList.remove('d-none');
        chatBox.scrollTop = chatBox.scrollHeight;

        // Captura o MAC e IP
        const macCliente = document.querySelector('input[name="mac"]')?.value || 'Sem MAC';
        const ipCliente = document.querySelector('input[name="ip"]')?.value || 'Sem IP';

        // Empacota os dados
        const formData = new FormData();
        formData.append('mensagem', texto);
        formData.append('mac', macCliente);
        formData.append('ip', ipCliente);
        if (arquivo) {
            formData.append('comprovante', arquivo);
        }

        fetch('views/enviar_suporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                digitandoIndicator.classList.add('d-none');
                const botMsg = document.createElement('div');
                botMsg.className = 'msg-bot';

                if (data.sucesso) {
                    botMsg.innerHTML = '<strong>Assistente Virtual 🤖</strong><br>✅ Chamado entregue com sucesso!<br><br>O seu endereço (MAC) já foi identificado. Por favor, <strong>aguarde de 2 a 5 minutos</strong> e tente recarregar qualquer página para verificar se o acesso foi libertado remotamente.';
                } else {
                    botMsg.innerHTML = '<strong>Assistente Virtual 🤖</strong><br>❌ Erro ao enviar para os técnicos. Por favor, tente novamente.';
                }

                chatBox.insertBefore(botMsg, digitandoIndicator);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                console.error('Erro no envio:', error);
                digitandoIndicator.classList.add('d-none');

                const botMsg = document.createElement('div');
                botMsg.className = 'msg-bot';
                botMsg.innerHTML = '<strong>Assistente Virtual 🤖</strong><br>❌ Ocorreu um erro de rede. Verifique a sua conexão.';
                chatBox.insertBefore(botMsg, digitandoIndicator);
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    // Limpa o chat e reseta tudo para o estado original quando o modal é fechado
    document.getElementById('modalSuporte').addEventListener('hidden.bs.modal', function() {
        const mensagens = chatBox.querySelectorAll('.msg-user, .msg-bot:not(:first-child):not(#digitando)');
        mensagens.forEach(msg => msg.remove());

        document.getElementById('textoSuporte').value = '';
        document.getElementById('comprovanteSuporte').value = '';
        document.getElementById('caixaMensagem').classList.add('d-none');
        opcoesContainer.classList.remove('d-none');

        // Reseta o visual do contador e do botão
        const btnEnviar = document.getElementById('btnEnviarSuporte');
        const contador = document.getElementById('contadorCaracteres');

        btnEnviar.classList.remove('btn-success');
        btnEnviar.classList.add('btn-secondary');
        btnEnviar.disabled = true;

        contador.innerText = `0 / ${MIN_CARACTERES} caracteres mínimos`;
        contador.className = 'text-muted fw-bold';
    });
</script>