// ============================================================
//  Autorizar.js  -  Autorización de Turnos
//  - Validación de cruces de agenda antes de autorizar
//  - Carga optimizada (cancelación de peticiones + token anti-obsoletas + skeleton)
// ============================================================

// ---- Control de peticiones de carga (evita solapamientos / respuestas obsoletas) ----
let peticionListarEnCurso = null;   // jqXHR de la carga actual
let listarRequestToken = 0;         // contador incremental para descartar respuestas viejas

// ---- Control de re-render (evita parpadeo en el auto-refresh) ----
let firmaAgendasActual = null;      // huella de los datos ya pintados
let primeraCargaHecha = false;      // para saber si ya hay algo en pantalla
let procesandoAccion = false;       // bloqueo global de botones (autorizar/rechazar)

// Genera una huella de los datos relevantes. Si no cambia, no se repinta el DOM.
function firmaAgendas(response) {
    if (!Array.isArray(response)) return 'vacio';
    return response
        .map(t => `${t.IdArea}|${t.EstadoTurno}|${t.NombreArea}|${t.Mes}|${t.NumeroMes}`)
        .sort()
        .join('#');
}

// ============================================================
//  CARGA DE AGENDAS (orquestador)
// ============================================================
function ListarPorAutorizacion(opciones) {
    // Blindaje: si alguien externo (p.ej. un Menu.js en caché) llama a esta
    // función sin argumentos, la tratamos como refresco silencioso siempre que
    // ya haya contenido en pantalla. Así nunca se dispara un repintado visible.
    if (opciones === undefined && primeraCargaHecha) {
        opciones = { silencioso: true };
    }
    opciones = opciones || {};
    const silencioso = opciones.silencioso === true; // auto-refresh: sin skeleton

    const month = $('#monthSelect').val();
    const year = $('#yearSelect').val();

    // Token único de esta petición
    const miToken = ++listarRequestToken;

    // Cancelar la petición anterior si sigue viva (evita race conditions)
    if (peticionListarEnCurso && peticionListarEnCurso.readyState !== 4) {
        peticionListarEnCurso.abort();
    }

    // Mostrar indicador de carga (solo si NO es refresco silencioso)
    if (!silencioso) {
        mostrarCargando();
    }

    peticionListarEnCurso = $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarPorAutorizacion',
        type: 'POST',
        data: { month: month, year: year },
        dataType: 'json',
        timeout: 20000,
        success: function (response) {
            // Descartar si ya salió una petición más reciente
            if (miToken !== listarRequestToken) return;
            // Descartar si el usuario cambió de mes/año mientras cargaba
            if ($('#monthSelect').val() !== month || $('#yearSelect').val() !== year) return;

            renderizarAgendas(response, month, year, silencioso);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Ignorar abortos intencionales (cancelamos nosotros)
            if (textStatus === 'abort') return;
            // Ignorar si ya hay una petición más reciente
            if (miToken !== listarRequestToken) return;

            console.error('Error en la solicitud AJAX:', textStatus, errorThrown);

            // Si es un refresco silencioso y ya hay agendas en pantalla,
            // NO borramos nada: se reintenta en el siguiente ciclo.
            if (silencioso && primeraCargaHecha) return;
            $("#turnosInfo").empty().append(`
                <div class="error-message">
                    <i class="material-icons">error</i>
                    <span>Ocurrió un error al cargar las agendas.</span>
                    <button id="btnReintentarCarga" class="btn-reintentar">
                        <i class="material-icons">refresh</i> Reintentar
                    </button>
                </div>
            `);
        }
    });
}

// ---- Skeleton / indicador de carga ----
function mostrarCargando() {
    $("#turnosInfo").html(`
        <div class="loading-agendas">
            <div class="spinner"></div>
            <span>Cargando agendas...</span>
        </div>
    `);
}

