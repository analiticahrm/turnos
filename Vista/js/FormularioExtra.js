function ingresarExtra() {
    limpiarExtra();
    $('#nombre').text(NombreUsuario);
    $('#modalExtrasLabel').html("Ver Horas Extras");
    $("#ModalIngresoExtra").modal('show');
}

function CrearExtra() {
    limpiarExtra();
    $("#AccionExtra").val("Ingresar");
    $('#exampleModalLabelExtra').html("Crear Hora Extra");

    $('#fDocumentoMedico').val(DocumentoUsuario);
    $('#nombre').text(NombreUsuario);
    
    // Ocultar o deshabilitar el campo de estado para el médico
    $('#fEstadoExtra').val('0').prop('disabled', true);
    
    idmedico(DocumentoUsuario, function(idMedico) {
        $('#fIdMedicose').val(idMedico);
        $("#ModalIngresoExtra").modal('show');
    });
}

function idmedico(DocumentoUsuario, callback) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorMedico.php?Accion=ListarPorDocumento',
        data: {
            DocumentoMedico: DocumentoUsuario
        },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                callback(data[0].IdMedico);
            } else {
                callback(null);
            }
        },
        error: function() {
            callback(null);
        }
    });
}

function validarExtra() {
    if (
        $('#fIdMedicose').val() == '' ||
        $('#fFechaInicioExtra').val() == '' ||
        $('#fFechaFinExtra').val() == '' ||
        $('#fEstadoExtra').val() == ''
    ) {
        mostrarAlerta('empty_fields');
        return false;
    }
    
    const fechaInicio = new Date($('#fFechaInicioExtra').val());
    const fechaFin = new Date($('#fFechaFinExtra').val());
    
    if (isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) {
        mostrarAlerta('invalid_date_format');
        return false;
    }
    
    if (fechaFin < fechaInicio) {
        mostrarAlerta('end_date_before_start', {fechaInicio, fechaFin});
        return false;
    }
    
    return true;
}

function mostrarAlerta(tipo, datos = {}) {
    const configBase = {
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Entendido',
        customClass: {
            popup: 'swal2-dark'
        }
    };
    
    switch(tipo) {
        case 'empty_fields':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Campos Incompletos',
                text: 'Por favor diligencie todos los campos en el Formulario'
            });
            break;
            
        case 'invalid_date_format':
            Swal.fire({
                ...configBase,
                icon: 'warning',
                title: 'Formato de fecha inválido',
                text: 'Por favor ingrese fechas válidas en el formato correcto'
            });
            break;
            
        case 'end_date_before_start':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Error en fechas',
                html: 'La fecha de fin <strong>no puede ser menor</strong> que la fecha de inicio<br><br>' +
                      `<strong>Fecha inicio:</strong> ${datos.fechaInicio.toLocaleDateString()}<br>` +
                      `<strong>Fecha fin:</strong> ${datos.fechaFin.toLocaleDateString()}`
            });
            break;
            
        default:
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error inesperado'
            });
    }
}

