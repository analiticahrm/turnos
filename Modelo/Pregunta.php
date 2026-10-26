<?php
class Pregunta {
    private $pdo;
    private $id_pregunta;
    private $id_formulario;
    private $titulo_pregunta;
    private $texto_pregunta;
    private $tipo_respuesta;
    private $orden;
    private $opcion_1_texto;
    private $opcion_4_texto;
    private $opcion_5_texto;
    private $opcion_no_aplica_texto;

    public function __construct($pdo, $id_pregunta = null, $titulo_pregunta = '', $texto_pregunta = '', $tipo_respuesta = '', $orden = 0, $opcion_1_texto = '', $opcion_4_texto = '', $opcion_5_texto = '', $opcion_no_aplica_texto = '') {
        $this->pdo = $pdo;
        $this->id_pregunta = $id_pregunta;
        $this->titulo_pregunta = $titulo_pregunta;
        $this->texto_pregunta = $texto_pregunta;
        $this->tipo_respuesta = $tipo_respuesta;
        $this->orden = $orden;
        $this->opcion_1_texto = $opcion_1_texto;
        $this->opcion_4_texto = $opcion_4_texto;
        $this->opcion_5_texto = $opcion_5_texto;
        $this->opcion_no_aplica_texto = $opcion_no_aplica_texto;
    }

    public function __get($key) {
        return $this->$key;
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function insertar($id_formulario, $titulo, $texto, $tipo, $orden, $opcion_1_texto, $opcion_4_texto, $opcion_5_texto, $opcion_no_aplica_texto) {
        $stmt = $this->pdo->prepare("INSERT INTO preguntas (id_formulario, titulo_pregunta, texto_pregunta, tipo_respuesta, orden, opcion_1_texto, opcion_4_texto, opcion_5_texto, opcion_no_aplica_texto) VALUES (:id_form, :titulo, :texto, :tipo, :orden, :opcion_1, :opcion_4, :opcion_5, :opcion_no_aplica)");
        return $stmt->execute([
            ':id_form' => $id_formulario,
            ':titulo' => $titulo,
            ':texto' => $texto,
            ':tipo' => $tipo,
            ':orden' => $orden,
            ':opcion_1' => $opcion_1_texto,
            ':opcion_4' => $opcion_4_texto,
            ':opcion_5' => $opcion_5_texto,
            ':opcion_no_aplica' => $opcion_no_aplica_texto
        ]);
    }

    public function modificar() {
        $stmt = $this->pdo->prepare("UPDATE preguntas SET titulo_pregunta = :titulo, texto_pregunta = :texto, tipo_respuesta = :tipo, opcion_1_texto = :opcion_1, opcion_4_texto = :opcion_4, opcion_5_texto = :opcion_5, opcion_no_aplica_texto = :opcion_no_aplica WHERE id_pregunta = :id");
        return $stmt->execute([
            ':titulo' => $this->titulo_pregunta,
            ':texto' => $this->texto_pregunta,
            ':tipo' => $this->tipo_respuesta,
            ':opcion_1' => $this->opcion_1_texto,
            ':opcion_4' => $this->opcion_4_texto,
            ':opcion_5' => $this->opcion_5_texto,
            ':opcion_no_aplica' => $this->opcion_no_aplica_texto,
            ':id' => $this->id_pregunta
        ]);
    }

    public function listarPorFormulario($idFormulario) {
        $stmt = $this->pdo->prepare("SELECT * FROM preguntas WHERE id_formulario = :id  ORDER BY id_pregunta; ");
        $stmt->execute([':id' => $idFormulario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar() {
        $stmt = $this->pdo->prepare("DELETE FROM preguntas WHERE id_pregunta = :id");
        return $stmt->execute([':id' => $this->id_pregunta]);
    }
}