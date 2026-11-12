-- Casilla «Generar turnos automáticos» de los perfiles de enfermería (Coordinación de Enfermería > Perfiles).
-- Los perfiles que ya existen quedan marcados (1). Ejecutar una sola vez.
ALTER TABLE perfil
    ADD COLUMN GenerarTurnosAutomaticos TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = el perfil entra en la generación automática de turnos' AFTER IdEstado;
