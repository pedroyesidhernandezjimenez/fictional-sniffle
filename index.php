<?php
session_start();

// Redirigir según rol si ya está autenticado
if(isset($_SESSION['user_id'])){
    if($_SESSION['role']=='solicitante') header("Location: my_tickets.php");
    elseif($_SESSION['role']=='tecnico') header("Location: list_tickets.php");
    elseif($_SESSION['role']=='admin') header("Location: admin_tickets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tickets UFPS - Mantenimiento SC403</title>
  <style>
    /* Reset y box sizing */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: Helvetica, Arial, sans-serif;
      background-color: #ffffff;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 100%;
      text-align: center;
      padding: 30px 20px;
    }
    h1 {
      color: #BC0017; /* Rojo institucional UFPS (R:188, G:0, B:23) */ 
      font-size: 1.9rem;
      margin-bottom: 16px;
    }
    p {
      font-size: 1rem;
      margin-top: 24px;
    }
    a {
      display: inline-block;
      margin: 8px;
      padding: 12px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      color: #ffffff;
      background-color: #BC0017;
      transition: background-color 0.3s ease;
    }
    a:hover {
      background-color: #990015;
    }
    .info {
      font-size: 0.9rem;
      margin-top: 12px;
      color: #666;
    }
    @media (max-width: 480px) {
      .container {
        padding: 25px 15px;
      }
      h1 {
        font-size: 1.6rem;
      }
      a {
        width: 100%;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Sistema de Tickets - UFPS</h1>
    <p>Oficina de Mantenimiento de Cómputo<br>Departamento de Sistemas, Aula Sur SC403</p>
    <p>
      <a href="login.php">Iniciar Sesión</a>
      <a href="register.php">Registrarse</a>
    </p>
    <p class="info">Universidad Francisco de Paula Santander &mdash; Cúcuta</p>
    <p class="info">Oficina de Mantenimiento de Computadores &mdash; ext:342 </p>
  </div>
</body>
</html>
