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

    <title>Adherencia Guías de Practica Clínica</title>
</head>
<body>
    <script src="js/Adherencia.js"></script>
    <!------------------------------------------------------Inicio Pagina---------------------------------------------------------------------------->
    <!---------------------------------------------------Inclusiones necesarias---------------------------------------------------------------------->
    <!----------------------------------------------------------------------------------------------------------------------------------------------->
    <?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos($_SESSION['TipoRol'], 'Administrador') === false) {

        if(strpos($_SESSION['TipoRol'], 'Asistencial GPC') === false){
            header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
        }

    }

    //Incluye Menu que es identico en cada formulario
    //instancias de objetos necesarios
    include "../Vista/Menu.php";
    include_once("../Modelo/conexionPDO.php");
    $objetoConexion = new conexion;
    $conexion = $objetoConexion->conectar();

    ?>
    <br></br>
    <div class="container">
    <div id="main-container" class="main-container">
    <h2 class="title" >Adherencia Guías de Practica Clínica</h2>
    <br></br>
    <form id="uploadForm" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="excelFile" class="form-label">Archivo Excel</label>
            <!-- Contenedor personalizado para el campo de archivo -->
            <div class="file-upload-wrapper">
                <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx,.xls" required>
                <label for="excelFile" class="file-upload-btn">Seleccionar archivo</label>
            </div>
            <!-- Mostrar nombre del archivo seleccionado -->
            <div id="fileName" class="file-name"></div>
        </div>
        
        <div class="mb-3">
        </div>
        <div class="mb-3">
            <label for="month" class="form-label">Mes</label>
            <select class="form-control" id="month" name="month" required>
                <option value="" disabled selected>Selecciona un mes</option>
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
        <div class="mb-3">
            <input type="hidden" class="form-control" id="year" name="year" min="2000" value="<?php echo date('Y'); ?>" required>
        </div>
        <input type="hidden" id="accion" name="accion" value="adegpc">
        <button type="submit" class="btn btn-primary">Cargar Turnos</button>
    </form>
    </div>
    </div>

    <?php
    $conexion = null;
    $objetoConexion = null;
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>


<footer >
        <div>Copyright2 &copy; Your Website
                            <?php echo "20" . date("y"); ?>
                        </div>
</footer>
<script>
        // Mostrar el nombre del archivo seleccionado
        const fileInput = document.getElementById('excelFile');
        const fileNameDisplay = document.getElementById('fileName');
        fileInput.addEventListener('change', function() {
            const fileName = this.files.length ? this.files[0].name : 'Ningún archivo seleccionado';
            fileNameDisplay.textContent = fileName;
        });
    </script>
</body>

</html>