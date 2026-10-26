<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Prueba de envío de correos con tu configuración actual</h2>";

// Incluir la clase Email
require_once __DIR__ . '/Email.php';
require_once __DIR__ . '/conexionPDO.php';

// Conectar a BD
$objetoConexion = new Conexion;
$conexion = $objetoConexion->conectar();

echo "<h3>1. Probando envío directo con PHPMailer (sin pasar por tu clase)</h3>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ruta del autoload - AJUSTA SEGÚN TU ESTRUCTURA
$autoloadPath = __DIR__ . '/../Controlador/phpmailer/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    echo "✅ Autoload encontrado en: $autoloadPath<br>";
} else {
    die("❌ No se encuentra PHPMailer en: $autoloadPath");
}

try {
    $mail = new PHPMailer(true);
    
    // Configuración
    $mail->isSMTP();
    $mail->Host = 'smtp-mail.outlook.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'analitica.sistemas@hrm.gov.co';
    $mail->Password = 'G.568529814173ot';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->SMTPDebug = 2; // Depuración detallada
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG: " . htmlspecialchars($str) . "<br>";
    };
    
    // Destinatarios - CAMBIA ESTE CORREO POR UNO DE PRUEBA
    $mail->setFrom('analitica.sistemas@hrm.gov.co', 'Sistema Turnos');
    $mail->addAddress('yesid.alejo300@gmail.com', 'Prueba'); // ¡CAMBIA ESTO!
    
    $mail->isHTML(true);
    $mail->Subject = 'Prueba directa PHPMailer - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>Prueba exitosa</h1><p>Este es un correo de prueba directo.</p>';
    
    echo "<h3>Intentando enviar...</h3>";
    
    if ($mail->send()) {
        echo "<h3 style='color:green'>✅ Correo enviado exitosamente (prueba directa)</h3>";
    } else {
        echo "<h3 style='color:red'>❌ Error: " . $mail->ErrorInfo . "</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>❌ Excepción: " . $e->getMessage() . "</h3>";
}

echo "<hr>";
echo "<h3>2. Probando con tu clase Email (con datos reales)</h3>";

// Obtener un médico real para probar
$testAreaId = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;

if ($testAreaId == 0) {
    echo "<p>Por favor, especifica un ID de área para probar. Ejemplo:</p>";
    echo "<code>?area_id=1</code><br><br>";
    
    // Mostrar áreas disponibles
    $query = $conexion->query("SELECT IdArea, NombreArea FROM area LIMIT 5");
    echo "<p>Áreas disponibles para prueba:</p><ul>";
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>ID: {$row['IdArea']} - {$row['NombreArea']}</li>";
    }
    echo "</ul>";
    exit;
}

echo "<p>Probando con Área ID: $testAreaId</p>";

// Crear instancia de Email
$mailer = new Email(
    $conexion,
    $testAreaId,
    1, // IdUsuario ficticio
    'Usuario Prueba',
    'test@example.com',
    'Area Prueba',
    'turnos@hrm.gov.co',
    'Sistema de Turnos Médicos'
);

// Listar médicos del área
$medicos = $mailer->ListarMedicosArea($testAreaId);

if (empty($medicos)) {
    echo "<p>❌ No se encontraron médicos en esta área</p>";
} else {
    echo "<p>✅ Se encontraron " . count($medicos) . " médicos en esta área</p>";
    
    // Probar enviar al primer médico
    $primerMedico = $medicos[0];
    echo "<p>Probando envío a: {$primerMedico['NombreMedico']} ({$primerMedico['Correomedico']})</p>";
    
    $mailer->_set('_NombreUsuario', $primerMedico['NombreMedico']);
    $mailer->_set('_CorreoUsuario', $primerMedico['Correomedico']);
    $mailer->_set('_NombreArea', $primerMedico['NombreArea']);
    
    // Activar modo debug en la clase (agrega esta línea temporalmente)
    echo "<p>Intentando enviar correo de prueba...</p>";
    
    if ($mailer->enviarCorreo($primerMedico['Correomedico'], $primerMedico['NombreMedico'], 'Autorizar', 'Mayo 2026')) {
        echo "<h3 style='color:green'>✅ Correo enviado exitosamente a {$primerMedico['NombreMedico']}</h3>";
    } else {
        echo "<h3 style='color:red'>❌ Falló el envío a {$primerMedico['NombreMedico']}</h3>";
        
        // Leer el log de errores
        if (file_exists(__DIR__ . '/email_errors_detallado.log')) {
            echo "<h4>Últimos errores registrados:</h4>";
            echo "<pre>";
            echo htmlspecialchars(file_get_contents(__DIR__ . '/email_errors_detallado.log'));
            echo "</pre>";
        }
    }
}
?>