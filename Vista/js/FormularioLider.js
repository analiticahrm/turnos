$(document).ready(function () {
    tabla = $('#tblDatos').DataTable({});
});



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
    $('#fIdLider').val("0");
    $('#fNombreLider').val("");
    $('#fTelefonoLider').val("");
    $('#fCorreoLider').val("");
    $('#fIdAreaLider').val("");
    $('#fFirmaLider').val("");
    $('#fEstadoLider').val("3");
}
function ingresar() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    limpiar()
    $('#exampleModalLabel').html("Agregar Lider");
    $("#Accion").val("Ingresar");
    $("#ModalIngreso").modal('show');
}

function loadSignature() {
    const url = document.getElementById('fFirmaLider').value;
    if (url) {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = function() {
            clearCanvas();
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.onerror = function() {
            Swal.fire({
                title: "¡Error!",
                text: "No se pudo cargar la imagen desde la URL proporcionada",
                icon: "error"
            });
        };
        img.src = url;
    } else {
        Swal.fire({
            title: "¡Error!",
            text: "No se proporcionó ninguna URL para cargar la firma",
            icon: "error"
        });
    }
    
}

function actualizar(id) {

    limpiar()
    $('#exampleModalLabel').html("Editar Usuario");
    $("#Accion").val("Actualizar");
    $('#fIdLider').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorLider.php?Accion=ListarPorId',
        data: { IdLider: id },
        success: function (msg) {
            
            $('#fNombreLider').val(msg[0]['NombreLider']);
            $('#fTelefonoLider').val(msg[0]['TelefonoLider']);
            $('#fCorreoLider').val(msg[0]['CorreoLider']);
            $('#fIdAreaLider').val(msg[0]['IdAreaLider']);
            $('#fFirmaLider').val(msg[0]['FirmaLider']);
            $('#fEstadoLider').val(msg[0]['EstadoLider']);
            $("#ModalIngreso").modal('show');
            loadSignature()
        },

        error: function (hxr) {
            MensajeError(hxr.ResponseText, "Error en la Transacción!")
        }

    });
    
    
}


function validar() {
    if (
        $('#fNombreLider').val() == '' ||
        $('#fTelefonoLider').val() == '' ||
        $('#fCorreoLider').val() == '' ||
        $('#fIdAreaLider').val() == '' ||
        $('#fFirmaLider').val() == '' ||
        $('#fEstadoLider').val() == '3'
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}
function Accion() {
    accion = $('#Accion').val()
    var Lider = {
        IdLider: $.trim($('#fIdLider').val()),
        NombreLider: $.trim($('#fNombreLider').val()),
        TelefonoLider : $.trim($('#fTelefonoLider').val()),
        CorreoLider : $.trim($('#fCorreoLider').val()),
        IdAreaLider : $.trim($('#fIdAreaLider').val()),
        FirmaLider : $.trim($('#fFirmaLider').val()),
        EstadoLider: $.trim($('#fEstadoLider').val())
    }
    if (validar()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorLider.php?Accion=ListarPorNombre',
                data: { NombreLider: $.trim($('#fNombreLider').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Lider);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado al Insertar", "Lider Registrada con Anterioridad!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops2...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                    console.log(textStatus + ' ' + errorThrown);
                }
            });
        }
        if (accion == "Actualizar") {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorLider.php?Accion=ListarPorNombreId',
                    data: {
                        NombreLider: $.trim($('#fNombreLider').val()),
                        IdLider: $.trim($('#fIdLider').val())
                    },
                    success: function (msg) {
                        if (JSON.stringify(msg) == '[]') {
                            CRUD(accion, Lider);
                            $('#modalCRUD').modal('hide');
                        } else {
                            MensajeError("Dato Duplicado al Actualizar", "Lider Registrada con Anterioridad!")
                        }
                    },
                    error: function (req, textStatus, errorThrown) {
                        MensajeError("Oops3...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                    }
                });
        }
    }
};
function eliminar($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar el Lider ' + $id + ' Registro?',
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
                IdLider: $.trim($id),
                NombreLider: $.trim($('#fNombreLider').val()),
                TelefonoLider : $.trim($('#fTelefonoLider').val()),
                CorreoLider : $.trim($('#fCorreoLider').val()),
                IdAreaLider : $.trim($('#fIdAreaLider').val()),
                FirmaLider : $.trim($('#fFirmaLider').val()),
                EstadoLider: $.trim($('#fEstadoLider').val())
            }
            CRUD('Eliminar', Usuario);
        }
    });
};
function CRUD(accion, objEvento) {
    console.log(JSON.stringify(objEvento));
    alert(JSON.stringify(objEvento));
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorLider.php?Accion=' + accion,
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
        error: function (hxr) {
            MensajeError(hxr.ResponseText, "Error en la Transacción!")
        }
    });
};

