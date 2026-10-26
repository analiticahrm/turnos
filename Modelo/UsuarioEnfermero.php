<?php
// Usuario del sistema de un enfermero o auxiliar (medico con IdEspecialidadMedico = 149).
// El trigger crear_usuario_despues_de_insertar_medico crea el usuario al insertar el medico, pero en ese
// instante todavía no hay perfil; aquí se crea (si falta) o se ajusta el rol una vez asignado el perfil:
// perfil "auxiliar" -> rol Auxiliar, cualquier otro perfil -> rol Enfermero. Nunca se toca a quien tenga otro
// rol (Administrador, Supervisor, Nomina...). La clave inicial es MD5(documento), igual que el trigger, y el
// usuario queda obligado a cambiarla en su primer ingreso al portal (usuario.DebeCambiarClave).
class UsuarioEnfermero {
    const ROL_ENFERMERO = 'Enfermero';
    const ROL_AUXILIAR = 'Auxiliar';

    // Nombre del rol que corresponde a la descripción de un perfil.
    static function rolPorPerfil($descripcionPerfil) {
        $texto = mb_strtolower((string)$descripcionPerfil, 'UTF-8');
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        return strpos($texto, 'auxiliar') !== false ? self::ROL_AUXILIAR : self::ROL_ENFERMERO;
    }

    // Crea o ajusta el usuario del enfermero. No lanza excepciones: un problema con el usuario no debe
    // impedir guardar al enfermero; el resultado informa qué pasó.
    static function sincronizar(PDO $conexion, $idMedico) {
        try {
            $stmt = $conexion->prepare("SELECT NombreMedico, DocumentoMedico, Correomedico, TelefonoMedico, IdEspecialidadMedico
                FROM medico WHERE IdMedico = ?");
            $stmt->execute([$idMedico]);
            $medico = $stmt->fetch(PDO::FETCH_ASSOC);
            $documento = $medico ? trim((string)$medico['DocumentoMedico']) : '';
            if (!$medico || (int)$medico['IdEspecialidadMedico'] !== 149 || $documento === '') {
                return ['success' => true, 'accion' => 'omitido'];
            }

            $roles = [];
            foreach ($conexion->query("SELECT IdRol, TipoRol FROM rol WHERE TipoRol IN ('Enfermero', 'Auxiliar', 'Asistencial')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $roles[$r['TipoRol']] = (int)$r['IdRol'];
            }
            if (!isset($roles[self::ROL_ENFERMERO]) || !isset($roles[self::ROL_AUXILIAR])) {
                return ['success' => false, 'accion' => 'omitido', 'message' => 'Faltan los roles Enfermero/Auxiliar (migración 06).'];
            }

            $stmt = $conexion->prepare("SELECT p.DescripcionPerfil FROM perfilenfermero pe
                INNER JOIN perfil p ON p.IdPerfil = pe.IdPerfil
                WHERE pe.IdMedico = ? ORDER BY pe.IdEnfPer LIMIT 1");
            $stmt->execute([$idMedico]);
            $descripcionPerfil = $stmt->fetchColumn();
            // Sin perfil todavía no se sabe si es enfermero o auxiliar: solo vale para crear el usuario.
            $rolDeseado = $roles[$descripcionPerfil !== false ? self::rolPorPerfil($descripcionPerfil) : self::ROL_ENFERMERO];

            $tieneMarca = (bool)$conexion->query("SHOW COLUMNS FROM usuario LIKE 'DebeCambiarClave'")->fetch();

            $stmt = $conexion->prepare("SELECT IdUsuario, IdRolUsuario FROM usuario WHERE DocumentoUsuario = ?");
            $stmt->execute([$documento]);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($usuarios)) {
                $columnas = "NombreUsuario, DocumentoUsuario, TelefonoUsuario, CorreoUsuario, ContraseñaUsuario, IdRolUsuario";
                $valores = "?, ?, ?, ?, MD5(?), ?";
                if ($tieneMarca) {
                    $columnas .= ", DebeCambiarClave";
                    $valores .= ", 1";
                }
                $ins = $conexion->prepare("INSERT INTO usuario ($columnas) VALUES ($valores)");
                $ins->execute([$medico['NombreMedico'], $documento, $medico['TelefonoMedico'], $medico['Correomedico'], $documento, $rolDeseado]);
                return ['success' => true, 'accion' => 'creado'];
            }

            // Ya existe (lo creó el trigger o es anterior): solo se cambia el rol si todavía es uno "de entrada".
            $modificables = [$roles['Asistencial'] ?? 3, $roles[self::ROL_ENFERMERO], $roles[self::ROL_AUXILIAR]];
            $cambiados = 0;
            if ($descripcionPerfil !== false) {
                $upd = $conexion->prepare("UPDATE usuario SET IdRolUsuario = ? WHERE IdUsuario = ? AND IdRolUsuario <> ?");
                foreach ($usuarios as $u) {
                    if (in_array((int)$u['IdRolUsuario'], $modificables, true)) {
                        $upd->execute([$rolDeseado, $u['IdUsuario'], $rolDeseado]);
                        $cambiados += $upd->rowCount();
                    }
                }
            }
            // Quien todavía usa la clave inicial (el documento) debe cambiarla antes de ver su sueldo.
            if ($tieneMarca) {
                $conexion->prepare("UPDATE usuario SET DebeCambiarClave = 1
                    WHERE DocumentoUsuario = ? AND DebeCambiarClave = 0 AND ContraseñaUsuario = MD5(?)
                      AND IdRolUsuario IN (?, ?)")
                    ->execute([$documento, $documento, $roles[self::ROL_ENFERMERO], $roles[self::ROL_AUXILIAR]]);
            }
            return ['success' => true, 'accion' => $cambiados > 0 ? 'rol_ajustado' : 'sin_cambios'];
        } catch (PDOException $e) {
            return ['success' => false, 'accion' => 'error', 'message' => $e->getMessage()];
        }
    }
}
