<?php
$host = "localhost";
$user = "root";
$pass = "juanm123";
$db = "arboles";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
