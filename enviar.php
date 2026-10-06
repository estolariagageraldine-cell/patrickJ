<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/conexion.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function responder($codigo, $ok, $mensaje) {
    http_response_code($codigo);
    echo json_encode(['ok' => $ok, 'mensaje' => $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

$raw = file_get_contents('php://input');
$datos = json_decode($raw, true);
if (!is_array($datos)) {
    $datos = [];
}

$email = filter_var(trim((string)($datos['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$imagen = trim((string)($datos['imagen'] ?? ''));
$imagen = basename($imagen);

if (!$email) {
    responder(400, false, 'El correo no es válido.');
}

if ($imagen === '' || strpos($imagen, '..') !== false) {
    responder(400, false, 'La imagen seleccionada no es válida.');
}

$ruta = __DIR__ . '/images/' . $imagen;
$ext = strtolower(pathinfo($imagen, PATHINFO_EXTENSION));

if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) || !is_file($ruta)) {
    responder(400, false, 'La imagen no existe o no tiene un formato válido.');
}

$config = require __DIR__ . '/config.php';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$pdo->exec("CREATE TABLE IF NOT EXISTS envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    enviado TINYINT(1) NOT NULL DEFAULT 0,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM envios WHERE ip = ? AND fecha > (NOW() - INTERVAL 1 HOUR)'
);
$stmt->execute([$ip]);
if ((int) $stmt->fetchColumn() >= 5) {
    responder(429, false, 'Demasiados envíos desde esta IP. Intenta más tarde.');
}

$stmt = $pdo->prepare(
    'INSERT INTO envios (email, imagen, ip, enviado) VALUES (?, ?, ?, 0)'
);
$stmt->execute([$email, $imagen, $ip]);
$registroId = (int) $pdo->lastInsertId();

$config = require __DIR__ . '/config.php';

if (empty($config['smtp_user']) || $config['smtp_user'] === 'tucorreo@gmail.com' || empty($config['smtp_pass']) || $config['smtp_pass'] === 'COLOCA_AQUI_TU_PASSWORD_DE_APP') {
    $pdo->prepare('UPDATE envios SET enviado = 0 WHERE id = ?')->execute([$registroId]);
    responder(500, false, 'Se guardó el correo en la base de datos, pero falta configurar SMTP en config.php antes de enviar correos.');
}

$enviado = 0;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_user'];
    $mail->Password = $config['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $config['smtp_port'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($config['smtp_user'], $config['from_name']);
    $mail->addAddress($email);
    $mail->Subject = 'Tu imagen de gatitos 🐱';
    $mail->isHTML(true);
    $mail->Body = '<p>¡Hola! Aquí tienes la imagen que elegiste en Gatitos Jane.</p><p><img src="cid:gato" style="max-width: 100%; border-radius: 12px;" /></p>';
    $mail->AltBody = 'Aquí tienes la imagen que elegiste en Gatitos Jane.';
    $mail->addEmbeddedImage($ruta, 'gato', $imagen);

    $mail->send();
    $enviado = 1;
} catch (Exception $e) {
    error_log('PHPMailer Error: ' . $e->getMessage());
}

$pdo->prepare('UPDATE envios SET enviado = ? WHERE id = ?')->execute([$enviado, $registroId]);

if ($enviado) {
    responder(200, true, '¡Listo! El correo se guardó en la base de datos y se envió correctamente.');
}

responder(500, false, 'El correo quedó guardado en la base de datos, pero no se pudo enviar. Revisa la configuración SMTP y la contraseña de aplicación de Gmail.');