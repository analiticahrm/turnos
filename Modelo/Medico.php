<?php
class Medico{
    private $_conexion;
    private $_IdMedico;
    private $_NombreMedico;
    private $_DocumentoMedico;
    private $_Correomedico;
    private $_TelefonoMedico;
    private $_IdEspecialidadMedico;
    private $_SueldoBaseMedico;
    private $_IdAreaMedico;
    private $_EstadoMedico;
    
    private $_IdEmpresa;
    private $_TipoContrato;
    private $_IdPerfil;
    private $_IdServicio;

    function __construct($_conexion, $_IdMedico, $_NombreMedico, $_DocumentoMedico, $_Correomedico, $_TelefonoMedico, $_IdEspecialidadMedico, $_SueldoBaseMedico, $_IdAreaMedico, $_EstadoMedico, $_IdEmpresa, $_TipoContrato, $_IdPerfil, $_IdServicio) {
    $this->_conexion = $_conexion;
    $this->_IdMedico = $_IdMedico;
    $this->_NombreMedico = $_NombreMedico;
    $this->_DocumentoMedico = $_DocumentoMedico;
    $this->_Correomedico = $_Correomedico;
    $this->_TelefonoMedico = $_TelefonoMedico;
    $this->_IdEspecialidadMedico = $_IdEspecialidadMedico;
    $this->_SueldoBaseMedico = $_SueldoBaseMedico;
    $this->_IdAreaMedico = $_IdAreaMedico;
    $this->_EstadoMedico = $_EstadoMedico;
    $this->_IdEmpresa = $_IdEmpresa;
    $this->_TipoContrato = $_TipoContrato;
    $this->_IdPerfil = $_IdPerfil;
    $this->_IdServicio = $_IdServicio;

    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    
    // Ajuste individual de sueldo: vacío = sin ajuste (NULL), y manda el sueldo de la categoría (SueldoCategoria).
    private function sueldoIndividual() {
        $s = trim((string)$this->_SueldoBaseMedico);
        return $s === '' ? null : $s;
    }

    // Aplica la regla contrato <-> empresa (ReglaContrato) a los datos recibidos: el contrato Planta fija la empresa
    // de planta y los demás contratos no pueden ir en ella. Devuelve un mensaje de error o null si todo está bien.
    private function aplicarReglaContrato() {
        include_once(__DIR__ . "/ReglaContrato.php");
        try {
            $r = ReglaContrato::resolver($this->_conexion, $this->_IdEmpresa, $this->_TipoContrato);
        } catch (ReglaContratoException $e) {
            return $e->getMessage();
        }
        if ($r !== null) {
            $this->_IdEmpresa = $r['IdEmpresa'];
            $this->_TipoContrato = $r['TipoContrato'];
        }
        return null;
    }

    function insertar($ServiciosApoyo = null){

    if (($errorContrato = $this->aplicarReglaContrato()) !== null) {
        return ["success" => false, "message" => $errorContrato];
    }

    try {

        $insercion = $this->_conexion->prepare("INSERT INTO medico (NombreMedico, DocumentoMedico, Correomedico, TelefonoMedico, IdEspecialidadMedico, SueldoBaseMedico, IdAreaMedico, EstadoMedico) VALUES
         (:NombreMedico, :DocumentoMedico, :Correomedico, :TelefonoMedico, :IdEspecialidadMedico, :SueldoBaseMedico, :IdAreaMedico, :EstadoMedico);");
        $insercion->bindValue(':NombreMedico', $this->_NombreMedico);
        $insercion->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $insercion->bindValue(':Correomedico', $this->_Correomedico);
        $insercion->bindValue(':TelefonoMedico', $this->_TelefonoMedico);
        $insercion->bindValue(':IdEspecialidadMedico', $this->_IdEspecialidadMedico);
        $insercion->bindValue(':SueldoBaseMedico', $this->sueldoIndividual());
        $insercion->bindValue(':IdAreaMedico', $this->_IdAreaMedico);
        $insercion->bindValue(':EstadoMedico', $this->_EstadoMedico);
        $exito = $insercion->execute();

                    // Obtener el ID del servicio recién insertado
        $this->_IdMedico = $this->_conexion->lastInsertId();

        if ($this->_IdEmpresa != '' AND $this->_TipoContrato != '') {
            $this->insertarEmpresaAsociada($this->_IdEmpresa, $this->_TipoContrato);
        }

        if ($this->_IdPerfil != '') {
            $this->insertarPerfilAsociado($this->_IdPerfil);
        }

        if($this->_IdServicio != ''){
            $this->insertarServicioAsociado($this->_IdServicio);
        }

        // Servicios de apoyo: van después del principal porque se excluye de la lista.
        $this->actualizarServiciosApoyo($this->normalizarServiciosApoyo($ServiciosApoyo));

        // Enfermeros y auxiliares: su usuario del sistema (rol Enfermero/Auxiliar) se crea o se ajusta
        // cuando ya tienen perfil asignado.
        $this->sincronizarUsuarioEnfermero();


            if ($exito) {
                return [
                    "success" => true,
                    "message" => "Enfermero insertado correctamente",
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "No se pudo insertar la enfermera"
                ];
            }
        } catch (PDOException $e) {
            return [
                "success" => false,
                "message" => "Error en la inserción: " . $e->getMessage()
            ];
        }

    }

   function modificar($ServiciosApoyo = null) {
    if (($errorContrato = $this->aplicarReglaContrato()) !== null) {
        return ["success" => false, "message" => $errorContrato];
    }
    try {
        $this->_conexion->beginTransaction();
        
        // Actualizar datos básicos del médico
        $modificacion = $this->_conexion->prepare("UPDATE medico SET
            NombreMedico = :NombreMedico,
            DocumentoMedico = :DocumentoMedico,
            Correomedico = :Correomedico,
            TelefonoMedico = :TelefonoMedico,
            IdEspecialidadMedico = :IdEspecialidadMedico,
            SueldoBaseMedico = :SueldoBaseMedico,
            IdAreaMedico = :IdAreaMedico,
            EstadoMedico = :EstadoMedico
            WHERE IdMedico = :IdMedico");
        
        $modificacion->bindValue(':IdMedico', $this->_IdMedico);
        $modificacion->bindValue(':NombreMedico', $this->_NombreMedico);
        $modificacion->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $modificacion->bindValue(':Correomedico', $this->_Correomedico);
        $modificacion->bindValue(':TelefonoMedico', $this->_TelefonoMedico);
        $modificacion->bindValue(':IdEspecialidadMedico', $this->_IdEspecialidadMedico);
        $modificacion->bindValue(':SueldoBaseMedico', $this->sueldoIndividual());
        $modificacion->bindValue(':IdAreaMedico', $this->_IdAreaMedico);
        $modificacion->bindValue(':EstadoMedico', $this->_EstadoMedico);
        $exito = $modificacion->execute();

        if (!$exito) {
            $this->_conexion->rollBack();
            return [
                "success" => false,
                "message" => "No se pudo modificar el médico"
            ];
        }
        // Manejar empresa
        if ($this->_IdEmpresa != '' && $this->_TipoContrato != '') {
            $this->actualizarEmpresaAsociada($this->_IdEmpresa, $this->_TipoContrato);
        }

        // Manejar perfil
        if ($this->_IdPerfil != '') {
            $this->actualizarPerfilAsociado($this->_IdPerfil);
        }

        // Manejar servicio
        if ($this->_IdServicio != '') {
            $this->actualizarServicioAsociado($this->_IdServicio);
        }

        // Servicios de apoyo (null = el formulario no los envió: se dejan como están)
        $this->actualizarServiciosApoyo($this->normalizarServiciosApoyo($ServiciosApoyo));

        // Usuario del enfermero/auxiliar (rol según perfil)
        $this->sincronizarUsuarioEnfermero();

        $this->_conexion->commit();
        
        return [
            "success" => true,
            "message" => "Médico modificado correctamente",
        ];
        
    } catch (PDOException $e) {
        $this->_conexion->rollBack();
        return [
            "success" => false,
            "message" => "Error en la modificación: " . $e->getMessage()
        ];
    }
}

// Nuevos métodos para actualizar relaciones
// Servicio principal que ya tiene guardado el enfermero (o 0 si no tiene).
private function servicioPrincipalActual() {
    $stmt = $this->_conexion->prepare("SELECT IdServicio FROM servicioenfermero WHERE IdMedico = ? ORDER BY IdSerEnf LIMIT 1");
    $stmt->execute([$this->_IdMedico]);
    return (int)$stmt->fetchColumn();
}

// Lista de ids de servicios de apoyo lista para guardar: acepta el JSON que manda el formulario o un
// array; quita repetidos y el servicio principal (no tiene sentido apoyar a su propio servicio).
// Devuelve null cuando el formulario no envió el dato (no se debe tocar lo guardado).
private function normalizarServiciosApoyo($serviciosApoyo) {
    if ($serviciosApoyo === null || $serviciosApoyo === '') {
        return null;
    }
    if (is_string($serviciosApoyo)) {
        $serviciosApoyo = json_decode($serviciosApoyo, true);
    }
    if (!is_array($serviciosApoyo)) {
        return null;
    }
    $principal = ($this->_IdServicio != '') ? (int)$this->_IdServicio : $this->servicioPrincipalActual();
    $ids = [];
    foreach ($serviciosApoyo as $id) {
        $id = (int)$id;
        if ($id > 0 && $id !== $principal) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

// Deja en servicioapoyoenfermero exactamente los servicios indicados (null = no tocar).
private function actualizarServiciosApoyo($nuevos) {
    if ($nuevos === null) {
        return;
    }
    $stmt = $this->_conexion->prepare("SELECT IdServicio FROM servicioapoyoenfermero WHERE IdMedico = ?");
    $stmt->execute([$this->_IdMedico]);
    $actuales = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $eliminar = array_diff($actuales, $nuevos);
    $anadir = array_diff($nuevos, $actuales);

    if (!empty($eliminar)) {
        $placeholders = implode(',', array_fill(0, count($eliminar), '?'));
        $stmt = $this->_conexion->prepare("DELETE FROM servicioapoyoenfermero WHERE IdMedico = ? AND IdServicio IN ($placeholders)");
        $stmt->execute(array_merge([$this->_IdMedico], array_values($eliminar)));
    }

    if (!empty($anadir)) {
        $stmt = $this->_conexion->prepare("INSERT INTO servicioapoyoenfermero (IdMedico, IdServicio, FechaAsignacion) VALUES (?, ?, ?)");
        $ahora = date('Y-m-d H:i:s');
        foreach ($anadir as $idServicio) {
            $stmt->execute([$this->_IdMedico, $idServicio, $ahora]);
        }
    }
}

// Enfermeros y auxiliares (especialidad 149): crea o ajusta su usuario para que entren al portal.
private function sincronizarUsuarioEnfermero() {
    if ((int)$this->_IdEspecialidadMedico !== 149) {
        return;
    }
    include_once(__DIR__ . "/UsuarioEnfermero.php");
    UsuarioEnfermero::sincronizar($this->_conexion, $this->_IdMedico);
}

private function actualizarEmpresaAsociada($idEmpresa, $tipoContrato) {
    // Verificar si ya existe la misma empresa con el mismo contrato
    $stmt = $this->_conexion->prepare("SELECT COUNT(*) FROM empresaenfermero 
                                     WHERE IdMedico = ? AND IdEmpresa = ? AND TipoContrato = ?");
    $stmt->execute([$this->_IdMedico, $idEmpresa, $tipoContrato]);
    $existe = $stmt->fetchColumn();

    if (!$existe) {
        // Eliminar cualquier empresa existente (asumiendo relación 1 a 1)
        $this->_conexion->prepare("DELETE FROM empresaenfermero WHERE IdMedico = ?")->execute([$this->_IdMedico]);
        
        // Insertar la nueva
        $this->insertarEmpresaAsociada($idEmpresa, $tipoContrato);
    }
}

private function actualizarPerfilAsociado($idPerfil) {
    // Verificar si ya tiene este perfil
    $stmt = $this->_conexion->prepare("SELECT COUNT(*) FROM perfilenfermero 
                                     WHERE IdMedico = ? AND IdPerfil = ?");
    $stmt->execute([$this->_IdMedico, $idPerfil]);
    $existe = $stmt->fetchColumn();

    if (!$existe) {
        // Relación 1 a 1: se cambia el perfil de la fila existente en vez de borrarla, para conservar
        // su IdEnfPer (turno_enfermeria cuelga de él y borrarlo fallaría en cuanto tenga turnos).
        $stmt = $this->_conexion->prepare("SELECT IdEnfPer FROM perfilenfermero WHERE IdMedico = ? ORDER BY IdEnfPer LIMIT 1");
        $stmt->execute([$this->_IdMedico]);
        $idEnfPer = $stmt->fetchColumn();
        if ($idEnfPer) {
            $this->_conexion->prepare("UPDATE perfilenfermero SET IdPerfil = ?, FechaAsignacion = ? WHERE IdEnfPer = ?")
                ->execute([$idPerfil, date('Y-m-d H:i:s'), $idEnfPer]);
        } else {
            $this->insertarPerfilAsociado($idPerfil);
        }
    }
}

private function actualizarServicioAsociado($idServicio) {
    // Verificar si ya tiene este servicio
    $stmt = $this->_conexion->prepare("SELECT COUNT(*) FROM servicioenfermero 
                                     WHERE IdMedico = ? AND IdServicio = ?");
    $stmt->execute([$this->_IdMedico, $idServicio]);
    $existe = $stmt->fetchColumn();

    if (!$existe) {
        // Eliminar cualquier servicio existente (asumiendo relación 1 a 1)
        $this->_conexion->prepare("DELETE FROM servicioenfermero WHERE IdMedico = ?")->execute([$this->_IdMedico]);
        
        // Insertar el nuevo
        $this->insertarServicioAsociado($idServicio);
    }

    // El servicio principal no puede ser a la vez de apoyo.
    $this->_conexion->prepare("DELETE FROM servicioapoyoenfermero WHERE IdMedico = ? AND IdServicio = ?")
        ->execute([$this->_IdMedico, $idServicio]);
}

function eliminar(){
    try {
        $eliminacion = $this->_conexion->prepare("DELETE FROM medico WHERE 
            IdMedico = :IdMedico");
        $eliminacion->bindValue(':IdMedico', $this->_IdMedico);
        $exito = $eliminacion->execute();

        if ($exito) {
            return [
                "success" => true,
                "message" => "Empresa modificada correctamente",
            ];
        } else {
            return [
                "success" => false,
                "message" => "No se pudo modificar la empresa"
            ];
        }
    } catch (PDOException $e) {
        return [
            "success" => false,
            "message" => "Error en la inserción: " . $e->getMessage()
        ];
    }
}

    private function insertarEmpresaAsociada($IdEmpresa, $TipoContrato) {
        if (!empty($IdEmpresa) && !empty($TipoContrato)) {
            $sql = "INSERT INTO empresaenfermero (IdMedico, IdEmpresa, TipoContrato, FechaAsignacion) VALUES (:IdMedico, :IdEmpresa, :TipoContrato, :FechaAsignacion)";
            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdMedico', $this->_IdMedico);
            $stmt->bindValue(':IdEmpresa', $IdEmpresa);
            $stmt->bindValue(':TipoContrato', $TipoContrato);
            $stmt->bindValue(':FechaAsignacion', date('Y-m-d H:i:s'));
            $stmt->execute();
            $listado = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $listado;
        }
    }

    private function insertarPerfilAsociado($IdPerfil) {
        if (!empty($IdPerfil)) {
            $sql = "INSERT INTO perfilenfermero (IdMedico, IdPerfil, FechaAsignacion) VALUES (:IdMedico, :IdPerfil, :FechaAsignacion)";
            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdMedico', $this->_IdMedico);
            $stmt->bindValue(':IdPerfil', $IdPerfil);
            $stmt->bindValue(':FechaAsignacion', date('Y-m-d H:i:s'));
            $stmt->execute();

        }
    }

    private function insertarServicioAsociado($IdServicio) {
        if (!empty($IdServicio)) {
            $sql = "INSERT INTO servicioenfermero (IdMedico, IdServicio, FechaAsignacion) VALUES (:IdMedico, :IdServicio, :FechaAsignacion)";
            $stmt = $this->_conexion->prepare($sql);
            $stmt->bindValue(':IdMedico', $this->_IdMedico);
            $stmt->bindValue(':IdServicio', $IdServicio);
            $stmt->bindValue(':FechaAsignacion', date('Y-m-d H:i:s'));
            $stmt->execute();

        }
    }

    function ListarPorId() {
        $listado = $this->_conexion->prepare("
        SELECT
        A.*,
        C.NombreEspecialidad
        FROM medico A
        LEFT JOIN especialidad C ON A.IdEspecialidadMedico = C.IdEspecialidad
        WHERE IdMedico = :IdMedico
        ");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorDocumento() {
        $listado = $this->_conexion->prepare("SELECT A.IdMedico FROM medico A WHERE A.DocumentoMedico = :DocumentoMedico;");
        $listado->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorIdArea($IdArea) {
        $listado = $this->_conexion->prepare("SELECT A.* ,
        B.NombreArea
        FROM medico A
        INNER JOIN area B ON A.IdAreaMedico = B.IdArea
        WHERE B.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }
    function listarPorAreaEspecialidad($filtro_area = '', $filtro_especialidad = '') {
        $sql = "SELECT A.NombreMedico
                FROM medico A
                INNER JOIN especialidad B ON A.IdEspecialidadMedico = B.IdEspecialidad
                INNER JOIN asignacionmedico T ON A.IdMedico = T.IdMedico
                INNER JOIN area C ON T.IdArea = C.IdArea
                WHERE 1=1";
    
        $params = [];
    
        if (!empty($filtro_area)) {
            $sql .= " AND C.NombreArea LIKE :area";
            $params[':area'] = '%' . $filtro_area . '%';
        }
        if (!empty($filtro_especialidad)) {
            $sql .= " AND B.NombreEspecialidad LIKE :especialidad";
            $params[':especialidad'] = '%' . $filtro_especialidad . '%';
        }
    
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    function ListarPorIdAreaFull($IdArea) {
        $listado = $this->_conexion->prepare("SELECT 
        A.*,
        B.NombreArea,
        C.NombreEspecialidad
        FROM medico A
        LEFT JOIN area B ON A.IdAreaMedico = B.IdArea
        INNER JOIN especialidad C ON A.IdEspecialidadMedico = C.IdEspecialidad
        WHERE B.IdArea = :IdArea
         ORDER BY NombreMedico;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }


    
    function ListarDocumento() {
        $listado = $this->_conexion->prepare("SELECT * FROM medico WHERE DocumentoMedico = :DocumentoMedico AND IdMedico != :IdMedico");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarPorDocumentoId() {
        $listado = $this->_conexion->prepare("SELECT * FROM medico WHERE DocumentoMedico = :DocumentoMedico AND IdMedico != :IdMedico");
        $listado->bindValue(':IdMedico', $this->_IdMedico);
        $listado->bindValue(':DocumentoMedico', $this->_DocumentoMedico);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listar() {

        $listado = $this->_conexion->prepare("SELECT 
        A.*,
        B.NombreArea
        FROM medico A
        INNER JOIN area B ON A.IdAreaMedico = B.IdArea
        ORDER BY NombreMedico;");
        $listado->execute();
        return $listado;
    }

// Los apoyos se acumulan por IdServicio (sin repetidos); al devolver deben ser listas JSON, no objetos.
private function normalizarSalidaEnfermeros(array $enfermeros) {
    foreach ($enfermeros as &$enfermero) {
        $enfermero['ServiciosApoyo'] = array_values($enfermero['ServiciosApoyo']);
    }
    unset($enfermero);
    return array_values($enfermeros);
}

function listarEnfermeros() {
    $stmt = $this->_conexion->prepare("
SELECT 
A.IdMedico,
A.DocumentoMedico,
A.NombreMedico,
A.Correomedico,
A.TelefonoMedico,
A.SueldoBaseMedico,
A.EstadoMedico,
A.IdAreaMedico,
B.IdEspecialidad,
B.NombreEspecialidad,
G.IdPerfil,
G.DescripcionPerfil,
H.IdServicio,
H.NombreServicio,
I.IdEmpresa,
I.NombreEmpresa,
E.TipoContrato,
J.IdServicio AS IdServicioApoyo,
J.NombreServicio AS NombreServicioApoyo
FROM medico A
INNER JOIN especialidad B ON A.IdEspecialidadMedico = B.IdEspecialidad
LEFT JOIN perfilenfermero C ON A.IdMedico = C.IdMedico
LEFT JOIN perfil G ON C.IdPerfil = G.IdPerfil
LEFT JOIN servicioenfermero D ON A.IdMedico = D.IdMedico
LEFT JOIN servicio H ON D.IdServicio = H.IdServicio
LEFT JOIN empresaenfermero E ON A.IdMedico = E.IdMedico
LEFT JOIN empresa I ON E.IdEmpresa = I.IdEmpresa
LEFT JOIN servicioapoyoenfermero F ON A.IdMedico = F.IdMedico
LEFT JOIN servicio J ON F.IdServicio = J.IdServicio
WHERE A.IdEspecialidadMedico = 149
ORDER BY NombreMedico;
    ");
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $enfermeros = [];
    foreach ($resultados as $fila) {
        $idMedico = $fila['IdMedico'];

        if (!isset($enfermeros[$idMedico])) {
            $enfermeros[$idMedico] = [
                "IdMedico" => $fila['IdMedico'],
                "DocumentoMedico" => $fila['DocumentoMedico'],
                "NombreMedico" => $fila['NombreMedico'],
                "Correomedico" => $fila['Correomedico'],
                "TelefonoMedico" => $fila['TelefonoMedico'],
                "SueldoBaseMedico" => $fila['SueldoBaseMedico'],
                "EstadoMedico" => $fila['EstadoMedico'],
                "IdAreaMedico" => $fila['IdAreaMedico'],
                "IdEspecialidad" => $fila['IdEspecialidad'],
                "NombreEspecialidad" => $fila['NombreEspecialidad'],
                "Perfil" => [
                    "IdPerfil" => $fila['IdPerfil'],
                    "DescripcionPerfil" => $fila['DescripcionPerfil']
                ],
                "Servicio" => [
                    "IdServicio" => $fila['IdServicio'],
                    "NombreServicio" => $fila['NombreServicio']
                ],
                "Empresa" => [
                    "IdEmpresa" => $fila['IdEmpresa'],
                    "NombreEmpresa" => $fila['NombreEmpresa'],
                    "TipoContrato" => $fila['TipoContrato']
                ],
                "ServiciosApoyo" => []
            ];
        }

        // Clave por servicio: el join con servicioenfermero puede repetir la misma fila de apoyo.
        if ($fila['IdServicioApoyo']) {
            $enfermeros[$idMedico]['ServiciosApoyo'][$fila['IdServicioApoyo']] = [
                "IdServicio" => $fila['IdServicioApoyo'],
                "NombreServicio" => $fila['NombreServicioApoyo']
            ];
        }
    }

    return $this->normalizarSalidaEnfermeros($enfermeros);
}

    function ListarEnfermeroPorId() {
    $stmt = $this->_conexion->prepare("
SELECT 
A.IdMedico,
A.DocumentoMedico,
A.NombreMedico,
A.Correomedico,
A.TelefonoMedico,
A.SueldoBaseMedico,
A.EstadoMedico,
A.IdAreaMedico,
B.IdEspecialidad,
B.NombreEspecialidad,
G.IdPerfil,
G.DescripcionPerfil,
H.IdServicio,
H.NombreServicio,
I.IdEmpresa,
I.NombreEmpresa,
E.TipoContrato,
J.IdServicio AS IdServicioApoyo,
J.NombreServicio AS NombreServicioApoyo
FROM medico A
INNER JOIN especialidad B ON A.IdEspecialidadMedico = B.IdEspecialidad
LEFT JOIN perfilenfermero C ON A.IdMedico = C.IdMedico
LEFT JOIN perfil G ON C.IdPerfil = G.IdPerfil
LEFT JOIN servicioenfermero D ON A.IdMedico = D.IdMedico
LEFT JOIN servicio H ON D.IdServicio = H.IdServicio
LEFT JOIN empresaenfermero E ON A.IdMedico = E.IdMedico
LEFT JOIN empresa I ON E.IdEmpresa = I.IdEmpresa
LEFT JOIN servicioapoyoenfermero F ON A.IdMedico = F.IdMedico
LEFT JOIN servicio J ON F.IdServicio = J.IdServicio
WHERE A.IdEspecialidadMedico = 149 AND A.IdMedico = :IdMedico
ORDER BY NombreMedico;
    ");
    $stmt->execute([':IdMedico' => $this->_IdMedico]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $enfermeros = [];
    foreach ($resultados as $fila) {
        $idMedico = $fila['IdMedico'];

        if (!isset($enfermeros[$idMedico])) {
            $enfermeros[$idMedico] = [
                "IdMedico" => $fila['IdMedico'],
                "DocumentoMedico" => $fila['DocumentoMedico'],
                "NombreMedico" => $fila['NombreMedico'],
                "Correomedico" => $fila['Correomedico'],
                "TelefonoMedico" => $fila['TelefonoMedico'],
                "SueldoBaseMedico" => $fila['SueldoBaseMedico'],
                "EstadoMedico" => $fila['EstadoMedico'],
                "IdAreaMedico" => $fila['IdAreaMedico'],
                "IdEspecialidad" => $fila['IdEspecialidad'],
                "NombreEspecialidad" => $fila['NombreEspecialidad'],
                "Perfil" => [
                    "IdPerfil" => $fila['IdPerfil'],
                    "DescripcionPerfil" => $fila['DescripcionPerfil']
                ],
                "Servicio" => [
                    "IdServicio" => $fila['IdServicio'],
                    "NombreServicio" => $fila['NombreServicio']
                ],
                "Empresa" => [
                    "IdEmpresa" => $fila['IdEmpresa'],
                    "NombreEmpresa" => $fila['NombreEmpresa'],
                    "TipoContrato" => $fila['TipoContrato']
                ],
                "ServiciosApoyo" => []
            ];
        }

        // Clave por servicio: el join con servicioenfermero puede repetir la misma fila de apoyo.
        if ($fila['IdServicioApoyo']) {
            $enfermeros[$idMedico]['ServiciosApoyo'][$fila['IdServicioApoyo']] = [
                "IdServicio" => $fila['IdServicioApoyo'],
                "NombreServicio" => $fila['NombreServicioApoyo']
            ];
        }
    }

    return $this->normalizarSalidaEnfermeros($enfermeros);
    }

    function listarfull(){
        
        $listado = $this->_conexion->prepare("SELECT 
        A.*,
        B.NombreArea,
        C.NombreEspecialidad
        FROM medico A
        LEFT JOIN area B ON A.IdAreaMedico = B.IdArea
        LEFT JOIN especialidad C ON A.IdEspecialidadMedico = C.IdEspecialidad
         ORDER BY NombreMedico;") or die(mysqli_error($this->_conexion));
        $listado->execute();
        return $listado;
    }
function HonorarioBase(){
    $listado = $this->_conexion->prepare("SELECT
        A.IdMedico,
        A.NombreMedico,
        A.SueldoBaseMedico
        FROM medico A 
        WHERE A.IdMedico = :IdMedico;
    ") or die(mysqli_error($this->_conexion));
    $listado->bindValue(':IdMedico', $this->_IdMedico);
    $listado->execute();
    $listado = $listado->fetchAll(PDO::FETCH_ASSOC);

    // Convertir SueldoBaseMedico a float
    foreach ($listado as &$medico) {
        $medico['SueldoBaseMedico'] = (float)$medico['SueldoBaseMedico'];
    }
    return $listado;
}
    function listarfullMedico($IdArea){
        $listado = $this->_conexion->prepare("SELECT 
            A.*
        FROM 
            medico A
        LEFT JOIN 
            asignacionmedico B ON A.IdMedico = B.IdMedico
        WHERE 
            B.IdArea IS NULL OR B.IdArea <> :IdArea;") or die(mysqli_error($this->_conexion));
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function listarPorArea($IdArea){
        $listado = $this->_conexion->prepare("SELECT
        C.*,
        D.NombreEspecialidad,
        A.IdAsignacionMedico
        FROM asignacionmedico A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
        WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }


    function listarPorArea2($IdArea){
        $listado = $this->_conexion->prepare("SELECT
        C.*,
        D.NombreEspecialidad,
        A.IdAsignacionMedico
        FROM asignacionmedico A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
        WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }

    function listarPorArea3($IdArea){
        $listado = $this->_conexion->prepare("SELECT
        C.*,
        D.NombreEspecialidad,
        A.IdAsignacionMedico
        FROM asignacionmedico A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
        WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }
    function listarPorArea4($IdArea){
        $listado = $this->_conexion->prepare("SELECT
        C.*,
        D.NombreEspecialidad,
        A.IdAsignacionMedico
        FROM asignacionmedico A
        INNER JOIN area B ON A.IdArea = B.IdArea
        INNER JOIN medico C ON A.IdMedico = C.IdMedico
        INNER JOIN especialidad D ON C.IdEspecialidadMedico = D.IdEspecialidad
        WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }
    function Asignar($IdMedico, $IdArea){
        $insercion = $this->_conexion->prepare("INSERT INTO asignacionmedico(IdMedico, IdArea) VALUES (:IdMedico, :IdArea);");
        $insercion->bindValue(':IdMedico',$IdMedico);
        $insercion->bindValue(':IdArea', $IdArea);
        $insercion->execute();
        return $insercion;
    }

    function EliminarAsig($IdAsignacionMedico){
        $eliminacion = $this->_conexion->prepare("DELETE FROM asignacionmedico WHERE 
            IdAsignacionMedico = :IdAsignacionMedico");
        $eliminacion->bindValue(':IdAsignacionMedico',$IdAsignacionMedico);
        $eliminacion->execute();
        return  $eliminacion;
    }

    function obtenerEstadisticas($idMedico, $fechaInicio = null, $fechaFin = null) {                                        
    $sql = "SELECT 
                COUNT(DISTINCT e.id_evaluacion) as total_evaluaciones,
                COUNT(DISTINCT e.id_formulario) as total_formularios,
                AVG(CASE WHEN r.respuesta IN ('1', '5') THEN CAST(r.respuesta AS DECIMAL) ELSE NULL END) as promedio_calificacion,
                COUNT(CASE WHEN r.respuesta = '1' THEN 1 END) as total_1,
                COUNT(CASE WHEN r.respuesta = '5' THEN 1 END) as total_5,
                COUNT(CASE WHEN r.respuesta = 'NO APLICA' THEN 1 END) as total_no_aplica
            FROM medico m
            LEFT JOIN evaluaciones e ON m.idMedico = e.id_medico
            LEFT JOIN respuestas r ON e.id_evaluacion = r.id_evaluacion
            WHERE m.idMedico = :idMedico";
    
    $params = [':idMedico' => $idMedico];
    
    if ($fechaInicio && $fechaFin) {
        $sql .= " AND e.fecha_evaluacion BETWEEN :fechaInicio AND :fechaFin";
        $params[':fechaInicio'] = $fechaInicio;
        $params[':fechaFin'] = $fechaFin;
    }
    
    $stmt = $this->_conexion->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}
