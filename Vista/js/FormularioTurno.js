function MensajeError(titulo, Mensaje, foot) {
  Swal.fire({
    icon: "error",
    title: titulo,
    text: Mensaje,
    footer: foot
  });
}

let fechaSeleccionada = null;
let mesSeleccionado = null;
let yearSeleccionado = null;
let urlParams = new URLSearchParams(window.location.search);
let idAreaUrl = urlParams.get("id");
let dataTable; // Variable global para la instancia de DataTable

var calendar; // Declarar globalmente

function inicializarDataTable(tableId) {
  // Destruir instancia anterior si existe
  if ($.fn.DataTable.isDataTable("#" + tableId)) {
    $("#" + tableId)
      .DataTable()
      .destroy();
    $("#" + tableId).empty(); // Limpiar completamente
  }

  // Inicializar DataTable
  const dataTable = $("#" + tableId).DataTable({
    responsive: true,
    language: {
      url: "../Vista/js/es-ES.json"
    },
    pageLength: 5,
    lengthMenu: [
      [5, 10, 25, 50, -1],
      [5, 10, 25, 50, "Todos"]
    ],
    order: [[0, "desc"]],
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    columnDefs: [
      {
        targets: [4, 5],
        orderable: false,
        searchable: false
      }
    ]
  });

  return dataTable;
}

// Función auxiliar para limpiar completamente
// Función alternativa de destrucción más agresiva
function destruirDataTableCompletamente(tableId) {
  const table = $("#" + tableId);
  if ($.fn.DataTable.isDataTable(table)) {
    const dataTable = table.DataTable();

    // Destruir con todas las opciones
    dataTable.clear().destroy();

    // Limpiar completamente
    table.removeAttr("style").removeClass("dataTable no-footer");
    table.find("thead, tfoot").remove();
    table.find("tbody").empty();
  }
}
// Inicializar DataTable cuando el modal se muestre
$("#modalTurnos").on("shown.bs.modal", function () {
  if ($.fn.DataTable.isDataTable("#tblDatos")) {
    dataTable.draw();
  } else {
    inicializarDataTable();
  }
});

// Destruir DataTable cuando el modal se cierre para evitar conflictos
$("#modalTurnos").on("hidden.bs.modal", function () {
  if ($.fn.DataTable.isDataTable("#tblDatos")) {
    $("#tblDatos").DataTable().destroy();
  }
});

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////Actividades//////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

const nombreArea = urlParams.get('nombreArea');

// Variable global para debug
const DEBUG = true;

// Variable para almacenar el ID de actividad en edición
let idActividadEnEdicion = null;

// Función de logging mejorada
function logDebug(message, data = null, type = 'info') {
    if (!DEBUG) return;
    
    const timestamp = new Date().toLocaleTimeString();
    const styles = {
        info: 'color: #007bff; font-weight: bold;',
        success: 'color: #28a745; font-weight: bold;',
        warning: 'color: #ffc107; font-weight: bold;',
        error: 'color: #dc3545; font-weight: bold;',
        id: 'color: #6f42c1; font-weight: bold;',
        ajax: 'color: #17a2b8; font-weight: bold;'
    };
    
    console.log(`%c[${timestamp}] ${message}`, styles[type] || styles.info);
    if (data !== null) {
        console.log('📦 Datos:', data);
    }
    console.log('----------------------------------------');
}

// Función para obtener el valor del campo ID de manera segura
function getSafeIdValue() {
    // Intentar con jQuery primero
    let value = $('#fIdActividad').val();
    if (value !== undefined && value !== null && value !== '') {
        return value;
    }
    
    // Intentar con DOM
    const element = document.getElementById('fIdActividad');
    if (element) {
        return element.value;
    }
    
    logDebug('⚠️ Elemento fIdActividad no encontrado o vacío', null, 'warning');
    return null;
}

// Función para establecer el valor del campo ID de manera segura
function setSafeIdValue(value) {
    logDebug('🔄 Intentando establecer ID:', {
        valor: value,
        tipo: typeof value
    });
    
    // Asegurar que el valor sea string
    const stringValue = String(value);
    
    // 1. Intentar con jQuery
    try {
        $('#fIdActividad').val(stringValue);
        const jqueryResult = $('#fIdActividad').val();
        logDebug('✅ jQuery estableció valor:', {
            esperado: stringValue,
            obtenido: jqueryResult,
            coincide: jqueryResult === stringValue
        });
    } catch (e) {
        logDebug('❌ Error con jQuery:', e.message, 'error');
    }
    
    // 2. Intentar con DOM directamente
    try {
        const element = document.getElementById('fIdActividad');
        if (element) {
            element.value = stringValue;
            logDebug('✅ DOM estableció valor:', {
                esperado: stringValue,
                obtenido: element.value,
                coincide: element.value === stringValue
            });
        } else {
            logDebug('⚠️ Elemento DOM no encontrado, creándolo...', null, 'warning');
            // Crear el elemento si no existe
            crearCampoIdOculto();
            // Intentar nuevamente
            const newElement = document.getElementById('fIdActividad');
            if (newElement) {
                newElement.value = stringValue;
                logDebug('✅ Elemento creado y valor establecido:', newElement.value);
            }
        }
    } catch (e) {
        logDebug('❌ Error con DOM:', e.message, 'error');
    }
    
    // 3. Verificar que se estableció correctamente
    const finalValue = getSafeIdValue();
    logDebug('🔍 Verificación final:', {
        esperado: stringValue,
        obtenido: finalValue,
        coincide: finalValue === stringValue
    });
    
    return finalValue === stringValue;
}

// Función para crear el campo ID si no existe
function crearCampoIdOculto() {
    // Verificar si ya existe
    if (document.getElementById('fIdActividad')) {
        logDebug('✅ Campo fIdActividad ya existe');
        return true;
    }
    
    logDebug('🛠️ Creando campo fIdActividad oculto', null, 'warning');
    
    try {
        // Crear el input
        const input = document.createElement('input');
        input.type = 'hidden';
        input.id = 'fIdActividad';
        input.name = 'fIdActividad';
        input.value = '0';
        
        // Buscar el modal body para agregarlo
        const modalBody = document.querySelector('#ModalIngresoAct .modal-body');
        if (modalBody) {
            // Agregar al principio del modal body
            modalBody.prepend(input);
            logDebug('✅ Campo fIdActividad creado en el modal', null, 'success');
        } else {
            // Si no encuentra el modal body, buscar el modal
            const modal = document.getElementById('ModalIngresoAct');
            if (modal) {
                modal.appendChild(input);
                logDebug('⚠️ Campo fIdActividad agregado al modal (no se encontró modal-body)', null, 'warning');
            } else {
                // Agregar al body como último recurso
                document.body.appendChild(input);
                logDebug('⚠️ Campo fIdActividad creado en el body (no se encontró modal)', null, 'warning');
            }
        }
        
        // Verificar que se creó correctamente
        const created = document.getElementById('fIdActividad');
        if (created) {
            logDebug('✅ Campo fIdActividad creado exitosamente', {
                id: created.id,
                type: created.type,
                value: created.value
            }, 'success');
            return true;
        } else {
            logDebug('❌ No se pudo crear el campo fIdActividad', null, 'error');
            return false;
        }
    } catch (e) {
        logDebug('❌ Error al crear campo:', e.message, 'error');
        return false;
    }
}

// Inicializar DataTable cuando el modal de actividades se muestre
$('#modalActividades').on('shown.bs.modal', function () {
    logDebug('Modal de actividades mostrado');
    if ($.fn.DataTable.isDataTable('#tblDatosActividades')) {
        dataTableActividades.draw();
    } else {
        inicializarDataTable();
    }
});

// Destruir DataTable cuando el modal de actividades se cierre
$('#modalActividades').on('hidden.bs.modal', function () {
    logDebug('Modal de actividades ocultado');
    if ($.fn.DataTable.isDataTable('#tblDatosActividades')) {
        $('#tblDatosActividades').DataTable().destroy();
    }
});

// Controlar cuando se cierra el modal de edición
$('#ModalIngresoAct').on('hidden.bs.modal', function () {
    logDebug('🔄 Modal de edición cerrado');
    // No limpiar automáticamente
});

// Controlar cuando se abre el modal de edición
$('#ModalIngresoAct').on('shown.bs.modal', function () {
    logDebug('🔄 Modal de edición mostrado');
    // Asegurar que el campo ID exista
    crearCampoIdOculto();
    // Si hay un ID en edición, restaurarlo
    if (idActividadEnEdicion) {
        setSafeIdValue(idActividadEnEdicion);
        logDebug('✅ ID restaurado al mostrar modal:', idActividadEnEdicion, 'id');
    }
});

// Controlar antes de mostrar el modal
$('#ModalIngresoAct').on('show.bs.modal', function () {
    logDebug('🔄 Modal de edición a punto de mostrarse');
    crearCampoIdOculto();
});

function ingresarAct() {
    logDebug('Ejecutando ingresarAct()');
    idActividadEnEdicion = null;
    cargarActividades();
    $('#modalActividadesLabel').html("Ver Actividades");
    $("#modalActividades").modal('show');
}

function VerMedicos() {
    logDebug('Ejecutando VerMedicos()');
    idActividadEnEdicion = null;
    $('#modalMedicosLabel').html("Ver Medicos");
    $("#modalMedicos").modal('show');
}

function CrearAct() {
    logDebug('Ejecutando CrearAct() - Nueva actividad');
    idActividadEnEdicion = null;
    crearCampoIdOculto();
    setSafeIdValue('0');
    document.getElementById('fNombreActividad').value = '';
    document.getElementById('fValorHoraActividad').value = '';
    document.getElementById('fValorHoraEfectivaActividad').value = '';
    document.getElementById('fInterconsultaActividad').value = '3';
    document.getElementById('fEstadoActividad').value = '3';
    document.getElementById('fTipoActividad').value = '';
    $("#Accion").val("Ingresar");
    $('#exampleModalLabelAct').html("Crear Actividad");
    $("#ModalIngresoAct").modal('show');
}

function validarAct() {
    logDebug('Validando formulario...');
    const campos = {
        fNombreActividad: $('#fNombreActividad').val(),
        fTipoActividad: $('#fTipoActividad').val(),
        fValorHoraActividad: $('#fValorHoraActividad').val(),
        fIdArea: $('#fIdArea').val(),
        fInterconsultaActividad: $('#fInterconsultaActividad').val(),
        fEstadoActividad: $('#fEstadoActividad').val()
    };
    
    logDebug('Campos del formulario:', campos);
    
    if (
        campos.fNombreActividad == '' ||
        campos.fTipoActividad == '' ||
        campos.fValorHoraActividad == '' ||
        campos.fIdArea == '' ||
        campos.fInterconsultaActividad == '' ||
        campos.fEstadoActividad == ''
    ) {
        logDebug('❌ Validación fallida - Campos incompletos', null, 'error');
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false;
    } else {
        logDebug('✅ Validación exitosa', null, 'success');
        return true;
    }
}

function limpiarFormulario() {
    logDebug('🧹 Ejecutando limpiarFormulario() - ANTES:', {
        fIdActividad: getSafeIdValue(),
        fNombreActividad: $('#fNombreActividad').val()
    });
    
    // Asegurar que el campo existe
    crearCampoIdOculto();
    
    // Limpiar todos los campos EXCEPTO el ID
    document.getElementById('fNombreActividad').value = '';
    document.getElementById('fValorHoraActividad').value = '';
    document.getElementById('fValorHoraEfectivaActividad').value = '';
    document.getElementById('fInterconsultaActividad').value = '3';
    document.getElementById('fEstadoActividad').value = '3';
    document.getElementById('fTipoActividad').value = '';
    
    // NO limpiar el ID aquí, solo resetearlo a 0
    setSafeIdValue('0');
    
    logDebug('🧹 limpiarFormulario() - DESPUÉS:', {
        fIdActividad: getSafeIdValue(),
        fNombreActividad: $('#fNombreActividad').val()
    });
}

// Función para cargar las actividades via AJAX
function cargarActividades() {
    const idArea = document.getElementById('fIdArea').value;
    logDebug('🔄 Cargando actividades para el área:', { IdArea: idArea });
    
    let permisosActividad = ActividadRol || "";
    let UpdateActividad = permisosActividad.includes("U");
    let DeleteActividad = permisosActividad.includes("D");
    
    logDebug('Permisos del usuario:', { permisosActividad, UpdateActividad, DeleteActividad });
    
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorActividad.php?Accion=ListarPorIdAreaFull',
        data: { IdArea: idArea },
        dataType: 'json',
        success: function(response) {
            logDebug('✅ Actividades cargadas exitosamente', { 
                cantidad: response ? response.length : 0,
                actividades: response ? response.map(a => ({ 
                    id: a.IdActividad, 
                    nombre: a.NombreActividad 
                })) : []
            }, 'success');
            
            if ($.fn.DataTable.isDataTable('#tblDatosActividades')) {
                $('#tblDatosActividades').DataTable().destroy();
            }
            
            let tbody = document.getElementById('content-actividades');
            tbody.innerHTML = '';
            
            if (response && response.length > 0) {
                response.forEach(function(actividad) {
                    let fila = document.createElement('tr');
                    
                    let botonUpdateActividad = '';
                    let botonDeleteActividad = '';
                    
                    if (UpdateActividad) {
                        botonUpdateActividad = `
                            <button onclick="actualizarAct(${actividad.IdActividad})" class="delete_btn" title="Actualizar Registro">
                                <span class="material-icons">update</span>
                            </button>
                        `;
                    }
                    
                    if (DeleteActividad) {
                        botonDeleteActividad = `
                            <button onclick="eliminarAct(${actividad.IdActividad})" class="delete_btn" title="Eliminar Registro">
                                <span class="material-icons">delete</span>
                            </button>
                        `;
                    }
                    
                    fila.innerHTML = `
                        <td>${actividad.NombreActividad}</td>
                        <td>${TipoActividad(actividad.TipoActividad)}</td>
                        <td>${formatearValorMonetario(actividad.ValorHoraActividad)}</td>
                        <td>${formatearValorMonetario(actividad.ValorHoraEfectivaActividad)}</td>
                        <td>
                            ${actividad.InterconsultaActividad == "1" ? 
                                '<img src="../Assets/ICON/activo.png" alt="Activo" class="IconEstado">' : 
                                '<img src="../Assets/ICON/inactivo.png" alt="Inactivo" class="IconEstado">'}
                        </td>
                        <td>
                            ${actividad.EstadoActividad == "1" ? 
                                '<img src="../Assets/ICON/activo.png" alt="Activo" class="IconEstado">' : 
                                '<img src="../Assets/ICON/inactivo.png" alt="Inactivo" class="IconEstado">'}
                        </td>
                        <td>
                            ${botonUpdateActividad}
                            ${botonDeleteActividad}
                        </td>
                    `;
                    
                    tbody.appendChild(fila);
                });
                
                inicializarDataTable('tblDatosActividades');
            } else {
                logDebug('⚠️ No hay actividades registradas', null, 'warning');
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay actividades registradas</td></tr>';
            }
        },
        error: function(xhr, status, error) {
            logDebug('❌ Error al cargar actividades:', { 
                status, 
                error, 
                response: xhr.responseText 
            }, 'error');
            MensajeError("Error", "No se pudieron cargar las actividades");
        }
    });
}

function TipoActividad(TipoActividad) {
    switch (TipoActividad) {
        case "1": return "TURNO";
        case "2": return "DISPONIBILIDAD";
        case "3": return "CONSULTA EXTERNA";
        case "4": return "JORNADA CX";
        case "5": return "ADMINISTRATIVO";
        case "6": return "EVENTO";
        case "7": return "OTRO";
        default: return "DESCONOCIDO";
    }
}

function listarAct(IdArea, IdActividad) {
    logDebug('📋 listarAct llamado:', { IdArea, IdActividad });
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorActividad.php?Accion=ListarPorIdArea',
        data: { IdActividad: IdActividad, IdArea: IdArea},
        success: function (msg) {
            logDebug('✅ listarAct response:', msg);
        },
        error: function (req, textStatus, errorThrown) {
            logDebug('❌ Error en listarAct:', { textStatus, errorThrown }, 'error');
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown);
        }
    });
}

function actualizarAct(id) {
    logDebug('🔄 INICIO actualizarAct()', { 
        idRecibido: id,
        tipo: typeof id,
        esValido: id !== null && id !== undefined && id !== ''
    }, 'ajax');

    // Guardar el ID en la variable global
    idActividadEnEdicion = id;
    logDebug('🔑 ID guardado en variable global:', idActividadEnEdicion, 'id');

    // Asegurar que el campo ID existe
    crearCampoIdOculto();

    // Limpiar formulario (esto NO debe borrar el ID)
    limpiarFormulario();
    
    // Restaurar el ID después de limpiar (FORZAR la asignación)
    setSafeIdValue(id);
    
    // Verificar que se estableció correctamente
    const verifiedId = getSafeIdValue();
    logDebug('✅ ID verificado después de asignación:', {
        esperado: id,
        obtenido: verifiedId,
        coincide: String(verifiedId) === String(id)
    }, 'id');

    // Establecer valores del modal
    $('#exampleModalLabelAct').html("Editar Actividades");
    $("#Accion").val("Actualizar");

    // Hacer la petición AJAX para obtener los datos
    logDebug('📡 Enviando petición AJAX para obtener datos de actividad:', { 
        IdActividad: id,
        URL: '../Controlador/ControladorActividad.php?Accion=ListarPorId'
    }, 'ajax');

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorActividad.php?Accion=ListarPorId',
        data: { IdActividad: id },
        dataType: 'json',
        success: function (msg) {
            logDebug('✅ AJAX exitoso - Datos recibidos:', {
                response: msg,
                tipo: typeof msg,
                esArray: Array.isArray(msg),
                longitud: msg ? msg.length : 0,
                primerElemento: msg && msg.length > 0 ? msg[0] : null
            }, 'success');

            if (msg && msg.length > 0) {
                const actividad = msg[0];
                logDebug('📊 Datos de la actividad:', {
                    IdActividad: actividad.IdActividad,
                    NombreActividad: actividad.NombreActividad,
                    TipoActividad: actividad.TipoActividad,
                    ValorHoraActividad: actividad.ValorHoraActividad,
                    EstadoActividad: actividad.EstadoActividad
                });

                // Asegurar que el campo ID existe antes de asignar
                crearCampoIdOculto();

                // Asignar todos los valores al formulario
                // FORZAR el ID nuevamente
                setSafeIdValue(actividad.IdActividad);
                $('#fNombreActividad').val(actividad.NombreActividad);
                $('#fTipoActividad').val(actividad.TipoActividad);
                $('#fValorHoraActividad').val(formatearValorMonetario(actividad.ValorHoraActividad));
                $('#fValorHoraEfectivaActividad').val(formatearValorMonetario(actividad.ValorHoraEfectivaActividad));
                $('#fInterconsultaActividad').val(actividad.InterconsultaActividad);
                $('#fEstadoActividad').val(actividad.EstadoActividad);

                // Verificar final
                const finalId = getSafeIdValue();
                logDebug('✅ Formulario actualizado con los datos:', {
                    'fIdActividad': finalId,
                    'fNombreActividad': $('#fNombreActividad').val(),
                    'fTipoActividad': $('#fTipoActividad').val(),
                    'fValorHoraActividad': $('#fValorHoraActividad').val()
                }, 'success');

                // Verificar si el tipo de actividad es DISPONIBILIDAD
                if (actividad.TipoActividad === '2') {
                    $('#campoValorHoraEfectiva').show();
                    logDebug('📌 Mostrando campo ValorHoraEfectiva (Tipo=2)');
                } else {
                    $('#campoValorHoraEfectiva').hide();
                    logDebug('📌 Ocultando campo ValorHoraEfectiva (Tipo=' + actividad.TipoActividad + ')');
                }

                // Mostrar el modal
                logDebug('📱 Mostrando modal de edición');
                $("#ModalIngresoAct").modal('show');
                
                // Asegurar el ID después de mostrar el modal
                setTimeout(function() {
                    crearCampoIdOculto();
                    setSafeIdValue(idActividadEnEdicion);
                    const postModalId = getSafeIdValue();
                    logDebug('🔒 ID asegurado después de mostrar modal:', {
                        esperado: idActividadEnEdicion,
                        obtenido: postModalId,
                        coincide: String(postModalId) === String(idActividadEnEdicion)
                    }, 'id');
                }, 100);
                
            } else {
                logDebug('❌ No se encontraron datos para la actividad ID:', id, 'error');
                MensajeError("Error", "No se encontraron datos para la actividad");
                $("#ModalIngresoAct").modal('show');
            }
        },
        error: function (req, textStatus, errorThrown) {
            logDebug('❌ Error en la petición AJAX:', {
                textStatus,
                errorThrown,
                status: req.status,
                responseText: req.responseText
            }, 'error');
            MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown);
            $("#ModalIngresoAct").modal('show');
        }
    });
}

