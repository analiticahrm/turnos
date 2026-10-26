/* Asignación de Turnos de Enfermería
   (Vista/CoordinacionEnfermeria.php, sección #asignacion-turnos-content + modal #dia-turnos-modal)

   - Tabla persona x día, agrupada por servicio y perfil, con edición directa en la celda.
   - Calendario (Mes / Semana / Día) con un punto por enfermero y panel del día editable.
   - Barra dinámica: período, servicio, perfil y búsqueda se aplican al instante (sin botón "Cargar").

   Todo vive en una IIFE para no chocar con los globales del monolito CoordinacionEnfermeria.js; solo se
   exponen inicializarAsignacionTurnos() y generarAutomatico(). Reutiliza showModal/hideModal de ese archivo.
   Las fechas son siempre 'YYYY-MM-DD' locales (nada de toISOString: Bogotá es UTC-5). */
(function () {
    'use strict';

    const API_TURNOS = '../Controlador/ControladorTurnoEnfermeria.php';
    const API_SERVICIO = '../Controlador/ControladorServicio.php';
    const API_TIPOS = '../Controlador/ControladorTipoTurno.php';
    const API_FESTIVO = '../Controlador/ControladorFestivo.php';
    const API_GENERADOR = '../Controlador/ControladorGeneradorTurnos.php';
    const API_PERMISOS = '../Controlador/ControladorPermisosEnfermeria.php';
    const API_NOVEDAD = '../Controlador/ControladorNovedadTurno.php';
    const LS_PREFS = 'asignacionTurnos.prefs';

    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const DIAS_CORTO = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    const DIAS_LARGO = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const MAX_PUNTOS = 30; // puntos por celda en la vista Mes antes de mostrar "+N"

    // Color de cada tipo de turno (tipoturno no tiene columna de color): por código, o paleta por orden.
    const COLOR_CODIGO = { D: '#f59e0b', N: '#6366f1', M: '#10b981', T: '#f97316', P: '#14b8a6', L: '#94a3b8' };
    const PALETA = ['#0ea5e9', '#ec4899', '#84cc16', '#a855f7', '#ef4444', '#06b6d4', '#eab308', '#8b5cf6'];

    // ---------------------------------------------------------------- estado
    const E = {
        montado: false,
        vista: 'tabla',        // 'tabla' | 'calendario'
        sub: 'mes',            // escala del calendario: 'mes' | 'semana' | 'dia'
        ref: hoyDate(),        // fecha de referencia del período mostrado
        hoyISO: iso(hoyDate()),
        servicio: '',          // '' = todos los servicios
        perfil: '',            // '' = todos los perfiles (IdPerfil como texto)
        q: '',                 // búsqueda normalizada (sin tildes, minúsculas)
        qTexto: '',            // búsqueda tal como la escribió el usuario
        servicios: [],
        serviciosPermitidos: null, // null = todos; lista de ids (texto) para un coordinador de servicio
        permisos: null,        // MiSesion: { admin, acciones } de quien usa la pantalla (solo para mostrar u ocultar botones)
        motivos: {},           // Codigo -> motivo de novedad (IN, CM, CA, AB, RE)
        servicioPorId: {},
        tipos: [],
        tipoPorId: {},
        serviciosPintados: false,
        festivos: {},          // { 'YYYY-MM-DD': descripción }
        festivosAnios: {},
        data: null,            // última respuesta de ListarGrilla
        porEnfPer: {},         // IdEnfPer -> fila de data.enfermeros
        token: 0,              // descarta respuestas de cargas viejas
        plegados: {},          // grupos plegados en la tabla
        resaltado: null,       // IdMedico resaltado en el calendario
        diaAbierto: null,      // 'YYYY-MM-DD' abierto en el panel del día
        sinAbierto: false,     // sección "Sin turno este día" desplegada
        sinQ: '',              // búsqueda dentro de esa sección
        selector: null,        // { ctx, ancla, pop } mientras el selector de turno está abierto
        picker: null,
        pickerAnio: 0,
        pendientes: {},        // 'IdEnfPer|fecha' mientras se guarda
        arrastre: null,        // { idEnfPer, fecha } del turno que se está arrastrando
        destinoMarcado: null,  // celda resaltada como posible destino
        moviendo: null,        // modo "Mover…": { idEnfPer, fecha } del turno que espera su celda destino
        real: false            // true = "Turnos reales" (quién cobra cada turno, sin intercambios); false = "Con cambios" (quién lo trabaja)
    };

    // ---------------------------------------------------------------- utilidades
    function $(id) { return document.getElementById(id); }

    function esc(valor) {
        return String(valor === null || valor === undefined ? '' : valor)
            .replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function normalizar(texto) {
        return String(texto || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function cap(texto) { return texto.charAt(0).toUpperCase() + texto.slice(1); }
    function pad(n) { return String(n).padStart(2, '0'); }
    function hhmm(hora) { return String(hora || '').substring(0, 5); }

    function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parseISO(s) {
        const p = String(s).split('-').map(Number);
        return new Date(p[0], p[1] - 1, p[2], 12, 0, 0); // a mediodía: sin sorpresas por cambios de hora
    }
    function hoyDate() {
        const d = new Date();
        return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 12, 0, 0);
    }
    function addDias(d, n) { const r = new Date(d); r.setDate(r.getDate() + n); return r; }
    function addMeses(d, n) {
        const r = new Date(d.getFullYear(), d.getMonth() + n, 1, 12, 0, 0);
        r.setDate(Math.min(d.getDate(), new Date(r.getFullYear(), r.getMonth() + 1, 0).getDate()));
        return r;
    }
    function inicioSemana(d) { return addDias(d, -d.getDay()); } // semana de domingo a sábado (igual que el calendario de médicos)
    function ultimoDiaMes(anio, mes0) { return new Date(anio, mes0 + 1, 0).getDate(); }

    function textoFecha(f, largo) {
        const d = parseISO(f);
        if (largo) return DIAS_LARGO[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()] + ' de ' + d.getFullYear();
        return DIAS_CORTO[d.getDay()] + ' ' + d.getDate() + ' ' + MESES[d.getMonth()].substring(0, 3);
    }

    // Matiz estable por enfermero (ángulo áureo): colores bien repartidos aunque los ids sean arbitrarios.
    function matiz(idMedico) { return ((Number(idMedico) * 137.508) % 360).toFixed(1); }

    function colorTipo(t) { return COLOR_CODIGO[String(t.Codigo).toUpperCase()] || PALETA[t._i % PALETA.length]; }
    function textoSobre(hex) {
        const n = parseInt(hex.substring(1), 16);
        const luz = (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255;
        return luz > 0.6 ? '#1f2937' : '#ffffff';
    }
    function estiloTipo(t) { const c = colorTipo(t); return '--tc:' + c + ';--tx:' + textoSobre(c); }
    function chipTipo(t) { return '<span class="at-chip" style="' + estiloTipo(t) + '">' + esc(t.Codigo) + '</span>'; }

    function porNombre(a, b) { return a.NombreMedico.localeCompare(b.NombreMedico, 'es'); }
    function porNombreItem(a, b) { return porNombre(a.e, b.e); }
    // Servicio > perfil > nombre: la tabla agrupa filas contiguas, así que no depende del orden del servidor.
    function porServicioPerfilNombre(a, b) {
        return (Number(a.EsApoyo || 0) - Number(b.EsApoyo || 0)) ||
            String(a.NombreServicio).localeCompare(String(b.NombreServicio), 'es') ||
            String(a.DescripcionPerfil).localeCompare(String(b.DescripcionPerfil), 'es') ||
            porNombre(a, b);
    }

    function guardarPrefs() {
        try { localStorage.setItem(LS_PREFS, JSON.stringify({ servicio: E.servicio, perfil: E.perfil, sub: E.sub })); } catch (e) { /* sin almacenamiento: no pasa nada */ }
    }
    function cargarPrefs() {
        try {
            const p = JSON.parse(localStorage.getItem(LS_PREFS) || '{}');
            if (typeof p.servicio === 'string') E.servicio = p.servicio;
            if (typeof p.perfil === 'string') E.perfil = p.perfil;
            if (p.sub === 'mes' || p.sub === 'semana' || p.sub === 'dia') E.sub = p.sub;
        } catch (e) { /* prefs corruptas: se ignoran */ }
    }

    // ---------------------------------------------------------------- red
    async function pedirJSON(url, opciones) {
        const resp = await fetch(url, opciones);
        const texto = await resp.text();
        try {
            return JSON.parse(texto);
        } catch (e) {
            console.error('Respuesta no válida de ' + url, texto);
            throw new Error('El servidor devolvió una respuesta inesperada (no es JSON). El detalle está en la consola (F12).');
        }
    }

    async function cargarCatalogos() {
        const respuestas = await Promise.all([
            pedirJSON(API_SERVICIO + '?Accion=Listar'),
            pedirJSON(API_TIPOS + '?Accion=Listar'),
            pedirJSON(API_TURNOS + '?Accion=MisServicios').catch(() => null),
            pedirJSON(API_PERMISOS + '?Accion=MiSesion').catch(() => null),
            pedirJSON(API_NOVEDAD + '?Accion=Motivos').catch(() => null)
        ]);
        // Lo que puede hacer esta persona (solo para mostrar u ocultar botones; el servidor decide siempre) y los motivos de novedad.
        E.permisos = (respuestas[3] && respuestas[3].success) ? respuestas[3].sesion : null;
        E.motivos = {};
        if (respuestas[4] && respuestas[4].success) respuestas[4].motivos.forEach(m => { E.motivos[m.Codigo] = m; });
        // Se ocultan solo los inactivos: un servicio "En implementación" también se programa.
        E.servicios = (Array.isArray(respuestas[0]) ? respuestas[0] : []).filter(s => String(s.IdEstado) !== '2');
        // Un coordinador de servicio solo trabaja con los servicios que coordina (el servidor lo vuelve a exigir).
        const mis = respuestas[2];
        E.serviciosPermitidos = (mis && mis.success && mis.todos === false) ? mis.servicios.map(String) : null;
        if (E.serviciosPermitidos) {
            E.servicios = E.servicios.filter(s => E.serviciosPermitidos.indexOf(String(s.IdServicio)) !== -1);
            if (E.servicio === '' || E.serviciosPermitidos.indexOf(String(E.servicio)) === -1) {
                E.servicio = E.servicios.length ? String(E.servicios[0].IdServicio) : '';
            }
        }
        E.tipos = (Array.isArray(respuestas[1]) ? respuestas[1] : []).slice().sort((a, b) => Number(a.IdTipoTurno) - Number(b.IdTipoTurno));
        E.tipoPorId = {};
        E.tipos.forEach((t, i) => { t._i = i; E.tipoPorId[t.IdTipoTurno] = t; });
        E.servicioPorId = {};
        E.servicios.forEach(s => { E.servicioPorId[s.IdServicio] = s; });
        if (E.servicio && !E.servicioPorId[E.servicio]) E.servicio = '';
        E.serviciosPintados = false;
    }

    async function asegurarFestivos(desde, hasta) {
        const anios = [];
        for (let a = Number(desde.substring(0, 4)); a <= Number(hasta.substring(0, 4)); a++) anios.push(a);
        for (const a of anios) {
            if (E.festivosAnios[a]) continue;
            try {
                const lista = await pedirJSON(API_FESTIVO + '?Accion=ListarPorAnio&Anio=' + a);
                (Array.isArray(lista) ? lista : []).forEach(f => { E.festivos[String(f.Fecha).substring(0, 10)] = f.Descripcion || 'Festivo'; });
                E.festivosAnios[a] = true;
            } catch (e) { /* los festivos son un adorno: no deben bloquear la pantalla */ }
        }
    }

    // Se carga siempre la grilla completa del mes de la fecha de referencia (con los días de las semanas vecinas):
    // cualquier semana o día que contenga la fecha cae dentro, así navegar dentro del mes no vuelve a pedir datos.
    function rangoCarga() {
        const primero = new Date(E.ref.getFullYear(), E.ref.getMonth(), 1, 12, 0, 0);
        const ultimo = new Date(E.ref.getFullYear(), E.ref.getMonth() + 1, 0, 12, 0, 0);
        return { desde: iso(inicioSemana(primero)), hasta: iso(addDias(inicioSemana(ultimo), 6)) };
    }

    async function cargar() {
        const rango = rangoCarga();
        const token = ++E.token;
        const cuerpo = $('at-cuerpo');
        if (!E.data) cuerpo.innerHTML = '<p class="vacio">Cargando…</p>';
        cuerpo.classList.add('cargando');
        try {
            const params = new URLSearchParams({ Accion: 'ListarGrilla', IdServicio: E.servicio, Desde: rango.desde, Hasta: rango.hasta });
            const resultados = await Promise.all([
                pedirJSON(API_TURNOS + '?' + params.toString()),
                asegurarFestivos(rango.desde, rango.hasta)
            ]);
            if (token !== E.token) return; // llegó una respuesta más nueva
            const resp = resultados[0];
            if (!resp || resp.success === false || !Array.isArray(resp.enfermeros)) {
                throw new Error((resp && resp.message) || 'No se pudo cargar la información de turnos');
            }
            resp.enfermeros.forEach(e => {
                if (Array.isArray(e.dias)) e.dias = {};
                if (!e.diasReales || Array.isArray(e.diasReales)) e.diasReales = e.diasReales ? {} : e.dias; // sin migración 15 no hay intercambios
                // Dos mapas por persona: con los cambios aprobados (e.dias) o los turnos reales; en pantalla se usa e.dias.
                e.diasConCambios = e.dias;
                e.dias = E.real ? e.diasReales : e.diasConCambios;
            });
            resp.enfermeros.sort(porServicioPerfilNombre);
            resp.sinPerfil = Array.isArray(resp.sinPerfil) ? resp.sinPerfil : [];
            E.data = resp;
            E.porEnfPer = {};
            resp.enfermeros.forEach(e => { E.porEnfPer[e.IdEnfPer] = e; });
            cuerpo.classList.remove('cargando');
            render();
            avisarCuadro();
        } catch (err) {
            if (token !== E.token) return;
            cuerpo.classList.remove('cargando');
            mostrarErrorCuerpo(err);
        }
    }

    // La barra del cuadro (Vista/js/CuadroTurnos.js) se actualiza al cargar otro servicio o mes.
    function avisarCuadro() {
        if (window.CuadroTurnosUI) window.CuadroTurnosUI.refrescar();
    }

    function mostrarErrorCuerpo(err) {
        $('at-cuerpo').innerHTML = '<div class="at-error"><p><i class="fas fa-exclamation-triangle"></i> ' + esc(err.message || 'No se pudo cargar') +
            '</p><button type="button" class="btn btn-outline" data-at-act="reintentar">Reintentar</button></div>';
    }

    // ---------------------------------------------------------------- datos derivados
    function enfermerosFiltrados() {
        const lista = E.data ? E.data.enfermeros : [];
        return lista.filter(e =>
            (!E.perfil || String(e.IdPerfil) === E.perfil) &&
            (!E.q || normalizar(e.NombreMedico).indexOf(E.q) !== -1));
    }

    function hayFiltroParcial() { return !!(E.perfil || E.q); }

    // ¿Puede esta persona hacer la acción? Solo decide qué botones se muestran: el servidor lo vuelve a exigir.
    function puede(accion) {
        if (E.real && /^(turnos\.(editar|novedad|novedad_revertir|generar)|cuadro\.)/.test(accion)) return false; // "Turnos reales" es solo lectura
        return !!(E.permisos && (E.permisos.admin || (E.permisos.acciones && E.permisos.acciones[accion])));
    }

    // Servicio desde el que se edita la fila: el elegido en el filtro o, viendo todos, el principal de la persona.
    function servicioContexto(e) { return E.servicio !== '' ? String(E.servicio) : String(e.IdServicio); }

    // Turno que la persona tiene ese día en OTRO servicio: no se puede tocar desde este y no cuenta para su cobertura.
    function esAjeno(e, t) {
        return !!t && t.IdServicio !== null && t.IdServicio !== undefined && String(t.IdServicio) !== servicioContexto(e);
    }

    // Personal requerido por turno del servicio (solo D y N tienen cupo definido en la tabla servicio).
    function requeridoPara(servicio, tipo) {
        const cod = String(tipo.Codigo).toUpperCase();
        const v = cod === 'D' ? servicio.PerfilesDiaServicio : (cod === 'N' ? servicio.PerfilesNocheServicio : null);
        return (v === null || v === undefined || v === '') ? null : Number(v);
    }

    // La cobertura solo tiene sentido con un servicio elegido y sin filtros parciales de perfil/búsqueda.
    function servicioParaCobertura() {
        return (E.servicio !== '' && !hayFiltroParcial()) ? (E.servicioPorId[E.servicio] || null) : null;
    }

    function turnosPorDia(filas) {
        const mapa = {};
        filas.forEach(e => {
            Object.keys(e.dias).forEach(f => {
                if (E.servicio !== '' && esAjeno(e, e.dias[f])) return; // está en otro servicio: no es de este
                if (e.dias[f].CodigoNovedad) return; // día con novedad (sello): no trabaja, no es un punto del calendario
                if (!mapa[f]) mapa[f] = [];
                mapa[f].push({ e: e, t: e.dias[f] });
            });
        });
        return mapa;
    }

    function diasDelMes() {
        const y = E.ref.getFullYear(), m = E.ref.getMonth();
        const dias = [];
        for (let d = 1; d <= ultimoDiaMes(y, m); d++) dias.push(y + '-' + pad(m + 1) + '-' + pad(d));
        return dias;
    }

    // Rango que muestra la vista actual (para la leyenda del calendario).
    function rangoVista() {
        if (E.vista === 'calendario' && E.sub === 'semana') {
            const a = inicioSemana(E.ref);
            return [iso(a), iso(addDias(a, 6))];
        }
        if (E.vista === 'calendario' && E.sub === 'dia') return [iso(E.ref), iso(E.ref)];
        if (E.vista === 'calendario') {
            const r = rangoCarga();
            return [r.desde, r.hasta];
        }
        const dias = diasDelMes();
        return [dias[0], dias[dias.length - 1]];
    }

    function textoFiltros() {
        const partes = [];
        if (E.servicio !== '' && E.servicioPorId[E.servicio]) partes.push(E.servicioPorId[E.servicio].NombreServicio);
        if (E.perfil) {
            const ejemplo = (E.data ? E.data.enfermeros : []).find(e => String(e.IdPerfil) === E.perfil);
            if (ejemplo) partes.push(ejemplo.DescripcionPerfil);
        }
        if (E.qTexto) partes.push('«' + E.qTexto + '»');
        return partes.length ? 'Mostrando: ' + partes.join(' · ') : '';
    }

    function claseDia(f, d) {
        const cls = [];
        if (d.getDay() === 0 || d.getDay() === 6) cls.push('finde');
        if (E.festivos[f]) cls.push('festivo');
        if (f === E.hoyISO) cls.push('hoy');
        return cls.join(' ');
    }

    // ---------------------------------------------------------------- barra
    function tituloPeriodo() {
        const r = E.ref;
        if (E.vista === 'calendario' && E.sub === 'semana') {
            const a = inicioSemana(r), b = addDias(a, 6);
            const ma = MESES[a.getMonth()].substring(0, 3), mb = MESES[b.getMonth()].substring(0, 3);
            return a.getMonth() === b.getMonth()
                ? a.getDate() + '–' + b.getDate() + ' ' + ma + ' ' + a.getFullYear()
                : a.getDate() + ' ' + ma + ' – ' + b.getDate() + ' ' + mb + ' ' + b.getFullYear();
        }
        if (E.vista === 'calendario' && E.sub === 'dia') return textoFecha(iso(r), true);
        return MESES[r.getMonth()] + ' ' + r.getFullYear();
    }

    function renderToolbar() {
        $('at-periodo-texto').textContent = cap(tituloPeriodo());

        const sel = $('at-servicio');
        if (!E.serviciosPintados) {
            sel.innerHTML = (E.serviciosPermitidos ? '' : '<option value="">Todos los servicios</option>') +
                E.servicios.map(s => '<option value="' + esc(s.IdServicio) + '">' + esc(s.NombreServicio) + '</option>').join('');
            E.serviciosPintados = true;
        }
        sel.value = E.servicio;

        document.querySelectorAll('[data-at-act="vista"]').forEach(b => b.classList.toggle('activo', b.dataset.vista === E.vista));
        document.querySelectorAll('[data-at-act="subvista"]').forEach(b => b.classList.toggle('activo', b.dataset.sub === E.sub));
        $('at-subvistas').hidden = E.vista !== 'calendario';
        document.querySelectorAll('[data-at-act="real"]').forEach(b => b.classList.toggle('activo', (b.dataset.real === '1') === E.real));
        $('at-cuerpo').classList.toggle('at-modo-real', E.real);

        const gen = $('at-generar');
        gen.disabled = E.servicio === '' || E.real;
        gen.title = E.real ? 'En «Turnos reales» no se puede editar' : (E.servicio === '' ? 'Elige un servicio para generar sus turnos' : 'Generar los turnos del mes para este servicio');

        $('at-ayuda').textContent = (E.vista === 'tabla'
            ? 'Haz clic en una celda para elegir el turno: se guarda al instante. D/N/Supr funcionan como atajos dentro del selector.'
            : 'Haz clic en un día para ver quién trabaja y editar sus turnos. Cada punto es un enfermero; el anillo es el turno de noche.') +
            (E.real ? ' Estás en «Turnos reales»: cada turno aparece en quien lo cobra (el cambio no mueve el pago). Solo lectura.'
                : ' El símbolo ⇄ marca un turno intercambiado: aquí aparece en quien lo trabaja.');

        renderPerfiles();
    }

    function renderPerfiles() {
        const cont = $('at-perfiles');
        const lista = E.data ? E.data.enfermeros : [];
        const cuenta = {};
        lista.forEach(e => {
            const k = String(e.IdPerfil);
            if (!cuenta[k]) cuenta[k] = { desc: e.DescripcionPerfil, n: 0 };
            cuenta[k].n++;
        });
        const ids = Object.keys(cuenta);
        if (!ids.length) { cont.innerHTML = ''; return; }
        if (E.perfil && !cuenta[E.perfil]) { E.perfil = ''; guardarPrefs(); }
        ids.sort((a, b) => cuenta[a].desc.localeCompare(cuenta[b].desc, 'es'));
        const chip = (id, texto, n) =>
            '<button type="button" class="at-chip-filtro' + (E.perfil === id ? ' activo' : '') + '" data-at-act="perfil" data-perfil="' + esc(id) + '">' +
            esc(texto) + ' <span class="at-cuenta">' + n + '</span></button>';
        cont.innerHTML = '<span class="at-perfiles-tit">Perfil</span>' + chip('', 'Todos', lista.length) +
            ids.map(id => chip(id, cuenta[id].desc, cuenta[id].n)).join('');
    }

    function renderAviso() {
        const aviso = $('at-aviso');
        const sin = E.data ? E.data.sinPerfil : [];
        if (!sin.length) { aviso.innerHTML = ''; return; }
        const nombres = sin.slice(0, 6).map(s => esc(s.NombreMedico)).join(', ') + (sin.length > 6 ? ' y ' + (sin.length - 6) + ' más' : '');
        aviso.innerHTML = '<div class="at-alerta"><i class="fas fa-exclamation-triangle"></i> <span><b>' + sin.length +
            (sin.length === 1 ? ' enfermero tiene' : ' enfermeros tienen') + ' servicio pero no perfil</b>, por eso no aparece' + (sin.length === 1 ? '' : 'n') +
            ' en la tabla ni puede' + (sin.length === 1 ? '' : 'n') + ' recibir turnos: ' + nombres +
            '. Asígnale' + (sin.length === 1 ? '' : 's') + ' un perfil en Enfermeros &gt; Lista de Enfermeros.</span></div>';
    }

    // ---------------------------------------------------------------- render general
    function render() {
        cerrarSelector();
        renderToolbar();
        renderAviso();
        renderCuerpo();
        if (E.diaAbierto) renderDiaModal();
    }

    function renderCuerpo() {
        const cuerpo = $('at-cuerpo');
        if (!E.data) return;
        E.hoyISO = iso(hoyDate()); // una vez por pintado (no por celda)
        const wrap = cuerpo.querySelector('.at-tabla-wrap');
        const scroll = wrap ? { t: wrap.scrollTop, l: wrap.scrollLeft } : null;

        if (E.vista === 'tabla') {
            cuerpo.innerHTML = tablaHTML();
            aplicarPlegado();
            const nuevo = cuerpo.querySelector('.at-tabla-wrap');
            if (nuevo && scroll) { nuevo.scrollTop = scroll.t; nuevo.scrollLeft = scroll.l; }
        } else {
            const buscaba = buscabaEnSin();
            cuerpo.innerHTML = calendarioHTML();
            restaurarBusquedaSin(cuerpo, buscaba);
        }
    }

    function vacioHTML() {
        if (E.data && E.data.enfermeros.length > 0) {
            return '<p class="vacio">Ningún enfermero coincide con el filtro actual.</p>';
        }
        return '<div class="at-vacio"><i class="fas fa-user-nurse"></i>' +
            '<p>No hay enfermeros con servicio y perfil asignados ' + (E.servicio === '' ? 'a ningún servicio' : 'a este servicio') + '.</p>' +
            '<small>Asígnalos en Enfermeros &gt; Lista de Enfermeros: cada enfermero necesita un servicio y un perfil para recibir turnos.</small></div>';
    }

    // ---------------------------------------------------------------- tabla
    function celdaHTML(e, fecha) {
        const t = e.dias[fecha];
        const d = parseISO(fecha);
        const cls = ['celda-turno'];
        const dia = claseDia(fecha, d);
        if (dia) cls.push(dia);
        let interior = '';
        let datos = '';
        let titulo = '';
        let arrastrable = '';
        if (t && t.CodigoNovedad) {
            // Sello: el turno se quitó de un cuadro confirmado; sigue visible en gris con las 2 letras del motivo.
            const motivo = E.motivos[t.CodigoNovedad];
            const tipoSello = E.tipoPorId[t.IdTipoTurno];
            cls.push('at-celda-sello');
            titulo = ' title="' + esc('Novedad: ' + (motivo ? motivo.Nombre : t.CodigoNovedad) + (tipoSello ? ' · turno ' + tipoSello.Codigo : '') +
                (t.NovedadPor ? ' · registró ' + t.NovedadPor : '') + (t.NovedadObservacion ? ' · ' + t.NovedadObservacion : '') +
                (t.NombreCubre ? ' · cubre ' + t.NombreCubre : ' · sin cobertura') + ' (clic para ver el detalle)') + '"';
            interior = '<span class="at-chip at-sello">' + esc(t.CodigoNovedad) + (t.NombreCubre ? '' : '<i class="at-sin-cobertura" title="Sin cobertura"></i>') + '</span>';
            datos = ' data-turno="' + esc(t.IdTurnoEnfermeria) + '" data-tipo="' + esc(t.IdTipoTurno) + '" data-sello="1"';
        } else if (t) {
            const tipo = E.tipoPorId[t.IdTipoTurno];
            const codigo = tipo ? tipo.Codigo : (t.TipoTurnoEnfermeria || '?');
            const estilo = tipo ? estiloTipo(tipo) : '--tc:#94a3b8;--tx:#1f2937';
            const ajeno = esAjeno(e, t);
            if (ajeno) {
                cls.push('at-ajeno');
                titulo = ' title="' + esc('Turno en ' + (t.NombreServicioTurno || 'otro servicio') + ': cámbialo o quítalo desde ese servicio') + '"';
            }
            if (t.IdTurnoOrigen && t.NombreOriginal && !ajeno) titulo = ' title="' + esc('Cobertura: cubre a ' + t.NombreOriginal) + '"';
            const cambiado = !!t.IdCambioTurno && !ajeno;
            if (cambiado) { cls.push('at-celda-cambio'); titulo = ' title="' + esc(textoCambio(t) + ' Para deshacerlo usa «Cambios de turno».') + '"'; }
            interior = '<span class="at-chip" style="' + estilo + '">' + esc(codigo) +
                (cambiado ? '<i class="at-cambio" aria-label="Turno intercambiado">&#8644;</i>' : '') +
                (String(t.EsExcepcion) === '1' && !ajeno && !t.IdTurnoOrigen ? '<i class="at-manual" title="Editado a mano: la generación automática no lo sobrescribe"></i>' : '') +
                (t.IdTurnoOrigen && !ajeno ? '<i class="at-cobertura" title="Cobertura de un compañero">&#8617;</i>' : '') +
                (ajeno ? '<i class="fas fa-lock at-candado"></i>' : '') + '</span>';
            datos = ' data-turno="' + esc(t.IdTurnoEnfermeria) + '" data-tipo="' + esc(t.IdTipoTurno) + '"';
            // Se puede arrastrar a otra celda (otra persona u otro día) con permiso de edición; los turnos de otro servicio no.
            if (!ajeno && !t.IdCambioTurno && puede('turnos.editar')) arrastrable = ' draggable="true"';
        }
        return '<td class="' + cls.join(' ') + '" data-enf="' + esc(e.IdEnfPer) + '" data-fecha="' + fecha + '"' + datos + arrastrable + titulo + '>' + interior + '</td>';
    }

    function celdaEl(idEnfPer, fecha) {
        return $('at-cuerpo').querySelector('td.celda-turno[data-enf="' + idEnfPer + '"][data-fecha="' + fecha + '"]');
    }

    function pieInterior(filas, dias) {
        const servicio = servicioParaCobertura();
        let html = '';
        E.tipos.forEach(t => {
            const req = servicio ? requeridoPara(servicio, t) : null;
            html += '<tr class="at-pie"><th class="nombre">' + chipTipo(t) + ' ' + esc(t.Nombre) +
                (req !== null ? ' <small>requeridos: ' + req + '</small>' : '') + '</th>';
            dias.forEach(f => {
                let n = 0;
                filas.forEach(e => { const x = e.dias[f]; if (x && !x.CodigoNovedad && !(E.servicio !== '' && esAjeno(e, x)) && String(x.IdTipoTurno) === String(t.IdTipoTurno)) n++; });
                const deficit = req !== null && req > 0 && n < req;
                const cls = (deficit ? 'at-deficit' : (n === 0 ? 'at-cero' : '')) + ' ' + claseDia(f, parseISO(f));
                html += '<td class="' + cls + '"' + (deficit ? ' title="Faltan ' + (req - n) + ' para cubrir el turno"' : '') + '>' + n + '</td>';
            });
            html += '</tr>';
        });
        return html;
    }

    function actualizarPie() {
        const tfoot = $('at-cuerpo').querySelector('tfoot');
        if (tfoot) tfoot.innerHTML = pieInterior(enfermerosFiltrados(), diasDelMes());
    }

    function tablaHTML() {
        const filas = enfermerosFiltrados();
        if (!filas.length) return vacioHTML();
        const dias = diasDelMes();
        const todos = E.servicio === '';

        const nS = {}, nP = {};
        filas.forEach(e => {
            const gs = 's' + e.IdServicio + (e.EsApoyo ? 'a' : ''), gp = gs + 'p' + e.IdPerfil;
            nS[gs] = (nS[gs] || 0) + 1;
            nP[gp] = (nP[gp] || 0) + 1;
        });

        let html = '<div class="at-tabla-wrap"><table class="grilla-turnos"><thead><tr><th class="nombre">Enfermero / Perfil</th>';
        dias.forEach(f => {
            const d = parseISO(f);
            html += '<th class="at-dia ' + claseDia(f, d) + '"' + (E.festivos[f] ? ' title="' + esc(E.festivos[f]) + '"' : '') + '>' +
                d.getDate() + '<small>' + DIAS_CORTO[d.getDay()] + '</small></th>';
        });
        html += '</tr></thead><tbody>';

        let ultimoS = null, ultimoP = null;
        const colspan = dias.length + 1;
        filas.forEach(e => {
            const gs = 's' + e.IdServicio + (e.EsApoyo ? 'a' : ''), gp = gs + 'p' + e.IdPerfil;
            if ((todos || e.EsApoyo) && gs !== ultimoS) {
                html += '<tr class="at-grupo at-grupo-s" data-g="' + gs + '"><td colspan="' + colspan + '"><div class="at-grupo-in">' +
                    '<button type="button" class="at-plegar" data-at-act="plegar" data-g="' + gs + '" aria-label="Plegar o desplegar servicio"><i class="fas fa-chevron-down"></i></button>' +
                    '<strong>' + (e.EsApoyo ? 'Apoyo de otros servicios' : esc(e.NombreServicio)) + '</strong><span class="at-cuenta">' + nS[gs] + '</span></div></td></tr>';
                ultimoS = gs;
                ultimoP = null;
            }
            if (gp !== ultimoP) {
                html += '<tr class="at-grupo at-grupo-p" data-gs="' + gs + '" data-g="' + gp + '"><td colspan="' + colspan + '"><div class="at-grupo-in">' +
                    '<button type="button" class="at-plegar" data-at-act="plegar" data-g="' + gp + '" aria-label="Plegar o desplegar perfil"><i class="fas fa-chevron-down"></i></button>' +
                    '<span>' + esc(e.DescripcionPerfil) + '</span><span class="at-cuenta">' + nP[gp] + '</span></div></td></tr>';
                ultimoP = gp;
            }
            html += '<tr class="at-fila" data-gs="' + gs + '" data-gp="' + gp + '"><td class="nombre"><strong>' + esc(e.NombreMedico) + '</strong><small>' + esc(e.DescripcionPerfil) +
                (e.EsApoyo ? ' · Principal: ' + esc(e.NombreServicioPrincipal || 'sin servicio') : '') + '</small></td>';
            dias.forEach(f => { html += celdaHTML(e, f); });
            html += '</tr>';
        });
        html += '</tbody><tfoot>' + pieInterior(filas, dias) + '</tfoot></table></div>';
        return html;
    }

    function aplicarPlegado() {
        const cuerpo = $('at-cuerpo');
        // Los encabezados de servicio solo existen cuando se ven todos los servicios: con uno solo, un
        // plegado antiguo del servicio no debe dejar sus filas ocultas sin forma de desplegarlas.
        const plegaServicio = E.servicio === '';
        cuerpo.querySelectorAll('tr[data-gs]').forEach(tr => {
            const esFila = tr.classList.contains('at-fila');
            // El grupo "Apoyo de otros servicios" (clave terminada en 'a') se pliega también viendo un solo servicio.
            const plegaEste = plegaServicio || /a$/.test(tr.dataset.gs);
            tr.hidden = !!((plegaEste && E.plegados[tr.dataset.gs]) || (esFila && E.plegados[tr.dataset.gp]));
        });
        cuerpo.querySelectorAll('.at-plegar').forEach(b => b.classList.toggle('plegado', !!E.plegados[b.dataset.g]));
    }

    // ---------------------------------------------------------------- selector de turno (en la celda)
    function cerrarSelector() {
        if (!E.selector) return;
        if (E.selector.ancla) E.selector.ancla.classList.remove('seleccionada');
        if (E.selector.pop) E.selector.pop.remove();
        E.selector = null;
    }

    function posicionarFlotante(pop, ancla, alinearIzquierda) {
        const r = ancla.getBoundingClientRect();
        pop.style.visibility = 'hidden';
        pop.style.left = '0px';
        pop.style.top = '0px';
        const w = pop.offsetWidth, h = pop.offsetHeight;
        const centrado = r.left + r.width / 2 - w / 2;
        let left = Math.min(Math.max(8, alinearIzquierda ? r.left : centrado), window.innerWidth - w - 8);
        let top = r.bottom + 6;
        if (top + h > window.innerHeight - 8) top = Math.max(8, r.top - h - 6);
        pop.style.left = left + 'px';
        pop.style.top = top + 'px';
        pop.style.visibility = '';
    }

    // Vista "Turnos reales": solo se consulta. Avisa y devuelve true si se intentó editar.
    function soloLectura() {
        if (!E.real) return false;
        toast('error', 'Estás viendo los turnos reales (quién cobra cada turno). Cambia a «Con cambios» para editar.');
        return true;
    }

    // Texto de la insignia ⇄ de un turno intercambiado (celda, punto del calendario y panel del día).
    function textoCambio(t) {
        if (!t || !t.IdCambioTurno) return '';
        const tipo = t.CambioTipo || '';
        const original = t.CambioFecha ? textoFecha(t.CambioFecha, true) + (tipo ? ' (' + tipo + ')' : '') : '';
        if (E.real) {
            return 'Cambio de turno aprobado: ' + (t.NombreEjecuta || 'otra persona') + ' trabaja este turno; ' + 'lo cobra su dueño original.';
        }
        return 'Cambio de turno aprobado: cubre el turno de ' + (t.NombrePago || 'otra persona') + (original ? '; el suyo original era ' + original : '') + '. El pago sigue siendo del turno original.';
    }

    function abrirSelector(ancla, ctx) {
        if (soloLectura()) return;
        cerrarSelector();
        const pop = document.createElement('div');
        pop.className = 'at-selector';
        pop.setAttribute('role', 'menu');
        pop.setAttribute('aria-label', 'Elegir turno');

        let opciones = '';
        if (!E.tipos.length) {
            opciones = '<p class="at-selector-vacio">No hay tipos de turno. Créalos en Esquemas &gt; Tipos de Turno.</p>';
        }
        E.tipos.forEach(t => {
            const actual = ctx.tipo !== null && String(ctx.tipo) === String(t.IdTipoTurno);
            opciones += '<button type="button" role="menuitem" class="at-op' + (actual ? ' actual' : '') + '" data-at-act="elegir" data-tipo="' + esc(t.IdTipoTurno) + '" data-letra="' + esc(t.Codigo) + '">' +
                chipTipo(t) + '<span class="at-op-txt"><b>' + esc(t.Nombre) + '</b><small>' + hhmm(t.HoraInicio) + ' – ' + hhmm(t.HoraFin) + '</small></span>' +
                (actual ? '<i class="fas fa-check"></i>' : '') + '</button>';
        });
        if (ctx.tipo !== null && puede('turnos.editar')) {
            // Alternativa a arrastrar (pantallas táctiles y teclado): se elige luego la celda destino.
            opciones += '<button type="button" role="menuitem" class="at-op" data-at-act="mover-inicio">' +
                '<span class="at-chip at-chip-quitar"><i class="fas fa-arrows-alt"></i></span><span class="at-op-txt"><b>Mover a otra celda…</b><small>Otra persona u otro día</small></span></button>';
        }
        if (ctx.tipo !== null) {
            opciones += '<button type="button" role="menuitem" class="at-op quitar" data-at-act="elegir" data-tipo="">' +
                '<span class="at-chip at-chip-quitar"><i class="fas fa-times"></i></span><span class="at-op-txt"><b>Quitar turno</b><small>Dejar el día libre</small></span></button>';
        }
        pop.innerHTML = '<div class="at-selector-cab"><strong>' + esc(ctx.nombre) + '</strong><small>' + esc(textoFecha(ctx.fecha, true)) + '</small></div>' + opciones;
        document.body.appendChild(pop);
        posicionarFlotante(pop, ancla, false);
        ancla.classList.add('seleccionada');
        E.selector = { ctx: ctx, ancla: ancla, pop: pop };
        const primero = pop.querySelector('.at-op.actual') || pop.querySelector('.at-op');
        if (primero) primero.focus({ preventScroll: true });
    }

    function contextoCelda(el) {
        const e = E.porEnfPer[el.dataset.enf];
        if (!e) return null;
        const t = e.dias[el.dataset.fecha];
        return { idEnfPer: el.dataset.enf, fecha: el.dataset.fecha, nombre: e.NombreMedico, tipo: t ? t.IdTipoTurno : null, idTurno: t ? t.IdTurnoEnfermeria : null,
            ajeno: esAjeno(e, t), servicioAjeno: t ? t.NombreServicioTurno : '', sello: t && t.CodigoNovedad ? t.CodigoNovedad : null };
    }

    // ---------------------------------------------------------------- guardar un turno
    function aplicarLocal(e, fecha, turno) {
        if (turno) e.dias[fecha] = turno; else delete e.dias[fecha];
    }

    function marcarGuardando(idEnfPer, fecha, on) {
        const td = celdaEl(idEnfPer, fecha);
        if (td) td.classList.toggle('guardando', on);
    }

    function refrescarTrasCambio(idEnfPer, fecha) {
        const e = E.porEnfPer[idEnfPer];
        if (E.vista === 'tabla') {
            const td = celdaEl(idEnfPer, fecha);
            if (td && e) td.outerHTML = celdaHTML(e, fecha);
            actualizarPie();
        } else {
            renderCuerpo();
        }
        if (E.diaAbierto) renderDiaModal();
    }

    // idTipo = null quita el turno. Actualiza la pantalla al instante y revierte con un aviso si el servidor falla.
    // Cuadro ya confirmado: quitar un turno exige motivo y cobertura (novedad) y cambiar su tipo exige una observación.
    // observacion: motivo del cambio de tipo (undefined = aún no se ha pedido).
    async function asignarTurno(idEnfPer, fecha, idTipo, observacion) {
        const e = E.porEnfPer[idEnfPer];
        if (!e) return;
        if (soloLectura()) return;
        const previo = e.dias[fecha] ? Object.assign({}, e.dias[fecha]) : null;
        if (previo && previo.CodigoNovedad) { abrirSello(idEnfPer, fecha); return; } // un día con sello se gestiona desde el sello
        if (previo && esAjeno(e, previo)) {
            toast('error', e.NombreMedico + ' tiene ese día un turno en ' + (previo.NombreServicioTurno || 'otro servicio') + ': cámbialo o quítalo desde ese servicio.');
            return;
        }
        if ((previo && idTipo !== null && String(previo.IdTipoTurno) === String(idTipo)) || (!previo && idTipo === null)) return;
        const clave = idEnfPer + '|' + fecha;
        if (E.pendientes[clave]) return;

        // Si ya se sabe que el cuadro está confirmado, se pide lo que falta ANTES de tocar la pantalla.
        if (previo && cuadroConfirmadoPara(e, fecha)) {
            if (idTipo === null) { await quitarConMotivo(e, previo, fecha); return; }
            if (observacion === undefined) {
                observacion = await pedirObservacionCambio(e, fecha, previo, idTipo);
                if (observacion === null) return;
            }
        }
        E.pendientes[clave] = true;
        let despues = null; // lo que el servidor pide hacer a continuación (motivo u observación), una vez libre el día

        const tipo = idTipo !== null ? E.tipoPorId[idTipo] : null;
        aplicarLocal(e, fecha, idTipo === null ? null : {
            IdTurnoEnfermeria: previo ? previo.IdTurnoEnfermeria : 0, IdEnfPer: idEnfPer, IdTipoTurno: idTipo,
            FechaTurnoEnfermeria: fecha, TipoTurnoEnfermeria: tipo ? tipo.Codigo : '', EsExcepcion: 1, IdEstado: 1,
            IdServicio: servicioContexto(e)
        });
        refrescarTrasCambio(idEnfPer, fecha);
        marcarGuardando(idEnfPer, fecha, true);

        try {
            const fd = new FormData();
            fd.append('IdEnfPer', idEnfPer);
            fd.append('Fecha', fecha);
            fd.append('IdServicio', servicioContexto(e)); // servicio desde el que se edita (principal o de apoyo)
            fd.append('IdTipoTurno', idTipo === null ? '' : idTipo);
            fd.append('EsExcepcion', '1'); // edición manual: la generación automática no la sobrescribe
            if (observacion) fd.append('Observacion', observacion);
            const resp = await pedirJSON(API_TURNOS + '?Accion=Asignar', { method: 'POST', body: fd });
            if (!resp.success && resp.requiereMotivo) {
                // El servidor sabe que el cuadro está confirmado (p. ej. un día fuera del mes que muestra la barra).
                aplicarLocal(e, fecha, previo);
                despues = () => quitarConMotivo(e, previo || { IdTurnoEnfermeria: resp.IdTurnoEnfermeria, IdTipoTurno: idTipo }, fecha);
            } else if (!resp.success && resp.requiereObservacion) {
                aplicarLocal(e, fecha, previo);
                despues = async () => {
                    const obs = await pedirObservacionCambio(e, fecha, previo, idTipo);
                    if (obs !== null) await asignarTurno(idEnfPer, fecha, idTipo, obs);
                };
            } else {
                if (!resp.success) throw new Error(resp.message || 'No se pudo guardar el turno');
                aplicarLocal(e, fecha, resp.turno || null);
            }
        } catch (err) {
            aplicarLocal(e, fecha, previo);
            toast('error', 'No se guardó el turno de ' + e.NombreMedico + ' (' + textoFecha(fecha) + '): ' + err.message);
        } finally {
            delete E.pendientes[clave];
            refrescarTrasCambio(idEnfPer, fecha);
        }
        if (despues) await despues();
    }

    // ¿El cuadro del servicio desde el que se edita está confirmado para esa fecha? Solo se sabe aquí para el mes que
    // muestra la barra del cuadro; para otras fechas (semanas que cruzan de mes) decide el servidor.
    function cuadroConfirmadoPara(e, fecha) {
        if (!window.CuadroTurnosUI || !window.CuadroTurnosUI.confirmado()) return false;
        const est = window.CuadroTurnosUI.estado();
        return !!est && String(est.IdServicio) === servicioContexto(e) &&
            Number(est.Anio) === Number(fecha.substring(0, 4)) && Number(est.Mes) === Number(fecha.substring(5, 7));
    }

    // Quitar un turno de un cuadro confirmado: motivo -> cobertura -> sello. Recarga la grilla si se registró.
    async function quitarConMotivo(e, turno, fecha) {
        if (!window.NovedadesTurnoUI) { toast('error', 'No se cargó la pantalla de novedades. Recarga la página.'); return; }
        const tipo = E.tipoPorId[turno.IdTipoTurno];
        const servicio = E.servicioPorId[servicioContexto(e)];
        const hecho = await window.NovedadesTurnoUI.quitar({
            idTurno: turno.IdTurnoEnfermeria, nombre: e.NombreMedico, fecha: fecha,
            tipoCodigo: tipo ? tipo.Codigo : '', tipoNombre: tipo ? tipo.Nombre : '', servicioNombre: servicio ? servicio.NombreServicio : ''
        });
        if (hecho) cargar();
    }

    // Detalle de un sello (día con novedad) con sus acciones: asignar cobertura, deshacer.
    async function abrirSello(idEnfPer, fecha) {
        const e = E.porEnfPer[idEnfPer];
        const t = e ? e.dias[fecha] : null;
        if (!e || !t || !t.CodigoNovedad || !window.NovedadesTurnoUI) return;
        const tipo = E.tipoPorId[t.IdTipoTurno];
        const motivo = E.motivos[t.CodigoNovedad];
        const hecho = await window.NovedadesTurnoUI.sello({
            idTurno: t.IdTurnoEnfermeria, nombre: e.NombreMedico, fecha: fecha,
            tipoCodigo: tipo ? tipo.Codigo : '', tipoNombre: tipo ? tipo.Nombre : '', servicioNombre: t.NombreServicioTurno || '',
            codigoNovedad: t.CodigoNovedad, motivoNombre: motivo ? motivo.Nombre : t.CodigoNovedad, observacion: t.NovedadObservacion,
            por: t.NovedadPor, fechaNovedad: t.NovedadFecha, cubre: t.NombreCubre || '',
            puedeCobertura: puede('turnos.novedad'), puedeRevertir: puede('turnos.novedad_revertir')
        });
        if (hecho) cargar();
    }

    // Cambiar el tipo de un turno de un cuadro confirmado (D -> N, etc.) pide el motivo; queda en la bitácora y se avisa al enfermero.
    async function pedirObservacionCambio(e, fecha, previo, idTipo) {
        const desde = E.tipoPorId[previo.IdTipoTurno], hacia = E.tipoPorId[idTipo];
        const r = await Swal.fire({
            title: 'Cambiar un turno confirmado',
            html: '<b>' + esc(e.NombreMedico) + '</b> · ' + esc(textoFecha(fecha, true)) + '<br>' + esc(desde ? desde.Codigo : '?') + ' &rarr; ' + esc(hacia ? hacia.Codigo : '?') +
                '<br><small>El cuadro ya está confirmado: se avisa al enfermero y el cambio queda en la bitácora.</small>',
            input: 'textarea', inputLabel: 'Motivo del cambio', inputPlaceholder: 'Ejemplo: cubrir una ausencia en la noche',
            showCancelButton: true, confirmButtonText: 'Cambiar turno', cancelButtonText: 'Cancelar',
            inputValidator: v => (!v || v.trim().length < 5) ? 'Escribe el motivo (mínimo 5 caracteres)' : undefined
        });
        return r.isConfirmed ? r.value.trim() : null;
    }

    function toast(tipo, texto) {
        let zona = $('at-toasts');
        if (!zona) {
            zona = document.createElement('div');
            zona.id = 'at-toasts';
            document.body.appendChild(zona);
        }
        const t = document.createElement('div');
        t.className = 'at-toast ' + tipo;
        t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        t.textContent = texto;
        zona.appendChild(t);
        setTimeout(() => { t.classList.add('saliendo'); setTimeout(() => t.remove(), 300); }, tipo === 'error' ? 7000 : 2500);
    }

    // ---------------------------------------------------------------- calendario
    function puntoHTML(e, t) {
        const tipo = E.tipoPorId[t.IdTipoTurno];
        const forma = tipo ? tipo._i % 6 : 0;
        const atenuado = E.resaltado !== null && String(E.resaltado) !== String(e.IdMedico) ? ' atenuado' : '';
        const cambio = t.IdCambioTurno ? ' at-punto-cambio' : '';
        const titulo = e.NombreMedico + ' · ' + (tipo ? tipo.Codigo + ' ' + tipo.Nombre : 'turno') + (t.IdCambioTurno ? ' · ⇄ ' + textoCambio(t) : '');
        return '<span class="at-punto f' + forma + ' v' + (Number(e.IdMedico) % 3) + atenuado + cambio + '" style="--h:' + matiz(e.IdMedico) + '" title="' + esc(titulo) + '"></span>';
    }

    function puntosHTML(lista) {
        const orden = lista.slice().sort((a, b) => {
            const ta = E.tipoPorId[a.t.IdTipoTurno], tb = E.tipoPorId[b.t.IdTipoTurno];
            return ((ta ? ta._i : 99) - (tb ? tb._i : 99)) || porNombre(a.e, b.e);
        });
        let html = orden.slice(0, MAX_PUNTOS).map(x => puntoHTML(x.e, x.t)).join('');
        if (orden.length > MAX_PUNTOS) html += '<span class="at-mas">+' + (orden.length - MAX_PUNTOS) + '</span>';
        return html;
    }

    function calendarioHTML() {
        if (!enfermerosFiltrados().length) return vacioHTML();
        let principal;
        if (E.sub === 'semana') principal = calSemanaHTML();
        else if (E.sub === 'dia') principal = calDiaHTML();
        else principal = calMesHTML();
        return '<div class="at-cal"><div class="at-cal-principal">' + principal + '</div>' + leyendaHTML() + '</div>';
    }

    function calMesHTML() {
        const y = E.ref.getFullYear(), m = E.ref.getMonth();
        const inicio = inicioSemana(new Date(y, m, 1, 12, 0, 0));
        const fin = addDias(inicioSemana(new Date(y, m + 1, 0, 12, 0, 0)), 6);
        const porDia = turnosPorDia(enfermerosFiltrados());
        const hoyISO = iso(hoyDate());
        let html = '<div class="at-mes"><div class="at-mes-cab">' + DIAS_CORTO.map(d => '<div>' + d + '</div>').join('') + '</div><div class="at-mes-grid">';
        for (let d = new Date(inicio); d <= fin; d = addDias(d, 1)) {
            const f = iso(d);
            const lista = porDia[f] || [];
            const cls = ['at-mes-celda'];
            if (d.getMonth() !== m) cls.push('fuera');
            if (f === hoyISO) cls.push('hoy');
            if (E.festivos[f]) cls.push('festivo');
            if (E.diaAbierto === f) cls.push('abierto');
            html += '<div class="' + cls.join(' ') + '" role="button" tabindex="0" data-at-act="abrir-dia" data-fecha="' + f + '" aria-label="' +
                esc(textoFecha(f, true)) + ': ' + lista.length + ' turnos">' +
                '<span class="at-mes-num">' + d.getDate() + '</span>' +
                (E.festivos[f] ? '<span class="at-festivo" title="' + esc(E.festivos[f]) + '">F</span>' : '') +
                '<div class="at-puntos">' + puntosHTML(lista) + '</div></div>';
        }
        return html + '</div></div>';
    }

    function calSemanaHTML() {
        const ini = inicioSemana(E.ref);
        const porDia = turnosPorDia(enfermerosFiltrados());
        const hoyISO = iso(hoyDate());
        let html = '<div class="at-semana">';
        for (let i = 0; i < 7; i++) {
            const d = addDias(ini, i), f = iso(d);
            const lista = porDia[f] || [];
            html += '<div class="at-sem-col' + (f === hoyISO ? ' hoy' : '') + (E.festivos[f] ? ' festivo' : '') + '">' +
                '<button type="button" class="at-sem-cab" data-at-act="abrir-dia" data-fecha="' + f + '" title="Abrir el día"><span>' + DIAS_CORTO[d.getDay()] + '</span><b>' + d.getDate() + '</b>' +
                (E.festivos[f] ? '<i title="' + esc(E.festivos[f]) + '">F</i>' : '') + '</button>';
            E.tipos.forEach(t => {
                const delTipo = lista.filter(x => String(x.t.IdTipoTurno) === String(t.IdTipoTurno)).sort(porNombreItem);
                html += '<div class="at-sem-turno" style="' + estiloTipo(t) + '"><div class="at-sem-turno-cab">' + chipTipo(t) +
                    '<span>' + esc(t.Nombre) + '</span><span class="at-cuenta">' + delTipo.length + '</span></div>' +
                    delTipo.map(x => '<button type="button" class="at-sem-nom" data-at-act="celda" data-enf="' + esc(x.e.IdEnfPer) + '" data-fecha="' + f + '" title="' +
                        esc(x.e.NombreMedico + ' · ' + x.e.DescripcionPerfil) + '">' + puntoHTML(x.e, x.t) + '<span>' + esc(x.e.NombreMedico) + '</span></button>').join('') +
                    '</div>';
            });
            html += '</div>';
        }
        return html + '</div>';
    }

    function calDiaHTML() {
        const f = iso(E.ref);
        const d = E.ref;
        return '<div class="at-dia-vista"><div class="at-dia-cab"><span class="cal-dia-num">' + d.getDate() + '</span><span class="cal-dia-texto">' +
            DIAS_LARGO[d.getDay()] + '<small>' + MESES[d.getMonth()] + ' ' + d.getFullYear() + (E.festivos[f] ? ' · ' + esc(E.festivos[f]) : '') + '</small></span></div>' +
            diaCuerpoHTML(f) + '</div>';
    }

    function leyendaHTML() {
        const rango = rangoVista();
        const items = [];
        enfermerosFiltrados().forEach(e => {
            const c = {};
            let total = 0;
            Object.keys(e.dias).forEach(f => {
                if (E.servicio !== '' && esAjeno(e, e.dias[f])) return;
                if (e.dias[f].CodigoNovedad) return;
                if (f >= rango[0] && f <= rango[1]) { c[e.dias[f].IdTipoTurno] = (c[e.dias[f].IdTipoTurno] || 0) + 1; total++; }
            });
            if (total) items.push({ e: e, c: c });
        });
        items.sort(porNombreItem);
        let html = '<aside class="at-leyenda" aria-label="Leyenda"><h4>Enfermeros <span class="at-cuenta">' + items.length + '</span></h4><div class="at-ley-lista">';
        if (!items.length) html += '<p class="cal-vacio">Nadie tiene turnos en este período.</p>';
        items.forEach(it => {
            const resumen = E.tipos.filter(t => it.c[t.IdTipoTurno]).map(t => t.Codigo + it.c[t.IdTipoTurno]).join(' ');
            html += '<button type="button" class="at-ley-item' + (E.resaltado !== null && String(E.resaltado) === String(it.e.IdMedico) ? ' activo' : '') + '" data-at-act="resaltar" data-med="' + esc(it.e.IdMedico) + '" title="Resaltar a este enfermero">' +
                '<span class="at-punto f0 v' + (Number(it.e.IdMedico) % 3) + '" style="--h:' + matiz(it.e.IdMedico) + '"></span>' +
                '<span class="at-ley-nom">' + esc(it.e.NombreMedico) + '</span><small>' + esc(resumen) + '</small></button>';
        });
        html += '</div><div class="at-ley-formas">' +
            E.tipos.map(t => '<span><span class="at-punto f' + (t._i % 6) + ' neutro"></span> ' + esc(t.Codigo) + ' ' + esc(t.Nombre) + '</span>').join('') + '</div></aside>';
        return html;
    }

    // ---------------------------------------------------------------- panel del día (modal y vista Día)
    function filaDiaHTML(e, f, t) {
        if (t && t.CodigoNovedad) {
            const motivo = E.motivos[t.CodigoNovedad];
            return '<div class="cal-fila" data-nombre="' + esc(normalizar(e.NombreMedico)) + '">' +
                '<span class="cal-barra" style="--h:' + matiz(e.IdMedico) + '"></span>' +
                '<div class="cal-info"><strong>' + esc(e.NombreMedico) + '</strong><small>' + esc(e.DescripcionPerfil) + ' · ' + esc(motivo ? motivo.Nombre : t.CodigoNovedad) + '</small></div>' +
                '<div class="cal-chips"><button type="button" class="at-mini at-mini-sello" data-at-act="sello" data-enf="' + esc(e.IdEnfPer) + '" data-fecha="' + f +
                '" title="Ver el detalle de la novedad"><span class="at-chip at-sello">' + esc(t.CodigoNovedad) + '</span></button>' +
                '<span class="at-en-otro">' + (t.NombreCubre ? 'Cubre: ' + esc(t.NombreCubre) : 'Sin cobertura') + '</span></div></div>';
        }
        if (t && esAjeno(e, t)) {
            const tipoAjeno = E.tipoPorId[t.IdTipoTurno];
            return '<div class="cal-fila ajena" data-nombre="' + esc(normalizar(e.NombreMedico)) + '">' +
                '<span class="cal-barra" style="--h:' + matiz(e.IdMedico) + '"></span>' +
                '<div class="cal-info"><strong>' + esc(e.NombreMedico) + '</strong><small>' + esc(e.DescripcionPerfil) + '</small></div>' +
                '<div class="cal-chips">' + (tipoAjeno ? chipTipo(tipoAjeno) : '') + '<span class="at-en-otro"><i class="fas fa-lock"></i> ' + esc(t.NombreServicioTurno || 'otro servicio') + '</span></div></div>';
        }
        if (t && t.IdCambioTurno) {
            // Turno intercambiado: no se edita aquí (primero se deshace el cambio) y se explica quién lo trabaja y quién lo cobra.
            const tipoCambio = E.tipoPorId[t.IdTipoTurno];
            return '<div class="cal-fila cambiado" data-nombre="' + esc(normalizar(e.NombreMedico)) + '">' +
                '<span class="cal-barra" style="--h:' + matiz(e.IdMedico) + '"></span>' +
                '<div class="cal-info"><strong>' + esc(e.NombreMedico) + '</strong><small>' + esc(e.DescripcionPerfil) + '</small></div>' +
                '<div class="cal-chips">' + (tipoCambio ? chipTipo(tipoCambio) : '') + '<span class="at-en-otro"><i class="at-cambio">&#8644;</i> ' + esc(textoCambio(t)) + '</span></div></div>';
        }
        const chips = E.tipos.map(tp =>
            '<button type="button" class="at-mini' + (t && String(t.IdTipoTurno) === String(tp.IdTipoTurno) ? ' activo' : '') + '" data-at-act="asignar" data-enf="' + esc(e.IdEnfPer) +
            '" data-fecha="' + f + '" data-tipo="' + esc(tp.IdTipoTurno) + '" style="' + estiloTipo(tp) + '" title="' + esc(tp.Nombre) + '">' + esc(tp.Codigo) + '</button>').join('');
        const quitar = t ? '<button type="button" class="at-mini quitar" data-at-act="asignar" data-enf="' + esc(e.IdEnfPer) + '" data-fecha="' + f + '" data-tipo="" title="Quitar turno" aria-label="Quitar turno"><i class="fas fa-times"></i></button>' : '';
        return '<div class="cal-fila' + (t && String(t.EsExcepcion) === '1' ? ' manual' : '') + '" data-nombre="' + esc(normalizar(e.NombreMedico)) + '">' +
            '<span class="cal-barra" style="--h:' + matiz(e.IdMedico) + '"></span>' +
            '<div class="cal-info"><strong>' + esc(e.NombreMedico) + '</strong><small>' + esc(e.DescripcionPerfil) + (E.servicio === '' ? ' · ' + esc(e.NombreServicio) : '') + '</small></div>' +
            '<div class="cal-chips">' + chips + quitar + '</div></div>';
    }

    function diaCuerpoHTML(f) {
        const filas = enfermerosFiltrados();
        const conTurno = [], sinTurno = [], enOtro = [], conNovedad = [];
        filas.forEach(e => {
            const t = e.dias[f];
            // Viendo un servicio, quien ese día trabaja en otro no cuenta aquí ni está "sin turno": se lista aparte.
            if (t && E.servicio !== '' && esAjeno(e, t)) enOtro.push({ e: e, t: t });
            else if (t && t.CodigoNovedad) conNovedad.push({ e: e, t: t }); // sello: no trabaja ni está "sin turno"
            else (t ? conTurno : sinTurno).push({ e: e, t: t });
        });
        const servicio = servicioParaCobertura();
        let html = '';
        const filtros = textoFiltros();
        if (filtros) html += '<p class="cal-filtros"><i class="fas fa-filter"></i> ' + esc(filtros) + '</p>';

        E.tipos.forEach(t => {
            const lista = conTurno.filter(x => String(x.t.IdTipoTurno) === String(t.IdTipoTurno)).sort(porNombreItem);
            const req = servicio ? requeridoPara(servicio, t) : null;
            const falta = req !== null && req > 0 && lista.length < req;
            html += '<section class="cal-turno" style="' + estiloTipo(t) + '"><h4>' + chipTipo(t) + '<span>' + esc(t.Nombre) + '</span><small>' + hhmm(t.HoraInicio) + ' – ' + hhmm(t.HoraFin) + '</small>' +
                '<span class="at-cuenta' + (falta ? ' deficit' : '') + '"' + (req !== null ? ' title="Requeridos: ' + req + '"' : '') + '>' + lista.length + (req !== null ? ' / ' + req : '') + '</span></h4>';
            if (!lista.length) html += '<p class="cal-vacio">Nadie asignado a este turno.</p>';
            lista.forEach(x => { html += filaDiaHTML(x.e, f, x.t); });
            html += '</section>';
        });

        if (conNovedad.length) {
            html += '<section class="cal-turno cal-novedad"><h4><i class="fas fa-stamp"></i><span>Con novedad</span><span class="at-cuenta">' + conNovedad.length + '</span></h4>' +
                conNovedad.sort(porNombreItem).map(x => filaDiaHTML(x.e, f, x.t)).join('') + '</section>';
        }

        if (enOtro.length) {
            html += '<section class="cal-turno cal-en-otro"><h4><i class="fas fa-lock"></i><span>En otro servicio</span><span class="at-cuenta">' + enOtro.length + '</span></h4>' +
                enOtro.sort(porNombreItem).map(x => filaDiaHTML(x.e, f, x.t)).join('') + '</section>';
        }

        html += '<section class="cal-sin"><button type="button" class="cal-sin-tit" data-at-act="toggle-sin" aria-expanded="' + E.sinAbierto + '">' +
            '<i class="fas fa-chevron-' + (E.sinAbierto ? 'down' : 'right') + '"></i> Sin turno este día <span class="at-cuenta">' + sinTurno.length + '</span>' +
            '<small>' + (E.sinAbierto ? '' : 'Desplegar para asignar') + '</small></button>';
        if (E.sinAbierto) {
            html += '<div class="cal-sin-cuerpo"><input type="search" class="form-control cal-sin-buscar" placeholder="Buscar enfermero…" data-at-sin-buscar autocomplete="off">' +
                '<div class="cal-sin-lista">' + sinTurno.sort(porNombreItem).map(x => filaDiaHTML(x.e, f, null)).join('') + '</div></div>';
        }
        return html + '</section>';
    }

    function aplicarBusquedaSin() {
        const q = normalizar(E.sinQ.trim());
        document.querySelectorAll('.cal-sin-lista .cal-fila').forEach(fila => {
            fila.hidden = !!q && fila.dataset.nombre.indexOf(q) === -1;
        });
    }

    function buscabaEnSin() {
        const a = document.activeElement;
        return !!(a && a.hasAttribute && a.hasAttribute('data-at-sin-buscar'));
    }

    // Tras repintar el panel del día (modal o vista Día) se restituye lo escrito en "Sin turno" y su foco.
    function restaurarBusquedaSin(contenedor, enfocar) {
        const input = contenedor.querySelector('[data-at-sin-buscar]');
        if (!input) return;
        input.value = E.sinQ;
        aplicarBusquedaSin();
        if (enfocar) { input.focus(); input.setSelectionRange(input.value.length, input.value.length); }
    }

    function renderDiaModal() {
        const f = E.diaAbierto;
        if (!f) return;
        const d = parseISO(f);
        $('cal-dia-num').textContent = d.getDate();
        $('cal-dia-nombre').textContent = DIAS_LARGO[d.getDay()];
        $('cal-dia-mes').textContent = MESES[d.getMonth()] + ' ' + d.getFullYear() + (E.festivos[f] ? ' · ' + E.festivos[f] : '');
        const cuerpo = $('cal-dia-cuerpo');
        const buscaba = buscabaEnSin();
        const scroll = cuerpo.scrollTop;
        cuerpo.innerHTML = diaCuerpoHTML(f);
        cuerpo.scrollTop = scroll;
        restaurarBusquedaSin(cuerpo, buscaba);
    }

    function abrirDia(f) {
        E.diaAbierto = f;
        E.sinAbierto = false;
        E.sinQ = '';
        renderDiaModal();
        showModal('dia-turnos-modal');
        if (E.vista === 'calendario' && E.sub === 'mes') renderCuerpo(); // marca el día abierto
    }

    function cerrarDia() {
        if (!E.diaAbierto) return;
        E.diaAbierto = null;
        hideModal('dia-turnos-modal');
        cerrarSelector();
        if (E.vista === 'calendario' && E.sub === 'mes') renderCuerpo();
    }

    // ---------------------------------------------------------------- navegación y selector de período
    function cerrarPicker() {
        if (!E.picker) return;
        E.picker.remove();
        E.picker = null;
        const btn = $('at-periodo-btn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function pickerHTML() {
        return '<div class="at-picker-cab"><button type="button" data-at-act="picker-anio" data-dir="-1" aria-label="Año anterior"><i class="fas fa-chevron-left"></i></button>' +
            '<strong>' + E.pickerAnio + '</strong><button type="button" data-at-act="picker-anio" data-dir="1" aria-label="Año siguiente"><i class="fas fa-chevron-right"></i></button></div>' +
            '<div class="at-picker-meses">' + MESES.map((m, i) =>
                '<button type="button" data-at-act="picker-mes" data-mes="' + i + '" class="' + (E.pickerAnio === E.ref.getFullYear() && i === E.ref.getMonth() ? 'activo' : '') + '">' + cap(m.substring(0, 3)) + '</button>').join('') + '</div>';
    }

    function abrirPicker(btn) {
        if (E.picker) { cerrarPicker(); return; }
        cerrarSelector();
        E.pickerAnio = E.ref.getFullYear();
        const p = document.createElement('div');
        p.className = 'at-picker';
        p.innerHTML = pickerHTML();
        document.body.appendChild(p);
        posicionarFlotante(p, btn, true);
        E.picker = p;
        btn.setAttribute('aria-expanded', 'true');
    }

    function mesClave(d) { return d.getFullYear() * 12 + d.getMonth(); }

    function cambiarReferencia(nueva) {
        const antes = mesClave(E.ref);
        E.ref = nueva;
        cerrarSelector();
        cerrarPicker();
        if (mesClave(E.ref) !== antes || !E.data) cargar(); else render();
    }

    function navegar(dir) {
        if (E.vista === 'calendario' && E.sub === 'semana') cambiarReferencia(addDias(E.ref, 7 * dir));
        else if (E.vista === 'calendario' && E.sub === 'dia') cambiarReferencia(addDias(E.ref, dir));
        else cambiarReferencia(addMeses(E.ref, dir));
    }

    // ---------------------------------------------------------------- mover un turno a otra celda (arrastrar y soltar)
    // Destino válido: celda vacía (sin turno, sello ni turno de otro servicio), distinta del origen y del mismo servicio.
    // El servidor lo vuelve a validar (servicio, cruces, cuadro confirmado).
    function destinoValido(origen, td) {
        if (!origen || !td) return false;
        const eDest = E.porEnfPer[td.dataset.enf];
        const eOrig = E.porEnfPer[origen.idEnfPer];
        if (!eDest || !eOrig) return false;
        if (String(td.dataset.enf) === String(origen.idEnfPer) && td.dataset.fecha === origen.fecha) return false;
        if (eDest.dias[td.dataset.fecha]) return false;
        if (servicioContexto(eDest) !== servicioContexto(eOrig)) return false;
        if (E.pendientes[td.dataset.enf + '|' + td.dataset.fecha]) return false;
        return true;
    }

    function limpiarMarcasMover() {
        document.querySelectorAll('.celda-turno.arrastrando, .celda-turno.destino-ok, .celda-turno.destino-no, .celda-turno.mover-origen')
            .forEach(td => td.classList.remove('arrastrando', 'destino-ok', 'destino-no', 'mover-origen'));
    }

    function marcarDestino(td, ok) {
        if (E.destinoMarcado && E.destinoMarcado !== td) E.destinoMarcado.classList.remove('destino-ok', 'destino-no');
        td.classList.toggle('destino-ok', ok);
        td.classList.toggle('destino-no', !ok);
        E.destinoMarcado = td;
    }

    function celdaDeEvento(ev) {
        const td = ev.target && ev.target.closest ? ev.target.closest('td.celda-turno') : null;
        return td && $('at-cuerpo') && $('at-cuerpo').contains(td) ? td : null;
    }

    function onDragStart(ev) {
        const td = celdaDeEvento(ev);
        if (!td || td.getAttribute('draggable') !== 'true' || !td.dataset.turno) return;
        E.arrastre = { idEnfPer: td.dataset.enf, fecha: td.dataset.fecha };
        cerrarSelector();
        if (ev.dataTransfer) {
            ev.dataTransfer.effectAllowed = 'move';
            ev.dataTransfer.setData('text/plain', td.dataset.turno);
        }
        td.classList.add('arrastrando');
    }

    function onDragOver(ev) {
        if (!E.arrastre) return;
        const td = celdaDeEvento(ev);
        if (!td) return;
        const ok = destinoValido(E.arrastre, td);
        if (ok) {
            ev.preventDefault();
            if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'move';
        }
        marcarDestino(td, ok);
    }

    function onDrop(ev) {
        if (!E.arrastre) return;
        const td = celdaDeEvento(ev);
        const origen = E.arrastre;
        E.arrastre = null;
        limpiarMarcasMover();
        if (!td) return;
        ev.preventDefault();
        if (destinoValido(origen, td)) moverTurno(origen, td.dataset.enf, td.dataset.fecha);
        else toast('error', 'Ese destino no es válido: debe ser una celda vacía del mismo servicio.');
    }

    function onDragEnd() {
        E.arrastre = null;
        limpiarMarcasMover();
    }

    // Modo "Mover…" (táctil o teclado): se elige la celda de origen en el selector y luego se hace clic en el destino.
    function iniciarMover(ctx) {
        if (soloLectura()) return;
        E.moviendo = { idEnfPer: ctx.idEnfPer, fecha: ctx.fecha };
        const td = celdaEl(ctx.idEnfPer, ctx.fecha);
        if (td) td.classList.add('mover-origen');
        $('at-aviso').innerHTML = '<div class="at-mover-aviso"><i class="fas fa-arrows-alt"></i> Moviendo el turno de <b>' + esc(ctx.nombre) + '</b> del ' + esc(textoFecha(ctx.fecha, true)) +
            ': haz clic en la celda destino (vacía, del mismo servicio). <button type="button" data-at-act="cancelar-mover">Cancelar</button></div>';
    }

    function cancelarMover() {
        E.moviendo = null;
        limpiarMarcasMover();
        if ($('at-aviso')) $('at-aviso').innerHTML = '';
    }

    function destinoDeMover(td) {
        const origen = E.moviendo;
        if (destinoValido(origen, td)) {
            cancelarMover();
            moverTurno(origen, td.dataset.enf, td.dataset.fecha);
        } else {
            toast('error', 'Ese destino no es válido: elige una celda vacía del mismo servicio.');
        }
    }

    async function moverTurno(origen, idEnfPerDestino, fecha, codigo, obs) {
        const eOrig = E.porEnfPer[origen.idEnfPer], eDest = E.porEnfPer[idEnfPerDestino];
        const t = eOrig ? eOrig.dias[origen.fecha] : null;
        if (!eOrig || !eDest || !t) return;
        const clave = origen.idEnfPer + '|' + origen.fecha;
        if (E.pendientes[clave]) return;
        E.pendientes[clave] = true;
        marcarGuardando(origen.idEnfPer, origen.fecha, true);
        try {
            const fd = new FormData();
            fd.append('IdTurno', t.IdTurnoEnfermeria);
            fd.append('IdEnfPerDestino', idEnfPerDestino);
            fd.append('Fecha', fecha);
            if (codigo) fd.append('Codigo', codigo);
            if (obs) fd.append('Observacion', obs);
            const resp = await pedirJSON(API_NOVEDAD + '?Accion=Mover', { method: 'POST', body: fd });
            if (!resp.success && resp.requiereMotivo) {
                // Cuadro confirmado: el origen queda con sello, así que hace falta el motivo.
                delete E.pendientes[clave];
                marcarGuardando(origen.idEnfPer, origen.fecha, false);
                if (!window.NovedadesTurnoUI) throw new Error('No se cargó la pantalla de novedades. Recarga la página.');
                const tipo = E.tipoPorId[t.IdTipoTurno];
                const servicio = E.servicioPorId[servicioContexto(eOrig)];
                const m = await window.NovedadesTurnoUI.motivoParaMover({
                    nombre: eOrig.NombreMedico + (eOrig === eDest ? '' : ' → ' + eDest.NombreMedico), fecha: origen.fecha,
                    tipoCodigo: tipo ? tipo.Codigo : '', tipoNombre: tipo ? tipo.Nombre : '', servicioNombre: servicio ? servicio.NombreServicio : ''
                });
                if (m) await moverTurno(origen, idEnfPerDestino, fecha, m.codigo, m.obs);
                return;
            }
            if (!resp.success) throw new Error(resp.message || 'No se pudo mover el turno');
            toast('ok', 'Turno movido a ' + eDest.NombreMedico + ' el ' + textoFecha(fecha) + '.');
            await cargar();
        } catch (err) {
            toast('error', 'No se movió el turno: ' + err.message);
        } finally {
            delete E.pendientes[clave];
            marcarGuardando(origen.idEnfPer, origen.fecha, false);
        }
    }

    // ---------------------------------------------------------------- generación automática
    async function generar() {
        if (E.servicio === '') {
            Swal.fire('Falta el servicio', 'Elige un servicio para generar sus turnos.', 'warning');
            return;
        }
        const servicio = E.servicioPorId[E.servicio];
        const anio = E.ref.getFullYear(), mes = E.ref.getMonth() + 1;

        const confirmacion = await Swal.fire({
            title: '¿Generar turnos automáticamente?',
            html: 'Se asignarán turnos día/noche para cubrir <b>' + esc(servicio ? servicio.NombreServicio : '') + '</b> en <b>' + MESES[mes - 1] + ' de ' + anio +
                '</b>, respetando horas máximas semanales, noches y turnos consecutivos por empresa.<br><br>' +
                '<label style="display:flex;gap:8px;align-items:center;justify-content:center"><input type="checkbox" id="chk-sobrescribir"> Sobrescribir los turnos ya generados (no toca los cambios hechos a mano)</label>',
            showCancelButton: true,
            confirmButtonText: 'Generar',
            cancelButtonText: 'Cancelar',
            // Se lee aquí porque el diálogo ya no existe cuando la promesa se resuelve.
            preConfirm: () => !!(document.getElementById('chk-sobrescribir') || {}).checked
        });
        if (!confirmacion.isConfirmed) return;

        const fd = new FormData();
        fd.append('IdServicio', E.servicio);
        fd.append('Anio', anio);
        fd.append('Mes', mes);
        fd.append('Sobrescribir', confirmacion.value ? '1' : '0');

        Swal.fire({ title: 'Generando...', text: 'Puede tardar unos segundos.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let resp;
        try {
            resp = await pedirJSON(API_GENERADOR + '?Accion=Generar', { method: 'POST', body: fd });
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
            return;
        }
        if (!resp.success) {
            Swal.fire('Error', resp.message || 'No se pudo generar', 'error');
            return;
        }

        const alertas = Array.isArray(resp.alertas) ? resp.alertas : [];
        let mensaje = 'Se crearon ' + resp.creados + ' turnos.';
        if (resp.conApoyo > 0) mensaje += ' ' + resp.conApoyo + ' se cubrieron con personal de apoyo de otros servicios.';
        if (alertas.length > 0) {
            mensaje += '<br><br><strong>Avisos de cobertura incompleta (' + alertas.length + '):</strong><ul style="text-align:left;max-height:200px;overflow-y:auto">' +
                alertas.slice(0, 30).map(a => '<li>' + esc(a) + '</li>').join('') + '</ul>';
            if (alertas.length > 30) mensaje += '<em>...y ' + (alertas.length - 30) + ' más.</em>';
        }
        await Swal.fire({ title: 'Generación completada', html: mensaje, icon: alertas.length > 0 ? 'warning' : 'success' });
        cargar();
    }

    // ---------------------------------------------------------------- eventos
    function onClick(ev) {
        const celda = ev.target.closest('td.celda-turno');
        if (E.moviendo && celda && $('at-cuerpo') && $('at-cuerpo').contains(celda)) { destinoDeMover(celda); return; }
        if (celda && $('at-cuerpo') && $('at-cuerpo').contains(celda)) {
            if (E.selector && E.selector.ancla === celda) { cerrarSelector(); return; }
            const ctx = contextoCelda(celda);
            if (ctx && ctx.ajeno) { cerrarSelector(); toast('error', ctx.nombre + ' tiene ese día un turno en ' + (ctx.servicioAjeno || 'otro servicio') + ': cámbialo o quítalo desde ese servicio.'); return; }
            if (ctx && ctx.sello) { cerrarSelector(); abrirSello(ctx.idEnfPer, ctx.fecha); return; }
            if (ctx) abrirSelector(celda, ctx);
            return;
        }

        const el = ev.target.closest('[data-at-act]');
        if (!el) return;
        const act = el.dataset.atAct;

        switch (act) {
            case 'anterior': navegar(-1); break;
            case 'siguiente': navegar(1); break;
            case 'hoy': cambiarReferencia(hoyDate()); break;
            case 'selector-periodo': abrirPicker(el); break;
            case 'picker-anio':
                E.pickerAnio += Number(el.dataset.dir);
                if (E.picker) E.picker.innerHTML = pickerHTML();
                break;
            case 'picker-mes': {
                const mes = Number(el.dataset.mes);
                cambiarReferencia(new Date(E.pickerAnio, mes, Math.min(E.ref.getDate(), ultimoDiaMes(E.pickerAnio, mes)), 12, 0, 0));
                break;
            }
            case 'vista':
                E.vista = el.dataset.vista;
                cerrarSelector();
                cerrarDia();
                render();
                break;
            case 'real':
                E.real = el.dataset.real === '1';
                E.moviendo = null; $('at-aviso').innerHTML = '';
                cerrarSelector();
                cargar(); // se vuelve a pedir para que el otro mapa refleje cualquier edición hecha en esta pantalla
                break;
            case 'subvista':
                E.sub = el.dataset.sub;
                guardarPrefs();
                cerrarSelector();
                render();
                break;
            case 'perfil':
                E.perfil = el.dataset.perfil;
                guardarPrefs();
                render();
                break;
            case 'plegar':
                E.plegados[el.dataset.g] = !E.plegados[el.dataset.g];
                aplicarPlegado();
                break;
            case 'abrir-dia':
                if (E.vista === 'calendario' && E.sub === 'dia') break;
                abrirDia(el.dataset.fecha);
                break;
            case 'cerrar-dia': cerrarDia(); break;
            case 'dia-agregar':
                E.sinAbierto = true;
                renderDiaModal();
                { const buscar = document.querySelector('[data-at-sin-buscar]'); if (buscar) buscar.focus(); }
                break;
            case 'toggle-sin':
                E.sinAbierto = !E.sinAbierto;
                if (!E.sinAbierto) E.sinQ = '';
                if (E.diaAbierto) renderDiaModal(); else renderCuerpo();
                break;
            case 'asignar':
                asignarTurno(el.dataset.enf, el.dataset.fecha, el.dataset.tipo === '' ? null : Number(el.dataset.tipo));
                break;
            case 'sello': abrirSello(el.dataset.enf, el.dataset.fecha); break;
            case 'mover-inicio': {
                const ctx = E.selector ? E.selector.ctx : null;
                if (!ctx) break;
                cerrarSelector();
                iniciarMover(ctx);
                break;
            }
            case 'cancelar-mover': cancelarMover(); break;
            case 'celda': {
                if (E.selector && E.selector.ancla === el) { cerrarSelector(); break; }
                const ctx = contextoCelda(el);
                if (ctx && ctx.ajeno) { cerrarSelector(); toast('error', ctx.nombre + ' tiene ese día un turno en ' + (ctx.servicioAjeno || 'otro servicio') + ': cámbialo o quítalo desde ese servicio.'); break; }
                if (ctx) abrirSelector(el, ctx);
                break;
            }
            case 'elegir': {
                const ctx = E.selector ? E.selector.ctx : null;
                if (!ctx) break;
                const tipo = el.dataset.tipo;
                cerrarSelector();
                asignarTurno(ctx.idEnfPer, ctx.fecha, tipo === '' ? null : Number(tipo));
                break;
            }
            case 'resaltar':
                E.resaltado = String(E.resaltado) === el.dataset.med ? null : el.dataset.med;
                renderCuerpo();
                break;
            case 'generar': generar(); break;
            case 'reintentar': iniciar(E.vista); break;
            default: break;
        }
    }

    function onMouseDown(ev) {
        if (E.selector && !E.selector.pop.contains(ev.target) && !E.selector.ancla.contains(ev.target)) cerrarSelector();
        if (E.picker && !E.picker.contains(ev.target) && !ev.target.closest('#at-periodo-btn')) cerrarPicker();
    }

    function onKey(ev) {
        if (E.moviendo && ev.key === 'Escape') { cancelarMover(); return; }
        if (E.selector) {
            const pop = E.selector.pop;
            if (ev.key === 'Escape') {
                ev.preventDefault();
                cerrarSelector();
                return;
            }
            if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
                ev.preventDefault();
                const ops = Array.from(pop.querySelectorAll('.at-op'));
                if (!ops.length) return;
                const i = ops.indexOf(document.activeElement);
                const siguiente = ev.key === 'ArrowDown' ? (i + 1) % ops.length : (i <= 0 ? ops.length - 1 : i - 1);
                ops[siguiente].focus();
                return;
            }
            if (ev.key === 'Delete' || ev.key === 'Backspace') {
                const quitar = pop.querySelector('.quitar');
                if (quitar) { ev.preventDefault(); quitar.click(); }
                return;
            }
            if (ev.key.length === 1) {
                const letra = ev.key.toUpperCase();
                const boton = Array.from(pop.querySelectorAll('[data-letra]')).find(b => b.dataset.letra.toUpperCase() === letra);
                if (boton) { ev.preventDefault(); boton.click(); }
            }
            return;
        }
        if (E.picker && ev.key === 'Escape') { cerrarPicker(); return; }
        if (ev.key === 'Escape' && E.diaAbierto) { cerrarDia(); return; }
        if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.classList && ev.target.classList.contains('at-mes-celda')) {
            ev.preventDefault();
            abrirDia(ev.target.dataset.fecha);
        }
    }

    let temporizadorBusqueda = null;
    function onInput(ev) {
        const t = ev.target;
        if (t.id === 'at-buscar') {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(() => {
                E.qTexto = t.value.trim();
                E.q = normalizar(E.qTexto);
                render();
            }, 150);
        } else if (t.hasAttribute && t.hasAttribute('data-at-sin-buscar')) {
            E.sinQ = t.value;
            aplicarBusquedaSin();
        }
    }

    function onChange(ev) {
        if (ev.target.id === 'at-servicio') {
            E.servicio = ev.target.value;
            E.perfil = '';
            guardarPrefs();
            cargar();
        }
    }

    function montarEventos() {
        document.addEventListener('click', onClick);
        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('keydown', onKey);
        document.addEventListener('input', onInput);
        document.addEventListener('change', onChange);
        document.addEventListener('dragstart', onDragStart);
        document.addEventListener('dragover', onDragOver);
        document.addEventListener('drop', onDrop);
        document.addEventListener('dragend', onDragEnd);
        window.addEventListener('resize', () => { cerrarSelector(); cerrarPicker(); });
        window.addEventListener('scroll', () => { cerrarSelector(); cerrarPicker(); }, true);
    }

    // ---------------------------------------------------------------- arranque
    async function iniciar(vistaPedida) {
        if (!E.montado) {
            cargarPrefs();
            montarEventos();
            E.montado = true;
        }
        E.vista = vistaPedida === 'calendario' ? 'calendario' : 'tabla';
        E.q = '';
        E.qTexto = '';
        const buscar = $('at-buscar');
        if (buscar) buscar.value = '';
        cerrarDia();
        try {
            await cargarCatalogos();
        } catch (err) {
            mostrarErrorCuerpo(err);
            return;
        }
        renderToolbar();
        await cargar();
    }

    // Se llama cada vez que se abre la sección desde el menú (loadContent en CoordinacionEnfermeria.js).
    window.inicializarAsignacionTurnos = iniciar;
    window.generarAutomatico = generar;
    // Lo que necesitan los demás módulos de esta pantalla (cuadro de turnos, novedades, arrastrar y soltar).
    window.AsignacionTurnosAPI = {
        contexto: function () {
            const s = E.servicio !== '' ? E.servicioPorId[E.servicio] : null;
            return { servicio: E.servicio, servicioNombre: s ? s.NombreServicio : '', anio: E.ref.getFullYear(), mes: E.ref.getMonth() + 1 };
        },
        recargar: function () { return cargar(); }
    };
})();
