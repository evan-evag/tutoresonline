<?php
require_once __DIR__ . '/includes/init.php';
$u   = requiere_login();
$pdo = conectar();
$esProfesor = $u['rol'] === 'profesor';
$dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

$profId = null;
if ($esProfesor) {
    $st = $pdo->prepare('SELECT id FROM profesores WHERE usuario_id = :u');
    $st->execute([':u' => $u['id']]);
    $profId = (int)$st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'perfil') {
            $nombre = trim($_POST['nombre'] ?? '');
            if (mb_strlen($nombre) < 3) throw new RuntimeException('Escribe tu nombre completo.');

            // Foto: subir una nueva (y borrar la anterior) o quitar la actual
            $fotoActual = $u['foto'];
            $fotoNueva  = guardar_foto($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($fotoNueva) {
                borrar_foto($fotoActual);
                $fotoActual = $fotoNueva;
            } elseif (!empty($_POST['quitar_foto'])) {
                borrar_foto($fotoActual);
                $fotoActual = null;
            }
            $pdo->prepare('UPDATE usuarios SET nombre = :n, foto = :f WHERE id = :id')
                ->execute([':n' => $nombre, ':f' => $fotoActual, ':id' => $u['id']]);
            $_SESSION['usuario']['nombre'] = $nombre;
            $_SESSION['usuario']['foto']   = $fotoActual;

            if ($esProfesor) {
                $tarifa = max(0, (int)($_POST['tarifa_hora'] ?? 0));
                $modal  = in_array($_POST['modalidad'] ?? '', ['presencial', 'virtual', 'ambas'], true) ? $_POST['modalidad'] : 'ambas';
                $lat = is_numeric($_POST['latitud'] ?? '')  ? (float)$_POST['latitud']  : null;
                $lng = is_numeric($_POST['longitud'] ?? '') ? (float)$_POST['longitud'] : null;
                $pdo->prepare('UPDATE profesores SET tarifa_hora = :t, descripcion = :d, direccion = :di,
                               latitud = :la, longitud = :lo, modalidad = :m WHERE id = :id')
                    ->execute([':t' => $tarifa, ':d' => trim($_POST['descripcion'] ?? ''), ':di' => trim($_POST['direccion'] ?? ''),
                               ':la' => $lat, ':lo' => $lng, ':m' => $modal, ':id' => $profId]);

                // Materias: se reemplaza la lista completa
                $pdo->prepare('DELETE FROM profesor_materia WHERE profesor_id = :p')->execute([':p' => $profId]);
                $ins = $pdo->prepare('INSERT IGNORE INTO profesor_materia (profesor_id, materia_id) VALUES (:p, :m)');
                foreach ((array)($_POST['materias'] ?? []) as $m) {
                    $ins->execute([':p' => $profId, ':m' => (int)$m]);
                }
            }
            flash('Perfil actualizado.');

        } elseif ($esProfesor && $accion === 'disp_agregar') {
            $dia = (int)($_POST['dia'] ?? 0);
            $hi  = $_POST['hora_inicio'] ?? '';
            $hf  = $_POST['hora_fin'] ?? '';
            if ($dia < 1 || $dia > 7 || !preg_match('/^\d{2}:\d{2}$/', $hi) || !preg_match('/^\d{2}:\d{2}$/', $hf) || $hi >= $hf) {
                throw new RuntimeException('El horario no es válido.');
            }
            $pdo->prepare('INSERT INTO disponibilidad (profesor_id, dia_semana, hora_inicio, hora_fin) VALUES (:p, :d, :hi, :hf)')
                ->execute([':p' => $profId, ':d' => $dia, ':hi' => $hi, ':hf' => $hf]);
            flash('Horario agregado.');

        } elseif ($esProfesor && $accion === 'disp_borrar') {
            $pdo->prepare('DELETE FROM disponibilidad WHERE id = :id AND profesor_id = :p')
                ->execute([':id' => (int)($_POST['id'] ?? 0), ':p' => $profId]);
            flash('Horario eliminado.');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirigir('perfil_editar.php');
}

// Datos actuales para rellenar los formularios
$prof = $mis = $disp = $materias = [];
if ($esProfesor) {
    $st = $pdo->prepare('SELECT * FROM profesores WHERE id = :id');
    $st->execute([':id' => $profId]);
    $prof = $st->fetch();
    $materias = $pdo->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();
    $st = $pdo->prepare('SELECT materia_id FROM profesor_materia WHERE profesor_id = :p');
    $st->execute([':p' => $profId]);
    $mis = $st->fetchAll(PDO::FETCH_COLUMN);
    $st = $pdo->prepare('SELECT * FROM disponibilidad WHERE profesor_id = :p ORDER BY dia_semana, hora_inicio');
    $st->execute([':p' => $profId]);
    $disp = $st->fetchAll();
}

$titulo = 'Mi perfil';
include __DIR__ . '/includes/header.php';
?>
<h1>Mi perfil</h1>
<section class="caja">
  <form method="post" enctype="multipart/form-data" class="form">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="perfil">
    <div class="foto-fila">
      <img class="avatar avatar--l" src="<?= foto_url($_SESSION['usuario']['foto']) ?>" alt="">
      <div>
        <label>Foto de perfil (JPG, PNG o WEBP, máx. 2 MB)
          <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if ($_SESSION['usuario']['foto']): ?>
          <label class="check"><input type="checkbox" name="quitar_foto"> Quitar foto actual</label>
        <?php endif; ?>
      </div>
    </div>
    <label>Nombre <input type="text" name="nombre" value="<?= e($_SESSION['usuario']['nombre']) ?>" required></label>
    <p class="suave">Correo: <?= e($u['correo']) ?></p>

    <?php if ($esProfesor): ?>
      <label>Descripción <textarea name="descripcion" rows="4"><?= e($prof['descripcion']) ?></textarea></label>
      <div class="fila">
        <label>Tarifa por hora (COP) <input type="number" name="tarifa_hora" min="0" step="1000" value="<?= (int)$prof['tarifa_hora'] ?>"></label>
        <label>Modalidad
          <select name="modalidad">
            <?php foreach (['presencial', 'virtual', 'ambas'] as $m): ?>
              <option value="<?= $m ?>" <?= $prof['modalidad'] === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label>Dirección <input type="text" name="direccion" value="<?= e($prof['direccion']) ?>"></label>
      <div class="fila">
        <label>Latitud <input type="text" name="latitud" value="<?= e($prof['latitud']) ?>" placeholder="4.710989"></label>
        <label>Longitud <input type="text" name="longitud" value="<?= e($prof['longitud']) ?>" placeholder="-74.072092"></label>
      </div>
      <fieldset>
        <legend>Materias que dictas</legend>
        <?php foreach ($materias as $m): ?>
          <label class="check"><input type="checkbox" name="materias[]" value="<?= (int)$m['id'] ?>" <?= in_array($m['id'], $mis) ? 'checked' : '' ?>> <?= e($m['nombre']) ?></label>
        <?php endforeach; ?>
      </fieldset>
    <?php endif; ?>
    <button class="btn" type="submit">Guardar cambios</button>
  </form>
</section>

<?php if ($esProfesor): ?>
<section class="caja">
  <h2>Mi disponibilidad</h2>
  <?php if (!$disp): ?><p class="vacio">Agrega los horarios en los que das clase.</p><?php endif; ?>
  <ul class="lista">
    <?php foreach ($disp as $d): ?>
      <li>
        <?= $dias[$d['dia_semana']] ?>: <?= hora_corta($d['hora_inicio']) ?> – <?= hora_corta($d['hora_fin']) ?>
        <form method="post" class="en-linea">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="disp_borrar">
          <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
          <button class="btn btn--claro btn--chico" type="submit">Quitar</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
  <form method="post" class="form fila fila--abajo">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="disp_agregar">
    <label>Día
      <select name="dia"><?php foreach ($dias as $n => $nom): ?><option value="<?= $n ?>"><?= $nom ?></option><?php endforeach; ?></select>
    </label>
    <label>Desde <input type="time" name="hora_inicio" required></label>
    <label>Hasta <input type="time" name="hora_fin" required></label>
    <button class="btn" type="submit">Agregar horario</button>
  </form>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
