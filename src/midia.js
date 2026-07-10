// src/midia.js

(function () {
    var tempoRestante = 15;
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
            btnLiberar.innerText = "Preencha os dados e aceite os termos";
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
        if (spinner) spinner.style.display = 'none';
        if (mediaElement) mediaElement.style.display = 'block';
        if (btnSom) btnSom.style.display = 'block';
        if (progressBar) progressBar.style.display = 'block';

        if (statusBox) {
            statusBox.innerHTML = 'Assista por <span id="countdown">15</span> seg...';
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

    if (mediaElement) {
        var mediaCarregada = false;
        var eventoCarregamento = mediaElement.tagName === 'VIDEO' ? 'canplaythrough' : 'load';

        mediaElement.addEventListener(eventoCarregamento, function () {
            if (!mediaCarregada) {
                mediaCarregada = true;
                iniciarCronometro();
            }
        });

        setTimeout(function () {
            if (!mediaCarregada) {
                mediaCarregada = true;
                iniciarCronometro();
            }
        }, 6000);
    } else {
        iniciarCronometro();
    }

    // INTERCEPTAÇÃO DO ENVIO (RESOLUÇÃO DO FALSO POSITIVO)
    if (formLiberar && btnLiberar) {
        formLiberar.addEventListener('submit', function (e) {
            e.preventDefault(); // Impede o envio tradicional por página

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
                        // SUCESSO CONFIRMADO: Utilizador criado na aba Users do MikroTik.
                        // CRIAMOS UM FORMULÁRIO POST INVISÍVEL PARA DRIBLAR O BLOQUEIO DE SEGURANÇA DOS NAVEGADORES

                        var macCli = encodeURIComponent(data.mac);
                        var urlSucesso = 'http://deboananet.club/sucesso?mac=' + macCli;

                        var formLogin = document.createElement('form');
                        formLogin.method = 'POST';
                        formLogin.action = 'http://' + data.hotspot_ip + '/login';

                        var inputUser = document.createElement('input');
                        inputUser.type = 'hidden';
                        inputUser.name = 'username';
                        inputUser.value = data.mac; // Envia o MAC limpo

                        var inputPass = document.createElement('input');
                        inputPass.type = 'hidden';
                        inputPass.name = 'password';
                        inputPass.value = data.mac; // Envia o MAC como senha limpo

                        var inputDst = document.createElement('input');
                        inputDst.type = 'hidden';
                        inputDst.name = 'dst';
                        inputDst.value = urlSucesso;

                        // Anexa o formulário invisível à página e clica em enviar instantaneamente
                        formLogin.appendChild(inputUser);
                        formLogin.appendChild(inputPass);
                        formLogin.appendChild(inputDst);
                        document.body.appendChild(formLogin);

                        formLogin.submit();

                    } else {
                        // SE FALHAR A COMUNICAÇÃO: Mostra caixa vermelha limpa integrada na interface
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
                    // SE O SERVIDOR CAIR OU HOUVER UMA DESCONEXÃO TOTAL
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