// ============================================================
//  RENDER DE AGENDAS
// ============================================================
function renderizarAgendas(response, month, year, silencioso) {
    const autorizarBellIcon = $("#notification-badge-autorizar").siblings(".fa-bell");

    // --- Cortocircuito: si nada cambió, no tocamos el DOM (cero parpadeo) ---
    const firmaNueva = firmaAgendas(response);
    if (silencioso && primeraCargaHecha && firmaNueva === firmaAgendasActual) {
        return; // los datos son idénticos, la pantalla ya está correcta
    }

    // Nunca repintar mientras el usuario tiene un modal abierto o una acción en curso
    if (silencioso && (procesandoAccion || (typeof Swal !== 'undefined' && Swal.isVisible()))) {
        return;
    }

    firmaAgendasActual = firmaNueva;

    if (!response || response.length === 0) {
        $("#notification-badge-autorizar").hide();
        autorizarBellIcon.removeClass("fa-bounce");
        let mensaje = `
        <div class="no-agendas">
            No hay agendas para autorizar para ${getMonthName(month)} ${year}.
        </div>
        `;
        // Swap atómico: reemplaza en una sola operación, sin empty() previo
        $("#turnosInfo").html(mensaje);
        primeraCargaHecha = true;
        $("#notification-badge-cuenta, #notification-badge-autorizar").hide();
        return;
    }

    // Contar áreas pendientes (EstadoTurno = 0)
    const numAreasPendientes = response.filter(t => t.EstadoTurno === 0).length;
    $("#notification-badge-autorizar").text(numAreasPendientes).toggle(numAreasPendientes > 0);
    if (numAreasPendientes > 0) {
        autorizarBellIcon.addClass("fa-bounce");
    } else {
        autorizarBellIcon.removeClass("fa-bounce");
    }

    // Crear contenedor de cuadrícula
    const gridContainer = $('<div class="grid-container"></div>');

    // Agrupar áreas por estado
    const areasPendientes = response.filter(t => t.EstadoTurno === 0);
    const areasAutorizadas = response.filter(t => t.EstadoTurno === 1);
    const areasRechazadas = response.filter(t => t.EstadoTurno === 2);
    const areasSinTurnos = response.filter(t => t.EstadoTurno === null);

    // Función para crear tarjetas de área
    const createAreaCard = (turno, status) => {
        const statusClass = getStatusClass(status);
        const statusText = getStatusText(status);
        const mesText = turno.Mes ? turno.Mes : `Sin mes especificado`;

        let buttons = '';
        

        if (status === 'pending') {
            if (rol == 1) {
                buttons = `
                <div class="card-buttons">
                    <button class="btn-autorizar" data-id="${turno.IdArea}"  data-name="${turno.NombreArea}" data-mes="${turno.NumeroMes}" data-nommes="${turno.Mes}" data-idusuario="${IdUsuario}">
                        <i class="material-icons">check_circle</i> Autorizar
                    </button>
                    <button class="btn-rechazar" data-id="${turno.IdArea}" data-name="${turno.NombreArea}" data-mes="${turno.NumeroMes}" data-nommes="${turno.Mes}" data-idusuario="${IdUsuario}"">
                        <i class="material-icons">cancel</i> Rechazar
                    </button>
                </div>
                `;
            }
        } else {
            buttons = `
            <div class="card-status">
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            `;
        }

        return `
        <div class="card ${statusClass}">
            <div class="card-header-container">
                <div class="card-header">
                    <h3 class="area-name">${turno.NombreArea}</h3>
                    <span class="month-badge">${mesText}</span>
                </div>
            </div>
            <div class="card-body">
                ${buttons}
            </div>
        </div>
        `;
    };

    // Agregar sección de áreas pendientes si existen
    if (areasPendientes.length > 0) {
        gridContainer.append('<h2 class="section-title pending"><i class="material-icons">pending_actions</i> Pendientes por Autorizar</h2>');
        areasPendientes.forEach(turno => {
            gridContainer.append(createAreaCard(turno, 'pending'));
        });
    }

    // Agregar sección de áreas autorizadas si existen
    if (areasAutorizadas.length > 0) {
        gridContainer.append('<h2 class="section-title approved"><i class="material-icons">verified</i> Autorizadas</h2>');
        areasAutorizadas.forEach(turno => {
            gridContainer.append(createAreaCard(turno, 'approved'));
        });
    }

    // Agregar sección de áreas rechazadas si existen
    if (areasRechazadas.length > 0) {
        gridContainer.append('<h2 class="section-title rejected"><i class="material-icons">warning</i> Rechazadas</h2>');
        areasRechazadas.forEach(turno => {
            gridContainer.append(createAreaCard(turno, 'rejected'));
        });
    }

    // Agregar sección de áreas sin turnos si existen
    if (areasSinTurnos.length > 0) {
        gridContainer.append('<h2 class="section-title no-data"><i class="material-icons">info</i> Sin Turnos Cargados</h2>');
        areasSinTurnos.forEach(turno => {
            gridContainer.append(createAreaCard(turno, 'no-data'));
        });
    }

    // --- Swap atómico ---
    // Construimos todo el grid en memoria y lo insertamos de una sola vez.
    // Sin empty() previo => el navegador no muestra el contenedor vacío.
    const esActualizacion = primeraCargaHecha && silencioso;
    if (esActualizacion) {
        // Actualización sutil: fade muy corto solo si de verdad cambiaron los datos
        gridContainer.addClass('grid-actualizada');
    }
    $("#turnosInfo").html(gridContainer);
    primeraCargaHecha = true;

    // Los botones ya no se re-enlazan aquí: usan delegación de eventos (ver initButtonEvents)
}

