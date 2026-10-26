function ingresarHorasAdministrativas() {
    if ($.fn.DataTable.isDataTable('#tablaHorasAdministrativas')) {
        table = $('#tablaHorasAdministrativas').DataTable();
        table.ajax.reload();
    } else {
        table = $('#tablaHorasAdministrativas').DataTable({
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": "../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorMedico",
                "type": "POST",
                "data": function(d) {
                    d.DocumentoUsuario = DocumentoUsuario;
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
                { "data": "FechaHorasAdministrativas" },
                { "data": "NumeroHorasAdministrativas", "render": function(data) { return data + " horas"; } },
                { "data": "ObsHorasAdministrativas", "render": function(data) { return data || 'N/A'; } },
                { 
                    "data": "EstadoHorasAdministrativas",
                    "render": function(data) {
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
                                tooltip = "Rechazada";
                                break;
                            case "3":
                                imagen = "liquidado.png";
                                tooltip = "Liquidada";
                                break;
                            default:
                                imagen = "default.png";
                                tooltip = "Estado desconocido";
                        }
                        return `<img src="../Assets/ICON/${imagen}" alt="${data}" title="${tooltip}" style="width: 28px; height: 28px;" />`;
                    }
                },
                { 
                    "data": "IdHorasAdministrativas",
                    "render": function(data) {
                        return `
                            <button class="update_btn" data-id="${data}">
                                <span class="material-icons" translate="no">update</span>
                            </button>
                            <button class="delete_btn" data-id="${data}">
                                <span class="material-icons" translate="no">delete</span>
                            </button>
                        `;
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
        $('#tablaHorasAdministrativas').on('click', '.update_btn', function() {
            var id = $(this).data('id');
            actualizarHorasAdministrativas(id);
        });
        $('#tablaHorasAdministrativas').on('click', '.delete_btn', function() {
            var id = $(this).data('id');
            eliminarHorasAdministrativas(id);
        });
    }
    return table;
}

// Modificar la función CrearHorasAdministrativas para inicializar documentos
function CrearHorasAdministrativas() {
    limpiarCompletamente();
    $("#AccionHorasAdministrativas").val("Ingresar");
    $('#exampleModalLabelHorasAdministrativas').html("Crear Horas Administrativas");
    $('#fDocumentoMedico').val(DocumentoUsuario);
    $('#nombre').text(NombreUsuario);
    
    // Agregar indicador visual de documento requerido
    const fileInputLabel = document.querySelector('label[for="archivosHoras"]');
    if (fileInputLabel) {
        fileInputLabel.innerHTML = 'Documentos de Soporte <span class="text-danger">*</span>';
    }
    
    idmedico(DocumentoUsuario, function(idMedico) {
        $('#fIdMedico').val(idMedico);
        const now = new Date();
        const formattedDate = now.toLocaleString('es-CO', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            hour12: false 
        }).replace(/,/, '');
        $('#fFechaHorasAdministrativas').text(formattedDate);
        $('#fEstadoHorasAdministrativas').val('0');
        $("#ModalIngresoHorasAdministrativas").modal('show');
    });
}
function verificarDocumentosExistentes() {
    const listaDocumentos = document.getElementById('listaDocumentos');
    const itemsDocumentos = listaDocumentos.querySelectorAll('.list-group-item');
    
    for (let item of itemsDocumentos) {
        // Si el item no es el mensaje de "No hay documentos" y tiene contenido real
        if (!item.classList.contains('text-muted') && 
            !item.textContent.includes('No hay documentos') &&
            !item.textContent.includes('Error al cargar')) {
            return true;
        }
    }
    return false;
}
function idmedico(DocumentoUsuario, callback) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorMedico.php?Accion=ListarPorDocumento',
        data: { DocumentoMedico: DocumentoUsuario },
        dataType: 'json',
        success: function(data) {
            if (data && data.length > 0) {
                callback(data[0].IdMedico);
            } else {
                callback(null);
            }
        },
        error: function() {
            callback(null);
        }
    });
}

