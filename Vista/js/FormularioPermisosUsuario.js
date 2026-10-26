$(document).ready(function () {
    tabla = $('#tblUsuarios').DataTable({});
});

// Definir módulos disponibles
const modulos = [
    { codigo: 'Usuario', nombre: 'Gestión de Usuarios' },
    { codigo: 'Area', nombre: 'Gestión de Áreas' },
    { codigo: 'Rol', nombre: 'Gestión de Roles' },
    { codigo: 'Actividad', nombre: 'Actividades' },
    { codigo: 'Especialidad', nombre: 'Especialidades' },
    { codigo: 'Medico', nombre: 'Médicos' },
    { codigo: 'Nomenclaturas', nombre: 'Nomenclaturas' },
    { codigo: 'Turnos', nombre: 'Turnos' },
    { codigo: 'Extra', nombre: 'Extras' },
    { codigo: 'Efectivas', nombre: 'Efectivas' },
    { codigo: 'GPC', nombre: 'GPC' },
    { codigo: 'DHC', nombre: 'DHC' }
];

// Definir tipos de permisos
const tiposPermiso = [
    { codigo: '', nombre: 'Sin permiso (usar rol base)' },
    { codigo: 'R', nombre: 'Solo Lectura' },
    { codigo: 'RC', nombre: 'Lectura y Creación' },
    { codigo: 'CU', nombre: 'Creación y Actualización' },
    { codigo: 'UD', nombre: 'Actualización y Eliminación' },
    { codigo: 'RU', nombre: 'Lectura y Actualización' },
    { codigo: 'RD', nombre: 'Lectura y Eliminación' },
    { codigo: 'RUD', nombre: 'Lectura, Actualización, Eliminación' },
    { codigo: 'CRUD', nombre: 'Control Total' }
];

function MensajeError(titulo, Mensaje) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje,
        customClass: { popup: 'swal2-dark' }
    });
}

function MensajeExito(titulo, Mensaje) {
    Swal.fire({
        icon: "success",
        title: titulo,
        text: Mensaje,
        customClass: { popup: 'swal2-dark' }
    });
}

function asignarPermisos(IdUsuario) {
    $('#fIdUsuarioPermiso').val(IdUsuario);
    
    // Cargar información del usuario
    $.ajax({
        type: 'POST',
        url: '../Controlador/Controladorusuario.php?Accion=ListarPorId',
        data: { IdUsuario: IdUsuario },
        success: function (usuario) {
            $('#nombreUsuarioSeleccionado').text(usuario[0]['NombreUsuario']);
            $('#rolBaseUsuario').text(usuario[0]['TipoRol']); // Asumiendo que el controlador devuelve el tipo de rol
            
            // Cargar permisos existentes del usuario
            cargarPermisosUsuario(IdUsuario);
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Error", "No se pudo cargar la información del usuario");
        }
    });
}

function cargarPermisosUsuario(idUsuario) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorPermisoUsuario.php?Accion=ListarPorUsuario',
        data: { IdUsuario: idUsuario },
        success: function (permisosExistentes) {
            generarGridPermisos(permisosExistentes);
            $("#ModalPermisos").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Error", "No se pudieron cargar los permisos existentes");
        }
    });
}

function generarGridPermisos(permisosExistentes) {
    let gridHTML = '';
    const permisosMap = {};
    
    // Convertir array de permisos a objeto para fácil acceso
    permisosExistentes.forEach(permiso => {
        permisosMap[permiso.ModuloPermiso] = permiso.TipoPermiso;
    });
    
    modulos.forEach(modulo => {
        const permisoActual = permisosMap[modulo.codigo] || '';
        
        gridHTML += `
            <div class="modulo-card">
                <div class="modulo-header">${modulo.nombre}</div>
                <div class="permiso-options">
                    <select class="form-control select-permiso" data-modulo="${modulo.codigo}">
        `;
        
        tiposPermiso.forEach(tipo => {
            const selected = tipo.codigo === permisoActual ? 'selected' : '';
            gridHTML += `<option value="${tipo.codigo}" ${selected}>${tipo.nombre}</option>`;
        });
        
        gridHTML += `
                    </select>
                </div>
            </div>
        `;
    });
    
    $('#gridPermisos').html(gridHTML);
}

function guardarPermisos() {
    const idUsuario = $('#fIdUsuarioPermiso').val();
    const permisos = [];
    
    // Recolectar todos los permisos seleccionados
    $('.select-permiso').each(function() {
        const modulo = $(this).data('modulo');
        const tipoPermiso = $(this).val();
        
        if (tipoPermiso !== '') { // Solo incluir si tiene permiso específico
            permisos.push({
                modulo: modulo,
                tipo: tipoPermiso
            });
        }
    });
    
    // Primero eliminar todos los permisos existentes
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorPermisoUsuario.php?Accion=EliminarTodos',
        data: { IdUsuario: idUsuario },
        success: function() {
            // Luego insertar los nuevos permisos
            let permisosGuardados = 0;
            let totalPermisos = permisos.length;
            
            if (totalPermisos === 0) {
                MensajeExito("Éxito", "Permisos actualizados correctamente");
                $("#ModalPermisos").modal('hide');
                setTimeout(function() { location.reload(); }, 1000);
                return;
            }
            
            permisos.forEach(permiso => {
                $.ajax({
                    type: 'POST',
                    url: '../Controlador/ControladorPermisoUsuario.php?Accion=Ingresar',
                    data: {
                        IdUsuario: idUsuario,
                        ModuloPermiso: permiso.modulo,
                        TipoPermiso: permiso.tipo
                    },
                    success: function() {
                        permisosGuardados++;
                        if (permisosGuardados === totalPermisos) {
                            MensajeExito("Éxito", "Permisos actualizados correctamente");
                            $("#ModalPermisos").modal('hide');
                            setTimeout(function() { location.reload(); }, 1000);
                        }
                    },
                    error: function() {
                        permisosGuardados++;
                        if (permisosGuardados === totalPermisos) {
                            MensajeExito("Éxito", "Permisos actualizados con algunas advertencias");
                            $("#ModalPermisos").modal('hide');
                            setTimeout(function() { location.reload(); }, 1000);
                        }
                    }
                });
            });
        },
        error: function() {
            MensajeError("Error", "No se pudieron actualizar los permisos");
        }
    });
}

function verPermisos(idUsuario) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorPermisoUsuario.php?Accion=ListarPorUsuario',
        data: { IdUsuario: idUsuario },
        success: function (permisos) {
            let listaHTML = '<ul>';
            
            if (permisos.length === 0) {
                listaHTML += '<li>El usuario no tiene permisos específicos. Usa los permisos de su rol base.</li>';
            } else {
                permisos.forEach(permiso => {
                    const moduloInfo = modulos.find(m => m.codigo === permiso.ModuloPermiso);
                    const tipoInfo = tiposPermiso.find(t => t.codigo === permiso.TipoPermiso);
                    listaHTML += `<li><strong>${moduloInfo.nombre}:</strong> ${tipoInfo.nombre}</li>`;
                });
            }
            
            listaHTML += '</ul>';
            $('#listaPermisos').html(listaHTML);
            $("#ModalVerPermisos").modal('show');
        },
        error: function() {
            MensajeError("Error", "No se pudieron cargar los permisos");
        }
    });
}

function mostrarUsuarios() {
    // Ya está mostrado por defecto
}