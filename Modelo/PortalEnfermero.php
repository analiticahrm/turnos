<?php
// Datos del portal del enfermero/auxiliar: sus propios turnos, recargos/extras y sueldo estimado.
// Todas las consultas reciben el IdMedico ya resuelto por Controlador/ControladorPortalEnfermero.php a partir
// de la sesión; nada aquí lee un IdMedico del cliente. El cálculo de horas y valores es el mismo de la
// consola de Nómina de Recargos (MotorNomina), para que ambas pantallas den la misma cifra.
include_once("MotorNomina.php");
include_once("ParametroEmpresa.php");
include_once("Festivo.php");
include_once("LiquidadorNomina.php");
include_once("CuadroTurnos.php");
include_once("TurnoEnfermeria.php");

class PortalEnfermero {
    private $_conexion;

    function __construct($conexion) {
        $this->_conexion = $conexion;
    }

    // Texto del tipo de contrato guardado en empresaenfermero.TipoContrato.
    static function textoContrato($tipo) {
        $mapa = [1 => 'Planta', 2 => 'Temporal', 3 => 'OPS', 4 => 'Otro'];
        return isset($mapa[(int)$tipo]) ? $mapa[(int)$tipo] : '';
    }

    // El enfermero/auxiliar (especialidad 149) de un documento. Si hubiera filas repetidas gana la activa.
    function medicoPorDocumento($documento) {
        $stmt = $this->_conexion->prepare("SELECT IdMedico, NombreMedico, DocumentoMedico, Correomedico, TelefonoMedico, SueldoBaseMedico, EstadoMedico
            FROM medico WHERE DocumentoMedico = :Doc AND IdEspecialidadMedico = 149
            ORDER BY (EstadoMedico = 1) DESC, IdMedico LIMIT 1");
        $stmt->bindValue(':Doc', $documento);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    function medicoPorId($idMedico) {
        $stmt = $this->_conexion->prepare("SELECT IdMedico, NombreMedico, DocumentoMedico, Correomedico, TelefonoMedico, SueldoBaseMedico, EstadoMedico
            FROM medico WHERE IdMedico = :Id AND IdEspecialidadMedico = 149");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Datos personales y laborales para el encabezado del portal. No incluye el sueldo (va en Nomina).
    function perfil($idMedico) {
        $medico = $this->medicoPorId($idMedico);
        if (!$medico) return null;

        $stmt = $this->_conexion->prepare("SELECT p.DescripcionPerfil FROM perfilenfermero pe
            INNER JOIN perfil p ON p.IdPerfil = pe.IdPerfil WHERE pe.IdMedico = :Id ORDER BY pe.IdEnfPer LIMIT 1");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        $perfil = $stmt->fetchColumn();

        $stmt = $this->_conexion->prepare("SELECT s.NombreServicio FROM servicioenfermero se
            INNER JOIN servicio s ON s.IdServicio = se.IdServicio WHERE se.IdMedico = :Id ORDER BY se.IdSerEnf LIMIT 1");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        $servicio = $stmt->fetchColumn();

        $stmt = $this->_conexion->prepare("SELECT s.NombreServicio FROM servicioapoyoenfermero sa
            INNER JOIN servicio s ON s.IdServicio = sa.IdServicio WHERE sa.IdMedico = :Id ORDER BY s.NombreServicio");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        $apoyo = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $empresa = $this->empresaDe($idMedico);

        return [
            'IdMedico' => (int)$medico['IdMedico'],
            'Nombre' => $medico['NombreMedico'],
            'Documento' => $medico['DocumentoMedico'],
            'Correo' => $medico['Correomedico'],
            'Telefono' => $medico['TelefonoMedico'],
            'Activo' => ((int)$medico['EstadoMedico'] === 1 || $medico['EstadoMedico'] === null),
            'Perfil' => $perfil !== false ? $perfil : null,
            'Servicio' => $servicio !== false ? $servicio : null,
            'ServiciosApoyo' => $apoyo,
            'Empresa' => $empresa ? $empresa['NombreEmpresa'] : null,
            'Contrato' => $empresa ? self::textoContrato($empresa['TipoContrato']) : null,
        ];
    }

    private function empresaDe($idMedico) {
        $stmt = $this->_conexion->prepare("SELECT ee.IdEmpresa, ee.TipoContrato, e.NombreEmpresa
            FROM empresaenfermero ee INNER JOIN empresa e ON e.IdEmpresa = ee.IdEmpresa
            WHERE ee.IdMedico = :Id ORDER BY ee.IdEmpEnf LIMIT 1");
        $stmt->bindValue(':Id', $idMedico);
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            // El nombre se guarda escapado para HTML (p. ej. S&amp;A); la pantalla lo muestra como texto.
            $fila['NombreEmpresa'] = html_entity_decode($fila['NombreEmpresa'], ENT_QUOTES, 'UTF-8');
        }
        return $fila ?: null;
    }

    function tiposTurno() {
        return $this->_conexion->query("SELECT IdTipoTurno, Codigo, Nombre, HoraInicio, HoraFin, Horas FROM tipoturno ORDER BY IdTipoTurno")->fetchAll(PDO::FETCH_ASSOC);
    }

    function festivos($desde, $hasta) {
        $stmt = $this->_conexion->prepare("SELECT Fecha, Descripcion FROM festivo WHERE Fecha BETWEEN :D AND :H ORDER BY Fecha");
        $stmt->bindValue(':D', $desde);
        $stmt->bindValue(':H', $hasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Servicio donde se presta cada turno: el guardado en el turno o, si no hay, el principal del enfermero.
    // $incluirSellos: además de sus turnos activos, trae los días con novedad (sello gris con las 2 letras del motivo),
    // que no son turnos suyos para el resumen, las horas ni la nómina.
    private function sqlTurnos($incluirSellos = false) {
        $hayNovedad = (new TurnoEnfermeria($this->_conexion))->novedadDisponible();
        $camposNovedad = $hayNovedad
            ? ", te.CodigoNovedad, mn.Nombre AS MotivoNovedad, (te.IdTurnoOrigen IS NOT NULL) AS EsCobertura"
            : ", NULL AS CodigoNovedad, NULL AS MotivoNovedad, 0 AS EsCobertura";
        // Turno intercambiado con otro enfermero (migración 15): se marca con ⇄; el pago sigue siendo del turno original.
        $camposNovedad .= TurnoEnfermeria::cambiosDisponible($this->_conexion) ? ", (te.IdCambioTurno IS NOT NULL) AS EsCambio" : ", 0 AS EsCambio";
        $joinNovedad = $hayNovedad ? " LEFT JOIN motivo_novedad_turno mn ON mn.Codigo = te.CodigoNovedad" : "";
        $condEstado = "(te.IdEstado IS NULL OR te.IdEstado <> 2" . ($incluirSellos && $hayNovedad ? " OR te.CodigoNovedad IS NOT NULL" : "") . ")";
        return "SELECT te.IdTurnoEnfermeria, te.FechaTurnoEnfermeria AS Fecha, te.IdTipoTurno, tt.Codigo, tt.Nombre AS NombreTipo,
                    tt.HoraInicio, tt.HoraFin, tt.Horas, te.FechaHoraInicio, te.FechaHoraFin, te.Observaciones, te.EsExcepcion,
                    COALESCE(te.IdServicio, se.IdServicio) AS IdServicio,
                    COALESCE(s.NombreServicio, sp.NombreServicio) AS NombreServicio" . $camposNovedad . "
                FROM turno_enfermeria te
                INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
                INNER JOIN tipoturno tt ON tt.IdTipoTurno = te.IdTipoTurno
                LEFT JOIN servicio s ON s.IdServicio = te.IdServicio
                LEFT JOIN servicioenfermero se ON se.IdSerEnf = (SELECT MIN(x.IdSerEnf) FROM servicioenfermero x WHERE x.IdMedico = pe.IdMedico)
                LEFT JOIN servicio sp ON sp.IdServicio = se.IdServicio" . $joinNovedad . "
                WHERE pe.IdMedico = :IdMedico AND " . $condEstado
            // El enfermero solo ve los turnos de cuadros ya confirmados (migración 13): antes, no ve nada.
            . CuadroTurnos::sqlSoloConfirmados($this->_conexion);
    }

    // Cuántos turnos activos del mes todavía no se ven porque su cuadro no está confirmado. Sirve para decir
    // "el cuadro aún no está disponible" sin revelar cuáles son.
    function turnosPendientesDeConfirmar($idMedico, $anio, $mes) {
        if (!CuadroTurnos::tablaDisponible($this->_conexion)) return 0;
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $stmt = $this->_conexion->prepare("SELECT COUNT(*) FROM turno_enfermeria te
            INNER JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
            WHERE pe.IdMedico = :IdMedico AND (te.IdEstado IS NULL OR te.IdEstado <> 2)
              AND te.FechaTurnoEnfermeria BETWEEN :Desde AND :Hasta" . CuadroTurnos::sqlSinConfirmar($this->_conexion));
        $stmt->bindValue(':IdMedico', $idMedico);
        $stmt->bindValue(':Desde', $desde);
        $stmt->bindValue(':Hasta', $hasta);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Turnos del mes (por fecha de inicio del turno), ordenados.
    function turnosDelMes($idMedico, $anio, $mes) {
        $desde = sprintf('%04d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde));
        $stmt = $this->_conexion->prepare($this->sqlTurnos(true) . " AND te.FechaTurnoEnfermeria BETWEEN :Desde AND :Hasta ORDER BY te.FechaHoraInicio");
        $stmt->bindValue(':IdMedico', $idMedico);
        $stmt->bindValue(':Desde', $desde);
        $stmt->bindValue(':Hasta', $hasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // El próximo turno que todavía no termina (para la tarjeta del resumen).
    function proximoTurno($idMedico) {
        $stmt = $this->_conexion->prepare($this->sqlTurnos() . " AND te.FechaHoraFin > NOW() ORDER BY te.FechaHoraInicio LIMIT 1");
        $stmt->bindValue(':IdMedico', $idMedico);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Liquidación del mes con el mismo cálculo (LiquidadorNomina) que ve el coordinador en Nómina de Recargos: cada
     * turno cuenta en el mes en que empieza, con la parametrización de la empresa, el sueldo (individual o de su
     * categoría) y las novedades del mes. 'pendiente' lista lo que falta para ver pesos; en ese caso 'liquidacion'
     * va en null y las horas son solo referenciales. 'tipos' trae las nueve categorías con su factor, horas y valor.
     */
    function nominaDelMes($idMedico, $anio, $mes) {
        if (!$this->medicoPorId($idMedico)) return null;
        $liquidador = new LiquidadorNomina($this->_conexion);
        $liquidador->soloCuadroConfirmado = true; // el enfermero no ve (ni se le estima) un mes cuyo cuadro no está confirmado
        $liq = $liquidador->liquidarPersona($idMedico, $anio, $mes);
        if (!$liq) return null;

        $horas = [];
        foreach (['horasOrdinarias', 'horasRecargoNocturno', 'horasRecargoFestivoDiurno', 'horasRecargoFestivoNocturno',
                  'horasExtraDiurna', 'horasExtraNocturna', 'horasExtraFestivaDiurna', 'horasExtraFestivaNocturna',
                  'horasFestivoDiurnoHabitual', 'horasFestivoNocturnoHabitual', 'totalHoras'] as $k) {
            $horas[$k] = isset($liq['horas'][$k]) ? $liq['horas'][$k] : 0;
        }

        return [
            'periodo' => $liq['periodo'],
            'turnos' => $liq['turnos'],
            'empresa' => $liq['empresa'] ? $liq['empresa']['Nombre'] : null,
            'contrato' => $liq['contrato'],
            'salarioBase' => $liq['sueldo']['valor'],
            'alcance' => $liq['alcance'],
            'parametrizado' => $liq['parametrizado'],
            'pendiente' => $liq['pendiente'],
            'horas' => $horas,
            'tipos' => $liq['tipos'],
            'festivos' => $liq['festivos'],
            'liquidacion' => $liq['liquidacion'],
        ];
    }
    // ------------------------------------------------------------ contraseña
    function debeCambiarClave($idUsuario) {
        try {
            $stmt = $this->_conexion->prepare("SELECT DebeCambiarClave FROM usuario WHERE IdUsuario = :Id");
            $stmt->bindValue(':Id', $idUsuario);
            $stmt->execute();
            return (int)$stmt->fetchColumn() === 1;
        } catch (PDOException $e) {
            return false; // migración 06 sin aplicar: no hay marca
        }
    }

    // Cambia la clave del propio usuario (MD5, igual que el resto del sistema). Valida la clave actual.
    function cambiarClave($idUsuario, $claveActual, $claveNueva, $documento) {
        $claveNueva = (string)$claveNueva;
        if (strlen($claveNueva) < 8) {
            return ['success' => false, 'message' => 'La contraseña nueva debe tener al menos 8 caracteres.'];
        }
        if ($claveNueva === (string)$documento) {
            return ['success' => false, 'message' => 'La contraseña nueva no puede ser tu número de documento.'];
        }
        if ($claveNueva === (string)$claveActual) {
            return ['success' => false, 'message' => 'La contraseña nueva debe ser distinta de la actual.'];
        }
        $stmt = $this->_conexion->prepare("SELECT 1 FROM usuario WHERE IdUsuario = :Id AND ContraseñaUsuario = MD5(:Actual)");
        $stmt->bindValue(':Id', $idUsuario);
        $stmt->bindValue(':Actual', (string)$claveActual);
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            return ['success' => false, 'message' => 'La contraseña actual no es correcta.'];
        }
        try {
            $upd = $this->_conexion->prepare("UPDATE usuario SET ContraseñaUsuario = MD5(:Nueva), DebeCambiarClave = 0 WHERE IdUsuario = :Id");
            $upd->bindValue(':Nueva', $claveNueva);
            $upd->bindValue(':Id', $idUsuario);
            $upd->execute();
        } catch (PDOException $e) {
            // Sin la columna DebeCambiarClave (migración 06 sin aplicar) se cambia solo la clave.
            $upd = $this->_conexion->prepare("UPDATE usuario SET ContraseñaUsuario = MD5(:Nueva) WHERE IdUsuario = :Id");
            $upd->bindValue(':Nueva', $claveNueva);
            $upd->bindValue(':Id', $idUsuario);
            $upd->execute();
        }
        return ['success' => true, 'message' => 'Contraseña actualizada.'];
    }
}
