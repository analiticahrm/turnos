// Pantalla "Roles y permisos" de la consola de coordinación de enfermería: matriz rol × acción, excepciones por
// usuario (con los servicios que coordina) y parámetros del módulo. Habla con
// Controlador/ControladorPermisosEnfermeria.php; el servidor vuelve a validar todo (esto es solo la interfaz).
// window.PE_PUEDE = { permisos: bool, parametros: bool } lo imprime Vista/CoordinacionEnfermeria.php.
(function () {
    'use strict';

    var API = '../Controlador/ControladorPermisosEnfermeria.php';
    var E = {
        cat: null, tab: null,
        rolSel: null, mapa: {}, esAdminRol: false, sucio: false,
        usuarios: [], userSel: null, ud: null, ov: {}, srv: {}, sucioU: false,
        params: null
    };

    function esc(t) {
        return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function raiz() { return document.getElementById('permisos-raiz'); }
    function puede() { return window.PE_PUEDE || { permisos: false, parametros: false }; }

    function pedir(accion, datos, post) {
        var url = API + '?Accion=' + encodeURIComponent(accion);
        var opts = { credentials: 'same-origin' };
        if (post) {
            opts.method = 'POST';
            opts.body = new URLSearchParams(datos || {});
        } else if (datos) {
            Object.keys(datos).forEach(function (k) { url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(datos[k]); });
        }
        return fetch(url, opts)
            .then(function (r) { return r.json().catch(function () { return { success: false, message: 'Respuesta no válida del servidor.' }; }); })
            .catch(function () { return { success: false, message: 'No se pudo comunicar con el servidor.' }; });
    }

    function aviso(icon, title, text) {
        if (typeof showThemedAlert === 'function') {
            return showThemedAlert({ icon: icon, title: title, text: text || '', timer: icon === 'success' ? 1800 : undefined, showConfirmButton: icon !== 'success' });
        }
        alert(title + (text ? '\n' + text : ''));
        return Promise.resolve();
    }
    function confirmar(title, text, boton) {
        if (typeof showThemedAlert === 'function') {
            return showThemedAlert({ icon: 'question', title: title, text: text, showCancelButton: true, confirmButtonText: boton || 'Continuar', cancelButtonText: 'Cancelar' })
                .then(function (r) { return !!(r && r.isConfirmed); });
        }
        return Promise.resolve(window.confirm(title + '\n' + text));
    }

    // ------------------------------------------------------------------ estructura

    function armarEstructura() {
        var p = puede();
        var tabs = '';
        if (p.permisos) {
            tabs += '<button type="button" class="pe-tab" data-pe-tab="roles">Roles</button>' +
                    '<button type="button" class="pe-tab" data-pe-tab="usuarios">Usuarios</button>';
        }
        if (p.parametros) tabs += '<button type="button" class="pe-tab" data-pe-tab="parametros">Parámetros</button>';
        raiz().innerHTML = '<div class="pe-tabs" role="tablist">' + tabs + '</div><div id="pe-panel"></div>';
        if (!raiz().dataset.peListo) {
            raiz().dataset.peListo = '1';
            raiz().addEventListener('click', onClick);
            raiz().addEventListener('change', onChange);
            raiz().addEventListener('input', onInput);
        }
    }

    function abrirTab(tab) {
        E.tab = tab;
        Array.prototype.forEach.call(raiz().querySelectorAll('.pe-tab'), function (b) {
            b.classList.toggle('activa', b.getAttribute('data-pe-tab') === tab);
        });
        var panel = document.getElementById('pe-panel');
        panel.innerHTML = '<p class="vacio">Cargando...</p>';
        if (tab === 'roles') pintarRoles();
        else if (tab === 'usuarios') pintarUsuarios();
        else if (tab === 'parametros') cargarParametros();
    }

    // ------------------------------------------------------------------ roles

    function pintarRoles() {
        var panel = document.getElementById('pe-panel');
        var lista = E.cat.roles.map(function (r) {
            var activo = E.rolSel && Number(E.rolSel) === Number(r.IdRol);
            return '<li><button type="button" class="pe-rol' + (activo ? ' activa' : '') + '" data-pe-rol="' + r.IdRol + '">' +
                '<span class="pe-rol-nombre">' + esc(r.TipoRol) + '</span>' +
                '<small>' + (Number(r.EsSistema) ? 'Rol base · ' : '') + r.Usuarios + (Number(r.Usuarios) === 1 ? ' usuario' : ' usuarios') + '</small>' +
                '</button></li>';
        }).join('');
        panel.innerHTML = '<div class="pe-layout">' +
            '<aside class="pe-lista"><button type="button" class="btn btn-outline" data-pe-nuevo-rol>+ Nuevo rol</button><ul>' + lista + '</ul></aside>' +
            '<section id="pe-matriz" class="pe-matriz"><p class="vacio">Elige un rol para ver y editar sus permisos.</p></section></div>';
        if (E.rolSel) cargarMatriz(E.rolSel);
    }

    function cargarMatriz(idRol) {
        var cont = document.getElementById('pe-matriz');
        cont.innerHTML = '<p class="vacio">Cargando...</p>';
        pedir('MatrizRol', { IdRol: idRol }).then(function (r) {
            if (!r.success) { cont.innerHTML = '<p class="vacio">' + esc(r.message) + '</p>'; return; }
            E.rolSel = Number(idRol);
            E.mapa = {};
            Object.keys(r.acciones || {}).forEach(function (k) { E.mapa[k] = r.acciones[k]; });
            E.esAdminRol = !!r.esAdmin;
            E.sucio = false;
            pintarMatriz(r.rol);
        });
    }

    function pintarMatriz(rol) {
        var cont = document.getElementById('pe-matriz');
        var h = '<div class="pe-matriz-cab"><div><h3>' + esc(rol.TipoRol) + '</h3>' +
            (E.esAdminRol ? '<small>Tiene todos los permisos; no se edita.</small>' : '<small>Marca lo que puede hacer este rol. “Solo sus servicios” limita la acción a los servicios que se asignen al usuario.</small>') +
            '</div>' +
            (E.esAdminRol ? '' : '<button type="button" class="btn btn-primary" data-pe-guardar-rol id="pe-guardar-rol" disabled>Guardar cambios</button>') + '</div>';
        E.cat.grupos.forEach(function (g) {
            var acc = E.cat.acciones.filter(function (a) { return a.grupo === g.grupo; });
            if (!acc.length) return;
            h += '<div class="pe-grupo"><div class="pe-grupo-cab"><b>' + esc(g.etiqueta) + '</b>' +
                (E.esAdminRol ? '' : '<span><a href="#" data-pe-todo="' + g.grupo + '">Todo</a> · <a href="#" data-pe-nada="' + g.grupo + '">Nada</a></span>') + '</div>';
            acc.forEach(function (a) {
                var on = E.esAdminRol || Object.prototype.hasOwnProperty.call(E.mapa, a.codigo);
                var alc = E.mapa[a.codigo] || 'todos';
                h += '<div class="pe-fila"><label class="pe-chk"><input type="checkbox" data-pe-accion="' + esc(a.codigo) + '"' +
                    (on ? ' checked' : '') + (E.esAdminRol ? ' disabled' : '') + '><span><b>' + esc(a.etiqueta) + '</b><small>' + esc(a.descripcion) + '</small></span></label>';
                if (a.servicio) {
                    h += '<select class="form-control pe-alc" data-pe-alc="' + esc(a.codigo) + '"' + ((!on || E.esAdminRol) ? ' disabled' : '') + '>' +
                        '<option value="todos"' + (alc === 'todos' ? ' selected' : '') + '>Todos los servicios</option>' +
                        '<option value="servicios"' + (alc === 'servicios' ? ' selected' : '') + '>Solo sus servicios</option></select>';
                }
                h += '</div>';
            });
            h += '</div>';
        });
        cont.innerHTML = h;
    }

    function marcarSucioRol() {
        E.sucio = true;
        var b = document.getElementById('pe-guardar-rol');
        if (b) b.disabled = false;
    }

    function setGrupo(grupo, valor) {
        E.cat.acciones.forEach(function (a) {
            if (a.grupo !== grupo) return;
            if (valor) { if (!E.mapa[a.codigo]) E.mapa[a.codigo] = 'todos'; } else { delete E.mapa[a.codigo]; }
        });
        marcarSucioRol();
        pintarMatriz({ TipoRol: nombreRol(E.rolSel) });
        var b = document.getElementById('pe-guardar-rol');
        if (b) b.disabled = false;
    }
    function nombreRol(id) {
        var r = E.cat.roles.filter(function (x) { return Number(x.IdRol) === Number(id); })[0];
        return r ? r.TipoRol : '';
    }

    function guardarRol() {
        var b = document.getElementById('pe-guardar-rol');
        if (b) b.disabled = true;
        pedir('GuardarMatrizRol', { IdRol: E.rolSel, Acciones: JSON.stringify(E.mapa) }, true).then(function (r) {
            if (r.success) { E.sucio = false; aviso('success', 'Permisos guardados', 'Se aplican en unos segundos a las sesiones abiertas.'); }
            else { if (b) b.disabled = false; aviso('error', 'No se pudo guardar', r.message); }
        });
    }

    function nuevoRol() {
        if (typeof Swal === 'undefined') return;
        Swal.fire({ title: 'Nuevo rol', input: 'text', inputLabel: 'Nombre del rol', inputPlaceholder: 'Ejemplo: Auditor de turnos', showCancelButton: true, confirmButtonText: 'Crear', cancelButtonText: 'Cancelar',
            inputValidator: function (v) { return (!v || v.trim().length < 3) ? 'Escribe al menos 3 caracteres' : undefined; } })
            .then(function (res) {
                if (!res.isConfirmed) return;
                pedir('CrearRol', { TipoRol: res.value.trim() }, true).then(function (r) {
                    if (!r.success) { aviso('error', 'No se pudo crear el rol', r.message); return; }
                    pedir('Catalogo').then(function (c) {
                        if (c.success) E.cat = c;
                        E.rolSel = Number(r.IdRol);
                        pintarRoles();
                    });
                });
            });
    }

    // ------------------------------------------------------------------ usuarios

    function pintarUsuarios() {
        var panel = document.getElementById('pe-panel');
        var opciones = '<option value="0">Todos los roles</option>' + E.cat.roles.map(function (r) {
            return '<option value="' + r.IdRol + '">' + esc(r.TipoRol) + '</option>';
        }).join('');
        panel.innerHTML = '<div class="pe-busca"><input type="search" id="pe-q" class="form-control" placeholder="Buscar por nombre, documento o correo" autocomplete="off">' +
            '<select id="pe-q-rol" class="form-control">' + opciones + '</select>' +
            '<button type="button" class="btn btn-outline" data-pe-buscar>Buscar</button></div>' +
            '<div class="pe-layout pe-layout-u"><aside class="pe-lista" id="pe-resultados"><p class="vacio">Busca a la persona para editar sus excepciones y los servicios que coordina.</p></aside>' +
            '<section id="pe-usuario" class="pe-matriz"></section></div>';
        if (E.usuarios.length) pintarResultados();
    }

    function buscarUsuarios() {
        var q = document.getElementById('pe-q').value.trim();
        var rol = document.getElementById('pe-q-rol').value;
        var cont = document.getElementById('pe-resultados');
        cont.innerHTML = '<p class="vacio">Buscando...</p>';
        pedir('BuscarUsuarios', { q: q, IdRol: rol }).then(function (r) {
            if (!r.success) { cont.innerHTML = '<p class="vacio">' + esc(r.message) + '</p>'; return; }
            E.usuarios = r.usuarios;
            pintarResultados();
        });
    }

    function pintarResultados() {
        var cont = document.getElementById('pe-resultados');
        if (!E.usuarios.length) { cont.innerHTML = '<p class="vacio">Sin resultados.</p>'; return; }
        cont.innerHTML = '<ul>' + E.usuarios.map(function (u) {
            var activo = E.userSel && Number(E.userSel) === Number(u.IdUsuario);
            return '<li><button type="button" class="pe-rol' + (activo ? ' activa' : '') + '" data-pe-user="' + u.IdUsuario + '">' +
                '<span class="pe-rol-nombre">' + esc(u.NombreUsuario) + '</span><small>' + esc(u.TipoRol || 'Sin rol') + ' · ' + esc(u.DocumentoUsuario || '') + '</small></button></li>';
        }).join('') + '</ul>' + (E.usuarios.length >= 40 ? '<p class="vacio">Se muestran los primeros 40; afina la búsqueda.</p>' : '');
    }

    function cargarUsuario(id) {
        var cont = document.getElementById('pe-usuario');
        cont.innerHTML = '<p class="vacio">Cargando...</p>';
        pedir('PermisosUsuario', { IdUsuario: id }).then(function (r) {
            if (!r.success) { cont.innerHTML = '<p class="vacio">' + esc(r.message) + '</p>'; return; }
            E.userSel = Number(id);
            E.ud = r;
            E.ov = {};
            Object.keys(r.overrides || {}).forEach(function (k) { E.ov[k] = r.overrides[k]; });
            E.srv = {};
            (r.servicios || []).forEach(function (s) { E.srv[s] = true; });
            E.sucioU = false;
            pintarResultados();
            pintarUsuario();
        });
    }

    function pintarUsuario() {
        var cont = document.getElementById('pe-usuario');
        var u = E.ud.usuario;
        var h = '<div class="pe-matriz-cab"><div><h3>' + esc(u.NombreUsuario) + '</h3><small>' + esc(u.TipoRol || 'Sin rol') + ' · ' + esc(u.CorreoUsuario || '') + '</small></div>' +
            (E.ud.esAdmin ? '' : '<button type="button" class="btn btn-primary" id="pe-guardar-usuario" data-pe-guardar-usuario disabled>Guardar cambios</button>') + '</div>';
        if (E.ud.esAdmin) {
            cont.innerHTML = h + '<p class="vacio">Es administrador: tiene todos los permisos y no admite excepciones.</p>';
            return;
        }
        h += '<div class="pe-grupo"><div class="pe-grupo-cab"><b>Servicios que coordina</b></div><p class="pe-nota">Se usan en las acciones con alcance “Solo sus servicios”. Un coordinador de servicio solo ve y aprueba lo de estos servicios.</p><div class="pe-servicios">' +
            E.cat.servicios.map(function (s) {
                return '<label class="pe-chk pe-chk-srv"><input type="checkbox" data-pe-srv="' + s.IdServicio + '"' + (E.srv[s.IdServicio] ? ' checked' : '') + '><span>' + esc(s.NombreServicio) + '</span></label>';
            }).join('') + '</div></div>';

        E.cat.grupos.forEach(function (g) {
            var acc = E.cat.acciones.filter(function (a) { return a.grupo === g.grupo; });
            if (!acc.length) return;
            h += '<div class="pe-grupo"><div class="pe-grupo-cab"><b>' + esc(g.etiqueta) + '</b><span class="pe-cab-cols"><span>Por su rol</span><span>Excepción</span></span></div>';
            acc.forEach(function (a) {
                var delRol = E.ud.accionesRol[a.codigo];
                var ov = E.ov[a.codigo];
                var valor = !ov ? 'heredar' : (ov.efecto === 'denegar' ? 'denegar' : (ov.alcance === 'servicios' ? 'servicios' : 'todos'));
                var textoRol = delRol ? (delRol === 'servicios' ? 'Sus servicios' : 'Todos') : '—';
                h += '<div class="pe-fila"><div class="pe-chk"><span><b>' + esc(a.etiqueta) + '</b><small>' + esc(a.descripcion) + '</small></span></div>' +
                    '<span class="pe-rolval' + (delRol ? ' si' : '') + '">' + textoRol + '</span>' +
                    '<select class="form-control pe-alc" data-pe-ov="' + esc(a.codigo) + '">' +
                    '<option value="heredar"' + (valor === 'heredar' ? ' selected' : '') + '>Heredar del rol</option>' +
                    '<option value="todos"' + (valor === 'todos' ? ' selected' : '') + '>Permitir en todos los servicios</option>' +
                    (a.servicio ? '<option value="servicios"' + (valor === 'servicios' ? ' selected' : '') + '>Permitir en sus servicios</option>' : '') +
                    '<option value="denegar"' + (valor === 'denegar' ? ' selected' : '') + '>Denegar</option></select></div>';
            });
            h += '</div>';
        });
        cont.innerHTML = h;
    }

    function guardarUsuario() {
        var b = document.getElementById('pe-guardar-usuario');
        if (b) b.disabled = true;
        var servicios = Object.keys(E.srv).filter(function (k) { return E.srv[k]; }).map(Number);
        pedir('GuardarPermisosUsuario', { IdUsuario: E.userSel, Overrides: JSON.stringify(E.ov), Servicios: JSON.stringify(servicios) }, true).then(function (r) {
            if (r.success) { E.sucioU = false; aviso('success', 'Permisos guardados', 'Se aplican en unos segundos a las sesiones abiertas.'); }
            else { if (b) b.disabled = false; aviso('error', 'No se pudo guardar', r.message); }
        });
    }

    // ------------------------------------------------------------------ parámetros

    function cargarParametros() {
        pedir('Parametros').then(function (r) {
            var panel = document.getElementById('pe-panel');
            if (!r.success) { panel.innerHTML = '<p class="vacio">' + esc(r.message) + '</p>'; return; }
            E.params = r.parametros;
            var filas = r.parametros.filter(function (p) { return p.editable; }).map(function (p) {
                var campo;
                if (p.validacion.tipo === 'lista') {
                    campo = '<select class="form-control" data-pe-param="' + esc(p.clave) + '">' + p.validacion.valores.map(function (v) {
                        return '<option value="' + esc(v) + '"' + (v === p.valor ? ' selected' : '') + '>' + esc(v) + '</option>';
                    }).join('') + '</select>';
                } else {
                    campo = '<input type="number" class="form-control" data-pe-param="' + esc(p.clave) + '" value="' + esc(p.valor) + '" min="' + p.validacion.min + '" max="' + p.validacion.max + '" step="1">';
                }
                return '<div class="pe-param"><label><b>' + esc(p.clave) + '</b><small>' + esc(p.descripcion) + '</small></label>' + campo + '</div>';
            }).join('');
            panel.innerHTML = '<div class="pe-matriz"><div class="pe-matriz-cab"><div><h3>Parámetros de enfermería</h3><small>Cupo de cambios de turno, correos y avisos.</small></div>' +
                '<button type="button" class="btn btn-primary" data-pe-guardar-params>Guardar parámetros</button></div>' + filas + '</div>';
        });
    }

    function guardarParametros() {
        var datos = {};
        Array.prototype.forEach.call(raiz().querySelectorAll('[data-pe-param]'), function (el) { datos[el.getAttribute('data-pe-param')] = el.value; });
        pedir('GuardarParametros', { Parametros: JSON.stringify(datos) }, true).then(function (r) {
            if (r.success) aviso('success', 'Parámetros guardados', '');
            else aviso('error', 'No se pudo guardar', r.message);
        });
    }

    // ------------------------------------------------------------------ eventos (delegados)

    function onClick(ev) {
        var t = ev.target.closest('[data-pe-tab],[data-pe-rol],[data-pe-user],[data-pe-todo],[data-pe-nada],[data-pe-guardar-rol],[data-pe-guardar-usuario],[data-pe-guardar-params],[data-pe-nuevo-rol],[data-pe-buscar]');
        if (!t) return;
        if (t.hasAttribute('data-pe-tab')) {
            var tab = t.getAttribute('data-pe-tab');
            guardiaCambios(function () { abrirTab(tab); });
        } else if (t.hasAttribute('data-pe-rol')) {
            var id = t.getAttribute('data-pe-rol');
            guardiaCambios(function () { E.rolSel = Number(id); pintarRoles(); });
        } else if (t.hasAttribute('data-pe-user')) {
            var uid = t.getAttribute('data-pe-user');
            guardiaCambios(function () { cargarUsuario(uid); });
        } else if (t.hasAttribute('data-pe-todo')) { ev.preventDefault(); setGrupo(t.getAttribute('data-pe-todo'), true); }
        else if (t.hasAttribute('data-pe-nada')) { ev.preventDefault(); setGrupo(t.getAttribute('data-pe-nada'), false); }
        else if (t.hasAttribute('data-pe-guardar-rol')) guardarRol();
        else if (t.hasAttribute('data-pe-guardar-usuario')) guardarUsuario();
        else if (t.hasAttribute('data-pe-guardar-params')) guardarParametros();
        else if (t.hasAttribute('data-pe-nuevo-rol')) guardiaCambios(nuevoRol);
        else if (t.hasAttribute('data-pe-buscar')) buscarUsuarios();
    }

    function onChange(ev) {
        var el = ev.target;
        var acc = el.getAttribute && el.getAttribute('data-pe-accion');
        if (acc) {
            if (el.checked) {
                var sel = raiz().querySelector('[data-pe-alc="' + acc + '"]');
                E.mapa[acc] = sel ? sel.value : 'todos';
                if (sel) sel.disabled = false;
            } else {
                delete E.mapa[acc];
                var sel2 = raiz().querySelector('[data-pe-alc="' + acc + '"]');
                if (sel2) sel2.disabled = true;
            }
            marcarSucioRol();
            return;
        }
        var alc = el.getAttribute && el.getAttribute('data-pe-alc');
        if (alc) { if (E.mapa[alc]) E.mapa[alc] = el.value; marcarSucioRol(); return; }
        var srv = el.getAttribute && el.getAttribute('data-pe-srv');
        if (srv) { E.srv[srv] = el.checked; marcarSucioUsuario(); return; }
        var ov = el.getAttribute && el.getAttribute('data-pe-ov');
        if (ov) {
            if (el.value === 'heredar') delete E.ov[ov];
            else if (el.value === 'denegar') E.ov[ov] = { efecto: 'denegar', alcance: 'todos' };
            else E.ov[ov] = { efecto: 'permitir', alcance: el.value };
            marcarSucioUsuario();
        }
    }

    function onInput(ev) {
        if (ev.target && ev.target.id === 'pe-q') {
            clearTimeout(onInput.t);
            onInput.t = setTimeout(buscarUsuarios, 350);
        }
    }

    function marcarSucioUsuario() {
        E.sucioU = true;
        var b = document.getElementById('pe-guardar-usuario');
        if (b) b.disabled = false;
    }

    // Avisa antes de descartar cambios sin guardar.
    function guardiaCambios(siguiente) {
        if (E.tab === 'roles' && E.sucio) {
            confirmar('Hay cambios sin guardar', 'Si continúas se descartan los cambios de este rol.', 'Descartar').then(function (ok) { if (ok) { E.sucio = false; siguiente(); } });
        } else if (E.tab === 'usuarios' && E.sucioU) {
            confirmar('Hay cambios sin guardar', 'Si continúas se descartan los cambios de este usuario.', 'Descartar').then(function (ok) { if (ok) { E.sucioU = false; siguiente(); } });
        } else {
            siguiente();
        }
    }

    // ------------------------------------------------------------------ entrada

    window.inicializarPermisos = function () {
        if (!raiz()) return;
        var p = puede();
        pedir('Catalogo').then(function (c) {
            // Quien solo administra parámetros no tiene acceso al catálogo de roles: se muestra solo esa pestaña.
            if (c.success) E.cat = c; else if (p.permisos) { raiz().innerHTML = '<p class="vacio">' + esc(c.message) + '</p>'; return; }
            armarEstructura();
            abrirTab(p.permisos ? (E.tab && E.tab !== 'parametros' ? E.tab : 'roles') : 'parametros');
        });
    };
})();
