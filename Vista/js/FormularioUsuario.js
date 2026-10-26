$(document).ready(function () {
    tabla = $('#tblDatos').DataTable({});
});
function MensajeError(titulo, Mensaje) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje,
        customClass: {
            popup: 'swal2-dark'
          }
    });
}

function limpiar() {
    $('#fIdUsuario').val("0");
    $('#fNombreUsuario').val("");
    $('#fDocumentoUsuario').val("");
    $('#fTelefonoUsuario').val("");
    $('#fCorreoUsuario').val("");
    $('#fContraseñaUsuario').val("");
    $('#fIdRolUsuario').val("");
}
function ingresar() {
    limpiar()
    $('#exampleModalLabel').html("Agregar Usuario");
    $("#Accion").val("Ingresar");
    $("#ModalIngreso").modal('show');
}
function actualizar(id) {
    limpiar()
    $('#exampleModalLabel').html("Editar Usuario");
    $("#Accion").val("Actualizar");
    $('#fIdUsuario').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/Controladorusuario.php?Accion=ListarPorId',
        data: { IdUsuario: id },
        success: function (msg) {
            $('#fNombreUsuario').val(msg[0]['NombreUsuario']);
            $('#fDocumentoUsuario').val(msg[0]['DocumentoUsuario']);
            $('#fTelefonoUsuario').val(msg[0]['TelefonoUsuario']);
            $('#fCorreoUsuario').val(msg[0]['CorreoUsuario']);
            $('#fIdRolUsuario').val(msg[0]['IdRolUsuario']);
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datoss!", textStatus + ' ' + errorThrown)
        }
    });
}

function validar() {
    if (
        $('#fNombreUsuario').val() == '' ||
        $('#fDocumentoUsuario').val() == '' ||
        $('#fTelefonoUsuario').val() == '' ||
        $('#fCorreoUsuario').val() == '' ||
        $('#fContraseñaUsuario').val() == '' ||
        $('#fIdRolUsuario').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function validarUdate() {
    if (
        $('#fNombreUsuario').val() == '' ||
        $('#fDocumentoUsuario').val() == '' ||
        $('#fTelefonoUsuario').val() == '' ||
        $('#fCorreoUsuario').val() == '' ||
        $('#fIdRolUsuario').val() == ''
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
        IdUsuario: $.trim($('#fIdUsuario').val()),
        NombreUsuario: $.trim($('#fNombreUsuario').val()),
        DocumentoUsuario: $.trim($('#fDocumentoUsuario').val()),
        TelefonoUsuario : $.trim($('#fTelefonoUsuario').val()),
        CorreoUsuario : $.trim($('#fCorreoUsuario').val()),
        ContraseñaUsuario : $.trim($('#fContraseñaUsuario').val()),
        IdRolUsuario: $.trim($('#fIdRolUsuario').val())
    }
    

    if (accion == "Ingresar") {
        if (validar()) {
            $.ajax({
                type: 'POST',
                url: '../Controlador/Controladorusuario.php?Accion=ListarPorNombre',
                data: { NombreUsuario: $.trim($('#fNombreUsuario').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Usuario);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado al Insertar", "Usuario Registrada con Anterioridad!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                }
            });
        }
        
    }
    

    if (accion == "Actualizar") {
        if (validarUdate()) {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/Controladorusuario.php?Accion=ListarPorNombreId',
                    data: {
                        NombreUsuario: $.trim($('#fNombreUsuario').val()),
                        IdUsuario: $.trim($('#fIdUsuario').val())
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
function eliminar($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar el Usuario ' + $id + ' Registro?',
        text: "No podrá recuperar los datos!",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
          }
    }).then((choice) => {
        if (choice.value === true) {
            var Usuario = {
                IdUsuario: $.trim($id),
                NombreUsuario: $.trim($('#fNombreUsuario').val()),
                DocumentoUsuario: $.trim($('#fDocumentoUsuario').val()),
                TelefonoUsuario : $.trim($('#fTelefonoUsuario').val()),
                CorreoUsuario : $.trim($('#fCorreoUsuario').val()),
                ContraseñaUsuario : $.trim($('#fContraseñaUsuario').val()),
                IdRolUsuario: $.trim($('#fIdRolUsuario').val())
            }
            CRUD('Eliminar', Usuario);
        }
    });
};
function CRUD(accion, objEvento) {
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

const fContraseñaUsuario = document.getElementById('fContraseñaUsuario');
const togglePassword = document.getElementById('togglePassword');
const togglePasswordClosed = document.getElementById('togglePasswordClosed');

function toggleVisibility() {
    if (fContraseñaUsuario.type === 'password') {
        fContraseñaUsuario.type = 'text';
        togglePassword.classList.remove('open');
        togglePassword.classList.add('closed');
        togglePasswordClosed.classList.remove('closed');
        togglePasswordClosed.classList.add('open');
    } else {
        fContraseñaUsuario.type = 'password';
        togglePassword.classList.remove('closed');
        togglePassword.classList.add('open');
        togglePasswordClosed.classList.remove('open');
        togglePasswordClosed.classList.add('closed');
    }
}

togglePassword.addEventListener('click', toggleVisibility);
togglePasswordClosed.addEventListener('click', toggleVisibility);
