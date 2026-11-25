<?php
/**
 * CUPS que a un médico se le pagan por evento (se excluyen de su liquidación de honorarios).
 *
 * Tabla cupsevento: IdCupsEvento, DocumentoMedico, Cups, Descripcion.
 * - Se identifica al médico por DocumentoMedico (en medico puede haber varias filas con el mismo documento).
 * - La columna Cups guarda el OID del procedimiento (cirugias.OIDProcedimiento), NO el código CUPS de 6 dígitos.
 *   La traducción código <-> OID la hace Modelo/CatalogoCups.php.
 */
class CupsEvento{
    private $_conexion;
    private $_IdCupsEvento;
    private $_DocumentoMedico;
    private $_Cups;
    private $_Descripcion;

    // Un médico por documento, para no repetir filas cuando el documento está duplicado en medico.
    const MEDICO_POR_DOCUMENTO = "(SELECT DocumentoMedico, MAX(IdMedico) AS IdMedico, MAX(NombreMedico) AS NombreMedico
            FROM medico GROUP BY DocumentoMedico)";

    function __construct($conexion, $IdCupsEvento = '', $DocumentoMedico = '', $Cups = '', $Descripcion = '') {
        $this->_conexion = $conexion;
        $this->_IdCupsEvento = $IdCupsEvento;
        $this->_DocumentoMedico = $DocumentoMedico;
        $this->_Cups = $Cups;
        $this->_Descripcion = $Descripcion;
    }

    function _get($k) {
        return $this->$k;
    }

    function _set($k, $v) {
        $this->$k = $v;
    }

    function insertar(){
        try {
            $insercion = $this->_conexion->prepare("INSERT INTO cupsevento (DocumentoMedico, Cups, Descripcion) VALUES
             (:DocumentoMedico, :Cups, :Descripcion)");
            $insercion->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
            $insercion->bindValue(':Cups', $this->_Cups);
            $insercion->bindValue(':Descripcion', $this->_Descripcion);
            $insercion->execute();
            return [
                "success" => true,
                "message" => "CUPS asignado correctamente",
                "IdCupsEvento" => $this->_conexion->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                "success" => false,
                "message" => "Error al guardar el CUPS: " . $e->getMessage()
            ];
        }
    }

    function modificar(){
        try {
            $modificacion = $this->_conexion->prepare("UPDATE cupsevento SET
                DocumentoMedico = :DocumentoMedico,
                Cups = :Cups,
                Descripcion = :Descripcion
                WHERE IdCupsEvento = :IdCupsEvento");
            $modificacion->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
            $modificacion->bindValue(':Cups', $this->_Cups);
            $modificacion->bindValue(':Descripcion', $this->_Descripcion);
            $modificacion->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
            $modificacion->execute();
            return [
                "success" => true,
                "message" => "CUPS actualizado correctamente",
            ];
        } catch (PDOException $e) {
            return [
                "success" => false,
                "message" => "Error al actualizar el CUPS: " . $e->getMessage()
            ];
        }
    }

    function eliminar(){
        try {
            $eliminacion = $this->_conexion->prepare("DELETE FROM cupsevento WHERE
                IdCupsEvento = :IdCupsEvento");
            $eliminacion->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
            $eliminacion->execute();
            if ($eliminacion->rowCount() > 0) {
                return ["success" => true, "message" => "CUPS eliminado correctamente"];
            }
            return ["success" => false, "message" => "El registro ya no existe"];
        } catch (PDOException $e) {
            return [
                "success" => false,
                "message" => "Error al eliminar el CUPS: " . $e->getMessage()
            ];
        }
    }

    /** ¿El médico ya tiene ese OID? Al actualizar se excluye el propio registro. */
    function existeAsignacion($excluirIdCupsEvento = '') {
        $consulta = $this->_conexion->prepare("SELECT COUNT(*) FROM cupsevento
            WHERE DocumentoMedico = :DocumentoMedico AND Cups = :Cups AND IdCupsEvento <> :IdCupsEvento");
        $consulta->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $consulta->bindValue(':Cups', $this->_Cups);
        $consulta->bindValue(':IdCupsEvento', $excluirIdCupsEvento === '' ? 0 : $excluirIdCupsEvento);
        $consulta->execute();
        return (int) $consulta->fetchColumn() > 0;
    }

    /** Documento del médico a partir de su IdMedico (las pantallas envían el IdMedico del select). */
    function documentoDeMedico($IdMedico) {
        $consulta = $this->_conexion->prepare("SELECT DocumentoMedico FROM medico WHERE IdMedico = :IdMedico");
        $consulta->bindValue(':IdMedico', $IdMedico);
        $consulta->execute();
        $documento = $consulta->fetchColumn();
        return ($documento === false || trim($documento) === '') ? null : trim($documento);
    }

    function ListarPorId() {
        $listado = $this->_conexion->prepare("SELECT
        B.IdMedico,
        A.DocumentoMedico,
        B.NombreMedico,
        A.IdCupsEvento,
        A.Cups,
        A.Descripcion
        FROM cupsevento A
        LEFT JOIN " . self::MEDICO_POR_DOCUMENTO . " B ON A.DocumentoMedico = B.DocumentoMedico
        WHERE A.IdCupsEvento = :IdCupsEvento;");
        $listado->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorMedico() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE DocumentoMedico = :DocumentoMedico");
        $listado->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorCups() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE Cups = :Cups");
        $listado->bindValue(':Cups', $this->_Cups);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorCupsId() {
        $listado = $this->_conexion->prepare("SELECT * FROM cupsevento WHERE Cups = :Cups AND IdCupsEvento != :IdCupsEvento");
        $listado->bindValue(':IdCupsEvento', $this->_IdCupsEvento);
        $listado->bindValue(':Cups', $this->_Cups);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {
        $listado = $this->_conexion->prepare("SELECT
            ce.*,
            m.IdMedico,
            m.NombreMedico
            FROM cupsevento ce
            INNER JOIN " . self::MEDICO_POR_DOCUMENTO . " m ON ce.DocumentoMedico = m.DocumentoMedico
            ORDER BY ce.Descripcion;");
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }

    function listarPorMedicoCompleto($IdMedico) {
        $listado = $this->_conexion->prepare("SELECT
            ce.*,
            m.IdMedico,
            m.NombreMedico
            FROM cupsevento ce
            INNER JOIN medico m ON ce.DocumentoMedico = m.DocumentoMedico
            WHERE m.IdMedico = :IdMedico
            ORDER BY ce.Descripcion");
        $listado->bindValue(':IdMedico', $IdMedico);
        $listado->execute();
        return $listado->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