function validarHorasAdministrativas() {
    if ( 
        $('#fIdMedico').val() == '' ||
        $('#fNumeroHorasAdministrativas').val() == ''
    ) {
        mostrarAlerta('empty_fields');
        return false;
    }
    if ($('#fNumeroHorasAdministrativas').val() <= 0) {
        mostrarAlerta('invalid_hours');
        return false;
    }

    // Validación de documentos
    const archivos = document.getElementById('archivosHoras').files;
    const idHoras = $('#fIdHorasAdministrativas').val();
    const hayDocumentosExistentes = verificarDocumentosExistentes();
    
    // Para nuevo registro
    if (idHoras === '0') {
        if (archivos.length === 0) {
            mostrarAlerta('no_documents');
            return false;
        }
    } 
    // Para edición
    else {
        if (!hayDocumentosExistentes && archivos.length === 0) {
            mostrarAlerta('no_documents');
            return false;
        }
    }

    return true;
}

function mostrarAlerta(tipo) {
    const configBase = {
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Entendido',
        customClass: { popup: 'swal2-dark' }
    };
    switch(tipo) {
        case 'empty_fields':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Campos Incompletos',
                text: 'Por favor diligencie el número de horas'
            });
            break;
        case 'invalid_hours':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Horas Inválidas',
                text: 'El número de horas debe ser mayor que cero'
            });
            break;
        case 'no_documents':
            Swal.fire({
                ...configBase,
                icon: 'error',
                title: 'Documento Requerido',
                html: 'Debe adjuntar al menos un documento de soporte.<br><small>Formatos permitidos: PDF, Word, Excel, PowerPoint, Imágenes</small>'
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

function toggleFormularioHorasAdministrativas(bloqueado) {
    $('#fNumeroHorasAdministrativas').prop('disabled', bloqueado);
    $('#btnGuardarHorasAdministrativas').prop('disabled', bloqueado);
}


// Función para cargar documentos
function cargarDocumentos(idHoras) {
    $.ajax({
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ObtenerDocumentos',
        type: 'POST',
        data: { IdHorasAdministrativas: idHoras },
        success: function(response) {
            const data = (response);
            const lista = document.getElementById('listaDocumentos');
            
            if (data.success && data.documentos.length > 0) {
                let html = '';
                
                data.documentos.forEach(doc => {
                    const icono = obtenerIconoDocumento(doc.TipoArchivo);
                    const tamaño = formatFileSize(doc.TamanioArchivo);
                    
                    html += `
                        <div class="list-group-item">
                            <div>
                                <i class="${icono} me-2"></i>
                                <a class="enlace" href="${doc.RutaArchivo}" target="_blank" class="text-decoration-none">
                                    ${doc.NombreArchivo}
                                </a>
                                <small class="text-muted ms-2">(${tamaño})</small>
                            </div>

                        </div>
                    `;
                });        //Boton para eliminar documentos
                            //<button class="delete_btn" onclick="eliminarDocumento(${doc.IdDocumento})">
                                //<i class="fa-solid fa-trash"></i>
                            //</button>
                lista.innerHTML = html;
                
                // Actualizar el indicador visual si estamos en edición
                if ($('#fIdHorasAdministrativas').val() !== '0') {
                    const fileInputLabel = document.querySelector('label[for="archivosHoras"]');
                    if (fileInputLabel && data.documentos.length > 0) {
                        fileInputLabel.innerHTML = 'Documentos de Soporte <span class="text-success">✓</span>';
                    }
                }
            } else {
                lista.innerHTML = '<div class="list-group-item text-muted">No hay documentos subidos</div>';
                
                // Actualizar el indicador visual si estamos en edición
                if ($('#fIdHorasAdministrativas').val() !== '0') {
                    const fileInputLabel = document.querySelector('label[for="archivosHoras"]');
                    if (fileInputLabel) {
                        fileInputLabel.innerHTML = 'Documentos de Soporte <span class="text-danger">*</span>';
                    }
                }
            }
        },
        error: function() {
            document.getElementById('listaDocumentos').innerHTML = 
                '<div class="list-group-item text-danger">Error al cargar documentos</div>';
        }
    });
}

// Función para eliminar documento
function eliminarDocumento(idDocumento) {
    Swal.fire({
        title: '¿Eliminar documento?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'swal2-dark' }
    }).then((result) => {
        if (result.isConfirmed) {
            const idHoras = $('#fIdHorasAdministrativas').val();
            
            $.ajax({
                url: '../Controlador/ControladorHorasAdministrativas.php?Accion=EliminarDocumento',
                type: 'POST',
                data: {
                    IdHorasAdministrativas: idHoras,
                    IdDocumento: idDocumento
                },
                success: function(response) {
                    const data = (response);
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Documento eliminado correctamente',
                            customClass: { popup: 'swal2-dark' }
                        });
                        cargarDocumentos(idHoras);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al eliminar documento',
                            customClass: { popup: 'swal2-dark' }
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar documento',
                        customClass: { popup: 'swal2-dark' }
                    });
                }
            });
        }
    });
}

