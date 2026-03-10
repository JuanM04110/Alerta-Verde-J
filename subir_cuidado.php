<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Cooldown: 1 cuidado cada 12 horas (ejemplo: 43200 segundos)
#$cooldown = 43200;

$cooldown = 10;

// Revisar último cuidado del usuario
$sqlCooldown = "SELECT fecha_cuidado FROM cuidados_plantacion WHERE usuario_id = $usuario_id ORDER BY fecha_cuidado DESC LIMIT 1";
$result = $conn->query($sqlCooldown);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $ultimo = strtotime($row['fecha_cuidado']);
    $ahora = time();

    if (($ahora - $ultimo) < $cooldown) {
        $tiempoRestante = $cooldown - ($ahora - $ultimo);
        $_SESSION['mensaje'] = "Esperá " . gmdate("H:i:s", $tiempoRestante) . " antes de subir otro cuidado.";
        header("Location: plantacion.php");
        exit;
    }
}

if (isset($_POST['plantacion_id']) && isset($_FILES['foto'])) {
    $plantacion_id = (int)$_POST['plantacion_id'];

    if (!file_exists("uploads")) { mkdir("uploads", 0777, true); }

    $foto = "uploads/" . uniqid() . "_" . basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'], $foto);

    $fecha = date("Y-m-d H:i:s");
    $sql = "INSERT INTO cuidados_plantacion (plantacion_id, usuario_id, foto, fecha_cuidado, aprobado, co2_mes)
            VALUES ('$plantacion_id', '$usuario_id', '$foto', '$fecha', 0, 2.5)";
    $conn->query($sql);

    $_SESSION['mensaje'] = "✅ Cuidado enviado, esperando aprobación 🌱";
}

header("Location: plantacion.php");
exit;
?>
