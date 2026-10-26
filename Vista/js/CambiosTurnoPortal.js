/* Cambios de turno desde el portal del enfermero (sección «Cambios de turno» de Vista/PortalEnfermeria.php).
   Flujo: el enfermero elige un turno suyo y a un compañero del servicio -> el compañero elige qué turno suyo ofrece y acepta
   (o rechaza) -> quien solicitó la envía a coordinación -> coordinación aprueba o rechaza (con motivo). Cada enfermero tiene
   un cupo mensual. Datos de Controlador/ControladorCambioTurno.php (la persona sale de la sesión; el servidor revalida todo).
   PortalEnfermeria.js llama a CambiosPortal.html() al abrir la sección y a actualizarInsignia() al iniciar. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorCambioTurno.php';
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    const ESTADOS = {
        pendiente_companero: ['Esperando a tu compañero', 'espera'],
        aceptada_companero: ['Aceptada: falta enviarla a coordinación', 'accion'],
        pendiente_coordinacion: ['En coordinación', 'espera'],
        aprobada: ['Aprobada', 'ok'],
        rechazada_companero: ['Rechazada por tu compañero', 'no'],
        rechazada_coordinacion: ['Rechazada por coordinación', 'no'],
        cancelada: ['Cancelada', 'no'],
        vencida: ['Vencida', 'no'],
        revertida: ['Deshecha por coordinación', 'no']
    };
    const ACTIVOS = ['pendiente_companero', 'aceptada_companero', 'pendiente_coordinacion'];

    const C = { datos: null };

    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function hhmm(f) { return String(f || '').substring(11, 16) || String(f || '').substring(0, 5); }
    function fechaCorta(f) {
        const d = new Date(String(f).substring(0, 10) + 'T12:00:00');
        return isNaN(d) ? esc(f) : DIAS[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()].substring(0, 3);
    }
    function fechaLarga(f) {
        const d = new Date(String(f).substring(0, 10) + 'T12:00:00');
        return isNaN(d) ? esc(f) : d.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });
    }
    const COLOR = { D: '#f59e0b', N: '#6366f1', M: '#10b981', T: '#f97316' };
    function chip(codigo) {
        const c = COLOR[String(codigo).toUpperCase()] || '#0ea5e9';
        const n = parseInt(c.substring(1), 16);
        const luz = (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
        return '<span class="at-chip" style="--tc:' + c + ';--tx:' + (luz > 0.6 ? '#1f2937' : '#ffffff') + '">' + esc(codigo) + '</span>';
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
            throw new Error('El servidor devolvió una respuesta inesperada.');
        }
    }

    // ------------------------------------------------------------------ pintar

    function lineaTurno(codigo, fecha, ini, fin) {
        return chip(codigo) + ' <b>' + esc(fechaCorta(fecha)) + '</b>' + (ini ? ' · ' + esc(hhmm(ini)) + ' – ' + esc(hhmm(fin)) : '');
    }

    function tarjeta(s) {
        const [texto, clase] = ESTADOS[s.Estado] || [s.Estado, 'espera'];
        const yoSolicito = s.rol === 'solicitante';
        const otro = yoSolicito ? s.NombreCompanero : s.NombreSolicitante;
        const ofrece = lineaTurno(s.CodigoA, s.FechaTurnoSolicitante, s.HoraIniA, s.HoraFinA);
        const recibe = s.CodigoB ? lineaTurno(s.CodigoB, s.FechaTurnoCompanero, s.HoraIniB, s.HoraFinB) : '<i>sin elegir todavía</i>';
        let notas = '';
        if (s.NotaSolicitante) notas += '<p class="cp-nota">' + esc(s.NombreSolicitante) + ': «' + esc(s.NotaSolicitante) + '»</p>';
        if (s.NotaCompanero) notas += '<p class="cp-nota">' + esc(s.NombreCompanero) + ': «' + esc(s.NotaCompanero) + '»</p>';
        if (s.MotivoRechazo) notas += '<p class="cp-motivo"><b>Motivo:</b> ' + esc(s.MotivoRechazo) + '</p>';
        if (s.MotivoCancelacion && s.Estado !== 'cancelada') notas += '<p class="cp-nota">' + esc(s.MotivoCancelacion) + '</p>';
        if (s.Estado === 'cancelada' && s.MotivoCancelacion) notas += '<p class="cp-nota">' + esc(s.MotivoCancelacion) + '</p>';
        let acciones = '';
        if (s.puedeResponder) acciones += '<button type="button" class="btn btn-primary" data-cp-act="responder" data-id="' + s.IdSolicitud + '">Responder</button>';
        if (s.puedeEnviar) acciones += '<button type="button" class="btn btn-primary" data-cp-act="enviar" data-id="' + s.IdSolicitud + '">Enviar a coordinación</button>';
        if (s.puedeCancelar) acciones += '<button type="button" class="btn btn-outline" data-cp-act="cancelar" data-id="' + s.IdSolicitud + '">Cancelar solicitud</button>';
        return '<article class="cp-tarjeta" data-cp-id="' + s.IdSolicitud + '"><header><span class="cp-estado cp-' + clase + '">' + esc(texto) + '</span><small>#' + s.IdSolicitud + ' · ' + esc(s.NombreServicio) + '</small></header>' +
            '<p class="cp-con">' + (yoSolicito ? 'Tú ↔ <b>' + esc(otro) + '</b>' : '<b>' + esc(otro) + '</b> ↔ tú') + '</p>' +
            '<div class="cp-turnos"><div><small>' + (yoSolicito ? 'Tú ofreces' : esc(s.NombreSolicitante) + ' ofrece') + '</small>' + ofrece + '</div>' +
            '<div><small>' + (yoSolicito ? esc(s.NombreCompanero) + ' ofrece' : 'Tú ofreces') + '</small>' + recibe + '</div></div>' + notas +
            (acciones ? '<footer>' + acciones + '</footer>' : '') + '</article>';
    }

    function html(d) {
        const cupo = d.cupo;
        const mes = MESES[cupo.mes - 1];
        const porResponder = d.solicitudes.filter(s => s.puedeResponder);
        const enCurso = d.solicitudes.filter(s => ACTIVOS.indexOf(s.Estado) !== -1 && !s.puedeResponder);
        const historial = d.solicitudes.filter(s => ACTIVOS.indexOf(s.Estado) === -1);
        let h = '<div class="pt-seccion">';
        h += '<div class="pt-panel cp-cupo"><div><h3>Tus cambios de ' + esc(mes) + '</h3>' +
            '<p class="cp-cupo-num"><b>' + cupo.restante + '</b> de ' + cupo.total + ' disponibles</p>' +
            '<small>' + cupo.aprobadas + ' aprobados · ' + cupo.pendientes + ' en trámite' + (cupo.extra ? ' · ' + cupo.extra + ' adicionales otorgados' : '') +
            '. Un cambio gasta un cupo a cada uno de los dos enfermeros cuando coordinación lo aprueba; si lo rechaza, no se gasta.</small></div>' +
            (d.puede.solicitar ? '<button type="button" class="btn btn-primary" data-cp-act="solicitar"' + (cupo.restante <= 0 ? ' disabled title="Ya usaste tus cambios de este mes: pide a coordinación uno adicional"' : '') +
                '><i class="fas fa-exchange-alt"></i> Solicitar un cambio</button>' : '') + '</div>';
        if (porResponder.length) h += '<div class="pt-panel"><h3>Por responder <span class="cp-n">' + porResponder.length + '</span></h3>' + porResponder.map(tarjeta).join('') + '</div>';
        h += '<div class="pt-panel"><h3>En curso</h3>' + (enCurso.length ? enCurso.map(tarjeta).join('') : '<p class="pt-nota">No tienes solicitudes en trámite.</p>') + '</div>';
        h += '<div class="pt-panel"><h3>Historial</h3>' + (historial.length ? historial.map(tarjeta).join('') : '<p class="pt-nota">Aquí verás tus cambios anteriores.</p>') + '</div>';
        return h + '</div>';
    }

    async function cargar() {
        const r = await pedir('Resumen');
        if (!r.success) throw new Error(r.message || 'No se pudieron cargar los cambios de turno');
        C.datos = r;
        pintarInsignia(r.solicitudes);
        return r;
    }

    function pintarInsignia(solicitudes) {
        const n = document.getElementById('pt-cambios-n');
        if (!n) return;
        const pendientes = (solicitudes || []).filter(s => s.puedeResponder || s.puedeEnviar).length;
        n.hidden = pendientes === 0;
        n.textContent = String(pendientes);
    }

    async function pintar() {
        try {
            const d = await cargar();
            return html(d);
        } catch (err) {
            return '<div class="at-error"><p><i class="fas fa-exclamation-triangle"></i> ' + esc(err.message) + '</p></div>';
        }
    }

    // ------------------------------------------------------------------ flujos

    async function repintar() {
        const cuerpo = document.getElementById('pt-cuerpo');
        if (cuerpo && document.querySelector('[data-pt-ir="cambios"].active')) cuerpo.innerHTML = await pintar();
        else { try { await cargar(); } catch (e) { /* solo la insignia */ } }
        if (window.CampanaEnfermeria) window.CampanaEnfermeria.refrescar();
    }

    async function aviso(icono, titulo, texto) {
        return Swal.fire({ icon: icono, title: titulo, text: texto || '', timer: icono === 'success' ? 2200 : undefined, showConfirmButton: icono !== 'success' });
    }

    async function solicitar() {
        Swal.fire({ title: 'Buscando tus turnos…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let turnos;
        try {
            const r = await pedir('MisTurnos');
            if (!r.success) throw new Error(r.message);
            turnos = r.turnos;
        } catch (err) { await aviso('error', 'No se pudo consultar', err.message); return; }
        if (!turnos.length) {
            await aviso('info', 'No tienes turnos para cambiar', 'Solo se pueden cambiar turnos futuros de un cuadro confirmado que no estén ya en otra solicitud.');
            return;
        }
        const paso1 = await Swal.fire({
            title: '¿Qué turno quieres cambiar?', width: 560, showCancelButton: true, confirmButtonText: 'Siguiente', cancelButtonText: 'Cancelar', focusConfirm: false,
            html: '<div class="cp-lista">' + turnos.map(t => '<label class="cp-op"><input type="radio" name="cp-turno" value="' + t.IdTurnoEnfermeria + '">' +
                '<span>' + chip(t.Codigo) + ' <b>' + esc(fechaLarga(t.Fecha)) + '</b><small>' + esc(hhmm(t.FechaHoraInicio)) + ' – ' + esc(hhmm(t.FechaHoraFin)) + ' · ' + esc(t.NombreServicio) + '</small></span></label>').join('') + '</div>',
            preConfirm: () => { const s = document.querySelector('input[name="cp-turno"]:checked'); if (!s) { Swal.showValidationMessage('Elige un turno'); return false; } return s.value; }
        });
        if (!paso1.isConfirmed) return;
        const idTurno = paso1.value;
        const turno = turnos.find(t => String(t.IdTurnoEnfermeria) === String(idTurno));

        Swal.fire({ title: 'Buscando compañeros…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let companeros;
        try {
            const r = await pedir('Companeros', { IdTurno: idTurno });
            if (!r.success) throw new Error(r.message);
            companeros = r.companeros;
        } catch (err) { await aviso('error', 'No se pudo consultar', err.message); return; }
        if (!companeros.length) { await aviso('info', 'Sin compañeros disponibles', 'No hay otros enfermeros del servicio o de apoyo para cambiar este turno.'); return; }
        const paso2 = await Swal.fire({
            title: '¿Con quién quieres cambiar?', width: 560, showCancelButton: true, confirmButtonText: 'Siguiente', cancelButtonText: 'Cancelar', focusConfirm: false,
            html: '<p class="cp-ctx">Solo con enfermeros de <b>' + esc(turno.NombreServicio) + '</b> o que lo apoyan.</p><div class="cp-lista">' + companeros.map(c => '<label class="cp-op"><input type="radio" name="cp-comp" value="' + c.IdMedico + '">' +
                '<span><b>' + esc(c.NombreMedico) + '</b><small>' + esc(c.Perfil || '') + '</small></span></label>').join('') + '</div>',
            preConfirm: () => { const s = document.querySelector('input[name="cp-comp"]:checked'); if (!s) { Swal.showValidationMessage('Elige a un compañero'); return false; } return s.value; }
        });
        if (!paso2.isConfirmed) return;
        const comp = companeros.find(c => String(c.IdMedico) === String(paso2.value));

        const paso3 = await Swal.fire({
            title: 'Revisa y envía', width: 520, showCancelButton: true, confirmButtonText: 'Enviar solicitud', cancelButtonText: 'Cancelar',
            html: '<div class="cp-resumen">Le propones a <b>' + esc(comp.NombreMedico) + '</b> cambiar tu turno ' + chip(turno.Codigo) + ' del <b>' + esc(fechaLarga(turno.Fecha)) +
                '</b>. Él o ella elegirá cuál de sus turnos te ofrece; después tú la envías a coordinación para que la apruebe.</div>',
            input: 'textarea', inputPlaceholder: 'Nota para tu compañero (opcional)', inputAttributes: { maxlength: 255 }
        });
        if (!paso3.isConfirmed) return;
        Swal.fire({ title: 'Enviando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const r = await pedir('Crear', { IdTurno: idTurno, IdMedicoCompanero: comp.IdMedico, Nota: (paso3.value || '').trim() }, true);
            if (!r.success) throw new Error(r.message);
            await aviso('success', 'Solicitud enviada', r.message);
        } catch (err) { await aviso('error', 'No se pudo enviar', err.message); }
        repintar();
    }

    async function responder(id) {
        Swal.fire({ title: 'Cargando tus turnos…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let opciones;
        const s = (C.datos ? C.datos.solicitudes : []).find(x => String(x.IdSolicitud) === String(id));
        try {
            const r = await pedir('OpcionesCompanero', { IdSolicitud: id });
            if (!r.success) throw new Error(r.message);
            opciones = r.opciones;
        } catch (err) { await aviso('error', 'No se pudo consultar', err.message); return; }
        const res = await Swal.fire({
            title: 'Cambio de turno solicitado', width: 580, showCancelButton: true, showDenyButton: true, focusConfirm: false,
            confirmButtonText: 'Aceptar y ofrecer este turno', denyButtonText: 'Rechazar', cancelButtonText: 'Cerrar',
            html: '<div class="cp-resumen">' + (s ? '<b>' + esc(s.NombreSolicitante) + '</b> te propone su turno ' + chip(s.CodigoA) + ' del <b>' + esc(fechaLarga(s.FechaTurnoSolicitante)) + '</b>.<br>' : '') +
                'Elige cuál de tus turnos le ofreces a cambio:</div><div class="cp-lista">' +
                (opciones.length ? opciones.map(o => '<label class="cp-op' + (o.viable ? '' : ' cp-no') + '"><input type="radio" name="cp-mio" value="' + o.IdTurnoEnfermeria + '"' + (o.viable ? '' : ' disabled') + '>' +
                    '<span>' + chip(o.Codigo) + ' <b>' + esc(fechaLarga(o.Fecha)) + '</b><small>' + esc(hhmm(o.FechaHoraInicio)) + ' – ' + esc(hhmm(o.FechaHoraFin)) + '</small>' +
                    (o.viable ? '' : '<small class="cp-razon">' + esc(o.razon) + '</small>') + '</span></label>').join('') : '<p class="pt-nota">No tienes turnos de este servicio para ofrecer.</p>') + '</div>' +
                '<textarea id="cp-nota" class="swal2-textarea" maxlength="255" placeholder="Nota (opcional)"></textarea>',
            preConfirm: () => { const sel = document.querySelector('input[name="cp-mio"]:checked'); if (!sel) { Swal.showValidationMessage('Elige el turno que ofreces'); return false; } return { turno: sel.value, nota: (document.getElementById('cp-nota').value || '').trim() }; },
            preDeny: () => (document.getElementById('cp-nota').value || '').trim()
        });
        if (res.isConfirmed) {
            try {
                const r = await pedir('Responder', { IdSolicitud: id, Acepta: '1', IdTurno: res.value.turno, Nota: res.value.nota }, true);
                if (!r.success) throw new Error(r.message);
                await aviso('success', 'Aceptaste el cambio', r.message);
            } catch (err) { await aviso('error', 'No se pudo aceptar', err.message); }
        } else if (res.isDenied) {
            try {
                const r = await pedir('Responder', { IdSolicitud: id, Acepta: '0', Nota: res.value || '' }, true);
                if (!r.success) throw new Error(r.message);
                await aviso('success', 'Rechazaste la solicitud', '');
            } catch (err) { await aviso('error', 'No se pudo rechazar', err.message); }
        } else {
            return;
        }
        repintar();
    }

    async function accionSimple(accion, id, titulo, texto, boton) {
        const ok = await Swal.fire({ title: titulo, text: texto, icon: 'question', showCancelButton: true, confirmButtonText: boton, cancelButtonText: 'Volver' });
        if (!ok.isConfirmed) return;
        try {
            const r = await pedir(accion, { IdSolicitud: id }, true);
            if (!r.success) throw new Error(r.message);
            await aviso('success', r.message, '');
        } catch (err) { await aviso('error', 'No se pudo completar', err.message); }
        repintar();
    }

    document.addEventListener('click', function (ev) {
        const el = ev.target.closest('[data-cp-act]');
        if (!el) return;
        switch (el.dataset.cpAct) {
            case 'solicitar': solicitar(); break;
            case 'responder': responder(el.dataset.id); break;
            case 'enviar': accionSimple('EnviarACoordinacion', el.dataset.id, '¿Enviar a coordinación?', 'Coordinación aprobará o rechazará el cambio. Si lo aprueba, ustedes dos intercambian los turnos; el pago de cada turno no cambia.', 'Enviar'); break;
            case 'cancelar': accionSimple('Cancelar', el.dataset.id, '¿Cancelar la solicitud?', 'Se libera tu cupo y se avisa a tu compañero.', 'Cancelar solicitud'); break;
        }
    });

    window.CambiosPortal = {
        html: pintar,
        actualizarInsignia: function () { cargar().catch(() => {}); },
        resaltar: function (id) {
            const t = document.querySelector('[data-cp-id="' + id + '"]');
            if (t) { t.classList.add('cp-resaltada'); t.scrollIntoView({ block: 'center' }); }
        }
    };
})();