// Función auxiliar para obtener icono según tipo de archivo
function obtenerIconoDocumento(tipoArchivo) {
    if (tipoArchivo.includes('pdf')) return 'fa-solid fa-file-pdf text-danger';
    if (tipoArchivo.includes('word') || tipoArchivo.includes('doc')) return 'fa-solid fa-file-word text-primary';
    if (tipoArchivo.includes('excel') || tipoArchivo.includes('sheet')) return 'fa-solid fa-file-excel text-success';
    if (tipoArchivo.includes('powerpoint') || tipoArchivo.includes('presentation')) return 'fa-solid fa-file-powerpoint text-warning';
    if (tipoArchivo.includes('image')) return 'fa-solid fa-file-image text-info';
    return 'fa-solid fa-file text-secondary';
}

// Función para formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Modificar la función actualizarHorasAdministrativas para cargar documentos
function actualizarHorasAdministrativas(id) {
    limpiarHorasAdministrativas();
    $('#exampleModalLabelHorasAdministrativas').html("Editar Horas Administrativas");
    $("#AccionHorasAdministrativas").val("Actualizar");
    $('#fIdHorasAdministrativas').val(id);

    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorId',
        data: { IdHorasAdministrativas: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontraron datos para editar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }
            const datos = response[0];
            $('#fIdHorasAdministrativas').val(datos.IdHorasAdministrativas);
            $('#fIdMedico').val(datos.IdMedico);
            const date = new Date(datos.FechaHorasAdministrativas);
            const formattedDate = date.toLocaleString('es-CO', { 
                year: 'numeric', 
                month: '2-digit', 
                day: '2-digit', 
                hour: '2-digit', 
                minute: '2-digit', 
                hour12: false 
            }).replace(/,/, '');
            $('#fFechaHorasAdministrativas').text(formattedDate);
            $('#fNumeroHorasAdministrativas').val(datos.NumeroHorasAdministrativas);
            $('#fObsHorasAdministrativas').val(datos.ObsHorasAdministrativas || '');
            $('#fEstadoHorasAdministrativas').val(datos.EstadoHorasAdministrativas);
            $('#nombre').text(datos.NombreMedico);
            
            // Cargar documentos
            cargarDocumentos(id);
            
            if (datos.EstadoHorasAdministrativas == "1" || datos.EstadoHorasAdministrativas == "3") {
                let mensaje = '';
                if (datos.EstadoHorasAdministrativas === "1") {
                    mensaje = 'Las horas administrativas ya están confirmadas y no pueden ser editadas.';
                } else {
                    mensaje = 'Las horas administrativas ya están liquidadas y no pueden ser editadas.';
                }
                
                toggleFormularioHorasAdministrativas(true);
                Swal.fire({
                    icon: 'warning',
                    title: 'Horas Administrativas Confirmadas',
                    text: mensaje + ' Por favor, contacte al supervisor directo.',
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                });
            } else {
                toggleFormularioHorasAdministrativas(false);
            }
            $('#ModalIngresoHorasAdministrativas').modal('show');
        },
        error: function(xhr, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener datos: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}


