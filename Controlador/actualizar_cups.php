<?php
require_once '../Modelo/conexionPDO.php';

class ActualizarCUPS {
    private $conexion;
    private $db;
    
    public function __construct() {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->conectar();
    }
    
    public function actualizarCUPSDesdeExcel($archivoExcel) {
        try {
            // 1. Leer el archivo Excel
            $datosExcel = $this->leerExcel($archivoExcel);
            
            if (empty($datosExcel)) {
                throw new Exception("No se pudieron leer los datos del archivo Excel.");
            }
            
            echo "<p>Filas leídas del Excel: <strong>" . count($datosExcel) . "</strong></p>";
            
            // 2. Procesar cada fila del Excel y actualizar la base de datos
            $actualizados = 0;
            $errores = 0;
            $noEncontrados = 0;
            
            foreach ($datosExcel as $indice => $fila) {
                $oid = $fila['OID'];
                $sipcodigo = $fila['SIPCODIGO'];
                
                // Actualizar la tabla cirugias
                $resultado = $this->actualizarRegistro($oid, $sipcodigo);
                
                if ($resultado === true) {
                    $actualizados++;
                } elseif ($resultado === false) {
                    $errores++;
                } else {
                    $noEncontrados++;
                }
                
                // Mostrar progreso cada 1000 registros
                if (($indice + 1) % 1000 == 0) {
                    echo "<p>Procesados: " . ($indice + 1) . " registros...</p>";
                    flush();
                }
            }
            
            // 3. Mostrar resultados
            echo "<h3>Proceso completado</h3>";
            echo "<p>Registros actualizados correctamente: <strong>$actualizados</strong></p>";
            echo "<p>Registros no encontrados en cirugías: <strong>$noEncontrados</strong></p>";
            echo "<p>Errores en actualización: <strong>$errores</strong></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        } finally {
            $this->conexion->desconectar($this->db);
        }
    }
    
    private function leerExcel($archivo) {
        $datos = [];
        
        // Verificar si el archivo existe
        if (!file_exists($archivo)) {
            throw new Exception("El archivo $archivo no existe.");
        }
        
        // Determinar la extensión del archivo
        $extension = pathinfo($archivo, PATHINFO_EXTENSION);
        
        if ($extension === 'xlsx') {
            // Usar PhpSpreadsheet para archivos .xlsx
            return $this->leerExcelConPhpSpreadsheet($archivo);
        } elseif ($extension === 'csv') {
            // Leer archivo CSV
            return $this->leerCSV($archivo);
        } else {
            throw new Exception("Formato de archivo no soportado. Use .xlsx o .csv");
        }
    }
    
    private function leerExcelConPhpSpreadsheet($archivo) {
        // Necesitas instalar PhpSpreadsheet: composer require phpoffice/phpspreadsheet
        require '../Vista/excel/vendor/autoload.php';

        
        $datos = [];
        
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($archivo);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Obtener todas las filas como array
            $rows = $worksheet->toArray();
            
            // Saltar la primera fila si contiene encabezados
            $encabezados = array_shift($rows);
            
            echo "<p>Primeros 5 registros del Excel:</p>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>OID</th><th>SIPCODIGO</th></tr>";
            
            // Mostrar primeros 5 registros para verificar
            $muestra = array_slice($rows, 0, 5);
            foreach ($muestra as $fila) {
                echo "<tr><td>" . $fila[0] . "</td><td>" . $fila[1] . "</td></tr>";
            }
            echo "</table>";
            
            foreach ($rows as $fila) {
                // Asumimos que la primera columna es OID y la segunda es SIPCODIGO
                if (isset($fila[0]) && isset($fila[1]) && $fila[0] !== '' && $fila[1] !== '') {
                    $datos[] = [
                        'OID' => (int)$fila[0],
                        'SIPCODIGO' => (string)$fila[1]
                    ];
                }
            }
            
            echo "<p>Total de filas procesables: <strong>" . count($datos) . "</strong></p>";
            
        } catch (Exception $e) {
            throw new Exception("Error al leer el archivo Excel: " . $e->getMessage());
        }
        
