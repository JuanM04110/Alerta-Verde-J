<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$mensaje = "";

/* ---- Acciones para plantaciones ---- */
if (isset($_GET['aprobar_plant']) && is_numeric($_GET['aprobar_plant'])) {
    $id = (int)$_GET['aprobar_plant'];
    $res = $conn->query("SELECT usuario_id FROM plantaciones WHERE id=$id AND aprobado=0");
    if ($res && $res->num_rows) {
        $user = $res->fetch_assoc()['usuario_id'];
        // Aprobar y dar 100 puntos
        $conn->query("UPDATE plantaciones SET aprobado=1 WHERE id=$id");
        $conn->query("UPDATE usuarios SET puntos = puntos + 100 WHERE id=$user");
        $mensaje = "<p style='color:green;'>Plantación aprobada y se otorgaron +100 puntos al usuario.</p>";
    }
    header("Location: admin_plant.php");
    exit;
}

if (isset($_GET['rechazar_plant']) && is_numeric($_GET['rechazar_plant'])) {
    $id = (int)$_GET['rechazar_plant'];
    $conn->query("UPDATE plantaciones SET aprobado=2 WHERE id=$id");
    header("Location: admin_plant.php");
    exit;
}

/* ---- Acciones para cuidados ---- */
if (isset($_GET['aprobar_cuidado']) && is_numeric($_GET['aprobar_cuidado'])) {
    $id = (int)$_GET['aprobar_cuidado'];
    // Obtener el cuidado y la plantación relacionada
    $res = $conn->query("SELECT c.*, p.usuario_id, p.co2_absorbido FROM cuidados_plantacion c JOIN plantaciones p ON c.plantacion_id=p.id WHERE c.id=$id AND c.aprobado=0");
    if ($res && $row = $res->fetch_assoc()) {
        $plant_id = (int)$row['plantacion_id'];
        $user = (int)$row['usuario_id'];
        $co2 = (float)$row['co2_mes'];
        // Marcar aprobado
        $conn->query("UPDATE cuidados_plantacion SET aprobado=1 WHERE id=$id");
        // Sumar CO2 a la plantación
        $conn->query("UPDATE plantaciones SET co2_absorbido = co2_absorbido + $co2 WHERE id=$plant_id");
        // (Opcional) podés sumar puntos normales si querés. Por ahora solo CO2.
        $mensaje = "<p style='color:green;'>Cuidado aprobado. +".number_format($co2,2)." kg CO₂ agregado a la plantación.</p>";
    }
    header("Location: admin_plant.php");
    exit;
}

if (isset($_GET['rechazar_cuidado']) && is_numeric($_GET['rechazar_cuidado'])) {
    $id = (int)$_GET['rechazar_cuidado'];
    $conn->query("UPDATE cuidados_plantacion SET aprobado=2 WHERE id=$id");
    header("Location: admin_plant.php");
    exit;
}

/* ---- Consultas para mostrar ---- */
// Plantaciones (todas)
$plantaciones = $conn->query("SELECT p.*, u.nombre AS usuario_nombre FROM plantaciones p LEFT JOIN usuarios u ON p.usuario_id=u.id ORDER BY p.fecha_plantacion DESC");

// Cuidados pendientes
$cuidados = $conn->query("SELECT c.*, p.nombre_arbol, u.nombre AS usuario_nombre FROM cuidados_plantacion c JOIN plantaciones p ON c.plantacion_id=p.id JOIN usuarios u ON c.usuario_id=u.id WHERE c.aprobado=0 ORDER BY c.fecha_cuidado DESC");

// Totales (resumen CO2)
$total_co2_res = $conn->query("SELECT SUM(co2_absorbido) AS total_co2 FROM plantaciones");
$total_co2 = ($total_co2_res && $total_co2_res->num_rows) ? $total_co2_res->fetch_assoc()['total_co2'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Plantaciones</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h1>🛠 Admin - Plantaciones</h1>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div><a href="admin.php">← Volver a Admin</a></div>
        <div>Total CO₂ absorbido: <strong><?= number_format($total_co2,2) ?> kg</strong></div>
    </div>

    <?php if ($mensaje) echo $mensaje; ?>

    <h2 style="margin-top:20px;">Plantaciones</h2>
    <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
        <tr style="background:#efefef;"><th>ID</th><th>Usuario</th><th>Árbol</th><th>Ubicación</th><th>Fecha</th><th>Estado</th><th>CO₂ (kg)</th><th>Acciones</th></tr>
        <?php while($p = $plantaciones->fetch_assoc()): ?>
            <tr>
                <td style="text-align:center;"><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['usuario_nombre']) ?></td>
                <td><?= htmlspecialchars($p['nombre_arbol']) ?></td>
                <td>
                    <?= htmlspecialchars($p['ubicacion']) ?><br>
                    <?php if ($p['lat'] && $p['lng']): ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $p['lat'] ?>,<?= $p['lng'] ?>" target="_blank">Ver en Maps</a>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $p['fecha_plantacion'] ?></td>
                <td style="text-align:center;"><?= $p['aprobado']==1 ? "✅ Aprobada" : ($p['aprobado']==2 ? "❌ Rechazada" : "⏳ Pendiente") ?></td>
                <td style="text-align:center;"><?= number_format($p['co2_absorbido'],2) ?></td>
                <td style="text-align:center;">
                    <?php if ($p['aprobado']==0): ?>
                        <a href="admin_plant.php?aprobar_plant=<?= $p['id'] ?>">✅ Aprobar (+100 pts)</a> |
                        <a href="admin_plant.php?rechazar_plant=<?= $p['id'] ?>">❌ Rechazar</a>
                        <br>
                        <?php if ($p['foto']): ?>
                            <button onclick="window.open('<?= $p['foto'] ?>','FotoPlant','width=700,height=500')">Ver foto</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($p['foto']): ?>
                            <button onclick="window.open('<?= $p['foto'] ?>','FotoPlant','width=700,height=500')">Ver foto</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Cuidados pendientes</h2>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>ID</th><th>Usuario</th><th>Árbol</th><th>Foto</th><th>Fecha</th><th>CO₂ mes (kg)</th><th>Acciones</th></tr>
        <?php while($c = $cuidados->fetch_assoc()): ?>
            <tr>
                <td style="text-align:center;"><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['usuario_nombre']) ?></td>
                <td><?= htmlspecialchars($c['nombre_arbol']) ?></td>
                <td style="text-align:center;">
                    <?php if ($c['foto']): ?>
                        <button onclick="window.open('<?= htmlspecialchars($c['foto']) ?>','FotoCuidado','width=700,height=500')">Ver foto</button>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= $c['fecha_cuidado'] ?></td>
                <td style="text-align:center;"><?= number_format($c['co2_mes'],2) ?></td>
                <td style="text-align:center;">
                    <a href="admin_plant.php?aprobar_cuidado=<?= $c['id'] ?>">✅ Aprobar</a> |
                    <a href="admin_plant.php?rechazar_cuidado=<?= $c['id'] ?>">❌ Rechazar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>
</body>
</html>
