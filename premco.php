<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location:index.php"); exit; }

$uid = $_SESSION['usuario_id'];

// Total CO₂ del usuario
$q = $conn->query("SELECT SUM(co2_absorbido) AS co2 FROM plantaciones WHERE usuario_id=$uid");
$co2 = ($q && $q->num_rows>0) ? $q->fetch_assoc()['co2'] : 0;

// Lista de premios CO₂
$premios = [
    ["id"=>1, "nombre"=>"Sticker Eco 🌱", "costo"=>5],
    ["id"=>2, "nombre"=>"Llaverito reciclado ♻️", "costo"=>10],
    ["id"=>3, "nombre"=>"Certificado eco digital 📜", "costo"=>20]
];

// procesar canje
if (isset($_GET['canjear'])) {
    $id = (int)$_GET['canjear'];

    foreach ($premios as $p) {
        if ($p["id"] == $id) {
            if ($co2 >= $p["costo"]) {
                echo "<script>alert('✅ Canjeaste: {$p['nombre']}');</script>";
            } else {
                echo "<script>alert('❌ No tenés suficiente CO₂');</script>";
            }
        }
    }
    echo "<script>window.location='premco.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Premios CO₂</title>
<link rel="stylesheet" href="css/styles.css">
</head>
<body>

<div class="container">
<a href="dashboard.php">← Volver</a>
<h1>🎁 Premios por CO₂ absorbido</h1>

<p><b>CO₂ disponible:</b> <?= number_format($co2,2) ?> kg</p>

<table class="table-co2">
<tr>
    <th>Premio</th>
    <th>Costo (kg CO₂)</th>
    <th>Acción</th>
</tr>

<?php foreach($premios as $p): ?>
<tr>
    <td><?= $p["nombre"] ?></td>
    <td><?= $p["costo"] ?></td>
    <td><a class="canje-btn" href="premco.php?canjear=<?= $p['id'] ?>">Canjear</a></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</body>
</html>