function formatearValorMonetario(valor) {
    if (valor === null || valor === undefined) {
        return '$0';
    }
    valor = valor.toString();
    let partes = valor.split('.');
    let parteEntera = partes[0];
    let parteDecimal = partes.length > 1 ? ',' + partes[1] : '';
    parteEntera = parteEntera.replace(/[^0-9]/g, '');
    if (parteEntera.length > 3) {
        parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    return '$' + parteEntera + parteDecimal;
}

function eliminarAct(id) {
    logDebug('🗑️ Eliminar actividad:', { idActividad: id });
    
    Swal.fire({
        title: '¿Está seguro que desea eliminar la Actividad ' + id + '?',
        text: "No podrá recuperar los datos!",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,      
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((choice) => {
        if (choice.value === true) {
            logDebug('✅ Confirmada eliminación para ID:', id);
            var Actividad = {
                IdActividad: $.trim(id),
                NombreActividad: $.trim($('#fNombreActividad').val()),
                TipoActividad: $.trim($('#fTipoActividad').val()),
                ValorHoraActividad: $.trim($('#fValorHoraActividad').val()),
                ValorHoraEfectivaActividad: $.trim($('#fValorHoraEfectivaActividad').val()),
                IdArea: $.trim($('#fIdArea').val()),
                InterconsultaActividad: $.trim($('#fInterconsultaActividad').val()),
                EstadoActividad: $.trim($('#fEstadoActividad').val())
            };
            CRUDAct('Eliminar', Actividad);
        } else {
            logDebug('❌ Eliminación cancelada para ID:', id);
        }
    });
}

function AccionAct() {
    logDebug('🎯 INICIO AccionAct()', null, 'ajax');
    
    const accion = $('#Accion').val();
    logDebug('Acción a realizar:', accion);
    
    // Intentar obtener el ID de múltiples fuentes
    let idActividad = getSafeIdValue();
    
    // Si el campo está vacío o es null o es 0, usar la variable global
    if (!idActividad || idActividad === '0' || idActividad === '' || idActividad === 'null') {
        if (idActividadEnEdicion) {
            idActividad = idActividadEnEdicion;
            // FORZAR la asignación en el campo
            setSafeIdValue(idActividad);
            logDebug('🔄 ID restaurado desde variable global:', idActividad, 'id');
        }
    }
    
    logDebug('🔑 ID de actividad obtenido en AccionAct:', {
        id: idActividad,
        tipo: typeof idActividad,
        esValido: idActividad && idActividad !== '0' && idActividad !== '' && idActividad !== 'null',
        esNumero: !isNaN(idActividad)
    }, 'id');
    
    // Si estamos actualizando y no hay ID válido, mostrar error
    if (accion === "Actualizar" && (!idActividad || idActividad === '0' || idActividad === '' || idActividad === 'null')) {
        logDebug('❌ ERROR: ID de actividad inválido para Actualizar', {
            idActividad: idActividad,
            accion: accion,
            idActividadEnEdicion: idActividadEnEdicion
        }, 'error');
        
        // Último intento: buscar en el DOM
        const hiddenInputs = document.querySelectorAll('input[type="hidden"]');
        let foundId = null;
        hiddenInputs.forEach(input => {
            if (input.id === 'fIdActividad' || input.name === 'fIdActividad') {
                foundId = input.value;
                logDebug('🔍 ID encontrado en búsqueda DOM:', foundId);
            }
        });
        
        if (foundId && foundId !== '0' && foundId !== '') {
            idActividad = foundId;
            logDebug('✅ ID recuperado de búsqueda DOM:', idActividad);
        } else {
            Swal.fire({
                title: 'Error al actualizar',
                html: `
                    <p>No se encontró un ID de actividad válido.</p>
                    <p style="font-size: 12px; color: #666;">Valor obtenido: <strong>${idActividad || 'NULL'}</strong></p>
                    <p style="font-size: 12px; color: #666;">ID en edición: <strong>${idActividadEnEdicion || 'NULL'}</strong></p>
                    <p style="font-size: 12px; color: #666;">Acción: <strong>${accion}</strong></p>
                    <p style="font-size: 12px; color: #666;">Revisa la consola para más detalles.</p>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return false;
        }
    }
    
    // Procesar valores numéricos
    let valorNumerico = document.getElementById('fValorHoraActividad').value.replace(/[^0-9]/g, '');
    let valorNumericoEfec = document.getElementById('fValorHoraEfectivaActividad').value.replace(/[^0-9]/g, '');
    
    // Construir objeto Actividad
    var Actividad = {
        IdActividad: idActividad || '0',
        NombreActividad: $.trim($('#fNombreActividad').val()),
        TipoActividad: $.trim($('#fTipoActividad').val()),
        ValorHoraActividad: valorNumerico || '0',
        ValorHoraEfectivaActividad: valorNumericoEfec || '0',
        IdArea: $.trim($('#fIdArea').val()),
        InterconsultaActividad: $.trim($('#fInterconsultaActividad').val()),
        EstadoActividad: $.trim($('#fEstadoActividad').val())
    };
    
    logDebug('📦 Objeto Actividad construido:', Actividad);
    
    if (!validarAct()) {
        logDebug('❌ Validación fallida - Saliendo de AccionAct', null, 'error');
        return false;
    }
    
    // Procesar según la acción
    if (accion === "Ingresar") {
        logDebug('📝 Procesando NUEVA actividad');
        $.ajax({
            type: 'POST',
            url: '../Controlador/ControladorActividad.php?Accion=ListarPorNombre',
            data: { 
                NombreActividad: $.trim($('#fNombreActividad').val()), 
                IdArea: $.trim($('#fIdArea').val())
            },
            success: function (msg) {
                logDebug('✅ Validación de duplicado para Ingresar:', { 
                    resultado: msg,
                    esVacio: JSON.stringify(msg) == '[]'
                });
                
                if (JSON.stringify(msg) == '[]') {
                    CRUDAct(accion, Actividad);
                    $('#ModalIngresoAct').modal('hide');
                } else {
                    MensajeError("Dato Duplicado al Insertar", "Actividad Registrada con Anterioridad!");
                }
            },
            error: function (req, textStatus, errorThrown) {
                logDebug('❌ Error en validación de duplicado:', { textStatus, errorThrown }, 'error');
                MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown);
            }
        });
    } 
    else if (accion === "Actualizar") {
        logDebug('✏️ Procesando ACTUALIZACIÓN de actividad', {
            id: idActividad,
            nombre: Actividad.NombreActividad,
            area: Actividad.IdArea
        });
        
        $.ajax({
            type: 'POST',
            url: '../Controlador/ControladorActividad.php?Accion=ListarPorNombreId',
            data: {
                NombreActividad: $.trim($('#fNombreActividad').val()),
                IdActividad: idActividad,
                IdArea: $.trim($('#fIdArea').val())
            },
            success: function (msg) {
                logDebug('✅ Validación de duplicado para Actualizar:', { 
                    resultado: msg,
                    esVacio: JSON.stringify(msg) == '[]'
                });
                
                if (JSON.stringify(msg) == '[]') {
                    CRUDAct(accion, Actividad);
                    idActividadEnEdicion = null;
                    $('#modalCRUD').modal('hide');
                } else {
                    MensajeError("Dato Duplicado al Actualizar", "Actividad Registrada con Anterioridad!");
                }
            },
            error: function (req, textStatus, errorThrown) {
                logDebug('❌ Error en validación de duplicado:', { textStatus, errorThrown }, 'error');
                MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown);
            }
        });
    } else {
        logDebug('⚠️ Acción no reconocida:', accion, 'warning');
    }
}

function CRUDAct(accion, objEvento) {
    logDebug(`🔄 CRUDAct - Acción: ${accion}`, { 
        datos: objEvento,
        id: objEvento.IdActividad
    }, 'ajax');
    
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorActividad.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            logDebug(`✅ CRUDAct - ${accion} exitoso:`, { 
                response: msg,
                status: 'success'
            }, 'success');
            
            if (msg) {
                Swal.fire({
                    title: "¡Exito!",
                    text: "Transacción realizada con exito!",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
                idActividadEnEdicion = null;
                $('#ModalIngresoAct').modal('hide');
                cargarActividades();
            }
        },
        error: function (req, textStatus, errorThrown) {
            logDebug(`❌ CRUDAct - ${accion} falló:`, {
                status: req.status,
                textStatus: textStatus,
                error: errorThrown,
                response: req.responseText
            }, 'error');
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown);
        }
    });
}

// Función para verificar el estado actual del formulario
function verificarEstadoFormulario() {
    const idValue = getSafeIdValue();
    logDebug('🔍 ESTADO ACTUAL DEL FORMULARIO:', {
        'fIdActividad': idValue,
        'fNombreActividad': $('#fNombreActividad').val(),
        'fTipoActividad': $('#fTipoActividad').val(),
        'fValorHoraActividad': $('#fValorHoraActividad').val(),
        'fValorHoraEfectivaActividad': $('#fValorHoraEfectivaActividad').val(),
        'fInterconsultaActividad': $('#fInterconsultaActividad').val(),
        'fEstadoActividad': $('#fEstadoActividad').val(),
        'Accion': $('#Accion').val(),
        'idActividadEnEdicion': idActividadEnEdicion
    });
}

// Ejecutar verificación al cargar la página
$(document).ready(function() {
    logDebug('🚀 Página cargada - Iniciando sistema de actividades');
    // Asegurar que el campo ID existe desde el inicio
    setTimeout(function() {
        crearCampoIdOculto();
        verificarEstadoFormulario();
    }, 100);
});
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////Turnos//////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function VerTurnos() {
  limpiar();
  $("#modalTablaTurnosLabel").html("Ver Turnos");
  $("#ModalTablaTurnos").modal("show");
}
function limpiar() {
  $("#fnomenclatura").val();
  $("#fnombre").val();
  $("#fdescripcion").val();
}
function limpiarTurnos() {
  $("#fIdMedico").val("").trigger("change");
  $("#fFechaHoraInicio").val("");
  $("#fFechaHoraFin").val("");
  $("#fNomenclatura").val("");
}
function ingresarTurnos() {
  limpiarTurnos();
  $("#exampleModalLabel2").html("Agregar Turnos");
  $("#Accion").val("Ingresar");
  const fechaInicioValue = fechaSeleccionada + "T00:00";
  const fechaFinValue = fechaSeleccionada + "T00:00";
  $("#fFechaHoraInicio").val(fechaInicioValue);
  $("#fFechaHoraFin").val(fechaFinValue);
  $("#ModalIngresoTurnos").modal("show");
  document.getElementById("btnEliminar").style.display = "none";
}
function actualizarTurnos(id) {
  limpiarTurnos();
  
  // Primero verificar el estado del turno antes de cargar los datos
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorTurno.php?Accion=ListarPorId",
    data: { IdTurno: id },
    success: function (msg) {
      // Verificar si el turno tiene estado 3 (liquidado)
      if (msg[0]["EstadoAdministrarTurnos"] == 3) {
        MensajeError(
          "Turno Liquidado",
          "No se puede editar un turno que ya ha sido liquidado"
        );
        return; // Salir de la función sin mostrar el modal
      }
      
      // Si no está liquidado, continuar con la carga normal
      $("#exampleModalLabel2").html("Editar Turnos");
      $("#Accion").val("Actualizar");
      $("#fIdTurno").val(id);

      // Verificar si el botón btnEliminar existe antes de modificar su estilo
      const btnEliminar = document.getElementById("btnEliminar");
      if (btnEliminar) {
        btnEliminar.style.display = "inline-block";
      }
      // Formatea las fechas al formato adecuado para datetime-local (YYYY-MM-DDTHH:MM)
      const fechaHoraInicio = msg[0]["FechaHoraInicio"]
        .replace(" ", "T")
        .slice(0, 16); // "YYYY-MM-DDTHH:MM"
      const fechaHoraFin = msg[0]["FechaHoraFin"]
        .replace(" ", "T")
        .slice(0, 16); // "YYYY-MM-DDTHH:MM"
      
      // Asigna los valores al formulario
      $("#fIdTurno").val(msg[0]["IdTurno"]);
      $("#fFechaHoraInicio").val(fechaHoraInicio);
      $("#fFechaHoraFin").val(fechaHoraFin);
      $("#fIdMedico").val(msg[0]["IdMedico"]).trigger("change.select2");
      $("#fNomenclatura").val(msg[0]["IdNomenclatura"]);
      $("#ModalIngresoTurnos").modal("show");
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos!",
        textStatus + " " + errorThrown
      );
    }
  });
}
function validarTurnos() {
  if ($("#fIdMedico").val() == "") {
    MensajeError(
      "Campos Incompletos",
      "Por favor diligencie todos los campos en el Formulario"
    );
    return false;
  } else {
    return true;
  }
}

async function AccionTurnos() {
  accion = $("#Accion").val();

  // Primero solicitamos la observación
  $("#ModalIngresoTurnos").modal("hide");
  const { value: observation } = await Swal.fire({
    title:
      "Observación para " +
      (accion === "Ingresar" ? "crear" : "actualizar") +
      " turno",
    input: "textarea",
    inputLabel: "Ingrese una observación:",
    inputPlaceholder: "Escriba sus observaciones aquí...",
    inputAttributes: {
      "aria-label": "Escriba sus observaciones aquí"
    },
    showCancelButton: true,
    confirmButtonText: accion === "Ingresar" ? "Crear" : "Actualizar",
    cancelButtonText: "Cancelar",
    customClass: {
      popup: "swal2-dark"
    },
    inputValidator: (value) => {
      if (!value) {
        return "Debe ingresar una observación";
      }
    }
  });

  // Si el usuario cancela la observación, no continuamos
  if (!observation) {
    return;
  }

  var Turnos = {
    IdTurno: $.trim($("#fIdTurno").val()),
    IdArea: $.trim($("#fIdArea").val()),
    FechaHoraInicio: $.trim($("#fFechaHoraInicio").val()).split("T")[0],
    FechaHoraFin: $.trim($("#fFechaHoraFin").val()).split("T")[0],
    IdNomenclatura: $.trim($("#fNomenclatura").val()),
    IdMedico: $.trim($("#fIdMedico").val()),
    EstadoTurno: $.trim($("#fEstadoTurno").val()),
    IdUsuario: IdUsuario,
    IdRol: IdRol,
    Observacion: observation // Agregamos la observación al objeto
  };

  if (validarTurnos()) {
    if (accion == "Ingresar") {
      $.ajax({
        type: "POST",
        url: "../Controlador/ControladorTurno.php?Accion=ListarPorTurno",
        data: {
          IdMedico: $.trim($("#fIdMedico").val()),
          IdNomenclatura: $.trim($("#fNomenclatura").val()),
          FechaHoraInicio: $.trim($("#fFechaHoraInicio").val()).split("T")[0],
          FechaHoraFin: $.trim($("#fFechaHoraFin").val()).split("T")[0],
          EstadoTurno: $.trim($("#fEstadoTurno").val())
        },
        success: async function (msg) {
          if (JSON.stringify(msg) == "[]") {
            let cruce = await validarcruces(Turnos);
            if (cruce) {
              CRUDTurnos(accion, Turnos);
            } else {
              return;
            }
            $("#ModalIngreso").modal("hide");
          } else {
            MensajeError(
              "Dato Duplicado al Insertar",
              "Turno Registrado con Anterioridad!"
            );
          }
        },
        error: function (req, textStatus, errorThrown) {
          MensajeError(
            "Oops...",
            "Error al obtener Datos!",
            textStatus + " " + errorThrown
          );
        }
      });
    }

    if (accion == "Actualizar") {
      $.ajax({
        type: "POST",
        url: "../Controlador/ControladorTurno.php?Accion=ListarPorTurno",
        data: {
          IdMedico: $.trim($("#fIdMedico").val()),
          IdNomenclatura: $.trim($("#fNomenclatura").val()),
          FechaHoraInicio: $.trim($("#fFechaHoraInicio").val()).split("T")[0],
          FechaHoraFin: $.trim($("#fFechaHoraFin").val()).split("T")[0],
          EstadoTurno: $.trim($("#fEstadoTurno").val())
        },
        success: async function (msg) {
          if (JSON.stringify(msg) == "[]") {
            let cruce = await validarcruces(Turnos);
            if (cruce) {
              CRUDTurnos(accion, Turnos);
            } else {
              return;
            }
            $("#modalCRUD").modal("hide");
          } else {
            MensajeError(
              "Dato Duplicado al Actualizar",
              "Turno Registrada con Anterioridad!"
            );
          }
        },
        error: function (req, textStatus, errorThrown) {
          MensajeError(
            "Oops...",
            "Error al obtener Datos!",
            textStatus + " " + errorThrown
          );
        }
      });
    }
  }
}
function validarcruces(objEvento) {
  return new Promise(function (resolve, reject) {
    $.ajax({
      type: "POST",
      url: "../Controlador/ControladorTurno.php?Accion=ListarPorCruce",
      data: objEvento,
      success: function (msg) {
        if (JSON.stringify(msg) == "[]") {
          resolve(true); // Resolvemos la promesa con 'true'
        } else {
          MensajeError(
            "Datos crusados al Insertar",
            "Turno se crusa con otro turno!"
          );
          resolve(false); // Resolvemos la promesa con 'false'
        }
      },
      error: function (req, textStatus, errorThrown) {
        MensajeError(
          "Oops...",
          "Error al obtener Datos54!",
          textStatus + " " + errorThrown
        );
        resolve(false); // En caso de error, resolvemos con 'false'
      }
    });
  });
}
function eliminarTurnos($id) {
  // Primero verificar el estado del turno antes de intentar eliminar
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorTurno.php?Accion=ListarPorId",
    data: { IdTurno: $id },
    success: function (msg) {
      // Verificar si el turno tiene estado 3 (liquidado)
      if (msg[0]["EstadoAdministrarTurnos"] == 3) {
        MensajeError(
          "Turno Liquidado",
          "No se puede eliminar un turno que ya ha sido liquidado"
        );
        return; // Salir de la función sin mostrar el diálogo de eliminación
      }
      
      // Si no está liquidado, continuar con el proceso normal de eliminación
      Swal.fire({
        title: "¿Está seguro que desea eliminar el Turno " + $id + "?",
        text: "No podrá recuperar los datos!",
        type: "warning",
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: "#058876",
        cancelButtonColor: "#550101",
        confirmButtonText: "Sí, eliminarlo!",
        cancelButtonText: "Cancelar",
        customClass: {
          popup: "swal2-dark"
        }
      }).then(async (choice) => {
        if (choice.value === true) {
          $("#ModalIngresoTurnos").modal("hide");
          const { value: observation } = await Swal.fire({
            title: "Observación para eliminar turno",
            input: "textarea",
            inputLabel: "Ingrese una observación para la eliminación:",
            inputPlaceholder: "Escriba sus observaciones aquí...",
            inputAttributes: {
              "aria-label": "Escriba sus observaciones aquí"
            },
            showCancelButton: true,
            confirmButtonText: "Confirmar eliminación",
            cancelButtonText: "Cancelar",
            customClass: {
              popup: "swal2-dark"
            },
            inputValidator: (value) => {
              if (!value) {
                return "Debe ingresar una observación";
              }
            }
          });

          if (observation) {
            var Turno = {
              IdTurno: $.trim($id),
              IdArea: $.trim($("#fIdArea").val()),
              FechaHoraInicio: $.trim($("#fFechaHoraInicio").val()).split("T")[0],
              FechaHoraFin: $.trim($("#fFechaHoraFin").val()).split("T")[0],
              IdNomenclatura: $.trim($("#fNomenclatura").val()),
              IdMedico: $.trim($("#fIdMedico").val()),
              IdUsuario: IdUsuario,
              IdRol: IdRol,
              Observacion: observation
            };
            CRUDTurnos("Eliminar", Turno);
          }
        }
      });
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al verificar el estado del turno!",
        textStatus + " " + errorThrown
      );
    }
  });
}
function CRUDTurnos(accion, objEvento) {
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorTurno.php?Accion=" + accion,
    data: objEvento,
    success: function (msg) {
      if (msg) {
        // Mensaje de éxito en el Swal
        Swal.fire({
          title: "¡Éxito!",
          text: "Transacción realizada con éxito!",
          icon: "success",
          customClass: {
            popup: "swal2-dark"
          }
        }).then(() => {
          // Definir el mensaje de la notificación según la acción
          let mensajeNotificacion;
          let PrioridadNotificacion;
          let TipoNotificacion;
          switch (accion.toLowerCase()) {
            case "ingresar":
              mensajeNotificacion = `El área de ${nombreArea} ha ingresado nuevo turno asistencial.`;
              PrioridadNotificacion = `baja`;
              TipoNotificacion = 1;
              break;
            case "actualizar":
              mensajeNotificacion = `El área de ${nombreArea} ha actualizado turno asistencial.`;
              PrioridadNotificacion = `media`;
              TipoNotificacion = 2;
              break;
            case "eliminar":
              mensajeNotificacion = `El área de ${nombreArea} ha eliminado turno asistencial.`;
              PrioridadNotificacion = `urgente`;
              TipoNotificacion = 3;
              break;
            default:
              mensajeNotificacion = `Se realizó una acción en turnos médicos del área ${nombreArea}.`;
          }

          // Enviar la notificación
          $.ajax({
            type: "POST",
            url: "../Controlador/ControladorNotificaciones.php?Accion=IngresarAdmin",
            data: {
              TipoNotificaciones: TipoNotificacion, // Ej: 'turno_ingresar', 'turno_actualizar'
              MensajeNotificaciones: mensajeNotificacion,
              EnlaceNotificaciones:
                "https://turnos.hrm.gov.co/Vista/FormularioTurnos.php?id=" +
                idAreaUrl +
                "&nombreArea=" +
                nombreArea +
                "",
              PrioridadNotificaciones: PrioridadNotificacion,
              FechaNotificaciones: formatearFecha(new Date())
            },
            success: function (notifResponse) {
              if (notifResponse.success) {
                console.log("Notificación creada con éxito");
              } else {
                console.error("Error al crear notificación");
              }
              // ACTUALIZAR EL CALENDARIO AUNQUE FALLE LA NOTIFICACIÓN
              actualizarCalendario();
              // Cerrar el modal si está abierto
              $("#ModalIngresoTurnos").modal("hide");
              $("#modalCal").modal("hide");
            },
            error: function () {
              console.error("Error en la solicitud de notificación");
              // ACTUALIZAR EL CALENDARIO AUNQUE FALLE LA NOTIFICACIÓN
              actualizarCalendario();
              // Cerrar el modal si está abierto
              $("#ModalIngresoTurnos").modal("hide");
              $("#modalCal").modal("hide");
            }
          });
        });
      }
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error en la Transacción!",
        textStatus + " " + errorThrown
      );
    }
  });
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////Horas Efectivas//////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
var table;
function limpiarhoras() {
    $('#fIdHorasEfectivas').val(0);
    $('#fIdMedicos').val("");
    $('#fIdTurnos').val("");
    $('#fFecHorFin').val("");
    $('#fFecHorIni').val("");
    $('#fObsHorasEfectivas').val("");
    $('#fEstadoHorasEfectivas').val("");
    // Limpiar el contenedor de observación de rechazo
    $('#obsRechazoEfectivasContainer').remove();
}

function ingresarHorEfec() {
    limpiarhoras()
    $("#Accion").val("Ingresar");
    $('#exampleModalLabelHor').html("Agregar Horas Efectivas");
    $("#ModalEfectivas").modal('show');
    cargarMedicos();
}

$(document).ready(function() {
    $('#fIdMedicos').change(function() {
        var idMedico = $(this).val();
        if (idMedico) {
            // Llamada sin callback (comportamiento normal)
            cargarTurnosMedico(idMedico);
            $('#fIdTurnos').prop('disabled', false);
        } else {
            $('#fIdTurnos').empty()
                .append('<option value="">Seleccione un médico primero</option>')
                .prop('disabled', true);
        }
    });
});

function cargarMedicos() {
    $.ajax({
        url: '../Controlador/ControladorMedico.php?Accion=listarPorArea&idArea=' + idAreaUrl,
        type: 'GET',
        dataType: 'json',
        success: function(data, textStatus, jqXHR) {

            $('#fIdMedicos').empty();
            $('#fIdMedicos').append('<option value="">Seleccione un médico</option>');
            
            $.each(data, function(index, medico) {
                $('#fIdMedicos').append(
                    $('<option></option>').val(medico.IdMedico).text(medico.NombreMedico)
                );
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la petición:', {
                status: jqXHR.status,
                statusText: textStatus,
                error: errorThrown,
                responseText: jqXHR.responseText
            });
            
            $('#fIdMedicos').empty();
            $('#fIdMedicos').append('<option value="">Error al cargar médicos</option>');
        },
        complete: function(jqXHR, textStatus) {
            console.log('Petición completada con estado:', textStatus);
        }
    });
}

function cargarTurnosMedico(idMedico, callback) {
    if (!idMedico) {
        $('#fIdTurnos').empty()
            .append('<option value="">Seleccione un médico primero</option>')
            .prop('disabled', true);
        
        if (typeof callback === 'function') callback();
        return;
    }

    $.ajax({
        url: '../Controlador/ControladorTurno.php?Accion=ListarTurnosDisponibilidadSinCorte',
        type: 'POST',
        data: {
            IdMedico: idMedico,
            month: mesSeleccionado,
            year: yearSeleccionado
        },
        dataType: 'json',
        success: function(data, status, xhr) {
            const $selectTurnos = $('#fIdTurnos').empty();
            
            if (data && data.length > 0) {
                $selectTurnos.append('<option value="">Seleccione un turno</option>');
                
                data.forEach(turno => {
                    const inicio = formatDateTime(turno.FechaHoraInicio);
                    const fin = formatDateTime(turno.FechaHoraFin);
                    const texto = `${inicio} - ${fin}`;
                    
                    $selectTurnos.append(
                        $('<option>', {
                            value: turno.IdTurno,
                            text: texto,
                            'data-inicio': turno.FechaHoraInicio,
                            'data-fin': turno.FechaHoraFin
                        })
                    );
                });
                
                $selectTurnos.prop('disabled', false);
            } else {
                $selectTurnos.append('<option value="">No hay turnos disponibles</option>');
            }
            
            // Ejecutar callback si existe (para el caso de edición)
            if (typeof callback === 'function') callback();
        },
        error: function(xhr, status, error) {
            $('#fIdTurnos').empty()
                .append('<option value="">Error al cargar turnos</option>')
                .prop('disabled', true);
            
            if (typeof callback === 'function') callback();
        }
    });
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '';
    
    try {
        const date = new Date(dateTimeString);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Error formateando fecha:', dateTimeString, e);
        return dateTimeString; // Devuelve el original si hay error
    }
}

function actualizarHorEfec(id) {
    limpiarhoras();
    $('#exampleModalLabelHor').html("Editar horas efectivas");
    $("#Accion").val("Actualizar");
    $('#fIdHorasEfectivas').val(id);

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorId',
        data: { IdHorasEfectivas: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                MensajeError("Error", "No se encontraron datos para editar");
                return;
            }

            const datos = response[0];
            
            // VERIFICACIÓN DEL ESTADO Y PERMISOS PARA ACTUALIZAR
            if (datos.EstadoHorasEfectivas == '3') { // Liquidado
                // Verificar permisos de actualización
                let permisos = EfectivasRol || "";
                let tienePermisoUpdate = permisos.includes("U");
                
                // Si no tiene permiso de actualización O no es super admin, bloquear
                if (!tienePermisoUpdate || (IdRol !== '1' && IdRol !== '7')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Registro Liquidado',
                        text: 'No se puede actualizar horas efectivas después de haber sido liquidadas.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                    return; // Salir sin abrir el modal
                } else {
                    // Si tiene permisos y es super admin, permitir con advertencia
                    Swal.fire({
                        icon: 'info',
                        title: 'Registro Liquidado - Modo Super Admin',
                        text: 'Está editando un registro liquidado. Esta acción solo está permitida para super administradores.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            cargarDatosEnFormularioEfectivas(datos);
                        }
                    });
                    return;
                }
            }
            
            // Verificar permiso normal de actualización
            let permisos = EfectivasRol || "";
            let tienePermisoUpdate = permisos.includes("U");
            
            if (!tienePermisoUpdate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin permisos',
                    text: 'No tiene permisos para actualizar horas efectivas.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
                return;
            }
            
            // Cargar datos normalmente
            cargarDatosEnFormularioEfectivas(datos);
            
        },
        error: function(xhr, textStatus, errorThrown) {
            MensajeError("Error", "Error al obtener datos: " + textStatus);
        }
    });
}

// Función auxiliar para cargar datos en el formulario (actualizada)
function cargarDatosEnFormularioEfectivas(datos) {
    $('#fIdHorasEfectivas').val(datos.IdHorasEfectivas);
    $('#fFecHorIni').val(datos.FecHorIni);
    $('#fFecHorFin').val(datos.FecHorFin);
    $('#fObsHorasEfectivas').val(datos.ObsHorasEfectivas);
    $('#fEstadoHorasEfectivas').val(datos.EstadoHorasEfectivas);

    // Mostrar observación de rechazo si existe y el estado es rechazado
    if (datos.EstadoHorasEfectivas == '2' && datos.ObsRechazoEfectivas) {
        // Crear o mostrar elemento para la observación de rechazo
        let obsRechazoElement = $('#obsRechazoEfectivasContainer');
        if (obsRechazoElement.length === 0) {
            // Crear el elemento si no existe
            $('<div id="obsRechazoEfectivasContainer" class="form-group"><label>Observación de Rechazo:</label><div class="form-control" style="background-color: #f8d7da; color: #721c24; min-height: 80px;">' + datos.ObsRechazoEfectivas + '</div></div>')
                .insertBefore('#ModalEfectivas .modal-footer');
        } else {
            // Actualizar el elemento si existe
            obsRechazoElement.find('.form-control').text(datos.ObsRechazoEfectivas);
        }
    } else {
        // Ocultar el elemento si no hay observación de rechazo
        $('#obsRechazoEfectivasContainer').remove();
    }

    // Cargar médicos primero
    cargarMedicos2(function() {
        // Seleccionar el médico (esto disparará el evento change)
        $('#fIdMedicos').val(datos.IdMedico).trigger('change.select2');
        
        // Cargar turnos con callback para seleccionar el específico
        cargarTurnosMedico(datos.IdMedico, function() {
            // Seleccionar el turno después de cargados
            $('#fIdTurnos').val(datos.IdTurno);
            
            // Mostrar el modal
            $("#ModalEfectivas").modal('show');
        });
    });
}

function cargarMedicos2(callback) {
    $.ajax({
        url: '../Controlador/ControladorMedico.php?Accion=listarPorArea&idArea=' + idAreaUrl,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#fIdMedicos').empty().append('<option value="">Seleccione un médico</option>');
            
            $.each(data, function(index, medico) {
                $('#fIdMedicos').append(
                    $('<option></option>').val(medico.IdMedico).text(medico.NombreMedico)
                );
            });
            
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function() {
            $('#fIdMedicos').empty().append('<option value="">Error al cargar médicos</option>');
        }
    });
}

function eliminarHorEfec(id) {
    // Primero obtener los datos del registro para verificar el estado
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorId',
        data: { IdHorasEfectivas: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                MensajeError("Error", "No se encontraron datos para eliminar");
                return;
            }

            const datos = response[0];
            
            // VERIFICACIÓN DEL ESTADO Y PERMISOS PARA ELIMINAR
            if (datos.EstadoHorasEfectivas == '3') { // Liquidado
                // Solo permitir eliminación a super administradores
                if (IdRol !== '1' && IdRol !== '7') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Registro Liquidado',
                        text: 'No se puede eliminar horas efectivas después de haber sido liquidadas.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                    return; // Salir sin mostrar confirmación de eliminación
                } else {
                    // Si es super admin, mostrar advertencia especial
                    Swal.fire({
                        title: '¡PRECAUCIÓN! - Registro Liquidado',
                        html: 'Está intentando eliminar un registro <strong>LIQUIDADO</strong>.<br><br>' +
                              '¿Está seguro que desea eliminar las horas efectivas ' + id + '?<br>' +
                              '<strong>Esta acción no se puede deshacer!</strong>',
                        icon: 'warning',
                        showCloseButton: true,
                        showCancelButton: true,      
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar de todas formas!',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    }).then((choice) => {
                        if (choice.value === true) {
                            procederEliminacion(id);
                        }
                    });
                    return;
                }
            }
            
            // Verificar permiso normal de eliminación
            let permisos = EfectivasRol || "";
            let tienePermisoDelete = permisos.includes("D");
            
            if (!tienePermisoDelete) {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin permisos',
                    text: 'No tiene permisos para eliminar horas efectivas.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        popup: 'swal2-dark'
                    }
                });
                return;
            }
            
            // Si pasa todas las validaciones, mostrar confirmación normal
            Swal.fire({
                title: '¿Está seguro que desea eliminar horas efectivas ' + id + '?',
                text: "No podrá recuperar los datos!",
                icon: 'warning',
                showCloseButton: true,
                showCancelButton: true,      
                confirmButtonColor: '#058876',
                cancelButtonColor: '#550101',
                confirmButtonText: 'Sí, eliminarlo!',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'swal2-dark'
                }
            }).then((choice) => {
                if (choice.value === true) {
                    procederEliminacion(id);
                }
            });
        },
        error: function(xhr, textStatus, errorThrown) {
            MensajeError("Error", "Error al verificar datos: " + textStatus);
        }
    });
}

// Función auxiliar para proceder con la eliminación
function procederEliminacion(id) {
    var HorasEfec = {
        IdHorasEfectivas: $.trim(id),
        IdTurno: $.trim($('#fIdTurnos').val()), 
        FecHorIni: $.trim($('#fFecHorIni').val()),
        FecHorFin: $.trim($('#fFecHorFin').val()),
        ObsHorasEfectivas: $.trim($('#fObsHorasEfectivas').val()),
        EstadoHorasEfectivas: $.trim($('#fEstadoHorasEfectivas').val())
    }
    CRUDHor('Eliminar', HorasEfec);
}

function validarHor() {
    if (
        $('#fIdMedicos').val() == '' ||
        $('#fIdTurnos').val() == '' ||
        $('#fFecHorIni').val() == '' ||
        $('#fFecHorFin').val() == '' ||
        $('#fEstadoHorasEfectivas').val() == 4
    ) {
        MensajeError("Campos Incompletos", "Por favor diligencie todos los campos en el Formulario");
        return false
    } else {
        return true
    }
}

function AccionHoras() {
    let accion = $('#Accion').val();
    let estadoHorasEfectivas = $.trim($('#fEstadoHorasEfectivas').val());
    let obsRechazoEfectivas = null;
    
    // Si el estado es "Rechazado" (2), solicitar observación
    if (estadoHorasEfectivas === '2') {
        // Mostrar modal para ingresar observación de rechazo
        $("#ModalEfectivas").modal('hide');
        
        Swal.fire({
            title: 'Observación de Rechazo',
            input: 'textarea',
            inputLabel: 'Por favor ingrese la razón del rechazo:',
            inputPlaceholder: 'Describa por qué se rechazan estas horas efectivas...',
            inputAttributes: {
                'aria-label': 'Describa por qué se rechazan estas horas efectivas'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar Rechazo',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal2-dark'
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe ingresar una observación para el rechazo';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                obsRechazoEfectivas = result.value;
                // Continuar con el proceso después de obtener la observación
                continuarProcesoEfectivas(accion, estadoHorasEfectivas, obsRechazoEfectivas);
            } else {
                // Si cancela, volver a mostrar el modal
                $("#ModalEfectivas").modal('show');
            }
        });
        
        return; // Salir de la función aquí, continuará en el callback
    }
    
    // Si no es rechazo, continuar normalmente
    continuarProcesoEfectivas(accion, estadoHorasEfectivas, obsRechazoEfectivas);
}

// Función auxiliar para continuar el proceso después de manejar el rechazo
function continuarProcesoEfectivas(accion, estadoHorasEfectivas, obsRechazoEfectivas) {
    // Validación adicional para actualizaciones de registros liquidados
    if (accion === 'Actualizar') {
        const idHorasEfectivas = $.trim($('#fIdHorasEfectivas').val());
        
        // Verificar permisos de actualización
        let permisos = EfectivasRol || "";
        let tienePermisoUpdate = permisos.includes("U");
        
        if (!tienePermisoUpdate) {
            Swal.fire({
                icon: 'error',
                title: 'Sin permisos',
                text: 'No tiene permisos para actualizar horas efectivas.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
            return;
        }
    }
    
    var HorasEfec = {
        IdHorasEfectivas: $.trim($('#fIdHorasEfectivas').val()),
        IdTurno: $.trim($('#fIdTurnos').val()), 
        FecHorIni: $.trim($('#fFecHorIni').val()),
        FecHorFin: $.trim($('#fFecHorFin').val()),
        ObsHorasEfectivas: $.trim($('#fObsHorasEfectivas').val()),
        EstadoHorasEfectivas: estadoHorasEfectivas,
        ObsRechazoEfectivas: obsRechazoEfectivas,  // Nuevo campo para observación de rechazo
        IdUsuarioRechazo: (estadoHorasEfectivas === '2') ? IdUsuario : null  // Agregar IdUsuario solo cuando se rechace
    }

    if (validarHor()) {
        if (accion == "Ingresar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorCruce',
                data: {IdMedico: $.trim($('#fIdMedicos').val()), FecHorIni: $.trim($('#fFecHorIni').val()), FecHorFin: $.trim($('#fFecHorFin').val())},
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUDHor(accion, HorasEfec);
                        $('#ModalIngreso').modal('hide');
                    } else {
                        MensajeError("Dato crusado al Insertar", "Medico ya tiene horas efectiva registradas en ese rango de fechas!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos555!", textStatus + ' ' + errorThrown)
                }
            });
        }
        if (accion == "Actualizar") {
            $.ajax({
                type: 'POST',
                url: '../Controlador/ControladorHorasEfectivas.php?Accion=ListarPorCruceActualizar',
                data: {IdHorasEfectivas: $.trim($('#fIdHorasEfectivas').val()), IdMedico: $.trim($('#fIdMedicos').val()), FecHorIni: $.trim($('#fFecHorIni').val()), FecHorFin: $.trim($('#fFecHorFin').val())},
                success: function (msg) {
                    if (JSON.stringify(msg) == '[]') {
                        CRUDHor(accion, HorasEfec);
                        $('#modalCRUD').modal('hide');
                    } else {
                        MensajeError("Dato crusado al Insertar", "Medico ya tiene horas efectiva registradas en ese rango de fechas!")
                    }
                },
                error: function (req, textStatus, errorThrown) {
                    MensajeError("Oops...", "Error al obtener Datos!", textStatus + ' ' + errorThrown)
                }
            });
        }
    } else {
        // Si la validación falla, volver a mostrar el modal
        $("#ModalEfectivas").modal('show');
    }
}

function CRUDHor(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasEfectivas.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Transacción realizada con éxito!",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                    }
                }).then(() => {
                    // En lugar de recargar la página, actualizamos solo la tabla
                    if ($.fn.DataTable.isDataTable('#tablaHorasEfectivas')) {
                        table.ajax.reload(null, false); // false para mantener la paginación
                    }
                    
                    // Cerrar el modal si está abierto
                    $('#ModalEfectivas').modal('hide');
                });
            }
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
}

function ingresarHoras() {
    if ($.fn.DataTable.isDataTable('#tablaHorasEfectivas')) {
        table = $('#tablaHorasEfectivas').DataTable();
        table.ajax.reload(); // Recargar datos si ya existe
    } else {
        table = $('#tablaHorasEfectivas').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "../Controlador/ControladorHorasEfectivas.php?Accion=ListarAreaMes",
                "data": function(d) {
                    d.IdArea = idAreaUrl;
                    d.month = mesSeleccionado;
                    d.year = yearSeleccionado;
                },
                "dataSrc": function(json) {
                    if (!json || !json.data) {
                        console.error("La respuesta no contiene 'data'.");
                        return [];
                    }
                    return json.data;
                },
                "error": function(xhr, status, error) {
                    console.error("Error en la solicitud:", status, error);
                    console.log("Respuesta completa:", xhr.responseText);
                }
            },
            "columns": [
                { "data": "NombreMedico" },
                { "data": "FechaHoraInicio" },
                { "data": "FechaHoraFin" },
                { "data": "FecHorIni" },
                { "data": "FecHorFin" },
                { "data": "HorasEfectivas" },
                // NUEVA COLUMNA: Observación de Rechazo
                { 
                    "data": "ObsRechazoEfectivas",
                    "render": function(data, type, row) {
                        if (row.EstadoHorasEfectivas == '2' && data) {
                            return `<span class="badge bg-danger obs-rechazo-btn" title="${data}">Ver Rechazo</span>`;
                        }
                        return '';
                    }
                },
                { 
                    "data": "EstadoHorasEfectivas",
                    "render": function(data, type, row) {
                        let imagen = "";
                        let tooltip = "";
                        
                        switch(data.toLowerCase()) {
                            case "0":
                                imagen = "pendiente.png";
                                tooltip = "Pendiente de aprobación";
                                break;
                            case "1":
                                imagen = "activo.png";
                                tooltip = "Confirmada";
                                break;
                            case "2":
                                imagen = "inactivo.png";
                                tooltip = "Rechazada" + (row.ObsRechazoEfectivas ? `: ${row.ObsRechazoEfectivas}` : '');
                                break;
                            case "3":
                                imagen = "liquidado.png";
                                tooltip = "Liquidada";
                                break;
                            default:
                                imagen = "desconocido.png";
                                tooltip = "Estado desconocido";
                        }
                        
                        return `<img src="../Assets/ICON/${imagen}" alt="${data}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    }
                },
                { 
                    "data": "IdHorasEfectivas",
                    "render": function(data, type, row) {
                        // Obtener los permisos de la variable EfectivasRol
                        let permisos = EfectivasRol || "";
                        
                        // Verificar qué permisos tiene el usuario
                        let tienePermisoUpdate = permisos.includes("U");
                        let tienePermisoDelete = permisos.includes("D");
                        
                        // Crear los botones según los permisos
                        let botonUpdate = '';
                        let botonDelete = '';
                        
                        if (tienePermisoUpdate) {
                            botonUpdate = `
                                <button class="update_btn" data-id="${data}">
                                    <span class="material-icons">update</span>
                                </button>
                            `;
                        }
                        
                        if (tienePermisoDelete) {
                            botonDelete = `
                                <button class="delete_btn" data-id="${data}">
                                    <span class="material-icons">delete</span>
                                </button>
                            `;
                        }
                        
                        return botonUpdate + botonDelete;
                    },
                    "orderable": false,
                    "searchable": false
                }
            ],
            "language": {
                "decimal": ",",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });
        
        // Manejar eventos de los botones
        $('#tablaHorasEfectivas').on('click', '.update_btn', function() {
            var id = $(this).data('id');
            actualizarHorEfec(id);
        });

        $('#tablaHorasEfectivas').on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            eliminarHorEfec(id);
        });

        // NUEVO: Evento para mostrar observación de rechazo al hacer clic en el badge
        $('#tablaHorasEfectivas').on('click', '.obs-rechazo-btn', function() {
            const obsRechazo = $(this).attr('title');
            Swal.fire({
                title: 'Observación de Rechazo',
                html: `<div style="text-align: left; background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; max-height: 300px; overflow-y: auto;">${obsRechazo}</div>`,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        });
    }
    return table;
}

$(document).ready(function() {
    // Inicializar la tabla por primera vez
    table = ingresarHoras();
    
    // Evento para abrir el modal
    $('#abrirModalHoras').click(function() {
        table = ingresarHoras();
        table.ajax.reload();
        $('#horasEfectivasModal').modal('show');
    });
});

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////Nomenclaturas///////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

const activities = [];

// Inicializar DataTable cuando el modal de actividades se muestre
$("#modalTurnos").on("shown.bs.modal", function () {
  if ($.fn.DataTable.isDataTable("#tblDatosNomenclaturas")) {
    dataTableActividades.draw();
  } else {
    inicializarDataTable();
  }
});

// Destruir DataTable cuando el modal de actividades se cierre
$("#modalTurnos").on("hidden.bs.modal", function () {
  if ($.fn.DataTable.isDataTable("#tblDatosNomenclaturas")) {
    $("#tblDatosNomenclaturas").DataTable().destroy();
  }
});

function limpiar() {
  $("#fnomenclatura").val();
  $("#fnombre").val();
  $("#fdescripcion").val();
}

function ingresarNom() {
  cargarnomenclaturas();

  limpiar();
  $("#modalTurnosLabel").html("Ver nomenclaturas");
  $("#modalTurnos").modal("show");
}
function ingresar(IdArea) {
  listarActvidades(IdArea);
  limpiar();
  $("#exampleModalLabel").html("Agregar nomenclaturas");
  $("#Accion").val("Ingresar");
  $("#ModalIngresoNomenclatura").modal("show");
}

function cargarnomenclaturas() {
    
    let permisosNomenclaturas = NomenclaturasRol || "";
    
    // Verificar qué permisos tiene el usuario (fuera del loop)
    let UpdateNomenclaturas = permisosNomenclaturas.includes("U");
    let DeleteNomenclaturas = permisosNomenclaturas.includes("D");
    
    
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorNomenclatura.php?Accion=ListarPorIdAreaFull",
    data: { IdArea: $("#fIdArea").val() },
    dataType: "json",
    success: function (msg) {
      // Destruir DataTable si existe
      if ($.fn.DataTable.isDataTable("#tblDatosNomenclaturas")) {
        $("#tblDatosNomenclaturas").DataTable().destroy();
      }

      let tbody = document.getElementById("content-nomenclaturas");
      tbody.innerHTML = "";
      msg.forEach(function (item) {
        // Calcular diferencia en horas
        let horasDiff = "";
        if (item.FechaHoraInicio && item.FechaHoraFin) {
          let inicio = new Date("1970-01-01T" + item.FechaHoraInicio);
          let fin = new Date("1970-01-01T" + item.FechaHoraFin);

          // Si la hora fin es menor que inicio, asumimos que cruza medianoche
          if (fin < inicio) {
            fin.setDate(fin.getDate() + 1);
          }

          let diffMs = fin - inicio;
          horasDiff = diffMs / (1000 * 60 * 60); // convertir a horas
        }

        let tr = document.createElement("tr");
        
        // Crear los botones según los permisos para CADA nomenclatura
        let botonUpdateNomenclaturas = '';
        let botonDeleteNomenclaturas = '';
        
        if (UpdateNomenclaturas) {
            botonUpdateNomenclaturas = `
                <button onclick="actualizarNom(${item.IdNomenclatura})" class="delete_btn" title="Actualizar Registro">
                    <span class="material-icons">update</span>
                </button>
            `;
        }
        
        if (DeleteNomenclaturas) {
            botonDeleteNomenclaturas = `
                <button onclick="eliminar(${item.IdNomenclatura})" class="delete_btn" title="Eliminar Registro">
                    <span class="material-icons">delete</span>
                </button>
            `;
        }
        
        tr.innerHTML = `
                    <td>${item.CodNomenclatura}</td>
                    <td>${item.Nombre}</td>
                    <td>${item.Descripcion}</td>
                    <td>${item.NombreActividad}</td>
                    <td>${item.FechaHoraInicio}</td>
                    <td>${item.FechaHoraFin}</td>
                    <td>${horasDiff}</td>
                    <td>${
                      item.EstadoNomenclatura == "1"
                        ? '<img src="../Assets/ICON/activo.png" alt="Activo" class="IconEstado">'
                        : '<img src="../Assets/ICON/inactivo.png" alt="Inactivo" class="IconEstado">'
                    }</td>
                    <td>
                        ${botonUpdateNomenclaturas}
                        ${botonDeleteNomenclaturas}
                    </td>
                `;
        tbody.appendChild(tr);
      });

      // Inicializar o recargar DataTable
      inicializarDataTable("tblDatosNomenclaturas");
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos!",
        textStatus + " " + errorThrown
      );
    }
  });
}

