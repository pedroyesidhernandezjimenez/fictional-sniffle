<?php
require 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nombre_completo = $_POST['nombre_completo'];
    $cargo = $_POST['cargo'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (correo,password,role,nombre_completo,cargo) VALUES (?,?,?,?,?)");
    $stmt->execute([$correo,$password,$role,$nombre_completo,$cargo]);

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro de Usuario - UFPS</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: Helvetica, Arial, sans-serif;
      background: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .register-container {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      padding: 30px 25px;
      width: 100%;
      max-width: 420px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #BC0017; /* Rojo institucional UFPS */
      font-size: 1.7rem;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    label {
      font-size: 0.95rem;
      color: #444;
      font-weight: bold;
    }
    input, select {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      width: 100%;
    }
    input:focus, select:focus {
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
      margin-top: 18px;
      text-align: center;
      font-size: 0.9rem;
    }
    .extra-links a {
      color: #BC0017;
      text-decoration: none;
      font-weight: bold;
    }
    .extra-links a:hover {
      text-decoration: underline;
    }
    @media (max-width: 480px) {
      .register-container {
        padding: 25px 18px;
      }
      h2 { font-size: 1.5rem; }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <h2>Registro de Usuario</h2>

    <form method="POST">
      <div>
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" placeholder="ejemplo@ufps.edu.co" required>
      </div>

      <div>
        <label for="nombre_completo">Nombre completo</label>
        <input type="text" id="nombre_completo" name="nombre_completo" required>
      </div>

      <div>
        <label for="cargo">Cargo</label>
        <select id="cargo" name="cargo" required>
          <option value="">Seleccione su cargo</option>
          <option value="secretario">Secretario</option>
          <option value="director">Director</option>
          <option value="administrativo">Administrativo</option>
          <option value="auxiliar">Auxiliar</option>
          <option value="profesional">Profesional</option>
          <option value="profesor">Profesor</option> 
          <option value="beca_trabajo">beca_trabajo</option>
        </select>
      </div>

      <div>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div>
        <label for="role">Rol</label>
        <select id="role" name="role" required>
          <option value="">Seleccione un rol</option>
          <option value="solicitante">Solicitante</option>
          <option value="profesor">Profesor</option>
          <option value="tecnico">Técnico</option>
          <option value="admin">Administrador</option>
          <option value="beca_trabajo">beca_trabajo</option>
        </select>
      </div>

      <button type="submit">Registrarse</button>
    </form>

    <div class="extra-links">
      <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
  </div>
</body>
</html>
