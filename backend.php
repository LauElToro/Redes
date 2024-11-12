<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el hash de transacción desde la solicitud POST
    $transactionHash = $_POST['transactionHash'] ?? '';

    // Validar que se ha ingresado un hash
    if (empty($transactionHash)) {
        echo json_encode(['error' => 'No se proporcionó un hash de transacción.']);
        exit;
    }

    // Validar el formato del hash
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $transactionHash)) {
        echo json_encode(['error' => 'El hash de transacción no es válido.']);
        exit;
    }

    // Función para obtener información de la transacción de Bitcoin
    function getBitcoinTransactionInfo($transactionHash) {
        // URL de la API con el token
        $apiToken = "e3b7f91917584e2a8be6c7d2d7264a5b"; // Reemplaza esto con tu propio token si es necesario
        $url = "https://api.blockcypher.com/v1/btc/main/txs/" . $transactionHash . "?token=" . $apiToken;

        // Inicializar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // Establecer un tiempo de espera más alto

        // Ejecutar la consulta y manejar errores de cURL
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Error en la solicitud cURL: ' . $error_msg];
        }
        curl_close($ch);

        // Decodificar la respuesta JSON
        $transactionData = json_decode($response, true);

        // Comprobar si la transacción existe o si hubo un error
        if (isset($transactionData['error'])) {
            return ['error' => 'Error de la API: ' . $transactionData['error']];
        }

        $amount = 0;
        $sender = '';
        $receiver = '';
        $status = 'Pendiente'; // Estado inicial por defecto

        // Sumar todos los valores de los outputs
        if (isset($transactionData['outputs'])) {
            foreach ($transactionData['outputs'] as $output) {
                $amount += $output['value'];
            }
        }

        // Convertir de satoshis a BTC
        $amount = $amount / 100000000;

        // Obtener la primera dirección del remitente y destinatario (solo para simplificar)
        if (!empty($transactionData['inputs'])) {
            $sender = $transactionData['inputs'][0]['addresses'][0] ?? 'Desconocido';
        }
        if (!empty($transactionData['outputs'])) {
            $receiver = $transactionData['outputs'][0]['addresses'][0] ?? 'Desconocido';
        }

        // Determinar el estado de la transacción basado en las confirmaciones
        if (isset($transactionData['confirmations'])) {
            if ($transactionData['confirmations'] == 0) {
                $status = 'Pendiente';
            } elseif ($transactionData['confirmations'] > 0 && $transactionData['confirmations'] < 6) {
                $status = 'Aprobado';
            } elseif ($transactionData['confirmations'] >= 6) {
                $status = 'Completado';
            }
        }

        return [
            'amount' => $amount,
            'symbol' => 'BTC',
            'sender' => $sender,
            'receiver' => $receiver,
            'transaction_action' => 'Transfer',
            'network' => 'Bitcoin Mainnet',
            'status' => $status,
            'confirmations' => $transactionData['confirmations'] ?? 0, // Añadir el número de confirmaciones para diagnosticar
        ];
    }

    $transactionInfo = getBitcoinTransactionInfo($transactionHash);
    echo json_encode($transactionInfo);
}
?>
