<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE correo=?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        // Guardar datos en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['cargo'] = $user['cargo'];
        $_SESSION['correo'] = $user['correo'];

        // Actualizar online y último login
        $stmt_online = $pdo->prepare("UPDATE users SET online=1, ultimo_login=NOW() WHERE id=?");
        $stmt_online->execute([$user['id']]);

        // Redirección según role
        if($user['role']=='tecnico') {
            header("Location: list_tickets.php");
        } elseif($user['role']=='solicitante') {
            header("Location: my_tickets.php");
        } elseif($user['role']=='beca_trabajo') {
            header("Location: my_tickets_becatrabajo.php");
        } elseif($user['role']=='profesor') {
            header("Location: mis_tickets_profesor.php");
        } else {
            header("Location: admin_tickets.php");
        }
        exit();
    } else {
        $error = "❌ Correo o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - UFPS</title>
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {font-family:Helvetica, Arial, sans-serif; background:#fff; display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px;}
.login-container {background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.15); padding:30px 25px; width:100%; max-width:380px; text-align:center;}
h2 {margin-bottom:20px; color:#BC0017; font-size:1.7rem;}
form {display:flex; flex-direction:column; gap:15px;}
input {padding:12px; border:1px solid #ccc; border-radius:8px; font-size:1rem; width:100%;}
input:focus {border-color:#BC0017; outline:none; box-shadow:0 0 5px rgba(188,0,23,0.3);}
button {background:#BC0017; color:#fff; padding:12px; border:none; border-radius:8px; font-size:1rem; font-weight:bold; cursor:pointer; transition:background 0.3s;}
button:hover {background:#990015;}
.error {color:#BC0017; margin-top:10px; font-size:0.95rem;}
.extra-links {margin-top:18px; font-size:0.9rem;}
.extra-links a {color:#BC0017; text-decoration:none; font-weight:bold;}
.extra-links a:hover {text-decoration:underline;}
@media(max-width:480px){.login-container{padding:25px 18px;} h2{font-size:1.5rem;}}
</style>
</head>
<body>
<div class="login-container">
    <h2>Iniciar Sesión</h2>

    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="correo" placeholder="Correo institucional" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>

    <div class="extra-links">
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</div>
</body>
</html>
