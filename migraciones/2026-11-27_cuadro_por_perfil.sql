-- =====================================================================
-- Cuadro de turnos de enfermería por servicio, mes y PERFIL.
-- Antes había un solo cuadro por servicio y mes: confirmar o reabrir afectaba a todos los perfiles a la vez.
-- Ahora cada perfil (Auxiliar de enfermería, Jefe de enfermería…) tiene su propio cuadro, que se confirma y se
-- reabre por separado. Con "Todos" seleccionado en la pantalla se confirman o reabren todos los perfiles.
--
-- Qué hace:
--   1. Agrega la columna IdPerfil a cuadro_turnos_enfermeria y la clave única (servicio, año, mes, perfil).
--   2. Divide cada cuadro existente en un cuadro por perfil con el MISMO estado, versión y datos de confirmación,
--      para que nadie deje de ver nada: lo confirmado sigue confirmado para todos los perfiles.
--   3. Pasa cada destinatario de correo al cuadro del perfil de esa persona y borra los cuadros viejos sin perfil.
--
-- Subir primero los archivos PHP/JS nuevos (funcionan con y sin esta migración) y después correr este script.
-- Correr completo. Si se corta a la mitad, se puede volver a correr: cada paso revisa lo que ya está hecho.
-- MariaDB 10.4+. Importar como UTF-8.
-- =====================================================================
SET NAMES utf8mb4;

-- 0. Respaldo de las dos tablas tal como están (sirve para volver atrás). Si ya existe de una corrida anterior, se
--    conserva ese (el original).
CREATE TABLE IF NOT EXISTS respaldo_cuadro_turnos_20261127 AS SELECT * FROM cuadro_turnos_enfermeria;
CREATE TABLE IF NOT EXISTS respaldo_cuadro_correo_20261127 AS SELECT * FROM cuadro_correo_destinatario;

-- 1. Columna, clave nueva y fuera la clave vieja (un cuadro por servicio y mes), que no dejaría crear un cuadro por
--    perfil. La clave nueva empieza por IdServicio, así la llave foránea del servicio sigue teniendo índice.
ALTER TABLE cuadro_turnos_enfermeria
    ADD COLUMN IF NOT EXISTS IdPerfil INT NOT NULL DEFAULT 0
        COMMENT 'perfil de enfermería del cuadro (perfil.IdPerfil); 0 solo durante la migración' AFTER IdServicio;

ALTER TABLE cuadro_turnos_enfermeria
    ADD UNIQUE KEY IF NOT EXISTS uq_cuadro_servicio_mes_perfil (IdServicio, Anio, Mes, IdPerfil);

ALTER TABLE cuadro_turnos_enfermeria DROP INDEX IF EXISTS uq_cuadro_servicio_mes;

START TRANSACTION;

-- 2. Un cuadro por perfil, copiando el estado del cuadro del servicio.
INSERT INTO cuadro_turnos_enfermeria
    (IdServicio, IdPerfil, Anio, Mes, Estado, Version, ConfirmadoPorId, ConfirmadoPor, FechaConfirmacion,
     ReabiertoPorId, ReabiertoPor, FechaReapertura, MotivoReapertura)
SELECT c.IdServicio, p.IdPerfil, c.Anio, c.Mes, c.Estado, c.Version, c.ConfirmadoPorId, c.ConfirmadoPor, c.FechaConfirmacion,
       c.ReabiertoPorId, c.ReabiertoPor, c.FechaReapertura, c.MotivoReapertura
FROM cuadro_turnos_enfermeria c
CROSS JOIN perfil p
WHERE c.IdPerfil = 0
  AND NOT EXISTS (SELECT 1 FROM cuadro_turnos_enfermeria x
                  WHERE x.IdServicio = c.IdServicio AND x.Anio = c.Anio AND x.Mes = c.Mes AND x.IdPerfil = p.IdPerfil);

-- 3. Cada destinatario pasa al cuadro de su perfil: el de sus turnos en ese servicio y mes; si no tiene, su primer perfil.
UPDATE cuadro_correo_destinatario d
JOIN cuadro_turnos_enfermeria viejo ON viejo.IdCuadro = d.IdCuadro AND viejo.IdPerfil = 0
JOIN cuadro_turnos_enfermeria nuevo
  ON nuevo.IdServicio = viejo.IdServicio AND nuevo.Anio = viejo.Anio AND nuevo.Mes = viejo.Mes
 AND nuevo.IdPerfil = COALESCE(
        (SELECT pe.IdPerfil FROM turno_enfermeria te
         JOIN perfilenfermero pe ON pe.IdEnfPer = te.IdEnfPer
         WHERE pe.IdMedico = d.IdMedico
           AND COALESCE(te.IdServicio, (SELECT se.IdServicio FROM servicioenfermero se
                                        WHERE se.IdMedico = pe.IdMedico ORDER BY se.IdSerEnf LIMIT 1)) = viejo.IdServicio
           AND YEAR(te.FechaTurnoEnfermeria) = viejo.Anio AND MONTH(te.FechaTurnoEnfermeria) = viejo.Mes
         ORDER BY te.IdTurnoEnfermeria LIMIT 1),
        (SELECT pe2.IdPerfil FROM perfilenfermero pe2 WHERE pe2.IdMedico = d.IdMedico ORDER BY pe2.IdEnfPer LIMIT 1),
        (SELECT MIN(p.IdPerfil) FROM perfil p))
SET d.IdCuadro = nuevo.IdCuadro;

-- Los cuadros viejos (sin perfil) ya no tienen destinatarios: se borran.
DELETE FROM cuadro_turnos_enfermeria
WHERE IdPerfil = 0
  AND NOT EXISTS (SELECT 1 FROM cuadro_correo_destinatario d WHERE d.IdCuadro = cuadro_turnos_enfermeria.IdCuadro);

COMMIT;

-- 4. El perfil pasa a ser obligatorio (sin valor por defecto).
ALTER TABLE cuadro_turnos_enfermeria
    MODIFY IdPerfil INT NOT NULL COMMENT 'perfil de enfermería del cuadro (perfil.IdPerfil)';

-- Comprobación: debe dar 0 (ningún cuadro sin perfil).
SELECT COUNT(*) AS cuadros_sin_perfil FROM cuadro_turnos_enfermeria WHERE IdPerfil = 0;

-- REVERSA (volver a un cuadro por servicio, con los datos de antes de la migración):
--   DELETE FROM cuadro_correo_destinatario;
--   DELETE FROM cuadro_turnos_enfermeria;
--   ALTER TABLE cuadro_turnos_enfermeria DROP INDEX uq_cuadro_servicio_mes_perfil, DROP COLUMN IdPerfil,
--       ADD UNIQUE KEY uq_cuadro_servicio_mes (IdServicio, Anio, Mes);
--   INSERT INTO cuadro_turnos_enfermeria SELECT * FROM respaldo_cuadro_turnos_20261127;
--   INSERT INTO cuadro_correo_destinatario SELECT * FROM respaldo_cuadro_correo_20261127;
-- Cuando todo esté bien, los respaldos se pueden borrar:
--   DROP TABLE respaldo_cuadro_turnos_20261127; DROP TABLE respaldo_cuadro_correo_20261127;
