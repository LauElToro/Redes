<?php
// Archivo verificar_transaccion_polygon.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/html; charset=utf-8');

    $txHash = $_POST['txHash'] ?? '';
    if (empty($txHash)) {
        echo '<div class="alert alert-danger">Por favor, proporciona un hash de transacción válido.</div>';
        exit;
    }

    // Obtener todos los detalles de la transacción
    $detallesTransaccion = obtenerDetallesTransaccionCompleta($txHash);
    if (isset($detallesTransaccion['error'])) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($detallesTransaccion['error']) . '</div>';
    } else {
        echo '<h5>Detalles de la transacción:</h5>';
        echo '<ul>';
        echo '<li><strong>Hash:</strong> ' . htmlspecialchars($txHash) . '</li>';
        echo '<li><strong>Emisor:</strong> ' . htmlspecialchars($detallesTransaccion['from']) . '</li>';
        echo '<li><strong>Receptor:</strong> ' . htmlspecialchars($detallesTransaccion['to']) . '</li>';
        echo '<li><strong>Red:</strong> Polygon</li>';
        echo '<li><strong>Monto de la transacción:</strong> ' . htmlspecialchars($detallesTransaccion['value']) . ' POL</li>';
        echo '<li><strong>Tarifa de gas:</strong> ' . htmlspecialchars($detallesTransaccion['gas_used']) . ' POL</li>';
        echo '</ul>';

        // Mostrar todos los tokens ERC-721 transferidos
        if (!empty($detallesTransaccion['nfts'])) {
            echo '<h5>NFTs Transferidos:</h5>';
            echo '<ul>';
            foreach ($detallesTransaccion['nfts'] as $nft) {
                // Mostrar solo los tokens que tienen metadatos válidos y una imagen disponible
                if (!isset($nft['error']) && !empty($nft['image']) && filter_var($nft['image'], FILTER_VALIDATE_URL)) {
                    echo '<li><strong>Token ID:</strong> ' . htmlspecialchars($nft['token_id']) . '</li>';
                    echo '<li><strong>URL de metadatos:</strong> ' . htmlspecialchars($nft['metadata_url']) . '</li>';
                    
                    $imagenTemporal = almacenarImagenTemporal($nft['image']);
                    if ($imagenTemporal !== null) {
                        echo '<li><strong>NFT:</strong> <img src="' . htmlspecialchars($imagenTemporal) . '" alt="Imagen del NFT" style="max-width: 200px; max-height: 200px;"></li>';
                    } else {
                        echo '<li><strong>NFT:</strong> Imagen no disponible (no se pudo almacenar)</li>';
                    }
                }
            }
            echo '</ul>';
        }
    }
}

// Función para almacenar la imagen temporalmente
function almacenarImagenTemporal($imageUrl) {
    // Validar si la URL de la imagen es válida antes de intentar descargarla
    if (empty($imageUrl) || $imageUrl === 'No disponible' || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return null; // Devolver null si la URL no es válida
    }

    // Generar un nombre de archivo seguro utilizando un hash de la URL
    $imagenNombre = md5($imageUrl) . '.jpg';
    $rutaTemporal = 'temp/' . $imagenNombre;

    // Asegurarse de que el directorio temporal existe
    if (!file_exists('temp')) {
        mkdir('temp', 0777, true);
    }

    // Descargar y almacenar la imagen si no está ya guardada
    if (!file_exists($rutaTemporal)) {
        $contenidoImagen = @file_get_contents($imageUrl);
        if ($contenidoImagen !== false) {
            file_put_contents($rutaTemporal, $contenidoImagen);
        } else {
            return null; // Devolver null si no se pudo obtener el contenido de la imagen
        }
    }

    return $rutaTemporal;
}

