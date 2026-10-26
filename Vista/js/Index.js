document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const botonNotificaciones = document.getElementById('boton-notificaciones');
    const botonNotificacionesMobile = document.getElementById('boton-notificaciones-mobile');
    const panelNotificaciones = document.getElementById('panel-notificaciones');
    const panelNotificacionesMobile = document.getElementById('panel-notificaciones-mobile');
    
    // Contadores
    const notificationBadge = document.getElementById('contador-notificaciones');
    const notificationBadgeMobile = document.getElementById('contador-notificaciones-mobile');
    
    // Listas de notificaciones
    const listaNotificaciones = document.getElementById('lista-notificaciones');
    const listaNotificacionesMobile = document.getElementById('lista-notificaciones-mobile');
    
    // Botones de acción
    const marcarTodasLeidas = document.getElementById('marcar-todas-leidas');
    const marcarTodasLeidasMobile = document.getElementById('marcar-todas-leidas-mobile');
    
    var fecIni = null;
    var fecFin = null;
        // Agrega estas variables globales al inicio de tu script
    var fechaInicioCalendario = null;
    var fechaFinCalendario = null;
    // Función para cargar notificaciones
    function cargarNotificaciones() {
        fetch('../Controlador/ControladorNotificaciones.php?Accion=ListarPorUsuario', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `IdUsuario=${IdUsuario}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            actualizarContadores(data);
            renderizarNotificaciones(data);
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Actualizar contadores
    function actualizarContadores(datos) {
        const notificaciones = datos.notificaciones || [];
        const noLeidas = notificaciones.filter(n => n.EstadoNotificaciones === 0).length;

        [notificationBadge, notificationBadgeMobile].forEach(badge => {
            badge.textContent = noLeidas;
            badge.style.display = noLeidas > 0 ? 'inline-block' : 'none';
        });

        document.querySelectorAll('.fa-bell').forEach(campana => {
            campana.classList.toggle('fa-bounce', noLeidas > 0);
        });
    }
    
    // Renderizar notificaciones en ambos paneles
    function renderizarNotificaciones(datos) {
        const notificaciones = datos.notificaciones || [];
        const html = notificaciones.map(notif => `
            <div class="notificacion ${notif.EstadoNotificaciones ? 'leida' : ''} ${notif.PrioridadNotificaciones}" 
                data-enlace="${notif.EnlaceNotificaciones}" 
                data-id="${notif.IdNotificaciones}">
                <div class="mensaje">${notif.MensajeNotificaciones}</div>
                <div class="fecha">${formatearFecha(notif.FechaNotificaciones)}</div>
            </div>
        `).join('');
        
        const noNotificationsHtml = '<div class="notificacion leida"><div class="mensaje">No hay notificaciones</div></div>';
        
        listaNotificaciones.innerHTML = html || noNotificationsHtml;
        listaNotificacionesMobile.innerHTML = html || noNotificationsHtml;
        
        // Configurar eventos para las notificaciones
        configurarEventosNotificaciones();
    }
    
    // Configurar eventos para las notificaciones
    function configurarEventosNotificaciones() {
        document.querySelectorAll('.notificacion').forEach(notifElement => {
            notifElement.addEventListener('click', function() {
                const enlace = this.getAttribute('data-enlace');
                const idNotificacion = this.getAttribute('data-id');
                
                if (idNotificacion) {
                    fetch('../Controlador/ControladorNotificaciones.php?Accion=marcarComoLeida', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `IdNotificaciones=${idNotificacion}`
                    }).then(() => {
                        window.location.href = enlace;
                    });
                } else {
                    window.location.href = enlace;
                }
            });
        });
    }
    
    // Formatear fecha
    function formatearFecha(fechaStr) {
        const fecha = new Date(fechaStr);
        return fecha.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
// Comportamiento para desktop (hover)
if (window.matchMedia("(min-width: 1001px)").matches) {
    // Desktop - comportamiento hover
    const notificationsDropdown = document.querySelector('.notifications-dropdown');
    
    notificationsDropdown.addEventListener('mouseenter', () => {
        panelNotificaciones.classList.add('active');
        // Cerrar el panel móvil por si acaso
        panelNotificacionesMobile.classList.remove('active');
    });
    
    notificationsDropdown.addEventListener('mouseleave', () => {
        panelNotificaciones.classList.remove('active');
    });
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!notificationsDropdown.contains(e.target)) {
            panelNotificaciones.classList.remove('active');
        }
    });
} else {
    // Móvil - comportamiento click
    botonNotificacionesMobile.addEventListener('click', (e) => {
        e.preventDefault();
        panelNotificacionesMobile.classList.toggle('active');
        // Cerrar el panel desktop por si acaso
        panelNotificaciones.classList.remove('active');
    });
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.mobile-notifications')) {
            panelNotificacionesMobile.classList.remove('active');
        }
    });
}
    
    // Cerrar paneles al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notifications-dropdown') && !e.target.closest('.mobile-notifications')) {
            [panelNotificaciones, panelNotificacionesMobile].forEach(panel => {
                panel.classList.remove('active');
            });
        }
    });
    
    // Marcar todas como leídas
    [marcarTodasLeidas, marcarTodasLeidasMobile].forEach(boton => {
        boton.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../Controlador/ControladorNotificaciones.php?Accion=MarcarLeidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `IdUsuario=${IdUsuario}`
            })
            .then(() => cargarNotificaciones());
        });
    });
    
    // Cargar notificaciones al inicio y cada 30 segundos
    cargarNotificaciones();
    setInterval(cargarNotificaciones, 30000);
});



function obtenerPrimerUltimoDiaMes(fecha) {
    const f = new Date(fecha);
    f.setHours(0, 0, 0, 0);

    const primerDiaMes = new Date(f.getFullYear(), f.getMonth(), 1);
    const ultimoDiaMes = new Date(f.getFullYear(), f.getMonth() + 1, 0);

    const primerDia = `${primerDiaMes.getFullYear()}-${String(primerDiaMes.getMonth() + 1).padStart(2, '0')}-01`;
    const ultimoDia = `${ultimoDiaMes.getFullYear()}-${String(ultimoDiaMes.getMonth() + 1).padStart(2, '0')}-${String(ultimoDiaMes.getDate()).padStart(2, '0')}`;

    return { primerDia, ultimoDia };
}


function Formulario(id, nombreArea) {
    // Redirige a la página del formulario
    window.location.href = 'FormularioTurnos.php?id=' + id + '&nombreArea=' + encodeURIComponent(nombreArea);
    // Espera un momento para que la redirección ocurra
}

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 830,
        contentHeight: 1000,
        aspectRatio: 0.9,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        locale: 'es',
        timeZone: 'America/Bogota', // Zona horaria de Colombia
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        // initialDate: '2025-05-01', // Opcional: establecer fecha inicial
        events: function (fetchInfo, successCallback, failureCallback) {
            // Calcular el mes central del rango visible
            const fechaInicio = new Date(fetchInfo.startStr);
            const fechaFin = new Date(fetchInfo.endStr);
            const fechaCentral = new Date((fechaInicio.getTime() + fechaFin.getTime()) / 2);
            const { primerDia, ultimoDia } = obtenerPrimerUltimoDiaMes(fechaCentral);
            const mesSeleccionado = fechaCentral.getMonth() + 1; // Mes en formato 1-12
           
            obtenerTurnosPorMes(primerDia, ultimoDia, DocumentoUsuario, successCallback, failureCallback);
        },
        datesSet: function (dateInfo) {
            // Usar dateInfo.view.currentStart para obtener el mes del título
        fechaInicioCalendario = dateInfo.start;
        fechaFinCalendario = dateInfo.end;
        
        // Usar dateInfo.view.currentStart para obtener el mes del título
        const mesMostrado = dateInfo.view.currentStart.getMonth() + 1; // Mes del título
        const anioMostrado = dateInfo.view.currentStart.getFullYear();
            
           
        },
        dateClick: function (info) {
            const fechaSeleccionada = info.dateStr;
            const fechaInicioValue = fechaSeleccionada + 'T00:00';
            const fechaFinValue = fechaSeleccionada + 'T23:59';
            obtenerTurnosPorDia(fechaInicioValue, fechaFinValue, DocumentoUsuario);
        },
        eventClick: function (info) {
            const fechaSeleccionada = info.jsEvent.target.closest('.fc-day').getAttribute('data-date');
            const fechaInicioValue = fechaSeleccionada + 'T00:00';
            const fechaFinValue = fechaSeleccionada + 'T23:59';
            obtenerTurnosPorDia(fechaInicioValue, fechaFinValue, DocumentoUsuario);
        },
        eventContent: function (arg) {
            let container = document.createElement('div');
            container.style.display = 'flex';
            container.style.alignItems = 'center';
            container.style.margin = '0px';

            let streak = document.createElement('div');
            streak.style.width = '4px';
            streak.style.height = '20px';
            streak.style.margin = '1px';
            streak.style.backgroundColor = arg.event.backgroundColor || '#3788d8';

            let title = document.createElement('div');
            title.innerText = arg.event.title;
            title.style.fontSize = '8px';
            title.style.fontWeight = 'bold';
            title.style.fontFamily = "'Estilo Montserrat', sans-serif";
            title.classList.add('evento-title');

            let cellWidth = arg.el ? arg.el.clientWidth : 100;
            let streakWidth = 2;
            let marginRight = 8;
            let availableWidth = cellWidth - streakWidth - marginRight;

            container.appendChild(streak);
            container.appendChild(title);

            return { domNodes: [container] };
        }
    });

    calendar.render();
});

function obtenerTurnosPorMes(FechaHoraInicio, FechaHoraFin, DocumentoUsuario, successCallback, failureCallback) {
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarPorTurnosAreaDocumento',
        type: 'POST',
        data: { FechaHoraInicio: FechaHoraInicio, FechaHoraFin: FechaHoraFin, DocumentoUsuario: DocumentoUsuario },
        success: function (response) {
            try {
                let turnos = Array.isArray(response) ? response : [];
                let eventos = turnos.map(turno => ({
                    title: `${turno.NombreActividad}`,
                    start: turno.FechaHoraInicio,
                    end: turno.FechaHoraFin,
                    extendedProps: { idTurno: turno.IdTurno },
                    backgroundColor: '#0b2e30', // Color de fondo del evento
                    
                }));
                console.log('Eventos formateados:', eventos);
                successCallback(eventos);
            } catch (error) {
                alert('Hubo un error al procesar la respuesta del servidor.');
                console.error('Error al procesar los turnos:', error);
                failureCallback(error);
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

function obtenerTurnosPorDia(FechaHoraInicio, FechaHoraFin, DocumentoUsuario) {
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarPorTurnosAreaDocumento',
        type: 'POST',
        data: { FechaHoraInicio: FechaHoraInicio, FechaHoraFin: FechaHoraFin, DocumentoUsuario: DocumentoUsuario },
        success: function (response) {
            // Verifica si la respuesta ya es un objeto
            console.log(typeof response); // Debería ser "object" si ya es un objeto JSON

            // Si la respuesta es una cadena JSON, usa JSON.parse
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error('Error al parsear JSON:', e);
                    return;
                }
            }

            // Si la respuesta es nula, indefinida o un array vacío, no abrir el modal
            if (!response || response.length === 0) {
                console.warn("No se encontraron turnos para mostrar.");
                return;
            }

            // Formatear la fecha para el título del modal
            let fecha = new Date(FechaHoraInicio);
            let dia = fecha.getDate();
            let diaSemana = fecha.toLocaleDateString('es-CO', { weekday: 'long' });
            diaSemana = diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1);

            // Actualizar el título del modal
            $("#diaModal").text(dia);
            $("#diaSemanaModal").text(diaSemana);

            // Limpiar el contenido previo del modal
            $("#turnosInfo").empty();

            // Iterar sobre los turnos y agregar la información al modal
            response.forEach(function(turno, index) {
                let horaInicio = new Date(turno.FechaHoraInicio).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });
                let horaFin = new Date(turno.FechaHoraFin).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });

                let contenido = `
                <div class="turno-container">
                    ${index === 0 ? `<div class="hora-inicio">${horaInicio}</div>` : '<div class="hora-inicio"></div>'}
                    
                    <div class="turno-info">
                        <div class="turno-item">
                            <div class="nombre-actividad">${turno.NombreActividad}</div>
                        </div>
                        <div class="rango-horas">${horaInicio} - ${horaFin}</div>
                    </div>
                </div>
                <br>
                `;

                $("#turnosInfo").append(contenido);
            });

            // Mostrar el modal
            $("#modal").modal('show');
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
            console.log('Respuesta completa del servidor:', jqXHR.responseText);
        }
    });
}

function ListarPorAutorizacion() {
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarPorAutorizacionNoti',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            // Selecciona específicamente el ícono dentro del enlace "Autorizar"
            const autorizarBellIcon = $("#notification-badge-autorizar").siblings(".fa-bell");
            
            if (!response || response.length === 0) {
                console.warn("No se encontraron turnos para mostrar.");
                // Ocultar el badge y quitar el efecto bounce solo en Autorizar
                $("#notification-badge-autorizar").hide();
                autorizarBellIcon.removeClass("fa-bounce");
                return;
            }

            // Mostrar el badge con el número de áreas pendientes solo en Autorizar
            const numAreasPendientes = response.length;
            $("#notification-badge-autorizar").text(numAreasPendientes).show();
            autorizarBellIcon.addClass("fa-bounce");
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
            console.log('Respuesta completa del servidor:', jqXHR.responseText);
        }
    });
}

// Ejecutar ListarPorAutorizacion cada 10 segundos
setInterval(ListarPorAutorizacion, 10000); // 10000 milisegundos = 10 segundos

// Ejecutar ListarPorAutorizacion al cargar la página
$(document).ready(function() {
    ListarPorAutorizacion(); // Primera ejecución
});



// Function to download PDF
// Modifica la función descargarPDF para usar las fechas almacenadas
function descargarPDF() {
    
    if (!fechaInicioCalendario || !fechaFinCalendario) {
        alert('No se ha podido determinar el rango de fechas del calendario.');
        return;
    }


     // Ajustar la fecha sumando un mes (por el problema de 0-indexed)
    const fechaAjustada = new Date(fechaInicioCalendario);
    
    // 🔐 Blindaje contra febrero y zonas horarias
    fechaAjustada.setDate(1);
    fechaAjustada.setHours(0, 0, 0, 0);
    fechaAjustada.setMonth(fechaAjustada.getMonth() + 1);
        

    // Obtener el primer y último día del mes mostrado
    const { primerDia, ultimoDia } = obtenerPrimerUltimoDiaMes(fechaAjustada);
    
    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=DescargarPDFMedico',
        type: 'POST',
        data: {
            FechaHoraInicio: primerDia,
            FechaHoraFin: ultimoDia,
            DocumentoUsuario: DocumentoUsuario
        },
        xhrFields: {
            responseType: 'blob' // Important for handling binary data (PDF file)
        },
        success: function (data, status, xhr) {
            const blob = new Blob([data], { type: 'application/pdf' });
            const link = document.createElement('a');
            const url = window.URL.createObjectURL(blob);
            link.href = url;
            const contentDisposition = xhr.getResponseHeader('Content-Disposition');
            let fileName = 'turnos.pdf';
            if (contentDisposition) {
                const match = contentDisposition.match(/filename="(.+)"/);
                if (match) fileName = match[1];
            }
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error al descargar el PDF:', textStatus, errorThrown);
            alert('Error al generar el archivo PDF.');
        }
    });
}

