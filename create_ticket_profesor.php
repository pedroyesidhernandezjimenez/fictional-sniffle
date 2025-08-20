<?php
session_start();
require 'db.php';

// Verificar que el usuario logueado sea profesor
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='profesor'){
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $tipo = $_POST['tipo_ticket'];
    $profesor_id = $_SESSION['user_id'];

    if($tipo == "requerimiento"){
        $stmt = $pdo->prepare("INSERT INTO tickets_profesor 
            (profesor_id, tipo_ticket, programa, sala_requerimiento, fecha_requerida, descripcion_actividad) 
            VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $profesor_id,
            $tipo,
            $_POST['programa'],
            $_POST['sala_requerimiento'],
            $_POST['fecha_requerida'],
            $_POST['descripcion_actividad']
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tickets_profesor 
            (profesor_id, tipo_ticket, sala_danio, descripcion_problema) 
            VALUES (?,?,?,?)");
        $stmt->execute([
            $profesor_id,
            $tipo,
            $_POST['sala_danio'],
            $_POST['descripcion_problema']
        ]);
    }

    $success = "✅ Ticket creado correctamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear Ticket (Profesor) - UFPS</title>
  <style>
    body {
      font-family: Helvetica, Arial, sans-serif;
      background: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .form-container {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 500px;
    }
    h2 {
      color: #BC0017; /* Color institucional UFPS */
      text-align: center;
      margin-bottom: 20px;
    }
    label {
      display: block;
      font-weight: bold;
      margin-top: 12px;
      color: #333;
    }
    input, textarea, select {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 5px;
      font-size: 1rem;
    }
    textarea { resize: vertical; }
    button {
      margin-top: 20px;
      width: 100%;
      padding: 12px;
      background: #BC0017;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover { background: #990015; }
    .msg { margin-top: 15px; font-weight: bold; text-align: center; }
    .success { color: green; }
    .error { color: red; }
  </style>
  <script>
    function toggleFields(){
      const tipo = document.getElementById("tipo_ticket").value;
      document.getElementById("requerimiento_fields").style.display = (tipo=="requerimiento")?"block":"none";
      document.getElementById("danio_fields").style.display = (tipo=="danio")?"block":"none";
    }
  </script>
</head>
<body>
  <div class="form-container">
    <h2>Crear Ticket (Profesor)</h2>

    <?php if($success): ?>
      <div class="msg success"><?= $success ?></div>
    <?php elseif($error): ?>
      <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="tipo_ticket">Tipo de Ticket:</label>
      <select name="tipo_ticket" id="tipo_ticket" onchange="toggleFields()" required>
        <option value="">-- Seleccione --</option>
        <option value="requerimiento">Requerimiento</option>
        <option value="danio">Error de sala</option>
      </select>

      <!-- Campos Requerimiento -->
      <div id="requerimiento_fields" style="display:none;">
        <label>Nombre del Programa:</label>
        <input type="text" name="programa">

        <label>Sala:</label>
        <input type="text" name="sala_requerimiento">

        <label>Fecha requerida:</label>
        <input type="date" name="fecha_requerida">

        <label>Descripción de la actividad:</label>
        <textarea name="descripcion_actividad" rows="3"></textarea>
      </div>

      <!-- Campos Daño -->
      <div id="danio_fields" style="display:none;">
        <label>Sala:</label>
        <input type="text" name="sala_danio">
        
        <label>Descripción del problema:</label>
        <textarea name="descripcion_problema" rows="3"></textarea>
      </div>

      <button type="submit">Crear Ticket</button>
    </form>
  </div>
</body>
</html>
