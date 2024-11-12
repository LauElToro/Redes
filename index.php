<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Transacción Bitcoin</title>
</head>
<body>
    <h2>Consulta de Transacción Bitcoin</h2>
    <form id="transactionForm">
        <label for="transactionHash">Hash de Transacción BTC:</label><br>
        <input type="text" id="transactionHash" name="transactionHash" required><br><br>
        <button type="submit">Consultar</button>
    </form>
    <div id="transactionResult"></div>

    <script>
        let checkInterval;

        document.getElementById('transactionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            let transactionHash = document.getElementById('transactionHash').value.trim();

            // Validar que el hash tiene el formato adecuado
            if (!isValidHash(transactionHash)) {
                document.getElementById('transactionResult').innerHTML = '<strong>Error:</strong> El hash de transacción proporcionado no es válido.';
                return;
            }

            // Limpiar cualquier resultado anterior
            document.getElementById('transactionResult').innerHTML = '';

            // Hacemos la solicitud al backend PHP usando AJAX (fetch API)
            fetchTransactionStatus(transactionHash);

            // Configurar un intervalo para verificar el estado cada 30 segundos
            checkInterval = setInterval(function() {
                checkTransactionStatus(transactionHash);
            }, 30000);
        });

        function isValidHash(hash) {
            const hashRegex = /^[a-fA-F0-9]{64}$/;
            return hashRegex.test(hash);
        }

        function fetchTransactionStatus(transactionHash) {
            fetch('backend.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'transactionHash=' + encodeURIComponent(transactionHash)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    document.getElementById('transactionResult').innerHTML = '<strong>Error:</strong> ' + data.error;
                    clearIntervalIfNeeded();
                } else {
                    document.getElementById('transactionResult').innerHTML = `
                        <p><strong>Monto:</strong> ${data.amount} ${data.symbol}</p>
                        <p><strong>Remitente:</strong> ${data.sender}</p>
                        <p><strong>Destinatario:</strong> ${data.receiver}</p>
                        <p><strong>Acción:</strong> ${data.transaction_action}</p>
                        <p><strong>Red:</strong> ${data.network}</p>
                        <p><strong>Estado:</strong> ${data.status}</p>
                    `;

                    // Si la transacción ya está aprobada, detenemos la verificación
                    if (data.status === 'Aprobado') {
                        clearIntervalIfNeeded();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('transactionResult').innerText = 'Hubo un error procesando la solicitud: ' + error.message;
                clearIntervalIfNeeded();
            });
        }

        function checkTransactionStatus(transactionHash) {
            console.log('Verificando el estado de la transacción...');
            fetchTransactionStatus(transactionHash);
        }

        function clearIntervalIfNeeded() {
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
        }
    </script>
</body>
</html>
