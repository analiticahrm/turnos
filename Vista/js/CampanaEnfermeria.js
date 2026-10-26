/* Campana de notificaciones del módulo de enfermería (consola de coordinación y portal del enfermero).
   Se monta sola en el elemento #campana-enfermeria. Consulta cada 45 s (y no mientras la pestaña está oculta) a
   Controlador/ControladorNotificacionesEnfermeria.php, que toma a la persona de la sesión. Todo texto se escapa: un
   mensaje con HTML nunca se interpreta. Al hacer clic en un aviso se marca como leído y se abre su enlace; si el enlace es
   de esta misma página se llama a CampanaEnfermeria.alNavegar(hash) para que la pantalla cambie de sección. */
(function () {
    'use strict';

    const API = '../Controlador/ControladorNotificacionesEnfermeria.php';
    const CADA_MS = 45000;
    const ICONOS = { cambio_turno: 'fa-exchange-alt', cuadro_turnos: 'fa-calendar-check', novedad_turno: 'fa-stamp', nomina: 'fa-file-invoice-dollar', mensaje: 'fa-envelope', sistema: 'fa-info-circle' };

    const C = { lista: [], noLeidas: 0, abierto: false, huella: '', temporizador: null, montado: false };

    function esc(t) {
        return String(t === null || t === undefined ? '' : t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    async function pedir(accion, datos) {
        const opts = { credentials: 'same-origin' };
        if (datos) { opts.method = 'POST'; opts.body = new URLSearchParams(datos); }
        const resp = await fetch(API + '?Accion=' + accion, opts);
        return resp.json();
    }

    function hace(fecha) {
        const t = new Date(String(fecha).replace(' ', 'T'));
        if (isNaN(t)) return '';
        const seg = Math.max(0, Math.round((Date.now() - t.getTime()) / 1000));
        if (seg < 60) return 'ahora';
        if (seg < 3600) return 'hace ' + Math.floor(seg / 60) + ' min';
        if (seg < 86400) return 'hace ' + Math.floor(seg / 3600) + ' h';
        if (seg < 604800) return 'hace ' + Math.floor(seg / 86400) + ' d';
        return t.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' });
    }

    function raiz() { return document.getElementById('campana-enfermeria'); }

    function montar() {
        const r = raiz();
        if (!r || C.montado) return;
        C.montado = true;
        r.innerHTML = '<button type="button" class="campana-btn" aria-haspopup="true" aria-expanded="false" aria-label="Notificaciones">' +
            '<i class="fas fa-bell"></i><span class="campana-n" hidden>0</span></button>' +
            '<div class="campana-panel" hidden role="dialog" aria-label="Notificaciones"></div>';
        r.querySelector('.campana-btn').addEventListener('click', alternar);
        r.querySelector('.campana-panel').addEventListener('click', alClicPanel);
        document.addEventListener('click', ev => { if (C.abierto && !r.contains(ev.target)) cerrar(); });
        document.addEventListener('keydown', ev => { if (ev.key === 'Escape' && C.abierto) cerrar(); });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refrescar(); });
        refrescar();
        C.temporizador = setInterval(() => { if (!document.hidden) refrescar(); }, CADA_MS);
    }

    async function refrescar() {
        try {
            const r = await pedir('Listar');
            if (!r.success) return;
            const huella = r.noLeidas + '|' + r.notificaciones.map(n => n.IdNotificaciones + ':' + n.EstadoNotificaciones).join(',');
            if (huella === C.huella) return; // nada cambió: no se repinta
            C.huella = huella;
            C.lista = r.notificaciones;
            C.noLeidas = r.noLeidas;
            pintarContador();
            if (C.abierto) pintarPanel();
        } catch (e) { /* sin red: se reintenta en la próxima vuelta */ }
    }

    function pintarContador() {
        const n = raiz().querySelector('.campana-n');
        n.hidden = C.noLeidas === 0;
        n.textContent = C.noLeidas > 99 ? '99+' : String(C.noLeidas);
        raiz().querySelector('.campana-btn').setAttribute('aria-label', C.noLeidas ? 'Notificaciones: ' + C.noLeidas + ' sin leer' : 'Notificaciones');
    }

    function pintarPanel() {
        const panel = raiz().querySelector('.campana-panel');
        let html = '<div class="campana-cab"><strong>Notificaciones</strong>' +
            (C.noLeidas ? '<button type="button" class="campana-todas" data-cm="todas">Marcar todas como leídas</button>' : '') + '</div>';
        if (!C.lista.length) {
            html += '<p class="campana-vacio">No tienes notificaciones.</p>';
        } else {
            html += '<ul class="campana-lista">' + C.lista.map(n => {
                const icono = ICONOS[n.TipoNotificaciones] || 'fa-bell';
                return '<li><button type="button" class="campana-item' + (Number(n.EstadoNotificaciones) === 0 ? ' nueva' : '') + (n.PrioridadNotificaciones === 'alta' || n.PrioridadNotificaciones === 'urgente' ? ' alta' : '') +
                    '" data-cm="abrir" data-id="' + esc(n.IdNotificaciones) + '"><i class="fas ' + icono + '" aria-hidden="true"></i>' +
                    '<span class="campana-txt">' + esc(n.MensajeNotificaciones) + '<small>' + esc(hace(n.FechaNotificaciones)) + '</small></span></button></li>';
            }).join('') + '</ul>';
        }
        panel.innerHTML = html;
    }

    function alternar() { C.abierto ? cerrar() : abrir(); }
    function abrir() {
        C.abierto = true;
        const r = raiz();
        r.querySelector('.campana-panel').hidden = false;
        r.querySelector('.campana-btn').setAttribute('aria-expanded', 'true');
        pintarPanel();
        refrescar();
    }
    function cerrar() {
        C.abierto = false;
        const r = raiz();
        r.querySelector('.campana-panel').hidden = true;
        r.querySelector('.campana-btn').setAttribute('aria-expanded', 'false');
    }

    async function alClicPanel(ev) {
        const el = ev.target.closest('[data-cm]');
        if (!el) return;
        if (el.dataset.cm === 'todas') {
            try { await pedir('MarcarTodas', {}); } catch (e) { return; }
            C.lista.forEach(n => { n.EstadoNotificaciones = 1; });
            C.noLeidas = 0;
            C.huella = '';
            pintarContador();
            pintarPanel();
            return;
        }
        if (el.dataset.cm === 'abrir') {
            const n = C.lista.find(x => String(x.IdNotificaciones) === el.dataset.id);
            if (!n) return;
            if (Number(n.EstadoNotificaciones) === 0) {
                n.EstadoNotificaciones = 1;
                C.noLeidas = Math.max(0, C.noLeidas - 1);
                C.huella = '';
                pintarContador();
                pedir('MarcarLeida', { IdNotificaciones: n.IdNotificaciones }).catch(() => {});
            }
            cerrar();
            navegar(n.EnlaceNotificaciones);
        }
    }

    // Solo enlaces internos de la forma Pagina.php#seccion/id; cualquier otra cosa se ignora.
    function navegar(enlace) {
        if (!enlace || !/^[A-Za-z0-9_]+\.php(#[A-Za-z0-9_\/-]*)?$/.test(enlace)) return;
        const partes = enlace.split('#');
        const pagina = partes[0];
        const hash = partes[1] || '';
        const actual = location.pathname.split('/').pop();
        if (pagina === actual) {
            location.hash = hash ? '#' + hash : '';
            if (typeof window.CampanaEnfermeria.alNavegar === 'function') window.CampanaEnfermeria.alNavegar(hash);
        } else {
            location.href = pagina + (hash ? '#' + hash : '');
        }
    }

    window.CampanaEnfermeria = { refrescar: refrescar, alNavegar: null };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', montar); else montar();
})();
