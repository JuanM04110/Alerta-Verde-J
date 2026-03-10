<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$resUser = $conn->query("SELECT nombre, puntos FROM usuarios WHERE id=$usuario_id");
$user = $resUser->fetch_assoc();

$mensaje = "";

if (isset($_POST['guardar'])) {
    $nombre_arbol = $_POST['nombre_arbol'];
    $estado = $_POST['estado'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    if (!file_exists("upload")) { mkdir("upload",0777,true); }
    $foto = "upload/" . time() . "_" . basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'], $foto);

    $sql = "INSERT INTO arboles (usuario_id, nombre_arbol, estado, foto, lat, lng, aprobado) 
            VALUES ('$usuario_id', '$nombre_arbol', '$estado', '$foto', '$lat', '$lng', 0)";
    if ($conn->query($sql)) {
        $mensaje = "<p style='color: green;'>🌱 Árbol registrado y pendiente de aprobación!</p>";
    } else {
        $mensaje = "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Alerta Verde Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/map.js"></script>
</head>
<body>
<div class="container">
    <h1>🌳 Alerta Verde</h1>
    <h2>Juan Maldonado </h2>

    <!-- Barra superior con puntos y cerrar sesión -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <div>
            Puntos: <strong><?= $user['puntos'] ?></strong> | 
            <a href="canje.php">[Canjear Puntos]</a> | 
            <a href="reciclar.php">[Reciclar]</a> | 
            <a href="plantacion.php">[Plantacion]</a> |
            <a href="premco.php">[Premios DE CO₂</a> |
            <a href="historial_usuario.php">[Historial]</a>
        </div>
        <div><a href="logout.php">Cerrar sesión</a></div>
    </div>

    <?= $mensaje ?>

    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nombre_arbol" placeholder="Nombre del árbol" required>
        <select name="estado" required>
            <option value="De Pie">De Pie</option>
            <option value="Caído">Caído</option>
        </select>
        <input type="file" name="foto" required>
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">
        <button type="button" onclick="getLocation()">📍 Obtener ubicación</button>
        <button type="submit" name="guardar">Guardar Árbol</button>
    </form>

    <div id="map"></div>
</div>

<script>
// Inicializar mapa
var map = L.map('map').setView([-27.366, -55.896], 13);// Buenos Aires por defecto
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(map);

// Pin de usuario actual
var userMarker;

// Función para obtener ubicación
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            document.getElementById("lat").value = lat;
            document.getElementById("lng").value = lng;

            if (userMarker) { map.removeLayer(userMarker); }
            userMarker = L.marker([lat,lng], {icon: L.icon({iconUrl:'https://cdn-icons-png.flaticon.com/512/64/64113.png',iconSize:[32,32]})})
                .addTo(map).bindPopup("📍 Tú estás aquí").openPopup();
            map.setView([lat,lng], 16);
            alert("Ubicación guardada: " + lat + ", " + lng);
        });
    } else { alert("❌ Geolocalización no soportada"); }
}

// Mostrar árboles aprobados
<?php
$res = $conn->query("SELECT * FROM arboles WHERE aprobado=1");
while($row = $res->fetch_assoc()) {
    $nombre = addslashes($row['nombre_arbol']);
    $estado = $row['estado'];
    $lat = $row['lat'];
    $lng = $row['lng'];
    echo "L.marker([$lat,$lng]).addTo(map).bindPopup('🌳 $nombre | Estado: $estado');\n";
}
?>
</script>
</body>
</html>
