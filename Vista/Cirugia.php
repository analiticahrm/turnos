<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Honorarios Médicos</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.css/dist/chart.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/Cirugia.css?v=1.6">
    
</head>
<body>
<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }


    ?>
    <div class="menu-container">
        <?php include "../Vista/Menu.php"; ?>
    </div>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="bi bi-hospital"></i> Honorarios Médicos</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="#dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="#medicos"><i class="bi bi-people-fill"></i> Médicos</a></li>
                    <li><a href="#cirugias"><i class="bi bi-clipboard2-pulse"></i> Cirugías</a></li>
                    <li><a href="#cups"><i class="bi bi-clipboard2-pulse"></i> Cups</a></li>

                    
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name">Administrador</span>
                        <span class="user-role">Supervisor</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <header class="top-nav">
                <div class="nav-left">
                    <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                    <div class="breadcrumb">
                        <span>Dashboard</span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content-area" id="dashboard-content">
                <!-- Filters -->
                <div class="filters-card">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="fecha-inicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha-inicio">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha-fin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha-fin">
                        </div>
                        <div class="col-md-3">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <select class="form-select" id="especialidad">
                                <option value="">Todas</option>
                                <!-- Dinámico -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="eps" class="form-label">EPS</label>
                            <select class="form-select" id="eps">
                                <option value="">Todas</option>
                                <!-- Dinámico -->
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" id="aplicar-filtros"><i class="bi bi-funnel"></i> Aplicar Filtros</button>
                            <button class="btn btn-outline-secondary" id="limpiar-filtros"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row summary-cards">
                    <div class="col-md-3">
                        <div class="card card-primary">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div class="card-info">
                                    <h3 id="total-honorarios">$0</h3>
                                    <p>Total Honorarios</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-success">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="bi bi-person-video3"></i>
                                </div>
                                <div class="card-info">
                                    <h3 id="total-cirujanos">0</h3>
                                    <p>Cirujanos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-success">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="bi bi-person-video3"></i>
                                </div>
                                <div class="card-info">
                                    <h3 id="total-anestesiologos">0</h3>
                                    <p>Anestesiologos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-info">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="bi bi-clipboard2-pulse"></i>
                                </div>
                                <div class="card-info">
                                    <h3 id="total-cirugias">0</h3>
                                    <p>Cirugías</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-warning">
                            <div class="card-body">
                                <div class="card-icon">
                                    <i class="bi bi-hospital"></i>
                                </div>
                                <div class="card-info">
                                    <h3 id="total-eps">0</h3>
                                    <p>EPS</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row charts-row">
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h4>Honorarios por Especialidad</h4>
                                <div class="chart-actions">
                                    <button class="btn-chart-action"><i class="bi bi-download"></i></button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="chart-especialidad"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h4>Honorarios por EPS</h4>
                                <div class="chart-actions">
                                    <button class="btn-chart-action"><i class="bi bi-download"></i></button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="chart-eps"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Médicos -->
                <div class="card table-card">
                    <div class="card-header">
                        <h4>Top Médicos</h4>
                        <div class="card-actions">
                           
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="top-medicos-table">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Documento</th>
                                        <th>Especialidad</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinámico -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Médicos Content -->
            <div class="content-area hidden" id="medicos-content">
                <div class="card">
                    <div class="card-header">
                        <h4>Búsqueda de Médicos</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="buscar-medico" placeholder="Documento o nombre del médico">
                                    <button class="btn btn-primary" type="button" id="btn-buscar-medico">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-success" id="btn-exportar-medicos">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped" id="medicos-table">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Nombre</th>
                                        <th>Especialidad</th>
                                        <th>Teléfono</th>
                                        <th>Correo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinámico -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalle Médico Modal -->
            <div class="modal fade" id="medicoModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="medicoModalTitle">Detalle del Médico</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                        <!-- Información básica -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 id="medico-nombre"></h5>
                                <p><strong>Documento:</strong> <span id="medico-documento"></span></p>
                                <p><strong>Especialidad:</strong> <span id="medico-especialidad"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Teléfono:</strong> <span id="medico-telefono"></span></p>
                                <p><strong>Correo:</strong> <span id="medico-correo"></span></p>
                                <p><strong>Rol Principal:</strong> <span id="medico-rol" class="badge bg-primary"></span></p>
                            </div>
                        </div>
                        
                        <!-- Resumen de honorarios -->
                        <div class="row mb-4" id="honorarios-medico-container">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Resumen de Honorarios</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6>Rol Principal</h6>
                                                <p><strong>Total Honorarios:</strong> <span id="honorario-principal">$0.00</span></p>
                                                <p><strong>Total Cirugías:</strong> <span id="cirugias-principal">0</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráficos -->
                        <div class="row mb-4">
                            <div class="col-md-6" id="chart-medico-eps-container">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chart-medico-eps"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6" id="chart-medico-mensual-container">
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chart-medico-mensual"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de cirugías -->
                        <div class="row" id="cirugias-medico-container">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Cirugías Realizadas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped" id="cirugias-medico-table">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Paciente</th>
                                                        <th>EPS</th>
                                                        <th>Ingreso</th>
                                                        <th>Procedimiento</th>
                                                        <th>Grupo QX</th>
                                                        <th>Honorario</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" id="btn-imprimir-reporte">
                                <i class="bi bi-printer"></i> Imprimir Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cirugías Content -->
            <div class="content-area hidden" id="cirugias-content">
                <div class="card">
                    <div class="card-header">
                        <h4>Registro de Cirugías</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="cirugias-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>EPS</th>
                                        <th>Procedimiento</th>
                                        <th>Grupo QX</th>
                                        <th>Cirujano</th>
                                        <th>Anestesista</th>
                                        <th>Honorarios</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dinámico -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CUPS Content -->
            <div class="content-area hidden" id="cups-content">
                <div class="container">
                    <header class="main-header2">
                        <div class="header-content">
                            <h1><i class="fas fa-stethoscope logo"></i> Gestion de CUPS Médicos</h1>
                            <div class="header-actions">
                                <button class="btn btn-success" id="refreshBtn"><i class="fas fa-sync-alt"></i> Actualizar</button>
                            </div>
                        </div>
                    </header>
                    
                    <div class="main-content2">
                        <div class="form-section">
                            <h2 class="form-title"><i class="fas fa-plus-circle"></i> Agregar CUPS a Médico</h2>
                            <form id="cupsForm">
                                <div class="form-group">
                                    <label for="medico">Seleccionar Médico</label>
                                    <select id="medico" required>
                                        <option value="">-- Seleccione un médico --</option>
                                        <!-- Las opciones se cargarán dinámicamente -->
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cups">Código CUPS</label>
                                    <input type="text" id="cups" placeholder="Ingrese el código CUPS" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion">Descripción (opcional)</label>
                                    <input type="text" id="descripcion" placeholder="Descripción del procedimiento">
                                </div>
                                
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Guardar CUPS
                                </button>
                            </form>
                        </div>
                        
                        <div class="table-section">
                            <div class="table-header">
                                <h2><i class="fas fa-list"></i> Lista de CUPS Asignados</h2>
                                <div class="search-box">
                                    <input type="text" placeholder="Buscar CUPS..." id="searchInput">
                                    <button class="btn btn-primary" id="searchBtn"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            
                            <div class="table-container">
                                <table id="cupsTable">
                                    <thead class="table-dark">
                                        <tr class="table-dark">
                                            <th>Médico</th>
                                            <th>Código CUPS</th>
                                            <th>Descripción</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal para editar -->
                <div class="modal" id="editModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-edit"></i> Editar CUPS</h2>
                            <span class="close">&times;</span>
                        </div>
                        <form id="editForm">
                            <input type="hidden" id="editId">
                            <div class="form-group">
                                <label for="editMedico">Médico</label>
                                <select id="editMedico" disabled>
                                    <!-- Las opciones se cargarán dinámicamente -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="editCups">Código CUPS</label>
                                <input type="text" id="editCups" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="editDescripcion">Descripción</label>
                                <input type="text" id="editDescripcion">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Notificación -->
                <div class="notification" id="notification">
                    <i class="fas fa-check-circle"></i>
                    <span id="notificationText">Operación realizada con éxito</span>
                </div>

            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/Cirugia.js?v=1.9"></script>
    <script src="js/utils.js?v=1.2"></script>
    <script src="js/chart.js?v=1.2"></script>
    <script src="js/cups.js?v=1.3"></script>


</body>
</html>