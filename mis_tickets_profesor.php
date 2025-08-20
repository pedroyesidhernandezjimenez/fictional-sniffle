<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='profesor'){
    header("Location: login.php"); exit();
}

// obtener datos del usuario actual
$stmt = $pdo->prepare("SELECT nombre_completo, cargo, correo FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

// obtener tickets del profesor
$tickets_stmt = $pdo->prepare("SELECT t.*, u.nombre_completo AS tecnico_nombre 
    FROM tickets_profesor t 
    LEFT JOIN users u ON t.tecnico_id=u.id 
    WHERE t.profesor_id=? 
    ORDER BY FIELD(estado,'En espera','En proceso','Solucionado'), fecha_creacion DESC");
$tickets_stmt->execute([$_SESSION['user_id']]);
$tickets_all = $tickets_stmt->fetchAll();

// separar tickets por estado
$tickets_activos = array_filter($tickets_all, fn($t) => $t['estado']!=='Solucionado');
$tickets_solucionados = array_filter($tickets_all, fn($t) => $t['estado']==='Solucionado');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Tickets Profesor - UFPS</title>
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
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}
h2 {
  color: #BC0017;
  font-size: 2rem;
  text-align: center;
}
.user-info {
  background: #f5f5f5;
  padding: 10px 15px;
  border-radius: 8px;
  border: 1px solid #ddd;
  font-size: 0.95rem;
}
.user-info p { margin: 4px 0; }
.actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
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
}
.btn:hover { background: #990015; }
.btn-logout { background: #e60000; border-radius: 30px; }

table {
  width: 100%;
  max-width: 1100px;
  border-collapse: collapse;
  margin-top: 20px;
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
.pdf-link { color: #BC0017; font-weight: bold; text-decoration: none; }
.pdf-link:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 768px) {
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
</style>
<script>
function toggleSolucionados() {
  const sec = document.getElementById('tickets-solucionados');
  sec.style.display = (sec.style.display==='none'?'block':'none');
}
</script>
</head>
<body>
<header>
  <h2>Mis Tickets Profesor</h2>

  <div class="user-info">
    <p><strong>Usuario:</strong> <?= htmlspecialchars($usuario['nombre_completo']) ?></p>
    <p><strong>Cargo:</strong> <?= htmlspecialchars($usuario['cargo']) ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($usuario['correo']) ?></p>
  </div>

  <div class="actions">
    <a href="create_ticket_profesor.php" class="btn">➕ Crear Ticket</a>
    <a href="logout.php" class="btn btn-logout">⏻ Salir</a>
    <button class="btn" onclick="toggleSolucionados()">Ver/Ocultar Solucionados</button>
  </div>
</header>

<!-- Tickets activos -->
<table>
  <thead>
    <tr>
      <th>Tipo</th>
      <th>Detalle</th>
      <th>Estado</th>
      <th>Técnico</th>
      <th>Fecha</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($tickets_activos as $t): ?>
    <tr>
      <td data-label="Tipo"><?= ucfirst($t['tipo_ticket']) ?></td>
      <td data-label="Detalle">
        <?php if($t['tipo_ticket']=='requerimiento'): ?>
          <strong>Programa:</strong> <?= $t['programa'] ?><br>
          <strong>Sala:</strong> <?= $t['sala_requerimiento'] ?><br>
          <strong>Fecha Requerida:</strong> <?= $t['fecha_requerida'] ?><br>
          <strong>Actividad:</strong> <?= $t['descripcion_actividad'] ?>
        <?php else: ?>
          <strong>Sala:</strong> <?= $t['sala_danio'] ?><br>
          <strong>Salón:</strong> <?= $t['salon'] ?><br>
          <strong>Problema:</strong> <?= $t['descripcion_problema'] ?>
        <?php endif; ?>
      </td>
      <td data-label="Estado"><?= $t['estado'] ?> <?= $t['firmado']?'(Firmado)':'' ?></td>
      <td data-label="Técnico"><?= $t['tecnico_nombre'] ?? '-' ?></td>
      <td data-label="Fecha"><?= $t['fecha_creacion'] ?></td>
      <td data-label="Acciones">
        <a href="ticket_profesor_pdf.php?id=<?= $t['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Tickets solucionados -->
<section id="tickets-solucionados" style="display:none; width:100%; max-width:1100px; margin-top:30px;">
  <h3 style="color:#BC0017; margin-bottom:10px;">Tickets Solucionados</h3>
  <table>
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Detalle</th>
        <th>Estado</th>
        <th>Técnico</th>
        <th>Fecha</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($tickets_solucionados as $t): ?>
      <tr>
        <td data-label="Tipo"><?= ucfirst($t['tipo_ticket']) ?></td>
        <td data-label="Detalle">
          <?php if($t['tipo_ticket']=='requerimiento'): ?>
            <strong>Programa:</strong> <?= $t['programa'] ?><br>
            <strong>Sala:</strong> <?= $t['sala_requerimiento'] ?><br>
            <strong>Fecha Requerida:</strong> <?= $t['fecha_requerida'] ?><br>
            <strong>Actividad:</strong> <?= $t['descripcion_actividad'] ?>
          <?php else: ?>
            <strong>Sala:</strong> <?= $t['sala_danio'] ?><br>
            <strong>Salón:</strong> <?= $t['salon'] ?><br>
            <strong>Problema:</strong> <?= $t['descripcion_problema'] ?>
          <?php endif; ?>
        </td>
        <td data-label="Estado"><?= $t['estado'] ?> <?= $t['firmado']?'(Firmado)':'' ?></td>
        <td data-label="Técnico"><?= $t['tecnico_nombre'] ?? '-' ?></td>
        <td data-label="Fecha"><?= $t['fecha_creacion'] ?></td>
        <td data-label="Acciones">
          <a href="ticket_profesor_pdf.php?id=<?= $t['id'] ?>" target="_blank" class="pdf-link">📄 PDF</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

</body>
</html>
