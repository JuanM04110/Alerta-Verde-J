<?php
include "db.php";
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Manejo de acciones
// APROBAR ÁRBOL
if (isset($_GET['aprobar'])) {
    $id = $_GET['aprobar'];
    $res = $conn->query("SELECT usuario_id FROM arboles WHERE id=$id");

    if ($res->num_rows) {
        $user = $res->fetch_assoc()['usuario_id'];

        // Aprobar árbol
        $conn->query("UPDATE arboles SET aprobado = 1 WHERE id = $id");

        // Sumar puntos al usuario
        $conn->query("UPDATE usuarios SET puntos = puntos + 10 WHERE id = $user");

        // Registrar en historial (PUNTOS POR ÁRBOL APROBADO)
        $conn->query("
            INSERT INTO historial_puntos (usuario_id, tipo, puntos, descripcion, fecha)
            VALUES ($user, 'arbol_aprobado', 10, 'Puntos otorgados por árbol aprobado', NOW())
        ");
    }

    header("Location: admin.php");
    exit;
}

// RECHAZAR ÁRBOL
if (isset($_GET['rechazar'])) {
    $id = $_GET['rechazar'];
    $conn->query("UPDATE arboles SET aprobado = 2 WHERE id = $id");
    header("Location: admin.php");
    exit;
}

if (isset($_GET['rechazar'])) {
    $id = $_GET['rechazar'];
    $conn->query("UPDATE arboles SET aprobado=2 WHERE id=$id");
}

// Filtrar por estado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';
$where = '';
if($filtro == 'pendiente') $where = "WHERE aprobado=0";
elseif($filtro == 'aprobado') $where = "WHERE aprobado=1";
elseif($filtro == 'rechazado') $where = "WHERE aprobado=2";

$res = $conn->query("SELECT a.*, u.nombre AS usuario_nombre FROM arboles a 
    LEFT JOIN usuarios u ON a.usuario_id = u.id $where ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Árboles</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>
<div class="container">
    <h1>🛠 Panel Admin</h1>
    <h2>Juan Maldonado</h2>
    <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <a href="admin.php">🌳 Árboles</a> | 
            <a href="admin_reci.php">♻️ Reciclaje</a> |
            <a href="admin_plant.php">Plantacion</a> |
            <a href="admin_historial.php">Historial</a>
        </div>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <h2>REGISTROS DE ÁRBOLES</h2>
    <button onclick="mostrarOcultos()">Mostrar ocultos</button>

    <div id="registros">
    <?php while($row = $res->fetch_assoc()): ?>
        <div class="reporte" style="border:1px solid #ccc; padding:10px; margin-top:10px; border-radius:8px;">
            <strong><?= $row['nombre_arbol'] ?></strong> | Estado: <?= ($row['aprobado']==0)?'Pendiente':(($row['aprobado']==1)?'Aprobado':'Rechazado') ?><br>
            Registrado por: <?= $row['usuario_nombre'] ?><br>
            Fecha: <?= $row['fecha'] ?><br>
            <button onclick="verFoto('<?= $row['foto'] ?>')">Ver Foto</button>
            <button onclick="verUbicacion(<?= $row['lat'] ?>, <?= $row['lng'] ?>)">Ver Ubicación</button>
            <?php if($row['aprobado']==0): ?>
                <a href="admin.php?aprobar=<?= $row['id'] ?>">✅ Aprobar</a> |
                <a href="admin.php?rechazar=<?= $row['id'] ?>">❌ Rechazar</a>
            <?php elseif ($row['aprobado']==2): ?>
                <a href="admin.php?aprobar=<?= $row['id'] ?>">♻️ Aprobar de nuevo</a>
            <?php endif; ?>
            <button onclick="ocultarReporte(this)">Ocultar</button>
        </div>
    <?php endwhile; ?>
    </div>

    <h2>FILTROS</h2>
    <a href="admin.php?filtro=pendiente">Pendientes</a> | 
    <a href="admin.php?filtro=aprobado">Aprobados</a> | 
    <a href="admin.php?filtro=rechazado">Rechazados</a> | 
    <a href="admin.php?filtro=todos">Todos</a>

    <div id="map" style="margin-top:20px;"></div>
</div>

<script>
var ocultos = [];

function ocultarReporte(btn){
    var reporte = btn.parentElement;
    ocultos.push(reporte);
    reporte.style.display = 'none';
}

function mostrarOcultos(){
    ocultos.forEach(function(div){
        div.style.display = 'block';
    });
    ocultos = [];
}

function verFoto(url){
    window.open(url,'Foto','width=600,height=400');
}

function verUbicacion(lat,lng){
    map.setView([lat,lng],16);
}

// Inicializar mapa
var map = L.map('map').setView([-27.366, -55.896], 13);;
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}).addTo(map);

<?php
$res2 = $conn->query("SELECT a.*, u.nombre AS usuario_nombre FROM arboles a 
    LEFT JOIN usuarios u ON a.usuario_id = u.id");
while($row2 = $res2->fetch_assoc()) {
    $nombre = addslashes($row2['nombre_arbol']);
    $estado = ($row2['aprobado']==0)?'Pendiente':(($row2['aprobado']==1)?'Aprobado':'Rechazado');
    $usuario = addslashes($row2['usuario_nombre']);
    $lat = $row2['lat'];
    $lng = $row2['lng'];
    echo "L.marker([$lat,$lng], {icon: L.icon({iconUrl:'https://cdn-icons-png.flaticon.com/512/64/64113.png',iconSize:[32,32]})})
          .addTo(map).bindPopup('🌳 $nombre | Estado: $estado | Registrado por: $usuario');\n";
}
?>
</script>
</body>
</html>