// ============================================================
//  FUNCIONES AUXILIARES
// ============================================================
function getMonthName(monthNumber) {
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                   'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return months[monthNumber - 1] || '';
}

function getStatusClass(status) {
    switch(status) {
        case 'pending': return 'status-pending';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        default: return 'status-no-data';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'pending': return 'Pendiente';
        case 'approved': return 'Autorizado';
        case 'rejected': return 'Rechazado';
        default: return 'Sin turnos';
    }
}

// ============================================================
//  VALIDACIÓN DE CRUCES DE AGENDA
// ============================================================

// Consulta el endpoint de cruces. Devuelve una Promise con el array de cruces (vacío = sin cruces).
function validarCruceArea(idArea, month, year) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '../Controlador/ControladorTurno.php?Accion=ValidarPorCruce',
            type: 'GET',
            data: { IdArea: idArea, month: month, year: year },
            dataType: 'json',
            timeout: 20000,
            success: function (response) {
                if (response && response.success) {
                    resolve(Array.isArray(response.data) ? response.data : []);
                } else {
                    reject(response && response.message ? response.message : 'Respuesta inválida del validador de cruces.');
                }
            },
            error: function (xhr, status, error) {
                reject(error || status || 'Error de conexión al validar cruces.');
            }
        });
    });
}

