<?php
class Festivo {
    private $_conexion;
    private $_IdFestivo;
    private $_Fecha;
    private $_Descripcion;
    private $_UsuarioCreacion;

    function __construct($conexion, $IdFestivo, $Fecha, $Descripcion, $UsuarioCreacion = '') {
        $this->_conexion = $conexion;
        $this->_IdFestivo = $IdFestivo;
        $this->_Fecha = $Fecha;
        $this->_Descripcion = $Descripcion;
        $this->_UsuarioCreacion = $UsuarioCreacion;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO festivo (Fecha, Descripcion, UsuarioCreacion) VALUES (:Fecha, :Descripcion, :UsuarioCreacion)");
            $insercion->bindValue(':Fecha', $this->_Fecha);
            $insercion->bindValue(':Descripcion', $this->_Descripcion);
            $insercion->bindValue(':UsuarioCreacion', $this->_UsuarioCreacion);
            $exito = $insercion->execute();

            return $exito
                ? ["success" => true, "message" => "Festivo insertado correctamente", "IdFestivo" => $this->_conexion->lastInsertId()]
                : ["success" => false, "message" => "No se pudo insertar el festivo"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM festivo WHERE IdFestivo = :IdFestivo");
            $eliminacion->bindValue(':IdFestivo', $this->_IdFestivo);
            $exito = $eliminacion->execute();
            return $exito
                ? ["success" => true, "message" => "Festivo eliminado correctamente"]
                : ["success" => false, "message" => "No se pudo eliminar el festivo"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM festivo ORDER BY Fecha");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorAnio($anio) {
        $listado = $this->_conexion->prepare("SELECT * FROM festivo WHERE YEAR(Fecha) = :Anio ORDER BY Fecha");
        $listado->bindValue(':Anio', $anio);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    // Devuelve el set de fechas festivas (YYYY-MM-DD) entre dos fechas, para que el
    // motor de nomina las use como lookup O(1) al clasificar cada microsegmento.
    function obtenerSetEntreFechas($fechaInicio, $fechaFin) {
        $listado = $this->_conexion->prepare("SELECT Fecha FROM festivo WHERE Fecha BETWEEN :Inicio AND :Fin");
        $listado->bindValue(':Inicio', $fechaInicio);
        $listado->bindValue(':Fin', $fechaFin);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_COLUMN);
    }
}