function ingresarHorasExtras() {
    if ($.fn.DataTable.isDataTable('#tablaHorasExtras')) {
        table = $('#tablaHorasExtras').DataTable();
        table.ajax.reload();
    } else {
        table = $('#tablaHorasExtras').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "../Controlador/ControladorExtra.php?Accion=ListarPorMedico",
                "type": "POST",
                "data": function(d) {
                    d.DocumentoUsuario = DocumentoUsuario;
                },
                "dataSrc": function(json) {
                    console.log(json);
                    if (!json || !json.data) {
                        console.error("La respuesta no contiene 'data'.");
                        return [];
                    }
                    return json.data;
                },
                "error": function(xhr, status, error) {
                    console.error("Error en la solicitud:", status, error);
                    console.log("Respuesta completa:", xhr.responseText);
                }
            },
            "columns": [
                { "data": "NombreMedico" },
                { "data": "FechaInicioExtra" },
                { "data": "FechaFinExtra" },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        let inicio = new Date(row.FechaInicioExtra);
                        let fin = new Date(row.FechaFinExtra);
                        
                        if (isNaN(inicio) || isNaN(fin)) {
                            return "Fechas inválidas";
                        }

                        let diferenciaMs = fin - inicio;
                        let horas = diferenciaMs / (1000 * 60 * 60);
                        
                        return horas.toFixed(2) + " horas";
                    }
                },
                { "data": "ObsExtra" },
                // NUEVA COLUMNA: Observación de Rechazo
                { 
                    "data": "ObsRechazoExtra",
                    "render": function(data, type, row) {
                        if (row.EstadoExtra == '2' && data) {
                            return `<button class="badge bg-danger ver-rechazo" 
                                    data-observacion="${data}" 
                                    title="Ver motivo de rechazo">
                                Ver Rechazo
                            </button>`;
                        }
                        return '';
                    },
                    "orderable": false,
                    "searchable": false
                },
                { 
                    "data": "EstadoExtra",
                    "render": function(data, type, row) {
                        let imagen = "";
                        let tooltip = "";
                        let estado = String(data).toLowerCase();
                        switch(estado) {
                            case "0":
                                imagen = "pendiente.png";
                                tooltip = "Pendiente de aprobación";
                                break;
                            case "1":
                                imagen = "activo.png";
                                tooltip = "Confirmada";
                                break;
                            case "2":
                                imagen = "inactivo.png";
                                tooltip = "Rechazada" + (row.ObsRechazoExtra ? ` - Click en 'Ver Rechazo' para más detalles` : '');
                                break;
                            case "3":
                                imagen = "liquidado.png";
                                tooltip = "Liquidada";
                                break;
                            default:
                                imagen = "default.png";
                                tooltip = "Estado desconocido";
                        }
                        return `<img src="../Assets/ICON/${imagen}" alt="${data}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    }
                },
                { 
                    "data": "IdExtra",
                    "render": function(data, type, row) {
                        let botones = '';
                        
                        // Solo mostrar botones si el estado es Pendiente (0)
                        if (row.EstadoExtra == '0') {
                            botones = `
                                <button class="update_btn" data-id="${data}" title="Editar">
                                    <span class="material-icons">update</span>
                                </button>
                                <button class="delete_btn" data-id="${data}" title="Eliminar">
                                    <span class="material-icons">delete</span>
                                </button>
                            `;
                        } else {
                            botones = `
                                <button class="update_btn" data-id="${data}" title="Ver detalles" style="opacity: 0.6;">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="delete_btn" data-id="${data}" disabled title="No se puede eliminar" style="opacity: 0.3;">
                                    <span class="material-icons">delete</span>
                                </button>
                            `;
                        }
                        
                        return botones;
                    },
                    "orderable": false,
                    "searchable": false
                }
            ],
            "language": {
                "decimal": ",",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });
        
        // Manejar eventos de los botones
        $('#tablaHorasExtras').on('click', '.update_btn', function() {
            var id = $(this).data('id');
            actualizarExtra(id);
        });

        $('#tablaHorasExtras').on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            // Solo permitir eliminar si no está deshabilitado
            if (!$(this).prop('disabled')) {
                eliminarExtra(id);
            }
        });

        // NUEVO: Evento para mostrar observación de rechazo
        $('#tablaHorasExtras').on('click', '.ver-rechazo', function() {
            const observacion = $(this).data('observacion');
            Swal.fire({
                title: 'Motivo de Rechazo',
                html: `
                    <div class="alert alert-danger" style="text-align: left; margin: 0;">
                        <h6><i class="fas fa-exclamation-triangle"></i> Razón del rechazo:</h6>
                        <p style="margin: 10px 0 0 0; white-space: pre-wrap;">${observacion}</p>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido',
                customClass: {
                    popup: 'swal2-dark'
                },
                width: '600px'
            });
        });
    }
    return table;
}

function toggleFormularioExtra(bloqueado) {
    $('#fFechaInicioExtra').prop('disabled', bloqueado);
    $('#fFechaFinExtra').prop('disabled', bloqueado);
    $('#fObsExtra').prop('disabled', bloqueado);
    $('#btnGuardarExtra').prop('disabled', bloqueado);
    if (bloqueado) {
        $('#alertaConfirmado').show();
    } else {
        $('#alertaConfirmado').hide();
    }
}

