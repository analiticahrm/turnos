$(document).ready(function () {
    tablaUsuarios = $('#datatablesSimple').DataTable({});
}); 
function verContraseña() {
    var tipoActual = $('#fContraseña').attr('type');
    // Cambiar el tipo de input
    if (tipoActual === 'password') {
        $('#ojoContraseña').html('visibility_off');
        $('#fContraseña').attr('type', 'text');
    } else {
        $('#ojoContraseña').html('visibility');
        $('#fContraseña').attr('type', 'password');
    }
}
function verConfContraseña() {
    var tipoActual = $('#fConfContraseña').attr('type');
    // Cambiar el tipo de input
    if (tipoActual === 'password') {
        $('#ojoConfConfContraseña').html('visibility_off');
        $('#fConfContraseña').attr('type', 'text');
    } else {
        $('#ojoConfConfContraseña').html('visibility');
        $('#fConfContraseña').attr('type', 'password');
    }
}
function MensajeError(titulo, Mensaje) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje
    });
}
function MensajeError(titulo, Mensaje, foot) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje,
        footer: foot
    });
}
function limpiar() {
    $('#fIdUsuario').val("0");
    $('#fPrimerNombre').val("");
    $('#fSegundoNombre').val("");
    $('#fPrimerApellido').val("");
    $('#fSegundoApellido').val("");
    $('#fFechaCreacion').html("<strong>N/A</strong>")
    $('#fFechaModifiacion').html("<strong>N/A</strong>")
    $('#fCorreo').val("");
    $('#fContraseña').val("");
    $('#fConfContraseña').val("");
    $('#fEstado').val("1");
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
        url: '../Controlador/ControladorUsuarioAjax.php?accion=ListarPorId',
        data: { IdUsuario: id },
        success: function (msg) {
            $('#fPrimerNombre').val(msg[0]['PrimerNombre']);
            $('#fSegundoNombre').val(msg[0]['SegundoNombre']);
            $('#fPrimerApellido').val(msg[0]['PrimerApellido']);
            $('#fSegundoApellido').val(msg[0]['SegundoApellido']);
            $('#fFechaCreacion').html("<strong>" + msg[0]['FechaCreacion'] + "</strong>")
            $('#fFechaModifiacion').html("<strong>" + msg[0]['FechaModificacion'] + "</strong>")
            $('#fCorreo').val(msg[0]['Correo']);
            $('#fEstado').val(msg[0]['Estado']);
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
        }
    });
}
function validarInsertar() {
    if (
        $('#fCorreo').val() == '' ||
        $('#fIdUsuario').val() == '' ||
        $('#fPrimerNombre').val() == '' ||
        $('#fPrimerApellido').val() == '' ||
        $('#fCorreo').val() == '' ||
        $('#fContraseña').val() == '' ||
        $('#fConfContraseña').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        if ($('#fContraseña').val() != $('#fConfContraseña').val()) {
            MensajeError("¡Contraseña Errada!", "No coinciden los campos contraseña y Confirmar Contraseña");
            return false
        } else {
            const expresionRegular = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!expresionRegular.test($('#fCorreo').val())) {
                MensajeError("Dirección de Correo", "Escriba Una direccion de correo válida");
            } else {
                return true
            }
        }
    }
}
function validarActualizar(){
    if (
        $('#fCorreo').val() == '' ||
        $('#fIdUsuario').val() == '' ||
        $('#fPrimerNombre').val() == '' ||
        $('#fPrimerApellido').val() == '' ||
        $('#fCorreo').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        if ($('#fContraseña').val() != $('#fConfContraseña').val()) {
            MensajeError("¡Contraseña Errada!", "No coinciden los campos contraseña y Confirmar Contraseña");
            return false
        } else {
            const expresionRegular = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!expresionRegular.test($('#fCorreo').val())) {
                MensajeError("Dirección de Correo", "Escriba Una direccion de correo válida");
            } else {
                return true
            }
        }
    }
}

function Accion() {
    accion = $('#Accion').val()
    var Usuario = {
        IdUsuario: $.trim($('#fIdUsuario').val()),
        PrimerNombre: $.trim($('#fPrimerNombre').val()),
        SegundoNombre: $.trim($('#fSegundoNombre').val()),
        PrimerApellido: $.trim($('#fPrimerApellido').val()),
        SegundoApellido: $.trim($('#fSegundoApellido').val()),
        Correo: $.trim($('#fCorreo').val()),
        Contraseña: $.trim($('#fContraseña').val()),
        IdRol: $.trim($('#fIdRol').val()),
        Estado: $.trim($('#fEstado').val())
    }

    if (accion == "Ingresar") {
        if (validarInsertar()) {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorUsuarioAjax.php?accion=ListarPorCorreo',
                data: { Correo: $.trim($('#fCorreo').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Usuario);
                        $('#modalCRUD').modal('hide');
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
        if (validarActualizar()) {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorUsuarioAjax.php?accion=ListarPorCorreoId',
                data: {
                    Correo: $.trim($('#fCorreo').val()),
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
        title: '¿Está seguro que desea eliminar el registro ' + $id + ' Registro?',
        text: "No podrá recuperar los datos!",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
    }).then((choice) => {
        if (choice.value === true) {
            var Usuario = {
                IdUsuario: $id,
                PrimerNombre: '',
                SegundoNombre: '',
                PrimerApellido: '',
                SegundoApellido: '',
                Correo: '',
                Contraseña: '',
                IdRol:'',
                Estado: '',
            }
            CRUD('Eliminar', Usuario);
        }
    });
};
function CRUD(accion, objEvento) {
   // alert(JSON.stringify(objEvento));
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorUsuarioAjax.php?accion=' + accion,
        data: objEvento,
        success: function (msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Exito!",
                    text: "Transacción realizada con exito!",
                    icon: "success"
                });
                setTimeout(function () {
                    location.reload();
                }, 1000);

            }
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
};