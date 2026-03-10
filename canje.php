<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";

// Lista de premios y sus precios en puntos
$premios = [
    "Plantín Aleatorio 🌱" => 150,
    "Platín Frutal 🍊" => 300,
    "Maceta Decorativa 🪴" => 120,
    "1KG de abono 🍄" => 180
];

// Crear tabla premios si no existe
$conn->query("
    CREATE TABLE IF NOT EXISTS premios (
        id INT(11) NOT NULL AUTO_INCREMENT,
        usuario_id INT(11) NOT NULL,
        premio VARCHAR(255) NOT NULL,
        fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )
");

// Obtener puntos actuales
$res = $conn->query("SELECT puntos FROM usuarios WHERE id=$usuario_id");
$puntosActuales = $res->fetch_assoc()['puntos'];

// Canjear puntos
if (isset($_POST['canjear'])) {
    $premioElegido = $_POST['premio'] ?? '';
    
    if (!array_key_exists($premioElegido, $premios)) {
        $mensaje = "<p style='color:red;'>❌ Premio inválido.</p>";
    } elseif ($puntosActuales < $premios[$premioElegido]) {
        $mensaje = "<p style='color:red;'>❌ No tenés suficientes puntos para ese premio.</p>";
    } else {
        $conn->query("INSERT INTO premios (usuario_id, premio) VALUES ($usuario_id, '$premioElegido')");
        // Restar puntos del usuario
        $conn->query("UPDATE usuarios SET puntos = puntos - {$premios[$premioElegido]} WHERE id=$usuario_id");
        $mensaje = "<p style='color:green;'>🎉 Premio: $premioElegido! SACA CAPTURA Y PRESENTALO EN EL JARDÍN BOTÁNICO. [[PENDIENTE  AGREGAR QR]] </p>";  
        $puntosActuales -= $premios[$premioElegido];
    }
}

// Obtener premios canjeados
$resPremios = $conn->query("SELECT * FROM premios WHERE usuario_id=$usuario_id ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Canje de Puntos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h2>Juan Maldonado - Noelia Schefler</h2>
    <h1>🎁 Canje de Puntos</h1>
    <p>Puntos actuales: <?= $puntosActuales ?></p>
    <?= $mensaje ?>

    <form method="post">
        <label for="premio">Elegí tu premio:</label>
        <select name="premio" id="premio" required>
            <option value="">--Seleccioná--</option>
            <?php foreach ($premios as $nombre => $precio): ?>
                <option value="<?= $nombre ?>"><?= $nombre ?> - <?= $precio ?> pts</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="canjear">Canjear Puntos</button>
    </form>

    <h2>Premios canjeados</h2>
    <?php
    if ($resPremios->num_rows > 0) {
        while ($row = $resPremios->fetch_assoc()) {
            echo "<p>🎁 {$row['premio']} - {$row['fecha']}</p>";
        }
    } else {
        echo "<p>No has canjeado premios todavía.</p>";
    }
    ?>

    <br>
    <a href="dashboard.php">⬅ Volver al Dashboard</a>
</div>
</body>
</html>
