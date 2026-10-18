<?php
require_once __DIR__ . '/includes/init.php';
$pdo = conectar();
$id  = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT p.*, u.nombre, u.foto,
    COALESCE((SELECT ROUND(AVG(r.calificacion),1) FROM resenas r WHERE r.profesor_id = p.id), 0) AS promedio,
    (SELECT COUNT(*) FROM resenas r WHERE r.profesor_id = p.id) AS total_resenas,
    (SELECT COUNT(*) FROM reservas v WHERE v.profesor_id = p.id AND v.estado = 'completada') AS clases,
    (SELECT GROUP_CONCAT(m.nombre SEPARATOR ', ') FROM profesor_materia pm
       JOIN materias m ON m.id = pm.materia_id WHERE pm.profesor_id = p.id) AS materias
  FROM profesores p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = :id");
$st->execute([':id' => $id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('Profesor no encontrado.'); }

$dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

$st = $pdo->prepare('SELECT dia_semana, hora_inicio, hora_fin FROM disponibilidad WHERE profesor_id = :id ORDER BY dia_semana, hora_inicio');
$st->execute([':id' => $id]);
$disp = $st->fetchAll();

$st = $pdo->prepare('SELECT r.*, u.nombre FROM resenas r JOIN usuarios u ON u.id = r.estudiante_id
                     WHERE r.profesor_id = :id ORDER BY r.creado_en DESC');
$st->execute([':id' => $id]);
$resenas = $st->fetchAll();

// ¿El estudiante tiene una clase completada con este profesor y sin reseña?
$reservaPendiente = null;
$u = usuario_actual();
if ($u && $u['rol'] === 'estudiante') {
    $st = $pdo->prepare("SELECT v.id FROM reservas v LEFT JOIN resenas r ON r.reserva_id = v.id
                         WHERE v.profesor_id = :p AND v.estudiante_id = :e AND v.estado = 'completada' AND r.id IS NULL
                         LIMIT 1");
    $st->execute([':p' => $id, ':e' => $u['id']]);
    $reservaPendiente = $st->fetchColumn();
}

$titulo = $p['nombre'];
include __DIR__ . '/includes/header.php';
?>
<p class="migas"><a href="buscar.php">← Volver a la búsqueda</a></p>
<div class="dos-col">
  <section class="caja">
    <div class="perfil">
      <img class="avatar avatar--l" src="<?= foto_url($p['foto']) ?>" alt="">
      <h1><?= e($p['nombre']) ?></h1>
      <p class="etiqueta"><?= e($p['materias'] ?: 'Sin materias') ?></p>
      <p class="nota"><span class="estrella"><?= estrellas((float)$p['promedio']) ?></span> <?= number_format((float)$p['promedio'], 1) ?> de 5</p>
    </div>
    <div class="datos">
      <div><span class="suave">Tarifa</span><strong><?= pesos($p['tarifa_hora']) ?> / hr</strong></div>
      <div><span class="suave">Clases dictadas</span><strong><?= (int)$p['clases'] ?></strong></div>
      <div><span class="suave">Respuesta</span><strong class="naranja">&lt; <?= (int)$p['respuesta_min'] ?> min</strong></div>
    </div>
    <p><?= nl2br(e($p['descripcion'] ?: 'Este profesor aún no tiene descripción.')) ?></p>
    <p class="suave">Modalidad: <?= e($p['modalidad']) ?><?= $p['direccion'] ? ' · ' . e($p['direccion']) : '' ?></p>

    <h3>Disponibilidad semanal</h3>
    <?php if (!$disp): ?><p class="suave">Sin horarios publicados.</p><?php else: ?>
      <ul class="lista">
        <?php foreach ($disp as $d): ?>
          <li><?= $dias[$d['dia_semana']] ?>: <?= hora_corta($d['hora_inicio']) ?> – <?= hora_corta($d['hora_fin']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h3>Reservar una clase</h3>
    <?php if (!$u): ?>
      <p><a href="login.php">Inicia sesión</a> para reservar.</p>
    <?php elseif ($u['rol'] !== 'estudiante'): ?>
      <p class="suave">Solo los estudiantes pueden reservar clases.</p>
    <?php else: ?>
      <form method="post" action="reservar.php" class="form">
        <?= csrf_campo() ?>
        <input type="hidden" name="profesor_id" value="<?= (int)$p['id'] ?>">
        <label>Fecha <input type="date" name="fecha" min="<?= date('Y-m-d') ?>" required></label>
        <div class="fila">
          <label>Hora inicial <input type="time" name="hora_inicio" required></label>
          <label>Hora final <input type="time" name="hora_fin" required></label>
        </div>
        <button class="btn btn--naranja btn--bloque" type="submit">Reservar clase</button>
      </form>
    <?php endif; ?>
  </section>

  <section>
    <h2>Reseñas de usuarios <span class="insignia"><?= (int)$p['total_resenas'] ?> opiniones</span></h2>

    <?php if ($reservaPendiente): ?>
      <form method="post" action="resena.php" class="caja form">
        <?= csrf_campo() ?>
        <input type="hidden" name="reserva_id" value="<?= (int)$reservaPendiente ?>">
        <h3>Califica tu clase</h3>
        <label>Calificación
          <select name="calificacion">
            <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> <?= $i === 1 ? 'estrella' : 'estrellas' ?></option><?php endfor; ?>
          </select>
        </label>
        <label>Título <input type="text" name="titulo" maxlength="100" required></label>
        <label>Comentario <textarea name="comentario" rows="3"></textarea></label>
        <button class="btn" type="submit">Publicar reseña</button>
      </form>
    <?php endif; ?>

    <?php if (!$resenas): ?>
      <p class="vacio">Aún no hay reseñas.</p>
    <?php endif; ?>
    <?php foreach ($resenas as $r): ?>
      <article class="resena">
        <p><strong><?= e($r['nombre']) ?></strong> <span class="suave"><?= date('d/m/Y', strtotime($r['creado_en'])) ?></span></p>
        <p class="estrella"><?= estrellas((float)$r['calificacion']) ?></p>
        <p><strong><?= e($r['titulo']) ?></strong></p>
        <p><?= nl2br(e($r['comentario'])) ?></p>
      </article>
    <?php endforeach; ?>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
