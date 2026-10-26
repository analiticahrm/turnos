<?php
// Administración del módulo de permisos de enfermería: matriz rol × acción, excepciones por usuario, servicios que
// coordina cada usuario y parámetros del módulo. Todo exige sesión y la acción correspondiente (la identidad y los
// permisos salen SIEMPRE de la sesión). Los cambios son POST, van en una transacción, quedan en la bitácora y suben
// PERMISOS_VERSION para que las sesiones abiertas recarguen sus permisos.
header('Content-Type: application/json; charset=utf-8');
session_start();

include_once("../Modelo/conexionPDO.php");
include_once("../Modelo/Permisos.php");
include_once("../Modelo/BitacoraTurnos.php");
include_once("../Modelo/ParametroEnfermeria.php");

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function exigirPost() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responder(['success' => false, 'message' => 'Esta acción solo se acepta por POST.'], 405);
    }
}

$accion = $_GET['Accion'] ?? '';

// MiSesion: cualquier usuario con sesión consulta sus propios permisos (menús y botones del cliente).
if ($accion === 'MiSesion') {
    if (!Permisos::logueado()) responder(['success' => false, 'sinSesion' => true, 'message' => 'Inicia sesión para continuar.'], 401);
    responder(['success' => true, 'sesion' => Permisos::resumen()]);
}

// Parámetros: su propia acción; el resto de la pantalla exige administrar permisos.
if ($accion === 'Parametros' || $accion === 'GuardarParametros') {
    Permisos::exigirGlobal('parametros.enfermeria');
} else {
    Permisos::exigirGlobal('permisos.administrar');
}

$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();
if (!$conexion) responder(['success' => false, 'message' => 'No se pudo conectar con la base de datos.'], 500);

$idActor = (int)($_SESSION['IdUsuario'] ?? 0);
$nombreActor = $_SESSION['NombreUsuario'] ?? '';

// Las tablas de la migración 12 deben existir para editar permisos.
function exigirTablasPermisos($conexion) {
    $r = $conexion->query("SHOW TABLES LIKE 'rol_accion'");
    if (!$r || $r->fetchColumn() === false) {
        responder(['success' => false, 'message' => 'Falta aplicar la migración 12 (permisos y roles) en la base de datos.'], 409);
    }
}

// Acciones del cliente -> [codigo => alcance] validadas contra el catálogo.
function acciones_validas($json) {
    $arr = json_decode((string)$json, true);
    if (!is_array($arr)) responder(['success' => false, 'message' => 'Las acciones no tienen un formato válido.'], 400);
    $r = [];
    foreach ($arr as $codigo => $alcance) {
        if (!PermisosCatalogo::existe($codigo)) responder(['success' => false, 'message' => 'Acción desconocida: ' . $codigo], 400);
        if (!in_array($alcance, ['todos', 'servicios'], true)) responder(['success' => false, 'message' => 'Alcance no válido para ' . $codigo], 400);
        $r[$codigo] = PermisosCatalogo::admiteServicio($codigo) ? $alcance : 'todos';
    }
    return $r;
}

