// ============================================================
//  Menu.js
//  - Notificaciones del usuario
//  - Badge de agendas pendientes por autorizar
//
//  IMPORTANTE: la función que actualiza el badge se llama
//  ActualizarBadgeAutorizar() y NO ListarPorAutorizacion().
//  El nombre anterior colisionaba con la función global de
//  Autorizar.js y provocaba repintados completos cada 10s.
// ============================================================

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

function Formulario(id, nombreArea) {
    window.location.href = 'FormularioTurnos.php?id=' + id + '&nombreArea=' + encodeURIComponent(nombreArea);
}

// ============================================================
//  BADGE DE AGENDAS PENDIENTES POR AUTORIZAR
// ============================================================
(function () {
    let peticionBadgeEnCurso = null;
    let ultimoConteoBadge = null;   // evita tocar el DOM si no cambió

    // Nombre único: no colisiona con nada de Autorizar.js
    window.ActualizarBadgeAutorizar = function () {
        // No pedir si la pestaña está oculta
        if (document.hidden) return;
        // No solapar peticiones
        if (peticionBadgeEnCurso && peticionBadgeEnCurso.readyState !== 4) return;

        peticionBadgeEnCurso = $.ajax({
            url: '../Controlador/ControladorTurno.php?Accion=ListarPorAutorizacionNoti',
            type: 'POST',
            dataType: 'json',
            timeout: 15000,
            success: function (response) {
                const badge = $("#notification-badge-autorizar");
                if (badge.length === 0) return; // el menú no tiene ese badge en esta vista

                const autorizarBellIcon = badge.siblings(".fa-bell");
                const conteo = (!response || response.length === 0) ? 0 : response.length;

                // Cortocircuito: si el conteo no cambió, no tocamos el DOM
                if (conteo === ultimoConteoBadge) return;
                ultimoConteoBadge = conteo;

                if (conteo === 0) {
                    badge.hide();
                    autorizarBellIcon.removeClass("fa-bounce");
                } else {
                    badge.text(conteo).show();
                    autorizarBellIcon.addClass("fa-bounce");
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus === 'abort') return;
                console.error('Error al actualizar badge de autorización:', textStatus, errorThrown);
            }
        });
    };

    $(document).ready(function () {
        // Solo si el badge existe en el menú de esta página
        if ($("#notification-badge-autorizar").length === 0) return;

        ActualizarBadgeAutorizar();

        // 30s en vez de 10s: es solo un contador, no necesita tanta frecuencia
        setInterval(ActualizarBadgeAutorizar, 30000);

        // Al volver a la pestaña, refrescar el contador
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) ActualizarBadgeAutorizar();
        });
    });
})();

