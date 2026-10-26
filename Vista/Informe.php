<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Font Awesome 6 (gratuito) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.5/locales/es.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
    <link rel="stylesheet" href="css/Informe.css?v=4.2">
    <title>Visualizador de Agendas Médicas</title>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    if (strpos($_SESSION['AreaRol'] ?? '', 'R') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    include "../Vista/Menu.php";
    include_once("../Modelo/ConexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();
    
    include_once("../Modelo/Area.php");
    $objetoArea = new Area($conexion, 0, '', '', '');
    $listarAreafull = $objetoArea->listarfull();

    ?>

    <!-- ===== HEADER ===== -->
    <header class="header-informe">
        <div class="container">
            <div class="header-content">
                <div class="header-title">
                    <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                    <div>
                        <h1>Agendas Médicas</h1>
                        <div class="subtitle">Visualización y análisis de agenda médica</div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../Vista/InformeDiario.php" class="btn-report">
                        <i class="fas fa-file-alt"></i> Reporte Diario
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== FILTROS ===== -->
    <section class="filtros-section">
        <div class="filtros-card">
            <form id="formFiltro">
                <div class="filtros-grid">
                    <div class="form-group">
                        <label for="fechainicio"><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
                        <input type="date" id="fechainicio" name="fechainicio" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="fechafin"><i class="fas fa-calendar-check"></i> Fecha Fin</label>
                        <input type="date" id="fechafin" name="fechafin" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="area"><i class="fas fa-hospital"></i> Especialidad</label>
                        <select id="area" name="area" class="form-control">
                            <option value="">Todas las especialidades</option>
                            <?php while ($unRegistro = $listarAreafull->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $unRegistro['NombreArea'] ?>" data-id="<?= $unRegistro['IdArea'] ?>">
                                    <?= $unRegistro['NombreArea'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="medico"><i class="fas fa-user-md"></i> Médico</label>
                        <select id="medico" name="medico" class="form-control">
                            <option value="">Todos los médicos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo_actividad"><i class="fas fa-tasks"></i> Actividad</label>
                        <select id="tipo_actividad" name="tipo_actividad" class="form-control">
                            <option value="">Todas las actividades</option>
                            <option value="1">Agenda</option>
                            <option value="2">Disponibilidad</option>
                            <option value="3">Consulta Externa</option>
                            <option value="4">Jornada CX</option>
                            <option value="5">Administrativo</option>
                            <option value="6">Evento</option>
                            <option value="7">Otro</option>
                        </select>
                    </div>
                                        
                    
                    <div class="filtros-actions">
                        <button type="button" class="btn-filtrar" onclick="filtrar()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                    
                    
                     </div>                    
                    <!-- En la sección de filtros, agrega este grupo -->
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Nomenclaturas</label>
                        <div class="nomenclatura-container" id="nomenclaturaContainer">
                            <div class="nomenclatura-search">
                                <input type="text" id="buscarNomenclatura" class="form-control" placeholder="Buscar nomenclatura..." style="margin-bottom: 8px;">
                            </div>
                            <div class="nomenclatura-grid" id="nomenclaturaGrid">
                                <!-- Aquí se cargarán dinámicamente los checkboxes -->
                                <div class="text-muted" style="padding: 10px; text-align: center;">
                                    <i class="fas fa-info-circle"></i> Seleccione un área para ver las nomenclaturas
                                </div>
                            </div>
                            <div class="nomenclatura-actions" style="margin-top: 8px;">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodasNomenclaturas(true)">
                                    <i class="fas fa-check-double"></i> Seleccionar todas
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="seleccionarTodasNomenclaturas(false)">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                                <span class="badge badge-info" id="contadorNomenclaturas" style="float: right; margin-top: 5px;">
                                    0 seleccionadas
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    
               
            </form>
        </div>
    </section>

    <!-- ===== DASHBOARD ===== -->
    <div class="dashboard-stats" id="dashboardStats" style="display:none;">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-value" id="totalTurnos">0</div>
                <div class="stat-label">Total Agendas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="stat-value" id="totalHoras">0</div>
                <div class="stat-label">Horas Totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-user-md"></i></div>
            <div class="stat-info">
                <div class="stat-value" id="totalMedicos">0</div>
                <div class="stat-label">Médicos Activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-list-ul"></i></div>
            <div class="stat-info">
                <div class="stat-value" id="totalActividades">0</div>
                <div class="stat-label">Actividades</div>
            </div>
        </div>
    </div>

    <!-- ===== RESUMEN HORAS ===== -->
    <div class="resumen-horas" id="resumenHoras" style="display:none;">
        <div class="resumen-card">
            <div class="resumen-header">
                <h4><i class="fas fa-hourglass-half"></i> Resumen de Horas por Actividad</h4>
                <span style="font-size:0.8rem; color:rgba(255,255,255,0.4);">Total general</span>
            </div>
            <div class="resumen-grid" id="contenidoResumen"></div>
        </div>
    </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="main-content" id="mainContent" style="display:none;">
        <!-- Tabla -->
        <div class="content-panel">
            <div class="panel-header">
                <h3><i class="fas fa-table"></i> Detalle de Agendas</h3>
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="badge-count" id="rowCount">0 registros</span>
                    <button type="button" class="btn-exportar" id="btnExportarExcel" onclick="exportarExcel()" disabled>
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>
            </div>
            <div class="table-wrap">
                <div id="resultados"></div>
            </div>
        </div>

        <!-- Calendario -->
        <div class="content-panel">
            <div class="panel-header">
                <h3><i class="fas fa-calendar-alt"></i> Calendario</h3>
                <span style="font-size:0.75rem; color:rgba(255,255,255,0.4);">Vista mensual</span>
            </div>
            <div class="calendar-wrapper">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- ===== ESTADO VACÍO ===== -->
    <div class="empty-state" id="emptyState">
        <div class="empty-icon"><i class="fas fa-search" style="font-size:4rem; color:#4a5568;"></i></div>
        <h4>Sin resultados</h4>
        <p>Selecciona un rango de fechas y aplica el filtro para visualizar las agendas.</p>
    </div>

    <footer>
        <div>Copyright &copy; Your Website <?= date('Y') ?></div>
    </footer>

    <script src="js/Informe.js?v=2.0"></script>
    <script>
        $(document).ready(function() {
            $('#area, #medico, #tipo_actividad').select2({
                placeholder: "Seleccione una opción",
                allowClear: true,
                dropdownAutoWidth: true,
                width: '100%'
            });

            $('#area').on('change', function() {
                var area = $(this).val();
                cargarMedicos(area, '');
            });
        });

        function limpiarFiltros() {
            document.getElementById('formFiltro').reset();
            $('#area, #medico, #tipo_actividad').val(null).trigger('change');
            document.getElementById('resultados').innerHTML = '';
            document.getElementById('resumenHoras').style.display = 'none';
            document.getElementById('mainContent').style.display = 'none';
            document.getElementById('dashboardStats').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';

            datosFiltrados = [];
            document.getElementById('btnExportarExcel').disabled = true;

            if (window.calendar) {
                window.calendar.removeAllEvents();
                window.calendar.render();
            }
        }
    </script>
</body>
</html>