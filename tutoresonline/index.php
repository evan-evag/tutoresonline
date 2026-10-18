<?php
require_once __DIR__ . '/includes/init.php';
$pdo = conectar();

$categorias    = $pdo->query('SELECT DISTINCT categoria FROM materias ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);
$recomendados  = buscar_profesores([], 6);
// Cookie: última materia buscada, para rellenar el formulario
$ultimaMateria = $_COOKIE['ultima_materia'] ?? '';

$titulo = 'Inicio';
include __DIR__ . '/includes/header.php';
?>
<h1>Materias</h1>
<div class="chips">
  <a class="chip chip--on" href="buscar.php">Todas</a>
  <?php foreach ($categorias as $c): ?>
    <a class="chip" href="buscar.php?categoria=<?= urlencode($c) ?>"><?= e($c) ?></a>
  <?php endforeach; ?>
</div>

<section class="caja caja--centro">
  <h2>¿Necesitas un tutor?</h2>
  <form method="get" action="buscar.php" class="form">
    <label>Materia
      <input type="text" name="materia" value="<?= e($ultimaMateria) ?>" placeholder="Ej: Matemáticas, Física...">
    </label>
    <div class="fila">
      <label>Hora inicial <input type="time" name="hora_inicio" value="08:00"></label>
      <label>Hora final <input type="time" name="hora_fin" value="10:00"></label>
    </div>
    <label>Ubicación
      <input type="text" name="ubicacion" placeholder="Ej: Calle 72, Chapinero...">
    </label>
    <button class="btn btn--bloque" type="submit">Buscar profesor</button>
  </form>
</section>

<h2>Recomendaciones</h2>
<div class="rejilla">
  <?php foreach ($recomendados as $p) echo tarjeta_profesor($p); ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