function actualizarExtra(id) {
    limpiarExtra();
    $('#exampleModalLabelExtra').html("Editar Hora Extra");
    $("#AccionExtra").val("Actualizar");
    $('#fIdExtra').val(id);

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorExtra.php?Accion=ListarPorId',
        data: { IdExtra: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontraron datos para editar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }

            const datos = response[0];
            $('#fIdExtra').val(datos.IdExtra);
            $('#fFechaInicioExtra').val(datos.FechaInicioExtra);
            $('#fFechaFinExtra').val(datos.FechaFinExtra);
            $('#fObsExtra').val(datos.ObsExtra);

            // Forzar estado pendiente para médico
            $('#fEstadoExtra').val('0');
            $('#nombre').text(NombreUsuario);

            // Bloquear edición si estado es 1 (Confirmado), 2 (Rechazado) o 3 (Liquidado)
            if (datos.EstadoExtra === 1 || datos.EstadoExtra === 2 || datos.EstadoExtra === 3) {
                toggleFormularioExtra(true);

                // Mensaje según estado
                let titulo = "";
                let mensaje = "";
                let icono = 'warning';

                switch (datos.EstadoExtra) {
                    case 1:
                        titulo = "Horas Extras Confirmadas";
                        mensaje = "Las horas extras ya están confirmadas y no pueden ser editadas.";
                        break;
                    case 2:
                        titulo = "Horas Extras Rechazadas";
                        mensaje = "Las horas extras han sido rechazadas y no pueden ser editadas.";
                        icono = 'error';
                        
                        // Mostrar observación de rechazo si existe
                        if (datos.ObsRechazoExtra) {
                            mensaje += `\n\nMotivo del rechazo: ${datos.ObsRechazoExtra}`;
                        }
                        break;
                    case 3:
                        titulo = "Horas Extras Liquidadas";
                        mensaje = "Las horas extras ya están liquidadas y no pueden ser editadas.";
                        break;
                }

                Swal.fire({
                    icon: icono,
                    title: titulo,
                    text: mensaje,
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                });

                // Si está rechazado, mostrar la observación de rechazo en el formulario
                if (datos.EstadoExtra === 2 && datos.ObsRechazoExtra) {
                    mostrarObservacionRechazoEnFormulario(datos.ObsRechazoExtra);
                }

            } else {
                toggleFormularioExtra(false);
                // Limpiar observación de rechazo si existe
                $('#obsRechazoContainer').remove();
            }

            $('#ModalIngresoExtra').modal('show');
        },
        error: function(xhr, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener datos: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

// Nueva función para mostrar la observación de rechazo en el formulario
function mostrarObservacionRechazoEnFormulario(observacion) {
    // Remover si ya existe
    $('#obsRechazoContainer').remove();
    
    // Crear elemento para mostrar la observación de rechazo
    const obsHtml = `
        <div id="obsRechazoContainer" class="form-group mt-3">
            <label class="text-danger"><i class="fas fa-exclamation-triangle"></i> Motivo del Rechazo:</label>
            <div class="form-control" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; min-height: 80px; white-space: pre-wrap;">
                ${observacion}
            </div>
            <small class="form-text text-muted">
                Esta solicitud fue rechazada por el supervisor. Contacte al área correspondiente para más información.
            </small>
        </div>
    `;
    
    // Insertar después del campo de observación general
    $('#fObsExtra').closest('.form-group').after(obsHtml);
}

function eliminarExtra(id) {
    // Primero, verificar el estado del registro
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorExtra.php?Accion=ListarPorId',
        data: { IdExtra: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontraron datos para eliminar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }

            const datos = response[0];

            // Bloquear eliminación si el estado es 1, 2 o 3
            if (datos.EstadoExtra == 1 || datos.EstadoExtra == 2 || datos.EstadoExtra == 3) {
                let estadoTexto = "";
                let titulo = "";
                let mensajeAdicional = "";

                switch (datos.EstadoExtra) {
                    case 1:
                        estadoTexto = "confirmadas";
                        titulo = "Confirmadas";
                        break;
                    case 2:
                        estadoTexto = "rechazadas";
                        titulo = "Rechazadas";
                        if (datos.ObsRechazoExtra) {
                            mensajeAdicional = `\n\nMotivo del rechazo: ${datos.ObsRechazoExtra}`;
                        }
                        break;
                    case 3:
                        estadoTexto = "liquidadas";
                        titulo = "Liquidadas";
                        break;
                }

                Swal.fire({
                    icon: 'warning',
                    title: `No se puede eliminar`,
                    text: `Las horas extras ya están ${estadoTexto} y no pueden ser eliminadas.${mensajeAdicional}`,
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Si no está confirmado, proceder con la confirmación de eliminación
            Swal.fire({
                title: '¿Está seguro que desea eliminar la Hora Extra ' + id + '?',
                text: "No podrá recuperar los datos!",
                icon: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonColor: '#058876',
                cancelButtonColor: '#550101',
                confirmButtonText: 'Sí, eliminarlo!',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal2-dark' }
            }).then((choice) => {
                if (choice.isConfirmed) {
                    var Extra = { IdExtra: $.trim(id) };
                    CRUDExtra('Eliminar', Extra);
                }
            });
        },
        error: function(xhr, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener datos: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

function limpiarExtra() {
    document.getElementById('fIdExtra').value = '0';
    document.getElementById('fFechaInicioExtra').value = '';
    document.getElementById('fFechaFinExtra').value = '';
    document.getElementById('fObsExtra').value = '';
    document.getElementById('fEstadoExtra').value = '0';
    document.getElementById('fEstadoExtra').disabled = true;
    toggleFormularioExtra(false);
    
    // Limpiar observación de rechazo
    $('#obsRechazoContainer').remove();
}

function AccionExtra() {
    let accion = $('#AccionExtra').val();
    
    let fechaLiquidacion = null;
    
    var Extra = {
        IdExtra: $.trim($('#fIdExtra').val()),
        Idmedico: $.trim($('#fIdMedicose').val()),
        FechaInicioExtra: $.trim($('#fFechaInicioExtra').val()),
        FechaFinExtra: $.trim($('#fFechaFinExtra').val()),
        FechaLiquidacion: fechaLiquidacion,
        ObsExtra: $.trim($('#fObsExtra').val()),
        ObsRechazoExtra: null,
        EstadoExtra: '0'
    };
    
    if (validarExtra()) {
        CRUDExtra(accion, Extra);
    }
}

function CRUDExtra(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorExtra.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Transacción realizada con éxito!",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                    }
                }).then(() => {
                    if ($.fn.DataTable.isDataTable('#tablaHorasExtras')) {
                        table.ajax.reload(null, false);
                    }
                    $('#ModalIngresoExtra').modal('hide');
                });
            }
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
}

$(document).ready(function() {
    table = ingresarHorasExtras();
    
    $('#abrirModalHorasExtras').click(function() {
        table.ajax.reload();
        $('#ModalIngresoExtra').modal('show');
    });
    
    $('#tablaHorasExtras tbody').on('click', '.editar-hora', function() {
        var id = $(this).data('id');
        console.log('Editar registro:', id);
    });

    $('#tablaHorasExtras tbody').on('click', '.eliminar-hora', function() {
        var id = $(this).data('id');
        if (confirm('¿Está seguro de eliminar este registro?')) {
            $.ajax({
                url: 'endpoint_api.php?action=eliminar_hora',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    table.ajax.reload();
                }
            });
        }
    });
    
    $('#fFechaInicioExtra, #fFechaFinExtra').change(function() {
        const fechaInicio = new Date($('#fFechaInicioExtra').val());
        const fechaFin = new Date($('#fFechaFinExtra').val());
        
        if (!isNaN(fechaInicio.getTime()) && !isNaN(fechaFin.getTime()) && fechaFin < fechaInicio) {
            $('#fFechaFinExtra').addClass('is-invalid');
            $('#fechaFinError').remove();
            $('#fFechaFinExtra').after('<div id="fechaFinError" class="invalid-feedback">La fecha de fin no puede ser menor que la de inicio</div>');
        } else {
            $('#fFechaFinExtra').removeClass('is-invalid');
            $('#fechaFinError').remove();
        }
    });
});