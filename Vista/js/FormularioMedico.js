$(document).ready(function () {
    tabla = $('#tblDatos').DataTable({});
});

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
function limpiar() {

    $('#fIdMedico').val("0");
    $('#fNombreMedico').val("");
    $('#fDocumentoMedico').val("");
    $('#fCorreomedico').val("");
    $('#fTelefonoMedico').val("");
    $('#IdEspecialidadMedico').val("");
    $('#SueldoBaseMedico').val("");
    $('#IdAreaMedico').val("");

}
function ingresar() {
    limpiar()
    $('#exampleModalLabel').html("Agregar Médico");
    $("#Accion").val("Ingresar");
    $("#ModalIngreso").modal('show');
}

function actualizar(id) {
    limpiar()
    $('#exampleModalLabel').html("Editar Médico");
    $("#Accion").val("Actualizar");
    $('#fIdMedico').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorMedico.php?Accion=ListarPorId',
        data: { IdMedico: id },
        success: function (msg) {

            $('#fNombreMedico').val(msg[0]['NombreMedico']);
            $('#fDocumentoMedico').val(msg[0]['DocumentoMedico']);
            $('#fCorreomedico').val(msg[0]['Correomedico']);
            $('#fTelefonoMedico').val(msg[0]['TelefonoMedico']);
            $('#fIdEspecialidadMedico').val(msg[0]['IdEspecialidadMedico']);
            $('#fSueldoBaseMedico').val(msg[0]['SueldoBaseMedico']);
            $('#fIdAreaMedico').val(msg[0]['IdAreaMedico']);
            $('#EstadoMedico').val(msg[0]['EstadoMedico']);
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
        }
    });
}

function validar() {
    if (
        $('#fNombreMedico').val() == '' ||
        $('#fDocumentoMedico').val() == '' ||
        $('#fCorreomedico').val() == '' ||
        $('#fTelefonoMedico').val() == '' ||
        $('#fIdEspecialidadMedico').val() == '' ||
        $('#fSueldoBaseMedico').val() == '' ||
        $('#fIdAreaMedico').val() == '' ||
        $('#EstadoMedico').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function Accion() {
    accion = $('#Accion').val()
    var Medico = {
    IdMedico: $.trim($('#fIdMedico').val()),
    NombreMedico: $.trim($('#fNombreMedico').val()),
    DocumentoMedico: $.trim($('#fDocumentoMedico').val()),
    Correomedico: $.trim($('#fCorreomedico').val()),
    TelefonoMedico: $.trim($('#fTelefonoMedico').val()),
    IdEspecialidadMedico: $.trim($('#fIdEspecialidadMedico').val()),
    SueldoBaseMedico: $.trim($('#fSueldoBaseMedico').val()),
    IdAreaMedico: $.trim($('#fIdAreaMedico').val()),
    EstadoMedico: $.trim($('#EstadoMedico').val())
    }
    if (validar()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorMedico.php?Accion=ListarDocumento',
                data: { IdMedico: $.trim($('#fIdMedico').val()), DocumentoMedico: $.trim($('#fDocumentoMedico').val())},
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Medico);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado al Insertar", "Medico Registrado con Anterioridad!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos3!", textStatus + ' ' + errorThrown)
                }
            });
        }
        if (accion == "Actualizar") {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorMedico.php?Accion=ListarDocumento',
                    data: { IdMedico: $.trim($('#fIdMedico').val()), DocumentoMedico: $.trim($('#fDocumentoMedico').val())},
                    success: function (msg) {
                        if (JSON.stringify(msg) == '[]') {
                            CRUD(accion, Medico);
                            $('#modalCRUD').modal('hide');
                        } else {
                            MensajeError("Dato Duplicado al Actualizar", "Medico Registrada con Anterioridad!")
                        }
                    },
                    error: function (req, textStatus, errorThrown) {
                        MensajeError("Oops...", "Error al obtener Datos999!", textStatus + ' ' + errorThrown)
                    }
                });
        }
    }
};
function eliminar($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar el Medico ' + $id + ' Registro?',
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
            var Medico = {
                IdMedico: $.trim($id),
                NombreMedico: $.trim($('#fNombreMedico').val()),
                DocumentoMedico: $.trim($('#fDocumentoMedico').val()),
                Correomedico: $.trim($('#fCorreomedico').val()),
                TelefonoMedico: $.trim($('#fTelefonoMedico').val()),
                IdEspecialidadMedico: $.trim($('#fIdEspecialidadMedico').val()),
                IdAreaMedico: $.trim($('#fIdAreaMedico').val()),
                EstadoMedico: $.trim($('#fEstadoMedico').val())
            }
            CRUD('Eliminar', Medico);
        }
    });
};
function CRUD(accion, objEvento) {
    console.log(JSON.stringify(objEvento));
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorMedico.php?Accion=' + accion,
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