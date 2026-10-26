<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <title>Visualizador de Turnos Médicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Librería para exportar a Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="../Vista/css/InformeDiario.css?v=1.4">
</head>
<body>
<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    
    $IdRol = $_SESSION['IdRol'];
    $rolesPermitidos = [1, 6];
    $mostrarBotonInforme = in_array($IdRol, $rolesPermitidos);
    
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['MedicoRol'], 'R') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    include "../Vista/Menu.php"; 
?>
    
    <div class="headerInforme">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-calendar-alt me-2"></i> Visualizador de Agendas Médicas</h1>
                    <p class="mb-0">Consulta las agendas por día y especialidad médica</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="date-picker-container">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <input type="text" class="form-control" id="fechaSeleccionada" placeholder="Seleccione una fecha">
                </div>
                <div class="col-md-2">
                    <button id="btnHoy" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-day me-1"></i> Hoy
                    </button>
                    <button id="btnManana" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-forward me-1"></i> Mañana
                    </button>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Buscar por médico, área o código...">
                    </div>
                </div>
                <div class="col-md-5">
                    <button id="btnActualizar" class="btn btn-primary me-2">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                    <!-- NUEVO BOTÓN DE EXPORTACIÓN A EXCEL -->
                    <button id="btnExportarExcel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                    
                  <?php if ($mostrarBotonInforme): ?>
                        <button class="btn btn-outline-secondary" onclick="window.location.href='../Vista/Informe.php'">
                            <i class="fas fa-file-alt me-1"></i> Informe Mensual
                        </button>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando agendas...</p>
        </div>

        <div class="empty-state" id="emptyState">
            <i class="fas fa-calendar-times fa-3x mb-3" style="color: var(--text-secondary);"></i>
            <h4>No hay datos para mostrar</h4>
            <p>Seleccione una fecha para visualizar las agendas médica</p>
        </div>

        <div id="turnosContainer" class="row g-3"></div>
    </div>

    <!-- Modal para detalles del turno -->
    <div class="modal fade" id="detalleTurnoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Detalles de la agenda</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleTurnoContent">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración del datepicker
            const fechaPicker = flatpickr("#fechaSeleccionada", {
                locale: "es",
                dateFormat: "Y-m-d",
                defaultDate: new Date(),
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        cargarTurnos(dateStr);
                    }
                }
            });

            let turnosOriginales = []; // Almacenar los turnos originales
            let fechaActual = null;    // Almacenar la fecha actual para el nombre del archivo

            // Cargar turnos para la fecha actual al iniciar
            cargarTurnos(fechaPicker.selectedDates[0]);

            // Eventos de los botones
            document.getElementById('btnHoy').addEventListener('click', function() {
                fechaPicker.setDate(new Date());
                cargarTurnos(fechaPicker.selectedDates[0]);
            });

            document.getElementById('btnManana').addEventListener('click', function() {
                const manana = new Date();
                manana.setDate(manana.getDate() + 1);
                fechaPicker.setDate(manana);
                cargarTurnos(fechaPicker.selectedDates[0]);
            });

            document.getElementById('btnActualizar').addEventListener('click', function() {
                if (fechaPicker.selectedDates.length > 0) {
                    cargarTurnos(fechaPicker.selectedDates[0]);
                }
            });

            // NUEVO: Evento para exportar a Excel
            document.getElementById('btnExportarExcel').addEventListener('click', function() {
                exportarAExcel();
            });

            // Evento para el campo de búsqueda
            document.getElementById('searchInput').addEventListener('input', function() {
                const query = this.value.toLowerCase();
                filtrarTurnos(query);
            });

            // Función para cargar los turnos
            function cargarTurnos(fecha) {
                const fechaStr = formatDate(fecha);
                fechaActual = fechaStr; // Guardamos la fecha actual
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('turnosContainer').innerHTML = '';
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('searchInput').value = ''; // Limpiar búsqueda

                fetchTurnos(fechaStr)
                    .then(data => {
                        document.getElementById('loadingSpinner').style.display = 'none';
                        turnosOriginales = data || []; // Guardar los turnos originales
                        if (turnosOriginales.length > 0) {
                            mostrarTurnos(turnosOriginales);
                        } else {
                            document.getElementById('emptyState').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        document.getElementById('loadingSpinner').style.display = 'none';
                        console.error('Error al cargar turnos:', error);
                        alert('Error al cargar los turnos. Por favor, intente nuevamente.');
                    });
            }

            // Función para filtrar los turnos
            function filtrarTurnos(query) {
                const turnosFiltrados = turnosOriginales.filter(turno => {
                    return (
                        turno.NombreMedico.toLowerCase().includes(query) ||
                        turno.NombreArea.toLowerCase().includes(query) ||
                        turno.CodNomenclatura.toLowerCase().includes(query)
                    );
                });

                document.getElementById('turnosContainer').innerHTML = '';
                if (turnosFiltrados.length > 0) {
                    mostrarTurnos(turnosFiltrados);
                    document.getElementById('emptyState').style.display = 'none';
                } else {
                    document.getElementById('emptyState').style.display = 'block';
                }
            }

            // NUEVA FUNCIÓN: Exportar los turnos actuales (originales o filtrados) a Excel
            function exportarAExcel() {
                // Decidimos si exportar los datos filtrados o todos los del día.
                // Por defecto, exportamos lo que el usuario está viendo en ese momento (filtrados).
                // Para obtener los datos VISUALIZADOS actualmente, podemos leer el contenedor o usar una variable.
                // Usaremos los turnos originales filtrándolos con el texto de búsqueda actual.
                const query = document.getElementById('searchInput').value.toLowerCase();
                let datosAExportar = turnosOriginales;
                
                if (query !== '') {
                    datosAExportar = turnosOriginales.filter(turno => {
                        return (
                            turno.NombreMedico.toLowerCase().includes(query) ||
                            turno.NombreArea.toLowerCase().includes(query) ||
                            turno.CodNomenclatura.toLowerCase().includes(query)
                        );
                    });
                }

                if (!datosAExportar || datosAExportar.length === 0) {
                    alert("No hay turnos para exportar en la fecha seleccionada.");
                    return;
                }

                // Preparamos los datos para la hoja de cálculo
                const datosExcel = datosAExportar.map(turno => ({
                    "ID Turno": turno.IdTurno,
                    "Médico": turno.NombreMedico,
                    "Documento": turno.DocumentoMedico,
                    "Especialidad": turno.NombreArea,
                    "Código Nomenclatura": turno.CodNomenclatura,
                    "Actividad": turno.NombreActividad,
                    "Descripción": turno.Nombre || '',
                    "Fecha Inicio": turno.FechaHoraInicio,
                    "Fecha Fin": turno.FechaHoraFin,
                    "Estado": turno.EstadoTurno === 1 ? "Activo" : "Inactivo"
                }));

                // Crear un libro de trabajo y una hoja
                const libro = XLSX.utils.book_new();
                const hoja = XLSX.utils.json_to_sheet(datosExcel);
                
                // Ajustar el ancho de las columnas (opcional, mejora la estética)
                const columnasAncho = [
                    {wch:10}, // ID
                    {wch:35}, // Médico
                    {wch:15}, // Documento
                    {wch:25}, // Especialidad
                    {wch:20}, // Código
                    {wch:25}, // Actividad
                    {wch:40}, // Descripción
                    {wch:20}, // Fecha Inicio
                    {wch:20}, // Fecha Fin
                    {wch:10}  // Estado
                ];
                hoja['!cols'] = columnasAncho;

                // Agregar la hoja al libro
                XLSX.utils.book_append_sheet(libro, hoja, "Agendas del día");
                
                // Generar el nombre del archivo con la fecha actual
                let nombreArchivo = `agendas_${fechaActual}.xlsx`;
                if (query !== '') {
                    nombreArchivo = `agendas_filtradas_${fechaActual}.xlsx`;
                }
                
                // Exportar el archivo
                XLSX.writeFile(libro, nombreArchivo);
            }

            // Función para formatear la fecha (YYYY-MM-DD)
            function formatDate(date) {
                const d = new Date(date);
                let month = '' + (d.getUTCMonth() + 1);
                let day = '' + d.getUTCDate();
                const year = d.getUTCFullYear();
            
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
            
                return [year, month, day].join('-');
            }

            // Función para mostrar los turnos en la interfaz (sin cambios relevantes)
            function mostrarTurnos(turnos) {
                const container = document.getElementById('turnosContainer');
                container.innerHTML = '';

                // Agrupar turnos por área
                const turnosPorArea = {};
                turnos.forEach(turno => {
                    if (!turnosPorArea[turno.NombreArea]) {
                        turnosPorArea[turno.NombreArea] = [];
                    }
                    turnosPorArea[turno.NombreArea].push(turno);
                });

                // Crear tarjetas por área
                for (const area in turnosPorArea) {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6 col-lg-4';

                    const card = document.createElement('div');
                    card.className = 'card card-area h-100';

                    // Header de la tarjeta
                    const cardHeader = document.createElement('div');
                    cardHeader.className = 'card-header card-header-area d-flex justify-content-between align-items-center';
                    cardHeader.innerHTML = `
                        <span><i class="fas fa-clinic-medical me-2"></i>${area}</span>
                        <span class="badge">${turnosPorArea[area].length} agendas</span>
                    `;

                    // Cuerpo de la tarjeta
                    const cardBody = document.createElement('div');
                    // Ordenar turnos por hora de inicio
                    turnosPorArea[area].sort((a, b) => {
                        return new Date(a.FechaHoraInicio) - new Date(b.FechaHoraInicio);
                    });

                    // Agregar cada turno
                    turnosPorArea[area].forEach(turno => {
                        const turnoElement = document.createElement('div');
                        turnoElement.className = 'turno-item d-flex justify-content-between align-items-center';
                        turnoElement.innerHTML = `
                            <div>
                                <span class="cod-nomenclatura">${turno.CodNomenclatura}</span>
                                <span class="hora-turno">${formatHora(turno.FechaHoraInicio)} - ${formatHora(turno.FechaHoraFin)}</span>
                                <div class="nombre-medico mt-1">${turno.NombreMedico}</div>
                                <div class="tipo-actividad">${turno.NombreActividad}</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary btn-detalle" data-id="${turno.IdTurno}">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        `;
                        cardBody.appendChild(turnoElement);
                    });

                    card.appendChild(cardHeader);
                    card.appendChild(cardBody);
                    col.appendChild(card);
                    container.appendChild(col);
                }

                // Agregar event listeners a los botones de detalle
                document.querySelectorAll('.btn-detalle').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const turnoId = this.getAttribute('data-id');
                        mostrarDetalleTurno(turnoId, turnos);
                    });
                });
            }

            // Función para formatear la hora
            function formatHora(fechaHora) {
                const date = new Date(fechaHora);
                return date.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
            }

            // Función para mostrar detalles del turno en modal
            function mostrarDetalleTurno(turnoId, turnos) {
                const turno = turnos.find(t => t.IdTurno == turnoId);
                if (!turno) return;

                const modalContent = document.getElementById('detalleTurnoContent');
                modalContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Información de la agenda</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Médico:</span>
                                    <span>${turno.NombreMedico}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Documento:</span>
                                    <span>${turno.DocumentoMedico}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Especialidad:</span>
                                    <span>${turno.NombreArea}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Nomenclatura:</span>
                                    <span class="badge bg-primary">${turno.CodNomenclatura}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Horario</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Fecha:</span>
                                    <span>${formatFecha(turno.FechaHoraInicio)}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Hora inicio:</span>
                                    <span>${formatHora(turno.FechaHoraInicio)}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Hora fin:</span>
                                    <span>${formatHora(turno.FechaHoraFin)}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold">Duración:</span>
                                    <span>${calcularDuracion(turno.FechaHoraInicio, turno.FechaHoraFin)}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Actividad</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6>${turno.NombreActividad}</h6>
                                    <p class="mb-0">${turno.Nombre || 'No hay descripción adicional'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                const modal = new bootstrap.Modal(document.getElementById('detalleTurnoModal'));
                modal.show();
            }

            // Función para formatear la fecha
            function formatFecha(fechaHora) {
                const date = new Date(fechaHora);
                return date.toLocaleDateString('es-CO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }

            // Función para calcular duración entre horas
            function calcularDuracion(inicio, fin) {
                const start = new Date(inicio);
                const end = new Date(fin);
                const diff = end - start;

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                return `${hours}h ${minutes}m`;
            }

            // Función para obtener los turnos
            function fetchTurnos(fecha) {
                return $.ajax({
                    url: '../Controlador/ControladorTurno.php?Accion=ListarPorEspecialidades',
                    type: 'POST',
                    data: {
                        FechaHoraInicio: fecha + ' 00:00:00',
                        FechaHoraFin: fecha + ' 23:59:59'
                    },
                    dataType: 'json'
                }).then(function (response) {
                    if (response.error) {
                        throw new Error(response.message);
                    }
                    return response;
                }).catch(function (xhr, status, error) {
                    console.error('Error en la solicitud AJAX:', status, error);
                    throw new Error('Error en la solicitud AJAX: ' + error);
                });
            }
        });
    </script>
</body>
</html>