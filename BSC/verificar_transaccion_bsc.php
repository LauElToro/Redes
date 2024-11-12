<?php
// verificar_transaccion.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hash']) && !empty($_POST['hash'])) {
        $transactionHash = trim($_POST['hash']);

        // Llamada a la función para verificar la transacción
        $estadoTransaccionBSC = verificarTransaccionBSC($transactionHash);

        // Mostrar el estado de la transacción BSC
        echo 'Estado de la transacción: ' . $estadoTransaccionBSC . '<br>';

        // Si la transacción está completada, mostrar más detalles
        if ($estadoTransaccionBSC === 'Completada') {
            $detallesTransaccionBSC = obtenerDetallesTransaccionBSC($transactionHash);
            if ($detallesTransaccionBSC['error'] === null) {
                echo 'Hash: ' . $transactionHash . '<br>';
                echo 'Monto de la transacción: ' . $detallesTransaccionBSC['amount'] . ' ' . $detallesTransaccionBSC['symbol'] . '<br>';
                echo 'Emisor: ' . $detallesTransaccionBSC['sender'] . '<br>';
                echo 'Receptor: ' . $detallesTransaccionBSC['receiver'] . '<br>';
                echo 'Red: ' . $detallesTransaccionBSC['network'] . '<br>';
                if ($detallesTransaccionBSC['icon'] !== 'No disponible') {
                    echo 'Icono de la moneda: <img src="' . $detallesTransaccionBSC['icon'] . '" alt="' . $detallesTransaccionBSC['symbol'] . '" style="width: 20px; height: 20px;"><br>';
                }
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
    $bscNodeUrl = "https://bsc-dataseed.binance.org/";

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
        return 'Error de conexión: ' . $curlError;
    }

    if ($httpCode !== 200) {
        return 'Error HTTP: ' . $httpCode;
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Respuesta JSON no válida: ' . json_last_error_msg();
    }

    if (isset($responseData['error'])) {
        return 'No encontrada: ' . $responseData['error']['message'];
    }

    if (isset($responseData['result']) && $responseData['result'] !== null) {
        return 'Completada';
    }

    return 'Estado desconocido';
}

// Función para obtener detalles de la transacción en Binance Smart Chain
function obtenerDetallesTransaccionBSC($transactionHash) {
    $bscNodeUrl = "https://bsc-dataseed.binance.org/";

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
            $tokensData = json_decode(file_get_contents('https://api.bscscan.com/api?module=token&action=listtokens&apikey=YourApiKeyToken'), true);
            if (!isset($tokensData['result']) || !is_array($tokensData['result'])) {
                return array(
                    'amount' => 'No disponible',
                    'symbol' => 'No disponible',
                    'sender' => 'No disponible',
                    'receiver' => 'No disponible',
                    'network' => 'Binance Smart Chain',
                    'error' => 'Esta moneda no está listada',
                    'icon' => 'No disponible'
                );
            }
            $contractAddress = strtolower($tokenTransfer['address']);
            $tokenInfo = array_filter($tokensData['result'], function($token) use ($contractAddress) {
                return strtolower($token['contractAddress']) === $contractAddress;
            });

            if (!empty($tokenInfo)) {
                $tokenInfo = array_values($tokenInfo)[0];
                $amountHex = $tokenTransfer['data'];
                $amountDecimal = hexdec($amountHex);
                $decimals = (int)$tokenInfo['decimals'];
                $amountScaled = $amountDecimal == 0 ? '0' : bcdiv((string)$amountDecimal, bcpow('10', (string)$decimals, 0), $decimals);
                $symbol = $tokenInfo['symbol'];
                $iconUrl = $tokenInfo['tokenLogo'];

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
    }

    return array(
        'amount' => 'No disponible',
        'symbol' => 'No disponible',
        'sender' => 'No disponible',
        'receiver' => 'No disponible',
        'network' => 'Binance Smart Chain',
        'error' => 'Esta moneda no está listada',
        'icon' => 'No disponible'
    );
}
?>
