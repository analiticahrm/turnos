$(document).ready(function () {
    tabla = $('#tblDatos').DataTable({});
    inicializarGridPermisos();
});

// Definir módulos del sistema
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

// Presets de permisos
const presets = {
    ninguno: '',
    soloLectura: 'R',
    completo: 'CRUD'
};

function MensajeError(titulo, Mensaje) {
    Swal.fire({
        icon: "error",
        title: titulo,
        text: Mensaje,
        customClass: { popup: 'swal2-dark' }
    });
}

function limpiar() {
    $('#fIdRol').val("0");
    $('#fTipoRol').val("");
    $('#fEstadoRol').val("3");
    // Los permisos se limpian en inicializarGridPermisos
}

function inicializarGridPermisos() {
    let gridHTML = '';
    
    modulos.forEach(modulo => {
        gridHTML += `
            <div class="modulo-card" data-modulo="${modulo.codigo}">
                <div class="modulo-header">${modulo.nombre}</div>
                <div class="permisos-opciones">
                    <div class="permiso-checkbox">
                        <input type="checkbox" id="chk${modulo.codigo}C" value="C" onchange="actualizarPermiso('${modulo.codigo}')">
                        <label for="chk${modulo.codigo}C">Crear</label>
                    </div>
                    <div class="permiso-checkbox">
                        <input type="checkbox" id="chk${modulo.codigo}R" value="R" onchange="actualizarPermiso('${modulo.codigo}')">
                        <label for="chk${modulo.codigo}R">Leer</label>
                    </div>
                    <div class="permiso-checkbox">
                        <input type="checkbox" id="chk${modulo.codigo}U" value="U" onchange="actualizarPermiso('${modulo.codigo}')">
                        <label for="chk${modulo.codigo}U">Actualizar</label>
                    </div>
                    <div class="permiso-checkbox">
                        <input type="checkbox" id="chk${modulo.codigo}D" value="D" onchange="actualizarPermiso('${modulo.codigo}')">
                        <label for="chk${modulo.codigo}D">Eliminar</label>
                    </div>
                </div>
                <div class="btn-group-permisos">
                    <button type="button" class="btn-permiso" onclick="aplicarPermisoModulo('${modulo.codigo}', '')">Ninguno</button>
                    <button type="button" class="btn-permiso" onclick="aplicarPermisoModulo('${modulo.codigo}', 'R')">Solo Lectura</button>
                    <button type="button" class="btn-permiso" onclick="aplicarPermisoModulo('${modulo.codigo}', 'CRUD')">Completo</button>
                </div>
                <input type="hidden" id="permiso${modulo.codigo}" value="R">
            </div>
        `;
    });
    
    $('#gridPermisos').html(gridHTML);
}

function actualizarPermiso(modulo) {
    const checks = $(`[id^="chk${modulo}"]`);
    let permiso = '';
    
    // CORRECCIÓN: Incluir todos los permisos marcados, incluyendo 'R'
    checks.each(function() {
        if ($(this).is(':checked')) {
            permiso += $(this).val();
        }
    });
    
    // Ordenar el permiso (C-R-U-D)
    permiso = ordenarPermiso(permiso);
    
    $(`#permiso${modulo}`).val(permiso);
}

function ordenarPermiso(permiso) {
    const orden = ['C', 'R', 'U', 'D'];
    let resultado = '';
    
    orden.forEach(letra => {
        if (permiso.includes(letra)) {
            resultado += letra;
        }
    });
    
    return resultado;
}

function aplicarPermisoModulo(modulo, tipoPermiso) {
    // Resetear checkboxes
    $(`#chk${modulo}C`).prop('checked', false);
    $(`#chk${modulo}R`).prop('checked', false);
    $(`#chk${modulo}U`).prop('checked', false);
    $(`#chk${modulo}D`).prop('checked', false);
    
    // Marcar según el tipo de permiso
    if (tipoPermiso.includes('C')) $(`#chk${modulo}C`).prop('checked', true);
    if (tipoPermiso.includes('R')) $(`#chk${modulo}R`).prop('checked', true);
    if (tipoPermiso.includes('U')) $(`#chk${modulo}U`).prop('checked', true);
    if (tipoPermiso.includes('D')) $(`#chk${modulo}D`).prop('checked', true);
    
    $(`#permiso${modulo}`).val(tipoPermiso);
}

