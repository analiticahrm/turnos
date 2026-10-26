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
function limpiar() {
    $('#fIdEspecialidad').val("0");
    $('#fNombreEspecialidad').val("");
    $('#fEstadoEspecialidad').val("3");
    $('#fLiquidarEspecialidad').prop('checked', false);
}
function ingresar() {
    limpiar()
    $('#exampleModalLabel').html("Agregar Especialidad");
    $("#Accion").val("Ingresar");
    $("#ModalIngreso").modal('show');
}

function actualizar(id) {
    limpiar()
    $('#exampleModalLabel').html("Editar Especialidad");
    $("#Accion").val("Actualizar");
    $('#fIdEspecialidad').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorEspecialidad.php?Accion=ListarPorId',
        data: { IdEspecialidad: id },
        success: function (msg) {
            $('#fNombreEspecialidad').val(msg[0]['NombreEspecialidad']);
            $('#fEstadoEspecialidad').val(msg[0]['EstadoEspecialidad']);
            // Agregar esta línea para el checkbox
            $('#fLiquidarEspecialidad').prop('checked', msg[0]['LiquidarEspecialidad'] == '1');
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
        }
    });
}

function validar() {
    if (
        $('#fNombreEspecialidad').val() == '' ||
        $('#fEstadoEspecialidad').val() == '3'
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function Accion() {
    accion = $('#Accion').val()
    var Especialidad = {
        IdEspecialidad: $.trim($('#fIdEspecialidad').val()),
        NombreEspecialidad: $.trim($('#fNombreEspecialidad').val()),
        EstadoEspecialidad: $.trim($('#fEstadoEspecialidad').val()),
        LiquidarEspecialidad: $('#fLiquidarEspecialidad').is(':checked') ? '1' : '0'
    }
    if (validar()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorEspecialidad.php?Accion=ListarPorNombre',
                data: { NombreEspecialidad: $.trim($('#fNombreEspecialidad').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Especialidad);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado al Insertar", "Especialidad Registrada con Anterioridad!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                }
            });
        }
        if (accion == "Actualizar") {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorEspecialidad.php?Accion=ListarPorNombreId',
                    data: {
                        NombreEspecialidad: $.trim($('#fNombreEspecialidad').val()),
                        IdEspecialidad: $.trim($('#fIdEspecialidad').val())
                    },
                    success: function (msg) {
                        if (JSON.stringify(msg) == '[]') {
                            CRUD(accion, Especialidad);
                            $('#modalCRUD').modal('hide');
                        } else {
                            MensajeError("Dato Duplicado al Actualizar", "Especialidad Registrada con Anterioridad!")
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
        title: '¿Está seguro que desea eliminar el Especialidad ' + $id + ' Registro?',
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
                IdEspecialidad: $.trim($id),
                NombreEspecialidad: $.trim($('#fNombreEspecialidad').val()),
                EstadoEspecialidad: $.trim($('#fEstadoEspecialidad').val()),
                LiquidarEspecialidad: $('#fLiquidarEspecialidad').is(':checked') ? '1' : '0' // Agregar esta línea
            }
            CRUD('Eliminar', Usuario);
        }
    });
};
function CRUD(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorEspecialidad.php?Accion=' + accion,
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
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
};