<?php
// verificar_transaccion.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hash']) && !empty($_POST['hash'])) {
        $transactionHash = trim($_POST['hash']);

        // Llamada a la función para verificar la transacción
        $estadoTransaccionBSC = verificarTransaccionBSC($transactionHash);

        // Mostrar el estado de la transacción BSC
        echo 'Estado de la transacción: ' . $estadoTransaccionBSC['status'] . '<br>';

        // Si la transacción está completada, mostrar más detalles
        if ($estadoTransaccionBSC['status'] === 'Completada') {
            $detallesTransaccionBSC = obtenerDetallesTransaccionBSC($transactionHash);
            if ($detallesTransaccionBSC['error'] === null) {
                echo 'Hash: ' . $transactionHash . '<br>';
                echo 'Monto de la transacción: ' . $detallesTransaccionBSC['amount'] . ' ' . $detallesTransaccionBSC['symbol'] . '<br>';
                echo 'Emisor: ' . $detallesTransaccionBSC['sender'] . '<br>';
                echo 'Receptor: ' . $detallesTransaccionBSC['receiver'] . '<br>';
                echo 'Red: ' . $detallesTransaccionBSC['network'] . '<br>';
                echo 'Icono de la moneda: <img src="' . $detallesTransaccionBSC['icon'] . '" alt="' . $detallesTransaccionBSC['symbol'] . '" style="width: 20px; height: 20px;"><br>';
            } else {
                echo 'Error: ' . $detallesTransaccionBSC['error'] . '<br>';
            }
        }
    } else {
        echo 'Error: No se proporcionó un hash de transacción válido.';
    }
} else {
    echo 'Error: Método de solicitud no válido.';
}

// Función para verificar el estado de la transacción en Binance Smart Chain
function verificarTransaccionBSC($transactionHash) {
    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $bscNodeUrl = "https://bsc-mainnet.infura.io/v3/$infuraProjectId";

    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionByHash',
        'params' => array($transactionHash),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($bscNodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $transactionJson);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return array('status' => 'Error de conexión: ' . $curlError);
    }

    if ($httpCode !== 200) {
        return array('status' => 'Error HTTP: ' . $httpCode);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('status' => 'Respuesta JSON no válida: ' . json_last_error_msg());
    }

    if (isset($responseData['error'])) {
        return array('status' => 'Error: ' . $responseData['error']['message']);
    }

    if (isset($responseData['result']) && $responseData['result'] !== null) {
        return array('status' => 'Completada');
    }

    return array('status' => 'Estado desconocido');
}

// Función para obtener detalles de la transacción en Binance Smart Chain (BEP-20)
function obtenerDetallesTransaccionBSC($transactionHash) {
    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $bscNodeUrl = "https://bsc-mainnet.infura.io/v3/$infuraProjectId";

    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionReceipt',
        'params' => array($transactionHash),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($bscNodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $transactionJson);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return array(
            'amount' => 'No disponible',
            'symbol' => 'No disponible',
            'sender' => 'No disponible',
            'receiver' => 'No disponible',
            'network' => 'Binance Smart Chain',
            'error' => 'Error de conexión: ' . $curlError,
            'icon' => 'No disponible'
        );
    }

    if ($httpCode !== 200) {
        return array(
            'amount' => 'No disponible',
            'symbol' => 'No disponible',
            'sender' => 'No disponible',
            'receiver' => 'No disponible',
            'network' => 'Binance Smart Chain',
            'error' => 'Error HTTP: ' . $httpCode,
            'icon' => 'No disponible'
        );
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return array(
            'amount' => 'No disponible',
            'symbol' => 'No disponible',
            'sender' => 'No disponible',
            'receiver' => 'No disponible',
            'network' => 'Binance Smart Chain',
            'error' => 'Respuesta JSON no válida: ' . json_last_error_msg(),
            'icon' => 'No disponible'
        );
    }

    if (isset($responseData['error'])) {
        return array(
            'amount' => 'No disponible',
            'symbol' => 'No disponible',
            'sender' => 'No disponible',
            'receiver' => 'No disponible',
            'network' => 'Binance Smart Chain',
            'error' => 'No encontrada: ' . $responseData['error']['message'],
            'icon' => 'No disponible'
        );
    }

    if (isset($responseData['result']) && $responseData['result'] !== null) {
        $logs = $responseData['result']['logs'];
        $tokenTransfer = null;

        foreach ($logs as $log) {
            if (isset($log['topics'][0]) && $log['topics'][0] === '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef') {
                $tokenTransfer = $log;
                break;
            }
        }

        if ($tokenTransfer) {
            $amountHex = $tokenTransfer['data'];
            $amountDecimal = hexdec($amountHex);
            $decimals = 18;  // Suposición de 18 decimales para BEP-20
            $amountScaled = $amountDecimal == 0 ? '0' : bcdiv((string)$amountDecimal, bcpow('10', (string)$decimals, 0), $decimals);

            // Recuperar el símbolo y el icono del token
            $symbol = 'TRX'; // Asumimos TRX como ejemplo
            $iconUrl = 'https://cryptologos.cc/logos/tron-trx-logo.png'; // URL del icono TRX

            return array(
                'amount' => rtrim(rtrim($amountScaled, '0'), '.'),
                'symbol' => $symbol,
                'sender' => '0x' . substr($tokenTransfer['topics'][1], 26),
                'receiver' => '0x' . substr($tokenTransfer['topics'][2], 26),
                'network' => 'Binance Smart Chain',
                'error' => null,
                'icon' => $iconUrl
            );
        }
    }

    return array(
        'amount' => 'No disponible',
        'symbol' => 'No disponible',
        'sender' => 'No disponible',
        'receiver' => 'No disponible',
        'network' => 'Binance Smart Chain',
        'error' => 'Estado desconocido',
        'icon' => 'No disponible'
    );
}
?>