function actualizarNom(id) {
  limpiar();
  $("#exampleModalLabelNom").html("Editar Nomenclatura");
  $("#Accion").val("Actualizar");
  $("#fIdNomenclatura").val(id);
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorNomenclatura.php?Accion=ListarPorId",
    data: { IdNomenclatura: id },
    success: function (msg) {
      // Asigna los valores al formulario
      $("#fIdNomenclatura2").val(msg[0]["IdNomenclatura"]);
      $("#fCodNomenclatura2").val(msg[0]["CodNomenclatura"]);
      $("#fNombre2").val(msg[0]["Nombre"]);
      $("#fDescripcion2").val(msg[0]["Descripcion"]);
      $("#fFechaHoraInicio2").val(msg[0]["FechaHoraInicio"]);
      $("#fFechaHoraFin2").val(msg[0]["FechaHoraFin"]);
      $("#fIdArea2").val(msg[0]["IdArea"]);
      $("#fIdActividad").val(msg[0]["IdActividad"]);
      $("#fEstadoNomenclatura").val(msg[0]["EstadoNomenclatura"]);
      // Mostrar el modal
      $("#ModalModiNomen").modal("show");
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos!",
        textStatus + " " + errorThrown
      );
    }
  });
}

function listarActvidades(IdArea) {
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorActividad.php?Accion=ListarPorIdAreas",
    data: { IdArea: IdArea },
    dataType: "json", // Asegúrate de que el servidor devuelve un JSON válido
    success: function (msg) {
      if (Array.isArray(msg)) {
        activities.length = 0; // Vacía el array antes de llenarlo
        activities.push(...msg); // Agrega las nuevas actividades
      } else {
        console.error("El servidor no devolvió un array válido.");
      }
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos!",
        textStatus + " " + errorThrown
      );
    }
  });
}
function validar() {
  if (
    $("#fnomenclatura").val() == "" ||
    $("#fnombre").val() == "" ||
    $("#fdescripcion").val() == ""
  ) {
    MensajeError(
      "Campos Incompletos",
      "Por favor diligencie todos los campos en el Formulario"
    );
    return false;
  } else {
    return true;
  }
}
function validar2() {
  if (
    $("#fnomenclatura2").val() == "" ||
    $("#fnombre2").val() == "" ||
    $("#fdescripcion2").val() == "" ||
    $("#fFechaHoraInicio2").val() == "" ||
    $("#fFechaHoraFin2").val() == "" ||
    $("#fIdActividad").val() == ""
  ) {
    MensajeError(
      "Campos Incompletos",
      "Por favor diligencie todos los campos en el Formulario"
    );
    return false;
  } else {
    return true;
  }
}
function Formulario(id, nombreArea) {
  // Redirige a la página del formulario
  window.location.href =
    "FormularioTurnos.php?id=" +
    id +
    "&nombreArea=" +
    encodeURIComponent(nombreArea);
  // Espera un momento para que la redirección ocurra
  setTimeout(function () {
    // Llama a la función `actualizar` después de la redirección
    // Asegúrate de que el formulario haya cargado completamente
    // Este paso es opcional, dependiendo de cómo cargues los datos
    actualizar(id);
  }, 1000); // Ajusta el tiempo de espera si es necesario
}
function actualizar(id) {
  limpiar();
  $("#Accion").val("Actualizar");
  $("#IdNomenclatura").val(id);
  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorArea.php?accion=ListarPorId",
    data: { IdRem: id },
    dataType: "json",
    success: function (msg) {
      var datos = msg[0];

      if (msg.error) {
        alert(msg.error);
        return;
      }
      // Actualiza el título con el nombre del área
      $("h1").text(datos["NombreAre"]);
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos1!",
        textStatus + " " + errorThrown
      );
    }
  });
}
function eliminar($id) {
  Swal.fire({
    title:
      "¿Está seguro que desea eliminar la nomenclatura " + $id + " Registro?",
    text: "No podrá recuperar los datos!",
    type: "warning",
    showCloseButton: true,
    showCancelButton: true,
    confirmButtonColor: "#058876",
    cancelButtonColor: "#550101",
    confirmButtonText: "Sí, eliminarlo!",
    cancelButtonText: "Cancelar",
    customClass: {
      popup: "swal2-dark"
    }
  }).then((choice) => {
    if (choice.value === true) {
      var Nomenclatura = {
        IdNomenclatura: $.trim($id),
        CodNomenclatura: $.trim($("#fCodNomenclatura").val()),
        Nombre: $.trim($("#fNombre").val()),
        Descripcion: $.trim($("#fDescripcion").val()),
        FechaHoraInicio: $.trim($("#fFechaHoraInicio").val()),
        FechaHoraFin: $.trim($("#fFechaHoraFin").val()),
        IdArea: $.trim($("#fIdArea").val())
      };
      CRUD("Eliminar", Nomenclatura);
    }
  });
}
function AccionNom() {
  accion = $("#Accion").val();
  var Nomenclatura = {
    IdNomenclatura: $.trim($("#fIdNomenclatura2").val()),
    CodNomenclatura: $.trim($("#fCodNomenclatura2").val()),
    Nombre: $.trim($("#fNombre2").val()),
    Descripcion: $.trim($("#fDescripcion2").val()),
    FechaHoraInicio: $.trim($("#fFechaHoraInicio2").val()),
    FechaHoraFin: $.trim($("#fFechaHoraFin2").val()),
    IdArea: $.trim($("#fIdArea2").val()),
    IdActividad: $.trim($("#fIdActividad").val()),
    EstadoNomenclatura: $.trim($("#fEstadoNomenclatura").val())
  };
  if (validar2()) {
    if (accion == "Actualizar") {
      CRUD(accion, Nomenclatura);
    }
  }
}
function Accion() {
  const accion = $("#Accion").val();
  const nomenclatura = $.trim($("#fCodNomenclatura").val());
  const nombre = $.trim($("#fNombre").val());
  const descripcion = $.trim($("#fDescripcion").val());
  const idArea = $.trim($("#fIdArea").val());
  const selectedRanges = getSelectedRanges(); // Obtener los rangos seleccionados con IdActividad

  // Validar campos antes de proceder
  if (!nomenclatura || !nombre || !descripcion || !idArea) {
    alert("Por favor, complete todos los campos obligatorios.");
    return;
  }

  if (selectedRanges.length === 0) {
    alert("Debe seleccionar al menos un rango de horas.");
    return;
  }

  // Validar que cada rango tenga una hora de inicio, fin y una actividad seleccionada
  for (let i = 0; i < selectedRanges.length; i++) {
    const range = selectedRanges[i];
    if (!range.start || !range.end || !range.IdActividad) {
      alert(
        "Por favor, seleccione un rango de hora válido y una actividad para todos los turnos."
      );
      return;
    }
  }

  if (!validar()) {
    return;
  }

  if (accion === "Ingresar") {
    // Verifica duplicados antes de proceder
    $.ajax({
      type: "POST",
      url: "../Controlador/ControladorNomenclatura.php?Accion=ListarPorCodNomenclatura",
      data: { CodNomenclatura: nomenclatura, IdArea: idArea },
      success: function (msg) {
        if (JSON.stringify(msg) === "[]") {
          // Procesa cada rango por separado
          selectedRanges.forEach((range) => {
            const turnoData = {
              IdNomenclatura: 0, // Generado por el servidor
              CodNomenclatura: nomenclatura,
              Nombre: nombre,
              Descripcion: descripcion,
              FechaHoraInicio: formatTime(range.start),
              FechaHoraFin: formatTime(range.end),
              IdArea: idArea,
              IdActividad: range.IdActividad,
              EstadoNomenclatura: 1 // Incluir el IdActividad seleccionado
            };
            // Ver los datos de cada rango antes de enviarlo

            CRUD(accion, turnoData);
          });

          $("#ModalIngresoNomenclatura").modal("hide");
        } else {
          MensajeError(
            "Dato Duplicado al Insertar",
            "Turno Registrado con Anterioridad!"
          );
        }
      },
      error: function (req, textStatus, errorThrown) {
        MensajeError(
          "Oops...",
          "Error al obtener Datos2!",
          `${textStatus} ${errorThrown}`
        );
      }
    });
  }
}
function getSelectedRanges() {
  const ranges = [];
  $("#selectedRanges li").each(function () {
    const text = $(this).text(); // Ejemplo: "04:00 - 10:00"
    const [start, end] = text.split(" - ").map((time) => time.trim());

    const selectActivity = $(this).find("select");
    const IdActividad = selectActivity.val();

    ranges.push({ start, end, IdActividad });
  });

  return ranges;
}
function formatTime(time) {
  if (!time) return "";

  const timeParts = time.split(":"); // Divide en [HH, MM]

  // Si solo hay HH:MM, añadimos :00 al final
  if (timeParts.length === 2) {
    timeParts.push("00"); // Añade los segundos
  }

  return timeParts.join(":"); // Retorna HH:MM:SS
}
function CRUD(accion, objEvento) {
  $.ajax({
    type: "POST",
    url: `../Controlador/ControladorNomenclatura.php?Accion=${accion}`,
    data: objEvento,
    success: function (msg) {
      if (msg) {
        Swal.fire({
          title: "¡Éxito!",
          text: "Turno registrado con éxito.",
          icon: "success",
          customClass: { popup: "swal2-dark" }
        });
        $("#ModalModiNomen").modal("hide");
        $("#ModalIngresoNomenclatura").modal("hide");
        cargarnomenclaturas();
      }
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error en la Transacción!",
        `${textStatus} ${errorThrown}`
      );
    }
  });
}

