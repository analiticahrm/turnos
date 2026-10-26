<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Language" content="es">
    <meta name="google" content="notranslate">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/FormularioHorasAdministrativas.css?v1.4">
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Horas Administrativas</title>
</head>
<body>
    <?php
    session_start();
    $DocumentoUsuario = $_SESSION['DocumentoUsuario'];
    $NombreUsuario = $_SESSION['NombreUsuario'];

    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'rol';
    $_SESSION['Menu'] = '1';

    if (strpos('' . $_SESSION['TipoRol'], 'Administrador') !== false) {
        if (strpos('' . $_SESSION['TipoRol'], 'Asistencial') !== false) {
            header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
        }
    }
    include "../Vista/Menu.php";
    ?>

<div id="ModalIngresoHorasAdministrativas" class="modal fade" tabindex="-1" aria-labelledby="exampleModalLabelHorasAdministrativas" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Cambiado a modal-lg para más espacio -->
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title" id="exampleModalLabelHorasAdministrativas">Registrar Horas Administrativas</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fa-xs"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fIdHorasAdministrativas" value="0">
                <input type="hidden" id="fDocumentoMedico" value="0">
                <input type="hidden" id="fIdMedico" value="0">
                <input type="hidden" id="fEstadoHorasAdministrativas" value="0">
                
                <div class="info-item">
                    <p id="nombre" class="info-value"></p>
                </div>
                <br>
                
                <label>Fecha y Hora de Registro:</label>
                <p id="fFechaHorasAdministrativas" class="form-control-static"></p><br>
                
                <label>Número de Horas:</label>
                <input required class="form-control" type="number" id="fNumeroHorasAdministrativas" min="0" step="0.5" placeholder="Horas administrativas"><br>
                <label>Observaciones:</label>
                <textarea class="form-control" id="fObsHorasAdministrativas" rows="3" placeholder="Observaciones"></textarea><br>

                <!-- SECCIÓN DE DOCUMENTOS -->
                <div class="documentos-section">
                    <label>Adjuntar documentos:</label>
                    <!-- Formulario para subir documentos -->
                    <div class="upload-section mb-3">
                        
                    <input type="file" id="archivosHoras" class="d-none" multiple 
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif" hidden>

                    <!-- Botón personalizado -->
                    <label for="archivosHoras" class="adjuntos">
                        <i class="fa-solid fa-paperclip"></i> Seleccionar documentos
                    </label>

                    <!-- Aquí se mostrarán los archivos seleccionados -->
                    <div id="archivosSeleccionados" class="mt-2 text-muted"></div>
                        <br>
                        <small class="form-text text-muted">
                            Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes (máx. 10MB cada uno)
                        </small>

                    </div>
                    <br>                    
                    <!-- Lista de documentos -->
                    <div class="documentos-list">
                        <h6>Documentos subidos:</h6>
                        <div id="listaDocumentos" class="list-group"></div>
                    </div>
                </div>
                <!-- FIN SECCIÓN DOCUMENTOS -->
                
                <div class="modal-footer">
                    <button id="btnGuardarHorasAdministrativas" onclick="AccionHorasAdministrativas()" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <input type="text" name="fEnviar" style="visibility: hidden;" id="AccionHorasAdministrativas" value="">
                    <input type="reset" value="Limpiar" class="btn btn-info" title="Limpiar Formulario" style="visibility: hidden;">
                </div>
            </div>
        </div>
    </div>
</div>

    <div id="main-container">
        <div class="responsive-div">
            <?php
            if (strpos('' . $_SESSION['TurnosRol'], 'C') === false) {
            } else {
                echo '<button type="button" class="action_btn22" style="margin-left: 0cm;display:flex;align-items: center;height: 100%;" title="Agregar Registro" data-open-modal onclick="CrearHorasAdministrativas()">
                    <i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>
                    Agregar Horas Administrativas
                </button>';
            }
            ?>
            <div class="FondoTabla">
                <table id="tablaHorasAdministrativas" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th scope="col">Médico</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Horas</th>
                            <th scope="col">Observaciones</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <br><br>
    <?php
    $conexion = null;
    $objetoConexion = null;
    ?>
    <script src="js/FormularioHorasAdministrativas.js?v=2.3"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <footer>
        <div>Copyright © Your Website <?php echo "20" . date("y"); ?></div>
    </footer>
    <script>
        var DocumentoUsuario = "<?php echo $DocumentoUsuario; ?>";
        var NombreUsuario = "<?php echo $NombreUsuario; ?>";
    </script>
</body>
</html>