<?php
include "db.php";
session_start();
$mensaje = "";

if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES ('$nombre', '$email', '$password')";
    if ($conn->query($sql)) {
        $mensaje = "<p style='color: green;'>✅ Te has registrado con éxito, ahora inicia sesión 👇</p>";
    } else {
        $mensaje = "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $res = $conn->query("SELECT * FROM usuarios WHERE email='$email'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['rol'] = $row['rol'];
            if ($row['rol'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $mensaje = "<p style='color: red;'>❌ Contraseña incorrecta</p>";
        }
    } else {
        $mensaje = "<p style='color: red;'>❌ Usuario no encontrado</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login / Registro</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="container">
    <h1>🌳 Alerta Verde</h1>
    <h2>Juan Maldonado - Noelia Schefler</h2>
    <?= $mensaje ?>

    <h2>Registrarse</h2>
    <form method="post">
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit" name="registrar">Registrarse</button>
    </form>

    <h2>Iniciar Sesión</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit" name="login">Entrar</button>
    </form>
</div>
</body>
</html>
