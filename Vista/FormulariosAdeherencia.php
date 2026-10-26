<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Formularios y Evaluaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Vista/css/FormulariosAdeherencia.css?v1.2">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- jQuery (must be loaded before DataTables) -->
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
    
        $CrearGuias = strpos('' . $_SESSION['GPCRol'], 'C') !== false;
        $updateGuias = strpos('' . $_SESSION['GPCRol'], 'U') !== false;
        $deleteGuias = strpos('' . $_SESSION['GPCRol'], 'D') !== false;
        $IdUsuario = $_SESSION['IdUsuario'];
        $IdRol = $_SESSION['IdRol'];

         include_once("../Modelo/conexionPDO.php");
         $objetoConexion = new Conexion;
         $conexion = $objetoConexion->conectar();
    include_once("../Modelo/usuario.php");
    $objetoUsuario = new Usuario($conexion, 0, '', '', '', '', '', '');
    $listarUsuarioAdherencia = $objetoUsuario->listarPorUsuario($IdUsuario);

    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {

        if(strpos($_SESSION['TipoRol'], 'Asistencial GPC') === false){
            if($listarUsuarioAdherencia->rowCount() == 0){
                if (strpos($_SESSION['GPCRol'], 'C') === false) {
                    header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
                    exit();
                }
            }
        }
    }

    include "../Vista/Menu.php";

    ?>
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-clipboard-check me-2"></i>Gestión de Formularios y Evaluaciones</h2>
                <div>
                    <?php
                        if (strpos($_SESSION['TipoRol'], 'Administrador') !== false || strpos($_SESSION['TipoRol'], 'Asistencial GPC') !== false) {
                    ?>
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#gestionFormulariosModal">
                        <i class="fas fa-cog me-2"></i>Gestionar Formularios
                    </button>

                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createFormModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Formulario
                    </button>
                    <?php
                        }
                    ?>
                </div>
            </div>
        </div>

        <!-- Modal para gestión de formularios y usuarios -->
        <div class="modal fade" id="gestionFormulariosModal" tabindex="-1" aria-labelledby="gestionFormulariosModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-cog me-2"></i>Gestión de Formularios y Usuarios</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-list me-2"></i>Formularios</h5>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="filtroFormularios" placeholder="Filtrar formularios...">
                                </div>
                                <br>
                                <div id="listaFormularios" class="list-group" style="max-height: 400px; overflow-y: auto;">
                                    <!-- Los formularios se cargarán aquí -->
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-users me-2"></i>Asignar a Usuarios</h5>
                                <div id="detalleFormulario" class="d-none">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 id="nombreFormularioSeleccionado" class="card-title"></h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input toggle-estado" type="checkbox" id="toggleEstadoFormulario">
                                                <label class="form-check-label" for="toggleEstadoFormulario">Formulario Activo</label>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Seleccionar Usuarios</label>
                                                <select id="selectUsuarios" class="form-select usuario-select" multiple>
                                                    <!-- Los usuarios se cargarán aquí -->
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Usuarios Asignados</label>
                                                <div id="usuariosAsignados" class="usuarios-asignados">
                                                    <!-- Los usuarios asignados se mostrarán aquí -->
                                                </div>
                                            </div>
                                            
                                            <button id="btnAsignarUsuario" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus me-1"></i>Asignar Seleccionados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="sinSeleccion" class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>Seleccione un formulario para gestionar sus usuarios.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                        <button type="button" class="btn btn-success" id="guardarCambios">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para crear nuevo formulario -->
        <div class="modal fade" id="createFormModal" tabindex="-1" aria-labelledby="createFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Nuevo Formulario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createFormForm">
                            <div class="mb-3">
                                <label for="nombre_formulario" class="form-label">Nombre del Formulario</label>
                                <input type="text" class="form-control" id="nombre_formulario" required placeholder="Ej: Evaluación de Competencias">
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" rows="3" placeholder="Describa el propósito de este formulario"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Formulario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para modificar formulario -->
        <div class="modal fade" id="editFormModal" tabindex="-1" aria-labelledby="editFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modificar Formulario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editFormForm">
                            <input type="hidden" id="edit_id_formulario">
                            <div class="mb-3">
                                <label for="edit_nombre_formulario" class="form-label">Nombre del Formulario</label>
                                <input type="text" class="form-control" id="edit_nombre_formulario" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion" rows="3"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas para formularios -->
        <ul class="nav nav-tabs" id="formTabs" role="tablist">
            <!-- Las pestañas se cargarán dinámicamente -->
        </ul>
        <div class="tab-content" id="formTabsContent">
            <!-- Los contenidos de las pestañas se cargarán dinámicamente -->
        </div>

        <!-- Modal para modificar preguntas -->
        <div class="modal fade" id="editQuestionsModal" tabindex="-1" aria-labelledby="editQuestionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-question-circle me-2"></i>Modificar Preguntas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-preguntas-container">
                            <div class="card mt-3" id="modal-pregunta-template" style="display: none;">
                                <div class="form-group mb-3">
                                    <label class="form-label">Título</label>
                                    <input type="text" class="form-control question-title-input" placeholder="Ingrese el título">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control question-textarea" placeholder="Ingrese la descripción"></textarea>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">Opciones de Respuesta</label>
                                    <div class="option">
                                        <input type="radio" name="modal-tipo-respuesta-[id]" value="1" required>
                                        <input type="text" class="form-control option-1-text" placeholder="Texto para opción 1 (ej. No, no cumple)">
                                    </div>
                                    <div class="option">
                                        <input type="radio" name="modal-tipo-respuesta-[id]" value="1" required>
                                        <input type="text" class="form-control option-4-text" placeholder="Texto para opción 4 (ej. Cumple adecuadamente)">
                                    </div>
                                    <div class="option">
                                        <input type="radio" name="modal-tipo-respuesta-[id]" value="5">
                                        <input type="text" class="form-control option-5-text" placeholder="Texto para opción 5 (ej. Sí, cumple)">
                                    </div>
                                    <div class="option">
                                        <input type="radio" name="modal-tipo-respuesta-[id]" value="no-aplica">
                                        <input type="text" class="form-control option-no-aplica-text" placeholder="Texto para No aplica" value="NO APLICA">
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-danger btn-sm remove-question">
                                        <i class="fas fa-trash-alt me-1"></i>Eliminar
                                    </button>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary mt-3" id="modal-add-question">
                                    <i class="fas fa-plus-circle me-2"></i>Agregar Nueva Pregunta
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                        <button type="button" class="btn btn-success" id="modal-save-questions">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Action Button (Mobile) -->
        <div class="fab d-lg-none" data-bs-toggle="modal" data-bs-target="#createFormModal">
            <i class="fas fa-plus"></i>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/FormulariosAdeherencia.js?v2.2"></script>
     <script>
        var IdUsuario = "<?php echo $IdUsuario; ?>";
        var IdRol = "<?php echo $IdRol; ?>";
        var CrearGuias = <?php echo $CrearGuias ? 'true' : 'false'; ?>;
        var updateGuias = <?php echo $updateGuias ? 'true' : 'false'; ?>;
        var deleteGuias = <?php echo $deleteGuias ? 'true' : 'false'; ?>;
    </script>
</body>
</html>