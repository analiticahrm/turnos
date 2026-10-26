<?php
// API única de permisos del módulo de enfermería, nómina y administración.
//   Permisos::tiene('turnos.editar', $idServicio)   -> bool
//   Permisos::exigir('nomina.aprobar')              -> 403 JSON y exit si no la tiene
//   Permisos::serviciosPermitidos('turnos.ver')     -> null (todos los servicios) o la lista de ids permitidos
// Los permisos se calculan al iniciar sesión ($_SESSION['perm']) y se recargan solos si cambian (PERMISOS_VERSION)
// o si la sesión es anterior a este módulo. El administrador (CodigoRol = 'ADMIN') tiene todas las acciones.
// Alcance 'servicios': la acción solo vale para los servicios que el usuario coordina (tabla usuario_servicio).
// Contingencia: si las tablas de la migración 12 no existen, se usan los permisos por defecto del rol deducido
// del nombre (como hasta ahora) en lugar de dejar a todos sin acceso.
require_once __DIR__ . '/PermisosCatalogo.php';

class Permisos {
    // Segundos entre revisiones de la versión de permisos en la base de datos.
    const REVISAR_CADA = 3;

    private static $tablasListas = null;

    // ------------------------------------------------------------------ consulta

    public static function logueado() {
        return self::perm() !== null;
    }

    public static function esAdmin() {
        $p = self::perm();
        return $p !== null && !empty($p['admin']);
    }

    public static function codigoRol() {
        $p = self::perm();
        return $p !== null ? $p['codigoRol'] : null;
    }

    // true si el usuario puede realizar la acción; con $idServicio, solo si ese servicio está dentro de su alcance.
    // Sin $idServicio y con alcance 'servicios' devuelve true si coordina al menos un servicio (sirve para menús).
    public static function tiene($codigo, $idServicio = null) {
        $p = self::perm();
        if ($p === null) return false;
        if (!empty($p['admin'])) return true;
        $alcance = $p['acciones'][$codigo] ?? null;
        if ($alcance === null) return false;
        if ($alcance === 'todos') return true;
        if ($idServicio === null || $idServicio === '') return count($p['servicios']) > 0;
        return in_array((int)$idServicio, $p['servicios'], true);
    }

    // true solo si la acción vale para TODOS los servicios (los datos globales: nómina, maestros, personal).
    public static function tieneGlobal($codigo) {
        $p = self::perm();
        if ($p === null) return false;
        if (!empty($p['admin'])) return true;
        return ($p['acciones'][$codigo] ?? null) === 'todos';
    }

    public static function tieneAlguna(array $codigos, $soloGlobal = false) {
        foreach ($codigos as $c) {
            if ($soloGlobal ? self::tieneGlobal($c) : self::tiene($c)) return true;
        }
        return false;
    }

    // null = todos los servicios; array = ids permitidos (vacío = ninguno).
    public static function serviciosPermitidos($codigo) {
        $p = self::perm();
        if ($p === null) return [];
        if (!empty($p['admin'])) return null;
        $alcance = $p['acciones'][$codigo] ?? null;
        if ($alcance === null) return [];
        if ($alcance === 'todos') return null;
        return $p['servicios'];
    }

    // Servicios que coordina el usuario (usuario_servicio), con independencia de la acción.
    public static function serviciosDelUsuario() {
        $p = self::perm();
        return $p === null ? [] : $p['servicios'];
    }

    // Usuario cuyas únicas acciones son las del portal del enfermero (no entra a la consola de coordinación).
    public static function esSoloPortal() {
        $p = self::perm();
        if ($p === null || !empty($p['admin']) || empty($p['acciones'])) return false;
        return count(array_diff(array_keys($p['acciones']), PermisosCatalogo::accionesPortal())) === 0;
    }

