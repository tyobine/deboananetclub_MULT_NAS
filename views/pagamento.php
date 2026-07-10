<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - Wi-Fi</title>
    <link href="/src/css/bootstrap.min.css" rel="stylesheet">
    <link href="/src/css/style.css" rel="stylesheet">
</head>

<body class="pagina-pix">
    <div class="pix-container px-3 text-center py-4 mx-auto">
        <div class="card">
            <div class="card-body text-center p-5">
                <h2 class="mb-4">Pagamento via PIX</h2>

                <div class="mb-4">
                    <p class="text-muted mb-2">Plano selecionado: Acesso <?php echo $plano_horas; ?> Horas</p>
                    <h3 class="text-primary">R$ <?php echo number_format($valor, 2, ',', '.'); ?></h3>
                </div>

                <div id="payment-status" class="mb-4">
                    <div class="alert alert-info">
                        <svg class="spinner" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25" />
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" />
                        </svg>
                        Aguardando pagamento...
                    </div>
                </div>

                <div class="qr-code-container mb-4" id="qr-code-box">
                    <img src="data:image/png;base64,<?php echo $qr_code_img; ?>"
                        alt="QR Code PIX" style="max-width: 300px; width: 100%;">
                </div>

                <div class="mb-4" id="pix-copy-box">
                    <p class="small text-muted">Ou copie o código PIX:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="pix-code" value="<?php echo htmlspecialchars($qr_code); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPixCode()">Copiar</button>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="/inicio?mac=<?= urlencode($mac) ?>&ip=<?= urlencode($ip) ?>" class="btn btn-outline-danger w-100 py-2">
                            <i class="bi bi-arrow-left-circle"></i> Cancelar e Voltar aos Planos
                        </a>
                    </div>
                </div>

                <p class="small text-muted" id="wait-msg">Aguarde. A tela será atualizada sozinha após o pagamento.</p>
            </div>
        </div>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code');
            pixCode.select();
            document.execCommand('copy');
            alert('Código PIX copiado!');
        }

        let checkInterval;

        function checkPaymentStatus() {
            fetch('/checar-status?txid=<?php echo $txid; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ativo') {
                        clearInterval(checkInterval);

                        document.getElementById('payment-status').innerHTML = `
                            <div class="alert alert-success">
                                <strong>Pagamento Confirmado!</strong><br>
                                Autenticando no roteador de forma segura...
                            </div>`;

                        const mac = encodeURIComponent(data.mac);
                        const destinoFinal = encodeURIComponent(`https://deboananet.club/inicio?mac=${data.mac}`);

                        // Resgata o gateway de hotspot que configuramos no config.php para esta cidade
                        const mikrotikGateway = "<?php echo ROUTERS[$router_id]['hotspot_ip'] ?? ROUTERS[ROUTER_DEFAULT]['hotspot_ip']; ?>";

                        window.location.href = `http://${mikrotikGateway}/login?username=${mac}&password=${mac}&dst=${destinoFinal}`;

                    } else if (data.status === 'estornado') {
                        clearInterval(checkInterval);

                        document.getElementById('qr-code-box').style.display = 'none';
                        document.getElementById('pix-copy-box').style.display = 'none';
                        document.getElementById('wait-msg').style.display = 'none';

                        document.getElementById('payment-status').innerHTML = `
                            <div class="alert alert-danger">
                                <strong>Falha de Conexão com o Roteador!</strong><br>
                                Fique tranquilo, o valor do seu PIX já foi estornado automaticamente para a sua conta.
                            </div>`;

                        setTimeout(() => {
                            window.location.href = '/inicio';
                        }, 6000);
                    }
                });
        }

        checkInterval = setInterval(checkPaymentStatus, 3000);
    </script>
</body>

</html>