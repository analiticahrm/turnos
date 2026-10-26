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
    $('#fIdArea').val("0");
    $('#fNombreArea').val("");
    $('#fEstadoArea').val("3");
}
function ingresar() {
    limpiar()
    $('#exampleModalLabel').html("Agregar Area");
    $("#Accion").val("Ingresar");
    $("#ModalIngreso").modal('show');
}

function AsignarLider() {
    $('#exampleModalAsignar').html("Aisgnar Lider");
    $("#ModalAsignar").modal('show');
}



function actualizar(id) {
    limpiar()
    $('#exampleModalLabel').html("Editar Area");
    $("#Accion").val("Actualizar");
    $('#fIdArea').val(id);
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=ListarPorId',
        data: { IdArea: id },
        success: function (msg) {
            $('#fNombreArea').val(msg[0]['NombreArea']);
            $('#fEstadoArea').val(msg[0]['EstadoArea']);
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error al obtener Datos444!", textStatus + ' ' + errorThrown)
        }
    });
}

$(document).ready(function() {
    // Inicializa Select2 en los elementos <select>
    $('#fIdAreas').select2({
        dropdownParent: $('#ModalAsignar'),
        placeholder: "Seleccione una area", // Texto de placeholder
        allowClear: true, // Permite limpiar la selección
        width: '100%' // Asegura que ocupe todo el ancho disponible
    });

    $('#fIdUsuario').select2({
        dropdownParent: $('#ModalAsignar'),
        placeholder: "Seleccione un usuario",
        allowClear: true,
        width: '100%' // Asegura que ocupe todo el ancho disponible
    });

    // Agregar evento change al select de área de trabajo usando Select2
    $('#fIdAreas').on('change', function() {
        // Obtener el valor seleccionado
        var idArea = $(this).val();

        // Si el valor está vacío, limpiar la lista de líderes
        if (!idArea) {
            $("#lideresinfo").html(""); // Limpiar el contenido
            console.log("No se ha seleccionado ninguna área. Lista de líderes limpiada.");
        } else {
            // Llamar a la función lideres con el id del área seleccionada
            lideres(idArea);
        }
    });
});

function lideres(id) {
    // Si el id está vacío, no hacer nada
    if (!id) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=ListarPorUsuario',
        data: { IdArea: id },
        success: function(response) {
            console.log("Respuesta recibida:", response);

            // Crear la tabla y su cabecera
            let contenido = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nombre del Líder</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // Añadir filas con los datos de los líderes
            response.forEach(function(turno) {
                contenido += `
                    <tr>
                        <td>${turno.NombreUsuario}</td>
                        <td>
                            <button class="btn btn-danger btn-lider" data-id="${turno.IdAreaLider}">Eliminar</button>
                        </td>
                    </tr>
                `;
            });

            // Cerrar la tabla
            contenido += `
                    </tbody>
                </table>
            `;

            // Insertar la tabla en el contenedor
            $("#lideresinfo").html(contenido);

            // Asignar evento de clic a los botones
            $(".btn-lider").on("click", function() {
                const IdAreaLider = $(this).data("id");
                EliminarLider(IdAreaLider);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error en la solicitud AJAX:", error);
        }
    });
}

function validarLider() {
    if (
        $('#fIdUsuario').val() == '' ||
        $('#fIdAreas').val() == ''
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function validar() {
    if (
        $('#fNombreArea').val() == '' ||
        $('#fEstadoArea').val() == '3'
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function Accion() {
    accion = $('#Accion').val()
    var Area = {
        IdArea: $.trim($('#fIdArea').val()),
        NombreArea: $.trim($('#fNombreArea').val()),
        EstadoArea: $.trim($('#fEstadoArea').val())
    }
    if (validar()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorArea.php?Accion=ListarPorNombre',
                data: { NombreArea: $.trim($('#fNombreArea').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Area);
                        $('#ModalAsignar').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado al Insertar", "Area Registrada con Anterioridad!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos777!", textStatus + ' ' + errorThrown)
                }
            });
        }
        if (accion == "Actualizar") {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorArea.php?Accion=ListarPorNombreId',
                    data: {
                        NombreArea: $.trim($('#fNombreArea').val()),
                        IdArea: $.trim($('#fIdArea').val())
                    },
                    success: function (msg) {
                        if (JSON.stringify(msg) == '[]') {
                            CRUD(accion, Area);
                            $('#modalCRUD').modal('hide');
                        } else {
                            MensajeError("Dato Duplicado al Actualizar", "Area Registrada con Anterioridad!")
                        }
                    },
                    error: function (req, textStatus, errorThrown) {
                        MensajeError("Oops...", "Error al obtener Datos888!", textStatus + ' ' + errorThrown)
                    }
                });
        }
    }
};

function AccionAsig() {
    var Lideres = {
        IdArea: $.trim($('#fIdAreas').val()),
        IdUsuario : $.trim($('#fIdUsuario ').val()),
    }
    if (validarLider()) {
        $.ajax({
            type: 'POST',
            url: '../Controlador/ControladorArea.php?Accion=ListarPorUnUsuario',
            data: { IdArea: $.trim($('#fIdAreas').val()), IdUsuario : $.trim($('#fIdUsuario').val())},
            success: function (msg) {
                if (JSON.stringify(msg) == '[]') {
                    CRUDLideres(Lideres);
                    $('#ModalIngreso').modal('hide');
                } else {
                    MensajeError("Dato Duplicado al Insertar", "Líder ya asignado con Anterioridad!")
                }
            },
            error: function (req, textStatus, errorThrown) {
                MensajeError("Oops...", "Error al obtener Datos333!", textStatus + ' ' + errorThrown)
            }
        });
    }
};

