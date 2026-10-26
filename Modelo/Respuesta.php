<?php
class Respuesta {
    private $pdo;
    private $id_respuesta;
    private $id_evaluacion;
    private $id_pregunta;
    private $respuesta;

    public function __construct($pdo, $id_respuesta = null, $id_evaluacion = null, $id_pregunta = null, $respuesta = '') {
        $this->pdo = $pdo;
        $this->id_respuesta = $id_respuesta;
        $this->id_evaluacion = $id_evaluacion;
        $this->id_pregunta = $id_pregunta;
        $this->respuesta = $respuesta;
    }

    public function __get($key) {
        return $this->$key;
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function insertar() {
        $stmt = $this->pdo->prepare("INSERT INTO respuestas (id_evaluacion, id_pregunta, respuesta) VALUES (:id_evaluacion, :id_pregunta, :respuesta)");
        return $stmt->execute([
            ':id_evaluacion' => $this->id_evaluacion,
            ':id_pregunta' => $this->id_pregunta,
            ':respuesta' => $this->respuesta
        ]);
    }

    public function actualizar($id_respuesta, $nueva_respuesta) {
        $stmt = $this->pdo->prepare("UPDATE respuestas SET respuesta = :respuesta WHERE id_respuesta = :id_respuesta");
        return $stmt->execute([
            ':id_respuesta' => $id_respuesta,
            ':respuesta' => $nueva_respuesta
        ]);
    }

    public function listarPorEvaluacion() {
        $stmt = $this->pdo->prepare("SELECT r.*, p.texto_pregunta 
                                     FROM respuestas r 
                                     JOIN preguntas p ON r.id_pregunta = p.id_pregunta 
                                     WHERE r.id_evaluacion = :id_evaluacion");
        $stmt->execute([':id_evaluacion' => $this->id_evaluacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
}