try {
    switch ($accion) {

        case 'Catalogo':
            $acciones = [];
            foreach (PermisosCatalogo::acciones() as $codigo => $a) {
                $acciones[] = ['codigo' => $codigo, 'grupo' => $a[0], 'etiqueta' => $a[1], 'descripcion' => $a[2], 'servicio' => $a[3]];
            }
            $grupos = [];
            foreach (PermisosCatalogo::grupos() as $k => $et) { $grupos[] = ['grupo' => $k, 'etiqueta' => $et]; }
            try {
                $roles = $conexion->query("SELECT r.IdRol, r.TipoRol, r.CodigoRol, r.EsSistema, r.EstadoRol,
                        (SELECT COUNT(*) FROM usuario u WHERE u.IdRolUsuario = r.IdRol) AS Usuarios
                    FROM rol r ORDER BY r.EsSistema DESC, r.TipoRol")->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $roles = $conexion->query("SELECT r.IdRol, r.TipoRol, NULL AS CodigoRol, 0 AS EsSistema, r.EstadoRol,
                        (SELECT COUNT(*) FROM usuario u WHERE u.IdRolUsuario = r.IdRol) AS Usuarios
                    FROM rol r ORDER BY r.TipoRol")->fetchAll(PDO::FETCH_ASSOC);
            }
            $servicios = $conexion->query("SELECT IdServicio, NombreServicio FROM servicio WHERE IdEstado = 1 ORDER BY NombreServicio")->fetchAll(PDO::FETCH_ASSOC);
            responder(['success' => true, 'grupos' => $grupos, 'acciones' => $acciones, 'roles' => $roles, 'servicios' => $servicios,
                'yo' => ['IdUsuario' => $idActor, 'IdRol' => (int)($_SESSION['IdRolUsuario'] ?? 0)]]);
            break;

        case 'MatrizRol':
            exigirTablasPermisos($conexion);
            $idRol = (int)($_GET['IdRol'] ?? 0);
            $st = $conexion->prepare("SELECT IdRol, TipoRol, CodigoRol FROM rol WHERE IdRol = :r");
            $st->bindValue(':r', $idRol, PDO::PARAM_INT);
            $st->execute();
            $rol = $st->fetch(PDO::FETCH_ASSOC);
            if (!$rol) responder(['success' => false, 'message' => 'Rol no encontrado.'], 404);
            $a = $conexion->prepare("SELECT CodigoAccion, Alcance FROM rol_accion WHERE IdRol = :r");
            $a->bindValue(':r', $idRol, PDO::PARAM_INT);
            $a->execute();
            $mapa = [];
            foreach ($a->fetchAll(PDO::FETCH_ASSOC) as $x) { $mapa[$x['CodigoAccion']] = $x['Alcance']; }
            responder(['success' => true, 'rol' => $rol, 'esAdmin' => ($rol['CodigoRol'] === 'ADMIN'), 'acciones' => (object)$mapa]);
            break;

        case 'GuardarMatrizRol':
            exigirPost();
            exigirTablasPermisos($conexion);
            $idRol = (int)($_POST['IdRol'] ?? 0);
            $nuevas = acciones_validas($_POST['Acciones'] ?? '');
            $st = $conexion->prepare("SELECT IdRol, TipoRol, CodigoRol FROM rol WHERE IdRol = :r");
            $st->bindValue(':r', $idRol, PDO::PARAM_INT);
            $st->execute();
            $rol = $st->fetch(PDO::FETCH_ASSOC);
            if (!$rol) responder(['success' => false, 'message' => 'Rol no encontrado.'], 404);
            if ($rol['CodigoRol'] === 'ADMIN') {
                responder(['success' => false, 'message' => 'El rol Administrador tiene todos los permisos y no se edita.'], 400);
            }
            // No dejar que alguien se quite a sí mismo el acceso a esta pantalla.
            if ($idRol === (int)($_SESSION['IdRolUsuario'] ?? -1) && !Permisos::esAdmin() && !isset($nuevas['permisos.administrar'])) {
                responder(['success' => false, 'message' => 'No puedes quitarle a tu propio rol el permiso de administrar permisos.'], 400);
            }
            $conexion->beginTransaction();
            $ant = $conexion->prepare("SELECT CodigoAccion, Alcance FROM rol_accion WHERE IdRol = :r ORDER BY CodigoAccion");
            $ant->bindValue(':r', $idRol, PDO::PARAM_INT);
            $ant->execute();
            $antes = [];
            foreach ($ant->fetchAll(PDO::FETCH_ASSOC) as $x) { $antes[$x['CodigoAccion']] = $x['Alcance']; }
            $del = $conexion->prepare("DELETE FROM rol_accion WHERE IdRol = :r");
            $del->bindValue(':r', $idRol, PDO::PARAM_INT);
            $del->execute();
            $ins = $conexion->prepare("INSERT INTO rol_accion (IdRol, CodigoAccion, Alcance) VALUES (:r, :c, :a)");
            foreach ($nuevas as $codigo => $alcance) {
                $ins->bindValue(':r', $idRol, PDO::PARAM_INT);
                $ins->bindValue(':c', $codigo);
                $ins->bindValue(':a', $alcance);
                $ins->execute();
            }
            ksort($nuevas);
            BitacoraTurnos::registrar($conexion, ['accion' => 'permisos_rol', 'motivo' => 'Rol: ' . $rol['TipoRol'], 'antes' => $antes, 'despues' => $nuevas]);
            Permisos::subirVersion($conexion);
            $conexion->commit();
            responder(['success' => true, 'message' => 'Permisos del rol guardados.']);
            break;

        case 'CrearRol':
            exigirPost();
            $nombre = trim((string)($_POST['TipoRol'] ?? ''));
            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
                responder(['success' => false, 'message' => 'El nombre del rol debe tener entre 3 y 100 caracteres.'], 400);
            }
            $ex = $conexion->prepare("SELECT 1 FROM rol WHERE TipoRol = :n");
            $ex->bindValue(':n', $nombre);
            $ex->execute();
            if ($ex->fetchColumn()) responder(['success' => false, 'message' => 'Ya existe un rol con ese nombre.'], 409);
            $ins = $conexion->prepare("INSERT INTO rol (TipoRol, EstadoRol) VALUES (:n, 1)");
            $ins->bindValue(':n', $nombre);
            $ins->execute();
            responder(['success' => true, 'IdRol' => (int)$conexion->lastInsertId(), 'message' => 'Rol creado. Ahora asígnale sus permisos.']);
            break;

        case 'BuscarUsuarios':
            $q = trim((string)($_GET['q'] ?? ''));
            $idRol = (int)($_GET['IdRol'] ?? 0);
            $sql = "SELECT u.IdUsuario, u.NombreUsuario, u.DocumentoUsuario, u.CorreoUsuario, u.IdRolUsuario, r.TipoRol
                FROM usuario u LEFT JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE 1 = 1";
            $par = [];
            if ($q !== '') {
                $sql .= " AND (u.NombreUsuario LIKE :q OR u.DocumentoUsuario LIKE :q OR u.CorreoUsuario LIKE :q)";
                $par[':q'] = '%' . $q . '%';
            }
            if ($idRol > 0) { $sql .= " AND u.IdRolUsuario = :r"; $par[':r'] = $idRol; }
            $sql .= " ORDER BY u.NombreUsuario LIMIT 40";
            $st = $conexion->prepare($sql);
            foreach ($par as $k => $v) { $st->bindValue($k, $v); }
            $st->execute();
            responder(['success' => true, 'usuarios' => $st->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'PermisosUsuario':
            exigirTablasPermisos($conexion);
            $idUsuario = (int)($_GET['IdUsuario'] ?? 0);
            $st = $conexion->prepare("SELECT u.IdUsuario, u.NombreUsuario, u.DocumentoUsuario, u.CorreoUsuario, u.IdRolUsuario, r.TipoRol, r.CodigoRol
                FROM usuario u LEFT JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE u.IdUsuario = :u");
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $st->execute();
            $usuario = $st->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) responder(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
            $ar = $conexion->prepare("SELECT CodigoAccion, Alcance FROM rol_accion WHERE IdRol = :r");
            $ar->bindValue(':r', (int)$usuario['IdRolUsuario'], PDO::PARAM_INT);
            $ar->execute();
            $delRol = [];
            foreach ($ar->fetchAll(PDO::FETCH_ASSOC) as $x) { $delRol[$x['CodigoAccion']] = $x['Alcance']; }
            $ov = $conexion->prepare("SELECT CodigoAccion, Efecto, Alcance FROM usuario_accion WHERE IdUsuario = :u");
            $ov->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $ov->execute();
            $overrides = [];
            foreach ($ov->fetchAll(PDO::FETCH_ASSOC) as $x) { $overrides[$x['CodigoAccion']] = ['efecto' => $x['Efecto'], 'alcance' => $x['Alcance']]; }
            $sv = $conexion->prepare("SELECT IdServicio FROM usuario_servicio WHERE IdUsuario = :u");
            $sv->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $sv->execute();
            responder(['success' => true, 'usuario' => $usuario, 'esAdmin' => ($usuario['CodigoRol'] === 'ADMIN'),
                'accionesRol' => (object)$delRol, 'overrides' => (object)$overrides,
                'servicios' => array_map('intval', $sv->fetchAll(PDO::FETCH_COLUMN))]);
            break;

        case 'GuardarPermisosUsuario':
            exigirPost();
            exigirTablasPermisos($conexion);
            $idUsuario = (int)($_POST['IdUsuario'] ?? 0);
            $st = $conexion->prepare("SELECT u.IdUsuario, u.NombreUsuario, r.CodigoRol FROM usuario u LEFT JOIN rol r ON r.IdRol = u.IdRolUsuario WHERE u.IdUsuario = :u");
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $st->execute();
            $usuario = $st->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) responder(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
            if ($usuario['CodigoRol'] === 'ADMIN') {
                responder(['success' => false, 'message' => 'Un administrador tiene todos los permisos; no admite excepciones.'], 400);
            }
            $ov = json_decode((string)($_POST['Overrides'] ?? '{}'), true);
            $sv = json_decode((string)($_POST['Servicios'] ?? '[]'), true);
            if (!is_array($ov) || !is_array($sv)) responder(['success' => false, 'message' => 'Datos con formato no válido.'], 400);
            $overrides = [];
            foreach ($ov as $codigo => $o) {
                if (!PermisosCatalogo::existe($codigo)) responder(['success' => false, 'message' => 'Acción desconocida: ' . $codigo], 400);
                $efecto = $o['efecto'] ?? '';
                $alcance = $o['alcance'] ?? 'todos';
                if (!in_array($efecto, ['permitir', 'denegar'], true) || !in_array($alcance, ['todos', 'servicios'], true)) {
                    responder(['success' => false, 'message' => 'Excepción no válida para ' . $codigo], 400);
                }
                $overrides[$codigo] = ['efecto' => $efecto, 'alcance' => PermisosCatalogo::admiteServicio($codigo) ? $alcance : 'todos'];
            }
            if ($idUsuario === $idActor && !Permisos::esAdmin() && ($overrides['permisos.administrar']['efecto'] ?? '') === 'denegar') {
                responder(['success' => false, 'message' => 'No puedes quitarte a ti mismo el permiso de administrar permisos.'], 400);
            }
            $servicios = array_values(array_unique(array_filter(array_map('intval', $sv))));

            $conexion->beginTransaction();
            $antOv = $conexion->prepare("SELECT CodigoAccion, Efecto, Alcance FROM usuario_accion WHERE IdUsuario = :u ORDER BY CodigoAccion");
            $antOv->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $antOv->execute();
            $antSv = $conexion->prepare("SELECT IdServicio FROM usuario_servicio WHERE IdUsuario = :u ORDER BY IdServicio");
            $antSv->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $antSv->execute();
            $antes = ['excepciones' => $antOv->fetchAll(PDO::FETCH_ASSOC), 'servicios' => array_map('intval', $antSv->fetchAll(PDO::FETCH_COLUMN))];

            $d1 = $conexion->prepare("DELETE FROM usuario_accion WHERE IdUsuario = :u");
            $d1->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $d1->execute();
            $i1 = $conexion->prepare("INSERT INTO usuario_accion (IdUsuario, CodigoAccion, Efecto, Alcance) VALUES (:u, :c, :e, :a)");
            foreach ($overrides as $codigo => $o) {
                $i1->bindValue(':u', $idUsuario, PDO::PARAM_INT);
                $i1->bindValue(':c', $codigo);
                $i1->bindValue(':e', $o['efecto']);
                $i1->bindValue(':a', $o['alcance']);
                $i1->execute();
            }
            $d2 = $conexion->prepare("DELETE FROM usuario_servicio WHERE IdUsuario = :u");
            $d2->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $d2->execute();
            if ($servicios) {
                // Solo servicios que existen.
                $marcas = implode(',', array_fill(0, count($servicios), '?'));
                $ex = $conexion->prepare("SELECT IdServicio FROM servicio WHERE IdServicio IN ($marcas)");
                $ex->execute($servicios);
                $validos = array_map('intval', $ex->fetchAll(PDO::FETCH_COLUMN));
                $i2 = $conexion->prepare("INSERT INTO usuario_servicio (IdUsuario, IdServicio, AsignadoPor) VALUES (:u, :s, :p)");
                foreach ($validos as $idServicio) {
                    $i2->bindValue(':u', $idUsuario, PDO::PARAM_INT);
                    $i2->bindValue(':s', $idServicio, PDO::PARAM_INT);
                    $i2->bindValue(':p', mb_substr((string)$nombreActor, 0, 100));
                    $i2->execute();
                }
                $servicios = $validos;
            }
            ksort($overrides);
            BitacoraTurnos::registrar($conexion, ['accion' => 'permisos_usuario', 'motivo' => 'Usuario: ' . $usuario['NombreUsuario'],
                'antes' => $antes, 'despues' => ['excepciones' => $overrides, 'servicios' => $servicios]]);
            Permisos::subirVersion($conexion);
            $conexion->commit();
            responder(['success' => true, 'message' => 'Permisos del usuario guardados.']);
            break;

        case 'Parametros':
            $def = ParametroEnfermeria::definicion();
            $valores = ParametroEnfermeria::todos($conexion);
            $lista = [];
            foreach ($def as $clave => $d) {
                $lista[] = ['clave' => $clave, 'valor' => $valores[$clave] ?? $d[0], 'descripcion' => $d[1], 'editable' => $d[2], 'validacion' => $d[3]];
            }
            responder(['success' => true, 'parametros' => $lista]);
            break;

        case 'GuardarParametros':
            exigirPost();
            $datos = json_decode((string)($_POST['Parametros'] ?? '{}'), true);
            if (!is_array($datos) || !$datos) responder(['success' => false, 'message' => 'No hay parámetros para guardar.'], 400);
            $def = ParametroEnfermeria::definicion();
            $normalizados = [];
            foreach ($datos as $clave => $valor) {
                if (!isset($def[$clave]) || !$def[$clave][2]) responder(['success' => false, 'message' => 'El parámetro ' . $clave . ' no se puede editar.'], 400);
                list($ok, $v) = ParametroEnfermeria::validar($clave, $valor);
                if (!$ok) responder(['success' => false, 'message' => $v], 400);
                $normalizados[$clave] = $v;
            }
            $conexion->beginTransaction();
            $antes = ParametroEnfermeria::todos($conexion);
            foreach ($normalizados as $clave => $v) { ParametroEnfermeria::guardar($conexion, $clave, $v, $nombreActor); }
            BitacoraTurnos::registrar($conexion, ['accion' => 'parametros',
                'antes' => array_intersect_key($antes, $normalizados), 'despues' => $normalizados]);
            $conexion->commit();
            responder(['success' => true, 'message' => 'Parámetros guardados.']);
            break;

        default:
            responder(['success' => false, 'message' => 'Acción no válida'], 400);
    }
} catch (PDOException $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    error_log('ControladorPermisosEnfermeria: ' . $e->getMessage());
    responder(['success' => false, 'message' => 'No se pudo completar la operación: ' . $e->getMessage()], 500);
}
