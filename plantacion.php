<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$mensaje = "";

// Registrar nueva plantación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre_arbol']);
    $lat = (float)$_POST['lat'];
    $lng = (float)$_POST['lng'];

    $fotoPath = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $fotoPath = "uploads/" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
    }

    $sql = "INSERT INTO plantaciones (usuario_id, nombre_arbol, fecha_plantacion, lat, lng, foto, aprobado) 
            VALUES ($usuario_id, '$nombre', NOW(), $lat, $lng, " . ($fotoPath ? "'$fotoPath'" : "NULL") . ", 0)";
    if ($conn->query($sql)) {
        $mensaje = "<p style='color:green;'>✅ Plantación registrada, pendiente de aprobación por un admin.</p>";
    } else {
        $mensaje = "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
    }
}

// Registrar cuidado
if (isset($_GET['cuidar'])) {
    $id = (int)$_GET['cuidar'];
    // Chequear que la plantación pertenezca al usuario
    $check = $conn->query("SELECT * FROM plantaciones WHERE id=$id AND usuario_id=$usuario_id AND aprobado=1");
    if ($check && $check->num_rows) {
        $conn->query("INSERT INTO cuidados (plantacion_id, fecha_cuidado, observaciones) VALUES ($id, NOW(), 'Cuidado registrado')");
        $mensaje = "<p style='color:blue;'>🌱 Cuidado registrado, pendiente de aprobación por admin.</p>";
    }
}

// Traer plantaciones del usuario
$plantaciones = $conn->query("SELECT * FROM plantaciones WHERE usuario_id=$usuario_id ORDER BY fecha_plantacion DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🌱 Plantaciones</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h1>🌱 Mis Plantaciones</h1>
    <h4>Requisitos:
        <ul>
            <li>El árbol debe ser plantado en un espacio público o en tu propiedad.</li>
            <li>Debes tomar una foto del árbol plantado.</li>
            <li>Proporcionar la ubicación del árbol.</li>
            <li>Esperar la aprobación de un administrador para ganar puntos.</li>
        </ul>
    </h4>
    <h4><ul>
            <li>Debe subir una foto periodicamente del árbol</li>
        </ul>
    </h4>
    <a href="dashboard.php" style="float:right;">← Volver</a>
    <?= $mensaje ?>

    <h2>Registrar nueva plantación</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nombre_arbol" placeholder="Nombre del árbol" required><br>
        <input type="file" name="foto" required><br>
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">
        <button type="button" onclick="getLocation()">📍 Obtener ubicación</button><br><br>
        <button type="submit" name="guardar">Registrar Plantación</button>
    </form>

    <h2>Mis árboles plantados</h2>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>Nombre</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
        <?php while($row = $plantaciones->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre_arbol']) ?></td>
                <td style="text-align:center;"><?= $row['fecha_plantacion'] ?></td>
                <td style="text-align:center;"><?= $row['aprobado'] ? "✅ Aprobado" : "⏳ Pendiente" ?></td>
                <td style="text-align:center;">
                    <?php if ($row['aprobado']): ?>
                        <a href="plantacion.php?cuidar=<?= $row['id'] ?>">🌱 Registrar cuidado</a>
                    <?php else: ?>
                        <em>Esperando aprobación</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById("lat").value = pos.coords.latitude;
            document.getElementById("lng").value = pos.coords.longitude;
            alert("Ubicación guardada ✔️");
        });
    } else {
        alert("Tu navegador no soporta geolocalización");
    }
}
</script>
</body>
</html>
