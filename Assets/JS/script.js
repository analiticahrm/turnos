$(document).ready(function() {
    $("#abrirModal").click(function() {
        // Realizar una solicitud AJAX para cargar la página PHP
        $.ajax({
            url: "/Vista/perfil2.php", // Cambia "pagina.php" por la URL de tu página PHP
            success: function(data) {
                // Mostrar el contenido de la página PHP en el modal
                $("#contenidoModal").html(data);
                $("#miModal").show();
            }
        });
    });

    $("#cerrarModal").click(function() {
        // Cerrar el modal al hacer clic en la "X"
        $("#miModal").hide();
    });
});
