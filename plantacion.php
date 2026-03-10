<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";

// Mostrar mensaje desde subir_cuidado.php
if(isset($_SESSION['mensaje'])) {
    $mensaje = "<div style='background:#dff0d8;padding:10px;border-radius:5px;margin:10px 0;color:green;'>
                    ".$_SESSION['mensaje']."
                </div>";
    unset($_SESSION['mensaje']);
}

// Crear carpeta uploads si no existe
if (!file_exists("uploads")) { mkdir("uploads", 0777, true); }

// Guardar nueva plantación
if (isset($_POST['guardar'])) {
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto = "uploads/" . uniqid() . "_" . basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto);

        $sql = "INSERT INTO plantaciones (usuario_id, nombre_arbol, foto, ubicacion, aprobado, co2_absorbido)
                VALUES ('$usuario_id', '$nombre', '$foto', '$ubicacion', 0, 0)";

        if ($conn->query($sql)) {
            $mensaje .= "<p style='color:green;'>🌱 Plantación registrada y pendiente de aprobación.</p>";
        } else {
            $mensaje .= "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
    } else {
        $mensaje .= "<p style='color:red;'>Debes subir una foto.</p>";
    }
}

// Cargar plantaciones del usuario
$resPlant = $conn->query("SELECT * FROM plantaciones WHERE usuario_id=$usuario_id");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantaciones - Usuario</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h1>🌿 Mis Plantaciones</h1>
    <a href="dashboard.php">← Volver</a> | 
    <a href="logout.php">Cerrar sesión</a>

    <?= $mensaje ?>

    <form method="post" enctype="multipart/form-data" style="margin-top:20px;">
        <input type="text" name="nombre" placeholder="Nombre del árbol o planta" required><br>
        <input type="text" name="ubicacion" placeholder="Ubicación (Ej: Plaza San Martín)" required><br>
        <input type="file" name="foto" required><br>
        <button type="submit" name="guardar">Registrar Plantación</button>
    </form>

    <hr>
    <h2>📋 Mis registros</h2>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;">
            <th>Nombre</th>
            <th>Ubicación</th>
            <th>Foto</th>
            <th>CO₂ absorbido</th>
            <th>Estado</th>
            <th>Cuidado</th>
        </tr>

        <?php while($row = $resPlant->fetch_assoc()): ?>
        <?php
        if ($row['aprobado'] == 1) {
            $pid = $row['id'];
            $q = $conn->query("SELECT fecha_cuidado FROM cuidados_plantacion 
                               WHERE plantacion_id=$pid AND usuario_id=$usuario_id 
                               ORDER BY fecha_cuidado DESC LIMIT 1");

            $cooldown = 10; // segundos de prueba
            $puede = true;
            $faltan = 0;

            if ($q && $q->num_rows > 0) {
                $ultimo = $q->fetch_assoc();
                $ult = strtotime($ultimo['fecha_cuidado']);
                $now = time();
                if (($now - $ult) < $cooldown) {
                    $puede = false;
                    $faltan = $cooldown - ($now - $ult);
                }
            }
        }
        ?>
        <tr>
            <td><?= htmlspecialchars($row['nombre_arbol']) ?></td>
            <td><?= htmlspecialchars($row['ubicacion']) ?></td>
            <td><img src="<?= $row['foto'] ?>" width="80"></td>
            <td><?= $row['co2_absorbido'] ?> kg</td>
            <td>
                <?php if($row['aprobado'] == 1): ?>
                    ✅ Aprobado
                <?php elseif($row['aprobado'] == 2): ?>
                    ❌ Rechazado
                <?php else: ?>
                    ⏳ Pendiente
                <?php endif; ?>
            </td>
            <td>
                <?php if($row['aprobado'] == 1): ?>
                    <?php if($puede): ?>
                        <form action="subir_cuidado.php" method="post" enctype="multipart/form-data" style="margin:0;">
                            <input type="hidden" name="plantacion_id" value="<?= $row['id'] ?>">
                            <input type="file" name="foto" required>
                            <button type="submit">Subir cuidado 🌿</button>
                        </form>
                    <?php else: ?>
                        <span id="timer_<?= $row['id'] ?>">⏳ Espera <?= $faltan ?>s</span>
                        <script>
                        let t<?= $row['id'] ?> = <?= $faltan ?>;
                        let el<?= $row['id'] ?> = document.getElementById("timer_<?= $row['id'] ?>");
                        let int<?= $row['id'] ?> = setInterval(()=>{
                            t<?= $row['id'] ?>--;
                            if(t<?= $row['id'] ?> <= 0){
                                el<?= $row['id'] ?>.innerHTML = "✨ ¡Ya podés cuidar!";
                                clearInterval(int<?= $row['id'] ?>);
                                location.reload();
                            } else {
                                el<?= $row['id'] ?>.innerHTML = "⏳ Espera " + t<?= $row['id'] ?> + "s";
                            }
                        },1000);
                        </script>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
