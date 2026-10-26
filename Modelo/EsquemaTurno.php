<?php
class EsquemaTurno {
    private $_conexion;
    private $_IdEsquema;
    private $_Nombre;
    private $_Descripcion;
    private $_DiasAplicacion;
    private $_Activo;

    function __construct($conexion, $IdEsquema, $Nombre, $Descripcion, $DiasAplicacion, $Activo) {
        $this->_conexion = $conexion;
        $this->_IdEsquema = $IdEsquema;
        $this->_Nombre = $Nombre;
        $this->_Descripcion = $Descripcion;
        $this->_DiasAplicacion = $DiasAplicacion;
        $this->_Activo = $Activo;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO esquematurno (Nombre, Descripcion, DiasAplicacion, Activo)
                VALUES (:Nombre, :Descripcion, :DiasAplicacion, :Activo)");
            $insercion->bindValue(':Nombre', $this->_Nombre);
            $insercion->bindValue(':Descripcion', $this->_Descripcion);
            $insercion->bindValue(':DiasAplicacion', $this->_DiasAplicacion);
            $insercion->bindValue(':Activo', $this->_Activo);
            $exito = $insercion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Esquema insertado correctamente" : "No se pudo insertar el esquema",
                "id" => $exito ? $this->_conexion->lastInsertId() : null
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la inserción: " . $e->getMessage()];
        }
    }

    function modificar() {
        try {
            $modificacion = $this->_conexion->prepare("UPDATE esquematurno SET
                Nombre = :Nombre,
                Descripcion = :Descripcion,
                DiasAplicacion = :DiasAplicacion,
                Activo = :Activo
                WHERE IdEsquema = :IdEsquema");
            $modificacion->bindValue(':IdEsquema', $this->_IdEsquema);
            $modificacion->bindValue(':Nombre', $this->_Nombre);
            $modificacion->bindValue(':Descripcion', $this->_Descripcion);
            $modificacion->bindValue(':DiasAplicacion', $this->_DiasAplicacion);
            $modificacion->bindValue(':Activo', $this->_Activo);
            $exito = $modificacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Esquema modificado correctamente" : "No se pudo modificar el esquema"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la modificación: " . $e->getMessage()];
        }
    }

    function eliminar() {
        try {
            $eliminarDetalles = $this->_conexion->prepare("DELETE FROM detalleesquema WHERE IdEsquema = :IdEsquema");
            $eliminarDetalles->bindValue(':IdEsquema', $this->_IdEsquema);
            $eliminarDetalles->execute();

            $eliminacion = $this->_conexion->prepare("DELETE FROM esquematurno WHERE IdEsquema = :IdEsquema");
            $eliminacion->bindValue(':IdEsquema', $this->_IdEsquema);
            $exito = $eliminacion->execute();

            return [
                "success" => $exito,
                "message" => $exito ? "Esquema eliminado correctamente" : "No se pudo eliminar el esquema"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error en la eliminación: " . $e->getMessage()];
        }
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM esquematurno ORDER BY Nombre");
        $listado->execute();
        return $listado;
    }

    function listarActivos() {
        $listado = $this->_conexion->prepare("SELECT * FROM esquematurno WHERE Activo = 1 ORDER BY Nombre");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM esquematurno WHERE IdEsquema = :IdEsquema");
        $listado->bindValue(':IdEsquema', $this->_IdEsquema);
        $listado->execute();
        return $listado->fetch(PDO::FETCH_ASSOC);
    }
}
