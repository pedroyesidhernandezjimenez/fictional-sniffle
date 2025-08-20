<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='tecnico'){
    header("Location: login.php"); 
    exit();
}

$tecnico_id = $_SESSION['user_id'];

/* ================== ACCIONES: TICKETS NORMALES ================== */

// Tomar ticket normal
if(isset($_POST['tomar_ticket'])){
    $ticket_id = (int)$_POST['ticket_id'];
    $stmt = $pdo->prepare("UPDATE tickets SET tecnico_id=?, estado='En proceso' WHERE id=? AND tecnico_id IS NULL");
    $stmt->execute([$tecnico_id, $ticket_id]);
}

// Liberar ticket normal (devolver a espera)
if(isset($_POST['liberar_ticket'])){
    $ticket_id = (int)$_POST['ticket_id'];
    $stmt = $pdo->prepare("UPDATE tickets SET tecnico_id=NULL, estado='En espera' WHERE id=? AND tecnico_id=?");
    $stmt->execute([$ticket_id, $tecnico_id]);
}

// Actualizar ticket normal
if(isset($_POST['update_ticket'])){
    $id = (int)$_POST['id'];
    $estado = $_POST['estado'];
    $marca_modelo = $_POST['marca_modelo'] ?? null;
    $numero_inventario = $_POST['numero_inventario'] ?? null;
    $solucion = $_POST['solucion'] ?? null;

    $stmt = $pdo->prepare("SELECT firmado, tecnico_id FROM tickets WHERE id=?");
    $stmt->execute([$id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);

    if($t && !$t['firmado'] && (int)$t['tecnico_id'] === $tecnico_id){
        if($estado === 'Solucionado'){
            $stmt = $pdo->prepare("UPDATE tickets 
                SET estado=?, marca_modelo=?, numero_inventario=?, solucion=?, firmado=1, fecha_terminacion=NOW()
                WHERE id=?");
            $stmt->execute([$estado, $marca_modelo, $numero_inventario, $solucion, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tickets 
                SET estado=?, marca_modelo=?, numero_inventario=?, solucion=?
                WHERE id=?");
            $stmt->execute([$estado, $marca_modelo, $numero_inventario, $solucion, $id]);
        }
    }
}

/* ================== ACCIONES: TICKETS PROFESOR ================== */

// Tomar ticket profesor
if(isset($_POST['tomar_profesor'])){
    $ticket_id = (int)$_POST['ticket_id'];
    $stmt = $pdo->prepare("UPDATE tickets_profesor SET tecnico_id=?, estado='En proceso' WHERE id=? AND tecnico_id IS NULL");
    $stmt->execute([$tecnico_id, $ticket_id]);
}

// Liberar ticket profesor
if(isset($_POST['liberar_profesor'])){
    $ticket_id = (int)$_POST['ticket_id'];
    $stmt = $pdo->prepare("UPDATE tickets_profesor SET tecnico_id=NULL, estado='En espera' WHERE id=? AND tecnico_id=?");
    $stmt->execute([$ticket_id, $tecnico_id]);
}

// Actualizar ticket profesor (solo estado/firmado)
if(isset($_POST['update_profesor'])){
    $id = (int)$_POST['id'];
    $estado = $_POST['estado'];

    $stmt = $pdo->prepare("SELECT firmado, tecnico_id FROM tickets_profesor WHERE id=?");
    $stmt->execute([$id]);
    $tp = $stmt->fetch(PDO::FETCH_ASSOC);

    if($tp && !$tp['firmado'] && (int)$tp['tecnico_id'] === $tecnico_id){
        if($estado === 'Solucionado'){
            $stmt = $pdo->prepare("UPDATE tickets_profesor SET estado=?, firmado=1 WHERE id=?");
            $stmt->execute([$estado, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tickets_profesor SET estado=? WHERE id=?");
            $stmt->execute([$estado, $id]);
        }
    }
}

/* ================== CONSULTAS ================== */

// Tickets normales separados por estado
$normales_stmt = $pdo->query("
    SELECT t.*, sol.nombre_completo AS solicitante_nombre, tec.nombre_completo AS tecnico_nombre
    FROM tickets t
    JOIN users sol ON t.user_id = sol.id
    LEFT JOIN users tec ON t.tecnico_id = tec.id
    ORDER BY t.fecha_creacion DESC
");
$tickets_normales = $normales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Tickets profesor separados por estado
$prof_stmt = $pdo->query("
    SELECT tp.*, prof.nombre_completo AS profesor_nombre, tec.nombre_completo AS tecnico_nombre
    FROM tickets_profesor tp
    JOIN users prof ON tp.profesor_id = prof.id
    LEFT JOIN users tec ON tp.tecnico_id = tec.id
    ORDER BY tp.fecha_creacion DESC
");

$tickets_prof = $prof_stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================== BECAS TRABAJO ================== */
$becas_stmt = $pdo->query("
    SELECT id, nombre_completo, online 
    FROM users 
    WHERE role='beca_trabajo'
    ORDER BY nombre_completo
");
$becas_activos = $becas_stmt->fetchAll(PDO::FETCH_ASSOC);


function h($s){ return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Técnico UFPS</title>
<style>
:root{
  --ufps:#BC0017; --ufps-dark:#990015; --ink:#222; --muted:#666;
  --card:#fff; --bg:#f6f6f6;
}
*{box-sizing:border-box;}
body{margin:0; font-family:Helvetica,Arial,sans-serif; background:var(--bg); color:var(--ink);}
.container{width:100%; max-width:1280px; padding:20px;}
header{background:var(--card); border:2px solid #000; border-radius:16px; padding:18px; display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap;}
header h1{margin:0; color:var(--ufps);}
.logout{background:var(--ufps); color:#fff; text-decoration:none; padding:10px 18px; border-radius:28px; font-weight:700; border:2px solid #000;}
.logout:hover{background:var(--ufps-dark);}
section{background:var(--card); border:2px solid #000; border-radius:16px; padding:16px; margin-bottom:18px;}
.sec-title h2{margin:0; font-size:1.2rem; color:#fff; background:var(--ufps); padding:8px 12px; border-radius:10px; display:inline-block; margin-bottom:10px;}
.table{width:100%; border-collapse:collapse; border:2px solid #000; overflow:auto;}
.table th, .table td{border:1px solid #000; padding:10px; vertical-align:top; font-size:.95rem;}
.table th{background:var(--ufps); color:#fff; text-align:center;}
.table tr:hover{background:#f9f2f2;}
.chip{display:inline-block; padding:4px 10px; border-radius:999px; font-size:.85rem; font-weight:700; border:1px solid #000; background:#eee;}
.chip.espera{background:#fff4f4;} .chip.proceso{background:#fff2c2;} .chip.solucionado{background:#d6ffd6;}
.badge{display:inline-block; padding:3px 8px; border-radius:8px; border:1px solid #000; font-weight:700;}
.badge.requerimiento{background:#eaf4ff;} .badge.danio{background:#ffecec;}
.btn{background:var(--ufps); color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer; font-weight:700; border:2px solid #000;}
.btn:hover{background:var(--ufps-dark);}
.btn-ghost{background:#fff; color:var(--ufps); border:2px solid var(--ufps); border-radius:8px; padding:6px 10px; cursor:pointer; font-weight:700;}
.pdf-link{color:var(--ufps); font-weight:700; text-decoration:none;}
.pdf-link:hover{text-decoration:underline;}
input[type="text"], textarea, select{width:100%; padding:8px; border:1px solid #bbb; border-radius:8px; font-size:.95rem;}
textarea{resize:vertical; min-height:70px;}
@media(max-width:900px){.table, .table thead, .table tbody, .table th, .table tr{display:block;}
.table tr{border:2px solid #000; border-radius:12px; margin-bottom:12px; background:#fff; padding:10px;}
.table th{display:none;}
.table td{border:none; border-bottom:1px dashed #ddd; padding:8px 6px; display:flex; justify-content:space-between; gap:12px;}
.table td:last-child{border-bottom:none;}
.table td::before{content:attr(data-label); font-weight:700; color:var(--ufps); min-width:45%; text-align:left;}
.sec-title h2{width:100%; text-align:center;}}
</style>
<script>
function toggleFirmados(sectionId){
  const section = document.getElementById(sectionId);
  const rows = section.querySelectorAll('tr[data-firmado]');
  rows.forEach(r=>r.style.display = r.style.display==='none'?'table-row':'none');
}
</script>
</head>
<body>
<div class="container">
<header>
<h1>Panel Técnico — UFPS</h1>
<a class="logout" href="logout.php">⏻ Salir</a>
</header>

<!-- ===================== TICKETS NORMALES ===================== -->
<section id="tickets-normales">
  <div class="sec-title">
    <h2>Tickets Solicitantes</h2>
    <button class="btn" type="button" onclick="toggleFirmados('tickets-normales')">Ver/Ocultar Firmados</button>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Dependencia / Ubicación / Solicitante</th>
        <th>Equipo / Descripción</th>
        <th>Técnico</th>
        <th>Acción / Estado</th>
        <th>Edición</th>
        <th>PDF</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($tickets_normales as $t): 
        $statusClass = ($t['estado']==='En espera'?'espera':($t['estado']==='En proceso'?'proceso':'solucionado'));
        $puedeEditar = (!$t['firmado'] && (int)$t['tecnico_id'] === $tecnico_id);
      ?>
      <tr data-firmado="<?= $t['firmado'] ? '1':'0' ?>" <?= $t['firmado']?'style="display:none"':'' ?>>
        <td data-label="ID"><?= (int)$t['id'] ?></td>
        <td data-label="Info">
          <b>Dependencia:</b> <?= h($t['nombre_dependencia']) ?><br>
          <b>Ubicación:</b> <?= h($t['ubicacion']) ?><br>
          <b>Solicitante:</b> <?= h($t['solicitante_nombre']) ?>
        </td>
        <td data-label="Equipo/Descripción">
          <b>Equipo:</b> <?= h($t['equipo']) ?><br>
          <b>Descripción:</b> <?= nl2br(h($t['descripcion'])) ?>
        </td>
        <td data-label="Técnico"><?= $t['tecnico_nombre'] ? h($t['tecnico_nombre']) : 'No asignado' ?></td>
        <td data-label="Acción/Estado">
          <span class="chip <?= $statusClass ?>"><?= h($t['estado']) ?><?= $t['firmado'] ? ' (Firmado)' : '' ?></span><br><br>
          <?php if(empty($t['tecnico_id'])): ?>
            <form method="POST">
              <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
              <button class="btn" name="tomar_ticket">Tomar</button>
            </form>
          <?php elseif($puedeEditar): ?>
            <form method="POST">
              <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
              <button class="btn" name="liberar_ticket">Liberar</button>
            </form>
          <?php else: ?>
            <button class="btn-ghost" disabled><?= $puedeEditar?'Asignado a ti':'Asignado' ?></button>
          <?php endif; ?>
        </td>
        <td data-label="Edición">
          <?php if($puedeEditar): ?>
            <details>
              <summary>Editar / Cambiar Estado</summary>
              <form method="POST">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">

                <label>Marca / Modelo</label>
                <input type="text" name="marca_modelo" value="<?= h($t['marca_modelo']) ?>" required>

                <label>Inventario</label>
                <input type="text" name="numero_inventario" value="<?= h($t['numero_inventario']) ?>" required>

                <label>Solución</label>
                <textarea name="solucion" required><?= h($t['solucion']) ?></textarea>

                <label>Estado</label>
                <select name="estado" required>
                  <option value="En espera" <?= $t['estado']==='En espera'?'selected':'' ?>>En espera</option>
                  <option value="En proceso" <?= $t['estado']==='En proceso'?'selected':'' ?>>En proceso</option>
                  <option value="Solucionado" <?= $t['estado']==='Solucionado'?'selected':'' ?>>Solucionado</option>
                </select>

                <div style="margin-top:8px;">
                  <button class="btn" type="submit" name="update_ticket">Actualizar</button>
                </div>
              </form>
            </details>
          <?php else: ?>
            <em style="color:var(--muted);">Solo lectura</em>
          <?php endif; ?>
        </td>
        <td data-label="PDF">
          <a class="pdf-link" href="ticket_pdf.php?id=<?= (int)$t['id'] ?>" target="_blank">📄 PDF</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<script>
function toggleFirmados(seccionId){
  const tabla = document.getElementById(seccionId);
  tabla.querySelectorAll('tr[data-firmado="1"]').forEach(r=>{
    r.style.display = (r.style.display==='none')?'table-row':'none';
  });
}
</script>


<!-- ===================== TICKETS PROFESORES ===================== -->
<section id="tickets-profesor">
  <div class="sec-title">
    <h2>Tickets Profesores</h2>
    <button class="btn" onclick="toggleFirmados('tickets-profesor')">Ver/Ocultar Firmados</button>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>ID</th><th>Tipo</th><th>Detalles</th><th>Profesor</th><th>Técnico</th>
        <th>Estado</th><th>Fecha</th><th>Acción</th><th>Edición</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($tickets_prof as $p):
        $statusClass = ($p['estado']==='En espera'?'espera':($p['estado']==='En proceso'?'proceso':'solucionado'));
        $puedeEditar = (!$p['firmado'] && (int)$p['tecnico_id'] === $tecnico_id);
      ?>
      <tr data-firmado="<?= $p['firmado']?'1':'0' ?>" <?= $p['firmado']?'style="display:none"':'' ?>>
        <td data-label="ID"><?= (int)$p['id'] ?></td>
        <td data-label="Tipo">
          <?php if($p['tipo_ticket']==='requerimiento'): ?>
            <span class="badge requerimiento">Requerimiento</span>
          <?php else: ?>
            <span class="badge danio">Daño</span>
          <?php endif; ?>
        </td>
        <td data-label="Detalles">
          <?php if($p['tipo_ticket']==='requerimiento'): ?>
            <b>Programa:</b> <?= h($p['programa']) ?><br>
            <b>Sala:</b> <?= h($p['sala_requerimiento']) ?><br>
            <b>Fecha requerida:</b> <?= h($p['fecha_requerida']) ?><br>
            <b>Actividad:</b> <?= nl2br(h($p['descripcion_actividad'])) ?>
          <?php else: ?>
            <b>Sala:</b> <?= h($p['sala_danio']) ?><br>
            <b>Salón:</b> <?= h($p['salon']) ?><br>
            <b>Problema:</b> <?= nl2br(h($p['descripcion_problema'])) ?>
          <?php endif; ?>
        </td>
        <td data-label="Profesor"><?= h($p['profesor_nombre']) ?></td>
        <td data-label="Técnico"><?= $p['tecnico_nombre']?h($p['tecnico_nombre']):'No asignado' ?></td>
        <td data-label="Estado"><span class="chip <?= $statusClass ?>"><?= h($p['estado']) ?><?= $p['firmado']?' (Firmado)':'' ?></span></td>
        <td data-label="Fecha"><?= h($p['fecha_creacion']) ?></td>
        <td data-label="Acción">
          <?php if(empty($p['tecnico_id'])): ?>
            <form method="POST"><input type="hidden" name="ticket_id" value="<?= (int)$p['id'] ?>"><button class="btn" name="tomar_profesor">Tomar</button></form>
          <?php elseif($puedeEditar): ?>
            <form method="POST"><input type="hidden" name="ticket_id" value="<?= (int)$p['id'] ?>"><button class="btn" name="liberar_profesor">Liberar</button></form>
          <?php else: ?>
            <button class="btn-ghost" disabled><?= $puedeEditar?'Asignado a ti':'Asignado' ?></button>
          <?php endif; ?>
        </td>
        <td data-label="Edición">
          <?php if($puedeEditar): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <label>Estado</label>
              <select name="estado">
                <option value="En espera" <?= $p['estado']==='En espera'?'selected':'' ?>>En espera</option>
                <option value="En proceso" <?= $p['estado']==='En proceso'?'selected':'' ?>>En proceso</option>
                <option value="Solucionado" <?= $p['estado']==='Solucionado'?'selected':'' ?>>Solucionado</option>
              </select>
              <div style="margin-top:8px;">
                <button class="btn" type="submit" name="update_profesor">Actualizar</button>
              </div>
            </form>
          <?php else: ?>
            <em style="color:var(--muted);">Solo lectura</em>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<section id="becas-trabajo">
  <div class="sec-title">
    <h2>Becas Trabajo</h2>
  </div>
  <div>
    <?php foreach($becas_activos as $b): ?>
      <span class="chip <?= $b['online'] ? 'proceso' : 'espera' ?>">
        <?= h($b['nombre_completo']) ?> — <?= $b['online'] ? '● En línea' : '● Desconectado' ?>
      </span>
    <?php endforeach; ?>
  </div>
</section>

</div>
</body>
</html>