// ============================================================
//  NOTIFICACIONES DEL USUARIO
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
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

    // Si el menú de notificaciones no está en esta página, no seguimos
    if (!panelNotificaciones && !panelNotificacionesMobile) return;

    // Huella de las notificaciones ya pintadas (evita repintar el panel)
    let firmaNotificaciones = null;

    function calcularFirma(notificaciones) {
        return notificaciones
            .map(n => `${n.IdNotificaciones}|${n.EstadoNotificaciones}`)
            .join('#');
    }

    // ¿Hay algún panel abierto? Si sí, no repintamos para no interrumpir al usuario
    function hayPanelAbierto() {
        return (panelNotificaciones && panelNotificaciones.classList.contains('active')) ||
               (panelNotificacionesMobile && panelNotificacionesMobile.classList.contains('active'));
    }

    // Función para cargar notificaciones
    function cargarNotificaciones(forzar) {
        if (document.hidden && !forzar) return;

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

                const notificaciones = data.notificaciones || [];
                const firmaNueva = calcularFirma(notificaciones);

                // Nada cambió -> no tocamos el DOM
                if (!forzar && firmaNueva === firmaNotificaciones) return;
                // Panel abierto -> no repintamos encima del usuario
                if (!forzar && hayPanelAbierto()) return;

                firmaNotificaciones = firmaNueva;
                actualizarContadores(notificaciones);
                renderizarNotificaciones(notificaciones);
            })
            .catch(error => console.error('Error:', error));
    }

    // Actualizar contadores
    function actualizarContadores(notificaciones) {
        const noLeidas = notificaciones.filter(n => n.EstadoNotificaciones === 0).length;

        [notificationBadge, notificationBadgeMobile].forEach(badge => {
            if (!badge) return;
            badge.textContent = noLeidas;
            badge.style.display = noLeidas > 0 ? 'inline-block' : 'none';
        });

        // Solo las campanas de notificaciones, NO la de autorizar
        document.querySelectorAll('.notifications-dropdown .fa-bell, .mobile-notifications .fa-bell')
            .forEach(campana => {
                campana.classList.toggle('fa-bounce', noLeidas > 0);
            });
    }

    // Renderizar notificaciones en ambos paneles
    function renderizarNotificaciones(notificaciones) {
        const html = notificaciones.map(notif => `
            <div class="notificacion ${notif.EstadoNotificaciones ? 'leida' : ''} ${notif.PrioridadNotificaciones}"
                data-enlace="${notif.EnlaceNotificaciones}"
                data-id="${notif.IdNotificaciones}">
                <div class="mensaje">${notif.MensajeNotificaciones}</div>
                <div class="fecha">${formatearFecha(notif.FechaNotificaciones)}</div>
            </div>
        `).join('');

        const noNotificationsHtml = '<div class="notificacion leida"><div class="mensaje">No hay notificaciones</div></div>';

        if (listaNotificaciones) listaNotificaciones.innerHTML = html || noNotificationsHtml;
        if (listaNotificacionesMobile) listaNotificacionesMobile.innerHTML = html || noNotificationsHtml;
    }

    // Eventos de notificaciones: delegados, se enlazan UNA sola vez
    function initEventosNotificaciones() {
        [listaNotificaciones, listaNotificacionesMobile].forEach(lista => {
            if (!lista) return;
            lista.addEventListener('click', function (e) {
                const notifElement = e.target.closest('.notificacion');
                if (!notifElement) return;

                const enlace = notifElement.getAttribute('data-enlace');
                const idNotificacion = notifElement.getAttribute('data-id');

                if (idNotificacion) {
                    fetch('../Controlador/ControladorNotificaciones.php?Accion=marcarComoLeida', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `IdNotificaciones=${idNotificacion}`
                    }).then(() => {
                        if (enlace) window.location.href = enlace;
                    });
                } else if (enlace) {
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
        const notificationsDropdown = document.querySelector('.notifications-dropdown');

        if (notificationsDropdown) {
            notificationsDropdown.addEventListener('mouseenter', () => {
                if (panelNotificaciones) panelNotificaciones.classList.add('active');
                if (panelNotificacionesMobile) panelNotificacionesMobile.classList.remove('active');
            });

            notificationsDropdown.addEventListener('mouseleave', () => {
                if (panelNotificaciones) panelNotificaciones.classList.remove('active');
            });
        }
    } else {
        // Móvil - comportamiento click
        if (botonNotificacionesMobile) {
            botonNotificacionesMobile.addEventListener('click', (e) => {
                e.preventDefault();
                if (panelNotificacionesMobile) panelNotificacionesMobile.classList.toggle('active');
                if (panelNotificaciones) panelNotificaciones.classList.remove('active');
            });
        }
    }

    // Cerrar paneles al hacer clic fuera (un solo listener)
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notifications-dropdown') && !e.target.closest('.mobile-notifications')) {
            [panelNotificaciones, panelNotificacionesMobile].forEach(panel => {
                if (panel) panel.classList.remove('active');
            });
        }
    });

    // Marcar todas como leídas
    [marcarTodasLeidas, marcarTodasLeidasMobile].forEach(boton => {
        if (!boton) return;
        boton.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../Controlador/ControladorNotificaciones.php?Accion=MarcarLeidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `IdUsuario=${IdUsuario}`
            })
                .then(() => cargarNotificaciones(true)); // forzar repintado
        });
    });

    // Enlazar eventos una sola vez
    initEventosNotificaciones();

    // Cargar notificaciones al inicio y cada 30 segundos
    cargarNotificaciones(true);
    setInterval(() => cargarNotificaciones(false), 30000);

    // Al volver a la pestaña, refrescar
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) cargarNotificaciones(false);
    });
});