/* Bitácora de cambios de turnos (Vista/CoordinacionEnfermeria.php, sección #bitacora-content).
   Consulta quién cambió qué y cuándo (turnos asignados, quitados, novedades, coberturas, cuadros confirmados, cambios entre
   enfermeros…), con filtros, paginación, detalle antes/después y exportación a CSV. Solo lectura:
   Controlador/ControladorBitacoraTurnos.php filtra por los servicios que el usuario puede ver. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorBitacoraTurnos.php';
    const API_SERVICIO = '../Controlador/ControladorServicio.php';
    const API_TURNOS = '../Controlador/ControladorTurnoEnfermeria.php';

    const B = { acciones: [], etiqueta: {}, servicios: [], pagina: 1, tamano: 25, total: 0, token: 0, montado: false };

    function $(id) { return document.getElementById(id); }
    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function pad(n) { return String(n).padStart(2, '0'); }
    function isoLocal(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    async function pedir(url) {
        const resp = await fetch(url, { credentials: 'same-origin' });
        const texto = await resp.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Respuesta no válida de ' + url, texto);
            throw new Error('El servidor devolvió una respuesta inesperada. El detalle está en la consola (F12).');
        }
    }

    function parametros(extra) {
        const p = new URLSearchParams(extra || {});
        [['Desde', 'bt-desde'], ['Hasta', 'bt-hasta'], ['IdServicio', 'bt-servicio'], ['AccionBitacora', 'bt-accion'], ['Q', 'bt-q']].forEach(([k, id]) => {
            const el = $(id);
            if (el && el.value.trim() !== '') p.set(k, el.value.trim());
        });
        return p;
    }

    function fechaHora(s) {
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? esc(s) : d.toLocaleString('es-CO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    function fechaCorta(s) {
        if (!s) return '';
        const d = new Date(String(s).substring(0, 10) + 'T12:00:00');
        return isNaN(d) ? esc(s) : d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function armar() {
        const raiz = $('bitacora-raiz');
        const hoy = new Date();
        const hace30 = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 30);
        raiz.innerHTML =
            '<div class="bt-filtros">' +
            '<div class="form-group"><label for="bt-desde">Desde</label><input type="date" id="bt-desde" class="form-control" value="' + isoLocal(hace30) + '"></div>' +
            '<div class="form-group"><label for="bt-hasta">Hasta</label><input type="date" id="bt-hasta" class="form-control" value="' + isoLocal(hoy) + '"></div>' +
            '<div class="form-group"><label for="bt-servicio">Servicio</label><select id="bt-servicio" class="form-control"></select></div>' +
            '<div class="form-group"><label for="bt-accion">Qué pasó</label><select id="bt-accion" class="form-control"></select></div>' +
            '<div class="form-group bt-q"><label for="bt-q">Persona, usuario o motivo</label><input type="search" id="bt-q" class="form-control" placeholder="Buscar…" autocomplete="off"></div>' +
            '<div class="bt-botones"><button type="button" class="btn btn-primary" data-bt-act="buscar"><i class="fas fa-search"></i> Buscar</button>' +
            '<button type="button" class="btn btn-outline" data-bt-act="limpiar">Limpiar</button>' +
            '<button type="button" class="btn btn-outline" data-bt-act="exportar"><i class="fas fa-file-csv"></i> Exportar CSV</button></div></div>' +
            '<div id="bt-resumen" class="bt-resumen"></div><div id="bt-tabla"></div><div id="bt-paginas" class="bt-paginas"></div>';
        pintarSelects();
        if (!B.montado) {
            B.montado = true;
            document.addEventListener('click', onClick);
            document.addEventListener('keydown', ev => { if (ev.key === 'Enter' && ev.target && ev.target.id === 'bt-q') { B.pagina = 1; cargar(); } });
        }
    }

    function pintarSelects() {
        $('bt-servicio').innerHTML = '<option value="">Todos los míos</option>' + B.servicios.map(s => '<option value="' + esc(s.IdServicio) + '">' + esc(s.NombreServicio) + '</option>').join('');
        $('bt-accion').innerHTML = '<option value="">Todo</option>' + B.acciones.map(a => '<option value="' + esc(a.codigo) + '">' + esc(a.etiqueta) + '</option>').join('');
    }

    async function cargar() {
        const token = ++B.token;
        $('bt-tabla').innerHTML = '<p class="vacio">Cargando…</p>';
        try {
            const r = await pedir(API + '?' + parametros({ Accion: 'Listar', Pagina: B.pagina, Tamano: B.tamano }).toString());
            if (token !== B.token) return;
            if (!r.success) throw new Error(r.message || 'No se pudo consultar la bitácora');
            B.total = r.total;
            pintarTabla(r.filas);
            pintarPaginas();
        } catch (err) {
            if (token !== B.token) return;
            $('bt-tabla').innerHTML = '<p class="vacio">' + esc(err.message) + '</p>';
            $('bt-resumen').textContent = '';
            $('bt-paginas').innerHTML = '';
        }
    }

    function pintarTabla(filas) {
        $('bt-resumen').textContent = B.total + (B.total === 1 ? ' registro' : ' registros');
        if (!filas.length) { $('bt-tabla').innerHTML = '<p class="vacio">No hay registros con esos filtros.</p>'; return; }
        let html = '<div class="table-responsive"><table class="bt-tabla"><thead><tr><th>Fecha y hora</th><th>Qué pasó</th><th>Servicio</th><th>Turno</th><th>Persona</th><th>Motivo</th><th>Usuario</th><th></th></tr></thead><tbody>';
        filas.forEach(f => {
            const persona = f.NombreAfectado ? esc(f.NombreAfectado) + (f.NombreRelacionado ? ' <small>↔ ' + esc(f.NombreRelacionado) + '</small>' : '') : (f.NombreRelacionado ? esc(f.NombreRelacionado) : '');
            html += '<tr data-bt-id="' + f.IdBitacora + '" tabindex="0">' +
                '<td>' + fechaHora(f.FechaHora) + '</td>' +
                '<td><span class="bt-accion">' + esc(B.etiqueta[f.Accion] || f.Accion) + '</span>' + (f.Fase ? ' <span class="bt-fase bt-' + esc(f.Fase) + '">' + esc(f.Fase) + '</span>' : '') + '</td>' +
                '<td>' + esc(f.NombreServicio || '—') + '</td><td>' + (f.FechaTurno ? fechaCorta(f.FechaTurno) : '—') + '</td>' +
                '<td>' + (persona || '—') + '</td>' +
                '<td>' + (f.CodigoMotivo ? '<span class="at-chip at-sello">' + esc(f.CodigoMotivo) + '</span> ' : '') + esc(f.Motivo || '') + '</td>' +
                '<td>' + esc(f.UsuarioNombre || '—') + '</td>' +
                '<td><button type="button" class="bt-ver" data-bt-act="detalle" data-id="' + f.IdBitacora + '" aria-label="Ver el detalle"><i class="fas fa-eye"></i></button></td></tr>';
        });
        $('bt-tabla').innerHTML = html + '</tbody></table></div>';
    }

    function pintarPaginas() {
        const paginas = Math.max(1, Math.ceil(B.total / B.tamano));
        $('bt-paginas').innerHTML = paginas <= 1 ? '' :
            '<button type="button" class="btn btn-outline" data-bt-act="pagina" data-dir="-1"' + (B.pagina <= 1 ? ' disabled' : '') + '>Anterior</button>' +
            '<span>Página ' + B.pagina + ' de ' + paginas + '</span>' +
            '<button type="button" class="btn btn-outline" data-bt-act="pagina" data-dir="1"' + (B.pagina >= paginas ? ' disabled' : '') + '>Siguiente</button>';
    }

    function bloque(titulo, datos) {
        if (datos === null || datos === undefined) return '';
        let cuerpo;
        if (typeof datos === 'object') {
            cuerpo = '<dl>' + Object.keys(datos).map(k => '<div><dt>' + esc(k) + '</dt><dd>' + esc(typeof datos[k] === 'object' ? JSON.stringify(datos[k]) : datos[k]) + '</dd></div>').join('') + '</dl>';
        } else {
            cuerpo = '<p>' + esc(datos) + '</p>';
        }
        return '<div class="bt-bloque"><h4>' + esc(titulo) + '</h4>' + cuerpo + '</div>';
    }

    async function detalle(id) {
        try {
            const r = await pedir(API + '?Accion=Detalle&IdBitacora=' + encodeURIComponent(id));
            if (!r.success) throw new Error(r.message);
            const f = r.registro;
            const filas = [
                ['Fecha y hora', fechaHora(f.FechaHora)], ['Qué pasó', esc(B.etiqueta[f.Accion] || f.Accion)], ['Usuario', esc(f.UsuarioNombre || '—') + (f.Ip ? ' · ' + esc(f.Ip) : '')],
                f.NombreServicio ? ['Servicio', esc(f.NombreServicio)] : null, f.FechaTurno ? ['Fecha del turno', fechaCorta(f.FechaTurno)] : null,
                f.NombreAfectado ? ['Persona', esc(f.NombreAfectado)] : null, f.NombreRelacionado ? ['Relacionada', esc(f.NombreRelacionado)] : null,
                f.CodigoMotivo ? ['Motivo', esc(f.CodigoMotivo) + (f.Motivo ? ' · ' + esc(f.Motivo) : '')] : (f.Motivo ? ['Motivo', esc(f.Motivo)] : null),
                f.Fase ? ['Cuadro', esc(f.Fase)] : null
            ].filter(Boolean);
            Swal.fire({ title: 'Detalle del registro', width: 640, confirmButtonText: 'Cerrar',
                html: '<div class="bt-detalle"><dl class="bt-resumen-dl">' + filas.map(x => '<div><dt>' + x[0] + '</dt><dd>' + x[1] + '</dd></div>').join('') + '</dl>' +
                    bloque('Antes', f.Antes) + bloque('Después', f.Despues) + '</div>' });
        } catch (err) {
            Swal.fire('No se pudo abrir', err.message, 'error');
        }
    }

    function onClick(ev) {
        const el = ev.target.closest('[data-bt-act]');
        const fila = ev.target.closest('tr[data-bt-id]');
        if (!$('bitacora-raiz') || !$('bitacora-raiz').contains(ev.target)) return;
        if (!el && fila) { detalle(fila.dataset.btId); return; }
        if (!el) return;
        switch (el.dataset.btAct) {
            case 'buscar': B.pagina = 1; cargar(); break;
            case 'limpiar': armar(); B.pagina = 1; cargar(); break;
            case 'exportar': window.location.href = API + '?' + parametros({ Accion: 'Exportar' }).toString(); break;
            case 'pagina': B.pagina = Math.max(1, B.pagina + Number(el.dataset.dir)); cargar(); break;
            case 'detalle': detalle(el.dataset.id); break;
        }
    }

    window.inicializarBitacora = async function () {
        try {
            if (!B.acciones.length) {
                const [acc, sv, mis] = await Promise.all([
                    pedir(API + '?Accion=Acciones'),
                    pedir(API_SERVICIO + '?Accion=Listar').catch(() => []),
                    pedir(API_TURNOS + '?Accion=MisServicios').catch(() => null)
                ]);
                B.acciones = acc.success ? acc.acciones : [];
                B.etiqueta = {};
                B.acciones.forEach(a => { B.etiqueta[a.codigo] = a.etiqueta; });
                let servicios = Array.isArray(sv) ? sv.filter(s => String(s.IdEstado) !== '2') : [];
                if (mis && mis.success && mis.todos === false) servicios = servicios.filter(s => mis.servicios.map(String).indexOf(String(s.IdServicio)) !== -1);
                B.servicios = servicios;
            }
        } catch (err) { console.error(err); }
        armar();
        B.pagina = 1;
        cargar();
    };
})();
