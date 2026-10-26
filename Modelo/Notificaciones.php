<?php
class Notificaciones{
    private $_conexion;
    private $_IdNotificaciones;
    private $_IdUsuario;
    private $_TipoNotificaciones;
    private $_MensajeNotificaciones;
    private $_EnlaceNotificaciones;
    private $_PrioridadNotificaciones;
    private $_EstadoNotificaciones;
    private $_FechaNotificaciones;


    function __construct($_conexion, $_IdNotificaciones, $_IdUsuario, $_TipoNotificaciones, $_MensajeNotificaciones, $_EnlaceNotificaciones, $_PrioridadNotificaciones, $_EstadoNotificaciones, $_FechaNotificaciones) {
    $this->_conexion = $_conexion;
    $this->_IdNotificaciones = $_IdNotificaciones;
    $this->_IdUsuario = $_IdUsuario;
    $this->_TipoNotificaciones = $_TipoNotificaciones;
    $this->_MensajeNotificaciones = $_MensajeNotificaciones;
    $this->_EnlaceNotificaciones = $_EnlaceNotificaciones;
    $this->_PrioridadNotificaciones = $_PrioridadNotificaciones;
    $this->_EstadoNotificaciones = $_EstadoNotificaciones;
    $this->_FechaNotificaciones = $_FechaNotificaciones;


    }
    function _get($k)
    {
        return $this->$k;
    }
    function _set($k, $v)
    {
        $this->$k = $v;
    }
    
    // La campana de médicos solo debe ver las notificaciones de médicos (Modulo = 'turnos'); las del módulo de
    // enfermería (Modulo = 'enfermeria', migración 11) tienen su propia campana. Si la migración aún no se aplicó,
    // no hay columna Modulo y no se filtra nada.
    private function filtroModulo($alias = '') {
        static $hayColumna = null;
        if ($hayColumna === null) {
            try {
                $r = $this->_conexion->query("SHOW COLUMNS FROM notificaciones LIKE 'Modulo'");
                $hayColumna = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                $hayColumna = false;
            }
        }
        return $hayColumna ? " AND " . $alias . "Modulo = 'turnos'" : '';
    }

    function insertar(){
        $insercion = $this->_conexion->prepare("INSERT INTO notificaciones (IdUsuario, TipoNotificaciones, MensajeNotificaciones, EnlaceNotificaciones, PrioridadNotificaciones, EstadoNotificaciones, FechaNotificaciones) VALUES
         (:IdUsuario, :TipoNotificaciones, :MensajeNotificaciones, :EnlaceNotificaciones, :PrioridadNotificaciones, :EstadoNotificaciones, :FechaNotificaciones);");
        $insercion->bindValue(':IdUsuario', $this->_IdUsuario);
        $insercion->bindValue(':TipoNotificaciones', $this->_TipoNotificaciones);
        $insercion->bindValue(':MensajeNotificaciones', $this->_MensajeNotificaciones);
        $insercion->bindValue(':EnlaceNotificaciones', $this->_EnlaceNotificaciones);
        $insercion->bindValue(':PrioridadNotificaciones', $this->_PrioridadNotificaciones);
        $insercion->bindValue(':EstadoNotificaciones', $this->_EstadoNotificaciones);
        $insercion->bindValue(':FechaNotificaciones', $this->_FechaNotificaciones);
        $insercion->execute();
        return $insercion;
    }

    function MarcarLeidos(){
        $marcar = $this->_conexion->prepare("UPDATE notificaciones A SET A.EstadoNotificaciones = 1
        WHERE A.IdUsuario = :IdUsuario" . $this->filtroModulo('A.'));
        $marcar->bindValue(':IdUsuario', $this->_IdUsuario);
        $marcar->execute();
        return $marcar;
    }
    

    function listarUsuariosAdmin() {
        $listado = $this->_conexion->prepare("SELECT * FROM usuario A WHERE A.IdRolUsuario = 1;");
        $listado->execute();
        return $listado;
    }

    function listarUsuariosLider($IdArea) {
        $listado = $this->_conexion->prepare("SELECT * FROM arealider A WHERE A.IdArea = :IdArea;");
        $listado->bindValue(':IdArea', $IdArea);
        $listado->execute();
        return $listado;
    }

    public function listarPorUsuario() {
        try {
            // 1. Obtener notificaciones (como ya lo haces)
            $stmt = $this->_conexion->prepare("SELECT * FROM notificaciones
                WHERE IdUsuario = :IdUsuario" . $this->filtroModulo() . "
ORDER BY `notificaciones`.`IdNotificaciones` DESC LIMIT 60;
            ");
            $stmt->bindValue(':IdUsuario', $this->_IdUsuario);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 2. Obtener contador de no leídas (usando tu método existente)
            $contadorNoLeidas = $this->contarNoLeidas($this->_IdUsuario);
            

                  // Transformar los datos antes de enviarlos
            $notificacionesFormateadas = array_map(function($notif) {
                return $notif;
            }, $notificaciones);
            
            echo json_encode([
                'notificaciones' => $notificacionesFormateadas,
                'contador' => $contadorNoLeidas
            ]);
            
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function marcarComoLeidas($IdUsuario) {
        try {
            $stmt = $this->_conexion->prepare("
                UPDATE notificaciones 
                SET EstadoNotificaciones = 1
                WHERE IdUsuario = :IdUsuario AND EstadoNotificaciones = 0" . $this->filtroModulo() . "
            ");
            $stmt->bindParam(':IdUsuario', $IdUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return ['success' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function marcarComoLeida() {
        try {
            $stmt = $this->_conexion->prepare("
                UPDATE notificaciones 
                SET EstadoNotificaciones = 1 
                WHERE IdNotificaciones = :IdNotificaciones AND EstadoNotificaciones = 0
            ");
            $stmt->bindParam(':IdNotificaciones',  $this->_IdNotificaciones);
            $stmt->execute();
            return ['success' => true];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    public function contarNoLeidas($usuario_id) {
        try {
            $stmt = $this->_conexion->prepare("
                SELECT COUNT(*) as total 
                FROM notificaciones 
                WHERE IdUsuario = :IdUsuario
                AND EstadoNotificaciones = 0" . $this->filtroModulo() . "
            ");
            $stmt->bindParam(':IdUsuario', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }


}