$(document).ready(function () {
  // Asignar la función al botón de limpiar formulario
  $("#limpiarFormulario").click(function () {
    limpiarFormularioNom();
  });

  const timeBar = $("#timeBar");
  const timeTicks = $("#timeTicks");
  const hoverMarker = $("<div>")
    .attr("id", "hoverMarker")
    .css({
      position: "absolute",
      padding: "2px 5px",
      backgroundColor: "#20b571",
      color: "#fff",
      borderRadius: "3px",
      fontSize: "12px",
      display: "none",
      pointerEvents: "none",
      transform: "translate(-50%, -100%)" // Centrado respecto al puntero
    })
    .appendTo(timeBar);

  let timeRanges = []; // Almacena todos los rangos definidos
  let tempStart = null;
  let tempColor = null; // Almacena el color temporal para el rango actual
  let startLine = null; // Almacena la línea de inicio temporal

  // Lista de colores para los rangos
  const colors = [
    "#20b571",
    "#ff5733",
    "#33a1ff",
    "#ff33a1",
    "#a133ff",
    "#33ffa1"
  ];
  let colorIndex = 0; // Índice para recorrer la lista de colores

  function limpiarFormularioNom() {
    // Limpiar la lista de rangos seleccionados
    $("#selectedRanges").empty();

    // Limpiar la barra de tiempo, excepto el marcador flotante
    timeBar.children().not("#hoverMarker").remove(); // Elimina todo excepto el marcador flotante
    generateTicks(); // Regenerar las marcas de tiempo

    // Limpiar el texto de la hora seleccionada
    $("#selectedTime").text("");

    // Reiniciar la variable de rangos de tiempo
    timeRanges = [];
    tempStart = null;
    tempColor = null;
    startLine = null;
    colorIndex = 0; // Reiniciar el índice de colores
  }

  function generateTicks() {
    for (let i = 0; i <= 48; i++) {
      // 48 intervalos de 30 minutos en 24 horas
      const hour = Math.floor(i / 2); // Obtener la hora
      const minute = (i % 2) * 30; // Obtener los minutos (0 o 30)
      const time = `${pad(hour)}:${pad(minute)}`; // Formatear la hora

      const tick = $("<div>")
        .text(time)
        .addClass("tick")
        .css({
          left: `${(i / 48) * 100}%` // 48 intervalos de 30 minutos
        });

      // Añadir una clase para diferenciar las marcas de hora completa
      if (minute === 0) {
        tick.addClass("hour-tick");
      } else {
        tick.addClass("half-hour-tick");
      }

      timeTicks.append(tick);
    }
  }

  generateTicks();

  // Mostrar marcador flotante (cada 30 minutos)
  timeBar.on("mousemove", function (e) {
    const barOffset = timeBar.offset(); // Posición del timeBar en la página
    const offsetX = e.pageX - barOffset.left; // Posición relativa del mouse
    const barWidth = timeBar.width();
    const percent = offsetX / barWidth;
    const interval = Math.floor(percent * 48); // 48 intervalos de 30 minutos
    const hour = Math.floor(interval / 2); // Obtener la hora
    const minute = (interval % 2) * 30; // Obtener los minutos (0 o 30)
    hoverMarker.text(`${pad(hour)}:${pad(minute)}`);
    hoverMarker.css({
      left: `${percent * 100}%`,
      display: "block"
    });
  });

  timeBar.on("mouseleave", function () {
    hoverMarker.hide();
  });

  // Agregar un nuevo rango al hacer clic (cada 30 minutos)
  timeBar.on("click", function (e) {
    const barWidth = timeBar.width();
    const offsetX = e.offsetX;
    const percent = offsetX / barWidth;
    const interval = Math.floor(percent * 48); // 48 intervalos de 30 minutos
    const hour = Math.floor(interval / 2); // Obtener la hora
    const minute = (interval % 2) * 30; // Obtener los minutos (0 o 30)
    const time = hour + minute / 60; // Convertir a formato decimal para cálculos

    if (tempStart === null) {
      // Seleccionar la hora de inicio
      tempStart = time;
      tempColor = colors[colorIndex % colors.length]; // Asignar un color único
      colorIndex++; // Incrementar el índice de colores

      // Dibujar la línea de inicio
      startLine = $("<div>")
        .css({
          position: "absolute",
          left: `${percent * 100}%`,
          width: "2px",
          height: "100%",
          backgroundColor: tempColor,
          pointerEvents: "none"
        })
        .appendTo(timeBar);

      $("#selectedTime").text(
        `Hora inicio: ${pad(hour)}:${pad(minute)} | Hora fin: --:--`
      );
    } else {
      // Seleccionar la hora de fin
      const tempEnd = time;
      if (isValidRange(tempStart, tempEnd)) {
        addRange(tempStart, tempEnd, tempColor);
        tempStart = null;
        tempColor = null;
        startLine.remove(); // Eliminar la línea de inicio temporal
        $("#selectedTime").text("Rango añadido correctamente.");
      } else {
        tempStart = null;
        tempColor = null;
        startLine.remove(); // Eliminar la línea de inicio temporal
      }
    }
  });

  // Validar si un rango es válido (sin cruces, considerando turnos nocturnos)
  function isValidRange(start, end) {
    return !timeRanges.some((range) => {
      if (range.end < range.start) {
        // El rango existente cruza la medianoche
        if (end < start) {
          // El rango nuevo también cruza la medianoche
          return !(end <= range.start && start >= range.end);
        } else {
          // El rango nuevo no cruza la medianoche
          return start < range.end || end > range.start;
        }
      } else {
        // El rango existente no cruza la medianoche
        if (end < start) {
          // El rango nuevo cruza la medianoche
          return start < range.end || end > range.start;
        } else {
          // Ambos rangos no cruzan la medianoche
          return !(end <= range.start || start >= range.end);
        }
      }
    });
  }

  // Agregar un rango y mostrarlo
  function addRange(start, end, color) {
    // Manejar turnos cruzados (de medianoche)
    if (end < start) {
      // Primera parte del rango
      timeRanges.push({ start, end: 24, color });
      renderRange(start, 24, color);

      // Segunda parte del rango
      timeRanges.push({ start: 0, end, color });
      renderRange(0, end, color);
    } else {
      timeRanges.push({ start, end, color });
      renderRange(start, end, color);
    }

    // Listar el rango en el modal
    listRange(start, end, color);
  }

  // Renderizar un rango en la barra (cada 30 minutos)
  function renderRange(start, end, color) {
    const startInterval = start * 2; // Convertir a intervalos de 30 minutos
    const endInterval = end * 2; // Convertir a intervalos de 30 minutos
    const rangeDiv = $("<div>")
      .css({
        position: "absolute",
        left: `${(startInterval / 48) * 100}%`, // 48 intervalos de 30 minutos
        width: `${((endInterval - startInterval) / 48) * 100}%`,
        height: "100%",
        backgroundColor: color, // Usar el color asignado
        opacity: 0.5, // Hacer el color semi-transparente
        border: `1px solid ${color}`,
        pointerEvents: "none" // Ignorar eventos de mouse
      })
      .appendTo(timeBar);
  }

  // Listar el rango seleccionado en la lista del modal
  function listRange(start, end, color) {
    const startHour = Math.floor(start);
    const startMinute = (start % 1) * 60;
    const endHour = Math.floor(end);
    const endMinute = (end % 1) * 60;
    const rangeText = `${pad(startHour)}:${pad(startMinute)} - ${pad(
      endHour
    )}:${pad(endMinute)}`;

    // Crear un contenedor para el rango y la lista desplegable
    const listItem = $("<li>").css({
      margin: "5px 0",
      fontSize: "14px",
      color: "#e9ecef",
      display: "flex",
      alignItems: "center"
    });

    // Añadir el texto del rango
    listItem.append($("<span>").text(rangeText).css({ marginRight: "10px" }));

    // Crear la lista desplegable de actividades
    const selectActivity = $("<select>").css({
      padding: "5px",
      borderRadius: "5px",
      border: "1px solid #ced4da",
      backgroundColor: "#fff",
      color: "#000"
    });

    // Añadir opciones de actividades (puedes personalizar estas opciones)
    activities.forEach((activity) => {
      selectActivity.append(
        $("<option>").text(activity.NombreActividad).val(activity.IdActividad)
      );
    });

    // Añadir opciones de actividades (puedes personalizar estas opciones)
    selectActivity.addClass("custom-select");
    selectActivity.find("option").css({
      color: "#e0e0e0", // Color azul para las opciones
      fontWeight: "bold"
    });

    // Añadir la lista desplegable al contenedor
    listItem.append(selectActivity);

    // Añadir el contenedor a la lista de rangos seleccionados
    $("#selectedRanges").append(listItem);
  }

  // Añadir ceros a números pequeños
  function pad(value) {
    return value < 10 ? "0" + value : value;
  }
});

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////EXTRAS//////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function ingresarExtra() {
    limpiarExtra();
    $('#modalExtrasLabel').html("Ver Horas Extras");
    $("#modalExtras").modal('show');
}

