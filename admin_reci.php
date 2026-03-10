<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Acciones: aprobar / rechazar
if (isset($_GET['aprobar'])) {
    $id = (int) $_GET['aprobar'];
    // sólo si está pendiente
    $res = $conn->query("SELECT usuario_id, puntos_solicitados, estado FROM reciclaje_requests WHERE id=$id");
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['estado'] === 'pendiente') {
            $usuario = (int)$row['usuario_id'];
            $pts = (int)$row['puntos_solicitados'];
            $conn->query("UPDATE reciclaje_requests SET estado='aprobado' WHERE id=$id");
            $conn->query("UPDATE usuarios SET puntos = puntos + $pts WHERE id=$usuario");
        }
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
    <h2>Juan Maldonado - Noelia Schefler</h2>
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