function eliminarHorasAdministrativas(id) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=ListarPorId',
        data: { IdHorasAdministrativas: id },
        dataType: 'json',
        success: function(response) {
            if (!response || response.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontraron datos para eliminar',
                    customClass: { popup: 'swal2-dark' }
                });
                return;
            }
            const datos = response[0];
            if (datos.EstadoHorasAdministrativas == "1" || datos.EstadoHorasAdministrativas == "3") {
                
                
                let mensaje = '';
                if (datos.EstadoHorasAdministrativas === "1") {
                    mensaje = 'Las horas administrativas ya están confirmadas y no pueden ser eliminadas.';
                } else {
                    mensaje = 'Las horas administrativas ya están liquidadas y no pueden ser eliminadas.';
                }
                
                Swal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    text: mensaje + ' Por favor, contacte al supervisor directo.',
                    customClass: { popup: 'swal2-dark' },
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            Swal.fire({
                title: '¿Está seguro que desea eliminar las Horas Administrativas ' + id + '?',
                text: "No podrá recuperar los datos!",
                icon: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonColor: '#058876',
                cancelButtonColor: '#550101',
                confirmButtonText: 'Sí, eliminarlo!',
                cancelButtonText: 'Cancelar',
                customClass: { popup: 'swal2-dark' }
            }).then((choice) => {
                if (choice.isConfirmed) {
                    var HorasAdministrativas = { IdHorasAdministrativas: $.trim(id) };
                    CRUDHorasAdministrativas('Eliminar', HorasAdministrativas);
                }
            });
        },
        error: function(xhr, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener datos: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

// Modificar la función limpiarHorasAdministrativas para limpiar documentos
function limpiarHorasAdministrativas() {
    document.getElementById('fIdHorasAdministrativas').value = '0';
    const now = new Date();
    const formattedDate = now.toLocaleString('es-CO', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: false 
    }).replace(/,/, '');
    document.getElementById('fFechaHorasAdministrativas').innerText = formattedDate;
    document.getElementById('fNumeroHorasAdministrativas').value = '';
    document.getElementById('fObsHorasAdministrativas').value = '';
    document.getElementById('fEstadoHorasAdministrativas').value = '0';
    
    // NO limpiar los archivos seleccionados aquí
    // document.getElementById('archivosHoras').value = '';
    
    document.getElementById('listaDocumentos').innerHTML = '<div class="list-group-item text-muted">No hay documentos subidos</div>';
    toggleFormularioHorasAdministrativas(false);
}
function limpiarCompletamente() {
    limpiarHorasAdministrativas();
    // Limpiar archivos seleccionados solo cuando sea necesario
    document.getElementById('archivosHoras').value = '';
    document.getElementById('archivosSeleccionados').innerHTML = '';
}
function AccionHorasAdministrativas() {
    let accion = $('#AccionHorasAdministrativas').val();
    const now = new Date();
    const formattedDate = now.toISOString().slice(0, 16);
    
    if (validarHorasAdministrativas()) {
        if (accion === "Ingresar") {
            // Para creación: primero crear el registro, luego subir archivos
            crearRegistroYSubirArchivos();
        } else if (accion === "Actualizar") {
            // Para actualización: actualizar registro y luego subir archivos
            actualizarRegistroYSubirArchivos();
        }
    }
}

