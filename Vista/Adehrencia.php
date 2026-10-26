<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioArea.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Adherencia Médica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../Vista/css/AppAdeherencia.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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

        if(strpos($_SESSION['TipoRol'], 'Asistencial GPC') === false || strpos($_SESSION['TipoRol'], 'Asistencial DHC') === false){
            header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
        }

    }
    include "../Vista/Menu.php";

    ?>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">Dashboard de Adherencia Médica</h1>
        
        <!-- Filtros -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4">
                    <label for="tipoAdherencia" class="form-label">Tipo de Adherencia</label>
                    <select id="tipoAdherencia" class="form-select">
                        <option value="GPC">Guías de Práctica Clínica (GPC)</option>
                        <option value="DHC">Dilegenciamiento de Historia Clinica (DHC)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtroMes" class="form-label">Mes</label>
                    <select id="filtroMes" class="form-select">
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtroAnio" class="form-label">Año</label>
                    <select id="filtroAnio" class="form-select">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button id="btnFiltrar" class="btn btn-primary">Filtrar</button>
                    <button id="btnReset" class="btn btn-secondary">Restablecer</button>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title">Promedio General</h5>
                    </div>
                    <div class="card-body text-center">
                        <h2 id="promedioGeneral" class="display-4">0%</h2>
                        <div class="progress mt-3" style="height: 20px;">
                            <div id="barraPromedio" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title">Máxima Adherencia</h5>
                    </div>
                    <div class="card-body">
                        <h4 id="maxAdherencia" class="text-center">0%</h4>
                        <p id="medicoMax" class="text-center">-</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title">Mínima Adherencia</h5>
                    </div>
                    <div class="card-body">
                        <h4 id="minAdherencia" class="text-center">0%</h4>
                        <p id="medicoMin" class="text-center">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title">Distribución de Adherencia</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="graficaBarras"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title">Tendencia anual</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="graficaLineas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de datos -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title">Detalle por Médico</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaAdherencia" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Especialidad</th>
                                        <th>Adherencia</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se llenará dinámicamente con JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="js/AppAdeherencia.js"></script>
</body>
</html>s