<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin'){
  header("Location: login.php"); exit();
}

// ========================
// Tickets Solicitante
// ========================
$stmt = $pdo->prepare("
    SELECT t.*, u.nombre_completo AS solicitante
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE u.role='solicitante'
    ORDER BY t.fecha_creacion DESC
");
$stmt->execute();
$tickets_solicitante = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================
// Tickets Profesor
// ========================
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.nombre_completo AS profesor_nombre, 
           tec.nombre_completo AS tecnico_nombre
    FROM tickets_profesor t
    JOIN users u ON t.profesor_id = u.id
    LEFT JOIN users tec ON t.tecnico_id = tec.id
    ORDER BY t.fecha_creacion DESC
");
$stmt->execute();
$tickets_profesor = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================
// Usuarios
// ========================
$usuarios = $pdo->query("SELECT id, nombre_completo, correo, role FROM users")->fetchAll(PDO::FETCH_ASSOC);

// ========================
// Eliminar usuario
// ========================
if(isset($_POST['delete_user'])){
  $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
  $stmt->execute([$_POST['user_id']]);
  header("Location: admin_dashboard.php");
  exit();
}

// ========================
// Actualizar usuario
// ========================
if(isset($_POST['update_user'])){
  $stmt = $pdo->prepare("UPDATE users SET nombre_completo=?, correo=?, role=? WHERE id=?");
  $stmt->execute([$_POST['nombre_completo'], $_POST['correo'], $_POST['role'], $_POST['user_id']]);
  header("Location: admin_dashboard.php");
  exit();
}

// ========================
// Cambiar contraseña del admin
// ========================
if(isset($_POST['cambiar_pass'])){
    $nueva = password_hash($_POST['nueva_pass'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$nueva, $_SESSION['user_id']]);
    $mensaje_pass = "✅ Contraseña cambiada correctamente.";
}

// ========================
// Estadísticas Generales
// ========================
$stats = $pdo->query("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

$stats_tecnicos = $pdo->query("
  SELECT u.nombre_completo as tecnico, 
         SUM(CASE WHEN t.estado='Pendiente' THEN 1 ELSE 0 END) as pendientes,
         SUM(CASE WHEN t.estado='En Proceso' THEN 1 ELSE 0 END) as en_proceso,
         SUM(CASE WHEN t.estado='Resuelto' THEN 1 ELSE 0 END) as resueltos
  FROM users u
  LEFT JOIN tickets t ON u.id = t.tecnico_id
  WHERE u.role='tecnico'
  GROUP BY u.id
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administración UFPS</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, sans-serif; background:#f4f6f8; color:#333; }
  header { background:#BC0017; color:#fff; padding:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
  header h1 { font-size:1.4rem; }
  nav { display:flex; gap:10px; flex-wrap:wrap; }
  nav button { background:#fff; color:#BC0017; border:none; padding:10px 16px; border-radius:20px; font-weight:bold; cursor:pointer; transition:0.3s; }
  nav button:hover { background:#f0f0f0; }
  .container { padding:20px; max-width:1200px; margin:auto; }
  section { display:none; }
  section.active { display:block; }
  h2 { margin-bottom:15px; color:#BC0017; }
  table { width:100%; border-collapse:collapse; margin-bottom:20px; background:#fff; border-radius:10px; overflow:hidden; }
  th, td { border:1px solid #ddd; padding:10px; text-align:left; font-size:0.9rem; }
  th { background:#BC0017; color:#fff; text-align:center; }
  tr:nth-child(even){ background:#f9f9f9; }
  .acciones button { margin:2px; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-size:0.8rem; }
  .btn-update { background:#0077cc; color:#fff; }
  .btn-delete { background:#e60000; color:#fff; }
  .btn-pdf { background:#BC0017; color:#fff; }
  .charts { display:grid; grid-template-columns: 1fr 1fr; gap:20px; }
  .chart-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 3px 6px rgba(0,0,0,0.1); text-align:center; }
  canvas { max-width:100%; height:auto; }
  .badge { padding:3px 8px; border-radius:8px; color:#fff; font-size:0.8rem; }
  .requerimiento { background:#3498db; }
  .danio { background:#e74c3c; }
  .chip { padding:4px 8px; border-radius:6px; color:#fff; font-weight:bold; font-size:0.85rem; }
  .espera { background:#f1c40f; }
  .proceso { background:#3498db; }
  .solucionado { background:#2ecc71; }

  /* Responsive */
  @media(max-width:768px){
    nav { justify-content:center; }
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr { margin-bottom:15px; border:1px solid #ddd; border-radius:8px; padding:10px; background:#fff; }
    td { display:flex; justify-content:space-between; padding:8px; border:none; border-bottom:1px solid #eee; }
    td:last-child{ border-bottom:none; }
    td::before { content: attr(data-label); font-weight:bold; color:#BC0017; }
    .charts { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<header>
  <h1>Panel de Administración - UFPS</h1>
  <nav>
    <button onclick="showSection('tickets-solicitante')">Tickets Solicitante</button>
    <button onclick="showSection('tickets-profesor')">Tickets Profesores</button>
    <button onclick="showSection('usuarios')">Usuarios</button>
    <button onclick="showSection('estadisticas')">Estadísticas</button>
    <button onclick="showSection('cambiar-pass')">Cambiar Contraseña</button>
    <a href="logout.php" style="background:#fff; color:#e60000; padding:10px 16px; border-radius:20px; text-decoration:none;">Salir</a>
  </nav>
</header>

<div class="container">
  <!-- Tickets Solicitante -->
  <section id="tickets-solicitante" class="active">
    <h2>Tickets Solicitante</h2>
    <table>
      <tr>
        <th>ID</th><th>Solicitante</th><th>Dependencia</th><th>Equipo</th>
        <th>Marca/Modelo</th><th>Inventario</th><th>Estado</th><th>Acciones</th>
      </tr>
      <?php foreach($tickets_solicitante as $t): ?>
      <tr>
        <td data-label="ID"><?= $t['id'] ?></td>
        <td data-label="Solicitante"><?= $t['solicitante'] ?></td>
        <td data-label="Dependencia"><?= $t['nombre_dependencia'] ?></td>
        <td data-label="Equipo"><?= $t['equipo'] ?></td>
        <td data-label="Marca/Modelo"><?= $t['marca_modelo'] ?></td>
        <td data-label="Inventario"><?= $t['numero_inventario'] ?></td>
        <td data-label="Estado"><?= $t['estado'] ?></td>
        <td data-label="Acciones" class="acciones">
          <a href="ticket_pdf.php?id=<?= $t['id'] ?>" target="_blank">
            <button class="btn-pdf">📄 PDF</button>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <!-- Tickets Profesor -->
  <section id="tickets-profesor">
    <h2>Tickets Profesores</h2>
    <table>
      <tr>
        <th>ID</th><th>Tipo</th><th>Detalles</th><th>Profesor</th><th>Técnico</th><th>Estado</th><th>Fecha</th><th>Acción</th><th>Edición</th>
      </tr>
      <?php foreach($tickets_profesor as $p):
        $statusClass = ($p['estado']==='En espera'?'espera':($p['estado']==='En Proceso'?'proceso':'solucionado'));
      ?>
      <tr>
        <td data-label="ID"><?= $p['id'] ?></td>
        <td data-label="Tipo"><?= $p['tipo_ticket']==='requerimiento'?'<span class="badge requerimiento">Requerimiento</span>':'<span class="badge danio">Daño</span>' ?></td>
        <td data-label="Detalles">
          <?php if($p['tipo_ticket']==='requerimiento'): ?>
            <b>Programa:</b> <?= htmlspecialchars($p['programa']) ?><br>
            <b>Sala:</b> <?= htmlspecialchars($p['sala_requerimiento']) ?><br>
            <b>Fecha requerida:</b> <?= htmlspecialchars($p['fecha_requerida']) ?><br>
            <b>Actividad:</b> <?= nl2br(htmlspecialchars($p['descripcion_actividad'])) ?>
          <?php else: ?>
            <b>Sala:</b> <?= htmlspecialchars($p['sala_danio']) ?><br>
            <b>Salón:</b> <?= htmlspecialchars($p['salon']) ?><br>
            <b>Problema:</b> <?= nl2br(htmlspecialchars($p['descripcion_problema'])) ?>
          <?php endif; ?>
        </td>
        <td data-label="Profesor"><?= htmlspecialchars($p['profesor_nombre']) ?></td>
        <td data-label="Técnico"><?= $p['tecnico_nombre'] ?? 'No asignado' ?></td>
        <td data-label="Estado"><span class="chip <?= $statusClass ?>"><?= $p['estado'] ?></span></td>
        <td data-label="Fecha"><?= $p['fecha_creacion'] ?></td>
        <td data-label="Acción">
          <?php if(empty($p['tecnico_id'])): ?>
            <form method="POST"><input type="hidden" name="ticket_id" value="<?= $p['id'] ?>"><button class="btn" type="submit" name="tomar_profesor">Tomar</button></form>
          <?php else: ?>
            <button class="btn" type="button" disabled>Asignado</button>
          <?php endif; ?>
        </td>
        <td data-label="Edición"><em style="color:var(--muted);">Solo lectura</em></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <!-- Usuarios -->
  <section id="usuarios">
    <h2>Administración de Usuarios</h2>
    <table>
      <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr>
      <?php foreach($usuarios as $u): ?>
      <tr>
        <form method="POST">
          <td data-label="ID"><?= $u['id'] ?></td>
          <td data-label="Nombre"><input type="text" name="nombre_completo" value="<?= htmlspecialchars($u['nombre_completo']) ?>"></td>
          <td data-label="Correo"><input type="correo" name="correo" value="<?= htmlspecialchars($u['correo']) ?>"></td>
          <td data-label="Rol">
            <select name="role">
              <option value="solicitante" <?= $u['role']=='solicitante'?'selected':'' ?>>Solicitante</option>
              <option value="profesor" <?= $u['role']=='profesor'?'selected':'' ?>>Profesor</option>
              <option value="tecnico" <?= $u['role']=='tecnico'?'selected':'' ?>>Técnico</option>
              <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
            </select>
          </td>
          <td data-label="Acciones" class="acciones">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button type="submit" name="update_user" class="btn-update">Actualizar</button>
            <button type="submit" name="delete_user" class="btn-delete" onclick="return confirm('¿Eliminar usuario?')">Eliminar</button>
          </td>
        </form>
      </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <!-- Estadísticas -->
  <section id="estadisticas">
    <h2>📊 Estadísticas de Tickets</h2>
    <div class="charts">
      <div class="chart-card">
        <h3>Estado General</h3>
        <canvas id="chartEstados"></canvas>
      </div>
      <div class="chart-card">
        <h3>Rendimiento por Técnico</h3>
        <canvas id="chartTecnicos"></canvas>
      </div>
    </div>
  </section>

  <!-- Cambiar Contraseña -->
  <section id="cambiar-pass">
    <h2>Cambiar Contraseña Admin</h2>
    <?php if(isset($mensaje_pass)) echo "<p style='color:green;'>$mensaje_pass</p>"; ?>
    <form method="POST">
      <label>Nueva Contraseña:</label><br>
      <input type="password" name="nueva_pass" required><br><br>
      <button type="submit" name="cambiar_pass" class="btn-update">Cambiar Contraseña</button>
    </form>
  </section>
</div>

<script>
function showSection(id){
  document.querySelectorAll("section").forEach(s => s.classList.remove("active"));
  document.getElementById(id).classList.add("active");
}

// ChartJS - Tickets por Estado
const ctxEstados = document.getElementById('chartEstados');
new Chart(ctxEstados, {
  type: 'pie',
  data: {
    labels: <?= json_encode(array_keys($stats)) ?>,
    datasets: [{ data: <?= json_encode(array_values($stats)) ?>, backgroundColor: ['#BC0017','#f1c40f','#2ecc71'] }]
  }
});

// ChartJS - Rendimiento Técnicos
const ctxTecnicos = document.getElementById('chartTecnicos');
new Chart(ctxTecnicos, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($stats_tecnicos,'tecnico')) ?>,
    datasets: [
      { label:'Pendientes', data: <?= json_encode(array_column($stats_tecnicos,'pendientes')) ?>, backgroundColor:'#f1c40f' },
      { label:'En Proceso', data: <?= json_encode(array_column($stats_tecnicos,'en_proceso')) ?>, backgroundColor:'#3498db' },
      { label:'Resueltos', data: <?= json_encode(array_column($stats_tecnicos,'resueltos')) ?>, backgroundColor:'#2ecc71' }
    ]
  },
  options:{ responsive:true, plugins:{legend:{position:'bottom'}} }
});
</script>
</body>
</html>
