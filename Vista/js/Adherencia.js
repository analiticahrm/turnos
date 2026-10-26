$(document).ready(function () {
    // Enviar el formulario
    $('#uploadForm').on('submit', function (e) {
        e.preventDefault(); // Evitar recarga de página
        var formData = new FormData(this);
        var accion = $('#accion').val(); // Obtener la acción del campo oculto
        var submitButton = $('button[type="submit"]'); // Referencia al botón
        // Deshabilitar el botón antes de enviar
        submitButton.prop('disabled', true);
        submitButton.text('Procesando...'); // Cambiar texto mientras se procesa

        $.ajax({
            url: `../Modelo/ValidarExcelAdeherencia.php?Accion=${accion}`,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                try {
                    if (typeof response !== 'object') {
                        response = JSON.parse(response); // Asegurarse de que sea un objeto
                    }
                    if (response.exists) {
                        Swal.fire({
                            icon: 'warning',
                            title: '¡Atención!',
                            text: 'Ya hay turnos cargados para este mes. ¿Deseas sobrescribirlos?',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, sobrescribir',
                            cancelButtonText: 'No, cancelar',
                            customClass: {
                                popup: 'swal2-dark'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                SobrescribirFormulario(accion);
                            } else {
                                submitButton.prop('disabled', false);
                                submitButton.text('Cargar Turnos');
                                $('#uploadModal').modal('hide');
                            }
                        });
                    } else {
                        enviarFormulario(accion);
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'Respuesta inesperada del servidor',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                    submitButton.prop('disabled', false);
                    submitButton.text('Cargar Turnos');
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: '¡Error!',
                    text: 'Hubo un error al verificar los turnos',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
                // Restaurar el estado del botón si ocurre un error
                submitButton.prop('disabled', false);
                submitButton.text('Cargar Turnos');
            }
        });

        // Función para enviar el formulario si el usuario acepta sobrescribir los turnos
        function enviarFormulario(accion) {
            $.ajax({
                url: `../Modelo/LeerExcelAdeherencia.php?Accion=${accion}`, // Usar la acción dinámica
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Los turnos fueron cargados correctamente',
                        customClass: {
                            popup: 'swal2-dark'
                        },
                        showConfirmButton: true
                    }).then(() => {
                        location.reload(); // Recargar la página después de mostrar el mensaje
                    });
                    // Restaurar el estado del botón y cerrar el modal
                    submitButton.prop('disabled', false); // Habilitar el botón nuevamente
                    submitButton.text('Cargar Turnos'); // Restaurar el texto original del botón
                    $('#uploadModal').modal('hide'); // Cerrar modal
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'Hubo un error al cargar los turnos',
                        customClass: {
                            popup: 'swal2-dark'
                        },
                        showConfirmButton: true
                    });
                    // Restaurar el estado del botón si ocurre un error
                    submitButton.prop('disabled', false);
                    submitButton.text('Cargar Turnos');
                }
            });
        }

        function SobrescribirFormulario(accion) {
            $.ajax({
                url: `../Modelo/SobrescribirExcelAdeherencia.php?Accion=${accion}`, // Usar la acción dinámica
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Los turnos fueron cargados correctamente',
                        customClass: {
                            popup: 'swal2-dark'
                        },
                        showConfirmButton: true
                    }).then(() => {
                        location.reload(); // Recargar la página después de mostrar el mensaje
                    });
                    // Restaurar el estado del botón y cerrar el modal
                    submitButton.prop('disabled', false); // Habilitar el botón nuevamente
                    submitButton.text('Cargar Turnos'); // Restaurar el texto original del botón
                    $('#uploadModal').modal('hide'); // Cerrar modal
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: '¡Error!',
                        text: 'Hubo un error al cargar los turnos',
                        customClass: {
                            popup: 'swal2-dark'
                        },
                        showConfirmButton: true
                    });
                    // Restaurar el estado del botón si ocurre un error
                    submitButton.prop('disabled', false);
                    submitButton.text('Cargar Turnos');
                }
            });
        }
    });
});