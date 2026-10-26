<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica si se ha enviado un archivo y si no hay errores
    if (isset($_FILES["nueva_foto"]) && $_FILES["nueva_foto"]["error"] == 0) {
        $nombre_archivo = $_FILES["nueva_foto"]["name"];
        $tipo_archivo = $_FILES["nueva_foto"]["type"];
        $tamaño_archivo = $_FILES["nueva_foto"]["size"];
        $temp_nombre = $_FILES["nueva_foto"]["tmp_name"];
        
        // Verifica si el archivo es una imagen
        if (strpos($tipo_archivo, "image") !== false) {
            // Ruta donde se almacenarán las fotos de perfil (cámbiala según tus necesidades)
            $directorio_destino = "../Vista/fotos_perfil";
            
            // Mueve el archivo temporal al directorio de destino
            if (move_uploaded_file($temp_nombre, $directorio_destino . $nombre_archivo)) {
                // Aquí puedes actualizar la base de datos o realizar otras acciones necesarias
                echo "La foto de perfil se ha cambiado con éxito.";
            } else {
                echo "Hubo un error al subir la foto de perfil.";
            }
        } else {
            echo "El archivo seleccionado no es una imagen.";
        }
    } else {
        echo "No se ha seleccionado ningún archivo o ha ocurrido un error.";
    }
}
?>
