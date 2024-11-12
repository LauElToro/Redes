<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Transacción Polygon</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Verificar Transacción en Polygon</h2>
        <form id="verificarTransaccionForm" method="POST">
            <div class="form-group">
                <label for="txHash">Hash de la Transacción:</label>
                <input type="text" class="form-control" id="txHash" name="txHash" required>
            </div>
            <button type="submit" class="btn btn-primary">Verificar</button>
        </form>

        <div id="resultado" class="mt-4"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#verificarTransaccionForm').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: 'verificar_transaccion_polygon.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#resultado').html(response);
                    },
                    error: function() {
                        $('#resultado').html('<div class="alert alert-danger">Hubo un error al procesar la solicitud. Inténtalo de nuevo.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>
