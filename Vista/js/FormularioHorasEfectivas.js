var table;
// Variables globales inicializadas
var FechaHorInicio = null;
var FechaHorFin = null;

// Función para crear fechas con zona horaria de Colombia - DECLARADA AL INICIO
const createColombiaDate = (dateString) => {
    try {
        // Validación más robusta
        if (!dateString || dateString === 'undefined' || typeof dateString !== 'string') {
            throw new Error("Fecha no proporcionada o formato inválido");
        }

        const [datePart, timePart] = dateString.split(' ');
        if (!datePart || !timePart) throw new Error("Formato de fecha inválido");

        let day, month, year;

        if (datePart.includes('-')) {
            [year, month, day] = datePart.split('-');
        } else if (datePart.includes('/')) {
            [day, month, year] = datePart.split('/');
        } else {
            throw new Error("Formato de fecha no reconocido");
        }

        if (!day || !month || !year || isNaN(day) || isNaN(month) || isNaN(year)) {
            throw new Error("Componentes de fecha inválidos");
        }

        // Construct ISO date in local time
        const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${timePart.substring(0, 5)}:00`;
     
        const date = new Date(isoDate);

        if (isNaN(date.getTime())) {
            throw new Error("Fecha creada inválida");
        }

        // Adjust to Colombia time (UTC-5)
        const colombiaDate = new Date(date.toLocaleString('en-US', { timeZone: 'America/Bogota' }));
       
        return colombiaDate;
    } catch (error) {
        console.error("Error creating Colombia date:", error.message, "Input:", dateString);
        return null;
    }
};

function limpiarhoras() {
    $('#fIdHorasEfectivas').val(0);
    $('#fIdMedicos').val("");
    $('#fIdTurnos').val("");
    $('#fFecHorFin').val("");
    $('#fFecHorIni').val("");
    $('#fObsHorasEfectivas').val("");
    $('#fEstadoHorasEfectivas').val("");
    toggleFormularioHorasEfectivas(false); // Desbloquear formulario al limpiar
    $('#obsRechazoEfectivasContainer').remove();
}

function ingresarHorEfec() {
    limpiarhoras();
    $("#Accion").val("Ingresar");
    $('#exampleModalLabelHor').html("Agregar Horas Efectivas");
    $("#ModalEfectivas").modal('show');
    $('#fDocumentoMedico').val(DocumentoUsuario);
    $('#nombre').text(NombreUsuario);
    
    // Asegurarse de que el formulario esté desbloqueado al crear nuevo
    toggleFormularioHorasEfectivas(false);
    
    idmedico(DocumentoUsuario, function(idMedico) {
        $('#fIdMedicos').val(idMedico);
        if (idMedico) {
            cargarTurnosMedico(idMedico);
        } else {
            console.error("No se pudo obtener el ID del médico");
            $('#fIdTurnos').empty()
                .append('<option value="">Error al obtener médico</option>')
                .prop('disabled', true);
        }
    });
}

// Versión corregida (necesitarías usar callbacks o promesas)
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

function cargarTurnosMedico(idMedico, callback) {
    if (!idMedico) {
        $('#fIdTurnos').empty()
            .append('<option value="">Seleccione un médico primero</option>')
            .prop('disabled', true);
        
        // Resetear variables globales
        FechaHorInicio = null;
        FechaHorFin = null;
        
        if (typeof callback === 'function') callback();
        return;
    }
    // Obtener fecha actual
    const fechaActual = new Date();
    const mesActual = fechaActual.getMonth() + 1; // Los meses van de 0 a 11
    const añoActual = fechaActual.getFullYear();

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarTurnosDisponibilidad',
        type: 'POST',
        data: {
            IdMedico: idMedico,
            month: mesActual,
            year: añoActual
        },
        dataType: 'json',
        success: function(data, status, xhr) {
            const $selectTurnos = $('#fIdTurnos').empty();
            
            // Resetear variables globales cuando se cargan nuevos turnos
            FechaHorInicio = null;
            FechaHorFin = null;
            
            if (data && data.length > 0) {
                $selectTurnos.append('<option value="">Seleccione un turno</option>');
                
                data.forEach(turno => {
                    const inicio = formatDateTime(turno.FechaHoraInicio);
                    const fin = formatDateTime(turno.FechaHoraFin);
                    const texto = `${inicio} - ${fin}`;
                    
                    $selectTurnos.append(
                        $('<option>', {
                            value: turno.IdTurno,
                            text: texto,
                            'data-inicio': turno.FechaHoraInicio,
                            'data-fin': turno.FechaHoraFin
                        })
                    );
                });
                
                $selectTurnos.prop('disabled', false);
            } else {
                $selectTurnos.append('<option value="">No hay agendas disponibles</option>');
            }
            
            // Ejecutar callback si existe (para el caso de edición)
            if (typeof callback === 'function') callback();
        },
        error: function(xhr, status, error) {
            $('#fIdTurnos').empty()
                .append('<option value="">Error al cargar agendas</option>')
                .prop('disabled', true);
            
            // Resetear variables globales en caso de error
            FechaHorInicio = null;
            FechaHorFin = null;
            
            if (typeof callback === 'function') callback();
        }
    });
}

$(document).ready(function() {
    $('#fIdTurnos').change(function() {
        const selectedOption = $('#fIdTurnos option:selected');
        FechaHorInicio = selectedOption.data('inicio') || null;
        FechaHorFin = selectedOption.data('fin') || null;
        
        console.log('Turno seleccionado (cambio):', {
            inicio: FechaHorInicio,
            fin: FechaHorFin,
            valor: selectedOption.val()
        });
    });
});

// Función auxiliar para obtener datos del turno seleccionado
function obtenerDatosTurnoSeleccionado() {
    const selectedOption = $('#fIdTurnos option:selected');
    if (selectedOption.length > 0 && selectedOption.val() !== '') {
        FechaHorInicio = selectedOption.data('inicio') || null;
        FechaHorFin = selectedOption.data('fin') || null;
        return true;
    }
    return false;
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    
    try {
        const date = new Date(dateTimeString);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Error formateando fecha:', dateTimeString, e);
        return dateTimeString; // Devuelve el original si hay error
    }
}

function actualizarHorEfec(id) {
    limpiarhoras();
    $('#exampleModalLabelHor').html("Editar horas efectivas");
    $("#Accion").val("Actualizar");
    $('#fIdHorasEfectivas').val(id);

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorId',
        data: { IdHorasEfectivas: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                MensajeError("Error", "No se encontraron datos para editar");
                return;
            }
        
            const datos = response[0];
            
            // Asignar valores básicos
            $('#fIdHorasEfectivas').val(datos.IdHorasEfectivas);

            const horaInicio = datos.FecHorIni.split(' ')[1].substring(0, 5);
            const horaFin = datos.FecHorFin.split(' ')[1].substring(0, 5);

            $('#fFecHorIni').val(horaInicio);
            $('#fFecHorFin').val(horaFin);

            $('#fObsHorasEfectivas').val(datos.ObsHorasEfectivas);
            $('#fEstadoHorasEfectivas').val(datos.EstadoHorasEfectivas);
            $('#fIdMedicos').val(datos.IdMedico);
        
            // BLOQUEAR EDICIÓN SI ESTADO ES 1, 2 o 3
            if (datos.EstadoHorasEfectivas == 1 || datos.EstadoHorasEfectivas == 2 || datos.EstadoHorasEfectivas == 3) {
                toggleFormularioHorasEfectivas(true);

                let titulo = "";
                let mensaje = "";

                switch (parseInt(datos.EstadoHorasEfectivas)) {
                    case 1:
                        titulo = "Horas Efectivas Confirmadas";
                        mensaje = "Las horas efectivas ya están confirmadas y no pueden ser editadas.";
                        break;
                    case 2:
                        titulo = "Horas Efectivas Rechazadas";
                        mensaje = "Las horas efectivas ya están rechazadas y no pueden ser editadas.";
                        break;
                    case 3:
                        titulo = "Horas Efectivas Liquidadas";
                        mensaje = "Las horas efectivas ya están liquidadas y no pueden ser editadas.";
                        break;
                }

                if (datos.EstadoHorasEfectivas == 2 && datos.ObsRechazoEfectivas) {
                    mensaje += `\n\nRazón del rechazo: ${datos.ObsRechazoEfectivas}`;
                    
                    // También mostrar la observación en el formulario
                    $('<div id="obsRechazoEfectivasContainer" class="form-group"><label>Observación de Rechazo:</label><div class="form-control" style="background-color: #f8d7da; color: #721c24; min-height: 80px;">' + datos.ObsRechazoEfectivas + '</div></div>')
                        .insertBefore('#ModalEfectivas .modal-footer');
                }

                // PARA ESTADOS 1, 2, 3: CARGAR TURNO ESTÁTICAMENTE SIN LLAMADA AJAX
                cargarTurnoEstatico(datos.IdTurno, datos.FechaHoraInicio, datos.FechaHoraFin);
                
                // Configurar las variables globales con los datos del turno
                FechaHorInicio = datos.FechaHoraInicio;
                FechaHorFin = datos.FechaHoraFin;

                Swal.fire({
                    icon: 'warning',
                    title: titulo,
                    text: mensaje + " Por favor, contacte al supervisor directo.",
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    $("#ModalEfectivas").modal('show');
                });

            } else {
                toggleFormularioHorasEfectivas(false);
                
                // PARA ESTADO PENDIENTE (0): CARGAR TURNOS NORMALMENTE CON AJAX
                cargarTurnosMedico(datos.IdMedico, function() {
                    $('#fIdTurnos').val(datos.IdTurno);
                    const selectedOption = $('#fIdTurnos option:selected');
                    FechaHorInicio = selectedOption.data('inicio') || null;
                    FechaHorFin = selectedOption.data('fin') || null;

                    $("#ModalEfectivas").modal('show');
                });
            }
        },
        error: function(xhr, textStatus) {
            MensajeError("Error", "Error al obtener datos: " + textStatus);
        }
    });
}

// NUEVA FUNCIÓN: Cargar turno estáticamente para estados confirmados/rechazados/liquidados
function cargarTurnoEstatico(idTurno, fechaHoraInicio, fechaHoraFin) {
    const $selectTurnos = $('#fIdTurnos').empty();
    
    if (idTurno && fechaHoraInicio && fechaHoraFin) {
        const inicio = formatDateTime(fechaHoraInicio);
        const fin = formatDateTime(fechaHoraFin);
        const texto = `${inicio} - ${fin}`;
        
        $selectTurnos.append(
            $('<option>', {
                value: idTurno,
                text: texto,
                'data-inicio': fechaHoraInicio,
                'data-fin': fechaHoraFin,
                selected: true
            })
        );
        
        $selectTurnos.prop('disabled', true); // Deshabilitar el select
    } else {
        $selectTurnos.append('<option value="">No hay información de la agenda</option>')
            .prop('disabled', true);
    }
}

function eliminarHorEfec(id) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorId',
        data: { IdHorasEfectivas: id },
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
            
            // NO PERMITIR ELIMINAR SI ESTADO ES 1,2,3
            if (datos.EstadoHorasEfectivas == 1 || datos.EstadoHorasEfectivas == 2 || datos.EstadoHorasEfectivas == 3) {

                let estadoTexto = "";
                let mensajeAdicional = "";
                
                switch (parseInt(datos.EstadoHorasEfectivas)) {
                    case 1: 
                        estadoTexto = "confirmadas"; 
                        break;
                    case 2: 
                        estadoTexto = "rechazadas";
                        if (datos.ObsRechazoEfectivas) {
                            mensajeAdicional = `\n\nRazón del rechazo: ${datos.ObsRechazoEfectivas}`;
                        }
                        break;
                    case 3: 
                        estadoTexto = "liquidadas"; 
                        break;
                }

                Swal.fire({
                    icon: 'warning',
                    title: `No se puede eliminar`,
                    text: `Las horas efectivas ya están ${estadoTexto} y no pueden ser eliminadas. Por favor, contacte al supervisor directo.${mensajeAdicional}`,
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // PERMITIR ELIMINACIÓN SOLO SI ESTADO ES 0
            Swal.fire({
                title: '¿Está seguro que desea eliminar horas efectivas ' + id + ' Registro?',
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
                    var HorasEfec = {
                        IdHorasEfectivas: $.trim(id),
                        IdTurno : $.trim($('#fIdTurnos').val()), 
                        FecHorIni : $.trim($('#fFecHorIni').val()),
                        FecHorFin: $.trim($('#fFecHorFin').val()),
                        ObsHorasEfectivas: $.trim($('#fObsHorasEfectivas').val()),
                        EstadoHorasEfectivas: $.trim($('#fEstadoHorasEfectivas').val())
                    }
                    CRUDHor('Eliminar', HorasEfec);
                }
            });
        },
        error: function(xhr, textStatus) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener datos: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

function validarHor() {
    if (
        $('#fIdMedicos').val() == '' ||
        $('#fIdTurnos').val() == '' ||
        $('#fFecHorIni').val() == '' ||
        $('#fFecHorFin').val() == '' ||
        $('#fEstadoHorasEfectivas').val() == 4
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function AccionHoras() {
    const accion = $('#Accion').val();
    const HorIni = $('#fFecHorIni').val();
    const HorFin = $('#fFecHorFin').val();

    const showErrorAlert = (title, text) => {
        Swal.fire({
            icon: 'error',
            customClass: { popup: 'swal2-dark' },
            title: title,
            text: text,
            confirmButtonColor: '#012c2a',
            confirmButtonText: 'Entendido'
        });
        return false;
    };

    // INTENTAR OBTENER DATOS DEL TURNO SI NO ESTÁN DISPONIBLES
    if (!FechaHorInicio || !FechaHorFin) {
        if (!obtenerDatosTurnoSeleccionado()) {
            return showErrorAlert(
                "Agenda no seleccionado", 
                "Por favor seleccione una agenda válido antes de continuar."
            );
        }
    }

    console.log('Procesando con datos:', {
        FechaHorInicio,
        FechaHorFin,
        HorIni,
        HorFin
    });


    // Parse shift start and end dates - SOLO UNA VEZ
    const inicioTurno = createColombiaDate(FechaHorInicio);
    const finTurno = createColombiaDate(FechaHorFin);

    if (!inicioTurno || !finTurno) {
        return showErrorAlert("Error de validación", "Las fechas de la agenda son inválidas.");
    }

    // Extract dates
    const [fechaTurnoIniDatePart] = FechaHorInicio.split(' '); // e.g., "2025-06-02"
    const [fechaTurnoFinDatePart] = FechaHorFin.split(' '); // e.g., "2025-06-03"

    // Helper function to parse time (HH:mm) to hours and minutes
    const parseTime = (timeStr) => {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return { hours, minutes };
    };

    // Helper function to compare times (HH:mm) relative to shift range
    const isWithinShiftRange = (time) => {
        const { hours, minutes } = parseTime(time);
        const shiftStart = parseTime("19:00");
        const shiftEnd = parseTime("07:00");

        const timeInMinutes = hours * 60 + minutes;
        const startInMinutes = shiftStart.hours * 60 + shiftStart.minutes;
        const endInMinutes = shiftEnd.hours * 60 + shiftEnd.minutes + (24 * 60); // 07:00 next day
        return timeInMinutes >= startInMinutes || timeInMinutes <= endInMinutes;
    };

    // Construct horIniDate
    let horIniDate;
    if (isWithinShiftRange(HorIni)) {
        const iniTime = parseTime(HorIni);
        if (iniTime.hours >= 0 && iniTime.hours < 7) {
            horIniDate = createColombiaDate(`${fechaTurnoFinDatePart} ${HorIni}`);
        } else {
            horIniDate = createColombiaDate(`${fechaTurnoIniDatePart} ${HorIni}`);
        }
    } else {
        return showErrorAlert("Hora de inicio inválida", `La hora de inicio (${HorIni}) no está dentro del turno (7:00 p.m. a 7:00 a.m.).`);
    }

    if (!horIniDate) {
        return showErrorAlert("Error de validación", "La hora de inicio es inválida.");
    }

    // Construct horFinDate
    let horFinDate;
    if (isWithinShiftRange(HorFin)) {
        const finTime = parseTime(HorFin);
        const iniTime = parseTime(HorIni);
        if (finTime.hours >= 0 && finTime.hours < 7 && (finTime.hours < iniTime.hours || (finTime.hours === iniTime.hours && finTime.minutes < iniTime.minutes))) {
            horFinDate = createColombiaDate(`${fechaTurnoFinDatePart} ${HorFin}`);
        } else {
            const dateToUse = iniTime.hours >= 0 && iniTime.hours < 7 ? fechaTurnoFinDatePart : fechaTurnoIniDatePart;
            horFinDate = createColombiaDate(`${dateToUse} ${HorFin}`);
        }
    } else {
        return showErrorAlert("Hora de fin inválida", `La hora de fin (${HorFin}) no está dentro de la agenda (7:00 p.m. a 7:00 a.m.).`);
    }

    if (!horFinDate) {
        return showErrorAlert("Error de validación", "La hora de fin es inválida.");
    }

    if (horIniDate < inicioTurno || horIniDate > finTurno || horFinDate < inicioTurno || horFinDate > finTurno) {
        return showErrorAlert(
            "Hora inválida",
            `Las horas (${HorIni} a ${HorFin}) no están dentro de la agenda (7:00 p.m. a 7:00 a.m.).`
        );
    }

    if (horFinDate <= horIniDate) {
        return showErrorAlert(
            "Hora de fin inválida",
            `La hora de fin (${HorFin}) debe ser después de la hora de inicio (${HorIni}).`
        );
    }

    // Convert to local Colombia time for database
    const formatToLocalColombia = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    };

    const fechasCombinadas = {
        inicio: formatToLocalColombia(new Date(horIniDate.toLocaleString('en-US', { timeZone: 'America/Bogota' }))),
        fin: formatToLocalColombia(new Date(horFinDate.toLocaleString('en-US', { timeZone: 'America/Bogota' })))
    };

    const HorasEfec = {
        IdHorasEfectivas: $.trim($('#fIdHorasEfectivas').val()),
        IdTurno: $.trim($('#fIdTurnos').val()),
        FecHorIni: fechasCombinadas.inicio,
        FecHorFin: fechasCombinadas.fin,
        ObsHorasEfectivas: $.trim($('#fObsHorasEfectivas').val()),
        EstadoHorasEfectivas: 0,
        IdMedico: $.trim($('#fIdMedicos').val())
    };

    if (validarHor()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorCruce',
                data: {
                    IdMedico: HorasEfec.IdMedico,
                    FecHorIni: HorasEfec.FecHorIni,
                    FecHorFin: HorasEfec.FecHorFin
                },
                success: function(msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUDHor(accion, HorasEfec);
                        $('#ModalEfectivas').modal('hide');
                    } else {
                        MensajeError("Dato cruzado al Insertar", "Médico ya tiene horas efectivas registradas en ese rango de fechas!");
                    }
                },
                error: function(req, textStatus, errorThrown) {
                    console.log("AJAX error:", textStatus, errorThrown);
                    MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown);
                }
            });
        } else if (accion == "Actualizar") {
            // Agrega aquí la lógica para actualizar si es necesario
            CRUDHor(accion, HorasEfec);
            $('#ModalEfectivas').modal('hide');
        }
    } else {
        console.log("validarHor() failed");
    }
}

function CRUDHor(accion, objEvento) {

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=' + accion,
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
                    // En lugar de recargar la página, actualizamos solo la tabla
                    if ($.fn.DataTable.isDataTable('#tablaHorasEfectivas')) {
                        table.ajax.reload(null, false); // false para mantener la paginación
                    }
                    
                    // Cerrar el modal si está abierto
                    $('#ModalEfectivas').modal('hide');
                });
            }
            
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
}

function ingresarHoras() {
    let NombreUsuarioRechazo = null;
    if ($.fn.DataTable.isDataTable('#tablaHorasEfectivas')) {
        table = $('#tablaHorasEfectivas').DataTable();
        table.ajax.reload(); // Recargar datos si ya existe
    } else {
        table = $('#tablaHorasEfectivas').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorMedico",
                "data": function(d) {
                    d.DocumentoMedico = DocumentoUsuario;
                },
                "dataSrc": function(json) {
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
                { "data": "FechaHoraInicio" },
                { "data": "FechaHoraFin" },
                { "data": "FecHorIni" },
                { "data": "FecHorFin" },
                { "data": "HorasEfectivas" },
                { 
                    "data": "ObsRechazoEfectivas",
                    "render": function(data, type, row) {
                        if (row.EstadoHorasEfectivas == '2' && data) {
                            return `<span class="badge bg-danger obs-rechazo-btn" title="${data}">Ver Rechazo</span>`;
                        }
                        return '';
                    }
                },
                { 
                    "data": "EstadoHorasEfectivas",
                    "render": function(data, type, row) {
                        // Asignar imagen según el estado
                        let imagen = "";
                        let tooltip = "";
                        NombreUsuarioRechazo = row.NombreUsuario
                        switch(data.toLowerCase()) {
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
                                tooltip = "Rechazada" + (row.ObsRechazoEfectivas ? `: ${row.ObsRechazoEfectivas}` : '');
                                break;
                            case "3":
                                imagen = "liquidado.png";
                                tooltip = "Liquidada";
                                break;
                            default:
                                imagen = "desconocido.png";
                                tooltip = "Estado desconocido";
                        }
                        
                        return `<img src="../Assets/ICON/${imagen}" alt="${data}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    }
                },
                { 
                    "data": "IdHorasEfectivas",
                    "render": function(data, type, row) {
                        return `
                            <button class="update_btn" data-id="${data}">
                                <span class="material-icons" translate="no">update</span>
                            </button>
                            <button class="delete_btn" data-id="${data}" >
                                <span class="material-icons" translate="no">delete</span>
                            </button>
                        `;
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
        $('#tablaHorasEfectivas').on('click', '.update_btn', function() {
            var id = $(this).data('id');
            actualizarHorEfec(id);
        });

        $('#tablaHorasEfectivas').on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            eliminarHorEfec(id);
        });
        $('#tablaHorasEfectivas').on('click', '.obs-rechazo-btn', function() {
            const obsRechazo = $(this).attr('title');
            Swal.fire({
                title: 'Observación de Rechazo',
                html: `<div style="text-align: left; background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; max-height: 300px; overflow-y: auto;">${obsRechazo}</div><br><div style="text-align: center; padding: 15px; max-height: 300px; overflow-y: auto;">Rechazado por:</div><br><div style="text-align: left; background: #d7f8f3; padding: 15px; border-radius: 5px; color: #1c725c; max-height: 300px; overflow-y: auto;">${NombreUsuarioRechazo}</div>`,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        });
    }
    return table;
}

// Nueva función para bloquear/desbloquear el formulario de horas efectivas
function toggleFormularioHorasEfectivas(bloqueado) {
    $('#fIdTurnos').prop('disabled', bloqueado);
    $('#fFecHorIni').prop('disabled', bloqueado);
    $('#fFecHorFin').prop('disabled', bloqueado);
    $('#fObsHorasEfectivas').prop('disabled', bloqueado);
    $('#btnGuardarHoras').prop('disabled', bloqueado); // Asegúrate de que este sea el ID correcto de tu botón
    
    if (bloqueado) {
        $('#alertaConfirmadoHoras').show(); // Asegúrate de tener este elemento en tu HTML
    } else {
        $('#alertaConfirmadoHoras').hide();
    }
}

$(document).ready(function() {
    // Inicializar la tabla por primera vez
    table = ingresarHoras();
    
    // Evento para abrir el modal
    $('#abrirModalHoras').click(function() {
        table.ajax.reload();
        $('#horasEfectivasModal').modal('show');
    });
    
    // Eventos para los botones de acción
    $('#tablaHorasEfectivas tbody').on('click', '.editar-hora', function() {
        var id = $(this).data('id');
    });

    $('#tablaHorasEfectivas tbody').on('click', '.eliminar-hora', function() {
        var id = $(this).data('id');
        if (confirm('¿Está seguro de eliminar este registro?')) {
            $.ajax({
                url: 'endpoint_api.php?action=eliminar_hora',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    table.ajax.reload();
                    alert('Registro eliminado correctamente');
                }
            });
        }
    });
});