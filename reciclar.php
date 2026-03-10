<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$mensaje = "";

/*
  Definimos las opciones de canje (material => [ ['label','unidad','cantidad','puntos'], ... ])
  Label: texto para mostrar
  unidad: ejemplo "Botellas", "Kg"
  cantidad: la unidad base (ej: 10 botellas)
  puntos: puntos que se piden por esa cantidad
*/
$options = [
    "Plástico" => [
        ["10 Botellas plásticas", "Botellas", 10, 5],
        ["25 Botellas plásticas", "Botellas", 25, 20],
        
    ],
    "Vidrio" => [
        ["5 Botellas de vidrio", "Botellas", 5, 10],
        ["10 Botellas de vidrio", "Botellas", 10, 25]
    ],
    
];

// Procesar envío de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opcion'])) {
    // valor del form: material|unidad|cantidad|puntos
    $val = $_POST['opcion'];
    list($material, $unidad, $cantidad, $puntos) = explode('|', $val);

    $material = $conn->real_escape_string($material);
    $unidad = $conn->real_escape_string($unidad);
    $cantidad = (int)$cantidad;
    $puntos = (int)$puntos;
    $punto_verde = isset($_POST['punto_verde']) ? $conn->real_escape_string($_POST['punto_verde']) : null;

    $sql = "INSERT INTO reciclaje_requests 
            (usuario_id, material, unidad, cantidad, puntos_solicitados, punto_verde) 
            VALUES ('$usuario_id', '$material', '$unidad', '$cantidad', '$puntos', " . ($punto_verde ? "'$punto_verde'" : "NULL") . ")";
    if ($conn->query($sql)) {
        $mensaje = "<p style='color:green;'>✅ Solicitud enviada. Espera a que un admin la apruebe. (+{$puntos} puntos si se aprueba)</p>";
    } else {
        $mensaje = "<p style='color:red;'>❌ Error al enviar: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reciclar - Enviar solicitud</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    
<div class="container">
    <h2>Juan Maldonado </h2>
    <h1>♻️ Reciclar</h1>
    <p>Elegí la opción que corresponda y enviá la solicitud. Un admin verificará y, si está todo ok, se te sumarán los puntos.</p>

    <?= $mensaje ?>

    <h3>Tabla de conversión (ejemplos)</h3>
    <table style="width:100%; border-collapse:collapse;">
        <tr style="background:#efefef;"><th>Material</th><th>Descripción</th><th>Unidad</th><th>Cantidad</th><th>Puntos</th><th>Elegir</th></tr>
        <?php foreach($options as $mat => $items): ?>
            <?php foreach($items as $it): 
                // $it = [label, unidad, cantidad, puntos]
                $label = $it[0]; $unidad = $it[1]; $cantidad = $it[2]; $pts = $it[3];
                $val = htmlspecialchars("$mat|$unidad|$cantidad|$pts");
            ?>
                <tr>
                    <td style="text-align:center;"><?= $mat ?></td>
                    <td><?= $label ?></td>
                    <td style="text-align:center;"><?= $unidad ?></td>
                    <td style="text-align:center;"><?= $cantidad ?></td>
                    <td style="text-align:center;"><?= $pts ?></td>
                    <td style="text-align:center;"><input type="radio" name="opcion" value="<?= $val ?>" form="formReciclar" required></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>

<form id="formReciclar" method="post" style="margin-top:15px;">
    <label for="punto_verde">Ecopunto (dónde entregás):</label><br>
    <select name="punto_verde" id="punto_verde" required style="width:100%; max-width:400px; padding:8px;">
        <option value="">-- Seleccioná un punto verde --</option>
        <option value="Ecopunto Dolores Sur">Ecopunto Dolores Sur</option>
        <option value="Ecopunto Los Alamos">Ecopunto Los Alamos</option>
        <option value="Ecopunto Barrio Hermoso">Ecopunto Barrio Hermoso</option>
        <option value="Ecopunto La cantera">Ecopunto La Cantera</option>
        <option value="Ecopunto La estación">Ecopunto La Estación</option>
        <option value="Ecopunto Mercado Concetrador">Ecopunto Mercado Concentrador</option>
    </select>
    <br><br>

    <button type="submit">Enviar Solicitud</button>
    <a href="dashboard.php" style="margin-left:10px;">← Volver</a>
</form>

    <hr>
    <h3>Nota</h3>
    <p>La solicitud queda en estado <strong>pendiente</strong>. Un admin la verificará y aprobará (entonces se acreditarán los puntos).</p>
</div>
</body>
</html>
