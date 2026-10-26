
document.addEventListener('DOMContentLoaded', function () {
    
    // Obtener el idArea desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const idArea = urlParams.get('id'); // Extrae el parámetro 'id'

    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 700,
        contentHeight: 1000,
        aspectRatio: 0.5,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        locale: 'es',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: function (fetchInfo, successCallback, failureCallback) {
            const fechaInicio = new Date(fetchInfo.startStr);
            const fechaFin = new Date(fetchInfo.endStr);
            obtenerTurnosPorMes(fechaInicio.toISOString(), fechaFin.toISOString(), idArea, successCallback, failureCallback);
        },
        datesSet: function (dateInfo) {
            const fechaInicio = new Date(dateInfo.startStr);
        
            // Aumentar un mes a la fecha
            const fechaAumentada = new Date(fechaInicio);
            fechaAumentada.setMonth(fechaAumentada.getMonth() + 1);
        
            // Obtener el año de la fecha aumentada
            const year = fechaAumentada.getFullYear();
        
            const urlParams = new URLSearchParams(window.location.search);
            const nombreArea = urlParams.get('nombreArea');
        

        
            // Limpiar la tabla antes de renderizar los nuevos datos
            const tbody = document.querySelector("#turnosTable tbody");
            tbody.innerHTML = "";
        
            // Obtener los turnos para el nuevo mes
            obtenerTurnosPorMes(dateInfo.startStr, dateInfo.endStr, idArea, function (eventos) {
                // Renderizar la tabla con los nuevos datos
            }, function (error) {
                console.error('Error al obtener los turnos:', error);
            });
        },

        eventClick: function (info) {
            const idTurno = info.event.extendedProps.idTurno;
            // Opcional: Mostrar detalles del turno al hacer clic en un evento
            actualizarTurnos(idTurno);
        },

        dateClick: function (info) {

            // Establecer la fecha seleccionada en los campos de inicio y fin
            const fechaSeleccionada = info.dateStr; // Formato YYYY-MM-DD
                   
            // Asignar la fecha seleccionada con una hora predeterminada (por ejemplo, 00:00)
            const fechaInicioValue = fechaSeleccionada + 'T00:00';
            const fechaFinValue = fechaSeleccionada + 'T00:00';         
                 
            $('#fFechaHoraInicio').val(fechaInicioValue);
            $('#fFechaHoraFin').val(fechaFinValue);

            // Abrir el modal
            
            $('#ModalIngresoTurnos').modal('show');
            document.getElementById('btnEliminar').style.display = 'none';
            $("#Accion").val("Ingresar");
            // Resetear otros campos del modal
            resetModal();
        },
        now: new Date().toISOString(),
    });

    calendar.render();

    // Función para resetear el modal
    function resetModal() {
        // Reseteamos los campos del formulario (ajustar según los campos del formulario)
        document.getElementById('formTurno').reset(); // Si tienes un formulario con el ID 'formularioTurno'
        // Si quieres limpiar campos específicos
        document.getElementById('fNomenclatura').value = ''; // Ejemplo de campo específico
        document.getElementById('fIdMedico').value = ''; // Ejemplo de campo específico
        $('#fIdMedico').val("");
        $('#fIdMedico').val('').trigger('change');
        $('#fIdMedico').prop('disabled', false); // Habilitar el campo select con id 'fIdMedico'
    }

    document.getElementById('fNomenclatura').addEventListener('change', function () {
        const nomenclaturaId = this.value;
        
        const fechaInicio = document.getElementById('fFechaHoraInicio');
        const fechaFin = document.getElementById('fFechaHoraFin');
    
        // Obtener las horas de inicio y fin según la nomenclatura seleccionada
        obtenerHorasNomenclatura(nomenclaturaId, function (horas) {
            // Verificar que los datos de la nomenclatura sean válidos
            if (!horas || !horas.FechaHoraInicio || !horas.FechaHoraFin) {
                console.error('Datos de nomenclatura no válidos');
                return;
            }
        
            // Obtener la fecha actual del campo fFechaHoraInicio (ya tiene la fecha seleccionada)
            const fechaSeleccionada = fechaInicio.value.split('T')[0]; // Extrae la parte de la fecha (yyyy-MM-dd)
        
            // Concatenar la fecha seleccionada con la hora de inicio y fin
            const fechaInicioValue = fechaSeleccionada + 'T' + horas.FechaHoraInicio;
            let fechaFinValue = fechaSeleccionada + 'T' + horas.FechaHoraFin;
        
            // Convertir las cadenas de fecha y hora a objetos Date
            const fechaInicioDate = new Date(fechaInicioValue);
            const fechaFinDate = new Date(fechaFinValue);
        
            // Verificar si la hora de fin es menor que la hora de inicio (evento pasa de medianoche)
            if (fechaFinDate < fechaInicioDate) {
                // Sumar un día a la fecha de fin
                fechaFinDate.setDate(fechaFinDate.getDate() + 1);
                // Actualizar la cadena de fechaFinValue con la nueva fecha
                fechaFinValue = fechaFinDate.toISOString().split('T')[0] + 'T' + horas.FechaHoraFin;
            }
        
            // Asignar los valores a los campos
            fechaInicio.value = fechaInicioValue;
            fechaFin.value = fechaFinValue;
        });
    });

    // Función para obtener las horas de la nomenclatura
    function obtenerHorasNomenclatura(id, callback) {

        $.ajax({
            type: 'POST',
            url: '../Controlador/ControladorNomenclatura.php?Accion=ListarPorId',
            data: { IdNomenclatura: id },
            success: function (msg) {
                if (msg.error) {
                    alert(msg.error);
                    return;
                }
                // Verifica que la respuesta tenga los datos esperados
                if (msg.length > 0 && msg[0].FechaHoraInicio && msg[0].FechaHoraFin) {
                    callback(msg[0]); // Llama al callback con los datos de la nomenclatura
                } else {
                    alert('Datos de nomenclatura no válidos');
                }
            },
            error: function (req, textStatus, errorThrown) {
                alert(JSON.stringify(req));
                MensajeError("Oops...", "Error al obtener Datos1!", textStatus + ' ' + errorThrown);
            }
        });
    }
});


function obtenerTurnosPorMes(FechaHoraInicio, FechaHoraFin, idArea, successCallback, failureCallback) {
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarPorTurnosAreaFecha',
        type: 'POST',
        data: { FechaHoraInicio: FechaHoraInicio, FechaHoraFin: FechaHoraFin, IdArea: idArea },
        success: function (response) {
            try {
                let turnos = typeof response === 'string' ? JSON.parse(response) : response;
                if (!Array.isArray(turnos)) {
                    throw new Error('La respuesta no es un array válido.');
                }

                // Obtener el año y mes de la fecha de inicio
                const fechaInicio = new Date(FechaHoraInicio);
                const year = fechaInicio.getFullYear();
                const month = fechaInicio.getMonth() + 1;

                // Renderizar la tabla con los datos del mes
                renderTable(turnos, year, month);

                // Mapear los turnos para FullCalendar
                let eventos = turnos.map(turno => ({
                    title: `${turno.NombreMedico} - ${turno.NombreActividad}`,
                    start: turno.FechaHoraInicio,
                    end: turno.FechaHoraFin,
                    extendedProps: { idTurno: turno.IdTurno }
                }));


                successCallback(eventos);
            } catch (error) {
                console.error('Error al procesar los turnos:', error);
                failureCallback(error);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
            failureCallback(errorThrown);
        }
    });
}

