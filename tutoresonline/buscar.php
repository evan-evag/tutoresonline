<?php
require_once __DIR__ . '/includes/init.php';

$f = [
    'materia'     => trim($_GET['materia'] ?? ''),
    'categoria'   => trim($_GET['categoria'] ?? ''),
    'hora_inicio' => $_GET['hora_inicio'] ?? '',
    'hora_fin'    => $_GET['hora_fin'] ?? '',
    'ubicacion'   => trim($_GET['ubicacion'] ?? ''),
];
// Si las horas no tienen formato HH:MM o están al revés, se ignoran
$horaOk = preg_match('/^\d{2}:\d{2}$/', $f['hora_inicio']) && preg_match('/^\d{2}:\d{2}$/', $f['hora_fin'])
          && $f['hora_inicio'] < $f['hora_fin'];
if (!$horaOk) { $f['hora_inicio'] = $f['hora_fin'] = ''; }

// Cookie: guarda la materia buscada por 30 días
if ($f['materia'] !== '') {
    setcookie('ultima_materia', $f['materia'], [
        'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

$resultados = buscar_profesores($f, 30);

$titulo = 'Buscar profesor';
include __DIR__ . '/includes/header.php';
?>
<h1>Profesores disponibles</h1>
<form method="get" class="filtros">
  <input type="text" name="materia" value="<?= e($f['materia']) ?>" placeholder="Materia">
  <input type="time" name="hora_inicio" value="<?= e($f['hora_inicio']) ?>">
  <input type="time" name="hora_fin" value="<?= e($f['hora_fin']) ?>">
  <input type="text" name="ubicacion" value="<?= e($f['ubicacion']) ?>" placeholder="Ubicación">
  <button class="btn" type="submit">Buscar</button>
  <a class="btn btn--claro" href="buscar.php">Limpiar</a>
</form>

<?php if (!$resultados): ?>
  <p class="vacio">No hay profesores con esos filtros. Prueba con otra materia u otro horario.</p>
<?php else: ?>
  <p class="suave"><?= count($resultados) ?> profesor<?= count($resultados) === 1 ? '' : 'es' ?> encontrado<?= count($resultados) === 1 ? '' : 's' ?></p>
  <div class="rejilla">
    <?php foreach ($resultados as $p) echo tarjeta_profesor($p); ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
