<?php
// verificar_transaccion.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hash']) && !empty($_POST['hash'])) {
        $transactionHash = trim($_POST['hash']);

        // Llamada a la función para verificar la transacción
        $estadoTransaccionETH = verificarTransaccion('ETH', $transactionHash);

        // Mostrar el estado de la transacción ETH
        echo 'Estado de la transacción: ' . $estadoTransaccionETH . '<br>';

        // Si la transacción está completada, mostrar más detalles
        if ($estadoTransaccionETH === 'Completada') {
            $detallesTransaccionETH = obtenerDetallesTransaccionETH($transactionHash);
            if ($detallesTransaccionETH['error'] === null) {
                echo 'Hash: ' . $transactionHash . '<br>';
                echo 'Monto de la transacción: ' . $detallesTransaccionETH['amount'] . ' ' . $detallesTransaccionETH['symbol'] . '<br>';
                echo 'Emisor: ' . $detallesTransaccionETH['sender'] . '<br>';
                echo 'Receptor: ' . $detallesTransaccionETH['receiver'] . '<br>';
                echo 'Red: ' . $detallesTransaccionETH['network'] . '<br>';
                echo 'Icono de la moneda: <img src="' . $detallesTransaccionETH['icon'] . '" alt="' . $detallesTransaccionETH['symbol'] . '" style="width: 20px; height: 20px;"><br>';
            } else {
                echo 'Error: ' . $detallesTransaccionETH['error'] . '<br>';
            }
        }
    } else {
        echo 'Error: No se proporcionó un hash de transacción válido.';
    }
} else {
    echo 'Error: Método de solicitud no válido.';
}

// Función para verificar el estado de la transacción en Ethereum
function verificarTransaccion($currency, $transactionHash) {
    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $ethereumNodeUrl = "https://mainnet.infura.io/v3/$infuraProjectId";

    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionByHash',
        'params' => array($transactionHash),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($ethereumNodeUrl);
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

// Función para obtener detalles de la transacción Ethereum
function obtenerDetallesTransaccionETH($transactionHash) {
    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $ethereumNodeUrl = "https://mainnet.infura.io/v3/$infuraProjectId";

    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionReceipt',
        'params' => array($transactionHash),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($ethereumNodeUrl);
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
            'network' => 'Ethereum',
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
            'network' => 'Ethereum',
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
            'network' => 'Ethereum',
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
            'network' => 'Ethereum',
            'error' => 'No encontrada: ' . $responseData['error']['message'],
            'icon' => 'No disponible'
        );
    }

    if (isset($responseData['result']) && $responseData['result'] !== null) {
        $logs = $responseData['result']['logs'];
        $tokenTransfer = null;

        foreach ($logs as $log) {
            if (isset($log['topics'][0]) && $log['topics'][0] === '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef') {
                // Evento de Transferencia de Token ERC-20
                $tokenTransfer = $log;
                break;
            }
        }

        if ($tokenTransfer) {
            // Cargar información de los tokens top 50 desde Etherscan
            $topTokensData = json_decode(file_get_contents('https://etherscan.io/token/tokenholderchart/'), true);
            if ($topTokensData === null) {
                return array(
                    'amount' => 'No disponible',
                    'symbol' => 'No disponible',
                    'sender' => 'No disponible',
                    'receiver' => 'No disponible',
                    'network' => 'Ethereum',
                    'error' => 'Esta moneda no está listada',
                    'icon' => 'No disponible'
                );
            }
            $topTokens = array_slice($topTokensData, 0, 50);
            $contractAddress = strtolower($tokenTransfer['address']);
            $tokenInfo = null;

            foreach ($topTokens as $token) {
                if (strtolower($token['address']) === $contractAddress) {
                    $tokenInfo = $token;
                    break;
                }
            }

            if ($tokenInfo) {
                $amountHex = $tokenTransfer['data'];
                $amountDecimal = hexdec($amountHex);
                $decimals = (int)$tokenInfo['decimals'];
                if ($amountDecimal == 0) {
                    $amountScaled = '0';
                } else {
                    $amountDecimalStr = number_format($amountDecimal, 0, '', '');
                    $amountScaled = bcdiv($amountDecimalStr, bcpow('10', (string)$decimals, 0), $decimals);
                }
                $symbol = $tokenInfo['symbol'];
                $iconUrl = $tokenInfo['logoURI'];

                return array(
                    'amount' => rtrim(rtrim($amountScaled, '0'), '.'), 
                    'symbol' => $symbol,
                    'sender' => '0x' . substr($tokenTransfer['topics'][1], 26),
                    'receiver' => '0x' . substr($tokenTransfer['topics'][2], 26),
                    'network' => 'Ethereum',
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
        'network' => 'Ethereum',
        'error' => 'Esta moneda no está listada',
        'icon' => 'No disponible'
    );
}
?>