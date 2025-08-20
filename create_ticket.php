<?php
session_start();
require 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='solicitante'){
    header("Location: login.php"); exit();
}

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id,nombre_dependencia,ubicacion,equipo,descripcion) VALUES (?,?,?,?,?)");
    $stmt->execute([$_SESSION['user_id'],$_POST['dependencia'],$_POST['ubicacion'],$_POST['equipo'],$_POST['descripcion']]);
    header("Location: my_tickets.php?new=1"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear Ticket - UFPS</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: Helvetica, Arial, sans-serif;
      background: #fff;
      padding: 20px;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .ticket-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      padding: 30px 25px;
      width: 100%;
      max-width: 500px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #BC0017;
      font-size: 1.8rem;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    label {
      font-weight: bold;
      font-size: 0.95rem;
      color: #444;
    }
    input, textarea {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      width: 100%;
    }
    textarea {
      resize: vertical;
      min-height: 100px;
    }
    input:focus, textarea:focus {
      border-color: #BC0017;
      outline: none;
      box-shadow: 0 0 5px rgba(188,0,23,0.3);
    }
    button {
      background: #BC0017;
      color: #fff;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #990015;
    }
    .extra-links {
      text-align: center;
      margin-top: 18px;
    }
    .extra-links a {
      color: #BC0017;
      font-weight: bold;
      text-decoration: none;
    }
    .extra-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="ticket-container">
    <h2>Crear Ticket</h2>

    <form method="POST">
      <div>
        <label for="dependencia">Dependencia</label>
        <input type="text" id="dependencia" name="dependencia" required>
      </div>

      <div>
        <label for="ubicacion">Ubicación</label>
        <input type="text" id="ubicacion" name="ubicacion" required>
      </div>

      <div>
        <label for="equipo">Equipo</label>
        <input type="text" id="equipo" name="equipo" required>
      </div>

      <div>
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" required></textarea>
      </div>

      <button type="submit">Crear</button>
    </form>

    <div class="extra-links">
      <p><a href="my_tickets.php">← Ver mis tickets</a></p>
    </div>
  </div>
</body>
</html>