function CrearExtra() {
    limpiarExtra();
    $("#AccionExtra").val("Ingresar");
    $('#exampleModalLabelExtra').html("Crear Hora Extra");

    // Cargar médicos en el select
    cargarMedicos3(function() {
        $("#ModalIngresoExtra").modal('show');
    });
}

function validarExtra() {
    // Validación de campos vacíos
    if (
        $('#fIdMedicose').val() == '' ||
        $('#fFechaInicioExtra').val() == '' ||
        $('#fFechaFinExtra').val() == '' ||
        $('#fEstadoExtra').val() == ''
    ) {
        mostrarAlerta('empty_fields');
        return false;
    }
    
    // Validación de fechas
    const fechaInicio = new Date($('#fFechaInicioExtra').val());
    const fechaFin = new Date($('#fFechaFinExtra').val());
    
    if (isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) {
        mostrarAlerta('invalid_date_format');
        return false;
    }
    
    if (fechaFin < fechaInicio) {
        mostrarAlerta('end_date_before_start', {fechaInicio, fechaFin});
        return false;
    }
    
    return true;
}

function mostrarAlerta(tipo, datos = {}) {
    const configBase = {
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Entendido',
        customClass: {
            popup: 'swal2-dark'
        }
    };
    
    switch(tipo) {
        case 'empty_fields':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Campos Incompletos',
                text: 'Por favor diligencie todos los campos en el Formulario'
            });
            break;
            
        case 'invalid_date_format':
            Swal.fire({
                ...configBase,
                icon: 'warning',
                title: 'Formato de fecha inválido',
                text: 'Por favor ingrese fechas válidas en el formato correcto'
            });
            break;
            
        case 'end_date_before_start':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Error en fechas',
                html: 'La fecha de fin <strong>no puede ser menor</strong> que la fecha de inicio<br><br>' +
                      `<strong>Fecha inicio:</strong> ${datos.fechaInicio.toLocaleDateString()}<br>` +
                      `<strong>Fecha fin:</strong> ${datos.fechaFin.toLocaleDateString()}`
            });
            break;
            
        default:
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error inesperado'
            });
    }
}

function limpiarExtra() {
    document.getElementById('fIdExtra').value = '0';
    document.getElementById('fIdMedicose').value = '';
    document.getElementById('fFechaInicioExtra').value = '';
    document.getElementById('fFechaFinExtra').value = '';
    document.getElementById('fObsExtra').value = '';
    document.getElementById('fEstadoExtra').value = '1';
    
    // Limpiar el contenedor de observación de rechazo
    $('#obsRechazoContainer').remove();
}

function cargarMedicos3(callback) {
    $.ajax({
        url: '../Controlador/ControladorMedico.php?Accion=listarPorArea&idArea=' + idAreaUrl,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#fIdMedicose').empty().append('<option value="">Seleccione un médico</option>');
            
            $.each(data, function(index, medico) {
                $('#fIdMedicose').append(
                    $('<option></option>').val(medico.IdMedico).text(medico.NombreMedico)
                );
            });
            
            // Inicializar Select2 después de cargar las opciones
            $('#fIdMedicose').select2();
            
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function() {
            $('#fIdMedicose').empty().append('<option value="">Error al cargar médicos</option>');
            $('#fIdMedicose').select2();
        }
    });
}

