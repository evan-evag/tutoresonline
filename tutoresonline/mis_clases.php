<?php
require_once __DIR__ . '/includes/init.php';
$u   = requiere_login();
$pdo = conectar();
$esProfesor = $u['rol'] === 'profesor';

// Acciones sobre una reserva (cambiar estado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $rid    = (int)($_POST['reserva_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    $nuevo  = ['confirmar' => 'confirmada', 'completar' => 'completada', 'cancelar' => 'cancelada'][$accion] ?? null;

    if ($nuevo) {
        if ($esProfesor) {   // el profesor gestiona las reservas que recibe
            $sql = 'UPDATE reservas v JOIN profesores p ON p.id = v.profesor_id
                    SET v.estado = :s WHERE v.id = :id AND p.usuario_id = :u';
        } elseif ($nuevo === 'cancelada') {   // el estudiante solo puede cancelar las suyas
            $sql = 'UPDATE reservas v SET v.estado = :s WHERE v.id = :id AND v.estudiante_id = :u';
        } else {
            $sql = null;
        }
        if ($sql) {
            $pdo->prepare($sql)->execute([':s' => $nuevo, ':id' => $rid, ':u' => $u['id']]);
            flash('Reserva actualizada.');
        }
    }
    redirigir('mis_clases.php');
}

if ($esProfesor) {
    $st = $pdo->prepare('SELECT v.*, us.nombre AS otro FROM reservas v
        JOIN profesores p ON p.id = v.profesor_id JOIN usuarios us ON us.id = v.estudiante_id
        WHERE p.usuario_id = :u ORDER BY v.fecha DESC, v.hora_inicio DESC');
} else {
    $st = $pdo->prepare('SELECT v.*, us.nombre AS otro, p.id AS prof_id FROM reservas v
        JOIN profesores p ON p.id = v.profesor_id JOIN usuarios us ON us.id = p.usuario_id
        WHERE v.estudiante_id = :u ORDER BY v.fecha DESC, v.hora_inicio DESC');
}
$st->execute([':u' => $u['id']]);
$reservas = $st->fetchAll();

$titulo = 'Mis clases';
include __DIR__ . '/includes/header.php';

function boton_estado(int $id, string $accion, string $texto, string $clase = 'btn btn--claro'): void {
    echo '<form method="post" class="en-linea">' . csrf_campo()
       . '<input type="hidden" name="reserva_id" value="' . $id . '">'
       . '<input type="hidden" name="accion" value="' . $accion . '">'
       . '<button class="' . $clase . '" type="submit">' . $texto . '</button></form> ';
}
?>
<h1>Mis clases</h1>
<?php if (!$reservas): ?>
  <p class="vacio"><?= $esProfesor ? 'Aún no tienes reservas.' : 'Aún no has reservado clases. <a href="buscar.php">Busca un profesor</a>.' ?></p>
<?php else: ?>
  <div class="tabla-caja"><table class="tabla">
    <thead><tr><th><?= $esProfesor ? 'Estudiante' : 'Profesor' ?></th><th>Fecha</th><th>Horario</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reservas as $r): ?>
      <tr>
        <td><?= e($r['otro']) ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
        <td><?= hora_corta($r['hora_inicio']) ?> – <?= hora_corta($r['hora_fin']) ?></td>
        <td><span class="estado estado--<?= e($r['estado']) ?>"><?= e($r['estado']) ?></span></td>
        <td>
          <?php
          $id = (int)$r['id'];
          if ($esProfesor) {
              if ($r['estado'] === 'pendiente')  boton_estado($id, 'confirmar', 'Confirmar', 'btn');
              if ($r['estado'] === 'confirmada') boton_estado($id, 'completar', 'Marcar completada', 'btn');
              if (in_array($r['estado'], ['pendiente', 'confirmada'])) boton_estado($id, 'cancelar', 'Cancelar');
          } else {
              if (in_array($r['estado'], ['pendiente', 'confirmada'])) boton_estado($id, 'cancelar', 'Cancelar');
              if ($r['estado'] === 'completada') echo '<a class="btn btn--claro" href="profesor.php?id=' . (int)$r['prof_id'] . '">Ver / reseñar</a>';
          }
          ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
