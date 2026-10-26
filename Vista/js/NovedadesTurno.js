/* Novedades de turno (Vista/CoordinacionEnfermeria.php, pantalla de Asignación de Turnos).
   Quitar un turno de un cuadro YA CONFIRMADO pide un motivo (IN, CM, CA, AB, RE) y quién lo cubre (apoyos del servicio);
   el día queda con un sello gris con las 2 letras. También permite asignar la cobertura que quedó pendiente y deshacer
   una novedad. Habla con Controlador/ControladorNovedadTurno.php, que vuelve a validar permisos y servicio.
   AsignacionTurnos.js lo invoca (NovedadesTurnoUI.quitar / NovedadesTurnoUI.sello) y recarga la grilla si devuelve true. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorNovedadTurno.php';
    let motivosCache = null;

    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

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

    async function motivos() {
        if (!motivosCache) {
            const r = await pedir('Motivos');
            motivosCache = r.success ? r.motivos : [];
        }
        return motivosCache;
    }

    function fechaLarga(f) {
        const d = new Date(String(f).substring(0, 10) + 'T12:00:00');
        return isNaN(d) ? f : d.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }

    function contexto(o) {
        return '<p class="nv-ctx"><b>' + esc(o.nombre) + '</b><br>' + esc(fechaLarga(o.fecha)) +
            (o.tipoCodigo ? ' · turno ' + esc(o.tipoCodigo) + (o.tipoNombre ? ' (' + esc(o.tipoNombre) + ')' : '') : '') +
            (o.servicioNombre ? ' · ' + esc(o.servicioNombre) : '') + '</p>';
    }

    // ------------------------------------------------------------------ pasos

    async function elegirMotivo(o, titulo, intro, boton) {
        const lista = await motivos();
        if (!lista.length) { await Swal.fire('Sin motivos', 'No hay motivos de novedad configurados.', 'warning'); return null; }
        const r = await Swal.fire({
            title: titulo || 'Quitar un turno confirmado',
            html: '<div class="nv-wrap">' + contexto(o) +
                '<p>' + (intro || 'El cuadro de este mes ya está confirmado, así que el día queda marcado con un sello. ¿Cuál es el motivo?') + '</p>' +
                '<div class="nv-motivos">' + lista.map(m =>
                    '<label class="nv-motivo"><input type="radio" name="nv-motivo" value="' + esc(m.Codigo) + '">' +
                    '<span class="at-chip at-sello">' + esc(m.Codigo) + '</span><span><b>' + esc(m.Nombre) + '</b><small>' + esc(m.Descripcion || '') + '</small></span></label>').join('') + '</div>' +
                '<textarea id="nv-obs" class="swal2-textarea" maxlength="255" placeholder="Observación (opcional)"></textarea></div>',
            showCancelButton: true, confirmButtonText: boton || 'Siguiente', cancelButtonText: 'Cancelar', focusConfirm: false, width: 560,
            preConfirm: () => {
                const sel = document.querySelector('input[name="nv-motivo"]:checked');
                if (!sel) { Swal.showValidationMessage('Elige un motivo'); return false; }
                return { codigo: sel.value, obs: (document.getElementById('nv-obs').value || '').trim() };
            }
        });
        return r.isConfirmed ? r.value : null;
    }

    // Lista los apoyos del servicio con su disponibilidad. Devuelve { idEnfPer: '' } (sin reemplazo) | { idEnfPer: n } | null.
    async function elegirCobertura(o, titulo, textoBoton, permitirSin) {
        Swal.fire({ title: 'Buscando quién puede cubrir…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let r;
        try {
            r = await pedir('CoberturaDisponible', { IdTurno: o.idTurno });
            if (!r.success) throw new Error(r.message);
        } catch (err) {
            await Swal.fire('No se pudo consultar', err.message, 'error');
            return null;
        }
        const cand = r.candidatos;
        let html = '<div class="nv-wrap">' + contexto(o) + '<p>Estos son los enfermeros de <b>apoyo</b> del servicio:</p><div class="nv-cands">';
        if (!cand.length) html += '<p class="nv-vacio">Este servicio no tiene enfermeros de apoyo asignados.</p>';
        cand.forEach(c => {
            html += '<label class="nv-cand' + (c.disponible ? '' : ' nv-no') + '"><input type="radio" name="nv-cub" value="' + c.IdEnfPer + '"' + (c.disponible ? '' : ' disabled') + '>' +
                '<span><b>' + esc(c.Nombre) + '</b><small>' + esc(c.Perfil || '') + ' · ' + c.turnosMes + ' turnos · ' + Math.round(c.horasMes) + ' h · ' + c.nochesMes + ' noches en el mes</small>' +
                (c.disponible ? '' : '<small class="nv-razon">' + esc(c.razon) + '</small>') + '</span></label>';
        });
        if (permitirSin) {
            html += '<label class="nv-cand"><input type="radio" name="nv-cub" value=""><span><b>Sin reemplazo por ahora</b><small>El sello queda y podrás asignar la cobertura después.</small></span></label>';
        }
        html += '</div></div>';
        const res = await Swal.fire({
            title: titulo, html: html, showCancelButton: true, confirmButtonText: textoBoton, cancelButtonText: 'Cancelar', focusConfirm: false, width: 560,
            preConfirm: () => {
                const sel = document.querySelector('input[name="nv-cub"]:checked');
                if (!sel) { Swal.showValidationMessage('Elige quién cubre el turno'); return false; }
                return { idEnfPer: sel.value };
            }
        });
        return res.isConfirmed ? res.value : null;
    }

    // ------------------------------------------------------------------ flujos

    // Quitar un turno de un cuadro confirmado: motivo -> cobertura -> registrar. Devuelve true si se registró la novedad.
    async function quitar(o) {
        const m = await elegirMotivo(o);
        if (!m) return false;
        const c = await elegirCobertura(o, '¿Quién cubre el turno?', 'Registrar novedad', true);
        if (!c) return false;
        Swal.fire({ title: 'Registrando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const r = await pedir('Aplicar', { IdTurno: o.idTurno, Codigo: m.codigo, Observacion: m.obs, IdEnfPerCubre: c.idEnfPer }, true);
            if (!r.success) throw new Error(r.message || 'No se pudo registrar la novedad');
            await Swal.fire({ title: 'Novedad registrada', text: c.idEnfPer === '' ? 'El día quedó con el sello; falta asignar la cobertura.' : 'El turno quedó cubierto.', icon: 'success', timer: 2000, showConfirmButton: false });
            return true;
        } catch (err) {
            await Swal.fire('No se pudo registrar', err.message, 'error');
            return false;
        }
    }

    // Detalle de un sello con sus acciones. Devuelve true si algo cambió (para recargar la grilla).
    async function sello(o) {
        const lineas = [
            ['Motivo', esc(o.motivoNombre || o.codigoNovedad)],
            o.observacion ? ['Observación', esc(o.observacion)] : null,
            o.por ? ['Registró', esc(o.por) + (o.fechaNovedad ? ' · ' + esc(String(o.fechaNovedad).substring(0, 16).replace('T', ' ')) : '')] : null,
            ['Cobertura', o.cubre ? '<b>' + esc(o.cubre) + '</b>' : '<i>Sin cobertura todavía</i>']
        ].filter(Boolean);
        const r = await Swal.fire({
            title: 'Novedad en el turno',
            html: '<div class="nv-wrap">' + contexto(o) + '<div class="nv-detalle"><span class="at-chip at-sello">' + esc(o.codigoNovedad) + '</span><dl>' +
                lineas.map(l => '<div><dt>' + l[0] + '</dt><dd>' + l[1] + '</dd></div>').join('') + '</dl></div></div>',
            showConfirmButton: !!(o.puedeCobertura && !o.cubre), confirmButtonText: 'Asignar cobertura',
            showDenyButton: !!o.puedeRevertir, denyButtonText: 'Deshacer novedad',
            showCancelButton: true, cancelButtonText: 'Cerrar', width: 520
        });
        if (r.isConfirmed) {
            const c = await elegirCobertura(o, 'Asignar cobertura', 'Asignar', false);
            if (!c) return false;
            try {
                const x = await pedir('AsignarCobertura', { IdTurno: o.idTurno, IdEnfPerCubre: c.idEnfPer }, true);
                if (!x.success) throw new Error(x.message);
                await Swal.fire({ title: 'Cobertura asignada', icon: 'success', timer: 1600, showConfirmButton: false });
                return true;
            } catch (err) {
                await Swal.fire('No se pudo asignar', err.message, 'error');
                return false;
            }
        }
        if (r.isDenied) {
            const ok = await Swal.fire({ title: '¿Deshacer la novedad?', text: 'El turno vuelve a ser de ' + o.nombre + (o.cubre ? ' y se retira la cobertura de ' + o.cubre : '') + '.', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Deshacer', cancelButtonText: 'Cancelar' });
            if (!ok.isConfirmed) return false;
            try {
                const x = await pedir('Revertir', { IdTurno: o.idTurno }, true);
                if (!x.success) throw new Error(x.message);
                await Swal.fire({ title: 'Novedad deshecha', icon: 'success', timer: 1600, showConfirmButton: false });
                return true;
            } catch (err) {
                await Swal.fire('No se pudo deshacer', err.message, 'error');
                return false;
            }
        }
        return false;
    }

    // Motivo para mover un turno de un cuadro confirmado (arrastrar y soltar). Devuelve { codigo, obs } o null.
    function motivoParaMover(o) {
        return elegirMotivo(o, 'Mover un turno confirmado',
            'El cuadro de este mes ya está confirmado: en la celda de origen queda un sello con el motivo y el turno pasa a la celda destino. ¿Cuál es el motivo?', 'Mover turno');
    }

    window.NovedadesTurnoUI = { quitar: quitar, sello: sello, motivoParaMover: motivoParaMover };
})();