        return $datos;
    }
    
    private function leerCSV($archivo) {
        $datos = [];
        
        if (($handle = fopen($archivo, "r")) !== FALSE) {
            // Leer encabezados (primera línea)
            $encabezados = fgetcsv($handle, 1000, ",");
            
            while (($fila = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($fila) >= 2 && !empty($fila[0]) && !empty($fila[1])) {
                    $datos[] = [
                        'OID' => (int)$fila[0],
                        'SIPCODIGO' => (string)$fila[1]
                    ];
                }
            }
            fclose($handle);
        }
        
        return $datos;
    }
    
    private function actualizarRegistro($oid, $sipcodigo) {
        try {
            // PRIMERO: Verificar si existe el OID en la tabla cirugias
            $sqlVerificar = "SELECT COUNT(*) as total FROM cirugias WHERE OIDProcedimiento = :oid";
            $stmtVerificar = $this->db->prepare($sqlVerificar);
            $stmtVerificar->bindParam(':oid', $oid, PDO::PARAM_INT);
            $stmtVerificar->execute();
            $resultado = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] == 0) {
                // No existe este OID en la tabla cirugias
                return 'no_encontrado';
            }
            
            // SEGUNDO: Actualizar TODOS los registros con este OID, sin importar el valor actual de CUPS
            $sql = "UPDATE cirugias 
                    SET Cups = :cups 
                    WHERE OIDProcedimiento = :oid";
            
            $stmt = $this->db->prepare($sql);
            
            // Vincular parámetros
            $stmt->bindParam(':cups', $sipcodigo, PDO::PARAM_STR);
            $stmt->bindParam(':oid', $oid, PDO::PARAM_INT);
            
            // Ejecutar la consulta
            $stmt->execute();
            
            // Verificar si se actualizó algún registro
            $filasAfectadas = $stmt->rowCount();
            
            if ($filasAfectadas > 0) {
                // Obtener cuántos registros se actualizaron
                $sqlContar = "SELECT COUNT(*) as total FROM cirugias WHERE OIDProcedimiento = :oid AND Cups = :cups";
                $stmtContar = $this->db->prepare($sqlContar);
                $stmtContar->bindParam(':oid', $oid, PDO::PARAM_INT);
                $stmtContar->bindParam(':cups', $sipcodigo, PDO::PARAM_STR);
                $stmtContar->execute();
                $contador = $stmtContar->fetch(PDO::FETCH_ASSOC);
                
                // Mostrar mensaje solo si estamos en modo debug
                if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                    echo "<p>✓ Actualizado OID: $oid → CUPS: $sipcodigo ($filasAfectadas registros)</p>";
                }
                return true;
            }
            
            return false;
            
        } catch (PDOException $e) {
            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                echo "<p style='color: red;'>Error al actualizar OID $oid: " . $e->getMessage() . "</p>";
            }
            return false;
        }
    }
    
    public function verificarActualizacion($limite = 10) {
        try {
            $sql = "SELECT c.IdCirugia, c.OIDProcedimiento, c.Procedimiento, c.Cups 
                    FROM cirugias c 
                    WHERE c.Cups != '0' AND c.Cups != ''
                    ORDER BY c.IdCirugia DESC 
                    LIMIT :limite";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Registros con CUPS diferentes de 0:</h3>";
            
            if (empty($resultados)) {
                echo "<p>No hay registros con CUPS diferentes de 0</p>";
                return;
            }
            
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID Cirugía</th><th>OID Procedimiento</th><th>Procedimiento</th><th>CUPS</th></tr>";
            
            foreach ($resultados as $fila) {
                echo "<tr>";
                echo "<td>" . $fila['IdCirugia'] . "</td>";
                echo "<td>" . $fila['OIDProcedimiento'] . "</td>";
                echo "<td>" . substr($fila['Procedimiento'], 0, 50) . "...</td>";
                echo "<td><strong>" . $fila['Cups'] . "</strong></td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error al verificar: " . $e->getMessage() . "</p>";
        }
    }
    
    public function estadisticas() {
        try {
            echo "<h3>Estadísticas de la tabla cirugias:</h3>";
            
            // Total registros
            $sqlTotal = "SELECT COUNT(*) as total FROM cirugias";
            $stmt = $this->db->query($sqlTotal);
            $total = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Total registros en cirugias: <strong>" . $total['total'] . "</strong></p>";
            
            // Registros con CUPS = 0
            $sqlCups0 = "SELECT COUNT(*) as total FROM cirugias WHERE Cups = '0'";
            $stmt = $this->db->query($sqlCups0);
            $cups0 = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Registros con CUPS = '0': <strong>" . $cups0['total'] . "</strong></p>";
            
            // Registros con CUPS NULL o vacío
            $sqlCupsNull = "SELECT COUNT(*) as total FROM cirugias WHERE Cups IS NULL OR Cups = ''";
            $stmt = $this->db->query($sqlCupsNull);
            $cupsNull = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Registros con CUPS NULL o vacío: <strong>" . $cupsNull['total'] . "</strong></p>";
            
            // Registros con CUPS diferente de 0
            $sqlCupsValido = "SELECT COUNT(*) as total FROM cirugias WHERE Cups != '0' AND Cups != '' AND Cups IS NOT NULL";
            $stmt = $this->db->query($sqlCupsValido);
            $cupsValido = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Registros con CUPS válido: <strong>" . $cupsValido['total'] . "</strong></p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error al obtener estadísticas: " . $e->getMessage() . "</p>";
        }
    }
}

