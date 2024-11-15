<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificador de Transacciones Ethereum</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>Verificar Estado de Transacción Ethereum</h2>
    <div class="form-group">
        <label for="transactionHash">Hash de la Transacción:</label>
        <input type="text" id="transactionHash" class="form-control" placeholder="Introduce el hash de la transacción">
    </div>
    <button id="verificarBtn" class="btn btn-primary">Verificar</button>
    <div id="resultado" class="mt-4"></div>
</div>

<script>
    $(document).ready(function () {
        $('#verificarBtn').click(function () {
            var transactionHash = $('#transactionHash').val().trim();

            if (transactionHash === '') {
                $('#resultado').html('<div class="alert alert-warning">Por favor, introduce un hash de transacción válido.</div>');
                return;
            }

            $.ajax({
                url: 'verificar_transaccion_limitada.php', 
                type: 'POST',
                data: { hash: transactionHash },
                success: function (response) {
                    $('#resultado').html(response);
                },
                error: function () {
                    $('#resultado').html('<div class="alert alert-danger">Hubo un error al procesar la solicitud. Inténtalo de nuevo más tarde.</div>');
                }
            });
        });
    });
</script>
</body>
</html>