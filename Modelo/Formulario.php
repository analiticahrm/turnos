<?php
class Formulario {
    private $pdo;
    private $id_formulario;
    private $nombre_formulario;
    private $descripcion;
    private $estado;

    public function __construct($pdo, $id_formulario = null, $nombre_formulario = '', $descripcion = '', $estado = 1) {
        $this->pdo = $pdo;
        $this->id_formulario = $id_formulario;
        $this->nombre_formulario = $nombre_formulario;
        $this->descripcion = $descripcion;
        $this->estado = $estado;
    }

    public function __get($key) {
        return $this->$key;
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function insertar() {
        $stmt = $this->pdo->prepare("INSERT INTO formularios (nombre_formulario, descripcion, estado) VALUES (:nombre, :descripcion, :estado)");
        $stmt->execute([
            ':nombre' => $this->nombre_formulario,
            ':descripcion' => $this->descripcion,
            ':estado' => $this->estado
        ]);
        return $this->pdo->lastInsertId();
    }

    public function modificar() {
        $stmt = $this->pdo->prepare("UPDATE formularios SET nombre_formulario = :nombre, descripcion = :descripcion, estado = :estado WHERE id_formulario = :id");
        return $stmt->execute([
            ':nombre' => $this->nombre_formulario,
            ':descripcion' => $this->descripcion,
            ':estado' => $this->estado,
            ':id' => $this->id_formulario
        ]);
    }

    public function eliminar() {
        $stmt = $this->pdo->prepare("DELETE FROM formularios WHERE id_formulario = :id");
        return $stmt->execute([':id' => $this->id_formulario]);
    }

public function listar($id_usuario = null, $id_rol = null) {
    // Si el rol es 1, 4 o 5, mostrar todos los formularios
    if (in_array($id_rol, [1, 4, 5])) {
        $sql = "SELECT * FROM formularios WHERE estado = 1 ORDER BY nombre_formulario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Si no tiene esos roles, mostrar solo los formularios asignados al usuario
        $sql = "SELECT A.* 
                FROM formularios A 
                INNER JOIN usuario_formulario B ON A.id_formulario = B.id_formulario
                WHERE B.id_usuario = :id_usuario AND A.estado = 1
                ORDER BY A.nombre_formulario";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function listarFull() {
        $sql = "SELECT * FROM formularios ORDER BY nombre_formulario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function listarActivos() {
        $stmt = $this->pdo->prepare("SELECT * FROM formularios WHERE estado = 1 ORDER BY nombre_formulario");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorId() {
        $stmt = $this->pdo->prepare("SELECT * FROM formularios WHERE id_formulario = :id");
        $stmt->execute([':id' => $this->id_formulario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cambiarEstado($nuevoEstado) {
        $stmt = $this->pdo->prepare("UPDATE formularios SET estado = :estado WHERE id_formulario = :id");
        return $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id' => $this->id_formulario
        ]);
    }

    // Métodos para gestión de formularios por usuario
    public function asignarAUsuario($id_usuario) {
        $stmt = $this->pdo->prepare("INSERT INTO usuario_formulario (id_usuario, id_formulario) VALUES (:id_usuario, :id_formulario)");
        return $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_formulario' => $this->id_formulario
        ]);
    }

    public function desasignarDeUsuario($id_usuario) {
        $stmt = $this->pdo->prepare("DELETE FROM usuario_formulario WHERE id_usuario = :id_usuario AND id_formulario = :id_formulario");
        return $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_formulario' => $this->id_formulario
        ]);
    }

    public function listarFormulariosPorUsuario($id_formulario) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, uf.fecha_asignacion, g.NombreUsuario, g.IdUsuario 
            FROM formularios f 
            INNER JOIN usuario_formulario uf ON f.id_formulario = uf.id_formulario 
            INNER JOIN usuario g ON uf.id_usuario = g.IdUsuario
            WHERE f.id_formulario = :id_formulario
            ORDER BY f.nombre_formulario
        ");
        $stmt->execute([':id_formulario' => $id_formulario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarFormulariosDisponiblesParaUsuario($id_usuario) {
        $stmt = $this->pdo->prepare("
            SELECT f.* 
            FROM formularios f 
            WHERE f.estado = 1 
            AND f.id_formulario NOT IN (
                SELECT uf.id_formulario 
                FROM usuario_formulario uf 
                WHERE uf.id_usuario = :id_usuario
            )
            ORDER BY f.nombre_formulario
        ");
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}