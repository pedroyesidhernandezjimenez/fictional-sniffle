<?php
session_start();
require 'db.php';

// Solo solicitantes pueden entrar aquí
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='solicitante'){
    header("Location: login.php"); exit();
}

// Obtener info del usuario logueado
$stmt = $pdo->prepare("SELECT nombre_completo, cargo, correo FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Tickets en espera y en proceso
$tickets_activos = $pdo->prepare("SELECT t.*, u.nombre_completo AS tecnico_nombre 
    FROM tickets t 
    LEFT JOIN users u ON t.tecnico_id=u.id 
    WHERE t.user_id=? AND t.estado IN ('En espera','En proceso')
    ORDER BY FIELD(estado,'En espera','En proceso'), fecha_creacion DESC");
$tickets_activos->execute([$_SESSION['user_id']]);
$tickets_activos = $tickets_activos->fetchAll();

// Tickets solucionados
$tickets_solucionados = $pdo->prepare("SELECT t.*, u.nombre_completo AS tecnico_nombre 
    FROM tickets t 
    LEFT JOIN users u ON t.tecnico_id=u.id 
    WHERE t.user_id=? AND t.estado='Solucionado'
    ORDER BY fecha_creacion DESC");
$tickets_solucionados->execute([$_SESSION['user_id']]);
$tickets_solucionados = $tickets_solucionados->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mis Tickets - UFPS</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: Helvetica, Arial, sans-serif;
      background: #fff;
      color: #333;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    header {
      width: 100%;
      max-width: 1100px;
      margin-bottom: 20px;
    }
    h2 {
      color: #BC0017;
      font-size: 2rem;
      text-align: center;
      margin-bottom: 10px;
    }
    .user-info {
      background: #f4f4f4;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 10px 15px;
      margin-bottom: 15px;
      font-size: 0.95rem;
    }
    .user-info strong { color: #BC0017; }
    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }
    .btn {
      background: #BC0017;
      color: #fff;
      padding: 10px 18px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s ease;
      display: inline-block;
      cursor: pointer;
    }
    .btn:hover { background: #990015; }
    .btn-logout { background: #e60000; }
    table {
      width: 100%;
      max-width: 1100px;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 0.95rem;
      border: 2px solid #000;
    }
    th, td {
      padding: 12px 10px;
      border: 1px solid #000;
      text-align: left;
    }
    th {
      background: #BC0017;
      color: #fff;
      font-weight: bold;
      text-align: center;
    }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #f1f1f1; }
    .pdf-link {
      color: #BC0017;
      font-weight: bold;
      text-decoration: none;
    }
    .pdf-link:hover { text-decoration: underline; }
    /* Responsive */
    @media (max-width: 768px) {
      .actions { justify-content: center; }
      table, thead, tbody, th, td, tr { display: block; }
      thead { display: none; }
      tr {
        margin-bottom: 20px;
        border: 2px solid #000;
        border-radius: 10px;
        padding: 10px;
        background: #fff;
      }
      td {
        display: flex;
        justify-content: space-between;
        padding: 8px;
        border: none;
        border-bottom: 1px solid #ddd;
      }
      td:last-child { border-bottom: none; }
      td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #BC0017;
        margin-right: 10px;
      }
    }
    /* Contenedor alternable */
    .tab-content { display: none; width: 100%; max-width: 1100px; }
    .tab-content.active { display: block; }
  </style>
  <script>
    function mostrarSeccion(seccion){
      document.getElementById('activos').classList.remove('active');
      document.getElementById('solucionados').classList.remove('active');
      document.getElementById(seccion).classList.add('active');
    }
  </script>
</head>
<body>
  <header>
    <h2>Mis Tickets</h2>

    <!-- Info del usuario -->
    <div class="user-info">
      <p><strong>Usuario:</strong> <?= htmlspecialchars($usuario['nombre_completo']) ?></p>
      <p><strong>Cargo:</strong> <?= htmlspecialchars($usuario['cargo']) ?></p>
      <p><strong>Correo:</strong> <?= htmlspecialchars($usuario['correo']) ?></p>
    </div>

    <!-- Botones -->
    <div class="actions">
      <a href="create_ticket.php" class="btn">➕ Crear Ticket</a>
      <button class="btn" onclick="mostrarSeccion('activos')">📋 Activos</button>
      <button class="btn" onclick="mostrarSeccion('solucionados')">✅ Solucionados</button>
      <a href="logout.php" class="btn btn-logout">⏻ Salir</a>
    </div>
  </header>

  <!-- Tickets activos -->
  <div id="activos" class="tab-content active">
    <table>
      <thead>
        <tr>
          <th>Dependencia</th>
          <th>Ubicación</th>
          <th>Equipo</th>
          <th>Descripción</th>
          <th>Estado</th>
          <th>Técnico</th>
          <th>Fecha Creación</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tickets_activos as $t): ?>
        <tr>
          <td data-label="Dependencia"><?= htmlspecialchars($t['nombre_dependencia']) ?></td>
          <td data-label="Ubicación"><?= htmlspecialchars($t['ubicacion']) ?></td>
          <td data-label="Equipo"><?= htmlspecialchars($t['equipo']) ?></td>
          <td data-label="Descripción"><?= htmlspecialchars($t['descripcion']) ?></td>
          <td data-label="Estado"><?= htmlspecialchars($t['estado']) ?> <?= $t['firmado']?'(Firmado)':'' ?></td>
          <td data-label="Técnico"><?= $t['tecnico_nombre'] ?? '-' ?></td>
          <td data-label="Fecha"><?= $t['fecha_creacion'] ?></td>
          <td data-label="Acciones">
            <a href="ticket_pdf.php?id=<?= $t['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tickets solucionados -->
  <div id="solucionados" class="tab-content">
    <table>
      <thead>
        <tr>
          <th>Dependencia</th>
          <th>Ubicación</th>
          <th>Equipo</th>
          <th>Descripción</th>
          <th>Estado</th>
          <th>Técnico</th>
          <th>Fecha Creación</th>
          <th>Fecha Firmado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tickets_solucionados as $t): ?>
        <tr>
          <td data-label="Dependencia"><?= htmlspecialchars($t['nombre_dependencia']) ?></td>
          <td data-label="Ubicación"><?= htmlspecialchars($t['ubicacion']) ?></td>
          <td data-label="Equipo"><?= htmlspecialchars($t['equipo']) ?></td>
          <td data-label="Descripción"><?= htmlspecialchars($t['descripcion']) ?></td>
          <td data-label="Estado"><?= htmlspecialchars($t['estado']) ?> <?= $t['firmado']?'(Firmado)':'' ?></td>
          <td data-label="Técnico"><?= $t['tecnico_nombre'] ?? '-' ?></td>
          <td data-label="Fecha"><?= $t['fecha_creacion'] ?></td>
          <td data-label="Fecha Firmado"><?= $t['fecha_firmado'] ?? '-' ?></td>
          <td data-label="Acciones">
            <a href="ticket_pdf.php?id=<?= $t['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</body>
</html>
