<?php
class Estado {
    private $_conexion;
    private $_IdEstado;
    private $_NombreEstado;
    private $_DescripcionEstado;

    function __construct($conexion, $IdEstado, $NombreEstado, $DescripcionEstado) {
        $this->_conexion = $conexion;
        $this->_IdEstado = $IdEstado;
        $this->_NombreEstado = $NombreEstado;
        $this->_DescripcionEstado = $DescripcionEstado;
    }

    function _get($k) { return $this->$k; }
    function _set($k, $v) { $this->$k = $v; }

    function insertar() {
        $insercion = $this->_conexion->prepare("INSERT INTO estado (NombreEstado, DescripcionEstado) VALUES (:NombreEstado, :DescripcionEstado)");
        $insercion->bindValue(':NombreEstado', $this->_NombreEstado);
        $insercion->bindValue(':DescripcionEstado', $this->_DescripcionEstado);
        $insercion->execute();
        return $insercion;
    }

    function modificar() {
        $modificacion = $this->_conexion->prepare("UPDATE estado SET
            NombreEstado = :NombreEstado,
            DescripcionEstado = :DescripcionEstado
            WHERE IdEstado = :IdEstado");
        $modificacion->bindValue(':IdEstado', $this->_IdEstado);
        $modificacion->bindValue(':NombreEstado', $this->_NombreEstado);
        $modificacion->bindValue(':DescripcionEstado', $this->_DescripcionEstado);
        $modificacion->execute();
        return $modificacion;
    }

    function eliminar() {
        $eliminacion = $this->_conexion->prepare("DELETE FROM estado WHERE IdEstado = :IdEstado");
        $eliminacion->bindValue(':IdEstado', $this->_IdEstado);
        $eliminacion->execute();
        return $eliminacion;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT * FROM estado ORDER BY NombreEstado");
        $listado->execute();
        return $listado;
    }

    function listarPorId() {
        $listado = $this->_conexion->prepare("SELECT * FROM estado WHERE IdEstado = :IdEstado");
        $listado->bindValue(':IdEstado', $this->_IdEstado);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
}
