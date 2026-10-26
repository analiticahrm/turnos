<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
    <link rel="shortcut icon" href="../Assets/ICON/hrm.png">
    <link rel="stylesheet" href="css/Asignar.css">
    <title>Asignar Médicos a Áreas</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
       <!-- Select2 CSS -->
       <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<?php
    session_start();
    if (!isset($_SESSION['TipoRol'])) {
        header("location:../Vista/Login.php?mensaje=incorrecto");
    }
    $formulario = 'area';
    $_SESSION['Menu'] = '1';
    if (strpos('' . $_SESSION['MedicoRol'], 'R') === false) {
        header("location:../Vista/index.php?mensaje=Permisos Insuficientes");
    }
    include "../Vista/Menu.php"; 
    ?>

<div class="contenido-principal">
    <div class="container">
        <table>
        <tbody>
		<tr>
			<td colspan="4">
            <h1>Asignar Médicos a Áreas</h1>
            </td>
		</tr>
		<tr>
			<td colspan="4">
                <label for="areas" class="form-label">Selecciona un Área:</label>
            <select id="areas" class="form-select">
                <option value="">-- Selecciona un Área --</option>
            </select>
            </td>
		</tr>
		<tr>
			<td colspan="4">
            <label for="medicos" class="form-label">Selecciona un Médico:</label>
        <diV class="agremedi"> 
        <select id="medicos" class="form-select">
            <option value="">-- Selecciona un Médico --</option>
        </select>
        <button id="agregar" class="btn btn-primary"  style="margin-left: 0cm;display:flex;align-items: center;height: 100%;"><i class="fa-solid fa-plus fa-lg" style="margin-right: 8px;"></i>Agregar</button>
        </div>

            </td>

		</tr>
		<tr>
			<td colspan="2"><h3>Médicos Asignados:</h3></td>
			<td colspan="2"><h3>Médicos a Asignar:</h3></td>
		</tr>
        <tr>
			<td colspan="2"><ul id="medicos-asignados"></ul></td>
			<td colspan="2"><ul id="medicos-a-asignar"></td>
		</tr>

		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
		<tr>
			<td colspan="4"> <button id="asignar" class="btn btn-success">Asignar Médicos</button><br></br></td>
            
		</tr>
	</tbody>
        </table>
        
    </div>
</div>

    <script src="js/Asignar.js"></script>
    <script>

    </script>
</body>
</html>
