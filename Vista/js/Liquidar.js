function obtenerPrimerUltimoDiaMes(fecha) {
    // Crear una nueva fecha a partir de la cadena de fecha
    const fechaObj = new Date(fecha);
    
    // Crear una nueva fecha para el primer día del mes
    const primerDiaMes = new Date(fechaObj.getFullYear(), fechaObj.getMonth(), 1);
    
    // Crear una nueva fecha para el último día del mes
    const ultimoDiaMes = new Date(fechaObj.getFullYear(), fechaObj.getMonth() + 1, 0);
    
    // Formatear las fechas como cadenas en el formato deseado
    const primerDia = primerDiaMes.toISOString().split('T')[0];
    const ultimoDia = ultimoDiaMes.toISOString().split('T')[0];

    return { primerDia, ultimoDia };
}

// Función para obtener turnos de un mes visible y agregarlos al calendario
function obtenerTurnosPorMes() {
    var formData = $('#formFiltro').serialize();
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=Filtro',
        type: 'GET',
        data: formData,
        success: function (response) {
            try {
               
                let turnos = Array.isArray(response) ? response : [];
                // Mapear los turnos al formato esperado por FullCalendar
                let eventos = turnos.map(turno => ({
                    title: `${turno.NombreActividad}`,
                    start: turno.FechaHoraInicio,
                    end: turno.FechaHoraFin,
                    extendedProps: { idTurno: turno.IdTurno }
                }));
                console.log('Eventos formateados:', eventos);  // Ver los eventos mapeados
                // Pasar los eventos al calendario
                successCallback(eventos);
            } catch (error) {
                alert('Hubo un error al procesar la respuesta del servidor.');

            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
            console.log('Respuesta completa del servidor:', jqXHR.responseText);
            try {
                let parsedResponse = JSON.parse(jqXHR.responseText);
                console.log('Respuesta JSON procesada:', parsedResponse);
            } catch (e) {
                console.error('Error al analizar el JSON:', e);
            }
            failureCallback(errorThrown);
        }        
    });
}