function eliminar($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar el Area ' + $id + ' Registro?',
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
                IdArea: $.trim($id),
                NombreArea: $.trim($('#fNombreArea').val()),
                EstadoArea: $.trim($('#fEstadoArea').val())
            }
            CRUD('Eliminar', Usuario);
        }
    });
};

function EliminarLider($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar el Area ' + $id + ' Registro?',
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
                IdAreaLider: $.trim($id),
                IdArea: $.trim($('#fIdArea').val()),
                IdUsuario : $.trim($('#fIdUsuario ').val()),
            }
            CRUD('EliminarLider', Usuario);
        }
    });
};

function CRUDLideres(objEvento) {

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=AsignarLider',
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

function CRUD(accion, objEvento) {

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=' + accion,
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

///Asignar especialidades


// Función para abrir el modal de especialidades
function AsignarEspecialidad() {
    $('#exampleModalEspecialidades').html("Asignar Especialidades");
    $("#ModalEspecialidades").modal('show');
    
    // Inicializar Select2 para el select múltiple
    $('#fEspecialidades').select2({
        dropdownParent: $('#ModalEspecialidades'),
        placeholder: "Seleccione especialidades",
        allowClear: true,
        width: '100%'
    });
    
    $('#fAreaEspecialidad').select2({
        dropdownParent: $('#ModalEspecialidades'),
        placeholder: "Seleccione un área",
        allowClear: true,
        width: '100%'
    });
    
    // Evento cuando cambia el área seleccionada
    $('#fAreaEspecialidad').on('change', function() {
        var idArea = $(this).val();
        if(idArea) {
            cargarEspecialidadesAsignadas(idArea);
        } else {
            $("#especialidadesAsignadas").html("");
        }
    });
}

// Función para cargar las especialidades ya asignadas a un área
function cargarEspecialidadesAsignadas(idArea) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=ListarEspecialidadesArea',
        data: { IdArea: idArea },
        success: function(response) {
            let contenido = '<h5>Especialidades asignadas:</h5>';
            
            if(response.length > 0) {
                contenido += '<ul class="list-group">';
                response.forEach(function(especialidad) {
                    contenido += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ${especialidad.NombreEspecialidad}
                            <button class="btn btn-danger btn-sm" onclick="quitarEspecialidad(${especialidad.IdAreaEsp}, ${idArea})">
                                <span class="material-icons">delete</span>
                            </button>
                        </li>
                    `;
                });
                contenido += '</ul>';
            } else {
                contenido += '<p>No hay especialidades asignadas a esta área.</p>';
            }
            
            $("#especialidadesAsignadas").html(contenido);
            
            // También cargamos las especialidades ya asignadas en el select2
            var especialidadesAsignadas = response.map(e => e.IdEspecialidad.toString());
            $('#fEspecialidades').val(especialidadesAsignadas).trigger('change');
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar especialidades:", error);
            $("#especialidadesAsignadas").html("<p>Error al cargar especialidades.</p>");
        }
    });
}

// Función para asignar especialidades
function AsignarEspecialidades() {
    var idArea = $('#fAreaEspecialidad').val();
    var especialidades = $('#fEspecialidades').val();
    
    if(!idArea) {
        MensajeError("Error", "Debe seleccionar un área");
        return;
    }
    
    if(!especialidades || especialidades.length === 0) {
        MensajeError("Error", "Debe seleccionar al menos una especialidad");
        return;
    }
    
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorArea.php?Accion=AsignarEspecialidades',
        data: { 
            IdArea: idArea,
            Especialidades: especialidades 
        },
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Especialidades asignadas correctamente",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
                // Actualizamos la lista de especialidades asignadas
                cargarEspecialidadesAsignadas(idArea);
            } else {
                MensajeError("Error", response.message || "Error al asignar especialidades");
            }
        },
        error: function(xhr, status, error) {
            MensajeError("Error", "Error en la conexión: " + error);
        }
    });
}

// Función para quitar una especialidad
function quitarEspecialidad(IdAreaEsp, idArea) {

    Swal.fire({
        title: '¿Está seguro?',
        text: "Esta acción quitará la especialidad del área",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, quitarla!',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorArea.php?Accion=QuitarEspecialidad',
                data: { IdAreaEsp: IdAreaEsp },
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: "¡Éxito!",
                            text: "Especialidad quitada correctamente",
                            icon: "success",
                            customClass: {
                                popup: 'swal2-dark'
                            }
                        });
                        // Actualizamos la lista
                        cargarEspecialidadesAsignadas(idArea);
                    } else {
                        MensajeError("Error", response.message || "Error al quitar especialidad");
                    }
                },
                error: function(xhr, status, error) {
                    MensajeError("Error", "Error en la conexión: " + error);
                }
            });
        }
    });
}