function crearRegistroYSubirArchivos() {
    const now = new Date();
    const formattedDate = now.toISOString().slice(0, 16);
    
    var HorasAdministrativas = {
        IdHorasAdministrativas: '0',
        IdMedico: $.trim($('#fIdMedico').val()),
        NumeroHorasAdministrativas: $.trim($('#fNumeroHorasAdministrativas').val()),
        ObsHorasAdministrativas: $.trim($('#fObsHorasAdministrativas').val()),
        FechaHorasAdministrativas: formattedDate,
        EstadoHorasAdministrativas: '0'
    };

    Swal.fire({
        title: 'Creando registro...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Primero crear el registro
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=Ingresar',
        data: HorasAdministrativas,
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result && result.lastInsertId) {
                    const nuevoId = result.lastInsertId;
                    $('#fIdHorasAdministrativas').val(nuevoId);
                    
                    // Ahora subir los archivos si hay
                    const archivos = document.getElementById('archivosHoras').files;
                    if (archivos.length > 0) {
                        subirArchivosDespuesDeCrear(nuevoId);
                    } else {
                        Swal.close();
                        Swal.fire({
                            title: "¡Éxito!",
                            text: "Registro creado correctamente",
                            icon: "success",
                            customClass: { popup: 'swal2-dark' }
                        }).then(() => {
                            recargarTablaYCerrarModal();
                        });
                    }
                } else {
                    throw new Error('No se recibió ID del registro creado');
                }
            } catch (error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al crear el registro: ' + error.message,
                    customClass: { popup: 'swal2-dark' }
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la transacción: ' + error,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}
function subirArchivosDespuesDeCrear(idHoras) {
    const archivos = document.getElementById('archivosHoras').files;
    const formData = new FormData();
    formData.append('IdHorasAdministrativas', idHoras);
    
    for (let i = 0; i < archivos.length; i++) {
        formData.append('archivos[]', archivos[i]);
    }

    $.ajax({
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=SubirDocumento',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.close();
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (data.success) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Registro creado y documentos subidos correctamente",
                    icon: "success",
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    recargarTablaYCerrarModal();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Registro creado con advertencias',
                    html: 'El registro se creó pero hubo problemas con los archivos:<br>' + 
                          (data.errores ? data.errores.join('<br>') : 'Error desconocido'),
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    recargarTablaYCerrarModal();
                });
            }
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'warning',
                title: 'Registro creado con advertencias',
                text: 'El registro se creó pero hubo errores al subir los archivos',
                customClass: { popup: 'swal2-dark' }
            }).then(() => {
                recargarTablaYCerrarModal();
            });
        }
    });
}