function aplicarPreset(tipo) {
    const permiso = presets[tipo];
    
    modulos.forEach(modulo => {
        aplicarPermisoModulo(modulo.codigo, permiso);
    });
}

function cargarPermisosDesdeTexto(permisos) {
    modulos.forEach(modulo => {
        const permiso = permisos[`${modulo.codigo}Rol`] || '';
        aplicarPermisoModulo(modulo.codigo, permiso);
    });
}

function obtenerPermisosDeFormulario() {
    const permisos = {};
    
    modulos.forEach(modulo => {
        permisos[`${modulo.codigo}Rol`] = $(`#permiso${modulo.codigo}`).val();
    });
    
    return permisos;
}

function ingresar() {
    limpiar();
    $('#exampleModalLabel').html("Agregar Rol");
    $("#Accion").val("Ingresar");
    aplicarPreset('soloLectura'); // Por defecto solo lectura
    $("#ModalIngreso").modal('show');
}

function actualizar(id) {
    limpiar();
    $('#exampleModalLabel').html("Editar Rol");
    $("#Accion").val("Actualizar");
    $('#fIdRol').val(id);
    
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorRol.php?Accion=ListarPorId',
        data: { IdRol: id },
        success: function (msg) {
            $('#fTipoRol').val(msg[0]['TipoRol']);
            $('#fEstadoRol').val(msg[0]['EstadoRol']);
            
            // Cargar permisos desde la base de datos
            cargarPermisosDesdeTexto(msg[0]);
            
            $("#ModalIngreso").modal('show');
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Error", "No se pudieron cargar los datos del rol");
        }
    });
}

function validar() {
    if ($('#fTipoRol').val() == '' || $('#fEstadoRol').val() == '3') {
        MensajeError("Campos Incompletos", "Por favor complete todos los campos obligatorios");
        return false;
    }
    return true;
}

function Accion() {
    const accion = $('#Accion').val();
    const permisos = obtenerPermisosDeFormulario();
    const Rol = {
        IdRol: $.trim($('#fIdRol').val()),
        TipoRol: $.trim($('#fTipoRol').val()),
        EstadoRol: $.trim($('#fEstadoRol').val()),
        ...permisos
    };
    
    if (validar()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorRol.php?Accion=ListarPorNombre',
                data: { TipoRol: $.trim($('#fTipoRol').val()) },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Rol);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado", "Ya existe un rol con ese nombre");
                    }
                },
                error: function (reg, textStatus, errorThrown) {
                    MensajeError("Error", "No se pudo verificar la duplicación");
                }
            });
        } else if (accion == "Actualizar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorRol.php?Accion=ListarPorNombreId',
                data: {
                    TipoRol: $.trim($('#fTipoRol').val()),
                    IdRol: $.trim($('#fIdRol').val())
                },
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUD(accion, Rol);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato Duplicado", "Ya existe otro rol con ese nombre");
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Error", "No se pudo verificar la duplicación");
                }
            });
        }
    }
}

function eliminar($id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar este rol?',
        text: "No podrá recuperar los datos!",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'swal2-dark' }
    }).then((choice) => {
        if (choice.value === true) {
            const Rol = {
                IdRol: $.trim($id),
                TipoRol: '',
                AreaRol: '',
                EstadoRol: ''
            };
            CRUD('Eliminar', Rol);
        }
    });
}

function CRUD(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorRol.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            Swal.fire({
                title: "¡Éxito!",
                text: "Transacción realizada con éxito!",
                icon: "success",
                customClass: { popup: 'swal2-dark' }
            });
            setTimeout(function () {
                location.reload();
            }, 1000);
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Error", "Error en la transacción: " + textStatus);
        }
    });
}