    // Tiene alguna acción de la consola de coordinación (cualquiera que no sea del portal).
    public static function tieneAccesoConsola() {
        $p = self::perm();
        if ($p === null) return false;
        if (!empty($p['admin'])) return true;
        return count(array_diff(array_keys($p['acciones']), PermisosCatalogo::accionesPortal())) > 0;
    }

    // Resumen para el cliente (menús, botones). La decisión real siempre se vuelve a tomar en el servidor.
    public static function resumen() {
        $p = self::perm();
        if ($p === null) return ['logueado' => false];
        return [
            'logueado'  => true,
            'codigoRol' => $p['codigoRol'],
            'admin'     => !empty($p['admin']),
            'acciones'  => (object)$p['acciones'],
            'servicios' => $p['servicios'],
        ];
    }

    // ------------------------------------------------------------------ exigencias (endpoints)

    public static function denegar($mensaje, $http = 403) {
        if (!headers_sent()) {
            http_response_code($http);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'sinSesion' => ($http === 401), 'message' => $mensaje]);
        exit;
    }

    public static function exigir($codigo, $idServicio = null) {
        if (!self::logueado()) {
            self::denegar('Inicia sesión para continuar.', 401);
        }
        if (!self::tiene($codigo, $idServicio)) {
            self::denegar('No tienes permiso para esta acción.');
        }
    }

    public static function exigirGlobal($codigo, $mensaje = 'No tienes permiso para esta acción.') {
        if (!self::logueado()) {
            self::denegar('Inicia sesión para continuar.', 401);
        }
        if (!self::tieneGlobal($codigo)) {
            self::denegar($mensaje);
        }
    }

    public static function exigirAlguna(array $codigos, $soloGlobal = false, $mensaje = 'No tienes permiso para esta acción.') {
        if (!self::logueado()) {
            self::denegar('Inicia sesión para continuar.', 401);
        }
        if (!self::tieneAlguna($codigos, $soloGlobal)) {
            self::denegar($mensaje);
        }
    }

    // ------------------------------------------------------------------ carga y vigencia

    // Calcula los permisos del usuario y los deja en la sesión. Se llama al iniciar sesión.
    public static function cargarEnSesion($pdo, $idUsuario) {
        self::iniciarSesion();
        $perm = self::calcular($pdo, (int)$idUsuario);
        if ($perm === null) {
            unset($_SESSION['perm']);
            return null;
        }
        $_SESSION['perm'] = $perm;
        return $perm;
    }

    // Calcula (sin tocar la sesión) los permisos efectivos de un usuario.
    public static function calcular($pdo, $idUsuario) {
        try {
            $st = $pdo->prepare("SELECT u.IdUsuario, u.IdRolUsuario, r.TipoRol, r.CodigoRol
                FROM usuario u LEFT JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE u.IdUsuario = :u");
            $st->bindValue(':u', (int)$idUsuario, PDO::PARAM_INT);
            $st->execute();
        } catch (PDOException $e) {
            // La migración 12 aún no se aplicó: no hay rol.CodigoRol.
            $st = $pdo->prepare("SELECT u.IdUsuario, u.IdRolUsuario, r.TipoRol, NULL AS CodigoRol
                FROM usuario u LEFT JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE u.IdUsuario = :u");
            $st->bindValue(':u', (int)$idUsuario, PDO::PARAM_INT);
            $st->execute();
        }
        $f = $st->fetch(PDO::FETCH_ASSOC);
        if (!$f) return null;

        $codigoRol = !empty($f['CodigoRol']) ? $f['CodigoRol'] : self::codigoPorNombre((string)$f['TipoRol']);
        $admin = ($codigoRol === 'ADMIN');
        $acciones = [];
        $servicios = [];
        $version = 0;

        if (self::tablasListas($pdo)) {
            $a = $pdo->prepare("SELECT CodigoAccion, Alcance FROM rol_accion WHERE IdRol = :r");
            $a->bindValue(':r', (int)$f['IdRolUsuario'], PDO::PARAM_INT);
            $a->execute();
            foreach ($a->fetchAll(PDO::FETCH_ASSOC) as $x) {
                $acciones[$x['CodigoAccion']] = $x['Alcance'];
            }
            $o = $pdo->prepare("SELECT CodigoAccion, Efecto, Alcance FROM usuario_accion WHERE IdUsuario = :u");
            $o->bindValue(':u', (int)$idUsuario, PDO::PARAM_INT);
            $o->execute();
            foreach ($o->fetchAll(PDO::FETCH_ASSOC) as $x) {
                if ($x['Efecto'] === 'denegar') {
                    unset($acciones[$x['CodigoAccion']]);
                } else {
                    $acciones[$x['CodigoAccion']] = $x['Alcance'];
                }
            }
            $s = $pdo->prepare("SELECT IdServicio FROM usuario_servicio WHERE IdUsuario = :u");
            $s->bindValue(':u', (int)$idUsuario, PDO::PARAM_INT);
            $s->execute();
            $servicios = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
            $version = self::versionActual($pdo);
        } else {
            $defecto = PermisosCatalogo::porDefecto();
            $acciones = $codigoRol !== null && isset($defecto[$codigoRol]) ? $defecto[$codigoRol] : [];
        }

        // Una acción sin alcance por servicio vale siempre para todos.
        foreach ($acciones as $codigo => $alcance) {
            if ($alcance !== 'todos' && !PermisosCatalogo::admiteServicio($codigo)) {
                $acciones[$codigo] = 'todos';
            }
        }

        return [
            'uid'       => (int)$idUsuario,
            'codigoRol' => $codigoRol,
            'admin'     => $admin,
            'acciones'  => $acciones,
            'servicios' => $servicios,
            'version'   => $version,
            'chk'       => time(),
        ];
    }

    // Sube la versión: las sesiones abiertas recargan sus permisos en unos segundos.
    public static function subirVersion($pdo) {
        if (!self::tablasListas($pdo)) return;
        $pdo->exec("UPDATE enfermeria_parametro SET Valor = CAST(Valor AS UNSIGNED) + 1 WHERE Clave = 'PERMISOS_VERSION'");
    }

    // Ids de usuario que tienen la acción para ese servicio (para decidir a quién notificar).
    // Sin $idServicio solo cuentan quienes la tienen para todos los servicios.
    public static function usuariosConAccion($pdo, $codigo, $idServicio = null, $incluirAdmin = false) {
        $ids = [];
        $idServicio = ($idServicio === null || $idServicio === '') ? null : (int)$idServicio;
        if (!self::tablasListas($pdo)) return [];

        $q = $pdo->prepare("SELECT u.IdUsuario, ra.Alcance FROM usuario u
            INNER JOIN rol_accion ra ON ra.IdRol = u.IdRolUsuario AND ra.CodigoAccion = :c");
        $q->bindValue(':c', $codigo);
        $q->execute();
        $porRol = $q->fetchAll(PDO::FETCH_ASSOC);

        $o = $pdo->prepare("SELECT IdUsuario, Efecto, Alcance FROM usuario_accion WHERE CodigoAccion = :c");
        $o->bindValue(':c', $codigo);
        $o->execute();
        $overrides = [];
        foreach ($o->fetchAll(PDO::FETCH_ASSOC) as $x) { $overrides[(int)$x['IdUsuario']] = $x; }

        $cand = [];
        foreach ($porRol as $x) { $cand[(int)$x['IdUsuario']] = $x['Alcance']; }
        foreach ($overrides as $uid => $x) {
            if ($x['Efecto'] === 'denegar') unset($cand[$uid]);
            else $cand[$uid] = $x['Alcance'];
        }

        $coordina = [];
        if ($idServicio !== null && $cand) {
            $s = $pdo->prepare("SELECT IdUsuario FROM usuario_servicio WHERE IdServicio = :s");
            $s->bindValue(':s', $idServicio, PDO::PARAM_INT);
            $s->execute();
            $coordina = array_flip(array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN)));
        }
        foreach ($cand as $uid => $alcance) {
            if ($alcance === 'todos' || !PermisosCatalogo::admiteServicio($codigo)) { $ids[$uid] = true; }
            elseif ($idServicio !== null && isset($coordina[$uid])) { $ids[$uid] = true; }
        }

        if ($incluirAdmin) {
            try {
                $adm = $pdo->query("SELECT u.IdUsuario FROM usuario u INNER JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE r.CodigoRol = 'ADMIN'");
                foreach ($adm->fetchAll(PDO::FETCH_COLUMN) as $uid) { $ids[(int)$uid] = true; }
            } catch (PDOException $e) { /* sin CodigoRol */ }
        }
        return array_map('intval', array_keys($ids));
    }

    // Rol deducido del nombre, solo para roles que aún no tienen CodigoRol (misma lógica que los strpos de antes,
    // pero con un resultado único y sin que "Coordinador de servicio" herede el acceso total de coordinación).
    public static function codigoPorNombre($tipoRol) {
        if (strpos($tipoRol, 'Administrador') !== false) return 'ADMIN';
        if (strpos($tipoRol, 'Talento Humano') !== false) return 'TALENTO_HUMANO';
        if (stripos($tipoRol, 'Coordinador de servicio') !== false) return 'COORD_SERVICIO';
        if (strpos($tipoRol, 'Coordinador') !== false) return 'COORD_ENFERMERIA';
        if (strpos($tipoRol, 'Subgerencia') !== false) return 'SUBGERENCIA';
        if (strpos($tipoRol, 'Nomina') !== false) return 'NOMINA';
        if ($tipoRol === 'Enfermero') return 'ENFERMERO';
        if ($tipoRol === 'Auxiliar') return 'AUXILIAR';
        return null;
    }

    // ------------------------------------------------------------------ internos

    private static function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function tablasListas($pdo) {
        if (self::$tablasListas === null) {
            try {
                $r = $pdo->query("SHOW TABLES LIKE 'rol_accion'");
                self::$tablasListas = $r && $r->fetchColumn() !== false;
            } catch (PDOException $e) {
                self::$tablasListas = false;
            }
        }
        return self::$tablasListas;
    }

    private static function versionActual($pdo) {
        try {
            $v = $pdo->query("SELECT Valor FROM enfermeria_parametro WHERE Clave = 'PERMISOS_VERSION'")->fetchColumn();
            return $v === false ? 0 : (int)$v;
        } catch (PDOException $e) {
            return 0;
        }
    }

    private static function conectar() {
        include_once __DIR__ . '/conexionPDO.php';
        $obj = new Conexion;
        return $obj->conectar();
    }

    // Permisos de la sesión actual; los carga o recarga si hace falta. null = sin sesión.
    private static function perm() {
        self::iniciarSesion();
        $uid = (int)($_SESSION['IdUsuario'] ?? 0);
        if ($uid === 0) return null;
        $p = $_SESSION['perm'] ?? null;
        if (!is_array($p) || (int)($p['uid'] ?? 0) !== $uid) {
            return self::recargar($uid);
        }
        if (time() - (int)$p['chk'] >= self::REVISAR_CADA) {
            $pdo = self::conectar();
            if ($pdo) {
                $v = self::tablasListas($pdo) ? self::versionActual($pdo) : 0;
                if ($v !== (int)$p['version']) {
                    return self::recargar($uid, $pdo);
                }
                $_SESSION['perm']['chk'] = time();
            }
        }
        return $_SESSION['perm'];
    }

    private static function recargar($uid, $pdo = null) {
        if ($pdo === null) $pdo = self::conectar();
        if (!$pdo) return $_SESSION['perm'] ?? null;
        $perm = self::calcular($pdo, $uid);
        if ($perm === null) {
            unset($_SESSION['perm']);
            return null;
        }
        $_SESSION['perm'] = $perm;
        return $perm;
    }
}