// Función para obtener los detalles completos de la transacción en Polygon
function obtenerDetallesTransaccionCompleta($transactionHash) {
    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $polygonNodeUrl = "https://polygon-mainnet.infura.io/v3/$infuraProjectId";

    $jsonRpcUrl = $polygonNodeUrl;

    // Solicitar el recibo de la transacción usando eth_getTransactionReceipt
    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionReceipt',
        'params' => array($transactionHash),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($jsonRpcUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $transactionJson);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return array('error' => 'Error de conexión: ' . $curlError);
    }

    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('error' => 'Error al decodificar la respuesta JSON: ' . json_last_error_msg());
    }

    if (isset($responseData['error'])) {
        return array('error' => 'Error en la solicitud: ' . $responseData['error']['message']);
    }

    if (isset($responseData['result']) && $responseData['result'] !== null) {
        $transactionReceipt = $responseData['result'];

        // Extraer los detalles principales de la transacción
        $detallesTransaccion = array(
            'from' => $transactionReceipt['from'],
            'to' => $transactionReceipt['to'],
            'value' => isset($transactionReceipt['effectiveGasPrice']) ? hexdec($transactionReceipt['effectiveGasPrice']) * hexdec($transactionReceipt['gasUsed']) / 1e18 : 'No disponible',
            'gas_used' => isset($transactionReceipt['gasUsed']) ? hexdec($transactionReceipt['gasUsed']) : 'No disponible',
            'nfts' => array()
        );

        // Procesar los logs para encontrar transferencias de NFTs (ERC-721)
        foreach ($transactionReceipt['logs'] as $log) {
            if (
                isset($log['topics'][0]) &&
                $log['topics'][0] === '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef' &&
                count($log['topics']) > 3
            ) {
                // Transfer event identifier for ERC-721
                $contractAddress = $log['address'];
                $tokenIdHex = $log['topics'][3];
                $tokenId = hexdec($tokenIdHex);

                // Obtener los metadatos del NFT
                $metadataUrl = obtenerMetadataUrl($contractAddress, $tokenId);
                if ($metadataUrl) {
                    $nftImage = 'No disponible';
                    $metadata = obtenerMetadataNFT($metadataUrl);
                    if ($metadata && isset($metadata['image'])) {
                        // Intentar obtener la imagen desde el campo `image`
                        $nftImage = $metadata['image'];

                        // A veces, la URL de la imagen puede venir con un prefijo "ipfs://", debemos convertirlo a una URL HTTP
                        if (strpos($nftImage, 'ipfs://') === 0) {
                            $nftImage = str_replace('ipfs://', 'https://ipfs.io/ipfs/', $nftImage);
                        }
                    }

                    // Almacenar detalles del NFT en el resultado final solo si hay metadatos válidos
                    if ($nftImage !== 'No disponible') {
                        $detallesTransaccion['nfts'][] = array(
                            'token_id' => $tokenId,
                            'image' => $nftImage,
                            'metadata_url' => $metadataUrl // Guardar la URL de los metadatos después de limpiar
                        );
                    }
                }
            }
        }

        return $detallesTransaccion;
    }

    return array('error' => 'Transacción no encontrada');
}
// Función para obtener la URL de los metadatos del NFT
function obtenerMetadataUrl($contractAddress, $tokenId) {
    // Esta implementación asume el estándar ERC721
    $methodId = '0xc87b56dd';
    $tokenIdHex = str_pad(dechex($tokenId), 64, '0', STR_PAD_LEFT);
    $data = $methodId . $tokenIdHex;

    $infuraProjectId = 'c4732e1e867f4c5e95a982af6f67759b';
    $polygonNodeUrl = "https://polygon-mainnet.infura.io/v3/$infuraProjectId";

    $transaction = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_call',
        'params' => array(
            array(
                'to' => $contractAddress,
                'data' => $data
            ),
            'latest'
        ),
        'id' => 1
    );

    $transactionJson = json_encode($transaction);

    $ch = curl_init($polygonNodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $transactionJson);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    if (isset($responseData['result']) && $responseData['result'] !== '0x') {
        // Eliminar los caracteres que no pertenecen a la URL de los metadatos
        $urlHex = substr($responseData['result'], 2);
        $url = hex2bin($urlHex);

        // Limpiar caracteres no deseados, como números o caracteres que se añadan accidentalmente
        $url = preg_replace('/^[^a-zA-Z]+/', '', $url);

        return trim($url); // Asegurarse de que no tenga caracteres no deseados
    }

    return null;
}

// Función para obtener los metadatos del NFT
function obtenerMetadataNFT($metadataUrl) {
    if (empty($metadataUrl)) {
        return null;
    }

    try {
        $ch = curl_init($metadataUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Error al conectarse a la URL de metadatos: ' . $curlError);
        }

        $metadata = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar la respuesta JSON de metadatos: ' . json_last_error_msg());
        }

        // Si los metadatos contienen un campo de error, devolver null
        if (isset($metadata['error']) && $metadata['error'] === 'Not found') {
            return null;
        }

        return $metadata;
    } catch (Exception $e) {
        error_log('Error al obtener metadatos del NFT: ' . $e->getMessage());
        return null;
    }
}
?>