// Uso del script
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de CUPS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="file"] { padding: 5px; }
        button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-right: 10px; }
        button:hover { background-color: #45a049; }
        .btn-blue { background-color: #2196F3; }
        .btn-blue:hover { background-color: #0b7dda; }
        .resultados { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #4CAF50; }
        .debug { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Actualizar Códigos CUPS desde Excel</h1>
        
        <div style="margin-bottom: 20px;">
            <button onclick="location.reload()">↻ Recargar</button>
            <button class="btn-blue" onclick="location.href='?estadisticas=1'">📊 Ver Estadísticas</button>
            <button class="btn-blue" onclick="location.href='?debug=1'">🐛 Modo Debug</button>
        </div>
        
        <?php
        // Mostrar estadísticas si se solicita
        if (isset($_GET['estadisticas'])) {
            $actualizador = new ActualizarCUPS();
            $actualizador->estadisticas();
            echo "<hr>";
        }
        ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="archivo_excel">Seleccionar archivo Excel/CSV:</label>
                <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx,.csv" required>
                <small>Formatos aceptados: .xlsx, .csv</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="forzar_actualizacion" value="1" checked>
                    Forzar actualización incluso si CUPS tiene valor "0"
                </label>
            </div>
            
            <button type="submit" name="procesar">Procesar Archivo</button>
        </form>
        
        <hr>
        
        <?php
        if (isset($_POST['procesar']) && isset($_FILES['archivo_excel'])) {
            // Procesar el archivo subido
            $archivo_temporal = $_FILES['archivo_excel']['tmp_name'];
            $nombre_archivo = $_FILES['archivo_excel']['name'];
            
            // Mover el archivo a una ubicación temporal
            $directorio_destino = 'uploads/';
            if (!file_exists($directorio_destino)) {
                mkdir($directorio_destino, 0777, true);
            }
            
            $archivo_destino = $directorio_destino . $nombre_archivo;
            
            if (move_uploaded_file($archivo_temporal, $archivo_destino)) {
                echo "<div class='resultados'>";
                if (isset($_GET['debug'])) {
                    echo "<div class='debug'><strong>Modo Debug activado</strong></div>";
                }
                echo "<h2>Procesando archivo: $nombre_archivo</h2>";
                
                // Crear instancia y ejecutar la actualización
                $actualizador = new ActualizarCUPS();
                $actualizador->actualizarCUPSDesdeExcel($archivo_destino);
                
                echo "<hr>";
                
                // Mostrar algunos registros actualizados
                $actualizador->verificarActualizacion();
                
                echo "</div>";
                
                // Eliminar el archivo temporal
                unlink($archivo_destino);
            } else {
                echo "<p style='color: red;'>Error al subir el archivo.</p>";
            }
        }
        ?>
        
        <hr>
        
        <h3>Notas importantes:</h3>
        <ul>
            <li>Este script actualizará TODOS los registros de cirugías con el CUPS correspondiente al OID</li>
            <li>Incluso si CUPS ya tiene valor "0", será actualizado con el valor del Excel</li>
            <li>Verifica los primeros 5 registros del Excel para confirmar que se está leyendo correctamente</li>
            <li>Si el Excel tiene muchos registros, el proceso puede tomar tiempo</li>
            <li><strong>IMPORTANTE:</strong> Haz una copia de seguridad de la tabla antes de ejecutar</li>
        </ul>
    </div>
</body>
</html>