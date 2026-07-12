(function () {
    // Agora lê dinamicamente o valor do banco de dados injetado pela View (Fallback: 15)
    var tempoRestante = (typeof window.tempoAnuncioGlobal !== 'undefined') ? parseInt(window.tempoAnuncioGlobal) : 15;
    var countdownElement = null;
    var btnLiberar = document.getElementById('btn-liberar');
    var statusBox = document.getElementById('status-box');
    var formLiberar = document.querySelector('form');
    var mediaElement = document.getElementById('ad-media');
    var spinner = document.getElementById('loading-spinner');
    var wppBox = document.getElementById('whatsapp-box');
    var wppInput = document.getElementById('whatsapp-input');
    var lgpdCheck = document.getElementById('lgpd-check');
    var btnSom = document.getElementById('btn-som');
    var progressBar = document.getElementById('video-progress');
    var tempoEsgotado = false;
    var mediaCarregada = false;

    // Máscara do WhatsApp
    if (wppInput) {
        wppInput.addEventListener('input', function (e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            verificarLiberacao();
        });
    }

    if (lgpdCheck) lgpdCheck.addEventListener('change', verificarLiberacao);

    function verificarLiberacao() {
        if (tempoEsgotado && wppInput.value.length >= 14 && lgpdCheck.checked) {
            btnLiberar.removeAttribute('disabled');
            btnLiberar.className = "btn btn-success btn-lg w-100 fw-bold";
            btnLiberar.innerText = "🚀 Liberar Minha Internet!";
        } else if (tempoEsgotado) {
            btnLiberar.setAttribute('disabled', 'true');
            btnLiberar.className = "btn btn-secondary btn-lg w-100 fw-bold";
            btnLiberar.innerText = "Preencha os dados e acerte os termos";
        }
    }

    if (mediaElement && mediaElement.tagName === 'VIDEO') {
        if (btnSom) {
            btnSom.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (mediaElement.muted) {
                    mediaElement.muted = false;
                    btnSom.innerHTML = "🔊 Desligar Som";
                } else {
                    mediaElement.muted = true;
                    btnSom.innerHTML = "🔇 Ligar Som";
                }
            });
        }
        if (progressBar) {
            mediaElement.addEventListener('timeupdate', function () {
                if (mediaElement.duration) {
                    var percentage = (mediaElement.currentTime / mediaElement.duration) * 100;
                    progressBar.value = percentage;
                }
            });
            progressBar.addEventListener('input', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var seekTime = (progressBar.value / 100) * mediaElement.duration;
                mediaElement.currentTime = seekTime;
            });
        }
    }

    function iniciarCronometro() {
        if (mediaCarregada) return; // Trava de segurança para impedir múltiplas execuções
        mediaCarregada = true;

        if (spinner) spinner.style.display = 'none';
        if (mediaElement) mediaElement.style.display = 'block';
        if (btnSom) btnSom.style.display = 'block';
        if (progressBar) progressBar.style.display = 'block';

        if (statusBox) {
            // Ajustado para renderizar o tempo dinâmico também no texto da interface
            statusBox.innerHTML = 'Assista por <span id="countdown">' + tempoRestante + '</span> seg...';
            countdownElement = document.getElementById('countdown');
        }
        if (btnLiberar) btnLiberar.innerText = "⏳ Assista ao anúncio...";

        var cronometro = setInterval(function () {
            tempoRestante--;
            if (tempoRestante > 0) {
                if (countdownElement) countdownElement.innerText = tempoRestante;
            } else {
                clearInterval(cronometro);
                tempoEsgotado = true;

                wppBox.style.display = 'block';
                wppInput.focus();

                if (statusBox) {
                    statusBox.className = "alert alert-success py-2 mb-2";
                    statusBox.innerHTML = "<strong>Tempo concluído!</strong> Preencha os dados abaixo.";
                }
                verificarLiberacao();
            }
        }, 1000);
    }

    // RESOLUÇÃO DE RACE CONDITION NO CARREGAMENTO DA MÍDIA (OTIMIZADO PARA HOTSPOT)
    if (mediaElement) {
        if (mediaElement.tagName === 'VIDEO') {
            // Em vez de esperar 'canplaythrough' (baixar muito do vídeo), 
            // liberamos o cronômetro no 'loadeddata' (baixou o primeiro frame)
            if (mediaElement.readyState >= 2) { 
                iniciarCronometro();
            } else {
                mediaElement.addEventListener('loadeddata', iniciarCronometro, { once: true });
                mediaElement.addEventListener('playing', iniciarCronometro, { once: true });
            }
        } else {
            // Verifica se a imagem já foi resolvida (cache do navegador)
            if (mediaElement.complete && mediaElement.naturalHeight !== 0) {
                iniciarCronometro();
            } else {
                mediaElement.addEventListener('load', iniciarCronometro, { once: true });
                mediaElement.addEventListener('error', iniciarCronometro, { once: true });
            }
        }

        // Failsafe Agressivo: Reduzido de 6 para 2.5 segundos. 
        // Se a rede estiver muito estrangulada, o cliente não pode ficar preso no spinner.
        // O cronômetro precisa rodar para ele poder preencher os dados e liberar a rede.
        setTimeout(function () {
            if (!mediaCarregada) iniciarCronometro();
        }, 2500);
    } else {
        iniciarCronometro();
    }

    // INTERCEPTAÇÃO DO ENVIO PARA LOGIN INVISÍVEL
    if (formLiberar && btnLiberar) {
        formLiberar.addEventListener('submit', function (e) {
            e.preventDefault();

            if (wppInput.value.length < 14 || !lgpdCheck.checked) {
                alert("Por favor, preencha o WhatsApp corretamente e aceite os termos.");
                return;
            }

            btnLiberar.setAttribute('disabled', 'true');
            btnLiberar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> A conectar com a torre...';

            var formData = new FormData(formLiberar);

            fetch('/liberar-gratis-confirmado', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso === true) {
                        // SUCESSO CONFIRMADO: Criação de form POST invisível para auto-login
                        var macCli = encodeURIComponent(data.mac);
                        var urlSucesso = window.location.origin + '/sucesso?mac=' + macCli;

                        var formLogin = document.createElement('form');
                        formLogin.method = 'POST';
                        formLogin.action = 'http://' + data.hotspot_ip + '/login';
                        formLogin.style.display = 'none';

                        var inputUser = document.createElement('input');
                        inputUser.type = 'hidden';
                        inputUser.name = 'username';
                        inputUser.value = data.mac;

                        var inputPass = document.createElement('input');
                        inputPass.type = 'hidden';
                        inputPass.name = 'password';
                        inputPass.value = data.mac;

                        var inputDst = document.createElement('input');
                        inputDst.type = 'hidden';
                        inputDst.name = 'dst';
                        inputDst.value = urlSucesso;

                        formLogin.appendChild(inputUser);
                        formLogin.appendChild(inputPass);
                        formLogin.appendChild(inputDst);
                        document.body.appendChild(formLogin);

                        formLogin.submit();
                    } else {
                        if (statusBox) {
                            statusBox.className = "alert alert-danger py-3 mb-3 text-center border border-danger shadow-sm";
                            statusBox.innerHTML = '<h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation"></i> Aviso da Rede</h6><small>' + (data.mensagem || "Falha de comunicação com a torre.") + '</small>';
                        }
                        btnLiberar.removeAttribute('disabled');
                        btnLiberar.className = "btn btn-outline-danger btn-lg w-100 fw-bold";
                        btnLiberar.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Tentar Novamente';
                    }
                })
                .catch(error => {
                    if (statusBox) {
                        statusBox.className = "alert alert-dark py-3 mb-3 text-center border shadow-sm";
                        statusBox.innerHTML = '<h6 class="fw-bold mb-1"><i class="fa-solid fa-wifi"></i> Torre Offline</h6><small>Não foi possível conectar. A torre parece estar sem energia ou internet.</small>';
                    }
                    btnLiberar.removeAttribute('disabled');
                    btnLiberar.className = "btn btn-outline-dark btn-lg w-100 fw-bold";
                    btnLiberar.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Tentar Novamente';
                });
        });
    }
})();