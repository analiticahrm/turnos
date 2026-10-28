<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación de Historias Clínicas</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../Vista/css/EvaluacionDHC.css?v3.0">

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

        if(strpos($_SESSION['TipoRol'], 'Asistencial DHC') === false){

            if(strpos($_SESSION['TipoRol'], 'Asistencial GPC') === false){
                header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
            }

        }

    }
    include "../Vista/Menu.php";
    ?>
    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-clipboard-check me-2"></i>Diligenciamiento de Historias Clínicas</h2>
        </div>

        <div class="card">
            <div class="flex-container" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="flex-item form-group" style="flex: 2; min-width: 260px;">
                    <label for="selectMedico">Médico / profesional</label>
                    <select id="selectMedico" class="form-control select2">
                        <option value="">Seleccione un profesional</option>
                    </select>
                </div>

                <div class="flex-item form-group" style="flex: 1; min-width: 160px;">
                    <label for="selectMonth">Mes</label>
                    <select id="selectMonth" class="form-control">
                        <option value="01">Enero</option>
                        <option value="02">Febrero</option>
                        <option value="03">Marzo</option>
                        <option value="04">Abril</option>
                        <option value="05">Mayo</option>
                        <option value="06">Junio</option>
                        <option value="07">Julio</option>
                        <option value="08">Agosto</option>
                        <option value="09">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                <div class="flex-item form-group" style="flex: 1; min-width: 120px;">
                    <label for="inputYear">Año</label>
                    <input type="number" id="inputYear" class="form-control" value="">
                </div>

                <div class="flex-item form-group" style="flex: 1; min-width: 140px; display: flex; align-items: flex-end;">
                    <button id="btnCargarCasos" class="btn btn-primary" style="width:100%;">Buscar casos</button>
                </div>
            </div>
        </div>

        <div id="casosContainer" class="card hidden">
            <div class="casos-header">
                <h2>Casos auditados este mes</h2>
                <button id="btnNuevoCaso" class="btn btn-success">+ Nuevo caso</button>
            </div>
            <div style="overflow-x: auto;">
                <table id="casosTable">
                    <thead>
                        <tr>
                            <th>Cédula paciente</th>
                            <th>Nombre</th>
                            <th>% Adherencia</th>
                            <th>Clasificación</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="casosTableBody"></tbody>
                </table>
                <p id="casosEmptyMsg" class="hidden" style="opacity:0.7; padding: 10px 0;">Aún no hay casos auditados para este profesional en el mes seleccionado.</p>
            </div>
        </div>

        <div id="formCasoContainer" class="hidden">
            <div class="card">
                <h2 id="formCasoTitle">Nuevo caso</h2>
                <div class="flex-container" style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 10px;">
                    <div class="flex-item form-group" style="flex: 1; min-width: 200px;">
                        <label for="inputCedula">Cédula del paciente</label>
                        <input type="text" id="inputCedula" class="form-control" placeholder="Número de identificación">
                    </div>
                    <div class="flex-item form-group" style="flex: 2; min-width: 240px;">
                        <label for="inputNombrePaciente">Nombre del paciente (opcional)</label>
                        <input type="text" id="inputNombrePaciente" class="form-control" placeholder="Nombre completo">
                    </div>
                </div>

                <div id="dominiosContainer"><!-- Acordeón de dominios generado dinámicamente --></div>

                <div class="form-group" style="margin-top: 20px;">
                    <label for="inputObservacionFinal" class="form-label">Observación final (opcional)</label>
                    <textarea id="inputObservacionFinal" class="form-control" rows="4" style="width: 100%; resize: vertical;" placeholder="Comentarios generales del auditor sobre este caso..."></textarea>
                </div>
            </div>

            <div class="card summary-card">
                <h2>Resumen de la evaluación</h2>
                <div id="resumenDominios" class="resumen-dominios"></div>
                <div class="overall-average">
                    <span id="overallPercent">0.00%</span>
                    <span id="overallClasificacion" class="clasificacion-badge">Sin calificar</span>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button id="btnGuardarCaso" class="btn btn-success">Guardar caso</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/EvaluacionDHC.js?v3.1"></script>
    </body>
</html>
