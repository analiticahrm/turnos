<?php
require_once '../../includes/db.php';
require_once '../../includes/interconsultas.php';

$idMedico = $_GET['id_medico'] ?? null;
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

if (!$idMedico) {
    header('Location: lista_medicos.php');
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/Adeherencia.css">

    <!-- Asegúrate de incluir correctamente la librería de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>Detallado Interconsultas</title>
</head>
<body>

<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false || strpos($_SESSION['TipoRol'], 'Asistencial DHC') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }

    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();

    ?>
<h2>Detalle de Tiempos de Respuesta</h2>
<h3 id="nombre-medico">Cargando datos del médico...</h3>
<p id="periodo-texto">Período: <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?></p>

<a id="btn-volver" href="lista_medicos.php?fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn btn-secondary mb-3">
    Volver al listado
</a>

<div id="contenedor-detalle">
    <div class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p>Cargando detalles del médico...</p>
    </div>
</div>

<script>
$(document).ready(function() {
    // Cargar datos del médico
    cargarDetalleMedico(<?= json_encode($idMedico) ?>, <?= json_encode($fechaInicio) ?>, <?= json_encode($fechaFin) ?>);
});

function cargarDetalleMedico(idMedico, fechaInicio, fechaFin) {
    $.ajax({
        url: '../Controlador/ControaldorInterconsulta.php',
        type: 'POST',
        dataType: 'json',
        data: {
            Accion: 'ObtenerDetalleMedico',
            IdMedico: idMedico,
            FechaHoraInicio: fechaInicio,
            FechaHoraFin: fechaFin
        },
        success: function(response) {
            if (response && response.medico) {
                mostrarDetalleMedico(response.medico, fechaInicio, fechaFin);
            } else {
                $('#contenedor-detalle').html(`
                    <div class="alert alert-danger">
                        No se encontraron datos para este médico en el período seleccionado.
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            $('#contenedor-detalle').html(`
                <div class="alert alert-danger">
                    Error al cargar los detalles. Por favor, intente nuevamente.
                </div>
            `);
        }
    });
}

function mostrarDetalleMedico(medico, fechaInicio, fechaFin) {
    // Actualizar nombre y especialidad
    $('#nombre-medico').text(`${medico.nombre} - ${medico.especialidad}`);
    
    // Calcular tiempos para el resumen
    const horasPromedio = Math.floor(medico.promedio_minutos / 60);
    const minutosPromedio = Math.floor(medico.promedio_minutos % 60);
    
    const horasTotales = Math.floor(medico.total_minutos / 60);
    const minutosTotales = Math.floor(medico.total_minutos % 60);
    
    // Construir HTML
    let html = `
        <div class="card mb-4">
            <div class="card-header">
                <h4>Resumen</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5>Total Interconsultas</h5>
                                <p class="display-4">${medico.total_interconsultas}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5>Tiempo Promedio</h5>
                                <p class="display-4">
                                    ${horasPromedio}h ${minutosPromedio}m
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5>Tiempo Total</h5>
                                <p class="display-4">
                                    ${horasTotales}h ${minutosTotales}m
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h4>Detalle por Interconsulta</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fecha Solicitud</th>
                    <th>Fecha Respuesta</th>
                    <th>Tiempo Total</th>
                    <th>Turnos Involucrados</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Agregar cada interconsulta
    medico.interconsultas.forEach(ic => {
        const horasIC = Math.floor(ic.total_minutos / 60);
        const minutosIC = Math.floor(ic.total_minutos % 60);
        
        html += `
            <tr>
                <td>${formatearFechaHora(ic.inicio)}</td>
                <td>${formatearFechaHora(ic.fin)}</td>
                <td>${horasIC}h ${minutosIC}m</td>
                <td>
                    <ul>
        `;
        
        // Agregar cada turno
        ic.turnos.forEach(turno => {
            const horasTurno = Math.floor(turno.minutos / 60);
            const minutosTurno = Math.floor(turno.minutos % 60);
            
            html += `
                <li>
                    ${formatearFechaHora(turno.inicio_turno)} a 
                    ${formatearFechaHora(turno.fin_turno)}:
                    ${horasTurno}h ${minutosTurno}m
                </li>
            `;
        });
        
        html += `
                    </ul>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    $('#contenedor-detalle').html(html);
}

function formatearFechaHora(fechaHora) {
    const fecha = new Date(fechaHora);
    const dia = fecha.getDate().toString().padStart(2, '0');
    const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
    const anio = fecha.getFullYear();
    const horas = fecha.getHours().toString().padStart(2, '0');
    const minutos = fecha.getMinutes().toString().padStart(2, '0');
    
    return `${dia}/${mes}/${anio} ${horas}:${minutos}`;
}
</script>

</body>

</html>