// Helpers de fecha para el modal ("2026-07-01 07:00:00")
function formatearFechaCorta(fechaStr) {
    if (!fechaStr) return '';
    const fecha = fechaStr.split(' ')[0];
    const partes = fecha.split('-');
    if (partes.length < 3) return fechaStr;
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatearHora(fechaStr) {
    if (!fechaStr) return '';
    const partes = fechaStr.split(' ');
    if (partes.length < 2) return '';
    return partes[1].substring(0, 5); // HH:MM
}

// Escapar texto que viene de la BD antes de inyectarlo como HTML
function escaparHtml(texto) {
    if (texto === null || texto === undefined) return '';
    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Muestra el modal bloqueante con el detalle de los cruces encontrados
function mostrarModalCruces(nombreArea, nommes, cruces) {
    // Agrupar cruces por médico (usa el ID como clave, el nombre para mostrar)
    const porMedico = {};
    cruces.forEach(c => {
        const key = c.IdMedico;
        if (!porMedico[key]) {
            porMedico[key] = {
                nombre: c.NombreMedico || `Médico ID ${c.IdMedico}`,
                items: []
            };
        }
        porMedico[key].items.push(c);
    });

    // Nombre del área origen: preferir el del JSON, si no el de la tarjeta
    const areaOrigen = (cruces[0] && cruces[0].NombreAreaOrigen) ? cruces[0].NombreAreaOrigen : nombreArea;

    // Listado único de áreas con las que cruza
    const areasCruce = [...new Set(cruces.map(c => c.NombreAreaCruce || `Área ${c.AreaCruce}`))];

    let html = `<div class="cruces-wrapper">`;
    html += `<p class="cruces-intro">
                La agenda de <strong>${escaparHtml(areaOrigen)}</strong> (${escaparHtml(nommes)}) tiene
                <strong>${cruces.length}</strong> cruce(s) de horario en
                <strong>${Object.keys(porMedico).length}</strong> médico(s),
                con: <strong>${escaparHtml(areasCruce.join(', '))}</strong>.
                <br>Corrige los cruces antes de autorizar.
             </p>`;

    Object.keys(porMedico).forEach(idMedico => {
        const grupo = porMedico[idMedico];
        html += `<div class="cruce-medico">`;
        html += `<div class="cruce-medico-titulo">
                    <i class="material-icons">person</i>
                    <span>${escaparHtml(grupo.nombre)}</span>
                    <span class="cruce-medico-id">ID ${escaparHtml(idMedico)}</span>
                 </div>`;
        html += `<table class="cruce-tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Turno en ${escaparHtml(areaOrigen)}</th>
                            <th>Cruza con</th>
                        </tr>
                    </thead>
                    <tbody>`;
        grupo.items.forEach(c => {
            const fecha = formatearFechaCorta(c.InicioArea);
            const horarioArea = `${formatearHora(c.InicioArea)} - ${formatearHora(c.FinArea)}`;
            const horarioCruce = `${formatearHora(c.InicioCruce)} - ${formatearHora(c.FinCruce)}`;
            const nombreCruce = c.NombreAreaCruce || `Área ${c.AreaCruce}`;
            html += `<tr>
                        <td>${fecha}</td>
                        <td>${horarioArea}</td>
                        <td>
                            <span class="cruce-area-nombre">${escaparHtml(nombreCruce)}</span>
                            <span class="cruce-area-horario">${horarioCruce}</span>
                        </td>
                     </tr>`;
        });
        html += `</tbody></table></div>`;
    });

    html += `</div>`;

    Swal.fire({
        title: 'No se puede autorizar',
        html: html,
        icon: 'error',
        width: '720px',
        confirmButtonText: 'Entendido',
        customClass: { popup: 'swal2-dark' }
    });
}

// ============================================================
//  EVENTOS DE BOTONES (delegados: se enlazan UNA sola vez)
// ============================================================
function setButtonsState(disabled) {
    $(".btn-autorizar, .btn-rechazar").prop('disabled', disabled);
    if (disabled) {
        $(".btn-autorizar, .btn-rechazar").addClass('btn-disabled');
    } else {
        $(".btn-autorizar, .btn-rechazar").removeClass('btn-disabled');
    }
}

function liberarAccion() {
    procesandoAccion = false;
    setButtonsState(false);
}

function initButtonEvents() {
    // ---------------- AUTORIZAR (con validación de cruces) ----------------
    $(document).on("click", ".btn-autorizar", function () {
        if (procesandoAccion) return;
        procesandoAccion = true;
        setButtonsState(true);

        const idArea = $(this).data("id");
        const IdUsuario = $(this).data("idusuario");
        const NombreArea = $(this).data("name");
        const mes = $(this).data("mes");          // NumeroMes
        const nommes = $(this).data("nommes");
        const year = $('#yearSelect').val();
        const nombreCard = $(this).closest('.card').find('h3').text();

        // 1) Validar cruces ANTES de cualquier otra cosa
        Swal.fire({
            title: 'Validando cruces...',
            html: 'Verificando que la agenda no se cruce con otras áreas.<br>Por favor espera...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading(),
            customClass: { popup: 'swal2-dark' }
        });

        validarCruceArea(idArea, mes, year)
            .then((cruces) => {
                // 2) Si hay cruces -> bloquear y mostrar detalle
                if (cruces && cruces.length > 0) {
                    mostrarModalCruces(nombreCard, nommes, cruces);
                    liberarAccion();
                    return;
                }

                // 3) Sin cruces -> confirmar y continuar con el flujo normal
                Swal.fire({
                    title: '¿Autorizar turnos?',
                    text: `¿Estás seguro de autorizar los turnos de ${nombreCard} para ${nommes}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, autorizar',
                    cancelButtonText: 'Cancelar',
                    customClass: { popup: 'swal2-dark' }
                }).then((result) => {
                    if (result.isConfirmed) {
                 
                        Autorizar("Autorizar", idArea, NombreArea, mes, nommes, liberarAccion, IdUsuario);
                    } else {
                        liberarAccion();
                    }
                });
            })
            .catch((err) => {
                console.error('Error validando cruces:', err);
                Swal.fire({
                    title: 'Error de validación',
                    html: 'No se pudo validar los cruces de agenda. Intenta nuevamente.<br><small>' + escaparHtml(err) + '</small>',
                    icon: 'error',
                    customClass: { popup: 'swal2-dark' }
                });
                liberarAccion();
            });
    });

    // ---------------- RECHAZAR ----------------
    $(document).on("click", ".btn-rechazar", function () {
        if (procesandoAccion) return;
        procesandoAccion = true;
        setButtonsState(true);

        const idArea = $(this).data("id");
        const NombreArea = $(this).data("name");
        const mes = $(this).data("mes");
        const nommes = $(this).data("nommes");

        Swal.fire({
            title: '¿Rechazar turnos?',
            text: `¿Estás seguro de rechazar los turnos de ${$(this).closest('.card').find('h3').text()} para ${nommes}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, rechazar',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'swal2-dark' }
        }).then((result) => {
            if (result.isConfirmed) {
                Autorizar("Rechazar", idArea, NombreArea, mes, nommes, liberarAccion, IdUsuario);
            } else {
                liberarAccion();
            }
        });
    });

    // ---------------- REINTENTAR CARGA ----------------
    $(document).on("click", "#btnReintentarCarga", function () {
        firmaAgendasActual = null;
        ListarPorAutorizacion({ silencioso: false });
    });
}

// ============================================================
//  CONTROLES DE FECHA
// ============================================================
function initDateControls() {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1;
    const currentYear = currentDate.getFullYear();

    // Cargar del localStorage o usar valores actuales
    let initialMonth = localStorage.getItem('selectedMonth') || currentMonth;
    let initialYear = localStorage.getItem('selectedYear') || currentYear;

    // Asegurar que sean números
    initialMonth = parseInt(initialMonth);
    initialYear = parseInt(initialYear);

    // Llenar select de meses
    const monthSelect = $('#monthSelect');
    monthSelect.empty();
    [
        {value: 1, name: 'Enero'}, {value: 2, name: 'Febrero'},
        {value: 3, name: 'Marzo'}, {value: 4, name: 'Abril'},
        {value: 5, name: 'Mayo'}, {value: 6, name: 'Junio'},
        {value: 7, name: 'Julio'}, {value: 8, name: 'Agosto'},
        {value: 9, name: 'Septiembre'}, {value: 10, name: 'Octubre'},
        {value: 11, name: 'Noviembre'}, {value: 12, name: 'Diciembre'}
    ].forEach(month => {
        monthSelect.append(`<option value="${month.value}" ${initialMonth === month.value ? 'selected' : ''}>${month.name}</option>`);
    });

    // Llenar select de años
    const yearSelect = $('#yearSelect');
    yearSelect.empty();
    for (let year = currentYear - 5; year <= currentYear + 5; year++) {
        yearSelect.append(`<option value="${year}" ${initialYear === year ? 'selected' : ''}>${year}</option>`);
    }

    // Guardar los valores iniciales en localStorage por si acaso
    localStorage.setItem('selectedMonth', initialMonth);
    localStorage.setItem('selectedYear', initialYear);

    // Manejar cambio de fecha
    $('.date-selector').change(function() {
        const month = $('#monthSelect').val();
        const year = $('#yearSelect').val();

        localStorage.setItem('selectedMonth', month);
        localStorage.setItem('selectedYear', year);

        // Cambio de mes: SÍ mostramos el indicador de carga (no es silencioso)
        // e invalidamos la firma porque el contenido va a ser distinto.
        firmaAgendasActual = null;
        ListarPorAutorizacion({ silencioso: false });
    });

    return {
        month: initialMonth,
        year: initialYear
    };
}

// ============================================================
//  AUTORIZAR / RECHAZAR (envío de correos + proceso)
// ============================================================
// ============================================================
//  AUTORIZAR / RECHAZAR (envío de correos + proceso)
// ============================================================
function Autorizar(accion, id, NombreArea, mes, nommes, callback, IdUsuario) {
    // Mostrar loading inmediato
    Swal.fire({
        title: 'Enviando correos...',
        html: 'El proceso puede tomar varios minutos.<br>Por favor espera...',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: { popup: 'swal2-dark' },
        willOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorEmail.php?Accion=' + accion,
        data: {
            IdArea: id,
            Mes: nommes,
            IdUser: IdUsuario
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const processId = response.process_id;
                const statusUrl = response.status_url;

                function checkStatus() {
                    $.ajax({
                        url: statusUrl,
                        type: 'GET',
                        dataType: 'json',
                        success: function(statusResponse) {
                            if (statusResponse.status === 'completed') {
                                Swal.fire({
                                    title: "¡Correos Enviados!",
                                    html: `${statusResponse.message}<br><br>✅ Enviados: ${statusResponse.sent_emails}<br>❌ Fallidos: ${statusResponse.failed_emails}`,
                                    icon: "success",
                                    customClass: { popup: 'swal2-dark' }
                                }).then(() => {
                                    // Continuar con la autorización y luego recargar
                                    continuarConAutorizacion(accion, id, NombreArea, mes, nommes, callback, IdUsuario);
                                });

                            } else if (statusResponse.status === 'failed') {
                                Swal.fire({
                                    title: "Error en envío de correos",
                                    html: statusResponse.message +
                                        (statusResponse.errors && statusResponse.errors.length ?
                                        "<br><br>Errores:<br>" + statusResponse.errors.join("<br>") : ""),
                                    icon: "error",
                                    customClass: { popup: 'swal2-dark' }
                                }).then(() => {
                                    if (typeof callback === 'function') callback();
                                });

                            } else if (statusResponse.status === 'processing') {
                                const porcentaje = Math.round((statusResponse.sent_emails / statusResponse.total_emails) * 100);
                                Swal.update({
                                    title: `Enviando correos... (${statusResponse.sent_emails}/${statusResponse.total_emails})`,
                                    html: `Progreso: ${porcentaje}%<br>Lote actual: ${statusResponse.current_batch}<br>Por favor espera...`,
                                });
                                setTimeout(checkStatus, 3000);
                            } else if (statusResponse.status === 'not_found') {
                                setTimeout(checkStatus, 2000);
                            }
                        },
                        error: function() {
                            setTimeout(checkStatus, 3000);
                        }
                    });
                }

                setTimeout(checkStatus, 2000);

            } else {
                Swal.fire({
                    title: "Error en envío de correos",
                    html: response.message,
                    icon: "error",
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    if (typeof callback === 'function') callback();
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en ControladorEmail:', xhr.responseText, status, error);
            Swal.fire({
                title: "Error",
                text: "Error al comunicarse con el servidor para enviar correos: " + error,
                icon: "error",
                customClass: { popup: 'swal2-dark' }
            }).then(() => {
                if (typeof callback === 'function') callback();
            });
        }
    });
}

// Función separada para continuar con el proceso después de enviar correos
function continuarConAutorizacion(accion, id, NombreArea, mes, nommes, callback, IdUsuario) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorTurno.php?Accion=' + accion,
        data: {
            IdArea: id,
            Mes: mes,
            IdUser: IdUsuario
        },
        success: function (msg) {
            if (msg) {
                let mensajeNotificacion;
                let PrioridadNotificacion;
                let TipoNotificacion;

                switch (accion.toLowerCase()) {
                    case 'autorizar':
                        mensajeNotificacion = `Su agenda del mes de ${nommes} cargada ha sido autorizada.`;
                        PrioridadNotificacion = `media`;
                        TipoNotificacion = 5;
                        break;
                    case 'rechazar':
                        mensajeNotificacion = `Su agenda del mes de ${nommes} cargada ha sido rechazada.`;
                        PrioridadNotificacion = `alta`;
                        TipoNotificacion = 5;
                        break;
                    default:
                        mensajeNotificacion = `Se realizó una acción en turnos médicos.`;
                        PrioridadNotificacion = `media`;
                        TipoNotificacion = 5;
                }

                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorNotificaciones.php?Accion=IngresarLider',
                    dataType: 'json',
                    data: {
                        IdArea: id,
                        TipoNotificaciones: TipoNotificacion,
                        MensajeNotificaciones: mensajeNotificacion,
                        EnlaceNotificaciones: 'https://turnos.hrm.gov.co/Vista/FormularioTurnos.php?id='+id+'&nombreArea='+NombreArea,
                        PrioridadNotificaciones: PrioridadNotificacion,
                        FechaNotificaciones: formatearFechas(new Date()),
                    },
                    success: function(notifResponse) {
                        if (notifResponse.success) {
                            Swal.fire({
                                title: "¡Éxito!",
                                html: `Se enviaron ${notifResponse.insertados} notificaciones correctamente.`,
                                icon: "success",
                                customClass: { popup: 'swal2-dark' }
                            });
                        } else {
                            Swal.fire({
                                title: "¡Advertencia!",
                                html: `Solo se enviaron ${notifResponse.insertados} notificaciones. Fallaron ${notifResponse.errores}.`,
                                icon: "warning",
                                customClass: { popup: 'swal2-dark' }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en AJAX notificación:', xhr.responseText, status, error);
                        Swal.fire({
                            title: "Error",
                            html: "Hubo un problema al enviar la notificación.",
                            icon: "error",
                            customClass: { popup: 'swal2-dark' }
                        });
                    }
                }).always(() => {
                    // PRIMERO llamar al callback para liberar la UI
                    if (typeof callback === 'function') callback();
                    
                    // LUEGO, después de un pequeño delay para asegurar que el servidor procesó, recargar
                    setTimeout(() => {
                        // Invalidar la firma para forzar el repintado
                        firmaAgendasActual = null;
                        // Usar { silencioso: false } para mostrar el skeleton y asegurar que se actualiza
                        ListarPorAutorizacion({ silencioso: false });
                    }, 500); // Esperar medio segundo para que el servidor procese
                });
            }
        },
        error: function (req, textStatus, errorThrown) {
            console.error('Error en ControladorTurno:', textStatus, errorThrown);
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown);
            if (typeof callback === 'function') callback();
        }
    });
}

function formatearFechas(fecha) {
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    const horas = String(fecha.getHours()).padStart(2, '0');
    const minutos = String(fecha.getMinutes()).padStart(2, '0');
    const segundos = String(fecha.getSeconds()).padStart(2, '0');

    return `${año}-${mes}-${dia} ${horas}:${minutos}:${segundos}`;
}

// ============================================================
//  INICIO
// ============================================================
$(document).ready(function() {
    // Insertar controles de fecha
    $('.turnos').before(`
    <div class="date-controls">
        <div class="control-group">
            <label for="monthSelect"><i class="material-icons">date_range</i> Mes:</label>
            <select id="monthSelect" class="form-select date-selector"></select>
        </div>
        <div class="control-group">
            <label for="yearSelect"><i class="material-icons">calendar_today</i> Año:</label>
            <select id="yearSelect" class="form-select date-selector"></select>
        </div>
    </div>
    `);

    // Inicializar controles
    initDateControls();

    // Enlazar eventos de botones UNA sola vez (delegación)
    initButtonEvents();

    // Cargar datos iniciales
    ListarPorAutorizacion();

    // ---- Auto-refresh silencioso e inteligente ----
    setInterval(() => {
        // No refrescar si hay una carga en curso
        if (peticionListarEnCurso && peticionListarEnCurso.readyState !== 4) return;
        // No refrescar si hay un modal abierto o una acción en proceso
        if (procesandoAccion) return;
        if (typeof Swal !== 'undefined' && Swal.isVisible()) return;
        // No refrescar si la pestaña no está visible (ahorra peticiones)
        if (document.hidden) return;

        ListarPorAutorizacion({ silencioso: true });
    }, 30000);

    // Al volver a la pestaña, refrescar de inmediato (silencioso)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !procesandoAccion) {
            ListarPorAutorizacion({ silencioso: true });
        }
    });
});