function actualizarRegistroYSubirArchivos() {
    const now = new Date();
    const formattedDate = now.toISOString().slice(0, 16);
    const idExistente = $('#fIdHorasAdministrativas').val();
    
    var HorasAdministrativas = {
        IdHorasAdministrativas: idExistente,
        IdMedico: $.trim($('#fIdMedico').val()),
        NumeroHorasAdministrativas: $.trim($('#fNumeroHorasAdministrativas').val()),
        FechaHorasAdministrativas: formattedDate,
        EstadoHorasAdministrativas: $('#fEstadoHorasAdministrativas').val(),
        ObsHorasAdministrativas: $.trim($('#fObsHorasAdministrativas').val())
    };

    Swal.fire({
        title: 'Actualizando registro...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Primero actualizar el registro
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=Actualizar',
        data: HorasAdministrativas,
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result && result.status === 'success') {
                    // Ahora subir los archivos nuevos si hay
                    const archivos = document.getElementById('archivosHoras').files;
                    if (archivos.length > 0) {
                        subirArchivosDespuesDeActualizar(idExistente);
                    } else {
                        Swal.close();
                        Swal.fire({
                            title: "¡Éxito!",
                            text: "Registro actualizado correctamente",
                            icon: "success",
                            customClass: { popup: 'swal2-dark' }
                        }).then(() => {
                            recargarTablaYCerrarModal();
                        });
                    }
                } else {
                    throw new Error(result.message || 'Error al actualizar');
                }
            } catch (error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al actualizar el registro: ' + error.message,
                    customClass: { popup: 'swal2-dark' }
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la transacción: ' + error,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

function subirArchivosDespuesDeActualizar(idHoras) {
    const archivos = document.getElementById('archivosHoras').files;
    const formData = new FormData();
    formData.append('IdHorasAdministrativas', idHoras);
    
    for (let i = 0; i < archivos.length; i++) {
        formData.append('archivos[]', archivos[i]);
    }

    $.ajax({
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=SubirDocumento',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.close();
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (data.success) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Registro y documentos actualizados correctamente",
                    icon: "success",
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    recargarTablaYCerrarModal();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Registro actualizado con advertencias',
                    html: 'El registro se actualizó pero hubo problemas con los archivos:<br>' + 
                          (data.errores ? data.errores.join('<br>') : 'Error desconocido'),
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    recargarTablaYCerrarModal();
                });
            }
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'warning',
                title: 'Registro actualizado con advertencias',
                text: 'El registro se actualizó pero hubo errores al subir los archivos',
                customClass: { popup: 'swal2-dark' }
            }).then(() => {
                recargarTablaYCerrarModal();
            });
        }
    });
}

function recargarTablaYCerrarModal() {
    if ($.fn.DataTable.isDataTable('#tablaHorasAdministrativas')) {
        table.ajax.reload(null, false);
    }
    $('#ModalIngresoHorasAdministrativas').modal('hide');
    
    // Limpiar completamente después de guardar exitosamente
    limpiarCompletamente();
}


function CRUDHorasAdministrativas(accion, objEvento) {
    $.ajax({
        type: 'POST',
        url: '../Controlador/ControladorHorasAdministrativas.php?Accion=' + accion,
        data: objEvento,
        success: function(msg) {
            if (msg) {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "Transacción realizada con éxito!",
                    icon: "success",
                    customClass: { popup: 'swal2-dark' }
                }).then(() => {
                    if ($.fn.DataTable.isDataTable('#tablaHorasAdministrativas')) {
                        table.ajax.reload(null, false);
                    }
                    $('#ModalIngresoHorasAdministrativas').modal('hide');
                });
            }
        },
        error: function(req, textStatus, errorThrown) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la transacción: ' + textStatus,
                customClass: { popup: 'swal2-dark' }
            });
        }
    });
}

// Agrega este código al final de tu JavaScript
$(document).ready(function() {
    table = ingresarHorasAdministrativas();
    
    // Manejar el cierre del modal
    $('#ModalIngresoHorasAdministrativas').on('hidden.bs.modal', function (e) {
        // Solo limpiar completamente si es un nuevo registro (ID = 0)
        if ($('#fIdHorasAdministrativas').val() === '0') {
            limpiarCompletamente();
        } else {
            // Para edición, mantener los archivos seleccionados
            // pero limpiar otros campos
            limpiarHorasAdministrativas();
        }
    });
});

document.getElementById('archivosHoras').addEventListener('change', function() {
    let lista = document.getElementById('archivosSeleccionados');
    
    if (this.files.length === 0) {
        lista.innerHTML = '<span class="text-muted">No se han seleccionado archivos</span>';
    } else {
        let html = '<div class="selected-files"><strong>Archivos a subir:</strong><ul class="mb-0 mt-1">';
        for (let i = 0; i < this.files.length; i++) {
            const tamaño = formatFileSize(this.files[i].size);
            const icono = obtenerIconoDocumento(this.files[i].type);
            html += `<li><i class="${icono} me-1"></i> ${this.files[i].name} <small class="text-muted">(${tamaño})</small></li>`;
        }
        html += '</ul></div>';
        lista.innerHTML = html;
    }
});
