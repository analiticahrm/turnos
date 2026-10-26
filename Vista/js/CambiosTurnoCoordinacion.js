/* Cambios de turno en la consola de coordinación (sección #cambios-content de Vista/CoordinacionEnfermeria.php).
   «Solicitudes»: la bandeja de cambios entre enfermeros, para aprobar, rechazar (con motivo) o deshacer.
   «Cupos»: cuántos cambios lleva cada enfermero este mes y, solo para el coordinador de toda enfermería, otorgar cupos extra.
   Datos de Controlador/ControladorCambioTurnoCoordinacion.php, que filtra por los servicios del usuario y valida cada acción. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorCambioTurnoCoordinacion.php';
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    const ESTADOS = {
        pendiente_companero: ['Esperando al compañero', 'espera'], aceptada_companero: ['Aceptada por el compañero', 'espera'],
        pendiente_coordinacion: ['Por aprobar', 'accion'], aprobada: ['Aprobada', 'ok'], rechazada_companero: ['Rechazada por el compañero', 'no'],
        rechazada_coordinacion: ['Rechazada', 'no'], cancelada: ['Cancelada', 'no'], vencida: ['Vencida', 'no'], revertida: ['Deshecha', 'no']
    };

    const K = { tab: 'solicitudes', filtro: 'por_aprobar', anio: 0, mes: 0, lista: [], puede: {}, token: 0 };

    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function $(id) { return document.getElementById(id); }
    function hhmm(f) { return String(f || '').substring(11, 16); }
    function fechaCorta(f) {
        const d = new Date(String(f).substring(0, 10) + 'T12:00:00');
        return isNaN(d) ? esc(f) : DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()].substring(0, 3);
    }

    async function pedir(accion, datos, post) {
        let url = API + '?Accion=' + encodeURIComponent(accion);
        const opts = { credentials: 'same-origin' };
        if (post) { opts.method = 'POST'; opts.body = new URLSearchParams(datos || {}); }
        else if (datos) Object.keys(datos).forEach(k => { url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(datos[k]); });
        const resp = await fetch(url, opts);
        const texto = await resp.text();
        try { return JSON.parse(texto); } catch (e) {
            console.error('Respuesta no válida de ' + url, texto);
            throw new Error('El servidor devolvió una respuesta inesperada. El detalle está en la consola (F12).');
        }
    }

    function estructura() {
        $('cambios-raiz').innerHTML = '<div class="pe-tabs" role="tablist">' +
            '<button type="button" class="pe-tab" data-cc-tab="solicitudes">Solicitudes</button>' +
            '<button type="button" class="pe-tab" data-cc-tab="cupos">Cupos del mes</button></div><div id="cc-panel"></div>';
    }

    function marcarTab() {
        document.querySelectorAll('#cambios-raiz .pe-tab').forEach(b => b.classList.toggle('activa', b.dataset.ccTab === K.tab));
    }

    // ------------------------------------------------------------------ solicitudes

    async function cargarSolicitudes() {
        const panel = $('cc-panel');
        const token = ++K.token;
        panel.innerHTML = '<p class="vacio">Cargando…</p>';
        try {
            const r = await pedir('Bandeja', { Filtro: K.filtro });
            if (token !== K.token) return;
            if (!r.success) throw new Error(r.message);
            K.lista = r.solicitudes;
            K.puede = r.puede;
            pintarSolicitudes();
        } catch (err) {
            if (token === K.token) panel.innerHTML = '<p class="vacio">' + esc(err.message) + '</p>';
        }
    }

    function cupoTexto(c) {
        return '<span class="cc-cupo' + (c.restante <= 0 ? ' cc-agotado' : '') + '">' + c.aprobadas + '/' + c.total + ' usados' + (c.pendientes ? ' (+' + c.pendientes + ' en trámite)' : '') + '</span>';
    }

    function pintarSolicitudes() {
        const opciones = [['por_aprobar', 'Por aprobar'], ['en_tramite', 'En trámite'], ['aprobada', 'Aprobadas'], ['rechazada_coordinacion', 'Rechazadas por coordinación'],
            ['rechazada_companero', 'Rechazadas por el compañero'], ['cancelada', 'Canceladas'], ['vencida', 'Vencidas'], ['revertida', 'Deshechas'], ['todas', 'Todas']];
        let h = '<div class="cc-filtros"><div class="form-group"><label for="cc-filtro">Mostrar</label><select id="cc-filtro" class="form-control">' +
            opciones.map(o => '<option value="' + o[0] + '"' + (K.filtro === o[0] ? ' selected' : '') + '>' + o[1] + '</option>').join('') + '</select></div>' +
            '<button type="button" class="btn btn-outline" data-cc-act="recargar"><i class="fas fa-sync-alt"></i> Actualizar</button></div>';
        if (!K.lista.length) {
            $('cc-panel').innerHTML = h + '<p class="vacio">No hay solicitudes con este filtro.</p>';
            return;
        }
        h += '<div class="table-responsive"><table class="cc-tabla"><thead><tr><th>#</th><th>Servicio</th><th>Quién cambia con quién</th><th>Turnos</th><th>Estado</th><th>Cupos del mes</th><th></th></tr></thead><tbody>';
        K.lista.forEach(s => {
            const [txt, clase] = ESTADOS[s.Estado] || [s.Estado, 'espera'];
            const turnoB = s.CodigoB ? esc(s.CodigoB) + ' ' + esc(fechaCorta(s.FechaTurnoCompanero)) : '—';
            let acc = '<button type="button" data-cc-act="detalle" data-id="' + s.IdSolicitud + '">Ver</button>';
            if (s.Estado === 'pendiente_coordinacion' && K.puede.resolver) {
                acc += '<button type="button" class="cc-ok" data-cc-act="aprobar" data-id="' + s.IdSolicitud + '">Aprobar</button>' +
                    '<button type="button" class="cc-no" data-cc-act="rechazar" data-id="' + s.IdSolicitud + '">Rechazar</button>';
            }
            if (s.Estado === 'aprobada' && K.puede.revertir) acc += '<button type="button" data-cc-act="revertir" data-id="' + s.IdSolicitud + '">Deshacer</button>';
            h += '<tr data-cc-id="' + s.IdSolicitud + '"><td>' + s.IdSolicitud + '</td><td>' + esc(s.NombreServicio) + '</td>' +
                '<td><b>' + esc(s.NombreSolicitante) + '</b> ↔ <b>' + esc(s.NombreCompanero) + '</b><br><small>' + esc(fechaTexto(s.FechaSolicitud)) + '</small></td>' +
                '<td>' + esc(s.CodigoA) + ' ' + esc(fechaCorta(s.FechaTurnoSolicitante)) + ' ↔ ' + turnoB + '</td>' +
                '<td><span class="cp-estado cp-' + clase + '">' + esc(txt) + '</span>' + (s.MotivoRechazo ? '<br><small>' + esc(s.MotivoRechazo) + '</small>' : '') + '</td>' +
                '<td>' + cupoTexto(s.cupoSolicitante) + '<br>' + cupoTexto(s.cupoCompanero) + '</td><td><div class="cc-acciones">' + acc + '</div></td></tr>';
        });
        $('cc-panel').innerHTML = h + '</tbody></table></div>';
    }

    function fechaTexto(s) {
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? esc(s) : d.toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }

    async function detalle(id) {
        try {
            const r = await pedir('Detalle', { IdSolicitud: id });
            if (!r.success) throw new Error(r.message);
            const s = r.solicitud;
            const [txt] = ESTADOS[s.Estado] || [s.Estado];
            const tA = s.turnoSolicitante, tB = s.turnoCompanero;
            const bloque = (titulo, quien, t) => '<div><small>' + esc(titulo) + '</small><br><b>' + esc(quien) + '</b><br>' +
                (t ? esc(t.Codigo) + ' (' + esc(t.NombreTipo) + ')<br>' + esc(fechaCorta(t.Fecha)) + ' · ' + esc(hhmm(t.FechaHoraInicio)) + ' – ' + esc(hhmm(t.FechaHoraFin)) : '<i>aún sin elegir</i>') + '</div>';
            Swal.fire({
                title: 'Solicitud #' + s.IdSolicitud, width: 640, confirmButtonText: 'Cerrar',
                html: '<div class="cc-detalle"><p><b>' + esc(s.NombreServicio) + '</b> · ' + esc(txt) + '</p>' +
                    '<div class="cc-par">' + bloque('Ofrece', s.NombreSolicitante, tA) + bloque('Ofrece', s.NombreCompanero, tB) + '</div>' +
                    '<p>Cupos del mes: ' + esc(s.NombreSolicitante) + ' ' + cupoTexto(s.cupoSolicitante) + ' · ' + esc(s.NombreCompanero) + ' ' + cupoTexto(s.cupoCompanero) + '</p>' +
                    (s.NotaSolicitante ? '<p>Nota de ' + esc(s.NombreSolicitante) + ': «' + esc(s.NotaSolicitante) + '»</p>' : '') +
                    (s.NotaCompanero ? '<p>Nota de ' + esc(s.NombreCompanero) + ': «' + esc(s.NotaCompanero) + '»</p>' : '') +
                    (s.MotivoRechazo ? '<p><b>Motivo del rechazo:</b> ' + esc(s.MotivoRechazo) + '</p>' : '') +
                    (s.MotivoCancelacion ? '<p><b>Cancelación:</b> ' + esc(s.MotivoCancelacion) + '</p>' : '') +
                    (s.ResueltoPor ? '<p>Resolvió: ' + esc(s.ResueltoPor) + ' · ' + esc(fechaTexto(s.FechaResolucion)) + '</p>' : '') +
                    s.problemas.map(p => '<div class="cc-problema"><i class="fas fa-exclamation-triangle"></i> ' + esc(p) + '</div>').join('') +
                    '<p class="cp-nota">Si se aprueba, cada uno trabaja el turno del otro, pero <b>el pago de cada turno sigue siendo de su dueño original</b>.</p></div>'
            });
        } catch (err) { Swal.fire('No se pudo abrir', err.message, 'error'); }
    }

    async function ejecutar(accion, datos, titulo) {
        try {
            const r = await pedir(accion, datos, true);
            if (!r.success) throw new Error(r.message);
            await Swal.fire({ icon: 'success', title: titulo || r.message, timer: 1800, showConfirmButton: false });
        } catch (err) { await Swal.fire('No se pudo completar', err.message, 'error'); }
        cargarSolicitudes();
        if (window.CampanaEnfermeria) window.CampanaEnfermeria.refrescar();
    }

    async function aprobar(id) {
        const s = K.lista.find(x => String(x.IdSolicitud) === String(id));
        const ok = await Swal.fire({ title: '¿Aprobar el cambio?', icon: 'question', showCancelButton: true, confirmButtonText: 'Aprobar', cancelButtonText: 'Volver',
            html: s ? '<b>' + esc(s.NombreSolicitante) + '</b> trabajará el ' + esc(s.CodigoB) + ' del ' + esc(fechaCorta(s.FechaTurnoCompanero)) + ' y <b>' + esc(s.NombreCompanero) + '</b> el ' +
                esc(s.CodigoA) + ' del ' + esc(fechaCorta(s.FechaTurnoSolicitante)) + '.<br><small>El pago de cada turno no cambia. Gasta un cupo a cada uno.</small>' : '' });
        if (ok.isConfirmed) ejecutar('Aprobar', { IdSolicitud: id }, 'Cambio aprobado');
    }

    async function rechazar(id) {
        const r = await Swal.fire({ title: 'Rechazar el cambio', input: 'textarea', inputLabel: 'Motivo del rechazo (lo verán los dos enfermeros)', inputPlaceholder: 'Ejemplo: el servicio queda sin cobertura de noche ese día',
            showCancelButton: true, confirmButtonText: 'Rechazar', cancelButtonText: 'Volver', inputAttributes: { maxlength: 500 },
            inputValidator: v => (!v || v.trim().length < 5) ? 'Escribe el motivo (mínimo 5 caracteres)' : undefined });
        if (r.isConfirmed) ejecutar('Rechazar', { IdSolicitud: id, Motivo: r.value.trim() }, 'Cambio rechazado');
    }

    async function revertir(id) {
        const ok = await Swal.fire({ title: '¿Deshacer el cambio?', text: 'Cada turno vuelve a su dueño original y se libera el cupo de los dos.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Deshacer', cancelButtonText: 'Volver' });
        if (ok.isConfirmed) ejecutar('Revertir', { IdSolicitud: id }, 'Cambio deshecho');
    }

    // ------------------------------------------------------------------ cupos

    async function cargarCupos() {
        const panel = $('cc-panel');
        panel.innerHTML = '<p class="vacio">Cargando…</p>';
        try {
            const r = await pedir('Cupos', K.anio ? { Anio: K.anio, Mes: K.mes } : null);
            if (!r.success) throw new Error(r.message);
            K.anio = r.anio; K.mes = r.mes;
            let h = '<div class="cc-filtros"><button type="button" class="btn btn-outline" data-cc-act="mes-ant" aria-label="Mes anterior"><i class="fas fa-chevron-left"></i></button>' +
                '<strong>' + esc(MESES[r.mes - 1]) + ' de ' + r.anio + '</strong><button type="button" class="btn btn-outline" data-cc-act="mes-sig" aria-label="Mes siguiente"><i class="fas fa-chevron-right"></i></button></div>';
            if (!r.cupos.length) { panel.innerHTML = h + '<p class="vacio">No hay enfermeros en tus servicios.</p>'; return; }
            h += '<div class="table-responsive"><table class="cc-tabla"><thead><tr><th>Enfermero</th><th>Servicio</th><th>Aprobados</th><th>En trámite</th><th>Adicionales</th><th>Quedan</th><th></th></tr></thead><tbody>';
            r.cupos.forEach(c => {
                h += '<tr><td>' + esc(c.Nombre) + '</td><td>' + esc(c.Servicio || '—') + '</td><td>' + c.aprobadas + '</td><td>' + c.pendientes + '</td><td>' +
                    (r.puedeOtorgar
                        ? '<div class="cc-stepper"><button type="button" data-cc-act="ajustar" data-delta="-1" data-id="' + c.IdMedico + '" aria-label="Quitar un cambio adicional" title="Quitar un cambio adicional"' + (c.extra <= 0 || c.restante <= 0 ? ' disabled' : '') + '>&minus;</button>' +
                          '<b>' + c.extra + '</b><button type="button" data-cc-act="ajustar" data-delta="1" data-id="' + c.IdMedico + '" aria-label="Dar un cambio adicional" title="Dar un cambio adicional"' + (c.extra >= 10 ? ' disabled' : '') + '>+</button></div>'
                        : c.extra) + '</td>' +
                    '<td><b' + (c.restante <= 0 ? ' class="cc-cupo cc-agotado"' : '') + '>' + c.restante + '</b> de ' + c.total + '</td><td></td></tr>';
            });
            panel.innerHTML = h + '</tbody></table></div>';
        } catch (err) { panel.innerHTML = '<p class="vacio">' + esc(err.message) + '</p>'; }
    }

    // Botones − / +: un cambio adicional por clic, sin observación.
    async function ajustar(idMedico, delta, boton) {
        boton.disabled = true;
        try {
            const x = await pedir('AjustarCupo', { IdMedico: idMedico, Anio: K.anio, Mes: K.mes, Delta: delta }, true);
            if (!x.success) throw new Error(x.message);
        } catch (err) { await Swal.fire('No se pudo ajustar', err.message, 'error'); }
        const y = window.scrollY;
        await cargarCupos();
        window.scrollTo(0, y);
    }

    async function otorgar(idMedico, nombre) {
        const r = await Swal.fire({ title: 'Otorgar cambios adicionales', width: 480, showCancelButton: true, confirmButtonText: 'Otorgar', cancelButtonText: 'Cancelar', focusConfirm: false,
            html: '<div style="text-align:left"><p><b>' + esc(nombre) + '</b> · ' + esc(MESES[K.mes - 1]) + ' de ' + K.anio + '</p>' +
                '<label for="cc-cant">¿Cuántos cambios adicionales?</label><input id="cc-cant" type="number" class="swal2-input" min="1" max="10" value="1" style="margin:6px 0 10px">' +
                '<label for="cc-mot">Motivo (opcional)</label><textarea id="cc-mot" class="swal2-textarea" maxlength="255" style="margin:6px 0 0"></textarea></div>',
            preConfirm: () => {
                const n = parseInt(document.getElementById('cc-cant').value, 10);
                if (!(n >= 1 && n <= 10)) { Swal.showValidationMessage('La cantidad debe estar entre 1 y 10'); return false; }
                return { n: n, motivo: (document.getElementById('cc-mot').value || '').trim() };
            } });
        if (!r.isConfirmed) return;
        try {
            const x = await pedir('OtorgarCupo', { IdMedico: idMedico, Anio: K.anio, Mes: K.mes, Cantidad: r.value.n, Motivo: r.value.motivo }, true);
            if (!x.success) throw new Error(x.message);
            await Swal.fire({ icon: 'success', title: x.message, timer: 1800, showConfirmButton: false });
        } catch (err) { await Swal.fire('No se pudo otorgar', err.message, 'error'); }
        cargarCupos();
    }

    // ------------------------------------------------------------------ eventos

    function abrirTab(tab) {
        K.tab = tab;
        marcarTab();
        if (tab === 'cupos') cargarCupos(); else cargarSolicitudes();
    }

    document.addEventListener('click', function (ev) {
        if (!$('cambios-raiz') || !$('cambios-raiz').contains(ev.target)) return;
        const tab = ev.target.closest('[data-cc-tab]');
        if (tab) { abrirTab(tab.dataset.ccTab); return; }
        const el = ev.target.closest('[data-cc-act]');
        if (!el) return;
        switch (el.dataset.ccAct) {
            case 'recargar': cargarSolicitudes(); break;
            case 'detalle': detalle(el.dataset.id); break;
            case 'aprobar': aprobar(el.dataset.id); break;
            case 'rechazar': rechazar(el.dataset.id); break;
            case 'revertir': revertir(el.dataset.id); break;
            case 'otorgar': otorgar(el.dataset.id, el.dataset.nombre); break;
            case 'ajustar': ajustar(el.dataset.id, Number(el.dataset.delta), el); break;
            case 'mes-ant': K.mes -= 1; if (K.mes < 1) { K.mes = 12; K.anio -= 1; } cargarCupos(); break;
            case 'mes-sig': K.mes += 1; if (K.mes > 12) { K.mes = 1; K.anio += 1; } cargarCupos(); break;
        }
    });
    document.addEventListener('change', function (ev) {
        if (ev.target && ev.target.id === 'cc-filtro') { K.filtro = ev.target.value; cargarSolicitudes(); }
    });

    window.inicializarCambios = function () {
        if (!$('cambios-raiz')) return;
        estructura();
        // Un aviso de la campana (#cambios/12) abre la bandeja con todas las solicitudes para poder ver la que se menciona.
        if (/^#cambios\/\d+/.test(location.hash)) K.filtro = 'todas';
        abrirTab('solicitudes');
    };
})();