function ingresarHorasExtras() {
    if ($.fn.DataTable.isDataTable('#tablaHorasExtras')) {
        table = $('#tablaHorasExtras').DataTable();
        table.ajax.reload();
    } else {
        table = $('#tablaHorasExtras').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "../Controlador/ControladorExtra.php?Accion=ListarPorIdArea",
                "type": "POST",
                "data": function(d) {
                    d.IdArea = idAreaUrl;
                    d.month = mesSeleccionado;
                    d.year = yearSeleccionado;
                },
                "dataSrc": function(json) {
                    console.log(json);
                    if (!json || !json.data) {
                        console.error("La respuesta no contiene 'data'.");
                        return [];
                    }
                    return json.data;
                },
                "error": function(xhr, status, error) {
                    console.error("Error en la solicitud:", status, error);
                    console.log("Respuesta completa:", xhr.responseText);
                }
            },
            "columns": [
                { "data": "NombreMedico" },
                { "data": "FechaInicioExtra" },
                { "data": "FechaFinExtra" },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        let inicio = new Date(row.FechaInicioExtra);
                        let fin = new Date(row.FechaFinExtra);
                        
                        if (isNaN(inicio) || isNaN(fin)) {
                            return "Fechas inválidas";
                        }

                        let diferenciaMs = fin - inicio;
                        let horas = diferenciaMs / (1000 * 60 * 60);
                        
                        return horas.toFixed(2) + " horas";
                    }
                },
                { "data": "ObsExtra" },
                // NUEVA COLUMNA: Observación de Rechazo
                { 
                    "data": "ObsRechazoExtra",
                    "render": function(data, type, row) {
                        if (row.EstadoExtra == '2' && data) {
                            return `<span class="badge bg-danger" title="${data}">Ver Rechazo</span>`;
                        }
                        return '';
                    }
                },
                { 
                    "data": "EstadoExtra",
                    "render": function(data, type, row) {
                        let imagen = "";
                        let tooltip = "";
                        
                        let estado = String(data).toLowerCase();
                        switch(estado) {
                            case "0":
                                imagen = "pendiente.png";
                                tooltip = "Pendiente de aprobación";
                                break;
                            case "1":
                                imagen = "activo.png";
                                tooltip = "Confirmada";
                                break;
                            case "2":
                                imagen = "inactivo.png";
                                tooltip = "Rechazada" + (row.ObsRechazoExtra ? `: ${row.ObsRechazoExtra}` : '');
                                break;
                            case "3":
                                imagen = "liquidado.png";
                                tooltip = "Liquidada";
                                break;
                            default:
                                imagen = "desconocido.png";
                                tooltip = "Estado desconocido";
                        }
                        
                        return `<img src="../Assets/ICON/${imagen}" alt="${data}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    }
                },
                { "data": "FechaLiquidacion" },
                { 
                    "data": "IdExtra",
                    "render": function(data, type, row) {
                        // Obtener los permisos de la variable ExtraRol
                        let permisos = ExtraRol || "";
                        
                        // Verificar qué permisos tiene el usuario
                        let tienePermisoUpdate = permisos.includes("U");
                        let tienePermisoDelete = permisos.includes("D");
                        
                        // Crear los botones según los permisos
                        let botonUpdate = '';
                        let botonDelete = '';
                        
                        if (tienePermisoUpdate) {
                            botonUpdate = `
                                <button class="update_btn" data-id="${data}">
                                    <span class="material-icons">update</span>
                                </button>
                            `;
                        }
                        
                        if (tienePermisoDelete) {
                            botonDelete = `
                                <button class="delete_btn" data-id="${data}">
                                    <span class="material-icons">delete</span>
                                </button>
                            `;
                        }
                        
                        return botonUpdate + botonDelete;
                    },
                    "orderable": false,
                    "searchable": false
                }
            ],
            "language": {
                "decimal": ",",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });
        
        // Manejar eventos de los botones
        $('#tablaHorasExtras').on('click', '.update_btn', function() {
            var id = $(this).data('id');
            actualizarExtra(id);
        });

        $('#tablaHorasExtras').on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            eliminarExtra(id);
        });

        // NUEVO: Evento para mostrar observación de rechazo al hacer clic en el badge
        $('#tablaHorasExtras').on('click', '.badge.bg-danger', function() {
            const obsRechazo = $(this).attr('title');
            Swal.fire({
                title: 'Observación de Rechazo',
                html: `<div style="text-align: left; background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">${obsRechazo}</div>`,
                icon: 'info',
                confirmButtonText: 'Cerrar',
                customClass: {
                    popup: 'swal2-dark'
                }
            });
        });
    }
    return table;
}


function actualizarExtra(id) {
    limpiarExtra();
    $('#modalExtrasLabel').html("Editar Hora Extra");
    $("#AccionExtra").val("Actualizar");
    $('#fIdExtra').val(id);

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorExtra.php?Accion=ListarPorId',
        data: { IdExtra: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                MensajeError("Error", "No se encontraron datos para editar");
                return;
            }

            const datos = response[0];
            
            // VERIFICACIÓN DEL ESTADO Y ROL
            if (datos.EstadoExtra == '3') {
                // Si el estado es 3 (liquidado), verificar el rol del usuario
                if (IdRol !== '1' && IdRol !== '7') {
                    // Mostrar alerta y no permitir la edición
                    Swal.fire({
                        icon: 'warning',
                        title: 'Registro Liquidado',
                        text: 'No se puede actualizar una hora extra después de haber sido liquidada.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    });
                    return; // Salir de la función sin abrir el modal
                } else {
                    // Si es super administrador (rol 1 o 7), mostrar advertencia pero permitir editar
                    Swal.fire({
                        icon: 'info',
                        title: 'Registro Liquidado - Modo Super Admin',
                        text: 'Está editando un registro liquidado. Esta acción solo está permitida para super administradores.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Continuar',
                        customClass: {
                            popup: 'swal2-dark'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Continuar con la carga del formulario
                            cargarDatosEnFormulario(datos);
                        }
                    });
                    return;
                }
            }
            
            // Si no está liquidado, cargar normalmente
            cargarDatosEnFormulario(datos);

        },
        error: function(xhr, textStatus, errorThrown) {
            MensajeError("Error", "Error al obtener datos: " + textStatus);
        }
    });
}

// Función auxiliar para cargar datos en el formulario (actualizada)
function cargarDatosEnFormulario(datos) {
    $('#fIdExtra').val(datos.IdExtra);
    $('#fFechaInicioExtra').val(datos.FechaInicioExtra);
    $('#fFechaFinExtra').val(datos.FechaFinExtra);
    $('#fObsExtra').val(datos.ObsExtra);
    $('#fEstadoExtra').val(datos.EstadoExtra);

    // Mostrar observación de rechazo si existe y el estado es rechazado
    if (datos.EstadoExtra == '2' && datos.ObsRechazoExtra) {
        // Crear o mostrar elemento para la observación de rechazo
        let obsRechazoElement = $('#obsRechazoContainer');
        if (obsRechazoElement.length === 0) {
            // Crear el elemento si no existe
            $('<div id="obsRechazoContainer" class="form-group"><label>Observación de Rechazo:</label><div class="form-control" style="background-color: #f8d7da; color: #721c24;">' + datos.ObsRechazoExtra + '</div></div>')
                .insertBefore('#ModalIngresoExtra .modal-footer');
        } else {
            // Actualizar el elemento si existe
            obsRechazoElement.find('.form-control').text(datos.ObsRechazoExtra);
        }
    } else {
        // Ocultar el elemento si no hay observación de rechazo
        $('#obsRechazoContainer').remove();
    }

    // Cargar médicos primero
    cargarMedicos3(function() {
        // Seleccionar el médico (esto disparará el evento change)
        $('#fIdMedicose').val(datos.IdMedico).trigger('change.select2');
        $("#ModalIngresoExtra").modal('show');
    });
}

function eliminarExtra(id) {
    Swal.fire({
        title: '¿Está seguro que desea eliminar la Hora Extra ' + id + '?',
        text: "No podrá recuperar los datos!",
        type: 'warning',
        showCloseButton: true,
        showCancelButton: true,
        confirmButtonColor: '#058876',
        cancelButtonColor: '#550101',
        confirmButtonText: 'Sí, eliminarlo!',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'swal2-dark'
        }
    }).then((choice) => {
        if (choice.value === true) {
            var Extra = {
                IdExtra: $.trim(id)
            };
            CRUDExtra('Eliminar', Extra);
        }
    });
}

function AccionExtra() {
    let accion = $("#AccionExtra").val();
    
    // Determinar FechaLiquidacion según el estado
    let estadoExtra = $.trim($("#fEstadoExtra").val());
    let fechaLiquidacion = null;
    let obsRechazoExtra = null; // Nueva variable para observación de rechazo
    
    // Si el estado es "Rechazado" (2), solicitar observación
    if (estadoExtra === '2') {
        // Mostrar modal para ingresar observación de rechazo
        $("#ModalIngresoExtra").modal('hide');
        
        Swal.fire({
            title: 'Observación de Rechazo',
            input: 'textarea',
            inputLabel: 'Por favor ingrese la razón del rechazo:',
            inputPlaceholder: 'Describa por qué se rechazan estas horas extras...',
            inputAttributes: {
                'aria-label': 'Describa por qué se rechazan estas horas extras'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar Rechazo',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal2-dark'
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe ingresar una observación para el rechazo';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                obsRechazoExtra = result.value;
                // Continuar con el proceso después de obtener la observación
                continuarProcesoExtra(accion, estadoExtra, fechaLiquidacion, obsRechazoExtra);
            } else {
                // Si cancela, volver a mostrar el modal
                $("#ModalIngresoExtra").modal('show');
            }
        });
        
        return; // Salir de la función aquí, continuará en el callback
    }
    
    // Si no es rechazo, continuar normalmente
    continuarProcesoExtra(accion, estadoExtra, fechaLiquidacion, obsRechazoExtra);
}

// Función auxiliar para continuar el proceso después de manejar el rechazo
function continuarProcesoExtra(accion, estadoExtra, fechaLiquidacion, obsRechazoExtra) {
    // Si el estado es "Liquidado" (3), asignar fecha actual
    if (estadoExtra === '3') {
        fechaLiquidacion = obtenerFechaColombia();
    }
    
    var Extra = {
        IdExtra: $.trim($("#fIdExtra").val()),
        Idmedico: $.trim($("#fIdMedicose").val()),
        FechaInicioExtra: $.trim($("#fFechaInicioExtra").val()),
        FechaFinExtra: $.trim($("#fFechaFinExtra").val()),
        ObsExtra: $.trim($("#fObsExtra").val()),
        EstadoExtra: estadoExtra,
        FechaLiquidacion: fechaLiquidacion,
        ObsRechazoExtra: obsRechazoExtra  // Nuevo campo para observación de rechazo
    };

    if (validarExtra()) {
        CRUDExtra(accion, Extra);
    } else {
        // Si la validación falla, volver a mostrar el modal
        $("#ModalIngresoExtra").modal('show');
    }
}

// Función para obtener la fecha actual de Colombia
function obtenerFechaColombia() {
    // Crear fecha en UTC
    const ahora = new Date();
    
    // Colombia está en UTC-5
    const offsetColombia = -5 * 60; // en minutos
    const fechaColombia = new Date(ahora.getTime() + offsetColombia * 60 * 1000);
    
    // Formatear a YYYY-MM-DD HH:MM:SS
    const año = fechaColombia.getUTCFullYear();
    const mes = String(fechaColombia.getUTCMonth() + 1).padStart(2, '0');
    const dia = String(fechaColombia.getUTCDate()).padStart(2, '0');
    const horas = String(fechaColombia.getUTCHours()).padStart(2, '0');
    const minutos = String(fechaColombia.getUTCMinutes()).padStart(2, '0');
    const segundos = String(fechaColombia.getUTCSeconds()).padStart(2, '0');
    
    return `${año}-${mes}-${dia} ${horas}:${minutos}:${segundos}`;
}

function validarExtra() {
    // Validación de campos vacíos
    if (
        $('#fIdMedicose').val() == '' ||
        $('#fFechaInicioExtra').val() == '' ||
        $('#fFechaFinExtra').val() == '' ||
        $('#fEstadoExtra').val() == ''
    ) {
        mostrarAlerta('empty_fields');
        return false;
    }
    
    // Validación de fechas
    const fechaInicio = new Date($('#fFechaInicioExtra').val());
    const fechaFin = new Date($('#fFechaFinExtra').val());
    
    if (isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) {
        mostrarAlerta('invalid_date_format');
        return false;
    }
    
    if (fechaFin < fechaInicio) {
        mostrarAlerta('end_date_before_start', {fechaInicio, fechaFin});
        return false;
    }
    
    return true;
}

function CRUDExtra(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorExtra.php?Accion=' + accion,
        data: objEvento,
        success: function (msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Transacción realizada con éxito!",
                    icon: "success",
                    customClass: {
                        popup: 'swal2-dark'
                    }
                }).then(() => {
                    // En lugar de recargar la página, actualizamos solo la tabla
                    if ($.fn.DataTable.isDataTable('#tablaHorasExtras')) {
                        table.ajax.reload(null, false); // false para mantener la paginación
                    }
                    
                    // Cerrar el modal si está abierto
                    $('#ModalIngresoExtra').modal('hide');
                });
            }
        },
        error: function (req, textStatus, errorThrown) {
            MensajeError("Oops...", "Error en la Transacción!", textStatus + ' ' + errorThrown)
        }
    });
}

$(document).ready(function() {
    // Inicializar la tabla por primera vez
    table = ingresarHorasExtras();

    $('.select2-medico').select2();

    // Evento para abrir el modal
    $('#abrirModalHorasExtras').click(function() {
        table.ajax.reload();
        $('#modalExtras').modal('show');
    });
    
    // Eventos para los botones de acción
    $('#tablaHorasExtras tbody').on('click', '.editar-hora', function() {
        var id = $(this).data('id');
        // Implementa la lógica para editar
        console.log('Editar registro:', id);
    });

    $('#tablaHorasExtras tbody').on('click', '.eliminar-hora', function() {
        var id = $(this).data('id');
        if (confirm('¿Está seguro de eliminar este registro?')) {
            $.ajax({
                url: 'endpoint_api.php?action=eliminar_hora',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    table.ajax.reload();
                    alert('Registro eliminado correctamente');
                }
            });
        }
    });
    
        // Validación en tiempo real de fechas
    $('#fFechaInicioExtra, #fFechaFinExtra').change(function() {
        const fechaInicio = new Date($('#fFechaInicioExtra').val());
        const fechaFin = new Date($('#fFechaFinExtra').val());
        
        if (!isNaN(fechaInicio.getTime()) && !isNaN(fechaFin.getTime()) && fechaFin < fechaInicio) {
            $('#fFechaFinExtra').addClass('is-invalid');
            $('#fechaFinError').remove();
            $('#fFechaFinExtra').after('<div id="fechaFinError" class="invalid-feedback">La fecha de fin no puede ser menor que la de inicio</div>');
        } else {
            $('#fFechaFinExtra').removeClass('is-invalid');
            $('#fechaFinError').remove();
        }
    });
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////EXCEL//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$(document).ready(function () {
  // Enviar el formulario
  $("#uploadForm").on("submit", function (e) {
    e.preventDefault(); // Evitar recarga de página

    // Validación del mes seleccionado
    const selectedMonth = parseInt($("#month").val());
    const selectedYear = parseInt($("#year").val());
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1; // Los meses en JS van de 0-11
    const currentYear = currentDate.getFullYear();
    const currentDay = currentDate.getDate();

    // Verificar si el mes seleccionado es anterior al actual
    if (
      tipoRol !== "Administrador" &&
      (selectedYear < currentYear ||
        (selectedYear === currentYear && selectedMonth < currentMonth) ||
        (selectedYear === currentYear &&
          selectedMonth === currentMonth &&
          currentDay > 3))
    ) {
      Swal.fire({
        icon: "error",
        title: "¡Error!",
        text: "No puedes cargar turnos para meses anteriores al actual. Por favor, selecciona un mes igual o posterior al actual.",
        customClass: {
          popup: "swal2-dark"
        }
      });
      return false; // Detener el envío del formulario
    }

    var formData = new FormData(this);
    var submitButton = $('button[type="submit"]'); // Referencia al botón
    // Deshabilitar el botón antes de enviar
    submitButton.prop("disabled", true);
    submitButton.text("Procesando..."); // Cambiar texto mientras se procesa

    // Primero validar el área del archivo Excel
    $.ajax({
      url: "../Modelo/validar_area_excel.php", // URL para validar el área
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        try {
          if (typeof response !== "object") {
            response = JSON.parse(response); // Asegurarse de que sea un objeto
          }
          if (response.success) {
            // Si el área es válida, proceder con la verificación de turnos existentes
            verificarTurnosExistentes();
          } else {
            // Si el área no es válida, mostrar alerta
            Swal.fire({
              icon: "error",
              title: "¡Error!",
              text:
                response.message ||
                "El archivo no corresponde al área especificada",
              customClass: {
                popup: "swal2-dark"
              }
            });
            // Restaurar el estado del botón
            submitButton.prop("disabled", false);
            submitButton.text("Cargar Turnos");
          }
        } catch (e) {
          Swal.fire({
            icon: "error",
            title: "¡Error!",
            text: "Respuesta inesperada del servidor",
            customClass: {
              popup: "swal2-dark"
            }
          });
          submitButton.prop("disabled", false);
          submitButton.text("Cargar Turnos");
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "¡Error!",
          text: "Hubo un error al validar el área del archivo",
          customClass: {
            popup: "swal2-dark"
          }
        });
        // Restaurar el estado del botón si ocurre un error
        submitButton.prop("disabled", false);
        submitButton.text("Cargar Turnos");
      }
    });
    const nombreArea = urlParams.get("nombreArea");
    let idAreaUrl = urlParams.get("id");

    // Función para verificar si ya existen turnos cargados en el mes
    function verificarTurnosExistentes() {
      $.ajax({
        url: "../Modelo/validar_excel.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
          try {
            if (typeof response !== "object") {
              response = JSON.parse(response); // Asegurarse de que sea un objeto
            }
            if (response.exists) {
              Swal.fire({
                icon: "warning",
                title: "¡Atención!",
                text: "Ya hay turnos cargados para este mes. ¿Deseas sobrescribirlos?",
                showCancelButton: true,
                confirmButtonText: "Sí, sobrescribir",
                cancelButtonText: "No, cancelar",
                customClass: {
                  popup: "swal2-dark"
                }
              }).then((result) => {
                if (result.isConfirmed) {
                  SobrescribirFormulario();
                } else {
                  submitButton.prop("disabled", false);
                  submitButton.text("Cargar Turnos");
                  $("#uploadModal").modal("hide");
                }
              });
            } else {
              enviarFormulario();
            }
          } catch (e) {
            Swal.fire({
              icon: "error",
              title: "¡Error!",
              text: "Respuesta inesperada del servidor",
              customClass: {
                popup: "swal2-dark"
              }
            });
            submitButton.prop("disabled", false);
            submitButton.text("Cargar Turnos");
          }
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "¡Error!",
            text: "Hubo un error al verificar los turnos",
            customClass: {
              popup: "swal2-dark"
            }
          });
          // Restaurar el estado del botón si ocurre un error
          submitButton.prop("disabled", false);
          submitButton.text("Cargar Turnos");
        }
      });
    }

    // Función para enviar el formulario si el usuario acepta sobrescribir los turnos
    function enviarFormulario() {
      $.ajax({
        url: "../Modelo/leer_excel.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
          // Primero mostrar el mensaje de éxito
          Swal.fire({
            icon: "success",
            title: "¡Éxito!",
            text: "Los turnos fueron cargados correctamente",
            customClass: {
              popup: "swal2-dark"
            },
            showConfirmButton: true
          }).then(() => {
            // Después de mostrar el mensaje, crear la notificación
            $.ajax({
              type: "POST",
              url: "../Controlador/ControladorNotificaciones.php?Accion=IngresarAdmin",
              data: {
                TipoNotificaciones: "turno_nuevo",
                MensajeNotificaciones:
                  "Se han cargado nuevos turnos médicos de " +
                  nombreArea +
                  " desde Excel",
                EnlaceNotificaciones:
                  "https://turnos.hrm.gov.co/Vista/Autorizar.php",
                PrioridadNotificaciones: "alta",
                FechaNotificaciones: formatearFecha(new Date())
              },
              success: function (notifResponse) {
                if (notifResponse.success) {
                  console.log("Notificación creada con éxito");
                } else {
                  console.error("Error al crear notificación");
                }
                location.reload(); // Recargar la página
              },
              error: function () {
                console.error("Error en la solicitud de notificación");
                location.reload(); // Recargar de todos modos
              }
            });
          });

          // Restaurar el estado del botón y cerrar el modal
          submitButton.prop("disabled", false);
          submitButton.text("Cargar Turnos");
          $("#uploadModal").modal("hide");
        },
        error: function () {
          Swal.fire({
            icon: "error",
            title: "¡Error!",
            text: "Hubo un error al cargar los turnos",
            customClass: {
              popup: "swal2-dark"
            },
            showConfirmButton: true
          });
          submitButton.prop("disabled", false);
          submitButton.text("Cargar Turnos");
        }
      });
    }

        function SobrescribirFormulario() {
          $.ajax({
            url: "../Modelo/sobrescribir_excel.php",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
        
            success: function (response) {
              submitButton.prop("disabled", false).text("Cargar Turnos");
        
              // ---- CASO ERROR CONTROLADO DESDE PHP ----
              if (!response || response.success !== true) {
                var html = response && response.message ? response.message : "Hubo un error al cargar los turnos";
        
                if (response && response.codigo === "HORAS_EFECTIVAS" && response.detalle && response.detalle.length) {
                  html += "<hr><b>Médicos con horas efectivas registradas:</b><ul style='text-align:left'>";
                  response.detalle.forEach(function (d) {
                    html += "<li>" + d.medico + " (" + d.turnos + " turnos)</li>";
                  });
                  html += "</ul>";
                }
        
                Swal.fire({
                  icon: "warning",
                  title: "No se pudo sobrescribir",
                  html: html,
                  customClass: { popup: "swal2-dark" },
                  width: 600,
                  showConfirmButton: true
                });
                return; // NO recarga, NO cierra modal
              }
        
              // ---- CASO ÉXITO ----
              $("#uploadModal").modal("hide");
              Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: response.message || "Los turnos fueron cargados correctamente",
                customClass: { popup: "swal2-dark" },
                showConfirmButton: true
              }).then(() => {
                $.ajax({
                  type: "POST",
                  url: "../Controlador/ControladorNotificaciones.php?Accion=IngresarAdmin",
                  data: {
                    TipoNotificaciones: "turno_nuevo",
                    MensajeNotificaciones: "Se han cargado nuevos turnos médicos de " + nombreArea + " desde Excel",
                    EnlaceNotificaciones: "https://turnos.hrm.gov.co/Vista/Autorizar.php",
                    PrioridadNotificaciones: "alta",
                    FechaNotificaciones: formatearFecha(new Date())
                  },
                  complete: function () {
                    location.reload();
                  }
                });
              });
            },
        
            error: function (xhr) {
              submitButton.prop("disabled", false).text("Cargar Turnos");
              Swal.fire({
                icon: "error",
                title: "¡Error!",
                text: "Error " + xhr.status + " al procesar el archivo. Revise el log del servidor.",
                customClass: { popup: "swal2-dark" },
                showConfirmButton: true
              });
            }
          });
        }

  });
});

function formatearFecha(fecha) {
  const año = fecha.getFullYear();
  const mes = String(fecha.getMonth() + 1).padStart(2, "0");
  const dia = String(fecha.getDate()).padStart(2, "0");
  const horas = String(fecha.getHours()).padStart(2, "0");
  const minutos = String(fecha.getMinutes()).padStart(2, "0");
  const segundos = String(fecha.getSeconds()).padStart(2, "0");

  return `${año}-${mes}-${dia} ${horas}:${minutos}:${segundos}`;
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////CALENDAR//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
const coloresMedicos = {};
let indiceColor = 0;

let fecIni;
let fecFin;

// Función para actualizar el calendario
function actualizarCalendario() {
  // Obtener la vista actual del calendario
  const currentView = calendar.view;
  const startStr = currentView.activeStart.toISOString().split("T")[0];
  const endStr = currentView.activeEnd.toISOString().split("T")[0];
  // Refetch events para el rango actual
  calendar.refetchEvents();
}

function obtenerPrimerUltimoDiaMes(fecha) {
  const fechaObj = new Date(fecha);
  const primerDiaMes = new Date(fechaObj.getFullYear(), fechaObj.getMonth(), 1);
  const ultimoDiaMes = new Date(
    fechaObj.getFullYear(),
    fechaObj.getMonth() + 1,
    0
  );

  const primerDia = `${primerDiaMes.getFullYear()}-${String(
    primerDiaMes.getMonth() + 1
  ).padStart(2, "0")}-${String(primerDiaMes.getDate()).padStart(2, "0")}`;
  const ultimoDia = `${ultimoDiaMes.getFullYear()}-${String(
    ultimoDiaMes.getMonth() + 1
  ).padStart(2, "0")}-${String(ultimoDiaMes.getDate()).padStart(2, "0")}`;

  return { primerDia, ultimoDia };
}

document.addEventListener("DOMContentLoaded", function () {
  const tipoActividadSelect = document.getElementById("fTipoActividad");
  const campoValorHoraEfectiva = document.getElementById("campoValorHoraEfectiva");

  tipoActividadSelect.addEventListener("change", function () {
    if (this.value === "2") {
      // '2' es el valor de DISPONIBILIDAD
      campoValorHoraEfectiva.style.display = "block";
    } else {
      campoValorHoraEfectiva.style.display = "none";
    }
  });

  const urlParams = new URLSearchParams(window.location.search);
  const idArea = urlParams.get("id");

  var calendarEl = document.getElementById("calendar");
  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    height: 700,
    contentHeight: 1000,
    aspectRatio: 0.5,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay"
    },
    locale: "es",
    timeZone: "America/Bogota", // Zona horaria de Colombia
    buttonText: {
      today: "Hoy",
      month: "Mes",
      week: "Semana",
      day: "Día"
    },
    // initialDate: new Date().toISOString().split('T')[0], // Opcional: establecer fecha inicial
    events: function (fetchInfo, successCallback, failureCallback) {
      // Calcular el mes central del rango visible
      const fechaInicio = new Date(fetchInfo.startStr);
      const fechaFin = new Date(fetchInfo.endStr);
      const fechaCentral = new Date(
        (fechaInicio.getTime() + fechaFin.getTime()) / 2
      );
      const { primerDia, ultimoDia } = obtenerPrimerUltimoDiaMes(fechaCentral);
      mesSeleccionado = fechaCentral.getMonth() + 1; // Mes en formato 1-12
      yearSeleccionado = fechaCentral.getFullYear();
      fecIni = primerDia;
      fecFin = ultimoDia;
      obtenerTurnosPorMes(
        primerDia,
        ultimoDia,
        idArea,
        successCallback,
        failureCallback
      );
    },
    datesSet: function (dateInfo) {
      // Usar dateInfo.view.currentStart para obtener el mes del título
      const mesMostrado = dateInfo.view.currentStart.getMonth() + 1; // Mes del título
      const anioMostrado = dateInfo.view.currentStart.getFullYear();
    },
    eventContent: function (arg) {
      const viewType = arg.view.type;
      const start = arg.event.start;
      const end = arg.event.end;
      const esEventoDeVariosDias =
        end &&
        (end.getDate() !== start.getDate() ||
          end.getMonth() !== start.getMonth());

      if (viewType === "dayGridMonth") {
        if (esEventoDeVariosDias) {
          return {
            html: `<div class="linea" style="background-color: ${arg.event.backgroundColor}; height: 5px; width: 100%; border-radius: 12px;"></div>`
          };
        } else {
          return {
            html: `<div class="punto" style="background-color: ${arg.event.backgroundColor}; width: 10px; height: 10px; border-radius: 50%;"></div>`
          };
        }
      } else {
        return {
          html: `<div>${arg.event.extendedProps.nombreMedico} - ${arg.event.extendedProps.nombreActividad}</div>`
        };
      }
    },
    eventClick: function (info) {
      const fechaSeleccionada = info.jsEvent.target
        .closest(".fc-day")
        .getAttribute("data-date");
      const fechaInicioValue = fechaSeleccionada + "T00:00";
      const fechaFinValue = fechaSeleccionada + "T23:59";
      obtenerTurnosPorDia(fechaInicioValue, fechaFinValue, idArea);
    },
    dateClick: function (info) {
      fechaSeleccionada = info.dateStr;
      const fechaInicioValue = fechaSeleccionada + "T00:00";
      const fechaFinValue = fechaSeleccionada + "T23:59";
      obtenerTurnosPorDia(fechaInicioValue, fechaFinValue, idArea);
    }
  });

  calendar.render();

  // Función para resetear el modal
  function resetModal() {
    // Reseteamos los campos del formulario (ajustar según los campos del formulario)
    document.getElementById("formTurno").reset(); // Si tienes un formulario con el ID 'formularioTurno'
    // Si quieres limpiar campos específicos
    document.getElementById("fNomenclatura").value = ""; // Ejemplo de campo específico
    document.getElementById("fIdMedico").value = ""; // Ejemplo de campo específico
    $("#fIdMedico").val("");
    $("#fIdMedico").val("").trigger("change");
    $("#fIdMedico").prop("disabled", false); // Habilitar el campo select con id 'fIdMedico'
  }

  document
    .getElementById("fNomenclatura")
    .addEventListener("change", function () {
      const nomenclaturaId = this.value;
      const fechaInicio = document.getElementById("fFechaHoraInicio");
      const fechaFin = document.getElementById("fFechaHoraFin");

      // Obtener las horas de inicio y fin según la nomenclatura seleccionada
      obtenerHorasNomenclatura(nomenclaturaId, function (horas) {
        // Verificar que los datos de la nomenclatura sean válidos
        if (!horas || !horas.FechaHoraInicio || !horas.FechaHoraFin) {
          return;
        }

        // Obtener la fecha actual del campo fFechaHoraInicio (ya tiene la fecha seleccionada)
        const fechaSeleccionada = fechaInicio.value.split("T")[0]; // Extrae la parte de la fecha (yyyy-MM-dd)

        // Concatenar la fecha seleccionada con la hora de inicio y fin
        const fechaInicioValue =
          fechaSeleccionada + "T" + horas.FechaHoraInicio;
        let fechaFinValue = fechaSeleccionada + "T" + horas.FechaHoraFin;

        // Convertir las cadenas de fecha y hora a objetos Date
        const fechaInicioDate = new Date(fechaInicioValue);
        const fechaFinDate = new Date(fechaFinValue);

        // Verificar si la hora de fin es menor que la hora de inicio (evento pasa de medianoche)
        if (fechaFinDate < fechaInicioDate) {
          // Sumar un día a la fecha de fin
          fechaFinDate.setDate(fechaFinDate.getDate() + 1);
          // Actualizar la cadena de fechaFinValue con la nueva fecha
          fechaFinValue =
            fechaFinDate.toISOString().split("T")[0] + "T" + horas.FechaHoraFin;
        }

        // Asignar los valores a los campos
        fechaInicio.value = fechaInicioValue;
        fechaFin.value = fechaFinValue;
      });
    });

  // Función para obtener las horas de la nomenclatura
  function obtenerHorasNomenclatura(id, callback) {
    $.ajax({
      type: "POST",
      url: "../Controlador/ControladorNomenclatura.php?Accion=ListarPorId",
      data: { IdNomenclatura: id },
      success: function (msg) {
        if (msg.error) {
          alert(msg.error);
          return;
        }

        // Verifica que la respuesta tenga los datos esperados
        if (msg.length > 0 && msg[0].FechaHoraInicio && msg[0].FechaHoraFin) {
          callback(msg[0]); // Llama al callback con los datos de la nomenclatura
        } else {
          alert("Datos de nomenclatura no válidos");
        }
      },
      error: function (req, textStatus, errorThrown) {
        MensajeError(
          "Oops...",
          "Error al obtener Datos1!",
          textStatus + " " + errorThrown
        );
      }
    });
  }

  // Event listener for the download button
  document
    .getElementById("descargarExcel")
    .addEventListener("click", function () {
      const IDdArea = urlParams.get("id");
      descargarExcel(fecIni, fecFin, IDdArea);
    });

  // Event listener for the download button
  document
    .getElementById("descargarPDF")
    .addEventListener("click", function () {
      const IDdArea = urlParams.get("id");
      descargarPDF(fecIni, fecFin, IDdArea);
    });
});
function generarColorOrdenado() {
  const colores = [
    "#141414",
    "#10375C",
    "#0f4437",
    "#7B1E3C",
    "#4A1F6F",
    "#A43F00",
    "#007B7F",
    "#1E7345",
    "#A31B2E",
    "#947B25",
    "#6B3E26",
    "#5A2C91"
  ];
  let color = colores[indiceColor % colores.length];
  indiceColor++; // Aumenta para el siguiente médico
  return color;
}
function obtenerColorMedico(idMedico) {
  if (!coloresMedicos[idMedico]) {
    coloresMedicos[idMedico] = generarColorOrdenado();
  }
  return coloresMedicos[idMedico];
}
function renderLeyendaMedicos(coloresMedicos, turnos) {
  const color = {}; // Mantener como 'const' y usarlo como un objeto

  turnos.forEach((turno) => {
    color[turno.IdMedico] = obtenerColorMedico(turno.IdMedico); // Asignar color dinámico al médico
  });

  const leyenda = document.getElementById("leyenda-medicos");
  // Limpiar la leyenda
  leyenda.innerHTML = "";

  // Renderizar la leyenda
  Object.keys(color).forEach((idMedico) => {
    const colorMedico = color[idMedico]; // Obtener el color del médico

    // Buscar el nombre del médico en los turnos
    const turno = turnos.find((t) => t.IdMedico == idMedico);
    const nombreMedico = turno ? turno.NombreMedico : `Médico ${idMedico}`; // Usar el nombre del médico si está disponible

    leyenda.innerHTML += `
            <span class="NomLeyenda">
                <span style="background-color: ${colorMedico}; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; display: inline-block;"></span>
                <span>${nombreMedico}</span><br>
            </span>
        `;
  });
}

function obtenerTurnosPorMes(
  FechaHoraInicio,
  FechaHoraFin,
  idArea,
  successCallback,
  failureCallback
) {
  $.ajax({
    url: "../Controlador/ControladorTurno.php?Accion=ListarPorTurnosAreaFecha",
    type: "POST",
    data: {
      FechaHoraInicio: FechaHoraInicio,
      FechaHoraFin: FechaHoraFin,
      IdArea: idArea
    },
    success: function (response) {
      try {
        const turnos =
          typeof response === "string" ? JSON.parse(response) : response;

        if (!Array.isArray(turnos)) {
          throw new Error("La respuesta no es un array válido.");
        }

        /* =====================================================
           OBTENER AÑO Y MES EN ZONA HORARIA COLOMBIA
        ===================================================== */
        const fechaBase = new Date(FechaHoraInicio);

        const partesFecha = new Intl.DateTimeFormat("en-US", {
          timeZone: "America/Bogota",
          year: "numeric",
          month: "numeric",
          day: "numeric",
          hour12: false
        }).formatToParts(fechaBase);

        const getPart = (type) =>
          Number(partesFecha.find(p => p.type === type)?.value);

        let year = getPart("year");
        let month = getPart("month") + 1; // mes siguiente

        // Normalizar cambio de año (evita mes 13)
        if (month > 12) {
          year += 1;
          month = 1;
        }

        // Renderizar la tabla con el mes correcto
        renderTable(turnos, year, month);

        /* =====================================================
           PROCESAR EVENTOS
        ===================================================== */
        let eventosUnDia = [];
        let eventosVariosDias = [];

        turnos.forEach((turno) => {
          const color = obtenerColorMedico(turno.IdMedico);

          const start = new Date(turno.FechaHoraInicio);
          const end = new Date(turno.FechaHoraFin);

          const esEventoDeVariosDias =
            end &&
            (
              start.getDate() !== end.getDate() ||
              start.getMonth() !== end.getMonth() ||
              start.getFullYear() !== end.getFullYear()
            );

          const evento = {
            title: "",
            start: turno.FechaHoraInicio,
            end: turno.FechaHoraFin,
            color: color,
            extendedProps: {
              idTurno: turno.IdTurno,
              nombreMedico: turno.NombreMedico,
              nombreActividad: turno.NombreActividad
            }
          };

          esEventoDeVariosDias
            ? eventosVariosDias.push(evento)
            : eventosUnDia.push(evento);
        });

        // Unir eventos (1 día primero)
        const eventos = eventosUnDia.concat(eventosVariosDias);

        // Renderizar leyenda
        renderLeyendaMedicos(coloresMedicos, turnos);

        successCallback(eventos);

      } catch (error) {
        console.error("Error al procesar los turnos:", error);
        failureCallback(error);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
      failureCallback(errorThrown);
    }
  });
}

function obtenerTurnosPorDia(FechaHoraInicio, FechaHoraFin, idArea) {
  $.ajax({
    url: "../Controlador/ControladorTurno.php?Accion=ListarPorTurnosAreaFecha",
    type: "POST",
    data: {
      FechaHoraInicio: FechaHoraInicio,
      FechaHoraFin: FechaHoraFin,
      IdArea: idArea
    },
    success: function (response) {
      // Si la respuesta es una cadena JSON, usa JSON.parse
      if (typeof response === "string") {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error("Error al parsear JSON:", e);
          return;
        }
      }

      // Formatear la fecha para el título del modal
      let fecha = new Date(FechaHoraInicio);
      let dia = fecha.getDate();
      let diaSemana = fecha.toLocaleDateString("es-CO", { weekday: "long" });
      diaSemana = diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1);

      // Actualizar el título del modal
      $("#diaModal").text(dia);
      $("#diaSemanaModal").text(diaSemana);

      // Limpiar el contenido previo del modal
      $("#turnosInfo5").empty();

      // Iterar sobre los turnos y agregar la información al modal
      response.forEach(function (turno, index) {
        const color = coloresMedicos[turno.IdMedico]; // Cambiado de IdTurno a IdMedico
        let horaInicio = new Date(turno.FechaHoraInicio).toLocaleTimeString(
          "es-CO",
          { hour: "2-digit", minute: "2-digit", hour12: true }
        );
        let horaFin = new Date(turno.FechaHoraFin).toLocaleTimeString("es-CO", {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true
        });

        let contenido = `
                <div class="turno-container" data-turno-id="${turno.IdTurno}">
                    ${
                      index === 0
                        ? `<div class="hora-inicio">${horaInicio}</div>`
                        : '<div class="hora-inicio"></div>'
                    }
                    
                    <div class="turno-info">
                        <div class="turno-item" data-color="${color}">
                            <div class="nombre-actividad">${
                              turno.NombreMedico
                            } - ${turno.NombreActividad}</div>
                        </div>
                        <div class="rango-horas">${horaInicio} - ${horaFin}</div>
                    </div>
                </div>
                <br>
                `;

        $("#turnosInfo5").append(contenido);
      });

      document.querySelectorAll(".turno-item").forEach((item) => {
        const color = item.getAttribute("data-color");
        item.style.setProperty("--color-medico", color);
      });

      // Agregar evento de clic a los turnos
      $(".turno-container").on("click", function () {
        let turnoId = $(this).data("turno-id"); // Obtener el ID del turno
        actualizarTurnos(turnoId); // Llamar a la función actualizarTurnos con el ID del turno
      });

      // Mostrar el modal
      $("#modalCal").modal("show");
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
      console.log("Respuesta completa del servidor:", jqXHR.responseText);
    }
  });
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////TABLA DE TURNOS//////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
let month = null;
let year = null;
let IdArea = null;

function procesarDatos(turnos) {
  const result = turnos.reduce((acc, turno) => {
    const key = `${turno.NombreMedico}-${turno.DocumentoMedico}`;
    if (!acc[key]) {
      acc[key] = {
        NombreMedico: turno.NombreMedico,
        DocumentoMedico: turno.DocumentoMedico,
        turnos: []
      };
    }
    acc[key].turnos.push(turno);
    return acc;
  }, {});
  return result;
}

function descargarExcel(FechaHoraInicio, FechaHoraFin, idArea) {
  $.ajax({
    url: "../Controlador/ControladorTurno.php?Accion=DescargarExcel",
    type: "POST",
    data: {
      FechaHoraInicio: FechaHoraInicio,
      FechaHoraFin: FechaHoraFin,
      IdArea: idArea
    },
    xhrFields: {
      responseType: "blob"
    },
    success: function (data, status, xhr) {
      const blob = new Blob([data], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
      });
      const link = document.createElement("a");
      const url = window.URL.createObjectURL(blob);
      link.href = url;
      const contentDisposition = xhr.getResponseHeader("Content-Disposition");
      let fileName = "turnos.xlsx";
      if (contentDisposition) {
        const match = contentDisposition.match(/filename="(.+)"/);
        if (match) fileName = match[1];
      }
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error al descargar el Excel:", textStatus, errorThrown);
      alert("Error al generar el archivo Excel.");
    }
  });
}

// Function to download PDF
function descargarPDF(FechaHoraInicio, FechaHoraFin, idArea) {
  $.ajax({
    url: "../Controlador/ControladorTurno.php?Accion=DescargarPDF",
    type: "POST",
    data: {
      FechaHoraInicio: FechaHoraInicio,
      FechaHoraFin: FechaHoraFin,
      IdArea: idArea
    },
    xhrFields: {
      responseType: "blob" // Important for handling binary data (PDF file)
    },
    success: function (data, status, xhr) {
      const blob = new Blob([data], { type: "application/pdf" });
      const link = document.createElement("a");
      const url = window.URL.createObjectURL(blob);
      link.href = url;
      const contentDisposition = xhr.getResponseHeader("Content-Disposition");
      let fileName = "turnos.pdf";
      if (contentDisposition) {
        const match = contentDisposition.match(/filename="(.+)"/);
        if (match) fileName = match[1];
      }
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error("Error al descargar el PDF:", textStatus, errorThrown);
      alert("Error al generar el archivo PDF.");
    }
  });
}

function parsearFecha(fechaStr) {
  if (!fechaStr || typeof fechaStr !== "string") {
    return null;
  }
  try {
    const fechaLimpia = fechaStr.split(".")[0];
    const [fechaPart, horaPart] = fechaLimpia.split(" ");
    if (!fechaPart || !horaPart) {
      return null;
    }
    const [year, month, day] = fechaPart.split("-");
    const [hours, minutes, seconds] = horaPart.split(":");
    const fechaObj = new Date(
      year,
      month - 1,
      day,
      hours,
      minutes,
      seconds || 0
    );
    if (isNaN(fechaObj.getTime())) {
      console.warn("[parsearFecha] Fecha inválida creada");
      return null;
    }
    return fechaObj;
  } catch (e) {
    console.error("[parsearFecha] Error al parsear fecha:", e);
    return null;
  }
}

function calcularHorasTrabajadas(turno) {
  if (!turno || !turno.FechaHoraInicio || !turno.FechaHoraFin) {
    console.warn("[calcularHoras] Turno inválido o faltan fechas");
    return 0;
  }
  const inicio = parsearFecha(turno.FechaHoraInicio);
  const fin = parsearFecha(turno.FechaHoraFin);
  if (!inicio || !fin) {
    console.warn("[calcularHoras] No se pudieron parsear las fechas");
    return 0;
  }
  if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) {
    console.warn("[calcularHoras] Fechas inválidas");
    return 0;
  }
  const diffMs = fin - inicio;
  const diffHoras = diffMs / (1000 * 60 * 60);
  const horasRedondeadas = Math.max(0, Math.round(diffHoras * 100) / 100);
  return horasRedondeadas;
}

function renderHorasExtrasTable(data) {
  const tbody = document.querySelector("#horasExtrasTable tbody");
  if (!tbody) {
    console.error(
      "[renderHorasExtrasTable] No se encontró el elemento #horasExtrasTable tbody"
    );
    return;
  }
  tbody.innerHTML = "";

  if (!data || !data.length) {
    const row = document.createElement("tr");
    row.innerHTML = `<td colspan="2">No hay datos de horas extras disponibles</td>`;
    tbody.appendChild(row);
    return;
  }

  data.forEach((item) => {
    const row = document.createElement("tr");
    row.innerHTML = `
            <td>${item.NombreMedico}</td>
            <td>${item.HorasExtras}</td>
        `;
    tbody.appendChild(row);
  });
}

function loadHorasExtras(month, year, idArea) {
  $.ajax({
    url: "../Controlador/ControladorExtra.php?Accion=NumeroExtras",
    type: "POST",
    data: {
      month: month,
      year: year,
      IdArea: idArea
    },
    dataType: "json",
    success: function (response) {
      if (response.success && response.data) {
        renderHorasExtrasTable(response.data);
      } else {
        console.warn(
          "[loadHorasExtras] No se recibieron datos válidos:",
          response
        );
        renderHorasExtrasTable([]);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error(
        "[loadHorasExtras] Error al cargar horas extras:",
        textStatus,
        errorThrown
      );
      renderHorasExtrasTable([]);
    }
  });
}

function renderTable(data, year, month, FechaHoraInicio, FechaHoraFin, idArea) {

  const tbody = document.querySelector("#turnosTable tbody");
  if (!tbody) {
    console.error(
      "[renderTable] No se encontró el elemento #turnosTable tbody"
    );
    return;
  }
  tbody.innerHTML = "";

  const daysInMonth = new Date(year, month, 0).getDate();

  // Crear cabeceras
  const headerRow = document.createElement("tr");
  headerRow.innerHTML = `<th>Nombre del Profesional</th><th>Documento</th>`;
  for (let dia = 1; dia <= daysInMonth; dia++) {
    headerRow.innerHTML += `<th>${dia}</th>`;
  }
  headerRow.innerHTML += `<th>Total Horas Mes</th>`;
  tbody.appendChild(headerRow);

  const daysOfWeekRow = document.createElement("tr");
  daysOfWeekRow.innerHTML = `<th></th><th></th>`;
  for (let dia = 1; dia <= daysInMonth; dia++) {
    const fecha = new Date(year, month - 1, dia);
    const dayOfWeek = fecha.toLocaleDateString("es-ES", { weekday: "short" });
    daysOfWeekRow.innerHTML += `<th>${dayOfWeek}</th>`;
  }
  daysOfWeekRow.innerHTML += `<th></th>`;
  tbody.appendChild(daysOfWeekRow);

  // Procesar médicos
  const datosProcesados = procesarDatos(data);

  Object.values(datosProcesados).forEach((medico) => {
    // Preprocesar turnos por día
    const turnosPorDia = {};
    let totalHorasMes = 0;
    medico.turnos.forEach((turno) => {
      try {
        const fecha = parsearFecha(turno.FechaHoraInicio);
        if (!fecha) {
          console.warn(`[renderTable] Turno tiene fecha inválida`);
          return;
        }
        const dia = fecha.getDate();
        if (fecha.getMonth() === month - 1 && fecha.getFullYear() === year) {
          if (!turnosPorDia[dia]) turnosPorDia[dia] = [];
          turnosPorDia[dia].push(turno);
          totalHorasMes += calcularHorasTrabajadas(turno);
        }
      } catch (e) {
        console.error(`[renderTable] Error procesando turno:`, e);
      }
    });

    // Determinar cuántas filas de códigos por día
    const maxTurnosPorDia = Math.max(
      ...Object.values(turnosPorDia).map((t) => t.length),
      0
    );

    // Crear filas para códigos
    const codigoRows = [];
    for (let i = 0; i < maxTurnosPorDia; i++) {
      const row = document.createElement("tr");
      if (i === 0) {
        row.innerHTML = `<td rowspan="${maxTurnosPorDia + 1}">${
          medico.NombreMedico
        }</td>
                                <td rowspan="${maxTurnosPorDia + 1}">${
          medico.DocumentoMedico
        }</td>`;
      }
      codigoRows.push(row);
    }

    // Fila para horas trabajadas
    const horasRow = document.createElement("tr");
    horasRow.className = "horas-trabajadas";

    // Llenar las filas
    for (let dia = 1; dia <= daysInMonth; dia++) {
      const turnosDia = turnosPorDia[dia] || [];
      const codigosUnicos = [];
      const turnosPorCodigo = {};
      turnosDia.forEach((turno) => {
        const codigo = turno.CodNomenclatura || "";
        if (!turnosPorCodigo[codigo]) {
          turnosPorCodigo[codigo] = [];
          codigosUnicos.push(codigo);
        }
        turnosPorCodigo[codigo].push(turno);
      });

      for (let i = 0; i < maxTurnosPorDia; i++) {
        const cell = document.createElement("td");
        if (i < codigosUnicos.length) {
          cell.textContent = codigosUnicos[i];
        }
        codigoRows[i].appendChild(cell);
      }

      const horasCell = document.createElement("td");
      const totalHorasDia = turnosDia.reduce(
        (sum, t) => sum + calcularHorasTrabajadas(t),
        0
      );
      horasCell.textContent = totalHorasDia > 0 ? totalHorasDia.toFixed(2) : "";
      horasRow.appendChild(horasCell);
    }

    for (let i = 0; i < maxTurnosPorDia; i++) {
      const totalCell = document.createElement("td");
      if (i === 0) {
      }
      codigoRows[i].appendChild(totalCell);
    }
    const totalHorasCell = document.createElement("td");
    totalHorasCell.textContent =
      totalHorasMes > 0 ? totalHorasMes.toFixed(2) : "";
    horasRow.appendChild(totalHorasCell);

    codigoRows.forEach((row) => tbody.appendChild(row));
    tbody.appendChild(horasRow);
  });
  // Cargar tabla de horas extras
  loadHorasExtras(month, year, idAreaUrl);
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////LIQUIDAR///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Función liquidarturnos modificada para usar sessionStorage
function liquidarturnos() {
  let NombreArea = urlParams.get("nombreArea");

  $.ajax({
    type: "POST",
    url: "../Controlador/ControladorEspecialidad.php?Accion=IdEspecialidad",
    data: { IdArea: idAreaUrl },
    success: function (msg) {
      // Crear formulario oculto para enviar por POST
      var form = document.createElement("form");
      form.method = "POST";
      form.action = "../Vista/LiquidarEspecialidad.php";
      form.style.display = "none";

      // Crear campos ocultos con los datos
      var campos = {
        IdArea: idAreaUrl,
        NombreArea: NombreArea,
        NombreEspecialidad: msg[0].NombreEspecialidad,
        OidEspecialidad: msg[0].OidEspecialidad,
        IdEspecialidad: msg[0].IdEspecialidad
      };

      for (var key in campos) {
        if (campos.hasOwnProperty(key)) {
          var input = document.createElement("input");
          input.type = "hidden";
          input.name = key;
          input.value = campos[key];
          form.appendChild(input);
        }
      }

      // Agregar formulario al documento y enviar
      document.body.appendChild(form);
      form.submit();
    },
    error: function (req, textStatus, errorThrown) {
      MensajeError(
        "Oops...",
        "Error al obtener Datos!",
        textStatus + " " + errorThrown
      );
    }
  });
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////NOVEDADES/////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function Novedades() {
  fetchRequests();
  limpiar();
  $("#modalNovedadesLabel").html("Ver novedades");
  $("#modalNovedades").modal("show");
}

// Variables globales
let currentRequestId = null;
const modal = new bootstrap.Modal(document.getElementById("requestModal"));

// Función para obtener las solicitudes desde el backend
async function fetchRequests() {
  try {
    showLoading(true);

    let url =
      "../Controlador/ControladorTurno.php?Accion=obtenerSolicitudesArea&IdArea=" +
      idAreaUrl +
      "&month=" +
      mesSeleccionado +
      "&year=" +
      yearSeleccionado;

    const response = await fetch(url);
    const data = await response.json();

    if (data.error) {
      throw new Error(data.error);
    }
    renderRequests(data);
  } catch (error) {
    console.error("Error al obtener solicitudes:", error);
    alert("Error al cargar las solicitudes: " + error.message);
  } finally {
    showLoading(false);
  }
}

// Función para obtener los detalles de una solicitud
async function fetchRequestDetails(requestId) {
  try {
    document.getElementById("modalBody").innerHTML = `
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                `;

    const response = await fetch(
      `../Controlador/ControladorTurno.php?Accion=ObtenerSolicitud&IdAuditoria=${requestId}`
    );
    const data = await response.json();

    if (data.error) {
      throw new Error(data.error);
    }

    renderRequestDetails(data);
  } catch (error) {
    console.error("Error al obtener detalles:", error);
    document.getElementById("modalBody").innerHTML = `
                    <div class="alert alert-danger">
                        Error al cargar los detalles: ${error.message}
                    </div>
                `;
  }
}

function renderRequests(requests) {
  const tbody = document.getElementById("requestsTableBody");

  if (requests.length === 0) {
    if ($.fn.DataTable.isDataTable("#tblDatosNovedade")) {
      $("#tblDatosNovedade").DataTable().clear().draw();
    }

    tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                    <p>No se encontraron solicitudes</p>
                </td>
            </tr>
        `;
    return;
  }

  // Si DataTable ya está inicializado
  if ($.fn.DataTable.isDataTable("#tblDatosNovedade")) {
    const table = $("#tblDatosNovedade").DataTable();
    table.clear();

    requests.forEach((request) => {
      table.row.add([
        `#${request.IdAuditoria}`,
        getActionBadge(request.Accion),
        request.NombreMedico || "N/A",
        formatDateTime(
          request.FechaHoraInicio_old || request.FechaHoraInicio_new
        ), // Compacto
        `${request.UsuarioSolicitante || "Usuario"} (ID: ${request.IdUsuario})`,
        formatDateTime(request.FechaSolicitud),
        getStatusBadge(request.EstadoSolicitud),
        `<button class="btn btn-sm btn-outline-primary2 view-details" 
                         data-id="${request.IdAuditoria}">
                    <i class="fas fa-eye"></i> Ver
                 </button>`
      ]);
    });

    table.draw();
  } else {
    // Inicializar DataTable por primera vez
    tbody.innerHTML = requests
      .map(
        (request) => `
            <tr>
                <td>#${request.IdAuditoria}</td>
                <td>${getActionBadge(request.Accion)}</td>
                <td>${request.NombreMedico || "N/A"}</td>
                <td>${formatDateTime(
                  request.FechaHoraInicio_old || request.FechaHoraInicio_new
                )}</td> <!-- Compacto -->
                <td>${request.UsuarioSolicitante || "Usuario"} (ID: ${
          request.IdUsuario
        })</td>
                <td>${formatDateTime(request.FechaSolicitud)}</td>
                <td>${getStatusBadge(request.EstadoSolicitud)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary2 view-details" 
                            data-id="${request.IdAuditoria}">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </td>
            </tr>
        `
      )
      .join("");

    inicializarDataTable("tblDatosNovedade");
  }

  // Event listeners
  $(document).on("click", ".view-details", function () {
    currentRequestId = $(this).data("id");
    document.getElementById("modalRequestId").textContent =
      "#" + currentRequestId;
    fetchRequestDetails(currentRequestId);
    $("#requestModal").modal("show");
  });
}
// Función para renderizar los detalles de una solicitud en el modal
function renderRequestDetails(details) {
  const modalBody = document.getElementById("modalBody");
  const btnApprove = document.getElementById("btnApprove");
  const btnReject = document.getElementById("btnReject");
  const request = details[0];

  // Mostrar u ocultar botones según el estado
  if (request.EstadoSolicitud === "PENDIENTE" && IdRol === "1") {
    btnApprove.style.display = "inline-block";
    btnReject.style.display = "inline-block";

    // Configurar event listeners para los botones

    btnApprove.onclick = async () => {
      $("#requestModal").modal("hide");
      const { value: observation } = await Swal.fire({
        title: "Observación para aprobación",
        input: "textarea",
        inputLabel: "Ingrese una observación para la aprobación:",
        inputPlaceholder: "Escriba sus observaciones aquí...",
        inputAttributes: {
          "aria-label": "Escriba sus observaciones aquí"
        },
        showCancelButton: true,
        confirmButtonText: "Aprobar",
        cancelButtonText: "Cancelar",
        customClass: {
          popup: "swal2-dark"
        },
        inputValidator: (value) => {
          if (!value) {
            return "Debe ingresar una observación";
          }
        }
      });

      if (observation) {
        processRequest(currentRequestId, "APROBADO", observation);
      }
    };

    btnReject.onclick = async () => {
      $("#requestModal").modal("hide");
      const { value: observation } = await Swal.fire({
        title: "Observación para rechazo",
        input: "textarea",
        inputLabel: "Ingrese una observación para el rechazo:",
        inputPlaceholder: "Escriba sus observaciones aquí...",
        inputAttributes: {
          "aria-label": "Escriba sus observaciones aquí"
        },
        showCancelButton: true,
        confirmButtonText: "Rechazar",
        cancelButtonText: "Cancelar",
        customClass: {
          popup: "swal2-dark"
        },
        inputValidator: (value) => {
          if (!value) {
            return "Debe ingresar una observación";
          }
        }
      });

      if (observation) {
        processRequest(currentRequestId, "RECHAZADO", observation);
      }
    };
  } else {
    btnApprove.style.display = "none";
    btnReject.style.display = "none";
  }

  // Construir el contenido según el tipo de acción
  let actionDetails = "";

  switch (request.Accion) {
    case "INSERT":
      actionDetails = `
                <div class="detail-item">
                    <strong class="text-success">Nuevo Turno a Crear:</strong>
                    <div class="card mt-2 border-success">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Médico:</small>
                                    <p class="mb-1 fw-bold">${
                                      request.NombreMedico || "N/A"
                                    }</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Nomenclatura:</small>
                                    <p class="mb-1 fw-bold">${
                                      request.Nomenclatura || "N/A"
                                    }</p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Fecha y Hora:</small>
                                    <p class="mb-1 fw-bold">${formatDateTime(
                                      request.FechaHoraInicio_new
                                    )} - ${formatDateTime(
        request.FechaHoraFin_new
      )}</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Duración:</small>
                                    <p class="mb-1 fw-bold">${calculateDuration(
                                      request.FechaHoraInicio_new,
                                      request.FechaHoraFin_new
                                    )}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      break;

    case "UPDATE":
      actionDetails = `
                <div class="detail-item">
                    <strong class="text-primary">Cambios Propuestos:</strong>
                    <div class="card mt-2">
                        <div class="card-header bg-light">
                            <ul class="nav nav-tabs card-header-tabs" id="updateTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab">Actual</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="proposed-tab" data-bs-toggle="tab" data-bs-target="#proposed" type="button" role="tab">Propuesto</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="differences-tab" data-bs-toggle="tab" data-bs-target="#differences" type="button" role="tab">Diferencias</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="updateTabsContent">
                                <div class="tab-pane fade show active" id="current" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Fecha y Hora Actual:</small>
                                            <p class="mb-1">${formatDateTime(
                                              request.FechaHoraInicio_old
                                            )} - ${formatDateTime(
        request.FechaHoraFin_old
      )}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Duración Actual:</small>
                                            <p class="mb-1">${calculateDuration(
                                              request.FechaHoraInicio_old,
                                              request.FechaHoraFin_old
                                            )}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="proposed" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Fecha y Hora Propuesta:</small>
                                            <p class="mb-1">${formatDateTime(
                                              request.FechaHoraInicio_new
                                            )} - ${formatDateTime(
        request.FechaHoraFin_new
      )}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Duración Propuesta:</small>
                                            <p class="mb-1">${calculateDuration(
                                              request.FechaHoraInicio_new,
                                              request.FechaHoraFin_new
                                            )}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="differences" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <small class="text-muted">Cambio de Horario:</small>
                                            <p class="mb-1">
                                                <span class="badge bg-danger">${formatDateTime(
                                                  request.FechaHoraInicio_old
                                                )}</span> 
                                                <i class="fas fa-arrow-right mx-2 text-primary"></i>
                                                <span class="badge bg-success">${formatDateTime(
                                                  request.FechaHoraInicio_new
                                                )}</span>
                                            </p>
                                            <small class="text-muted">Diferencia:</small>
                                            <p class="mb-1">
                                                ${calculateTimeDifference(
                                                  request.FechaHoraInicio_old,
                                                  request.FechaHoraInicio_new
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      break;

    case "DELETE":
      actionDetails = `
                <div class="detail-item">
                    <strong class="text-danger">Turno a Eliminar:</strong>
                    <div class="card mt-2 border-danger">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Médico:</small>
                                    <p class="mb-1">${
                                      request.NombreMedico || "N/A"
                                    }</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Nomenclatura:</small>
                                    <p class="mb-1">${
                                      request.Nomenclatura || "N/A"
                                    }</p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Fecha y Hora:</small>
                                    <p class="mb-1">${formatDateTime(
                                      request.FechaHoraInicio_old
                                    )} - ${formatDateTime(
        request.FechaHoraFin_old
      )}</p>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">Duración:</small>
                                    <p class="mb-1">${calculateDuration(
                                      request.FechaHoraInicio_old,
                                      request.FechaHoraFin_old
                                    )}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Advertencia:</strong> Esta acción eliminará permanentemente el turno.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      break;
  }

  modalBody.innerHTML = `
        <div class="detalleNoverdad">
            <div class="detail-item">
                <strong>Acción:</strong>
                ${getActionBadge(request.Accion)}
            </div>
            <div class="detail-item">
                <strong>Solicitante:</strong>
                ${request.UsuarioSolicitante || "Usuario"} (ID: ${
    request.IdUsuario
  })
            </div>

            <div class="detail-item">
                <strong>Médico:</strong>
                ${request.NombreMedico || "N/A"}
            </div>
            <div class="detail-item">
                <strong>Fecha Solicitud:</strong>
                ${formatDateTime(request.FechaSolicitud)}
            </div>

            <div class="detail-item">
                <strong>Nomenclatura:</strong>
                ${request.Nomenclatura || "N/A"}
            </div>
            <div class="detail-item">
                <strong>Estado:</strong>
                ${getStatusBadge(request.EstadoSolicitud)}
            </div>

            ${
              request.FechaAprobacion
                ? `
            <div class="detail-item">
                <strong>Fecha Resolución:</strong>
                ${formatDateTime(request.FechaAprobacion)}
            </div>
            <div class="detail-item">
                <strong>Resuelto por:</strong>
                ${request.UsuarioAprobador || ""} (ID: ${
                    request.IdUsuarioAprobador
                  })
            </div>
            `
                : ""
            }
        </div>

        
        ${actionDetails}
        
        <div class="detail-item">
            <strong>Observaciones:</strong>
            <div class="mt-2 p-3 bg-light rounded">
                ${request.Observacion || "Sin observaciones"}
            </div>
        </div>

        ${
          request.Accion === "UPDATE"
            ? `
        <div class="detail-item">
            <strong>Historial de Cambios:</strong>
            <div class="timeline mt-3">
                <div class="timeline-item">
                    <strong>Creación original</strong>
                    <p class="mb-0 text-muted">${formatDateTime(
                      request.FechaSolicitud
                    )} por ${request.UsuarioSolicitante}</p>
                </div>
                ${
                  request.FechaAprobacion
                    ? `
                <div class="timeline-item">
                    <strong>Solicitud de modificación</strong>
                    <p class="mb-0 text-muted">${formatDateTime(
                      request.FechaSolicitud
                    )} por ${request.UsuarioSolicitante}</p>
                </div>
                <div class="timeline-item">
                    <strong>Resolución</strong>
                    <p class="mb-0 text-muted">${formatDateTime(
                      request.FechaAprobacion
                    )} por ${request.UsuarioAprobador}</p>
                    <p class="mb-0">Estado: ${getStatusBadge(
                      request.EstadoSolicitud
                    )}</p>
                </div>
                `
                    : `
                <div class="timeline-item">
                    <strong>Solicitud de modificación pendiente</strong>
                    <p class="mb-0 text-muted">${formatDateTime(
                      request.FechaSolicitud
                    )} por ${request.UsuarioSolicitante}</p>
                </div>
                `
                }
            </div>
        </div>
        `
            : ""
        }
    `;
}
// Función para procesar una solicitud (APROBADO/rechazar)
async function processRequest(requestId, action, observation = "") {
  try {
    const formData = new FormData();
    formData.append("IdAuditoria", requestId);
    formData.append("Accion", action);
    formData.append("Observacion", observation);
    formData.append("IdUsuario", IdUsuario); // Esto debería venir de la sesión

    const response = await fetch(
      "../Controlador/ControladorTurno.php?Accion=ProcesarSolicitud",
      {
        method: "POST",
        body: formData
      }
    );

    const result = await response.json();

    if (result.error) {
      throw new Error(result.error);
    }

    if (action === "APROBADO") {
      Swal.fire({
        title: "¡Exito!",
        text: "Solicitud realizada con exito!",
        icon: "success",
        customClass: {
          popup: "swal2-dark"
        }
      });
    } else if (action === "RECHAZADO") {
      Swal.fire({
        title: "¡Rechazado!",
        text: "Solicitud rechazada con exito!",
        icon: "error",
        customClass: {
          popup: "swal2-dark"
        }
      });
    }

    $("#requestModal").modal("hide");
    fetchRequests(); // Recargar la lista
  } catch (error) {
    console.error("Error al procesar solicitud:", error);
    alert("Error al procesar la solicitud: " + error.message);
  }
}
// Función auxiliar para calcular la duración entre dos fechas
function calculateDuration(start, end) {
  if (!start || !end) return "N/A";

  const startDate = new Date(start);
  const endDate = new Date(end);
  const diffMs = Math.abs(endDate - startDate);
  const diffMins = Math.floor(diffMs / 60000);
  const hours = Math.floor(diffMins / 60);
  const minutes = diffMins % 60;

  return `${hours}h ${minutes}m`;
}

// Función auxiliar para calcular la diferencia de tiempo entre dos fechas
function calculateTimeDifference(oldDate, newDate) {
  if (!oldDate || !newDate) return "N/A";

  const oldDateTime = new Date(oldDate);
  const newDateTime = new Date(newDate);
  const diffMs = Math.abs(newDateTime - oldDateTime);
  const diffMins = Math.floor(diffMs / 60000);
  const hours = Math.floor(diffMins / 60);
  const minutes = diffMins % 60;

  const direction = newDateTime > oldDateTime ? "después" : "antes";

  return `${hours}h ${minutes}m ${direction}`;
}

// Función para mostrar/ocultar el estado de carga
function showLoading(show) {
  const tbody = document.getElementById("requestsTableBody");
  if (show) {
    tbody.innerHTML = `
                    <tr>
                        <td colspan="8">
                            <div class="loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
  }
}

// Funciones auxiliares
function getActionBadge(action) {
  const badges = {
    INSERT: '<span class="badge bg-info">Inserción</span>',
    UPDATE: '<span class="badge bg-primary">Modificación</span>',
    DELETE: '<span class="badge bg-danger">Eliminación</span>'
  };
  return (
    badges[action] || '<span class="badge bg-secondary">Desconocido</span>'
  );
}

function getStatusBadge(status) {
  const badges = {
    PENDIENTE: '<span class="badge bg-warning status-badge">Pendiente</span>',
    APROBADO: '<span class="badge bg-success status-badge">Aprobado</span>',
    RECHAZADO: '<span class="badge bg-danger status-badge">Rechazado</span>'
  };
  return (
    badges[status] ||
    '<span class="badge bg-secondary status-badge">Desconocido</span>'
  );
}

function formatDateTime(dateTimeString) {
  if (!dateTimeString) return "N/A";

  const date = new Date(dateTimeString);
  return date.toLocaleString("es-ES");
}

// Event listeners cuando el documento está listo
document.addEventListener("DOMContentLoaded", function () {
  // Cargar solicitudes al iniciar
  fetchRequests();
});
