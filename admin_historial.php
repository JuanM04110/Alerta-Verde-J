<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$filtro = "";
if(isset($_GET['buscar']) && $_GET['buscar'] != ""){
    $b = $conn->real_escape_string($_GET['buscar']);
    $filtro = "WHERE u.nombre LIKE '%$b%' OR u.email LIKE '%$b%'";
}

$sql = "SELECT h.*, u.nombre 
        FROM historial_puntos h
        JOIN usuarios u ON h.usuario_id = u.id
        $filtro
        ORDER BY h.fecha DESC";

$hist = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Historial General</title>
<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
<h1>📂 Historial de puntos (Admin)</h1>
<a href='admin.php'>← Volver</a>

<form method="get" style="margin-top:10px;">
<input type="text" name="buscar" placeholder="Buscar usuario..." value="<?= $_GET['buscar'] ?? '' ?>">
<button type="submit">Buscar</button>
</form>

<table style='width:100%; border-collapse:collapse;margin-top:10px;'>
<tr style='background:#ddd;'>
<th>Fecha</th>
<th>Usuario</th>
<th>Tipo</th>
<th>Descripción</th>
<th>Puntos</th>
</tr>

<?php while($r = $hist->fetch_assoc()): ?>
<tr>
<td><?= $r['fecha'] ?></td>
<td><?= $r['nombre'] ?></td>
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
