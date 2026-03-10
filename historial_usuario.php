<?php
include "db.php";
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Fechas del form
$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

// Consulta con filtro si hay fechas
if ($desde && $hasta) {
    $movs = $conn->query("
        SELECT * FROM historial_puntos 
        WHERE usuario_id = $usuario_id 
        AND DATE(fecha) BETWEEN '$desde' AND '$hasta'
        ORDER BY fecha DESC
    ");
} else {
    $movs = $conn->query("
        SELECT * FROM historial_puntos 
        WHERE usuario_id = $usuario_id 
        ORDER BY fecha DESC
    ");
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Historial de Puntos</title>
<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
<h1>📊 Historial de puntos</h1>
<a href='dashboard.php'>← Volver</a>

<!-- ✅ Form de filtro por fecha -->
<form method="GET" action="" style="margin-top:15px; margin-bottom:15px;">
    <label>Desde:</label>
    <input type="date" name="desde" value="<?= $desde ?>">

    <label>Hasta:</label>
    <input type="date" name="hasta" value="<?= $hasta ?>">

    <button type="submit">Filtrar</button>
</form>

<!-- Si se aplicó filtro, mostrar aviso -->
<?php if ($desde && $hasta): ?>
<p>📅 Mostrando movimientos desde <b><?= $desde ?></b> hasta <b><?= $hasta ?></b></p>
<?php endif; ?>

<table style='width:100%; border-collapse:collapse;margin-top:10px;'>
<tr style='background:#ddd;'>
<th>Fecha</th>
<th>Tipo</th>
<th>Descripción</th>
<th>Puntos</th>
</tr>

<?php while($r = $movs->fetch_assoc()): ?>
<tr>
<td><?= $r['fecha'] ?></td>
<td><?= ucfirst($r['tipo']) ?></td>
<td><?= $r['descripcion'] ?></td>
<td style="color:<?= ($r['puntos']>=0?'green':'red') ?>;">
<?= ($r['puntos']>=0?'+':'') . $r['puntos'] ?>
</td>
</tr>
<?php endwhile; ?>

</table>

</div>
</body>
</html>
