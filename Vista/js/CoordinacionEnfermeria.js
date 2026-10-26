        // Función para alternar submenús
        function toggleSubmenu(menu) {
            const submenu = document.getElementById(`${menu}-submenu`);
            submenu.classList.toggle('show');
        }
        function MensajeError(titulo, Mensaje) {
            showThemedAlert({
                icon: "error",
                title: titulo,
                text: Mensaje,
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        }
        // Función para cargar contenido dinámico
        function loadContent(content) {
            // Ocultar todos los contenidos primero. Se usa getElementById + chequeo de null
            // (en vez de una lista fija de .style.display='none') porque, segun el rol, el
            // servidor puede no haber renderizado algunos de estos divs (p.ej. un usuario con
            // solo rol Nomina no tiene en el DOM dashboard-content/empresas-content/etc., y
            // quien no puede parametrizar nomina no tiene parametrizacion-nomina-content).
            ['dashboard-content', 'servicios-content', 'esquemas-content', 'tipos-turnos-content',
             'empresas-content', 'perfiles-content', 'enfermeros-content', 'excepciones-content',
             'generalidades-content', 'asignacion-turnos-content',
             'nomina-recargos-content', 'novedades-nomina-content', 'parametrizacion-nomina-content',
             'permisos-content', 'bitacora-content', 'cambios-content'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            // Mostrar el contenido seleccionado
            if (content === 'dashboard') {
                document.getElementById('dashboard-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Dashboard';
                document.getElementById('page-description').textContent = 'Resumen general del sistema';
            } else if (content === 'areas-servicio') {
                document.getElementById('servicios-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Áreas de Servicio';
                document.getElementById('page-description').textContent = 'Gestión de áreas y servicios del hospital';
            } else if (content === 'config-esquemas') {
                document.getElementById('esquemas-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Configurar Esquemas';
                document.getElementById('page-description').textContent = 'Configuración de esquemas de turnos';
                cargarEsquemas();
            } else if (content === 'tipos-turno') {
                document.getElementById('tipos-turnos-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Tipos de Turno';
                document.getElementById('page-description').textContent = 'Gestión de tipos de turno';
                cargarTiposTurno();
            } else if (content === 'empresas') {
                document.getElementById('empresas-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Empresas';
                document.getElementById('page-description').textContent = 'Gestión de empresas asociadas';
                cargarEmpresas(); // Cargar datos de empresas
                cargarDiagnosticoContratos();
            }
            else if (content === 'perfiles') {
                document.getElementById('perfiles-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Perfiles';
                document.getElementById('page-description').textContent = 'Gestión de perfiles de servicio';
                cargarPerfiles(); // Cargar datos de perfiles
            }
            else if (content === 'lista-enfermeros') {
                document.getElementById('enfermeros-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Lista de Enfermeros';
                document.getElementById('page-description').textContent = 'Gestión de enfermeros';
                cargarEnfermeros(); // Cargar datos de enfermeros
            }else if (content === 'excepciones ') {
                document.getElementById('excepciones-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Excepciones ';
                document.getElementById('page-description').textContent = 'Gestión de Excepciones';
                cargarExcepciones(); // Cargar datos de habilidades
            }else if (content === 'lista-generalidades') {
                document.getElementById('generalidades-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Generalidades';
                document.getElementById('page-description').textContent = 'Gestión de generalidades';
                cargarGeneralidades(); // Cargar datos de generalidades
            } else if (content === 'asignacion-turnos' || content === 'calendario') {
                // Una sola seccion con dos vistas (Tabla | Calendario): el menu "Calendario" abre la segunda.
                const esCalendario = content === 'calendario';
                document.getElementById('asignacion-turnos-content').style.display = 'block';
                document.getElementById('page-title').textContent = esCalendario ? 'Calendario de Turnos de Enfermería' : 'Asignación de Turnos de Enfermería';
                document.getElementById('page-description').textContent = esCalendario
                    ? 'Quién trabaja cada día, por servicio y perfil; haz clic en un día para verlo y editarlo'
                    : 'Asignación de turnos por servicio, perfil y período; haz clic en una celda para cambiar el turno';
                inicializarAsignacionTurnos(esCalendario ? 'calendario' : 'tabla');
            } else if (content === 'nomina-recargos') {
                document.getElementById('nomina-recargos-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Nómina de Recargos';
                document.getElementById('page-description').textContent = 'Cálculo y liquidación de recargos, horas extra y festivos';
                inicializarNominaRecargos();
            } else if (content === 'novedades-nomina') {
                document.getElementById('novedades-nomina-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Novedades de Nómina';
                document.getElementById('page-description').textContent = 'Licencias, incapacidades, vacaciones, horas adicionales, descuentos y bonificaciones del mes';
                inicializarNovedades();
            } else if (content === 'parametrizacion-nomina') {
                document.getElementById('parametrizacion-nomina-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Parametrización de Nómina';
                document.getElementById('page-description').textContent = 'Recargos, extras, umbrales y festivos por empresa';
                cargarParametrosEmpresa();
                cargarFestivos();
            } else if (content === 'cambios') {
                document.getElementById('cambios-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Cambios de turno';
                document.getElementById('page-description').textContent = 'Solicitudes de cambio entre enfermeros de un mismo servicio y cupos del mes';
                inicializarCambios();
            } else if (content === 'bitacora') {
                document.getElementById('bitacora-content').style.display = 'block';
                document.getElementById('page-title').textContent = 'Bitácora de cambios';
                document.getElementById('page-description').textContent = 'Quién cambió qué en los turnos: asignaciones, novedades, coberturas, cuadros confirmados y cambios entre enfermeros';
                inicializarBitacora();
            } else if (content === 'permisos') {
                document.getElementById('permisos-content').style.display = 'block';
                const soloParametros = window.PE_PUEDE && !window.PE_PUEDE.permisos;
                document.getElementById('page-title').textContent = soloParametros ? 'Parámetros de enfermería' : 'Roles y permisos';
                document.getElementById('page-description').textContent = soloParametros
                    ? 'Cupo de cambios de turno, correos y avisos'
                    : 'Qué puede hacer cada rol, excepciones por usuario y servicios que coordina cada persona';
                inicializarPermisos();
            } else {
                // Para otros contenidos, mostramos un mensaje simple
                const dash = document.getElementById('dashboard-content');
                if (dash) dash.style.display = 'block';
                document.getElementById('page-title').textContent = content.charAt(0).toUpperCase() + content.slice(1).replace('-', ' ');
                document.getElementById('page-description').textContent = `Página de ${content.replace('-', ' ')}`;
            }
            
            // Actualizar menú activo
            const menuItems = document.querySelectorAll('.menu-item, .submenu-item');
            menuItems.forEach(item => {
                item.classList.remove('active');
            });
            
            // Marcar como activo el elemento correspondiente (simplificado).
            // event puede no existir cuando loadContent() se llama programáticamente
            // (ej. window.onload = loadDashboard, sin clic real de por medio).
            if (typeof event !== 'undefined' && event && event.currentTarget && event.currentTarget.classList) {
                event.currentTarget.classList.add('active');
            }
        }

        // Función para cargar el dashboard por defecto
        function loadDashboard() {
            loadContent('dashboard');
        }

        // Funciones para mostrar/ocultar modales
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Control del tema día/noche
const themeToggle = document.getElementById('themeToggle');
const body = document.body;

// Verificar preferencia del usuario
const userPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
const currentTheme = localStorage.getItem('theme');

// Aplicar tema guardado o preferencia del sistema
if (currentTheme === 'dark' || (!currentTheme && userPrefersDark)) {
    body.classList.add('dark-mode');
    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
}

// Observador para cambios en la preferencia del sistema
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) {
        if (e.matches) {
            body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            body.classList.remove('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }
});

// Cambiar tema al hacer clic
themeToggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    
    // Cambiar icono y guardar preferencia
    if (body.classList.contains('dark-mode')) {
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        localStorage.setItem('theme', 'dark');
    } else {
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        localStorage.setItem('theme', 'light');
    }
    
    // Forzar actualización de cualquier alerta abierta (opcional)
    if (Swal.isVisible()) {
        Swal.update({
            background: body.classList.contains('dark-mode') ? 'var(--modal-bg)' : '#ffffff',
            color: body.classList.contains('dark-mode') ? 'var(--text-primary)' : '#333333'
        });
    }
});
/**
 * Muestra un SweetAlert2 adaptado al tema actual
 * @param {object} config - Configuración de SweetAlert2
 * @returns {Promise} Promise de SweetAlert2
 */
function showThemedAlert(config) {
    try {
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        const defaultConfig = {
            background: isDarkMode ? getComputedStyle(document.body).getPropertyValue('--modal-bg') : '#ffffff',
            color: isDarkMode ? getComputedStyle(document.body).getPropertyValue('--text-primary') : '#333333',
            customClass: {
                popup: isDarkMode ? 'dark-mode' : '',
                title: isDarkMode ? 'dark-mode' : '',
                content: isDarkMode ? 'dark-mode' : '',
                confirmButton: isDarkMode ? 'dark-mode' : '',
                cancelButton: isDarkMode ? 'dark-mode' : ''
            },
            confirmButtonColor: isDarkMode ? getComputedStyle(document.body).getPropertyValue('--accent-color') : '#3085d6',
            cancelButtonColor: isDarkMode ? getComputedStyle(document.body).getPropertyValue('--danger-color') : '#d33'
        };
        
        // Combinar configuraciones
        const finalConfig = {
            ...defaultConfig,
            ...config,
            customClass: {
                ...defaultConfig.customClass,
                ...(config.customClass || {})
            }
        };
        
        return Swal.fire(finalConfig);
    } catch (error) {
        console.error('Error en showThemedAlert:', error);
        // Fallback a SweetAlert normal si hay error
        return Swal.fire(config);
    }
}

// Cargar dashboard al inicio
// Cada rol ve solo sus secciones: se abre la primera que exista (Subgerencia o Nómina no tienen dashboard).
// Secciones a las que se puede llegar por enlace (#bitacora, #cambios/12…): las de los avisos de la campana.
function seccionPorHash(h) {
    const k = String(h || '').replace(/^#/, '').split('/')[0];
    const contenedor = { bitacora: 'bitacora-content', permisos: 'permisos-content', cambios: 'cambios-content', calendario: 'asignacion-turnos-content', 'asignacion-turnos': 'asignacion-turnos-content', 'nomina-recargos': 'nomina-recargos-content' }[k];
    return contenedor && document.getElementById(contenedor) ? k : '';
}

window.onload = function () {
    const porEnlace = seccionPorHash(location.hash);
    if (porEnlace) {
        loadContent(porEnlace);
    } else if (document.getElementById('dashboard-content')) {
        loadDashboard();
    } else if (document.getElementById('nomina-recargos-content')) {
        loadContent('nomina-recargos');
    } else if (document.getElementById('permisos-content')) {
        loadContent('permisos');
    }
    if (window.CampanaEnfermeria) window.CampanaEnfermeria.alNavegar = function (h) { const s = seccionPorHash('#' + h); if (s) loadContent(s); };
};


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////EMPRESAS////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Funciones para gestión de empresas
var empresasTable; // Variable global para la tabla

function cargarEmpresas() {
    if ($.fn.DataTable.isDataTable('#empresas-table')) {
        empresasTable.destroy(); // Destruye la tabla existente si ya está inicializada
    }
    empresasTable = $('#empresas-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorEmpresa.php?Accion=Listar',
            "dataSrc": ""
        },
        "columns": [
            { "data": "IdEmpresa" },
            { "data": "NombreEmpresa" },
            { "data": "ContactoEmpresa" },
            { "data": "TelefonoEmpresa" },
            { "data": "EmailEmpresa" },
            {
                "data": "ManejaPlanta",
                "render": function(data, type, row) {
                    return data == 1 ? '<span class="status active">Maneja planta</span>' : '';
                }
            },
            { 
                "data": "IdEstado",
                "render": function(data, type, row) {
                    return `<span class="status ${data == 1 ? 'active' : 'inactive'}">${data == 1 ? 'Activo' : 'Inactivo'}</span>`;
                }
            },
            {
                "data": "IdEmpresa",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="editarEmpresa(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarEmpresa(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
            }
        ],
            "paging": true,          // Activa la paginación
            "ordering": false,       // Desactiva la ordenación en todas las columnas
           
    });

}

function escHtmlContratos(texto) {
    const d = document.createElement('div');
    d.textContent = texto == null ? '' : String(texto);
    return d.innerHTML;
}

// Los nombres de empresa se guardaron con entidades HTML (p. ej. S&amp;A): se muestran como texto normal.
function decodificarEntidades(texto) {
    const t = document.createElement('textarea');
    t.innerHTML = texto == null ? '' : String(texto);
    return t.value;
}

// Diagnóstico de solo lectura: enfermeros cuyo contrato no corresponde con su empresa (no corrige nada).
function cargarDiagnosticoContratos() {
    const cont = document.getElementById('diagnostico-contratos-resultado');
    if (!cont) return;
    cont.innerHTML = '<p>Revisando...</p>';
    fetch('../Controlador/ControladorEmpresa.php?Accion=DiagnosticoContratos')
        .then(response => response.json())
        .then(d => {
            if (!d.success) {
                cont.innerHTML = '<p>' + escHtmlContratos(d.message || 'No se pudo revisar.') + '</p>';
                return;
            }
            let html = '';
            if (!d.empresaPlanta) {
                html += '<p><strong>Ninguna empresa está marcada como «Maneja personal de planta».</strong> Edite la empresa del Hospital y active esa opción: el contrato Planta se asigna solo a esa empresa.</p>';
            } else {
                html += '<p>Empresa de planta: <strong>' + escHtmlContratos(d.empresaPlanta.Nombre) + '</strong>. Los contratos Temporal, OPS y Otro van en las demás empresas.</p>';
            }
            if (d.activosSinEmpresa > 0) {
                html += '<p>' + d.activosSinEmpresa + ' enfermero(s) activos no tienen empresa ni contrato registrados.</p>';
            }
            const activos = d.incoherentes.filter(i => i.Activo);
            const inactivos = d.incoherentes.length - activos.length;
            if (!activos.length) {
                html += '<p>No hay enfermeros activos con el contrato en conflicto con su empresa.</p>';
            } else {
                html += '<p><strong>' + activos.length + ' enfermero(s) activos con contrato y empresa que no corresponden.</strong> Corríjalos uno por uno con el botón Editar: el sistema no los cambia solo.</p>';
                html += '<div style="max-height: 360px; overflow: auto;"><table class="display" style="width:100%"><thead><tr>' +
                    '<th>Nombre</th><th>Documento</th><th>Empresa</th><th>Contrato</th><th>Qué corregir</th><th></th></tr></thead><tbody>';
                activos.forEach(i => {
                    html += '<tr><td>' + escHtmlContratos(i.Nombre) + '</td><td>' + escHtmlContratos(i.Documento) + '</td><td>' +
                        escHtmlContratos(i.Empresa) + '</td><td>' + escHtmlContratos(i.Contrato) + '</td><td>' +
                        escHtmlContratos(i.Sugerencia) + '</td><td><button class="btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick="abrirModalEnfermero(' +
                        i.IdMedico + ')"><i class="fas fa-edit"></i></button></td></tr>';
                });
                html += '</tbody></table></div>';
            }
            if (inactivos > 0) {
                html += '<p style="margin-top: 8px;">Además hay ' + inactivos + ' inactivo(s) con el mismo conflicto; no afectan turnos ni nómina.</p>';
            }
            cont.innerHTML = html;
        })
        .catch(() => {
            cont.innerHTML = '<p>No se pudo revisar el diagnóstico.</p>';
        });
}

function abrirModalEmpresa(empresaId = null) {
    const modal = document.getElementById('nueva-empresa-modal');
    const titulo = document.getElementById('empresa-modal-title');
    
    if (empresaId) {
        titulo.textContent = 'Editar Empresa';
        // Cargar datos de la empresa
        fetch(`../Controlador/ControladorEmpresa.php?Accion=ListarPorIdGet&IdEmpresa=${empresaId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('empresa-id').value = data[0].IdEmpresa;
                document.getElementById('empresa-nombre').value = data[0].NombreEmpresa;
                document.getElementById('empresa-contacto').value = data[0].ContactoEmpresa;
                document.getElementById('empresa-telefono').value = data[0].TelefonoEmpresa;
                document.getElementById('empresa-email').value = data[0].EmailEmpresa;
                document.getElementById('empresa-estado').value = data[0].IdEstado;
                document.getElementById('empresa-maneja-planta').checked = data[0].ManejaPlanta == 1;
            });
    } else {
        titulo.textContent = 'Nueva Empresa';
        // Limpiar formulario
        document.getElementById('empresa-id').value = '';
        document.getElementById('empresa-nombre').value = '';
        document.getElementById('empresa-contacto').value = '';
        document.getElementById('empresa-telefono').value = '';
        document.getElementById('empresa-email').value = '';
        document.getElementById('empresa-estado').value = '1';
        document.getElementById('empresa-maneja-planta').checked = false;
    }
    
    showModal('nueva-empresa-modal');
}

function editarEmpresa(id) {
    abrirModalEmpresa(id);
}

function guardarEmpresa() {
    const empresaId = document.getElementById('empresa-id').value;
    const accion = empresaId ? 'Actualizar' : 'Ingresar';
    const formData = new FormData();
    formData.append('Accion', accion);
    formData.append('IdEmpresa', empresaId);
    formData.append('NombreEmpresa', document.getElementById('empresa-nombre').value);
    formData.append('ContactoEmpresa', document.getElementById('empresa-contacto').value);
    formData.append('TelefonoEmpresa', document.getElementById('empresa-telefono').value);
    formData.append('EmailEmpresa', document.getElementById('empresa-email').value);
    formData.append('IdEstado', document.getElementById('empresa-estado').value);
    formData.append('ManejaPlanta', document.getElementById('empresa-maneja-planta').checked ? '1' : '0');
    formData.append('UsuarioCreacion', IdUsuario);
    formData.append('FechaCreacion', new Date().toISOString());
    formData.append('UsuarioModificacion', IdUsuario);
    formData.append('FechaModificacion', new Date().toISOString());

    fetch('../Controlador/ControladorEmpresa.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        } catch (err) {
            console.error('Error parsing JSON:', text);
            throw new Error(text || `HTTP error! status: ${response.status}`);
        }
    })
    .then(data => {
        if (data.success) {
            hideModal('nueva-empresa-modal');
            empresasTable.ajax.reload();
            cargarDiagnosticoContratos();
            const conAviso = (data.message || '').indexOf('Ojo') !== -1;

            showThemedAlert({
                icon: conAviso ? 'warning' : 'success',
                title: conAviso ? 'Revise' : 'Éxito',
                text: conAviso ? data.message : 'Empresa ' + (accion === 'Ingresar' ? 'creada' : 'actualizada') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar la empresa',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

function eliminarEmpresa(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdEmpresa', id);

            fetch('../Controlador/ControladorEmpresa.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    empresasTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminada',
                        text: 'Empresa eliminada correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar la empresa',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar la empresa',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////PERFIL//////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Funciones para gestión de perfiles
var perfilesTable; // Variable global para la tabla

function cargarPerfiles() {
    if ($.fn.DataTable.isDataTable('#perfiles-table')) {
        perfilesTable.destroy(); // Destruye la tabla existente si ya está inicializada
    }

    perfilesTable = $('#perfiles-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorPerfil.php?Accion=Listar',
            "dataSrc": ""
        },
        "columns": [
            { "data": "IdPerfil" },
            {
                // El servicio es opcional: NULL = perfil general (aplica a todos los servicios)
                "data": "NombreServicio",
                "render": function(data, type, row) {
                    if (data) return data;
                    return type === 'display' ? '<em style="color: var(--text-secondary);">General (todos los servicios)</em>' : '';
                }
            },
            { "data": "DescripcionPerfil" },
            { 
                "data": "IdEstado",
                "render": function(data, type, row) {
                    return `<span class="status ${data == 1 ? 'active' : 'inactive'}">${data == 1 ? 'Activo' : 'Inactivo'}</span>`;
                }
            },
            {
                "data": "IdPerfil",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="editarPerfil(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarPerfil(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
            }
        ],
            "paging": true,          // Activa la paginación
            "ordering": false,       // Desactiva la ordenación en todas las columnas
           
    });

}

function cargarServicios() {
    return fetch('../Controlador/ControladorServicio.php?Accion=Listar')
        .then(response => response.json())
        .then(servicios => {
            const select = document.getElementById('servicio-perfil');
            select.innerHTML = ''; // Limpiar opciones existentes
            
            // Opción por defecto: el servicio del perfil es opcional (vacío = perfil general)
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '— Sin servicio (perfil general) —';
            select.appendChild(defaultOption);

            // Agregar cada servicio como opción
            servicios.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.IdServicio;
                option.textContent = servicio.NombreServicio;
                select.appendChild(option);
            });
            
            return servicios;
        })
        .catch(error => {
            console.error('Error al cargar servicios:', error);
            return [];
        });
}

function abrirModalPerfil(perfilId = null) {
    const modal = document.getElementById('nueva-perfil-modal');
    const titulo = document.getElementById('nueva-perfil-modal-title');

    // Cargar servicios primero
        Promise.all([
        cargarServicios()
    ]).then(() => {
        if (perfilId) {
            titulo.textContent = 'Editar Perfil';
            // Cargar datos del perfil
            fetch(`../Controlador/ControladorPerfil.php?Accion=ListarPorIdGet&IdPerfil=${perfilId}`)
                .then(response => response.json())
                .then(data => {
                    // Solo se carga si llegó exactamente el perfil pedido (evita editar otro por error)
                    const perfil = Array.isArray(data) ? data.find(p => String(p.IdPerfil) === String(perfilId)) : null;
                    if (!perfil) {
                        throw new Error('No se encontró el perfil solicitado');
                    }
                    document.getElementById('perfil-id').value = perfil.IdPerfil;
                    document.getElementById('servicio-perfil').value = perfil.IdServicio ?? '';
                    document.getElementById('descripcion-perfil').value = perfil.DescripcionPerfil;
                    document.getElementById('perfil-estado').value = perfil.IdEstado;
                })
                .catch(error => {
                    console.error('Error al cargar el perfil:', error);
                    hideModal('nueva-perfil-modal');
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el perfil para editarlo',
                        confirmButtonText: 'Aceptar'
                    });
                });
        } else {
            titulo.textContent = 'Nuevo Perfil';
            // Limpiar formulario
            document.getElementById('perfil-id').value = '';
            document.getElementById('servicio-perfil').value = '';
            document.getElementById('descripcion-perfil').value = '';
            document.getElementById('perfil-estado').value = '1';
        }

        showModal('nueva-perfil-modal');
    });
}

function editarPerfil(id) {
    abrirModalPerfil(id);
}

function guardarPerfil() {
    const perfilId = document.getElementById('perfil-id').value;
    const accion = perfilId ? 'Actualizar' : 'Ingresar';
    const formData = new FormData();
    formData.append('Accion', accion);
    formData.append('IdPerfil', perfilId);
    formData.append('IdServicio', document.getElementById('servicio-perfil').value);
    formData.append('DescripcionPerfil', document.getElementById('descripcion-perfil').value);
    formData.append('IdEstado', document.getElementById('perfil-estado').value);
    formData.append('UsuarioCreacion', IdUsuario);
    formData.append('FechaCreacion', new Date().toISOString());
    formData.append('UsuarioModificacion', IdUsuario);
    formData.append('FechaModificacion', new Date().toISOString());

    // Validación antes de enviar. El servicio es opcional (vacío = perfil general); solo la descripción es obligatoria.
    if (!document.getElementById('descripcion-perfil').value.trim()) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor ingrese la descripción del perfil',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }

    fetch('../Controlador/ControladorPerfil.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        } catch (err) {
            console.error('Error parsing JSON:', text);
            throw new Error(text || `HTTP error! status: ${response.status}`);
        }
    })
    .then(data => {
        if (data.success) {
            hideModal('nueva-perfil-modal');
            perfilesTable.ajax.reload();
            
            showThemedAlert({
                icon: 'success',
                title: 'Éxito',
                text: 'Perfil ' + (accion === 'Ingresar' ? 'creado' : 'actualizado') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar el perfil',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

function eliminarPerfil(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdPerfil', id);

            fetch('../Controlador/ControladorPerfil.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    perfilesTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Perfil eliminado correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar el perfil',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar el perfil',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////SERVICIOS///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Variables globales
var serviciosTable;
var todosLosPerfiles = [];

// Función para cargar la tabla de servicios
// Mapa global de colores por IdPerfil
const perfilColorMap = {};
const badgeClasses = ['badge-primary', 'badge-success', 'badge-warning', 'badge-danger', 'badge-info'];
let colorIndex = 0;

serviciosTable = $('#servicios-table').DataTable({
    "ajax": {
        "url": '../Controlador/ControladorServicio.php?Accion=ListarServiciosPerfiles',
        "dataSrc": ""
    },
    "columns": [
        { "data": "IdServicio" },
        { "data": "NombreServicio" },
        { "data": "ObservacionesServicio" },
        { "data": "RelacionEnfermeroPaciente" },
        {
            // Personas requeridas (día / noche): suma de los perfiles; es lo que lee "Generar automáticamente"
            "data": null,
            "render": function(data, type, row) {
                return `${row.PerfilesDiaServicio ?? '—'} / ${row.PerfilesNocheServicio ?? '—'}`;
            }
        },
        {
            // Horas requeridas (día / noche): suma de las horas de los perfiles
            "data": null,
            "render": function(data, type, row) {
                return `${row.HorasDiaServicio ?? '—'} / ${row.HorasNocheServicio ?? '—'}`;
            }
        },
        {
            "data": "Perfiles",
            "render": function(data, type, row) {
                const nec = v => (v === null || v === undefined || v === '') ? '—' : v;
                const personas = v => (v === null || v === undefined || v === '') ? '— personas' : `${v} ${Number(v) === 1 ? 'persona' : 'personas'}`;
                if (type === 'display') {
                    return `<div style="display: flex; flex-wrap: wrap; gap: 5px;">${
                        data.map(perfil => {
                            // Si el IdPerfil aún no tiene color asignado, se asigna uno
                            if (!perfilColorMap[perfil.IdPerfil]) {
                                perfilColorMap[perfil.IdPerfil] = badgeClasses[colorIndex % badgeClasses.length];
                                colorIndex++;
                            }
                            const badgeClass = perfilColorMap[perfil.IdPerfil];
                            const detalle = `Día: ${personas(perfil.PerfilesDia)} · ${nec(perfil.HorasDia)} h | Noche: ${personas(perfil.PerfilesNoche)} · ${nec(perfil.HorasNoche)} h`;
                            return `<span class="badge ${badgeClass}" title="${detalle}">${perfil.DescripcionPerfil} <small>D ${nec(perfil.PerfilesDia)}p/${nec(perfil.HorasDia)}h · N ${nec(perfil.PerfilesNoche)}p/${nec(perfil.HorasNoche)}h</small></span>`;
                        }).join('')
                    }</div>`;
                }
                return data.map(perfil => perfil.DescripcionPerfil).join(', ');
            }
        },
        { 
            "data": "IdEstado",
            "render": function(data, type, row) {
                let estadoClass = 'active';
                let estadoText = 'Activo';
                
                if (data == 2) {
                    estadoClass = 'inactive';
                    estadoText = 'Inactivo';
                } else if (data == 3) {
                    estadoClass = 'pending';
                    estadoText = 'En implementación';
                }
                
                return `<span class="status ${estadoClass}">${estadoText}</span>`;
            }
        },
        {
            "data": "IdServicio",
            "render": function(data, type, row) {
                return `
                    <button class="btn-outline" onclick="verServicio(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-outline" onclick="editarServicio(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-outline" onclick="eliminarServicio(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            }
        }
    ],
    "columnDefs": [
        { "width": "50px", "targets": 0 },
        { "width": "120px", "targets": 1 },
        { "width": "200px", "targets": 2 },
    ],
    "paging": true,
    "ordering": false,
    "autoWidth": false
});


// Función para cargar todos los perfiles disponibles
function cargarEsquemasPerfil() {
    return fetch('../Controlador/ControladorEsquemaTurno.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            todosLosEsquemas = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar esquemas:', error);
            showThemedAlert({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los esquemas',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
            return [];
        });
}

function cargarTodosLosPerfiles() {
    return fetch('../Controlador/ControladorPerfil.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            todosLosPerfiles = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar perfiles:', error);
            showThemedAlert({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los perfiles',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
            return [];
        });
}
// Necesidad de cada perfil en el servicio: personas y horas requeridas en el turno de día y en el de noche
// (columnas de servicioperfil con estos mismos nombres). El esquema por perfil queda para más adelante.
const CAMPOS_NECESIDAD_PERFIL = [
    { clave: 'PerfilesDia', etiqueta: 'Personas día', ayuda: 'Cuántas personas de este perfil se necesitan en el turno de día' },
    { clave: 'PerfilesNoche', etiqueta: 'Personas noche', ayuda: 'Cuántas personas de este perfil se necesitan en el turno de noche' },
    { clave: 'HorasDia', etiqueta: 'Horas día', ayuda: 'Cuántas horas de este perfil se necesitan en el turno de día' },
    { clave: 'HorasNoche', etiqueta: 'Horas noche', ayuda: 'Cuántas horas de este perfil se necesitan en el turno de noche' }
];

// Pinta cada perfil con su casilla; al marcarlo se piden sus personas y horas de día y de noche. La suma de los
// perfiles marcados es la necesidad del servicio (el modelo la guarda en servicio.*Servicio, que es lo que leen el
// generador automático y la cobertura de Asignación de Turnos).
// "asociados": [{IdPerfil, HorasDia, HorasNoche, PerfilesDia, PerfilesNoche}] tal como los devuelve ObtenerPorId.
function renderPerfilesServicio(perfiles, asociados) {
    const contenedor = document.getElementById('perfiles-disponibles');
    contenedor.innerHTML = '';

    if (!perfiles || perfiles.length === 0) {
        const vacio = document.createElement('p');
        vacio.className = 'vacio';
        vacio.style.padding = '0';
        vacio.textContent = 'Aún no hay perfiles. Créalos en Enfermeros > Perfiles.';
        contenedor.appendChild(vacio);
        return;
    }

    const porId = {};
    (asociados || []).forEach(a => { porId[String(a.IdPerfil)] = a; });

    perfiles.forEach(perfil => {
        const asociado = porId[String(perfil.IdPerfil)];

        const item = document.createElement('div');
        item.className = 'perfil-item' + (asociado ? ' activo' : '');

        const cabecera = document.createElement('div');
        cabecera.className = 'perfil-item-cab';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'perfil-checkbox';
        checkbox.value = perfil.IdPerfil;
        checkbox.id = `perfil-${perfil.IdPerfil}`;
        checkbox.checked = !!asociado;
        checkbox.addEventListener('change', () => item.classList.toggle('activo', checkbox.checked));

        const label = document.createElement('label');
        label.htmlFor = checkbox.id;
        label.textContent = perfil.DescripcionPerfil;

        cabecera.appendChild(checkbox);
        cabecera.appendChild(label);

        const campos = document.createElement('div');
        campos.className = 'perfil-campos';
        CAMPOS_NECESIDAD_PERFIL.forEach(campo => {
            const grupo = document.createElement('div');
            grupo.className = 'perfil-campo';

            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.step = '1';
            input.className = 'form-control perfil-necesidad';
            input.id = `necesidad-${perfil.IdPerfil}-${campo.clave}`;
            input.dataset.perfilId = perfil.IdPerfil;
            input.dataset.campo = campo.clave;
            input.placeholder = '0';
            input.title = campo.ayuda;
            const valor = asociado ? asociado[campo.clave] : null;
            input.value = (valor === null || valor === undefined) ? '' : valor;

            const etiqueta = document.createElement('label');
            etiqueta.htmlFor = input.id;
            etiqueta.textContent = campo.etiqueta;
            etiqueta.title = campo.ayuda;

            grupo.appendChild(etiqueta);
            grupo.appendChild(input);
            campos.appendChild(grupo);
        });

        item.appendChild(cabecera);
        item.appendChild(campos);
        contenedor.appendChild(item);
    });

    const totales = document.createElement('div');
    totales.id = 'perfiles-totales';
    totales.className = 'perfiles-totales';
    contenedor.appendChild(totales);

    // Asignación directa (no addEventListener): el contenedor se reutiliza y así no se acumulan manejadores.
    contenedor.oninput = actualizarTotalesPerfiles;
    contenedor.onchange = actualizarTotalesPerfiles;
    actualizarTotalesPerfiles();
}

// Necesidad total del servicio = suma de los perfiles marcados (lo mismo que guarda el modelo).
function actualizarTotalesPerfiles() {
    const destino = document.getElementById('perfiles-totales');
    if (!destino) return;
    const suma = { PerfilesDia: 0, PerfilesNoche: 0, HorasDia: 0, HorasNoche: 0 };
    let hayDato = false;
    obtenerPerfilesSeleccionados().forEach(p => {
        CAMPOS_NECESIDAD_PERFIL.forEach(campo => {
            if (/^\d+$/.test(p[campo.clave])) {
                suma[campo.clave] += parseInt(p[campo.clave], 10);
                hayDato = true;
            }
        });
    });
    const personas = n => `${n} ${n === 1 ? 'persona' : 'personas'}`;
    destino.textContent = hayDato
        ? `Necesidad del servicio — Día: ${personas(suma.PerfilesDia)} · ${suma.HorasDia} h   |   Noche: ${personas(suma.PerfilesNoche)} · ${suma.HorasNoche} h`
        : 'Necesidad del servicio: sin definir (marca perfiles e indica personas y horas).';
}

// Función para abrir el modal de servicio
function abrirModalServicio(servicioId = null) {
    const titulo = document.getElementById('servicio-modal-title');

    cargarTodosLosPerfiles().then(perfiles => {
        if (servicioId) {
            titulo.textContent = 'Editar Servicio';

            // Cargar datos del servicio
            fetch(`../Controlador/ControladorServicio.php?Accion=ObtenerPorId&IdServicio=${servicioId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.IdServicio) {
                        throw new Error('Servicio no encontrado');
                    }
                    document.getElementById('servicio-id').value = data.IdServicio;
                    document.getElementById('servicio-nombre').value = data.NombreServicio ?? '';
                    document.getElementById('servicio-descripcion').value = data.ObservacionesServicio ?? '';
                    document.getElementById('emfermero-paciente').value = data.RelacionEnfermeroPaciente ?? '';
                    document.getElementById('servicio-estado').value = data.IdEstado ?? '1';

                    // La API devuelve los perfiles asociados con su necesidad en "Perfiles"
                    renderPerfilesServicio(perfiles, data.Perfiles || []);
                })
                .catch(error => {
                    console.error('Error al cargar servicio:', error);
                    hideModal('servicio-modal');
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar la información del servicio',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                });
        } else {
            titulo.textContent = 'Nuevo Servicio';
            // Limpiar formulario
            document.getElementById('servicio-id').value = '';
            document.getElementById('servicio-nombre').value = '';
            document.getElementById('servicio-descripcion').value = '';
            document.getElementById('emfermero-paciente').value = '';
            document.getElementById('servicio-estado').value = '1';
            renderPerfilesServicio(perfiles, []);
        }

        showModal('servicio-modal');
    });
}

// Función para guardar un servicio
function guardarServicio() {
    const servicioId = document.getElementById('servicio-id').value;
    const accion = servicioId ? 'Actualizar' : 'Ingresar';
    
    // Validación básica
    if (!document.getElementById('servicio-nombre').value) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor ingrese el nombre del servicio',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }
    
    // Perfiles marcados con su necesidad (personas y horas de día y de noche): cada valor vacío o entero >= 0
    const perfilesSeleccionados = obtenerPerfilesSeleccionados();
    const perfilInvalido = perfilesSeleccionados.find(p =>
        CAMPOS_NECESIDAD_PERFIL.some(campo => p[campo.clave] !== '' && !/^\d+$/.test(p[campo.clave])));
    if (perfilInvalido) {
        const etiqueta = document.querySelector(`label[for="perfil-${perfilInvalido.IdPerfil}"]`);
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: `Revisa las personas y horas de "${etiqueta ? etiqueta.textContent : 'un perfil'}": deben ser números enteros (0 o más)`,
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }

    const formData = new FormData();
    formData.append('Accion', accion);
    formData.append('IdServicio', servicioId);
    formData.append('NombreServicio', document.getElementById('servicio-nombre').value);
    formData.append('ObservacionesServicio', document.getElementById('servicio-descripcion').value);
    formData.append('RelacionEnfermeroPaciente', document.getElementById('emfermero-paciente').value);
    formData.append('IdEstado', document.getElementById('servicio-estado').value);
    formData.append('Perfiles', JSON.stringify(perfilesSeleccionados));
    formData.append('UsuarioCreacion', IdUsuario);
    formData.append('FechaCreacion', new Date().toISOString());
    formData.append('UsuarioModificacion', IdUsuario);
    formData.append('FechaModificacion', new Date().toISOString());

    fetch('../Controlador/ControladorServicio.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch {
            // Antes se mostraba el cuerpo crudo (p. ej. el HTML de un warning de PHP): ilegible para el usuario.
            console.error('Respuesta no válida de ControladorServicio.php:', text);
            throw new Error('El servidor devolvió una respuesta inesperada (no es JSON). El detalle está en la consola del navegador (F12).');
        }
    })
    .then(data => {
        if (data.success) {
            hideModal('servicio-modal');
            serviciosTable.ajax.reload();
            
            showThemedAlert({
                icon: 'success',
                title: 'Éxito',
                text: 'Servicio ' + (accion === 'Ingresar' ? 'creado' : 'actualizado') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            // El modelo devuelve 'message' (texto para el usuario) y 'error' (detalle técnico de MySQL)
            throw new Error(data.message || data.error || 'Error al guardar el servicio');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar el servicio',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

// Perfiles marcados en el formulario de servicio con su necesidad (personas y horas de día y noche);
// '' = sin definir. Es lo que se envía a ControladorServicio y lo que guarda servicioperfil.
function obtenerPerfilesSeleccionados() {
    return Array.from(document.querySelectorAll('.perfil-checkbox:checked')).map(checkbox => {
        const perfil = { IdPerfil: checkbox.value };
        CAMPOS_NECESIDAD_PERFIL.forEach(campo => {
            const input = document.getElementById(`necesidad-${checkbox.value}-${campo.clave}`);
            perfil[campo.clave] = input ? input.value.trim() : '';
        });
        return perfil;
    });
}

// Funciones auxiliares
function editarServicio(id) {
    abrirModalServicio(id);
}

function verServicio(id) {
    // Implementar vista detallada si es necesario
    showThemedAlert({
        icon: 'info',
        title: 'Información',
        text: 'Función de visualización detallada no implementada aún',
        confirmButtonText: 'Aceptar',
        customClass: {
            popup: 'swal2-dark'
        }
    });
}

function eliminarServicio(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción! Se eliminará el servicio y todas sus relaciones.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdServicio', id);

            fetch('../Controlador/ControladorServicio.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    serviciosTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Servicio eliminado correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    throw new Error(data.message || data.error || 'Error al eliminar el servicio');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al eliminar el servicio',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    cargarServicios();
});



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////ENFERMERO///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Variables globales para enfermeros
var enfermerosTable;
var todasLasEmpresas = [];
var todosLosPerfilesEnfermeria = [];
var todosLosServiciosEnfermeria = [];

// Mapa de colores para los servicios de apoyo
const servicioApoyoColorMap = {};
const contratoColorMap = {
    1: 'badge-primary',    // Planta
    2: 'badge-warning',    // Temporal
    3: 'badge-success',       // OPS
    4: 'badge-secondary',   // OTRO
    5: 'badge-info',        // INFO
    null: 'badge-danger'
};
let colorIndexServiciosApoyo = 0;

function cargarEnfermeros() {
    if ($.fn.DataTable.isDataTable('#enfermeros-table')) {
        enfermerosTable.destroy();
    }

    enfermerosTable = $('#enfermeros-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorMedico.php?Accion=ListarEnfermeros',
            "dataSrc": ""
        },
        "columns": [
            { "data": "NombreMedico" },
            { "data": "DocumentoMedico" },
            { "data": "Correomedico" },
            { 
                "data": "Empresa",
                "render": function(data, type, row) {
                    return data ? data.NombreEmpresa : 'Sin empresa';
                }
            },
            { 
                "data": "Empresa",
                "render": function(data, type, row) {
                        if (!data || data.TipoContrato === null) {
                            const badgeClass = contratoColorMap[null];
                            return `<span class="badge ${badgeClass}">Sin contrato</span>`;
                        }
                    
                    const tipoContrato = data.TipoContrato;
                    let textoContrato = '';
                    
                    switch(tipoContrato) {
                        case 1: textoContrato = 'Planta'; break;
                        case 2: textoContrato = 'Temporal'; break;
                        case 3: textoContrato = 'OPS'; break;
                        case 4: textoContrato = 'OTRO'; break;
                        default: textoContrato = 'Sin contrato';
                    }
                    
                    const badgeClass = contratoColorMap[tipoContrato] || 'badge-secondary';
                    return `<span class="badge ${badgeClass}">${textoContrato}</span>`;
                },
                "width": "100px"
            },
            { 
                "data": "Perfil",
                "render": function(data, type, row) {

                    if (!data) return 'Sin perfil';

                    if (data.IdPerfil === null || data.IdPerfil === undefined) {
                       
                        const badgeClass = contratoColorMap[null];
                        return `<span class="badge ${badgeClass}">Sin perfil</span>`;
                    }
                    // Asignar color si no está asignado
                    if (!perfilColorMap[data.IdPerfil]) {
                        perfilColorMap[data.IdPerfil] = badgeClasses[colorIndex % badgeClasses.length];
                        colorIndex++;
                    }
                    return `<span class="badge ${perfilColorMap[data.IdPerfil]}">${data.DescripcionPerfil}</span>`;
                }
            },
            { 
                "data": "Servicio",
                "render": function(data, type, row) {
                    return data ? data.NombreServicio : 'Sin servicio';
                }
            },
            { 
                "data": "ServiciosApoyo",
                "render": function(data, type, row) {
                     if (!data || data.length === 0) {
                        const badgeClass = contratoColorMap[null];
                        return `<span class="badge ${badgeClass}">Sin servicios de apoyo</span>`;
                    }

                    return `<div style="display: flex; flex-wrap: wrap; gap: 5px;">${
                        data.map(servicio => {
                            // Asignar color si no está asignado
                            if (!servicioApoyoColorMap[servicio.IdServicio]) {
                                servicioApoyoColorMap[servicio.IdServicio] = badgeClasses[colorIndexServiciosApoyo % badgeClasses.length];
                                colorIndexServiciosApoyo++;
                            }
                            const badgeClass = servicioApoyoColorMap[servicio.IdServicio];
                            return `<span class="badge ${badgeClass}">${servicio.NombreServicio}</span>`;
                        }).join('')
                    }</div>`;
                }
            },
            { 
                "data": "EstadoMedico",
                "render": function(data, type, row) {
                    return `<span class="status ${data == 1 ? 'active' : 'inactive'}">${data == 1 ? 'Activo' : 'Inactivo'}</span>`;
                }
            },
            {
                "data": "SueldoBaseMedico",
            },
            {
                "data": "IdMedico",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="editarEnfermero(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarEnfermero(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
            }
        ],
        "columnDefs": [

            { "width": "120px", "targets": 0 },
            { "width": "100px", "targets": 1 },
            { "width": "100px", "targets": 2 },
            { "width": "150px", "targets": 3 },
            { "width": "120px", "targets": 4 },
            { "width": "120px", "targets": 5 },
            { "width": "120px", "targets": 6 },
            { "width": "200px", "targets": 7 },
            { "width": "80px", "targets": 8 },
            { "width": "80px", "targets": 9 },
            { "width": "80px", "targets": 10 }
        ],
        "paging": true,
        "ordering": false,
        "autoWidth": false
    });
}

// Función para cargar todas las empresas
function cargarEmpresasEnfermeria() {
    return fetch('../Controlador/ControladorEmpresa.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            todasLasEmpresas = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar empresas:', error);
            return [];
        });
}

// Función para cargar todos los perfiles de enfermería
function cargarPerfilesEnfermeria() {
    return fetch('../Controlador/ControladorPerfil.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            todosLosPerfilesEnfermeria = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar perfiles:', error);
            return [];
        });
}

// Función para cargar todos los servicios
function cargarServiciosEnfermeria() {
    return fetch('../Controlador/ControladorServicio.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            todosLosServiciosEnfermeria = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar servicios:', error);
            return [];
        });
}

// El servicio elegido como principal no se ofrece como apoyo: se oculta y se desmarca.
function ocultarServicioPrincipalEnApoyo() {
    const principal = document.getElementById('enfermero-servicio').value;
    document.querySelectorAll('#servicios-apoyo-disponibles .form-check').forEach(div => {
        const esPrincipal = principal !== '' && div.dataset.servicio === principal;
        div.style.display = esPrincipal ? 'none' : '';
        if (esPrincipal) {
            const cb = div.querySelector('input');
            if (cb) cb.checked = false;
        }
    });
}

// Regla contrato <-> empresa: el contrato Planta pertenece a la empresa marcada «Maneja personal de planta» (el
// Hospital); Temporal, OPS y Otro van en cualquier otra empresa. Mantiene enlazados los dos selects; el servidor
// aplica la misma regla al guardar.
function empresaDePlantaEnfermeria() {
    return todasLasEmpresas.find(e => String(e.ManejaPlanta) === '1') || null;
}

// cargaInicial = true cuando se llama al abrir un registro guardado (el aviso explica qué traía); false cuando el
// usuario cambia un select.
function aplicarReglaContratoEmpresa(cargaInicial) {
    const selEmpresa = document.getElementById('enfermero-empresa');
    const selContrato = document.getElementById('enfermero-contrato');
    const ayuda = document.getElementById('enfermero-contrato-ayuda');
    const optPlanta = selContrato.querySelector('option[value="1"]');
    const planta = empresaDePlantaEnfermeria();
    let texto = '';
    let alerta = false;

    if (!planta) {
        optPlanta.disabled = true;
        if (selContrato.value === '1') selContrato.value = '';
        selEmpresa.disabled = false;
        Array.from(selEmpresa.options).forEach(o => { o.disabled = false; });
        texto = 'No hay una empresa marcada como «Maneja personal de planta» (menú Empresas): sin ella no se puede registrar contrato Planta.';
        alerta = true;
    } else {
        const nombre = decodificarEntidades(planta.NombreEmpresa);
        optPlanta.disabled = false;
        const esPlanta = selContrato.value === '1';
        if (esPlanta) {
            selEmpresa.value = planta.IdEmpresa;
            texto = 'Contrato Planta: la empresa es siempre «' + nombre + '».';
        } else {
            if (String(selEmpresa.value) === String(planta.IdEmpresa)) {
                selEmpresa.value = '';
                texto = cargaInicial
                    ? 'Este registro estaba en «' + nombre + '» con un contrato que no es Planta, y esa empresa solo maneja personal de planta. ' +
                      'Si es de planta, cambie el contrato a Planta; si es temporal, elija su empresa.'
                    : '«' + nombre + '» solo maneja personal de planta; para este contrato elija otra empresa.';
                alerta = !!cargaInicial;
            } else if (selContrato.value !== '') {
                texto = '«' + nombre + '» solo maneja personal de planta; para este contrato elija otra empresa.';
            } else {
                texto = 'Elija el tipo de contrato: Planta es siempre «' + nombre + '».';
            }
        }
        selEmpresa.disabled = esPlanta;
        Array.from(selEmpresa.options).forEach(o => {
            o.disabled = !esPlanta && o.value !== '' && String(o.value) === String(planta.IdEmpresa);
        });
    }
    ayuda.textContent = texto;
    ayuda.style.color = alerta ? '#c0392b' : '';
}

function abrirModalEnfermero(enfermeroId = null) {
    const modal = document.getElementById('nueva-enfermero-modal');
    const titulo = document.getElementById('nueva-enfermero-modal-title');

    // Cargar todos los datos necesarios primero
    Promise.all([
        cargarEmpresasEnfermeria(),
        cargarPerfilesEnfermeria(),
        cargarServiciosEnfermeria()
    ]).then(() => {
        // Llenar select de empresas
        const selectEmpresa = document.getElementById('enfermero-empresa');
        selectEmpresa.innerHTML = '<option value="">Seleccione una empresa</option>';
        todasLasEmpresas.forEach(empresa => {
            const option = document.createElement('option');
            option.value = empresa.IdEmpresa;
            option.textContent = decodificarEntidades(empresa.NombreEmpresa);
            selectEmpresa.appendChild(option);
        });

        // Llenar select de perfiles
        const selectPerfil = document.getElementById('enfermero-perfil');
        selectPerfil.innerHTML = '<option value="">Seleccione un perfil</option>';
        todosLosPerfilesEnfermeria.forEach(perfil => {
            const option = document.createElement('option');
            option.value = perfil.IdPerfil;
            option.textContent = perfil.DescripcionPerfil;
            selectPerfil.appendChild(option);
        });

        // Llenar select de servicios
        const selectServicio = document.getElementById('enfermero-servicio');
        selectServicio.innerHTML = '<option value="">Seleccione un servicio</option>';
        todosLosServiciosEnfermeria.forEach(servicio => {
            const option = document.createElement('option');
            option.value = servicio.IdServicio;
            option.textContent = servicio.NombreServicio;
            selectServicio.appendChild(option);
        });

        // Checkboxes de servicios de apoyo (uno por servicio; el principal se oculta)
        const apoyoContainer = document.getElementById('servicios-apoyo-disponibles');
        apoyoContainer.innerHTML = '';
        todosLosServiciosEnfermeria.forEach(servicio => {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check';
            checkboxDiv.dataset.servicio = servicio.IdServicio;

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input servicio-apoyo-checkbox';
            checkbox.value = servicio.IdServicio;
            checkbox.id = `servicio-apoyo-${servicio.IdServicio}`;

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = `servicio-apoyo-${servicio.IdServicio}`;
            label.textContent = servicio.NombreServicio;

            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            apoyoContainer.appendChild(checkboxDiv);
        });
        selectServicio.onchange = ocultarServicioPrincipalEnApoyo;
        document.getElementById('enfermero-contrato').onchange = () => aplicarReglaContratoEmpresa(false);
        selectEmpresa.onchange = () => aplicarReglaContratoEmpresa(false);

        if (enfermeroId) {
            titulo.textContent = 'Editar Enfermero';
            // Cargar datos del enfermero
            fetch(`../Controlador/ControladorMedico.php?Accion=ListarPorIdGet&IdMedico=${enfermeroId}`)
                .then(response => response.json())
                .then(data => {
                    const enfermero = data[0];
                    document.getElementById('enfermero-id').value = enfermero.IdMedico;
                    document.getElementById('enfermero-nombre').value = enfermero.NombreMedico;
                    document.getElementById('enfermero-documento').value = enfermero.DocumentoMedico;
                    document.getElementById('enfermero-email').value = enfermero.Correomedico;
                    document.getElementById('enfermero-telefono').value = enfermero.TelefonoMedico;
                    document.getElementById('enfermero-especialidad').value = enfermero.NombreEspecialidad;
                    document.getElementById('enfermero-Idespecialidad').value = enfermero.IdEspecialidad;
                    document.getElementById('enfermero-sueldo').value = enfermero.SueldoBaseMedico;
                    document.getElementById('enfermero-Idarea').value = enfermero.IdAreaMedico;
                    document.getElementById('enfermero-estado').value = enfermero.EstadoMedico;

                    // Si hay datos de empresa, perfil y servicio
                    if (enfermero.Empresa) {
                       
                        document.getElementById('enfermero-empresa').value = enfermero.Empresa.IdEmpresa;
                        document.getElementById('enfermero-contrato').value = enfermero.Empresa.TipoContrato || '';
                    } else {
                        document.getElementById('enfermero-empresa').value = '';
                        document.getElementById('enfermero-contrato').value = '';
                    }
                    
                    if (enfermero.Perfil) {
                        document.getElementById('enfermero-perfil').value = enfermero.Perfil.IdPerfil;
                    }
                    
                    if (enfermero.Servicio) {
                        document.getElementById('enfermero-servicio').value = enfermero.Servicio.IdServicio;
                    }

                    // Marcar los servicios de apoyo guardados
                    if (enfermero.ServiciosApoyo && enfermero.ServiciosApoyo.length > 0) {
                        enfermero.ServiciosApoyo.forEach(servicio => {
                            const checkbox = document.getElementById(`servicio-apoyo-${servicio.IdServicio}`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                    ocultarServicioPrincipalEnApoyo();
                    aplicarReglaContratoEmpresa(true);
                });
        } else {
            titulo.textContent = 'Nuevo Enfermero';
            // Limpiar formulario
            document.getElementById('enfermero-id').value = '';
            document.getElementById('enfermero-nombre').value = '';
            document.getElementById('enfermero-documento').value = '';
            document.getElementById('enfermero-email').value = '';
            document.getElementById('enfermero-telefono').value = '';
            document.getElementById('enfermero-especialidad').value = 'ENFERMEROS Y AUXILIARES DE ENFERMERIA';
            document.getElementById('enfermero-Idespecialidad').value = '';
            document.getElementById('enfermero-Idarea').value = '';
            document.getElementById('enfermero-sueldo').value = '';
            document.getElementById('enfermero-estado').value = '1';
            document.getElementById('enfermero-empresa').value = '';
            document.getElementById('enfermero-contrato').value = '';
            document.getElementById('enfermero-perfil').value = '';
            document.getElementById('enfermero-servicio').value = '';
            
            // Desmarcar todos los servicios de apoyo
            document.querySelectorAll('.servicio-apoyo-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            ocultarServicioPrincipalEnApoyo();
            aplicarReglaContratoEmpresa(true);
        }

        showModal('nueva-enfermero-modal');
    }).catch(error => {
        console.error('Error al cargar datos para el modal:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los datos necesarios',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

function guardarEnfermero() {
    const enfermeroId = document.getElementById('enfermero-id').value;
    const accion = enfermeroId ? 'Actualizar' : 'Ingresar';
    
    // Validaciones básicas
    if (!document.getElementById('enfermero-nombre').value) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor ingrese el nombre del enfermero',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }

    // Regla contrato <-> empresa (el servidor la vuelve a validar)
    const contratoElegido = document.getElementById('enfermero-contrato').value;
    const empresaElegida = document.getElementById('enfermero-empresa').value;
    if (contratoElegido !== '' && contratoElegido !== '1' && empresaElegida === '') {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Seleccione la empresa temporal a la que pertenece. Solo el contrato Planta no la pide: es siempre la del Hospital.',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }
    if (contratoElegido === '' && empresaElegida !== '') {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Seleccione el tipo de contrato.',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }

    // Servicios de apoyo marcados (el servicio principal nunca cuenta como apoyo)
    const principalSeleccionado = document.getElementById('enfermero-servicio').value;
    const serviciosApoyoSeleccionados = Array.from(document.querySelectorAll('.servicio-apoyo-checkbox:checked'))
        .map(checkbox => checkbox.value)
        .filter(id => id !== principalSeleccionado);

    const formData = new FormData();
    formData.append('Accion', accion);
    formData.append('IdMedico', enfermeroId);
    formData.append('NombreMedico', document.getElementById('enfermero-nombre').value);
    formData.append('DocumentoMedico', document.getElementById('enfermero-documento').value);
    formData.append('Correomedico', document.getElementById('enfermero-email').value);
    formData.append('TelefonoMedico', document.getElementById('enfermero-telefono').value);
    formData.append('NombreEspecialidad', document.getElementById('enfermero-especialidad').value);
    formData.append('IdAreaMedico', document.getElementById('enfermero-Idarea').value);
    formData.append('IdEspecialidadMedico', document.getElementById('enfermero-Idespecialidad').value);
    formData.append('SueldoBaseMedico', document.getElementById('enfermero-sueldo').value);
    formData.append('EstadoMedico', document.getElementById('enfermero-estado').value);
    
    // Datos adicionales
    formData.append('IdEmpresa', document.getElementById('enfermero-empresa').value);
    formData.append('TipoContrato', document.getElementById('enfermero-contrato').value);
    formData.append('IdPerfil', document.getElementById('enfermero-perfil').value);
    formData.append('IdServicio', document.getElementById('enfermero-servicio').value);
    formData.append('ServiciosApoyo', JSON.stringify(serviciosApoyoSeleccionados));
    
    if(accion === 'Ingresar') {
        formData.append('IdAreaMedico', 59);
        formData.append('IdEspecialidadMedico', 149);
    }

   

  const json = {};

    for (let [key, value] of formData.entries()) {
        json[key] = value;
    }

    console.log(JSON.stringify(json, null, 2));
    //alert(JSON.stringify(json, null, 2));

    fetch('../Controlador/ControladorMedico.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        } catch (err) {
            console.error('Error parsing JSON:', text);
            throw new Error(text || `HTTP error! status: ${response.status}`);
        }
    })
    .then(data => {
        if (data.success) {
            hideModal('nueva-enfermero-modal');
            if (typeof enfermerosTable !== 'undefined' && enfermerosTable && enfermerosTable.ajax) {
                enfermerosTable.ajax.reload();
            }
            cargarDiagnosticoContratos();
            showThemedAlert({
                icon: 'success',
                title: 'Éxito',
                text: 'Enfermero ' + (accion === 'Ingresar' ? 'creado' : 'actualizado') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar el enfermero',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}
function editarEnfermero(id) {
    abrirModalEnfermero(id);
}

// Las funciones editarEnfermero y eliminarEnfermero se mantienen igual


function eliminarEnfermero(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdMedico', id);

            fetch('../Controlador/ControladorMedico.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    enfermerosTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Enfermero eliminado correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar el enfermero',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar el enfermero',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////GENERALIDAD/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Funciones para gestión de habilidades
var generalidadesTable; // Variable global para la tabla

function cargarGeneralidades() {
    if ($.fn.DataTable.isDataTable('#generalidades-table')) {
        generalidadesTable.destroy(); // Destruye la tabla existente si ya está inicializada
    }

    generalidadesTable = $('#generalidades-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorGeneralidades.php?Accion=Listar',
            "dataSrc": ""
        },
        "columns": [
            { "data": "IdGeneralidades" },
            { "data": "NombreEmpresa" },
            { 
                "data": "HorSemanales",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " hora" : " horas");
                }
            },
            { 
                "data": "MaxTrilibres",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " jornada libre" : " jorndas libres");
                }
            },
                        { 
                "data": "MaxLibres",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " dias libre" : " dias libres");
                }
            },
            { 
                "data": "MaxHoras",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " hora" : " horas");
                }
            },
            { 
                "data": "MaxNoches",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " noche" : " noches");
                }
            },
            { 
                "data": "MaxTurnos",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " turno" : " turnos");
                }
            },
            { 
                "data": "MaxDescanso",
                "render": function(data, type, row) {
                    return data + (data == 1 ? " día" : " días");
                }
            },
            {
                "data": "IdGeneralidades",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="editarGeneralidad(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarGeneralidad(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        "paging": true,          // Activa la paginación
        "ordering": false,       // Desactiva la ordenación en todas las columnas
        "language": {
            "url": "../Vista/js/es-ES.json" // Español si lo deseas
        }
    });
}


function abrirModalGeneralidades(generalidadId = null) {

    // Limpiar campos del modal
    const modal = document.getElementById('nueva-generalidad-modal');
    const titulo = document.getElementById('nueva-generalidad-modal-title');

        Promise.all([
        cargarEmpresasEnfermeria(),
    ]).then(() => {
        // Llenar select de empresas
        const selectEmpresa = document.getElementById('nombre-empresa');
        selectEmpresa.innerHTML = '<option value="">Seleccione una empresa</option>';
        todasLasEmpresas.forEach(empresa => {
            const option = document.createElement('option');
            option.value = empresa.IdEmpresa;
            option.textContent = empresa.NombreEmpresa;
            selectEmpresa.appendChild(option);
        });

            if (generalidadId) {
        titulo.textContent = 'Editar Generalidad';
        // Cargar datos de la generalidad a editar
        fetch(`../Controlador/ControladorGeneralidades.php?Accion=ListarPorIdGet&IdGeneralidades=${generalidadId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('generalidades-id').value = data[0].IdGeneralidades;
                document.getElementById('idEmpresa').value = data[0].IdEmpresa;
                document.getElementById('horas-semanales').value = data[0].HorSemanales;
                document.getElementById('max-turnos-libres').value = data[0].MaxTrilibres;
                document.getElementById('max-dias-libres').value = data[0].MaxLibres;
                document.getElementById('max-horas').value = data[0].MaxHoras;
                document.getElementById('max-noches').value = data[0].MaxNoches;
                document.getElementById('max-turnos').value = data[0].MaxTurnos;
                document.getElementById('max-descanso').value = data[0].MaxDescanso;
                document.getElementById('nombre-empresa').value = data[0].IdEmpresa;
            });
    }else {
        titulo.textContent = 'Nueva Generalidad';
        // Limpiar campos del modal
        document.getElementById('generalidades-id').value = '';
        document.getElementById('idEmpresa').value = '';
        document.getElementById('nombre-empresa').value = '';
        document.getElementById('horas-semanales').value = '';
        document.getElementById('max-turnos-libres').value = '';
        document.getElementById('max-dias-libres').value = '';
        document.getElementById('max-horas').value = '';
        document.getElementById('max-noches').value = '';
        document.getElementById('max-turnos').value = '';
        document.getElementById('max-descanso').value = '';
    }
    showModal('nueva-generalidad-modal');

         }).catch(error => {
        console.error('Error al cargar datos para el modal:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los datos necesarios',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });   
}

function editarGeneralidad(id) {
    abrirModalGeneralidades(id);
}

function guardarGeneralidad() {
    const generalidadId = document.getElementById('generalidades-id').value;
    const accion = generalidadId ? 'Actualizar' : 'Ingresar';
    const formData = new FormData();
    formData.append('Accion', accion);
    formData.append('IdGeneralidades', generalidadId);
    formData.append('IdEmpresa', document.getElementById('nombre-empresa').value);
    formData.append('HorSemanales', document.getElementById('horas-semanales').value);
    formData.append('MaxTrilibres', document.getElementById('max-turnos-libres').value);
    formData.append('MaxLibres', document.getElementById('max-dias-libres').value);
    formData.append('MaxHoras', document.getElementById('max-horas').value);
    formData.append('MaxNoches', document.getElementById('max-noches').value);
    formData.append('MaxTurnos', document.getElementById('max-turnos').value);
    formData.append('MaxDescanso', document.getElementById('max-descanso').value);

    // Validación antes de enviar
    if (!document.getElementById('nombre-empresa').value) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor ingrese una descripción para la generalidad',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }

    fetch('../Controlador/ControladorGeneralidades.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        } catch (err) {
            console.error('Error parsing JSON:', text);
            throw new Error(text || `HTTP error! status: ${response.status}`);
        }
    })
    .then(data => {
        if (data.success) {
            hideModal('nueva-generalidad-modal');
            generalidadesTable.ajax.reload();

            showThemedAlert({
                icon: 'success',
                title: 'Éxito',
                text: 'Generalidad ' + (accion === 'Ingresar' ? 'creada' : 'actualizada') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar la generalidad',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

function eliminarGeneralidad(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdGeneralidades', id);

            fetch('../Controlador/ControladorGeneralidades.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    generalidadesTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Generalidad eliminada correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    showThemedAlert({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al eliminar la generalidad',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar la generalidad',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////TIPO DE TURNO///////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Variables globales para las tablas
var tiposTurnoTable;
var esquemasTable;
var tiposTurnoDisponibles = [];

// Función para calcular la diferencia en horas entre dos horas
function calcularDiferenciaHoras(horaInicio, horaFin) {
    // Convertir horas a minutos desde medianoche
    const [inicioHoras, inicioMinutos] = horaInicio.split(':').map(Number);
    const [finHoras, finMinutos] = horaFin.split(':').map(Number);
    
    const inicioTotalMinutos = inicioHoras * 60 + inicioMinutos;
    const finTotalMinutos = finHoras * 60 + finMinutos;
    
    // Calcular diferencia en minutos
    let diferenciaMinutos = finTotalMinutos - inicioTotalMinutos;
    
    // Si la hora fin es menor que la inicio, asumimos que es del día siguiente
    if (diferenciaMinutos < 0) {
        diferenciaMinutos += 24 * 60; // Agregar 24 horas en minutos
    }
    
    // Convertir a horas (con decimales para los minutos)
    return diferenciaMinutos / 60;
}

// Función para actualizar la duración cuando cambian las horas
function actualizarDuracion() {
    const horaInicio = document.getElementById('tipo-turno-hora-inicio').value;
    const horaFin = document.getElementById('tipo-turno-hora-fin').value;
    
    if (horaInicio && horaFin) {
        const duracion = calcularDiferenciaHoras(horaInicio, horaFin);
        document.getElementById('tipo-turno-duracion').value = duracion.toFixed(1);
    }
}

// Agregar event listeners a los campos de hora
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tipo-turno-hora-inicio').addEventListener('change', actualizarDuracion);
    document.getElementById('tipo-turno-hora-fin').addEventListener('change', actualizarDuracion);
});

// Función para cargar los tipos de turno
function cargarTiposTurno() {
    if ($.fn.DataTable.isDataTable('#tipos-turno-table')) {
        tiposTurnoTable.destroy();
    }

    tiposTurnoTable = $('#tipos-turno-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorTipoTurno.php?Accion=Listar',
            "dataSrc": ""
        },
        "columns": [
            { 
                "data": "Codigo",
                "className": "text-center"
            },
            { "data": "Nombre" },
            { 
                "data": null,
                "render": function(data, type, row) {
                    return `${row.HoraInicio} - ${row.HoraFin}`;
                }
            },
            { 
                "data": "Horas",
                "render": function(data) {
                    return data + ' horas';
                },
                "className": "text-center"
            },
            { "data": "Descripcion" },
            {
                "data": "IdTipoTurno",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="abrirModalTipoTurno(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarTipoTurno(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
                "className": "text-center"
            }
        ],
        "paging": true,          // Activa la paginación
        "ordering": false,       // Desactiva la ordenación en todas las columnas
        "language": {
            "url": "../Vista/js/es-ES.json" // Español si lo deseas
        }
    });
}

// Función para abrir modal de tipo de turno
function abrirModalTipoTurno(tipoTurnoId = null) {
    const modal = document.getElementById('tipo-turno-modal');
    const titulo = document.getElementById('tipo-turno-modal-title');
    
    if (tipoTurnoId) {
        titulo.textContent = 'Editar Tipo de Turno';
        // Cargar datos del tipo de turno
        const formData = new FormData();
        formData.append('IdTipoTurno', tipoTurnoId);
            fetch('../Controlador/ControladorTipoTurno.php?Accion=ListarPorId', {
                method: 'POST',
                body: formData
            })

            .then(response => response.json())
            .then(data => {
                document.getElementById('tipo-turno-id').value = data.IdTipoTurno;
                document.getElementById('tipo-turno-codigo').value = data.Codigo;
                document.getElementById('tipo-turno-nombre').value = data.Nombre;
                document.getElementById('tipo-turno-hora-inicio').value = data.HoraInicio;
                document.getElementById('tipo-turno-hora-fin').value = data.HoraFin;
                
                // Calcular duración automáticamente
                const duracion = calcularDiferenciaHoras(data.HoraInicio, data.HoraFin);
                document.getElementById('tipo-turno-duracion').value = duracion.toFixed(1);
                
                document.getElementById('tipo-turno-descripcion').value = data.Descripcion;
            });
    } else {
        titulo.textContent = 'Nuevo Tipo de Turno';
        // Limpiar formulario
        document.getElementById('tipo-turno-id').value = '';
        document.getElementById('tipo-turno-codigo').value = '';
        document.getElementById('tipo-turno-nombre').value = '';
        document.getElementById('tipo-turno-hora-inicio').value = '';
        document.getElementById('tipo-turno-hora-fin').value = '';
        document.getElementById('tipo-turno-duracion').value = '';
        document.getElementById('tipo-turno-descripcion').value = '';
    }
    
    showModal('tipo-turno-modal');
}

// Función para guardar tipo de turno
function guardarTipoTurno() {
    const tipoTurnoId = document.getElementById('tipo-turno-id').value;
    const accion = tipoTurnoId ? 'Actualizar' : 'Ingresar';
    const formData = new FormData();
    
    const horaInicio = document.getElementById('tipo-turno-hora-inicio').value;
    const horaFin = document.getElementById('tipo-turno-hora-fin').value;
    
    // Validación de horas
    if (horaInicio && horaFin) {
        const duracion = calcularDiferenciaHoras(horaInicio, horaFin);
        if (duracion <= 0) {
            showThemedAlert({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La hora de fin debe ser posterior a la hora de inicio',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
            return;
        }
        
        if (duracion > 12) {
            showThemedAlert({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La duración no puede ser mayor a 12 horas',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
            return;
        }
    }
    
    // Validación básica
    if (!document.getElementById('tipo-turno-codigo').value || 
        !document.getElementById('tipo-turno-nombre').value) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor complete los campos obligatorios',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
        return;
    }
    
    formData.append('Accion', accion);
    formData.append('IdTipoTurno', tipoTurnoId);
    formData.append('Codigo', document.getElementById('tipo-turno-codigo').value);
    formData.append('Nombre', document.getElementById('tipo-turno-nombre').value);
    formData.append('HoraInicio', document.getElementById('tipo-turno-hora-inicio').value);
    formData.append('HoraFin', document.getElementById('tipo-turno-hora-fin').value);
    formData.append('Horas', document.getElementById('tipo-turno-duracion').value);
    formData.append('Descripcion', document.getElementById('tipo-turno-descripcion').value);

    fetch('../Controlador/ControladorTipoTurno.php?Accion=' + accion, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideModal('tipo-turno-modal');
            tiposTurnoTable.ajax.reload();
            showThemedAlert({
                icon: 'success',
                title: 'Éxito',
                text: 'Tipo de turno ' + (accion === 'Ingresar' ? 'creado' : 'actualizado') + ' correctamente',
                confirmButtonText: 'Aceptar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar el tipo de turno',
            confirmButtonText: 'Aceptar',
            customClass: {
                popup: 'swal2-dark'
            }
        });
    });
}

// Función para eliminar tipo de turno
function eliminarTipoTurno(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdTipoTurno', id);

            fetch('../Controlador/ControladorTipoTurno.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    tiposTurnoTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Tipo de turno eliminado correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el tipo de turno');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al eliminar el tipo de turno',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////ESQUEMAS/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Variables globales para tipos de turno
var tiposTurnoDisponibles = [];
var detalleEsquemaActual = [];

function cargarEsquemas() {
    if ($.fn.DataTable.isDataTable('#esquemas-table')) {
        esquemasTable.destroy();
    }

    esquemasTable = $('#esquemas-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorEsquemaTurno.php?Accion=Listar',
            "dataSrc": ""
        },
        "columns": [
            { "data": "Nombre" },
            { 
                "data": "IdEsquema",
                "render": function(data, type, row) {
                    // Cargar detalles del esquema para mostrar la secuencia
                    return cargarSecuenciaEsquema(data);
                }
            },
            { "data": "DiasAplicacion" },
            { "data": "Descripcion" },
            { 
                "data": "Activo",
                "render": function(data) {
                    return `<span class="status ${data ? 'active' : 'inactive'}">${data ? 'Activo' : 'Inactivo'}</span>`;
                },
                "className": "text-center"
            },
            {
                "data": "IdEsquema",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="abrirModalEsquema(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarEsquema(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
                "className": "text-center"
            }
        ],
        "paging": true,
        "ordering": false,
        "language": {
            "url": "../Vista/js/es-ES.json"
        }
    });
}

// Función para cargar la secuencia de un esquema
function cargarSecuenciaEsquema(esquemaId) {
    // Esta función se ejecuta para cada celda, pero necesitamos una solución asíncrona
    // Por ahora retornamos un placeholder y actualizamos después de cargar
    return `<span class="loading-secuencia" data-esquema="${esquemaId}">Cargando...</span>`;
}

// Función para cargar los detalles de todos los esquemas después de cargar la tabla
function cargarDetallesEsquemas() {
    $('.loading-secuencia').each(function() {
        const esquemaId = $(this).data('esquema');
        cargarDetalleEsquema(esquemaId, $(this));
    });
}

// Función para cargar el detalle de un esquema específico
function cargarDetalleEsquema(esquemaId, elemento) {
    fetch(`../Controlador/ControladorDetalleEsquema.php?Accion=ListarPorEsquema&IdEsquema=${esquemaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success !== false) {
                // Ordenar por el campo Orden y obtener los códigos
                const secuenciaOrdenada = data.sort((a, b) => a.Orden - b.Orden);
                
                // Usa directamente detalle.Codigo en lugar de buscar en tiposTurnoDisponibles
                const codigos = secuenciaOrdenada.map(detalle => detalle.Codigo || '?');
                
                elemento.text(codigos.join('-'));
            } else {
                elemento.text('Sin turnos');
            }
        })
        .catch(error => {
            console.error('Error al cargar detalle del esquema:', error);
            elemento.text('Error');
        });
}

// Función para cargar los tipos de turno disponibles
function cargarTiposTurnoDisponibles() {
    return fetch('../Controlador/ControladorTipoTurno.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            tiposTurnoDisponibles = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar tipos de turno:', error);
            return [];
        });
}

// Función para llenar los selects de turnos
function llenarSelectTurnos(selectElement) {
    selectElement.innerHTML = '';
    tiposTurnoDisponibles.forEach(tipo => {
        const option = document.createElement('option');
        option.value = tipo.IdTipoTurno;
        option.textContent = `${tipo.Codigo}`;
        option.title = `${tipo.HoraInicio} - ${tipo.HoraFin} (${tipo.Horas} horas)`;
        selectElement.appendChild(option);
    });
}

// Función para agregar un nuevo turno a la secuencia
function agregarTurno() {
    const container = document.getElementById('secuencia-turnos-container');
    const index = container.children.length + 1;
    
    const turnoDiv = document.createElement('div');
    turnoDiv.className = 'turno-item';
    turnoDiv.dataset.index = index;
    
    const select = document.createElement('select');
    select.className = 'form-control turno-select';
    select.required = true;
    llenarSelectTurnos(select);
    
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.className = 'btn btn-outline btn-eliminar-turno';
    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
    deleteBtn.onclick = function() { eliminarTurno(this); };
    
    turnoDiv.appendChild(select);
    turnoDiv.appendChild(deleteBtn);
    container.appendChild(turnoDiv);
}

// Función para eliminar un turno de la secuencia
function eliminarTurno(button) {
    const container = document.getElementById('secuencia-turnos-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
        // Reindexar los elementos restantes
        Array.from(container.children).forEach((item, index) => {
            item.dataset.index = index + 1;
        });
    } else {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Debe haber al menos un turno en la secuencia',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
    }
}

// Función para cargar los detalles de un esquema existente
function cargarDetallesEsquemaExistente(esquemaId) {
    return fetch(`../Controlador/ControladorDetalleEsquema.php?Accion=ListarPorEsquema&IdEsquema=${esquemaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success !== false) {
                detalleEsquemaActual = data.sort((a, b) => a.Orden - b.Orden);
                return detalleEsquemaActual;
            } else {
                detalleEsquemaActual = [];
                return [];
            }
        })
        .catch(error => {
            console.error('Error al cargar detalles del esquema:', error);
            detalleEsquemaActual = [];
            return [];
        });
}

// Función para abrir modal de esquema
function abrirModalEsquema(esquemaId = null) {
    const modal = document.getElementById('esquema-modal');
    const titulo = document.getElementById('esquema-modal-title');
    
    // Cargar tipos de turno primero
    cargarTiposTurnoDisponibles().then(() => {
        // Limpiar secuencia
        const container = document.getElementById('secuencia-turnos-container');
        container.innerHTML = '';
        
        if (esquemaId) {
            titulo.textContent = 'Editar Esquema';
            // Cargar datos del esquema
            const formData = new FormData();
            formData.append('IdEsquema', esquemaId);
            fetch('../Controlador/ControladorEsquemaTurno.php?Accion=ListarPorId', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('esquema-id').value = data.IdEsquema;
                    document.getElementById('esquema-nombre').value = data.Nombre;
                    document.getElementById('esquema-descripcion').value = data.Descripcion;
                    document.getElementById('esquema-activo').value = data.Activo ? '1' : '0';
                    
                    // Marcar días de aplicación
                    const dias = data.DiasAplicacion.split(',');
                    document.querySelectorAll('.dia-aplicacion').forEach(checkbox => {
                        checkbox.checked = dias.includes(checkbox.value);
                    });
                    
                    // Cargar detalles del esquema
                    cargarDetallesEsquemaExistente(esquemaId).then(detalles => {
                        if (detalles.length > 0) {
                            detalles.forEach((detalle, index) => {
                                agregarTurno();
                                const select = container.children[index].querySelector('.turno-select');
                                select.value = detalle.IdTipoTurno;
                            });
                        } else {
                            // Agregar primer turno por defecto si no hay detalles
                            agregarTurno();
                        }
                    });
                });
        } else {
            titulo.textContent = 'Nuevo Esquema';
            // Limpiar formulario
            document.getElementById('esquema-id').value = '';
            document.getElementById('esquema-nombre').value = '';
            document.getElementById('esquema-descripcion').value = '';
            document.getElementById('esquema-activo').value = '1';
            
            // Marcar días laborables por defecto
            document.querySelectorAll('.dia-aplicacion').forEach(checkbox => {
                checkbox.checked = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'].includes(checkbox.value);
            });
            
            // Agregar primer turno por defecto
            agregarTurno();
        }
        
        showModal('esquema-modal');
    });
}

// Función para guardar esquema (modificada para usar ControladorDetalleEsquema)
function guardarEsquema() {
    const esquemaId = document.getElementById('esquema-id').value;
    const accion = esquemaId ? 'Actualizar' : 'Ingresar';
    
    // Validación básica
    if (!document.getElementById('esquema-nombre').value) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor ingrese el nombre del esquema',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
        return;
    }
    
    const turnos = Array.from(document.querySelectorAll('.turno-select'))
        .map((select, index) => ({
            IdTipoTurno: select.value,
            Orden: index + 1
        }));
    
    // Validar que todos los turnos tengan un valor seleccionado
    if (turnos.some(t => !t.IdTipoTurno)) {
        showThemedAlert({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor seleccione un turno para cada elemento de la secuencia',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
        return;
    }
    
    // Obtener días de aplicación
    const diasAplicacion = Array.from(document.querySelectorAll('.dia-aplicacion:checked'))
        .map(checkbox => checkbox.value).join(',');
    
    // Primero guardar/actualizar el esquema principal
    const formDataEsquema = new FormData();
    formDataEsquema.append('Accion', accion);
    formDataEsquema.append('IdEsquema', esquemaId);
    formDataEsquema.append('Nombre', document.getElementById('esquema-nombre').value);
    formDataEsquema.append('Descripcion', document.getElementById('esquema-descripcion').value);
    formDataEsquema.append('DiasAplicacion', diasAplicacion);
    formDataEsquema.append('Activo', document.getElementById('esquema-activo').value);

    fetch('../Controlador/ControladorEsquemaTurno.php?Accion=' + accion, {
        method: 'POST',
        body: formDataEsquema
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const nuevoEsquemaId = data.id || esquemaId;
            // Ahora guardar los detalles del esquema
            guardarDetallesEsquema(nuevoEsquemaId, turnos).then(() => {
                hideModal('esquema-modal');
                esquemasTable.ajax.reload();
                showThemedAlert({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Esquema ' + (accion === 'Ingresar' ? 'creado' : 'actualizado') + ' correctamente',
                    confirmButtonText: 'Aceptar',
                    customClass: { popup: 'swal2-dark' }
                });
            });
        } else {
            throw new Error(data.message || 'Error desconocido al guardar el esquema');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showThemedAlert({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar el esquema',
            confirmButtonText: 'Aceptar',
            customClass: { popup: 'swal2-dark' }
        });
    });
}

// Función para guardar los detalles del esquema
function guardarDetallesEsquema(esquemaId, turnos) {
    // Primero eliminar todos los detalles existentes
    return new Promise((resolve, reject) => {
        // Si es una actualización, eliminar los detalles existentes
        if (detalleEsquemaActual.length > 0) {
            const deletePromises = detalleEsquemaActual.map(detalle => {
                const formData = new FormData();
                formData.append('Accion', 'Eliminar');
                formData.append('IdDetalle', detalle.IdDetalle);
                return fetch('../Controlador/ControladorDetalleEsquema.php?Accion=Eliminar', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            });
            
            Promise.all(deletePromises).then(() => {
                // Después de eliminar, insertar los nuevos detalles
                insertarNuevosDetalles(esquemaId, turnos, resolve, reject);
            }).catch(reject);
        } else {
            // Si es nuevo, simplemente insertar
            insertarNuevosDetalles(esquemaId, turnos, resolve, reject);
        }
    });
}

// Función para insertar nuevos detalles
function insertarNuevosDetalles(esquemaId, turnos, resolve, reject) {
    const insertPromises = turnos.map((turno, index) => {
        const formData = new FormData();
        formData.append('Accion', 'Ingresar');
        formData.append('IdDetalle', 0);
        formData.append('IdEsquema', esquemaId);
        formData.append('IdTipoTurno', turno.IdTipoTurno);
        formData.append('Orden', turno.Orden);
        
        return fetch('../Controlador/ControladorDetalleEsquema.php?Accion=Ingresar', {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    });
    
    Promise.all(insertPromises).then(resolve).catch(reject);
}

// Función para eliminar esquema (incluye eliminación de detalles)
function eliminarEsquema(id) {
    showThemedAlert({
        title: '¿Estás seguro?',
        text: "¡Esta acción eliminará el esquema y todos sus turnos asociados!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('Accion', 'Eliminar');
            formData.append('IdEsquema', id);

            fetch('../Controlador/ControladorEsquemaTurno.php?Accion=Eliminar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    esquemasTable.ajax.reload();
                    showThemedAlert({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Esquema eliminado correctamente',
                        confirmButtonText: 'Aceptar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el esquema');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showThemedAlert({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al eliminar el esquema',
                    confirmButtonText: 'Aceptar',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
            });
        }
    });
}

// Actualizar la tabla después de cargar
$(document).ready(function() {
    // Cuando se cargue la tabla de esquemas, cargar los detalles
    $('#esquemas-table').on('draw.dt', function() {
        setTimeout(cargarDetallesEsquemas, 100);
    });
});


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////Excepciones////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

var ExcepcionesTable;
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos según tipo de excepción
            document.getElementById('tipoExcepcion').addEventListener('change', function() {
                const tipo = this.value;
                // Ocultar todos los campos primero
                document.getElementById('campoDiaSemana').style.display = 'none';
                document.getElementById('campoFechaEspecifica').style.display = 'none';
                document.getElementById('campoRangoFechas').style.display = 'none';
                document.getElementById('campoFinSemana').style.display = 'none';
                document.getElementById('campoTurnoNocturno').style.display = 'none';
                
                // Mostrar los campos correspondientes
                if (tipo === 'DIA_SEMANA') {
                    document.getElementById('campoDiaSemana').style.display = 'block';
                } else if (tipo === 'DIA_ESPECIFICO') {
                    document.getElementById('campoFechaEspecifica').style.display = 'block';
                } else if (tipo === 'RANGO_FECHAS') {
                    document.getElementById('campoRangoFechas').style.display = 'block';
                } else if (tipo === 'FIN_SEMANA') {
                    document.getElementById('campoFinSemana').style.display = 'block';
                } else if (tipo === 'TURNO_NOCTURNO') {
                    document.getElementById('campoTurnoNocturno').style.display = 'block';
                }
            });

        });


function cargarExcepciones() {
    if ($.fn.DataTable.isDataTable('#excepciones-table')) {
        ExcepcionesTable.destroy();
    }

    ExcepcionesTable = $('#excepciones-table').DataTable({
        "ajax": {
            "url": '../Controlador/ControladorExcepcionesPersonal.php?Accion=ListarTodas',
            "dataSrc": ""
        },
        "columns": [
            { "data": "NombreMedico" },
            { "data": "DescripcionPerfil" },
            { "data": "TipoExcepcion"},
            { "data": "Descripcion"},
            { "data": "Motivo"},
            { 
            "data": "Estado",
                "render": function(data, type, row) {
                    let estadoClass = 'active';
                    let estadoText = 'Activo';
                    if (data == 2) {
                        estadoClass = 'inactive';
                        estadoText = 'Inactivo';
                    } else if (data == 3) {
                        estadoClass = 'pending';
                        estadoText = 'En implementación';
                    }
                    return `<span class="status ${estadoClass}">${estadoText}</span>`;
                }
            },
            {
                "data": "IdExcepcion",
                "render": function(data, type, row) {
                    return `
                        <button class="btn-outline" onclick="abrirModalExcepciones(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-outline" onclick="eliminarExcepciones(${data})" style="padding: 5px 10px; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
                "className": "text-center"
            }
        ],
        "paging": true,          // Activa la paginación
        "ordering": false,       // Desactiva la ordenación en todas las columnas
        "language": {
            "url": "../Vista/js/es-ES.json" // Español si lo deseas
        }
    });
}

function cargarTodaEnfermeria() {
    return fetch('../Controlador/ControladorMedico.php?Accion=ListarEnfermeros')
        .then(response => response.json())
        .then(data => {
            todaEnfermeria = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar enfermeria:', error);
            return [];
        });
}

function cargarLosPerfiles() {
    return fetch('../Controlador/ControladorPerfil.php?Accion=Listar')
        .then(response => response.json())
        .then(data => {
            TodosPerfiles = data;
            return data;
        })
        .catch(error => {
            console.error('Error al cargar perfiles:', error);
            return [];
        });
}

function abrirModalExcepciones(excepcionesId = null) {
    const modal = document.getElementById('excepcion-modal');
    const titulo = document.getElementById('excepcion-modal-title');
    
    // Cargar servicios primero
    Promise.all([
        cargarTodaEnfermeria(),
        cargarLosPerfiles(),
        cargarServicios()
    ]).then(() => {
        // Llenar select de enfermeros
        const selectEnfermero = document.getElementById('idPersonal');
        selectEnfermero.innerHTML = '<option value="">Seleccione un enfermero</option>';
        todaEnfermeria.forEach(enfermeros => {
            const option = document.createElement('option');
            option.value = enfermeros.IdMedico;
            option.textContent = enfermeros.NombreMedico;
            selectEnfermero.appendChild(option);
        });

        const inputPerfil = document.getElementById('tipoPersonal');
        inputPerfil.innerHTML = '<option value="">Seleccione un perfil</option>';
        TodosPerfiles.forEach(perfiles => {
            const option = document.createElement('option');
            option.value = perfiles.IdPerfil;
            option.textContent = perfiles.DescripcionPerfil;
            inputPerfil.appendChild(option);
        });

        selectEnfermero.addEventListener("change", function() {
            const idSeleccionado = parseInt(this.value);
            const enfermero = todaEnfermeria.find(e => e.IdMedico === idSeleccionado);

            if (enfermero) {
                inputPerfil.value = enfermero.Perfil.DescripcionPerfil;
            } else {
                inputPerfil.value = "";
            }
        });

        if (excepcionesId) {
            titulo.textContent = 'Editar excepcion';
            // Cargar datos del perfil
            fetch(`../Controlador/ControladorExcepcionesPersonal.php?Accion=ListarPorId&IdExcepcion=${excepcionesId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('excepcion-id').value = data.IdExcepcion;
                    document.getElementById('idPersonal').value = data.IdPersonal;
                    document.getElementById('tipoPersonal').value = data.TipoPersonal;
                    document.getElementById('tipoExcepcion').value = data.TipoExcepcion;
                    document.getElementById('motivo').value = data.Motivo;
                    document.getElementById('diaSemana').value = data.DiaSemana;
                    document.getElementById('fechaEspecifica').value = data.FechaEspecifica;
                    document.getElementById('fechaInicio').value = data.FechaInicio;
                    document.getElementById('fechaFin').value = data.FechaFin;
                    document.getElementById('aplicaFinSemana').value = data.AplicaFinSemana;
                    document.getElementById('maxFinesSemanaMes').value = data.MaxFinesSemanaMes;
                    document.getElementById('noTurnosNocturnos').value = (data.NoTurnosNocturnos== 1);
                    document.getElementById('descripcion').value = data.Descripcion;
                    document.getElementById('estado').checked = (data.Estado == 1);
                    // Disparar manualmente el evento change después de cargar los datos
                    const tipoExcepcionSelect = document.getElementById('tipoExcepcion');
                    tipoExcepcionSelect.dispatchEvent(new Event('change'));

                });
        } else {
            titulo.textContent = 'Nueva excepcion';
            // Limpiar formulario
            document.getElementById('excepcion-id').value = '';
            document.getElementById('idPersonal').value = '';
            document.getElementById('tipoPersonal').value = '';
            document.getElementById('tipoExcepcion').value = '';
            document.getElementById('motivo').value = '';
            document.getElementById('diaSemana').value = '';
            document.getElementById('fechaEspecifica').value = '';
            document.getElementById('fechaInicio').value = '';
            document.getElementById('fechaFin').value = '';
            document.getElementById('aplicaFinSemana').value = '';
            document.getElementById('maxFinesSemanaMes').value = '';
            document.getElementById('noTurnosNocturnos').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('estado').value = '0';
            
            // 🔥 PARA NUEVA EXCEPCIÓN TAMBIÉN DISPARAR EL EVENTO
            const tipoExcepcionSelect = document.getElementById('tipoExcepcion');
            tipoExcepcionSelect.dispatchEvent(new Event('change'));
        }

        showModal('excepcion-modal');
    });
}

 function guardarExcepcion (){
    // Guardar excepción
    const form = document.getElementById('formNuevaExcepcion');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Aquí iría el código para enviar los datos al servidor
    const formData = {
        tipoPersonal: document.getElementById('tipoPersonal').value,
        idPersonal: document.getElementById('idPersonal').value,
        tipoExcepcion: document.getElementById('tipoExcepcion').value,
        motivo: document.getElementById('motivo').value,
        descripcion: document.getElementById('descripcion').value,
        estado: document.getElementById('estado').checked ? 1 : 0,
        // Campos específicos según el tipo
        diaSemana: document.getElementById('diaSemana').value,
        fechaEspecifica: document.getElementById('fechaEspecifica').value,
        fechaInicio: document.getElementById('fechaInicio').value,
        fechaFin: document.getElementById('fechaFin').value,
        aplicaFinSemana: document.getElementById('aplicaFinSemana').checked ? 1 : 0,
        maxFinesSemanaMes: document.getElementById('maxFinesSemanaMes').value,
        noTurnosNocturnos: document.getElementById('noTurnosNocturnos').checked ? 1 : 0
    };
    
    console.log('Datos a enviar:', formData);

    // Simulamos envío exitoso
    alert('Excepción guardada correctamente');
    document.getElementById('nuevaExcepcionModal').querySelector('.btn-close').click();

 }

// ==================== Nomina de Recargos (antes Vista/NominaRecargos.php, pagina standalone) ====================

let fuenteActual = 'enfermeria';

function fmt(n) { return Number(n || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

// Se llama cada vez que se abre la seccion (no solo en la carga inicial de la pagina, como
// hacia el init() de la vieja pagina standalone), asi que reinicia fechas/resultados a su
// estado por defecto en cada visita, igual que hacia un F5 sobre esa pagina.
function inicializarNominaRecargos() {
    const hoy = new Date();
    const primero = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    document.getElementById('fecha-inicio').value = primero.toISOString().slice(0, 10);
    document.getElementById('fecha-fin').value = hoy.toISOString().slice(0, 10);
    document.getElementById('resumen').innerHTML = '<p class="vacio">Selecciona un período y presiona Calcular.</p>';
    document.getElementById('detalle').innerHTML = '';
    cambiarFuente();
    cargarEmpresasSelectNomina();
}

async function cambiarFuente() {
    fuenteActual = document.getElementById('sel-fuente').value;
    document.getElementById('lbl-areaservicio').textContent = fuenteActual === 'enfermeria' ? 'Servicio' : 'Área';
    const sel = document.getElementById('sel-areaservicio');
    sel.innerHTML = '<option value="">Todos</option>';
    const url = fuenteActual === 'enfermeria'
        ? '../Controlador/ControladorServicio.php?Accion=Listar'
        : '../Controlador/ControladorArea.php?Accion=listarfullArea';
    try {
        const data = await fetch(url).then(r => r.json());
        (data || []).forEach(x => {
            const opt = document.createElement('option');
            if (fuenteActual === 'enfermeria') { opt.value = x.IdServicio; opt.textContent = x.NombreServicio; }
            else { opt.value = x.IdArea; opt.textContent = x.NombreArea; }
            sel.appendChild(opt);
        });
    } catch (e) { /* si el area no tiene endpoint compatible, se deja "Todos" */ }
}

// Renombrada desde cargarEmpresas() (nombre original en NominaRecargos.php): esta consola ya
// tiene una cargarEmpresas() propia que llena la tabla DataTable de Empresas (CRUD); esta otra
// solo llena el <select> de filtro. Ademas, a diferencia del init() de una sola vez de la pagina
// standalone, aqui se puede volver a llamar cada vez que se reabre la seccion, asi que limpia el
// <select> antes de repoblarlo (si no, acumularia opciones duplicadas en cada visita).
async function cargarEmpresasSelectNomina() {
    const sel = document.getElementById('sel-empresa');
    sel.innerHTML = '<option value="">Todas</option>';
    const data = await fetch('../Controlador/ControladorEmpresa.php?Accion=Listar').then(r => r.json());
    data.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.IdEmpresa; opt.textContent = e.NombreEmpresa;
        sel.appendChild(opt);
    });
}

async function calcular() {
    const inicio = document.getElementById('fecha-inicio').value;
    const fin = document.getElementById('fecha-fin').value;
    if (!inicio || !fin) { Swal.fire('Faltan fechas', 'Selecciona el período a calcular.', 'warning'); return; }

    const idAreaServicio = document.getElementById('sel-areaservicio').value;
    const idEmpresa = document.getElementById('sel-empresa').value;

    let url = `../Controlador/ControladorNominaRecargos.php?Accion=Calcular&Fuente=${fuenteActual}&FechaInicio=${inicio}&FechaFin=${fin}`;
    if (idAreaServicio) url += `&IdAreaServicio=${idAreaServicio}`;
    if (idEmpresa) url += `&IdEmpresa=${idEmpresa}`;

    document.getElementById('resumen').innerHTML = '<p class="vacio">Calculando...</p>';
    document.getElementById('detalle').innerHTML = '';

    const data = await fetch(url).then(r => r.json());
    // Estado de aprobación de las liquidaciones del mes (solo enfermería): se carga antes de pintar el detalle.
    await cargarFlujoNomina(fuenteActual === 'enfermeria' ? inicio : null);
    renderResumen(data.porArea || []);
    if (data.aviso) document.getElementById('resumen').insertAdjacentHTML('afterbegin', '<p class="aviso">' + paramEsc(data.aviso) + '</p>');
    renderDetalle(data.detalle || [], inicio);
}

function renderResumen(porArea) {
    if (porArea.length === 0) { document.getElementById('resumen').innerHTML = '<p class="vacio">Sin turnos en el período/filtro seleccionado.</p>'; return; }
    let html = '<div class="table-responsive"><table><thead><tr><th>Área/Servicio</th><th>Personas</th><th>Horas</th><th>Devengado</th><th>Neto</th></tr></thead><tbody>';
    porArea.forEach(a => {
        html += `<tr><td>${a.area}</td><td>${a.personas}</td><td>${fmt(a.totalHoras)}</td><td>$${fmt(a.totalDevengado)}</td><td>${a.conNeto === false ? '—' : '$' + fmt(a.totalNeto)}</td></tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('resumen').innerHTML = html;
}

function renderDetalle(detalle, fechaInicio) {
    if (detalle.length === 0) { document.getElementById('detalle').innerHTML = '<p class="vacio">Sin resultados.</p>'; return; }
    const esEnf = fuenteActual === 'enfermeria';
    let html = '<div class="table-responsive"><table><thead><tr><th>Nombre</th><th>Área/Servicio</th><th>Ord.</th><th>R.Noct</th><th>R.Fest.D</th><th>R.Fest.N</th>' +
        (esEnf ? '<th title="Festivo diurno habitual">Hab.D</th><th title="Festivo nocturno habitual">Hab.N</th>' : '') +
        '<th>Ext.D</th><th>Ext.N</th><th>Ext.Fest.D</th><th>Ext.Fest.N</th><th>Devengado</th><th>Deducciones</th><th>Neto</th><th></th></tr></thead><tbody>';
    detalle.forEach((f, i) => {
        const motivo = (f.Pendiente && f.Pendiente.length) ? f.Pendiente.join(' ') : 'Sin sueldo base o sin empresa/parametrización asignada';
        const alerta = f.SinParametrizar ? ` <span class="aviso" title="${paramEsc(motivo)}">⚠</span>` : '';
        const hab = esEnf
            ? `<td>${fmt(f.horasFestivoDiurnoHabitual || 0)}</td><td>${fmt(f.horasFestivoNocturnoHabitual || 0)}</td>` : '';
        const colilla = esEnf && f.liquidacionDisponible !== false && f.sueldoDevengado !== undefined
            ? `<button class="btn btn-outline btn-sm" onclick="verColilla(${i})">Colilla</button> ` : '';
        html += `<tr>
            <td>${f.NombreMedico}${alerta}</td><td>${f.AreaServicio}</td>
            <td>${fmt(f.horasOrdinarias)}</td><td>${fmt(f.horasRecargoNocturno)}</td>
            <td>${fmt(f.horasRecargoFestivoDiurno)}</td><td>${fmt(f.horasRecargoFestivoNocturno)}</td>${hab}
            <td>${fmt(f.horasExtraDiurna)}</td><td>${fmt(f.horasExtraNocturna)}</td>
            <td>${fmt(f.horasExtraFestivaDiurna)}</td><td>${fmt(f.horasExtraFestivaNocturna)}</td>
            <td>$${fmt(f.totalDevengado)}</td><td>${f.alcance === 'recargos' ? '—' : '$' + fmt(f.totalDeducciones)}</td><td>${f.alcance === 'recargos' ? '—' : '$' + fmt(f.totalNeto)}</td>
            <td style="white-space:nowrap">${colilla}${botonGuardarNomina(f, i, esEnf)}</td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('detalle').innerHTML = html;
    window._detalleActual = detalle;
    window._fechaInicioActual = fechaInicio;
}

// Colilla completa de una persona (enfermería): las categorías de hora con su factor y la cadena hasta el neto.
function verColilla(i) {
    const f = window._detalleActual[i];
    const pesosC = v => '$' + fmt(v);
    const CONCEPTO = { LM: 'Licencia de maternidad', INC: 'Incapacidad', VAC: 'Vacaciones', OTRA: 'Otra novedad' };
    const ORIGEN = { persona: 'ajuste individual de la ficha', categoria: 'sueldo de la categoría' };
    const fila = (txt, val, clase) => `<tr${clase === 'tot' ? ' style="font-weight:700"' : ''}><td>${txt}</td><td style="text-align:right">${clase === 'neg' ? '− ' : ''}${pesosC(val)}</td></tr>`;

    let tipos = '<table style="width:100%; font-size:0.85rem"><thead><tr><th style="text-align:left">Concepto</th><th>Factor</th><th>Horas</th><th style="text-align:right">Valor</th></tr></thead><tbody>';
    (f.tipos || []).forEach(t => {
        tipos += `<tr${t.horas > 0 ? '' : ' style="opacity:.55"'}><td>${paramEsc(t.etiqueta)}</td><td style="text-align:center">${t.factor === null ? '—' : '× ' + Number(t.factor).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 4})}</td><td style="text-align:center">${fmt(t.horas)}</td><td style="text-align:right">${t.valor === null ? '—' : pesosC(t.valor)}</td></tr>`;
    });
    tipos += '</tbody></table>';

    let cadena = '<table style="width:100%; font-size:0.9rem"><tbody>';
    if (f.sueldoDevengado === undefined) {
        cadena += `<tr><td colspan="2">${paramEsc((f.Pendiente || []).join(' ') || 'Sin liquidar.')}</td></tr>`;
    } else if (f.alcance === 'recargos') {
        // Alcance «solo sueldo base y recargos/extras»: nada de auxilios, aportes, retención ni descuentos
        cadena += fila('Sueldo base', f.sueldoDevengado);
        cadena += fila('Recargos y horas extra', f.totalRecargosExtras);
        cadena += fila('Total (sueldo base + recargos y extras)', f.totalDevengado, 'tot');
        if (f.novedadesIgnoradas > 0) {
            cadena += `<tr><td colspan="2" style="opacity:.7">${f.novedadesIgnoradas} novedad(es) del mes no se tienen en cuenta porque la empresa liquida solo sueldo base y recargos/extras.</td></tr>`;
        }
    } else {
        cadena += fila(`Sueldo (${fmt(f.diasLaborados)} días laborados)`, f.sueldoDevengado);
        (f.lineasNovedad || []).forEach(x => { cadena += fila(`${paramEsc(CONCEPTO[x.Concepto] || x.Concepto)} (${fmt(x.Dias)} días)`, x.Valor); });
        cadena += fila('Recargos y horas extra', f.totalRecargosExtras);
        cadena += fila('Total devengado', f.totalDevengado, 'tot');
        if (f.auxilioTransporte > 0) cadena += fila('Auxilio de transporte', f.auxilioTransporte);
        if (f.subsidioAlimentacion > 0) cadena += fila('Subsidio de alimentación', f.subsidioAlimentacion);
        if (f.bonificacionNoSalarial > 0) cadena += fila('Bonificación no salarial', f.bonificacionNoSalarial);
        cadena += fila('Salud', f.descuentoSalud, 'neg') + fila('Pensión', f.descuentoPension, 'neg');
        if (f.descuentoFondoSolidaridad > 0) cadena += fila('Fondo de solidaridad pensional', f.descuentoFondoSolidaridad, 'neg');
        if (f.retencionFuente > 0) cadena += fila('Retención en la fuente', f.retencionFuente, 'neg');
        (f.lineasDescuento || []).forEach(x => { cadena += fila(paramEsc(x.Concepto), x.Valor, 'neg'); });
        cadena += fila('Neto a pagar', f.totalNeto, 'tot');
    }
    cadena += '</tbody></table>';

    const fest = f.festivos && f.festivos.aplica
        ? `<p>Fechas festivas trabajadas: <strong>${f.festivos.fechasTrabajadas}</strong> (límite con tarifa normal: ${f.festivos.limite})${f.festivos.tarifaHabitualAplicada ? ' — <strong>tarifa habitual aplicada</strong>' : ''}.</p>` : '';
    Swal.fire({
        title: paramEsc(f.NombreMedico),
        width: 760,
        html: `<div style="text-align:left">
            <p>${paramEsc(f.Empresa || 'Sin empresa')}${f.Contrato ? ' · ' + paramEsc(f.Contrato) : ''} · Sueldo base ${f.SalarioBase ? pesosC(f.SalarioBase) : '—'}${ORIGEN[f.OrigenSueldo] ? ' (' + ORIGEN[f.OrigenSueldo] + ')' : ''} · ${f.turnos} turnos · ${fmt(f.totalHoras)} h</p>
            ${fest}${tipos}<hr>${cadena}</div>`,
        confirmButtonText: 'Cerrar'
    });
}

async function guardarFila(i) {
    const fila = window._detalleActual[i];
    const fd = new FormData();
    fd.append('Fuente', fuenteActual);
    // Enfermería: solo se manda a quién y qué mes; el servidor recalcula la liquidación. (Médicos: la fila calculada.)
    const mesEnf = fuenteActual === 'enfermeria';
    fd.append('FechaInicio', mesEnf ? window._fechaInicioActual.slice(0, 7) + '-01' : window._fechaInicioActual);
    if (mesEnf) fd.append('IdMedico', fila.IdMedico); else fd.append('Fila', JSON.stringify(fila));
    const resp = await fetch('../Controlador/ControladorNominaRecargos.php?Accion=Guardar', { method: 'POST', body: fd }).then(r => r.json());
    if (resp.success) {
        await Swal.fire('Guardado', `Liquidación de ${fila.NombreMedico} guardada (registro de nómina #${resp.IdNomina}).${resp.reemplazada ? ' Reemplaza la anterior.' : ''}`, 'success');
        if (mesEnf) { await cargarFlujoNomina(window._fechaInicioActual); renderDetalle(window._detalleActual, window._fechaInicioActual); }
    } else {
        Swal.fire('Error', resp.message || 'No se pudo guardar', 'error');
    }
}

// ==================== Aprobación de la nómina de enfermería (Nómina guarda → Subgerencia aprueba → Talento Humano revisa) ====================
const ENDPOINT_NOMINA = '../Controlador/ControladorNominaRecargos.php';
const ETIQUETA_FLUJO = { guardada: 'Guardada', aprobada: 'Aprobada', revisada: 'Revisada', devuelta: 'Devuelta' };
const COLOR_FLUJO = { guardada: '#b45309', aprobada: '#0369a1', revisada: '#15803d', devuelta: '#b91c1c' };
window._flujo = { filas: [], porMedico: {} };

function insigniaFlujo(sit) {
    return `<span style="display:inline-block;padding:1px 9px;border-radius:12px;border:1px solid ${COLOR_FLUJO[sit]};color:${COLOR_FLUJO[sit]};font-size:12px;font-weight:700">${ETIQUETA_FLUJO[sit]}</span>`;
}

// En enfermería, el botón de cada persona depende de en qué paso va su liquidación del mes.
function botonGuardarNomina(f, i, esEnf) {
    const puedeGuardar = !esEnf || (window.NOMINA_PERM && window.NOMINA_PERM.guardar);
    if (!esEnf) return `<button class="btn btn-primary btn-sm" onclick="guardarFila(${i})" ${f.SinParametrizar ? 'disabled' : ''}>Guardar</button>`;
    const fl = window._flujo.porMedico[f.IdMedico];
    const sit = fl ? fl.Situacion : null;
    if (sit === 'aprobada' || sit === 'revisada') return insigniaFlujo(sit);
    if (!puedeGuardar) return sit ? insigniaFlujo(sit) : '';
    const etiqueta = sit === 'guardada' ? 'Guardar de nuevo' : 'Guardar';
    return (sit ? insigniaFlujo(sit) + ' ' : '') + `<button class="btn btn-primary btn-sm" onclick="guardarFila(${i})" ${f.SinParametrizar ? 'disabled' : ''}>${etiqueta}</button>`;
}

async function cargarFlujoNomina(inicio) {
    const sec = document.getElementById('flujo-nomina-seccion');
    window._flujo = { filas: [], porMedico: {}, anio: null, mes: null };
    if (!sec) return;
    if (!inicio) { sec.style.display = 'none'; return; }
    const anio = Number(inicio.slice(0, 4)), mes = Number(inicio.slice(5, 7));
    let r;
    try { r = await fetch(`${ENDPOINT_NOMINA}?Accion=EstadoPeriodo&Anio=${anio}&Mes=${mes}`).then(x => x.json()); } catch (e) { r = null; }
    if (!r || !r.success || !r.flujo) { sec.style.display = 'none'; return; }
    window._flujo = { filas: r.filas, porMedico: {}, anio, mes, resumen: r.resumen };
    r.filas.forEach(f => { window._flujo.porMedico[f.IdMedico] = f; });
    renderFlujoNomina();
}

function renderFlujoNomina() {
    const P = window.NOMINA_PERM || {};
    const sec = document.getElementById('flujo-nomina-seccion');
    const { filas, anio, mes, resumen } = window._flujo;
    sec.style.display = (filas.length || P.guardar || P.aprobar || P.revisar) ? '' : 'none';

    // Aprobar o revisar todo el mes de una vez (según permiso y si hay algo pendiente).
    let lote = '';
    if (P.aprobar && resumen.guardada) lote += `<button onclick="loteNomina('aprobar')"><i class="fas fa-check-double"></i> Aprobar las ${resumen.guardada} guardadas</button> `;
    if (P.revisar && resumen.aprobada) lote += `<button onclick="loteNomina('revisar')"><i class="fas fa-clipboard-check"></i> Marcar las ${resumen.aprobada} aprobadas como revisadas</button>`;
    document.getElementById('flujo-nomina-lote').innerHTML = lote;

    if (!filas.length) {
        document.getElementById('flujo-nomina').innerHTML = '<p class="vacio">Ninguna liquidación guardada para este mes. Guarda las de cada persona desde el detalle de abajo.</p>';
        return;
    }
    const cuando = v => v ? String(v).slice(0, 16).replace('T', ' ') : '';
    let html = `<p class="aviso-flujo" style="margin:0 0 8px;font-size:13px">Guardadas: <b>${resumen.guardada}</b> · Aprobadas: <b>${resumen.aprobada}</b> · Revisadas: <b>${resumen.revisada}</b> · Devueltas: <b>${resumen.devuelta}</b>.
        Nómina guarda, Subgerencia aprueba o devuelve con motivo y Talento Humano revisa. Con la liquidación guardada, los turnos de ese mes de la persona no se pueden cambiar.</p>`;
    html += '<div class="table-responsive"><table><thead><tr><th>Persona</th><th>Estado</th><th>Devengado</th><th>Neto</th><th>Trazabilidad</th><th></th></tr></thead><tbody>';
    filas.forEach(f => {
        const sit = f.Situacion;
        let traza = '';
        if (sit === 'devuelta') traza = `Devuelta por ${paramEsc(f.DevueltaPor || '')} (${cuando(f.DevueltaFecha)}): <i>${paramEsc(f.MotivoDevolucion || '')}</i>`;
        else {
            traza = `Guardó ${paramEsc(f.GuardadaPor || '—')} ${cuando(f.GuardadaFecha)}`;
            if (f.AprobadaPor) traza += `<br>Aprobó ${paramEsc(f.AprobadaPor)} ${cuando(f.AprobadaFecha)}${f.AprobadaObservacion ? ' · ' + paramEsc(f.AprobadaObservacion) : ''}`;
            if (f.RevisadaPor) traza += `<br>Revisó ${paramEsc(f.RevisadaPor)} ${cuando(f.RevisadaFecha)}`;
        }
        let acc = '';
        if (sit === 'guardada' && P.aprobar) acc += `<button class="btn btn-primary btn-sm" onclick="accionNomina('aprobar', ${f.IdNomina})">Aprobar</button> `;
        if (sit === 'aprobada' && P.revisar) acc += `<button class="btn btn-primary btn-sm" onclick="accionNomina('revisar', ${f.IdNomina})">Revisar</button> `;
        if ((sit === 'guardada' || sit === 'aprobada') && P.devolver) acc += `<button class="btn btn-outline btn-sm" onclick="accionNomina('devolver', ${f.IdNomina})">Devolver</button> `;
        if (sit === 'guardada' && P.anular) acc += `<button class="btn btn-outline btn-sm" onclick="accionNomina('anular', ${f.IdNomina})">Anular</button>`;
        html += `<tr><td>${paramEsc(f.NombreMedico)}</td><td>${insigniaFlujo(sit)}</td><td>$${fmt(f.TotalDevengado)}</td><td>$${fmt(f.TotalNeto)}</td><td style="font-size:12px">${traza}</td><td style="white-space:nowrap">${acc}</td></tr>`;
    });
    document.getElementById('flujo-nomina').innerHTML = html + '</tbody></table></div>';
}

async function postNomina(accion, datos) {
    const fd = new FormData();
    Object.keys(datos).forEach(k => fd.append(k, datos[k]));
    return fetch(`${ENDPOINT_NOMINA}?Accion=${accion}`, { method: 'POST', body: fd }).then(r => r.json());
}

async function accionNomina(accion, idNomina) {
    const nombre = (window._flujo.filas.find(f => f.IdNomina === idNomina) || {}).NombreMedico || '';
    const pide = { devolver: ['Devolver la liquidación', 'Motivo de la devolución (obligatorio)', 'Devolver'], anular: ['Anular la liquidación', 'Motivo de la anulación (obligatorio)', 'Anular'] }[accion];
    const datos = { IdNomina: idNomina };
    if (pide) {
        const r = await Swal.fire({
            title: pide[0], html: `<p>${paramEsc(nombre)}</p>`, input: 'textarea', inputLabel: pide[1], inputAttributes: { maxlength: 255 },
            showCancelButton: true, confirmButtonText: pide[2], cancelButtonText: 'Cancelar',
            inputValidator: v => (!v || v.trim().length < 5) ? 'Escribe al menos 5 caracteres.' : null
        });
        if (!r.isConfirmed) return;
        datos.Motivo = r.value.trim();
    } else if (accion === 'aprobar') {
        const r = await Swal.fire({ title: 'Aprobar la liquidación', html: `<p>${paramEsc(nombre)}</p>`, input: 'text', inputLabel: 'Observación (opcional)', inputAttributes: { maxlength: 255 },
            showCancelButton: true, confirmButtonText: 'Aprobar', cancelButtonText: 'Cancelar' });
        if (!r.isConfirmed) return;
        datos.Observacion = r.value || '';
    }
    const resp = await postNomina({ aprobar: 'Aprobar', revisar: 'Revisar', devolver: 'Devolver', anular: 'Anular' }[accion], datos);
    if (resp.success) { toastNomina(resp.message); } else { Swal.fire('No se pudo', resp.message || 'Error', 'error'); }
    await refrescarNomina();
}

async function loteNomina(accion) {
    const { anio, mes } = window._flujo;
    const c = await Swal.fire({ title: accion === 'aprobar' ? '¿Aprobar todas las guardadas?' : '¿Marcar todas las aprobadas como revisadas?', icon: 'question',
        showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar' });
    if (!c.isConfirmed) return;
    const resp = await postNomina(accion === 'aprobar' ? 'AprobarPeriodo' : 'RevisarPeriodo', { Anio: anio, Mes: mes });
    if (resp.success) { toastNomina(resp.message + (resp.fallidas ? ` (${resp.fallidas} no se pudieron procesar)` : '')); } else { Swal.fire('No se pudo', resp.message || 'Error', 'error'); }
    await refrescarNomina();
}

function toastNomina(texto) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: texto, showConfirmButton: false, timer: 3000 });
}

async function refrescarNomina() {
    await cargarFlujoNomina(window._fechaInicioActual || null);
    if (window._detalleActual && window._fechaInicioActual) renderDetalle(window._detalleActual, window._fechaInicioActual);
}

// ==================== Parametrizacion Nomina (antes Vista/ParametrizacionNomina.php, pagina standalone) ====================

// La pantalla se arma sola a partir del esquema de Modelo/ParametroEmpresa.php (acción Esquema): secciones, tipos de
// campo, rangos, ayudas y valores por defecto. Cada empresa tiene su propia parametrización con vigencia; los campos
// con interruptor ("Aplicar...", "Pagar...") deshabilitan los que dependen de él.
const PARAM_ENDPOINT = '../Controlador/ControladorParametroEmpresa.php';
let paramEsquema = null;               // { grupos, campos }
const paramAbiertos = new Set();       // secciones abiertas ("idEmpresa-grupo"), para que sobrevivan al recargar tras guardar
let paramPrimeraCarga = true;

function paramEsc(texto) {
    return escHtmlContratos(texto);
}



function paramCampoHtml(idEmpresa, clave, def, valor) {
    const id = `p-${idEmpresa}-${clave}`;
    const dep = (def.depende ? ` data-depende="${def.depende}"` : '') + (def.solo ? ` data-solo="${def.solo}"` : '');
    const etiqueta = paramEsc(def.etiqueta);
    if (def.tipo === 'bool') {
        return `<div class="form-group param-bool"${dep}>
            <label class="param-check"><input type="checkbox" id="${id}" data-campo="${clave}" ${Number(valor) === 1 ? 'checked' : ''}
                onchange="paramActualizarDependencias(${idEmpresa})"><span>${etiqueta}</span></label></div>`;
    }
    let control;
    if (def.tipo === 'enum') {
        const opciones = Object.keys(def.opciones).map(v =>
            `<option value="${paramEsc(v)}" ${String(valor) === v ? 'selected' : ''}>${paramEsc(def.opciones[v])}</option>`).join('');
        control = `<select class="form-control" id="${id}" data-campo="${clave}" onchange="paramActualizarDependencias(${idEmpresa})">${opciones}</select>`;
    } else if (def.tipo === 'time') {
        control = `<input type="time" class="form-control" id="${id}" data-campo="${clave}" value="${paramEsc(String(valor).substring(0, 5))}">`;
    } else {
        const paso = def.tipo === 'int' ? 1 : 'any';
        const min = def.min !== undefined ? ` min="${def.min}"` : '';
        const max = def.max !== undefined ? ` max="${def.max}"` : '';
        control = `<input type="number" class="form-control" id="${id}" data-campo="${clave}" step="${paso}"${min}${max} value="${paramEsc(valor)}">`;
    }
    return `<div class="form-group"${dep}><label for="${id}">${etiqueta}</label>${control}</div>`;
}

// La tabla de «Tramos de ley» solo se usa en la liquidación completa: se oculta si ninguna empresa liquida completa.
function paramActualizarTramos() {
    const contenedor = document.getElementById('tramos-ley');
    if (!contenedor) return;
    const alcances = Array.from(document.querySelectorAll('[data-campo="AlcanceLiquidacion"]'));
    const ninguna = alcances.length > 0 && alcances.every(s => s.value === 'recargos');
    const seccion = contenedor.closest('.data-section');
    if (seccion) seccion.style.display = ninguna ? 'none' : '';
}

// Un campo con "data-depende" solo se puede editar mientras su interruptor está encendido; uno con "data-solo" solo
// aplica al método de clasificación indicado (los que no aplican quedan atenuados: no cambian el cálculo).
function paramActualizarDependencias(idEmpresa) {
    const tarjeta = document.querySelector(`.param-empresa[data-empresa="${idEmpresa}"]`);
    if (!tarjeta) return;
    const metodo = document.getElementById(`p-${idEmpresa}-MetodoClasificacion`);
    const alcance = document.getElementById(`p-${idEmpresa}-AlcanceLiquidacion`);
    // Las secciones que solo aplican a la liquidación completa se ocultan cuando el alcance es «solo recargos y extras».
    tarjeta.querySelectorAll('details[data-alcance]').forEach(seccion => {
        seccion.style.display = (alcance && alcance.value !== seccion.dataset.alcance) ? 'none' : '';
    });
    paramActualizarTramos();
    tarjeta.querySelectorAll('[data-depende], [data-solo]').forEach(grupo => {
        let apagado = false;
        if (grupo.dataset.depende) {
            const interruptor = document.getElementById(`p-${idEmpresa}-${grupo.dataset.depende}`);
            if (interruptor && !interruptor.checked) apagado = true;
        }
        if (grupo.dataset.solo && metodo && metodo.value !== grupo.dataset.solo) apagado = true;

        grupo.classList.toggle('param-apagado', apagado);
        grupo.querySelectorAll('input, select').forEach(el => { el.disabled = apagado; });
    });
}

function paramToggle(el) {
    if (el.open) paramAbiertos.add(el.dataset.clave); else paramAbiertos.delete(el.dataset.clave);
}

// listar() en Modelo/ParametroEmpresa.php devuelve una fila por CADA empresa: IdParametro viene null para las que aun
// no tienen una, ya con los valores por defecto del esquema en los demas campos.
async function cargarParametrosEmpresa() {
    const cont = document.getElementById('tarjetas-empresas');
    try {
        if (!paramEsquema) {
            const e = await fetch(`${PARAM_ENDPOINT}?Accion=Esquema`).then(r => r.json());
            if (!e.success) throw new Error(e.message || 'No se pudo leer el esquema de parámetros');
            paramEsquema = e;
        }
        const data = await fetch(`${PARAM_ENDPOINT}?Accion=Listar`).then(r => r.json());
        if (!Array.isArray(data)) throw new Error((data && data.message) || 'No se pudo leer la parametrización');

        if (paramPrimeraCarga && data.length) {
            paramAbiertos.add(`${data[0].IdEmpresa}-clasificacion`);
            paramPrimeraCarga = false;
        }
        // La primera parametrización rige por defecto desde el 1 de enero: así sirve también para liquidar meses ya pasados del año.
        const hoy = new Date().getFullYear() + '-01-01';
        let html = '';
        data.forEach(p => {
            const sinParametrizar = p.IdParametro === null;
            const insignias = (Number(p.ManejaPlanta) === 1 ? ' <span class="status active">Maneja planta</span>' : '') +
                (sinParametrizar ? ' <span class="aviso">⚠ Sin parametrizar todavía</span>' : '');
            const vigencia = sinParametrizar
                ? `<label>Rige desde <input type="date" id="p-${p.IdEmpresa}-vigdesde" class="form-control" value="${hoy}"></label>
                   <button class="btn btn-primary" onclick="guardarParametro(${p.IdEmpresa}, 'crear', this)">Crear parametrización</button>`
                : `<label>Rige desde <input type="date" id="p-${p.IdEmpresa}-inicio" class="form-control" value="${paramEsc(p.FechaVigenciaInicio)}"></label>
                   <button class="btn btn-primary" onclick="guardarParametro(${p.IdEmpresa}, 'actualizar', this)">Guardar cambios</button>
                   <label>Nueva vigencia desde <input type="date" id="p-${p.IdEmpresa}-vigdesde" class="form-control"></label>
                   <button class="btn btn-outline" onclick="guardarParametro(${p.IdEmpresa}, 'nueva', this)">Guardar como nueva vigencia</button>`;

            html += `<div class="data-section param-empresa" data-empresa="${p.IdEmpresa}" data-parametro="${p.IdParametro ?? ''}" data-fecha-inicio="${p.FechaVigenciaInicio || ''}">
                <div class="section-header"><div class="section-title">${p.NombreEmpresa}${insignias}</div></div>
                <div class="param-vigencia">${vigencia}</div>`;
            Object.keys(paramEsquema.grupos).forEach(grupo => {
                const g = paramEsquema.grupos[grupo];
                const clave = `${p.IdEmpresa}-${grupo}`;
                html += `<details class="param-grupo" data-clave="${clave}"${g.alcance ? ` data-alcance="${g.alcance}"` : ''} ${paramAbiertos.has(clave) ? 'open' : ''} ontoggle="paramToggle(this)">
                    <summary><i class="fas ${g.icono}"></i><span>${paramEsc(g.titulo)}</span></summary>
                    <div class="form-grid">`;
                Object.keys(paramEsquema.campos).forEach(c => {
                    const def = paramEsquema.campos[c];
                    if (def.grupo === grupo) html += paramCampoHtml(p.IdEmpresa, c, def, p[c]);
                });
                html += `</div></details>`;
            });
            html += `</div>`;
        });
        cont.innerHTML = html || '<p class="vacio">No hay empresas registradas todavía.</p>';
        data.forEach(p => paramActualizarDependencias(p.IdEmpresa));
    } catch (e) {
        cont.innerHTML = `<p class="vacio aviso">${paramEsc(e.message || 'No se pudo cargar la parametrización')}</p>`;
    }
    cargarSueldosCategoria();
    cargarTramosLey();
}

// modo: 'crear' (primera parametrización), 'actualizar' (edita la vigente tal como está) o 'nueva' (cierra la
// vigencia actual el día anterior y abre otra). El botón se deshabilita durante la petición para evitar doble envío.
async function guardarParametro(idEmpresa, modo, btn) {
    const tarjeta = document.querySelector(`.param-empresa[data-empresa="${idEmpresa}"]`);
    const fd = new FormData();
    fd.append('IdEmpresa', idEmpresa);
    fd.append('UsuarioCreacion', IdUsuario);
    fd.append('UsuarioModificacion', IdUsuario);
    tarjeta.querySelectorAll('[data-campo]').forEach(el => {
        fd.append(el.dataset.campo, el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value);
    });

    let url;
    if (modo === 'actualizar') {
        fd.append('IdParametro', tarjeta.dataset.parametro);
        // "Rige desde" se puede corregir (p. ej. para liquidar meses anteriores a la fecha en que se creó la parametrización).
        const inicioEditado = document.getElementById(`p-${idEmpresa}-inicio`);
        const inicio = inicioEditado && inicioEditado.value ? inicioEditado.value : tarjeta.dataset.fechaInicio;
        fd.append('FechaVigenciaInicio', inicio);
        url = `${PARAM_ENDPOINT}?Accion=Actualizar`;
    } else {
        const desde = document.getElementById(`p-${idEmpresa}-vigdesde`).value;
        if (!desde) {
            Swal.fire('Falta la fecha', 'Indique desde qué fecha rige esta parametrización.', 'warning');
            return;
        }
        if (modo === 'nueva') {
            if (tarjeta.dataset.fechaInicio && desde <= tarjeta.dataset.fechaInicio) {
                Swal.fire('Fecha no válida', `La nueva vigencia debe empezar después de la actual (${tarjeta.dataset.fechaInicio}).`, 'warning');
                return;
            }
            const ok = await Swal.fire({
                title: 'Nueva vigencia',
                text: `La vigencia actual terminará el día anterior a ${desde} y desde esa fecha regirán los valores de esta pantalla. Los cálculos de meses anteriores conservan los valores que regían entonces.`,
                icon: 'question', showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar'
            });
            if (!ok.isConfirmed) return;
        }
        fd.append('FechaVigenciaInicio', desde);
        url = `${PARAM_ENDPOINT}?Accion=Ingresar`;
    }

    btn.disabled = true;
    try {
        const resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
        if (resp.success) {
            Swal.fire('Guardado', resp.message, 'success');
            cargarParametrosEmpresa(); // recarga para que la tarjeta quede con su IdParametro y vigencia reales
        } else {
            const lista = (resp.errores && resp.errores.length)
                ? '<ul style="text-align:left">' + resp.errores.map(e => `<li>${paramEsc(e)}</li>`).join('') + '</ul>'
                : paramEsc(resp.message || 'No se pudo guardar');
            Swal.fire({ icon: 'error', title: 'Revise los datos', html: lista });
            btn.disabled = false;
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo guardar', 'error');
        btn.disabled = false;
    }
}

// ---------- Tramos de ley (fondo de solidaridad pensional y retención en la fuente) ----------
const TRAMOS_ENDPOINT = '../Controlador/ControladorNominaTramo.php';
const TRAMOS_DEF = {
    FSP: {
        titulo: 'Fondo de solidaridad pensional',
        unidad: 'veces el SMMLV', conSuma: false
    },
    RETENCION: {
        titulo: 'Retención en la fuente',
        unidad: 'UVT', conSuma: true
    }
};

function tramoFilaHtml(tipo, f) {
    const def = TRAMOS_DEF[tipo];
    const num = (campo, valor, extra) => `<td><input type="number" step="any" min="0" class="form-control" data-tramo="${campo}" value="${valor === null || valor === undefined ? '' : valor}" ${extra || ''}></td>`;
    return `<tr>${num('Desde', f.Desde)}${num('Hasta', f.Hasta, 'placeholder="sin tope"')}${num('Tasa', f.Tasa, 'max="100"')}` +
        (def.conSuma ? num('Suma', f.Suma) : '') +
        `<td><button class="btn btn-outline btn-sm" title="Quitar tramo" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td></tr>`;
}

async function cargarTramosLey() {
    const cont = document.getElementById('tramos-ley');
    if (!cont) return;
    try {
        const resp = await fetch(`${TRAMOS_ENDPOINT}?Accion=Listar`).then(r => r.json());
        if (!resp.success) throw new Error(resp.message || 'No se pudieron leer los tramos');
        let html = '';
        Object.keys(TRAMOS_DEF).forEach(tipo => {
            const def = TRAMOS_DEF[tipo];
            html += `<div class="tramos-bloque" data-tipo="${tipo}">
                <h4>${def.titulo}</h4>
                <div class="table-responsive"><table class="tramos-tabla"><thead><tr>
                    <th>Desde (${def.unidad})</th><th>Hasta (${def.unidad})</th><th>Tasa %</th>${def.conSuma ? '<th>Suma (UVT)</th>' : ''}<th></th>
                </tr></thead><tbody>${resp.tramos.filter(t => t.Tipo === tipo).map(t => tramoFilaHtml(tipo, t)).join('')}</tbody></table></div>
                <div class="param-vigencia">
                    <button class="btn btn-outline btn-sm" onclick="tramoAgregar('${tipo}')"><i class="fas fa-plus"></i> Agregar tramo</button>
                    <button class="btn btn-primary btn-sm" onclick="guardarTramos('${tipo}', this)">Guardar tramos</button>
                </div></div>`;
        });
        cont.innerHTML = html;
    } catch (e) {
        cont.innerHTML = `<p class="vacio aviso">${paramEsc(e.message || 'No se pudieron leer los tramos')}</p>`;
    }
}

function tramoAgregar(tipo) {
    const tbody = document.querySelector(`.tramos-bloque[data-tipo="${tipo}"] tbody`);
    const ultima = tbody.querySelector('tr:last-child');
    // El nuevo tramo empieza donde termina el anterior. Si el anterior estaba abierto (sin tope), hay que
    // completar su «Hasta» antes de guardar: el nuevo queda con «Desde» vacío para que no se pase por alto.
    let desde = '';
    if (ultima) {
        const hasta = ultima.querySelector('[data-tramo="Hasta"]');
        desde = hasta.value;
    }
    tbody.insertAdjacentHTML('beforeend', tramoFilaHtml(tipo, { Desde: desde === '' ? null : desde, Hasta: null, Tasa: 0, Suma: 0 }));
}

async function guardarTramos(tipo, btn) {
    const filas = [...document.querySelectorAll(`.tramos-bloque[data-tipo="${tipo}"] tbody tr`)].map(tr => {
        const f = {};
        tr.querySelectorAll('[data-tramo]').forEach(i => { f[i.dataset.tramo] = i.value; });
        return f;
    });
    const fd = new FormData();
    fd.append('Tipo', tipo);
    fd.append('Filas', JSON.stringify(filas));
    btn.disabled = true;
    try {
        const resp = await fetch(`${TRAMOS_ENDPOINT}?Accion=Guardar`, { method: 'POST', body: fd }).then(r => r.json());
        if (resp.success) {
            Swal.fire('Guardado', resp.message, 'success');
            cargarTramosLey();
        } else {
            const lista = (resp.errores && resp.errores.length)
                ? '<ul style="text-align:left">' + resp.errores.map(e => `<li>${paramEsc(e)}</li>`).join('') + '</ul>'
                : paramEsc(resp.message || 'No se pudo guardar');
            Swal.fire({ icon: 'error', title: 'Revise los tramos', html: lista });
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo guardar', 'error');
    }
    btn.disabled = false;
}

// ---------- Sueldos por categoría (empresa + perfil) ----------
const SUELDO_ENDPOINT = '../Controlador/ControladorSueldoCategoria.php';
const formatoPesos = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

async function cargarSueldosCategoria() {
    const cont = document.getElementById('sueldos-categoria');
    if (!cont) return;
    try {
        const resp = await fetch(`${SUELDO_ENDPOINT}?Accion=Listar`).then(r => r.json());
        if (!resp.success) throw new Error(resp.message || 'No se pudieron leer los sueldos');
        if (!resp.categorias.length) {
            cont.innerHTML = '<p class="vacio">No hay categorías: registre empresas y asigne perfiles a los enfermeros.</p>';
            return;
        }
        let html = '<div class="table-responsive"><table class="tramos-tabla"><thead><tr><th>Empresa</th><th>Perfil</th><th>Personas activas</th>' +
            '<th>Con sueldo individual</th><th>Sueldo de la categoría</th><th>Rige desde</th><th></th></tr></thead><tbody>';
        resp.categorias.forEach(c => {
            const etiqueta = decodificarEntidades(c.NombreEmpresa) + ' · ' + c.DescripcionPerfil;
            const eti = paramEsc(etiqueta).replace(/'/g, '&#39;');
            html += `<tr><td>${paramEsc(decodificarEntidades(c.NombreEmpresa))}</td><td>${paramEsc(c.DescripcionPerfil)}</td><td>${c.Personas}</td>
                <td>${c.ConAjusteIndividual}</td>
                <td>${c.Sueldo === null ? '<span class="aviso">Sin definir</span>' : formatoPesos.format(c.Sueldo)}</td>
                <td>${c.FechaVigenciaInicio || ''}</td>
                <td style="white-space:nowrap">
                    <button class="btn btn-outline btn-sm" onclick="sueldoNuevaVigencia(${c.IdEmpresa}, ${c.IdPerfil}, '${eti}')">${c.Sueldo === null ? 'Definir sueldo' : 'Nueva vigencia'}</button>
                    ${c.IdSueldoCategoria ? `<button class="btn btn-outline btn-sm" onclick="sueldoCorregir(${c.IdSueldoCategoria}, ${c.Sueldo})">Corregir valor</button>` : ''}
                    <button class="btn btn-outline btn-sm" onclick="sueldoHistorial(${c.IdEmpresa}, ${c.IdPerfil}, '${eti}')">Historial</button>
                </td></tr>`;
        });
        cont.innerHTML = html + '</tbody></table></div>';
    } catch (e) {
        cont.innerHTML = `<p class="vacio aviso">${paramEsc(e.message || 'No se pudieron leer los sueldos')}</p>`;
    }
}

async function sueldoNuevaVigencia(idEmpresa, idPerfil, etiqueta) {
    const hoy = new Date().toISOString().slice(0, 10);
    const r = await Swal.fire({
        title: 'Sueldo de la categoría',
        html: `<div style="text-align:left"><p style="margin:0 0 10px">${paramEsc(etiqueta)}</p>
            <label for="sw-sueldo">Sueldo mensual ($)</label><input id="sw-sueldo" type="number" step="any" min="0" class="swal2-input" style="margin:6px 0 12px; width:100%">
            <label for="sw-desde">Rige desde</label><input id="sw-desde" type="date" class="swal2-input" value="${hoy}" style="margin:6px 0; width:100%"></div>`,
        showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', focusConfirm: false,
        preConfirm: () => {
            const sueldo = document.getElementById('sw-sueldo').value;
            const desde = document.getElementById('sw-desde').value;
            if (!sueldo || Number(sueldo) <= 0) { Swal.showValidationMessage('Escriba el sueldo mensual.'); return false; }
            if (!desde) { Swal.showValidationMessage('Indique desde qué fecha rige.'); return false; }
            return { sueldo, desde };
        }
    });
    if (!r.isConfirmed) return;
    const fd = new FormData();
    fd.append('IdEmpresa', idEmpresa); fd.append('IdPerfil', idPerfil);
    fd.append('Sueldo', r.value.sueldo); fd.append('FechaVigenciaInicio', r.value.desde);
    const resp = await fetch(`${SUELDO_ENDPOINT}?Accion=NuevaVigencia`, { method: 'POST', body: fd }).then(x => x.json());
    if (resp.success) { Swal.fire('Guardado', resp.message, 'success'); cargarSueldosCategoria(); }
    else Swal.fire('No se guardó', resp.message || 'Error', 'error');
}

async function sueldoCorregir(idSueldoCategoria, actual) {
    const r = await Swal.fire({
        title: 'Corregir el valor', text: 'Cambia el sueldo de la vigencia actual sin abrir una vigencia nueva (error de digitación).',
        input: 'number', inputValue: actual, inputAttributes: { step: 'any', min: 0 },
        showCancelButton: true, confirmButtonText: 'Corregir', cancelButtonText: 'Cancelar',
        inputValidator: v => (!v || Number(v) <= 0) ? 'Escriba un sueldo mayor que 0.' : null
    });
    if (!r.isConfirmed) return;
    const fd = new FormData();
    fd.append('IdSueldoCategoria', idSueldoCategoria); fd.append('Sueldo', r.value);
    const resp = await fetch(`${SUELDO_ENDPOINT}?Accion=CorregirValor`, { method: 'POST', body: fd }).then(x => x.json());
    if (resp.success) { Swal.fire('Listo', resp.message, 'success'); cargarSueldosCategoria(); }
    else Swal.fire('No se guardó', resp.message || 'Error', 'error');
}

async function sueldoHistorial(idEmpresa, idPerfil, etiqueta) {
    const resp = await fetch(`${SUELDO_ENDPOINT}?Accion=Historial&IdEmpresa=${idEmpresa}&IdPerfil=${idPerfil}`).then(x => x.json());
    const filas = (resp.historial || []).map(h => `<tr><td>${paramEsc(h.FechaVigenciaInicio)}</td><td>${h.FechaVigenciaFin ? paramEsc(h.FechaVigenciaFin) : 'vigente'}</td><td style="text-align:right">${formatoPesos.format(h.Sueldo)}</td></tr>`).join('');
    Swal.fire({
        title: 'Historial de sueldos',
        html: `<p>${paramEsc(etiqueta)}</p>` + (filas
            ? `<table style="width:100%; text-align:left"><thead><tr><th>Desde</th><th>Hasta</th><th style="text-align:right">Sueldo</th></tr></thead><tbody>${filas}</tbody></table>`
            : '<p>Todavía no hay sueldos registrados.</p>')
    });
}

// ---------- Novedades de nómina ----------
const NOVEDAD_ENDPOINT = '../Controlador/ControladorNominaNovedad.php';
let novedadCatalogos = null;
let novedadesPersonas = null;
let novedadesCache = [];

function novedadPeriodo() {
    const v = document.getElementById('nov-periodo').value || new Date().toISOString().slice(0, 7);
    return { anio: Number(v.slice(0, 4)), mes: Number(v.slice(5, 7)) };
}

async function inicializarNovedades() {
    const per = document.getElementById('nov-periodo');
    if (!per.value) per.value = new Date().toISOString().slice(0, 7);
    if (!novedadCatalogos) {
        novedadCatalogos = await fetch(`${NOVEDAD_ENDPOINT}?Accion=Catalogos`).then(r => r.json());
        const llenar = (id, mapa, vacio) => {
            document.getElementById(id).innerHTML = (vacio ? `<option value="">${vacio}</option>` : '') +
                Object.keys(mapa).map(k => `<option value="${k}">${paramEsc(mapa[k])}</option>`).join('');
        };
        llenar('nov-tipo', novedadCatalogos.tipos);
        llenar('nov-concepto-dias', novedadCatalogos.conceptosDias, 'Seleccione');
        llenar('nov-categoria', novedadCatalogos.categoriasHoras, 'Seleccione');
    }
    cargarNovedades();
}

async function novedadCargarPersonas() {
    if (novedadesPersonas) return;
    const data = await fetch('../Controlador/ControladorMedico.php?Accion=ListarEnfermeros').then(r => r.json());
    novedadesPersonas = (Array.isArray(data) ? data : []).filter(p => String(p.EstadoMedico) === '1');
    document.getElementById('nov-persona').innerHTML = '<option value="">Seleccione la persona</option>' +
        novedadesPersonas.map(p => `<option value="${p.IdMedico}">${paramEsc(p.NombreMedico)} — ${paramEsc(p.DocumentoMedico)}</option>`).join('');
}

function novedadDetalle(n) {
    const cats = (novedadCatalogos && novedadCatalogos.categoriasHoras) || {};
    const conc = (novedadCatalogos && novedadCatalogos.conceptosDias) || {};
    switch (n.Tipo) {
        case 'DIAS_NOVEDAD':
            return [conc[n.Concepto] || n.Concepto, `${Number(n.Dias)} día(s)` + (n.Porcentaje !== null ? ` al ${Number(n.Porcentaje)} %` : '')];
        case 'HORAS_EXTRA':
            return [cats[n.Categoria] || n.Categoria, `${Number(n.Horas)} h`];
        case 'DESCUENTO':
            return [n.Concepto, n.Valor !== null ? formatoPesos.format(n.Valor) : `${Number(n.Porcentaje)} % del devengado`];
        default:
            return [n.Concepto || 'Bonificación no salarial', formatoPesos.format(n.Valor)];
    }
}

async function cargarNovedades() {
    const { anio, mes } = novedadPeriodo();
    const tbody = document.querySelector('#tabla-novedades tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="vacio">Cargando...</td></tr>';
    const resp = await fetch(`${NOVEDAD_ENDPOINT}?Accion=Listar&Anio=${anio}&Mes=${mes}`).then(r => r.json());
    novedadesCache = resp.novedades || [];
    if (!novedadesCache.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="vacio">No hay novedades en este mes.</td></tr>';
        return;
    }
    const tipos = novedadCatalogos.tipos;
    tbody.innerHTML = novedadesCache.map(n => {
        const [detalle, cantidad] = novedadDetalle(n);
        return `<tr><td>${paramEsc(n.NombreMedico)}<br><small>${paramEsc(n.DocumentoMedico)}</small></td><td>${paramEsc(tipos[n.Tipo] || n.Tipo)}</td>
            <td>${paramEsc(detalle)}</td><td>${paramEsc(cantidad)}</td><td>${paramEsc(n.Observaciones || '')}</td>
            <td style="white-space:nowrap"><button class="btn btn-outline btn-sm" onclick="abrirModalNovedad(${n.IdNovedad})"><i class="fas fa-edit"></i></button>
            <button class="btn btn-outline btn-sm" onclick="eliminarNovedad(${n.IdNovedad})"><i class="fas fa-trash"></i></button></td></tr>`;
    }).join('');
}

function novedadMostrarBloque() {
    const tipo = document.getElementById('nov-tipo').value;
    document.querySelectorAll('.novedad-bloque').forEach(b => { b.style.display = b.dataset.tipo === tipo ? '' : 'none'; });
}

async function abrirModalNovedad(idNovedad) {
    await novedadCargarPersonas();
    const n = idNovedad ? novedadesCache.find(x => Number(x.IdNovedad) === Number(idNovedad)) : null;
    document.getElementById('novedad-modal-title').textContent = n ? 'Editar novedad' : 'Nueva novedad';
    document.getElementById('nov-id').value = n ? n.IdNovedad : '';
    document.getElementById('nov-persona').value = n ? n.IdMedico : '';
    document.getElementById('nov-persona').disabled = !!n;
    document.getElementById('nov-tipo').value = n ? n.Tipo : 'DIAS_NOVEDAD';
    document.getElementById('nov-concepto-dias').value = n && n.Tipo === 'DIAS_NOVEDAD' ? n.Concepto : '';
    document.getElementById('nov-dias').value = n && n.Dias !== null ? Number(n.Dias) : '';
    document.getElementById('nov-pct-dias').value = n && n.Tipo === 'DIAS_NOVEDAD' && n.Porcentaje !== null ? Number(n.Porcentaje) : '';
    document.getElementById('nov-categoria').value = n && n.Tipo === 'HORAS_EXTRA' ? n.Categoria : '';
    document.getElementById('nov-horas').value = n && n.Horas !== null ? Number(n.Horas) : '';
    document.getElementById('nov-concepto-desc').value = n && n.Tipo === 'DESCUENTO' ? (n.Concepto || '') : '';
    document.getElementById('nov-valor-desc').value = n && n.Tipo === 'DESCUENTO' && n.Valor !== null ? Number(n.Valor) : '';
    document.getElementById('nov-pct-desc').value = n && n.Tipo === 'DESCUENTO' && n.Porcentaje !== null ? Number(n.Porcentaje) : '';
    document.getElementById('nov-concepto-bono').value = n && n.Tipo === 'BONIFICACION_NO_SALARIAL' ? (n.Concepto || '') : '';
    document.getElementById('nov-valor-bono').value = n && n.Tipo === 'BONIFICACION_NO_SALARIAL' && n.Valor !== null ? Number(n.Valor) : '';
    document.getElementById('nov-observaciones').value = n ? (n.Observaciones || '') : '';
    novedadMostrarBloque();
    showModal('nueva-novedad-modal');
}

async function guardarNovedad() {
    const { anio, mes } = novedadPeriodo();
    const tipo = document.getElementById('nov-tipo').value;
    const fd = new FormData();
    fd.append('IdNovedad', document.getElementById('nov-id').value);
    fd.append('IdMedico', document.getElementById('nov-persona').value);
    fd.append('Anio', anio); fd.append('Mes', mes); fd.append('Tipo', tipo);
    fd.append('Observaciones', document.getElementById('nov-observaciones').value);
    if (tipo === 'DIAS_NOVEDAD') {
        fd.append('Concepto', document.getElementById('nov-concepto-dias').value);
        fd.append('Dias', document.getElementById('nov-dias').value);
        fd.append('Porcentaje', document.getElementById('nov-pct-dias').value);
    } else if (tipo === 'HORAS_EXTRA') {
        fd.append('Categoria', document.getElementById('nov-categoria').value);
        fd.append('Horas', document.getElementById('nov-horas').value);
    } else if (tipo === 'DESCUENTO') {
        fd.append('Concepto', document.getElementById('nov-concepto-desc').value);
        fd.append('Valor', document.getElementById('nov-valor-desc').value);
        fd.append('Porcentaje', document.getElementById('nov-pct-desc').value);
    } else {
        fd.append('Concepto', document.getElementById('nov-concepto-bono').value);
        fd.append('Valor', document.getElementById('nov-valor-bono').value);
    }
    const resp = await fetch(`${NOVEDAD_ENDPOINT}?Accion=Guardar`, { method: 'POST', body: fd }).then(r => r.json());
    if (resp.success) {
        hideModal('nueva-novedad-modal');
        cargarNovedades();
        Swal.fire('Guardado', resp.message, 'success');
    } else {
        const lista = (resp.errores && resp.errores.length)
            ? '<ul style="text-align:left">' + resp.errores.map(e => `<li>${paramEsc(e)}</li>`).join('') + '</ul>'
            : paramEsc(resp.message || 'No se pudo guardar');
        Swal.fire({ icon: 'error', title: 'Revise los datos', html: lista });
    }
}

async function eliminarNovedad(id) {
    const ok = await Swal.fire({ title: 'Eliminar la novedad', text: 'Se quita del cálculo del mes.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' });
    if (!ok.isConfirmed) return;
    const fd = new FormData();
    fd.append('IdNovedad', id);
    const resp = await fetch(`${NOVEDAD_ENDPOINT}?Accion=Eliminar`, { method: 'POST', body: fd }).then(r => r.json());
    if (resp.success) cargarNovedades(); else Swal.fire('Error', resp.message || 'No se pudo eliminar', 'error');
}

async function cargarFestivos() {
    const data = await fetch('../Controlador/ControladorFestivo.php?Accion=Listar').then(r => r.json());
    const tbody = document.querySelector('#tabla-festivos tbody');
    tbody.innerHTML = data.map(f => `<tr>
        <td>${f.Fecha}</td><td>${f.Descripcion || ''}</td>
        <td><button class="btn btn-outline btn-sm" onclick="quitarFestivo(${f.IdFestivo})">Quitar</button></td>
    </tr>`).join('');
}

async function agregarFestivo() {
    const fecha = document.getElementById('festivo-fecha').value;
    const desc = document.getElementById('festivo-desc').value;
    if (!fecha) { Swal.fire('Falta la fecha', '', 'warning'); return; }
    const fd = new FormData();
    fd.append('Fecha', fecha);
    fd.append('Descripcion', desc);
    const resp = await fetch('../Controlador/ControladorFestivo.php?Accion=Ingresar', { method: 'POST', body: fd }).then(r => r.json());
    if (resp.success) { document.getElementById('festivo-fecha').value = ''; document.getElementById('festivo-desc').value = ''; cargarFestivos(); }
    else Swal.fire('Error', resp.message || 'No se pudo agregar', 'error');
}

async function quitarFestivo(id) {
    const fd = new FormData();
    fd.append('IdFestivo', id);
    await fetch('../Controlador/ControladorFestivo.php?Accion=Eliminar', { method: 'POST', body: fd });
    cargarFestivos();
}

// ==================== Asignacion de Turnos de Enfermeria ====================
// La tabla, el calendario, la edicion de turnos y "Generar automaticamente" viven ahora en
// Vista/js/AsignacionTurnos.js (expone inicializarAsignacionTurnos() y generarAutomatico()).
// loadContent() solo elige la vista inicial: 'tabla' (Turnos > Generar/Asignar) o 'calendario'.
