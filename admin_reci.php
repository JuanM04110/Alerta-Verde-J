<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Acciones: aprobar / rechazar
// Aprobar reciclaje
if (isset($_GET['aprobar'])) {
    $id = (int)$_GET['aprobar'];

    // Conseguimos info de la solicitud
    $r = $conn->query("SELECT usuario_id, puntos_solicitados FROM reciclaje_requests WHERE id = $id AND estado = 'pendiente'");
    
    if ($r->num_rows) {
        $data = $r->fetch_assoc();
        $user = $data['usuario_id'];
        $puntos = $data['puntos_solicitados'];

        // Marcamos como aprobado
        $conn->query("UPDATE reciclaje_requests SET estado='aprobado' WHERE id=$id");

        // Sumamos puntos al usuario
        $conn->query("UPDATE usuarios SET puntos = puntos + $puntos WHERE id = $user");

        // Guardamos en historial
        $conn->query("
            INSERT INTO historial_puntos (usuario_id, tipo, puntos, descripcion, fecha)
            VALUES ($user, 'reciclaje', $puntos, 'Puntos por reciclaje aprobado', NOW())
        ");
    }

    header("Location: admin_reci.php");
    exit;
}

if (isset($_GET['rechazar'])) {
    $id = (int) $_GET['rechazar'];
    $conn->query("UPDATE reciclaje_requests SET estado='rechazado' WHERE id=$id");
    header("Location: admin_reci.php");
    exit;
}

// Filtro
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendiente';
$allowed = ['pendiente','aprobado','rechazado','todos'];
if (!in_array($filtro, $allowed)) $filtro = 'pendiente';

$where = ($filtro === 'todos') ? "" : "WHERE r.estado='$filtro'";

$res = $conn->query("SELECT r.*, u.nombre AS usuario_nombre 
                     FROM reciclaje_requests r 
                     LEFT JOIN usuarios u ON r.usuario_id = u.id
                     $where
                     ORDER BY r.fecha DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Reciclaje</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h2>Juan Maldonado </h2>
    <h1>🛠 Admin - Solicitudes de Reciclaje</h1>
    <a href="admin.php">← Volver al panel principal</a> | <a href="logout.php" style="float:right;">Cerrar sesión</a>

    <p>Filtro:
        <a href="admin_reci.php?filtro=pendiente">Pendientes</a> |
        <a href="admin_reci.php?filtro=aprobado">Aprobadas</a> |
        <a href="admin_reci.php?filtro=rechazado">Rechazadas</a> |
        <a href="admin_reci.php?filtro=todos">Todos</a>
    </p>

    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>ID</th><th>Usuario</th><th>Material</th><th>Detalle</th><th>Puntos</th><th>Punto Verde</th><th>Fecha</th><th>Acciones</th></tr>
        <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td style="text-align:center;"><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['usuario_nombre']) ?></td>
                <td style="text-align:center;"><?= htmlspecialchars($row['material']) ?></td>
                <td><?= htmlspecialchars($row['cantidad']).' '.$row['unidad'] ?></td>
                <td style="text-align:center;"><?= $row['puntos_solicitados'] ?></td>
                <td><?= htmlspecialchars($row['punto_verde']) ?></td>
                <td style="text-align:center;"><?= $row['fecha'] ?></td>
                <td style="text-align:center;">
                    <?php if ($row['estado'] === 'pendiente'): ?>
                        <a href="admin_reci.php?aprobar=<?= $row['id'] ?>">✅ Aprobar</a> |
                        <a href="admin_reci.php?rechazar=<?= $row['id'] ?>">❌ Rechazar</a>
                    <?php elseif ($row['estado'] === 'aprobado'): ?>
                        <span style="color:green;">Aprobado</span>
                    <?php else: ?>
                        <span style="color:gray;">Rechazado</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
