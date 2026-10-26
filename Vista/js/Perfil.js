
function Formulario(id, nombreArea) {
    // Redirige a la página del formulario
    window.location.href = 'FormularioTurnos.php?id=' + id + '&nombreArea=' + encodeURIComponent(nombreArea);
    // Espera un momento para que la redirección ocurra
    setTimeout(function() {
        // Llama a la función `actualizar` después de la redirección
        // Asegúrate de que el formulario haya cargado completamente
        // Este paso es opcional, dependiendo de cómo cargues los datos
        actualizar(id);
    }, 1000); // Ajusta el tiempo de espera si es necesario
}


function MensajeError(titulo, Mensaje, foot) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje,
        footer: foot,
        customClass: {
            popup: 'swal2-dark'
          }
    });
}

function perfil(id) {
    $('#exampleModalLabel').html("Editar Usuario");
    $("#Accion").val("Actualizar");
    $('#editUsuario').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/Controladorusuario.php?Accion=ListarPorId',
        data: { IdUsuario: id },
        success: function (msg) {
            // Llenar los <p> en el perfil del usuario
            $('#nombre').text(msg[0]['NombreUsuario']);
            $('#documento').text(msg[0]['DocumentoUsuario']);
            $('#telefono').text(msg[0]['TelefonoUsuario']);
            $('#correo').text(msg[0]['CorreoUsuario']);


        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
        }
    });
}


function VerDatos(id) {
    $('#exampleModalLabel').html("Editar Usuario");
    $("#Accion").val("Actualizar");
    $('#editUsuario').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/Controladorusuario.php?Accion=ListarPorId',
        data: { IdUsuario: id },
        success: function (msg) {
            $('#editNombre').val(msg[0]['NombreUsuario']);
            $('#editDocumentoUsuario').val(msg[0]['DocumentoUsuario']);
            $('#editTelefono').val(msg[0]['TelefonoUsuario']);
            $('#editCorreo').val(msg[0]['CorreoUsuario']);
            $('#editIdRolUsuario').val(msg[0]['IdRolUsuario']);
            $("#modal").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datoss!", textStatus + ' ' + errorThrown)
        }
    });
}


function validar() {
    if (
        $('#editNombre').val() == '' ||
        $('#editTelefono').val() == '' ||
        $('#editCorreo').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}
function Accion() {
    accion = $('#Accion').val()
    var Usuario = {
        IdUsuario: $.trim($('#editUsuario').val()),
        NombreUsuario: $.trim($('#editNombre').val()),
        DocumentoUsuario: $.trim($('#editDocumentoUsuario').val()),
        TelefonoUsuario : $.trim($('#editTelefono').val()),
        CorreoUsuario : $.trim($('#editCorreo').val()),
        ContraseñaUsuario : $.trim($('#editContrasena').val()),
        IdRolUsuario : $.trim($('#editIdRolUsuario').val()),
    }
    if (validar()) {
        if (accion == "Actualizar") {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/Controladorusuario.php?Accion=ListarPorNombreId',
                    data: {
                        NombreUsuario: $.trim($('#editNombre').val()),
                        IdUsuario: $.trim($('#editUsuario').val())
                    },
                    success: function (msg) {
                        if (JSON.stringify(msg) == '[]') {
                            CRUD(accion, Usuario);
                            $('#modalCRUD').modal('hide');
                        } else {
                            MensajeError("Dato Duplicado al Actualizar", "Usuario Registrada con Anterioridad!")
                        }
                    },
                    error: function (req, textStatus, errorThrown) {
                        MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                    }
                });
        }
    }
};


function CRUD(accion, objEvento) {
    console.log(JSON.stringify(objEvento));
    $.ajax({
        type: 'POST',
        url: '../Controlador/Controladorusuario.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Exito!",
                    text: "Transacción realizada con exito!",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                      }
                });
                setTimeout(function () {
                    location.reload();
                }, 1000);

            }
        },
        error: function (hxr) {
            MensajeError(hxr.responseText, "Error en la Transacción!")
        }
    });
};
