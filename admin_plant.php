<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Aprobar plantación
if (isset($_GET['aprobar'])) {
    $id = (int)$_GET['aprobar'];
    $res = $conn->query("SELECT usuario_id FROM plantaciones WHERE id=$id AND aprobado=0");
    if ($res && $res->num_rows) {
        $user = $res->fetch_assoc()['usuario_id'];
        $conn->query("UPDATE plantaciones SET aprobado=1, puntos=50 WHERE id=$id");
        $conn->query("UPDATE usuarios SET puntos = puntos + 50 WHERE id=$user");
    }
    header("Location: admin_plant.php");
    exit;
}

// Rechazar plantación
if (isset($_GET['rechazar'])) {
    $id = (int)$_GET['rechazar'];
    $conn->query("UPDATE plantaciones SET aprobado=2 WHERE id=$id");
    header("Location: admin_plant.php");
    exit;
}

// Aprobar cuidado
if (isset($_GET['aprobar_cuidado'])) {
    $id = (int)$_GET['aprobar_cuidado'];
    $res = $conn->query("SELECT c.*, p.usuario_id FROM cuidados c 
                         JOIN plantaciones p ON c.plantacion_id=p.id 
                         WHERE c.id=$id AND c.puntos_otorgados=0");
    if ($res && $row = $res->fetch_assoc()) {
        $uid = $row['usuario_id'];
        $conn->query("UPDATE cuidados SET puntos_otorgados=10 WHERE id=$id");
        $conn->query("UPDATE usuarios SET puntos = puntos + 10 WHERE id=$uid");
    }
    header("Location: admin_plant.php");
    exit;
}

// Listar plantaciones
$plantaciones = $conn->query("SELECT p.*, u.nombre AS usuario 
                              FROM plantaciones p 
                              LEFT JOIN usuarios u ON p.usuario_id=u.id 
                              ORDER BY p.fecha_plantacion DESC");

// Listar cuidados pendientes
$cuidados = $conn->query("SELECT c.*, u.nombre AS usuario, p.nombre_arbol 
                          FROM cuidados c 
                          JOIN plantaciones p ON c.plantacion_id=p.id
                          JOIN usuarios u ON p.usuario_id=u.id
                          WHERE c.puntos_otorgados=0
                          ORDER BY c.fecha_cuidado DESC");
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
    <a href="admin.php">← Volver al panel principal</a> | 
    <a href="logout.php" style="float:right;">Cerrar sesión</a>

    <h2>Plantaciones</h2>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>Usuario</th><th>Árbol</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
        <?php while($row = $plantaciones->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['nombre_arbol']) ?></td>
                <td style="text-align:center;"><?= $row['fecha_plantacion'] ?></td>
                <td style="text-align:center;"><?= $row['aprobado']==1 ? "✅ Aprobado" : ($row['aprobado']==2 ? "❌ Rechazado" : "⏳ Pendiente") ?></td>
                <td style="text-align:center;">
                    <?php if ($row['aprobado']==0): ?>
                        <a href="admin_plant.php?aprobar=<?= $row['id'] ?>">✅ Aprobar</a> | 
                        <a href="admin_plant.php?rechazar=<?= $row['id'] ?>">❌ Rechazar</a>
                    <?php elseif ($row['aprobado']==1): ?>
                        <span style="color:green;">✔</span>
                    <?php else: ?>
                        <span style="color:gray;">✖</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Cuidados pendientes</h2>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>Usuario</th><th>Árbol</th><th>Fecha cuidado</th><th>Acción</th></tr>
        <?php while($row = $cuidados->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['nombre_arbol']) ?></td>
                <td style="text-align:center;"><?= $row['fecha_cuidado'] ?></td>
                <td style="text-align:center;">
                    <a href="admin_plant.php?aprobar_cuidado=<?= $row['id'] ?>">🌱 Aprobar cuidado (+10 pts)</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
