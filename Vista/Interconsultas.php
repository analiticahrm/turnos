<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">

    <title>Interconsultas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tiempos de Respuesta Médica</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../Vista/css/Interconsultas.css?v=3.1">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>

    </style>
</head>
<body>
<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['TurnosRol'], 'CRUD') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    include "../Vista/Menu.php"; 
    ?>
    
    <div class="container-fluid py-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Reporte de Tiempos de Respuesta Médica
                </h4>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-4 g-3">
                    <div class="col-md-3">
                        <label for="fechaInicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fechaInicio" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fechaFin" value="<?= date('Y-m-t') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="especialidadSelect" class="form-label">Especialidad</label>
                        <select class="form-select" id="especialidadSelect">
                            <option value="">Cargando especialidades...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="medicoSelect" class="form-label">Médico</label>
                        <select class="form-select" id="medicoSelect" disabled>
                            <option value="">Todos los médicos</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100 me-2" id="btnBuscar">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <button class="btn btn-primary w-100 me-2" id="btnExportarExcel" disabled>
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                    </div>
                </div>
                
                <!-- Resumen Estadístico -->
                <div class="row mb-4" id="resumenEstadistico">
                    <!-- Se llenará dinámicamente -->
                </div>
                
                <!-- Tabla de Resultados -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="reporteTable">
                        <thead class="table-dark">
                            <tr>
                                <th width="25%">Médico</th>
                                <th width="20%">Especialidad</th>
                                <th width="15%">Interconsultas</th>
                                <th width="20%">Tiempo Promedio</th>
                                <th width="20%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <tr class="textable">
                                <td colspan="5" class="text-center">Ingrese los filtros y haga clic en Buscar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal para Detalles -->
    <div class="modal fade" id="detalleModal" tabindex="-1" aria-labelledby="detalleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detalleModalLabel">Detalle de Interconsultas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetalleBody">
                    <div class="text-center my-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p>Cargando detalles...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Script personalizado -->
    <script src="js/Interconsultas.js?v=4.2"></script>
    <footer >
        <div>Copyright2 &copy; Your Website
                            <?php echo "20" . date("y"); ?>
                        </div>
</footer>
</body>
</html>