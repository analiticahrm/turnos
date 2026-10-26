/* Barra del cuadro de turnos (Vista/CoordinacionEnfermeria.php, sección de Asignación de Turnos, #at-cuadro).
   Muestra si el cuadro del servicio y mes elegidos está en borrador o confirmado, permite confirmarlo (con el envío de
   correos a los enfermeros), continuar o reintentar el envío y reabrirlo con su motivo.
   Habla con Controlador/ControladorCuadroTurnos.php; el servidor vuelve a validar permisos y servicio en cada petición.
   AsignacionTurnos.js le avisa (CuadroTurnosUI.refrescar) cada vez que carga otro servicio o mes. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorCuadroTurnos.php';
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    const C = { ctx: null, estado: null, token: 0, ocupado: false };

    function $(id) { return document.getElementById(id); }
    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function cap(t) { return t.charAt(0).toUpperCase() + t.slice(1); }

    async function pedir(accion, datos, post) {
        let url = API + '?Accion=' + encodeURIComponent(accion);
        const opts = { credentials: 'same-origin' };
        if (post) {
            opts.method = 'POST';
            opts.body = new URLSearchParams(datos || {});
        } else if (datos) {
            Object.keys(datos).forEach(k => { url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(datos[k]); });
        }
        const resp = await fetch(url, opts);
        const texto = await resp.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Respuesta no válida de ' + url, texto);
            throw new Error('El servidor devolvió una respuesta inesperada. El detalle está en la consola (F12).');
        }
    }

    function contexto() {
        return window.AsignacionTurnosAPI ? window.AsignacionTurnosAPI.contexto() : null;
    }

    function textoPeriodo(c) { return MESES[c.mes - 1] + ' de ' + c.anio; }

    function fechaCorta(s) {
        const t = String(s || '').replace(' ', 'T');
        const d = new Date(t);
        return isNaN(d) ? '' : d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ------------------------------------------------------------------ pintar

    function pintar(mensajeVacio) {
        const caja = $('at-cuadro');
        if (!caja) return;
        const c = C.ctx;
        if (mensajeVacio || !c || !C.estado) {
            caja.innerHTML = '<div class="cu-barra cu-neutro"><i class="fas fa-clipboard-check"></i><span>' + esc(mensajeVacio || '') + '</span></div>';
            return;
        }
        const e = C.estado;
        const puede = e.puede || {};
        const periodo = esc(cap(textoPeriodo(c)));
        let html;
        if (!e.confirmado) {
            html = '<div class="cu-barra cu-borrador"><i class="fas fa-pencil-ruler"></i>' +
                '<div class="cu-texto"><b>Cuadro en borrador</b> · ' + periodo +
                '<small>Los enfermeros todavía no ven este mes; puedes ajustarlo libremente.' +
                (e.MotivoReapertura ? ' Reabierto el ' + esc(fechaCorta(e.FechaReapertura)) + ' (' + esc(e.MotivoReapertura) + ').' : '') + '</small></div>' +
                (puede.confirmar ? '<button type="button" class="cu-boton cu-primario" data-cu-act="confirmar"><i class="fas fa-paper-plane"></i> Confirmar cuadro</button>' : '') + '</div>';
        } else {
            const k = e.correos || {};
            let correos = '';
            if (k.total) {
                correos = 'Correos: ' + k.enviados + ' de ' + k.conCorreo + ' enviados' +
                    (k.errores ? ' · ' + k.errores + ' con error' : '') +
                    (k.sinCorreo ? ' · ' + k.sinCorreo + ' sin correo' : '') +
                    (k.pendientes ? ' · ' + k.pendientes + ' por enviar' : '');
            }
            html = '<div class="cu-barra cu-confirmado"><i class="fas fa-check-circle"></i>' +
                '<div class="cu-texto"><b>Cuadro confirmado</b> · ' + periodo +
                '<small>' + (e.ConfirmadoPor ? 'Por ' + esc(e.ConfirmadoPor) + ' el ' + esc(fechaCorta(e.FechaConfirmacion)) + '. ' : '') + esc(correos) + '</small></div>' +
                '<div class="cu-acciones">' +
                (puede.confirmar && k.pendientes ? '<button type="button" class="cu-boton cu-primario" data-cu-act="continuar"><i class="fas fa-envelope"></i> Continuar envío</button>' : '') +
                (puede.confirmar && k.errores ? '<button type="button" class="cu-boton" data-cu-act="reintentar"><i class="fas fa-redo"></i> Reintentar fallidos</button>' : '') +
                ((k.errores || k.sinCorreo) ? '<button type="button" class="cu-boton" data-cu-act="detalle"><i class="fas fa-list"></i> Ver detalle</button>' : '') +
                (puede.reabrir ? '<button type="button" class="cu-boton" data-cu-act="reabrir"><i class="fas fa-unlock"></i> Reabrir</button>' : '') +
                '</div></div>';
        }
        caja.innerHTML = html;
    }

    // ------------------------------------------------------------------ cargar

    async function refrescar() {
        const caja = $('at-cuadro');
        if (!caja) return;
        const c = contexto();
        C.ctx = c;
        if (!c || c.servicio === '') {
            C.estado = null;
            pintar('Elige un servicio para ver o confirmar su cuadro de turnos.');
            return;
        }
        const token = ++C.token;
        try {
            const r = await pedir('Estado', { IdServicio: c.servicio, Anio: c.anio, Mes: c.mes });
            if (token !== C.token) return; // llegó una respuesta más nueva
            if (!r.success) throw new Error(r.message || 'No se pudo consultar el cuadro');
            C.estado = r.cuadro;
            pintar();
            window.dispatchEvent(new CustomEvent('cuadro-turnos', { detail: { confirmado: !!C.estado.confirmado, servicio: c.servicio, anio: c.anio, mes: c.mes } }));
        } catch (err) {
            if (token !== C.token) return;
            C.estado = null;
            pintar('No se pudo consultar el cuadro: ' + err.message);
        }
    }

    // ------------------------------------------------------------------ confirmar

    async function confirmar() {
        const c = C.ctx;
        if (!c || C.ocupado) return;
        let resumen;
        try {
            const r = await pedir('Resumen', { IdServicio: c.servicio, Anio: c.anio, Mes: c.mes });
            if (!r.success) throw new Error(r.message);
            resumen = r.resumen;
        } catch (err) {
            Swal.fire('No se pudo preparar la confirmación', err.message, 'error');
            return;
        }
        if (!resumen.turnos) {
            Swal.fire('No hay turnos para confirmar', 'Genera o asigna turnos de ' + esc(resumen.servicio || '') + ' en ' + textoPeriodo(c) + ' antes de confirmar el cuadro.', 'warning');
            return;
        }
        let html = '<div style="text-align:left">' +
            '<p>Se confirmará el cuadro de <b>' + esc(resumen.servicio || '') + '</b> de <b>' + esc(textoPeriodo(c)) + '</b>: <b>' + resumen.turnos + '</b> turnos (' +
            Math.round(resumen.horas) + ' h) de <b>' + resumen.personas + '</b> personas.</p>' +
            '<p>Cada enfermero recibirá un correo y verá sus turnos en su portal. Después de confirmar, quitar o mover un turno exige un motivo.</p>';
        if (resumen.sinCorreo.length) {
            html += '<p style="color:#b45309"><b>' + resumen.sinCorreo.length + ' sin correo registrado</b> (se les avisa solo dentro del sistema): ' +
                esc(resumen.sinCorreo.slice(0, 6).map(p => p.Nombre).join(', ')) + (resumen.sinCorreo.length > 6 ? '…' : '') + '</p>';
        }
        if (resumen.diasConDeficit) {
            html += '<p style="color:#b45309"><b>' + resumen.diasConDeficit + (resumen.diasConDeficit === 1 ? ' día queda' : ' días quedan') + ' por debajo del personal requerido.</b> ' +
                'Puedes confirmar igual, o volver y completarlos.</p>';
        }
        html += '</div>';
        const ok = await Swal.fire({ title: '¿Confirmar el cuadro de turnos?', html: html, showCancelButton: true, confirmButtonText: 'Confirmar y avisar', cancelButtonText: 'Volver' });
        if (!ok.isConfirmed) return;

        C.ocupado = true;
        Swal.fire({ title: 'Confirmando…', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
        try {
            const r = await pedir('Confirmar', { IdServicio: c.servicio, Anio: c.anio, Mes: c.mes }, true);
            if (!r.success) {
                C.ocupado = false;
                await Swal.fire('No se pudo confirmar', r.message || 'Error desconocido', 'error');
                refrescar();
                return;
            }
            await enviarTodos(r.IdCuadro, 'Avisando a ' + r.destinatarios + ' personas…');
        } catch (err) {
            C.ocupado = false;
            await Swal.fire('Error', err.message, 'error');
        }
        C.ocupado = false;
        refrescar();
    }

    // Envía los correos pendientes lote a lote, mostrando el avance. Si se cierra la página a la mitad, lo que falta
    // queda pendiente y se retoma con "Continuar envío".
    async function enviarTodos(idCuadro, titulo) {
        Swal.fire({ title: titulo || 'Enviando correos…', html: '<div id="cu-progreso">Preparando…</div>', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
        let ultimo = null;
        for (let vuelta = 0; vuelta < 300; vuelta++) {
            const r = await pedir('EnviarLote', { IdCuadro: idCuadro }, true);
            if (!r.success) {
                await Swal.fire('El envío se interrumpió', (r.message || 'Error desconocido') + '. Lo confirmado queda guardado: usa «Continuar envío» para seguir.', 'warning');
                return;
            }
            ultimo = r;
            const progreso = document.getElementById('cu-progreso');
            if (progreso) progreso.textContent = r.enviados + ' de ' + r.conCorreo + ' enviados' + (r.errores ? ' · ' + r.errores + ' con error' : '');
            if (r.pendientes === 0) break;
            if (r.enviadosAhora + r.fallosAhora === 0) break; // nada avanzó: evita dar vueltas sin fin
        }
        let icono = 'success', texto = 'Se avisó a ' + ultimo.enviados + ' de ' + ultimo.conCorreo + ' personas por correo.';
        if (ultimo.errores) { icono = 'warning'; texto += ' ' + ultimo.errores + ' correos fallaron: usa «Reintentar fallidos» o revisa el detalle.'; }
        if (ultimo.pendientes) { icono = 'warning'; texto += ' Faltan ' + ultimo.pendientes + ' por enviar: usa «Continuar envío».'; }
        if (ultimo.sinCorreo) texto += ' ' + ultimo.sinCorreo + ' no tienen correo registrado (solo recibieron el aviso dentro del sistema).';
        await Swal.fire({ title: 'Cuadro confirmado', text: texto, icon: icono });
    }

    async function continuar() {
        const e = C.estado;
        if (!e || !e.IdCuadro || C.ocupado) return;
        C.ocupado = true;
        try { await enviarTodos(e.IdCuadro, 'Continuando el envío…'); } catch (err) { await Swal.fire('Error', err.message, 'error'); }
        C.ocupado = false;
        refrescar();
    }

    async function reintentar() {
        const e = C.estado;
        if (!e || !e.IdCuadro || C.ocupado) return;
        C.ocupado = true;
        try {
            const r = await pedir('ReintentarFallidos', { IdCuadro: e.IdCuadro }, true);
            if (!r.success) throw new Error(r.message);
            if (r.reintentar > 0) await enviarTodos(e.IdCuadro, 'Reintentando ' + r.reintentar + ' correos…');
        } catch (err) {
            await Swal.fire('Error', err.message, 'error');
        }
        C.ocupado = false;
        refrescar();
    }

    async function detalle() {
        const e = C.estado;
        if (!e || !e.IdCuadro) return;
        try {
            const r = await pedir('EstadoCorreos', { IdCuadro: e.IdCuadro });
            if (!r.success) throw new Error(r.message);
            const p = r.correos.problemas;
            const filas = p.map(x => '<tr><td>' + esc(x.Nombre) + '</td><td>' + (x.Estado === 'sin_correo' ? '<i>Sin correo registrado</i>' : esc(x.Correo || '')) + '</td><td>' +
                (x.Estado === 'error' ? esc(x.Error || 'Error') : 'No se envió') + '</td></tr>').join('');
            Swal.fire({ title: 'Correos con problemas', width: 720,
                html: '<div style="max-height:320px;overflow:auto;text-align:left"><table style="width:100%;font-size:13px"><thead><tr><th>Persona</th><th>Correo</th><th>Motivo</th></tr></thead><tbody>' + (filas || '<tr><td colspan="3">Sin problemas.</td></tr>') + '</tbody></table></div>' });
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        }
    }

    async function reabrir() {
        const c = C.ctx;
        if (!c || C.ocupado) return;
        const res = await Swal.fire({
            title: '¿Reabrir el cuadro?',
            html: 'Los enfermeros dejarán de ver <b>' + esc(textoPeriodo(c)) + '</b> hasta que lo confirmes de nuevo (se volverá a avisar por correo).',
            input: 'textarea', inputLabel: 'Motivo de la reapertura', inputPlaceholder: 'Ejemplo: corregir los turnos de la primera semana',
            showCancelButton: true, confirmButtonText: 'Reabrir', cancelButtonText: 'Cancelar',
            inputValidator: v => (!v || v.trim().length < 5) ? 'Escribe el motivo (mínimo 5 caracteres)' : undefined
        });
        if (!res.isConfirmed) return;
        C.ocupado = true;
        try {
            const r = await pedir('Reabrir', { IdServicio: c.servicio, Anio: c.anio, Mes: c.mes, Motivo: res.value.trim() }, true);
            if (!r.success) throw new Error(r.message);
            await Swal.fire({ title: 'Cuadro reabierto', text: 'Ya puedes ajustarlo libremente.', icon: 'success', timer: 1800, showConfirmButton: false });
        } catch (err) {
            await Swal.fire('No se pudo reabrir', err.message, 'error');
        }
        C.ocupado = false;
        refrescar();
    }

    document.addEventListener('click', function (ev) {
        const el = ev.target.closest('[data-cu-act]');
        if (!el || !$('at-cuadro') || !$('at-cuadro').contains(el)) return;
        switch (el.dataset.cuAct) {
            case 'confirmar': confirmar(); break;
            case 'continuar': continuar(); break;
            case 'reintentar': reintentar(); break;
            case 'detalle': detalle(); break;
            case 'reabrir': reabrir(); break;
        }
    });

    window.CuadroTurnosUI = {
        refrescar: refrescar,
        // ¿El cuadro que se está viendo está confirmado? (otras pantallas lo usan para pedir motivo al quitar o mover)
        confirmado: function () { return !!(C.estado && C.estado.confirmado); },
        estado: function () { return C.estado; }
    };
})();
